import {isObject}  from '/js/core/types.js'
import PageSummary from '/js/admin/pages/page-summary.js'
import Page        from '/js/admin/pages/page.js'
import Upload      from '/js/admin/uploads/upload.js'

/** @import SectionFactory from '/js/admin/sections/section-factory.js' */

/**
 * Thin fetch wrapper over /api/admin/*. Validates response shapes via the
 * target entity classes' `fromObject` factories.
 *
 * Extends EventTarget so callers (Sidebar, MediaView) can listen for
 * `pages.changed` / `uploads.changed` after mutating operations and
 * refresh.
 */
export default class Api extends EventTarget {

    /** @param {SectionFactory} sectionFactory */
    constructor (sectionFactory) {
        super()
        this.#sectionFactory = sectionFactory
    }

    /** @type {SectionFactory} */
    #sectionFactory

    async listPages () {
        const response = await fetch('/api/admin/pages')
        if (!response.ok)
            throw new Error(await this.#errorMessage(response, 'Failed to load pages'))

        const data = await response.json()
        if (!isObject(data) || !Array.isArray(data.pages))
            throw new Error('listPages: invalid response shape')

        return data.pages.map(page => PageSummary.fromObject(page))
    }

    /** @returns {Promise<{type: string, label: string}[]>} */
    async listSections () {
        const response = await fetch('/api/admin/sections')
        if (!response.ok)
            throw new Error(await this.#errorMessage(response, 'Failed to load section types'))

        const data = await response.json()
        if (!isObject(data) || !Array.isArray(data.sections))
            throw new Error('listSections: invalid response shape')

        return data.sections.map(section => {
            if (!isObject(section) || typeof section.type !== 'string' || typeof section.label !== 'string')
                throw new Error('listSections: invalid section shape')
            return {type: section.type, label: section.label}
        })
    }

    /** @param {number} id */
    async getPage (id) {
        const response = await fetch(`/api/admin/pages/${id}`)
        if (response.status === 404)
            return null
        if (!response.ok)
            throw new Error(await this.#errorMessage(response, `Failed to load page #${id}`))

        const data = await response.json()
        if (!isObject(data))
            throw new Error('getPage: invalid response shape')

        return Page.fromObject(data.page, this.#sectionFactory)
    }

    /** @param {{path: string, title: string}} payload */
    async createPage (payload) {
        const response = await fetch('/api/admin/pages', {
            method:  'POST',
            headers: {'Content-Type': 'application/json'},
            body:    JSON.stringify(payload),
        })
        if (!response.ok)
            throw new Error(await this.#errorMessage(response, 'Failed to create page'))

        const data = await response.json()
        if (!isObject(data) || typeof data.id !== 'number')
            throw new Error('createPage: invalid response shape')

        this.dispatchEvent(new Event('pages.changed'))
        return data.id
    }

    /**
     * @param {number} id
     * @param {{
     *     title:    string,
     *     metaDesc: string | null,
     *     status:   string,
     *     sections: {type: string, data: Record<string, unknown>}[],
     * }} payload
     */
    async putPage (id, payload) {
        const response = await fetch(`/api/admin/pages/${id}`, {
            method:  'PUT',
            headers: {'Content-Type': 'application/json'},
            body:    JSON.stringify(payload),
        })
        if (!response.ok)
            throw new Error(await this.#errorMessage(response, 'Failed to save page'))

        this.dispatchEvent(new Event('pages.changed'))
    }

    /**
     * @param {number}                          id
     * @param {{path: string, title: string}}   payload
     * @returns {Promise<number>}               id of the newly created page
     */
    async copyPage (id, payload) {
        const response = await fetch(`/api/admin/pages/${id}/copy`, {
            method:  'POST',
            headers: {'Content-Type': 'application/json'},
            body:    JSON.stringify(payload),
        })
        if (!response.ok)
            throw new Error(await this.#errorMessage(response, 'Failed to copy page'))

        const data = await response.json()
        if (!isObject(data) || typeof data.id !== 'number')
            throw new Error('copyPage: invalid response shape')

        this.dispatchEvent(new Event('pages.changed'))
        return data.id
    }

    /** @param {number} id */
    async deletePage (id) {
        const response = await fetch(`/api/admin/pages/${id}`, {method: 'DELETE'})
        if (!response.ok)
            throw new Error(await this.#errorMessage(response, 'Failed to delete page'))

        this.dispatchEvent(new Event('pages.changed'))
    }

    /** @returns {Promise<Upload[]>} */
    async listUploads () {
        const response = await fetch('/api/admin/uploads')
        if (!response.ok)
            throw new Error(await this.#errorMessage(response, 'Failed to load uploads'))

        const data = await response.json()
        if (!isObject(data) || !Array.isArray(data.uploads))
            throw new Error('listUploads: invalid response shape')

        return data.uploads.map(upload => Upload.fromObject(upload))
    }

    /**
     * Multipart upload of a single file. Field name is `file` — must match
     * MediaController::createUpload.
     *
     * @param   {File} file
     * @returns {Promise<Upload>}
     */
    async uploadFile (file) {
        const form = new FormData()
        form.append('file', file)

        const response = await fetch('/api/admin/uploads', {
            method: 'POST',
            body:   form,
            // NOTE: do NOT set Content-Type — the browser sets it to
            // `multipart/form-data; boundary=...` with the correct boundary
            // when given a FormData body.
        })
        if (!response.ok)
            throw new Error(await this.#errorMessage(response, 'Failed to upload file'))

        const data = await response.json()
        if (!isObject(data) || !isObject(data.upload))
            throw new Error('uploadFile: invalid response shape')

        this.dispatchEvent(new Event('uploads.changed'))
        return Upload.fromObject(data.upload)
    }

    /** @param {number} id */
    async deleteUpload (id) {
        const response = await fetch(`/api/admin/uploads/${id}`, {method: 'DELETE'})
        if (!response.ok)
            throw new Error(await this.#errorMessage(response, 'Failed to delete upload'))

        this.dispatchEvent(new Event('uploads.changed'))
    }

    /**
     * Ask the backend to generate (if missing) a sized variant of an
     * image upload and return its public URL. Idempotent — the server
     * caches the file on disk, so calling this twice with the same
     * dimensions is cheap on the second call.
     *
     * @param {number} id
     * @param {number} width
     * @param {number} height
     * @returns {Promise<string>}
     */
    async ensureVariant (id, width, height) {
        const response = await fetch(`/api/admin/uploads/${id}/variants`, {
            method:  'POST',
            headers: {'Content-Type': 'application/json'},
            body:    JSON.stringify({width, height}),
        })
        if (!response.ok)
            throw new Error(await this.#errorMessage(response, 'Failed to generate variant'))

        const data = await response.json()
        if (!isObject(data) || typeof data.url !== 'string')
            throw new Error('ensureVariant: invalid response shape')

        return data.url
    }

    /**
     * Best-effort extraction of the backend's user-facing error message
     * from a non-2xx Response. Backend errors follow the shape
     * `{error: "<message>"}`; if parsing fails or the shape is wrong, we
     * fall back to a generic message that includes the status code.
     *
     * @param {Response} response
     * @param {string}   fallback
     * @returns {Promise<string>}
     */
    async #errorMessage (response, fallback) {
        try {
            const data = await response.json()
            if (isObject(data) && typeof data.error === 'string' && data.error !== '')
                return data.error
        } catch {
            // body wasn't JSON / was empty — fall through
        }
        return `${fallback} (HTTP ${response.status})`
    }

}
