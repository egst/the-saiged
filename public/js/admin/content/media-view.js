import {escape} from '/js/core/escape.js'

/**
 * @import Api      from '/js/admin/api.js'
 * @import Router   from '/js/core/router.js'
 * @import Loader   from '/js/admin/loader.js'
 * @import Notifier from '/js/admin/notifier.js'
 * @import Upload   from '/js/admin/uploads/upload.js'
 */

/**
 * Media library view at /admin/uploads. Grid of thumbnails for image
 * uploads (filename fallback for videos), an Upload button that opens a
 * file picker, and a delete control on each tile.
 *
 * Self-loads its data on construction (Api.listUploads). After mutating
 * operations (upload, delete) it re-renders from the API rather than
 * patching the local list — keeps "what's on screen" honest about
 * what's actually on the server.
 */
export default class MediaView {

    /** @type {Api} */
    #api
    /** @type {Router} */
    #router
    /** @type {Loader} */
    #loader
    /** @type {Notifier} */
    #notifier

    /** @type {HTMLDivElement} */
    #element
    /** @type {HTMLDivElement} */
    #grid
    /** @type {HTMLInputElement} */
    #fileInput
    /** @type {HTMLButtonElement} */
    #uploadButton

    /**
     * @param {Api}      api
     * @param {Router}   router
     * @param {Loader}   loader
     * @param {Notifier} notifier
     */
    constructor (api, router, loader, notifier) {
        this.#api      = api
        this.#router   = router
        this.#loader   = loader
        this.#notifier = notifier

        this.#fileInput      = document.createElement('input')
        this.#fileInput.type = 'file'
        this.#fileInput.hidden = true
        this.#fileInput.accept = 'image/*,video/*'
        this.#fileInput.addEventListener('change', () => this.#onFilePicked())

        this.#uploadButton = document.createElement('button')
        this.#uploadButton.type        = 'button'
        this.#uploadButton.className   = 'primary'
        this.#uploadButton.textContent = 'Upload'
        this.#uploadButton.addEventListener('click', () => this.#fileInput.click())

        this.#grid = document.createElement('div')
        this.#grid.className = 'media-grid'

        this.#element = this.#build()
        void this.#load()
    }

    /** @returns {HTMLDivElement} */
    get element () {
        return this.#element
    }

    #build () {
        const root = document.createElement('div')
        root.className = 'media-view'

        const summary = document.createElement('div')
        summary.className = 'page-summary'

        const title = document.createElement('h2')
        title.className   = 'page-summary-title'
        title.textContent = 'Uploads'

        summary.append(title)

        const actions = document.createElement('div')
        actions.className = 'page-actions'
        actions.append(this.#uploadButton, this.#fileInput)

        root.append(summary, actions, this.#grid)
        return root
    }

    async #load () {
        const loading = this.#loader.start()
        try {
            const uploads = await this.#api.listUploads()
            this.#renderGrid(uploads)
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Failed to load uploads'
            this.#notifier.error(message, error)
        } finally {
            loading.stop()
        }
    }

    /** @param {Upload[]} uploads */
    #renderGrid (uploads) {
        this.#grid.replaceChildren()
        if (uploads.length === 0) {
            const empty = document.createElement('p')
            empty.className   = 'placeholder'
            empty.textContent = 'No uploads yet. Click Upload to add the first one.'
            this.#grid.append(empty)
            return
        }
        for (const upload of uploads)
            this.#grid.append(this.#renderTile(upload))
    }

    /** @param {Upload} upload */
    #renderTile (upload) {
        const tile = document.createElement('div')
        tile.className   = 'media-tile'
        tile.dataset.id  = String(upload.id)

        const preview = document.createElement('div')
        preview.className = 'media-tile-preview'
        if (upload.kind === 'image' && upload.thumbUrl !== null) {
            const img = document.createElement('img')
            img.src = upload.thumbUrl
            img.alt = upload.filename
            img.loading = 'lazy'
            preview.append(img)
        } else {
            const placeholder = document.createElement('span')
            placeholder.className   = 'media-tile-kind'
            placeholder.textContent = upload.kind
            preview.append(placeholder)
        }

        const meta = document.createElement('div')
        meta.className = 'media-tile-meta'
        meta.innerHTML = `
            <strong>${escape(upload.filename)}</strong>
            <small>${escape(this.#formatSize(upload.size))}${upload.width !== null ? ` · ${upload.width}×${upload.height}` : ''}</small>
        `

        const remove = document.createElement('button')
        remove.type        = 'button'
        remove.className   = 'media-tile-remove'
        remove.title       = 'Delete upload'
        remove.textContent = '×'
        remove.addEventListener('click', () => this.#onDelete(upload))

        tile.append(preview, meta, remove)
        return tile
    }

    async #onFilePicked () {
        const file = this.#fileInput.files?.[0]
        // Reset input so the same file can be re-picked after an error.
        this.#fileInput.value = ''
        if (file === undefined)
            return

        const loading = this.#loader.start()
        this.#uploadButton.disabled = true
        try {
            const upload = await this.#api.uploadFile(file)
            this.#notifier.success(`Uploaded ${upload.filename}`)
            await this.#load()
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Failed to upload file'
            this.#notifier.error(message, error)
        } finally {
            this.#uploadButton.disabled = false
            loading.stop()
        }
    }

    /** @param {Upload} upload */
    async #onDelete (upload) {
        if (!window.confirm(`Delete ${upload.filename}? This cannot be undone.`))
            return

        const loading = this.#loader.start()
        try {
            await this.#api.deleteUpload(upload.id)
            this.#notifier.success(`Deleted ${upload.filename}`)
            await this.#load()
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Failed to delete upload'
            this.#notifier.error(message, error)
        } finally {
            loading.stop()
        }
    }

    /** @param {number} bytes */
    #formatSize (bytes) {
        if (bytes < 1024)        return `${bytes} B`
        if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
        return `${(bytes / 1024 / 1024).toFixed(1)} MB`
    }

}
