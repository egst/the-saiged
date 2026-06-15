/**
 * @import Api    from '/js/admin/api.js'
 * @import Router from '/js/core/router.js'
 * @import Loader from '/js/admin/loader.js'
 * @import Notifier from '/js/admin/notifier.js'
 */

/**
 * View for copying a page. Shows the source page's identity and a form
 * with new path + title (title prefilled with "<source> (copy)"). On
 * submit calls Api.copyPage which performs a deep server-side copy and
 * navigates to the new page's editor.
 *
 * The source page is fetched on construction — this view stands alone
 * and can be deep-linked / refreshed without prior state.
 */
export default class CopyPage {

    /** @type {number} */
    #sourceId
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
    /** @type {HTMLInputElement} */
    #pathInput
    /** @type {HTMLInputElement} */
    #titleInput
    /** @type {HTMLButtonElement} */
    #submitButton
    /** @type {HTMLElement} */
    #sourceLabel

    /**
     * @param {number} sourceId
     * @param {Api}    api
     * @param {Router} router
     * @param {Loader}   loader
     * @param {Notifier} notifier
     */
    constructor (sourceId, api, router, loader, notifier) {
        this.#sourceId = sourceId
        this.#api      = api
        this.#router   = router
        this.#loader   = loader
        this.#notifier = notifier

        this.#pathInput    = document.createElement('input')
        this.#titleInput   = document.createElement('input')
        this.#submitButton = document.createElement('button')
        this.#sourceLabel  = document.createElement('p')

        this.#element = this.#build()
        void this.#loadSource()
    }

    /** @returns {HTMLDivElement} */
    get element () {
        return this.#element
    }

    #build () {
        const root = document.createElement('div')
        root.className = 'create-page'

        const heading = document.createElement('h2')
        heading.className   = 'page-summary-title'
        heading.textContent = 'Copy page'

        this.#sourceLabel.className   = 'page-meta'
        this.#sourceLabel.textContent = 'Loading source page…'

        // Wrap heading + source-meta in a .page-summary block so the
        // inner gap is `small` (matching PageEditor's title/meta line),
        // while the outer view keeps its `large` rhythm.
        const summary = document.createElement('div')
        summary.className = 'page-summary'
        summary.append(heading, this.#sourceLabel)
        root.append(summary)

        const form = document.createElement('form')
        form.addEventListener('submit', event => {
            event.preventDefault()
            void this.#submit()
        })

        const pathLabel = document.createElement('label')
        pathLabel.append('Path')
        this.#pathInput.name        = 'path'
        this.#pathInput.required    = true
        this.#pathInput.placeholder = '/about'
        pathLabel.append(this.#pathInput)
        form.append(pathLabel)

        const titleLabel = document.createElement('label')
        titleLabel.append('Title')
        this.#titleInput.name        = 'title'
        this.#titleInput.required    = true
        this.#titleInput.placeholder = 'About us'
        titleLabel.append(this.#titleInput)
        form.append(titleLabel)

        const actions = document.createElement('div')
        actions.className = 'page-actions'
        this.#submitButton.type        = 'submit'
        this.#submitButton.className   = 'primary'
        this.#submitButton.textContent = 'Create copy'
        actions.append(this.#submitButton)
        form.append(actions)

        root.append(form)
        return root
    }

    async #loadSource () {
        const loading = this.#loader.start()
        try {
            const source = await this.#api.getPage(this.#sourceId)
            if (source === null) {
                this.#sourceLabel.textContent = `Source page #${this.#sourceId} not found.`
                this.#submitButton.disabled = true
                return
            }
            const publicPath = `/${source.path.replace(/^\/+/, '')}`
            this.#sourceLabel.textContent = `Copying ${source.title} (${publicPath})`
            this.#pathInput.value  = publicPath
            this.#titleInput.value = `${source.title} (copy)`
            this.#titleInput.select()
        } catch (error) {
            const message = error instanceof Error ? error.message : `Failed to load source page #${this.#sourceId}`
            this.#notifier.error(message, error)
            this.#sourceLabel.textContent = `Failed to load source page #${this.#sourceId}.`
            this.#submitButton.disabled = true
        } finally {
            loading.stop()
        }
    }

    async #submit () {
        // Storage form has no leading slash; strip whatever the user typed.
        const path  = this.#pathInput.value.trim().replace(/^\/+/, '')
        const title = this.#titleInput.value.trim()
        if (path === '' || title === '')
            return

        const loading = this.#loader.start()
        this.#submitButton.disabled = true
        try {
            const id = await this.#api.copyPage(this.#sourceId, {path, title})
            this.#notifier.success(`Copied to /${path}`)
            this.#router.go(`/admin/pages/${id}`)
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Failed to copy page'
            this.#notifier.error(message, error)
            this.#submitButton.disabled = false
        } finally {
            loading.stop()
        }
    }

}
