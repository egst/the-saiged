/**
 * @import Api    from '/js/admin/api.js'
 * @import Router from '/js/core/router.js'
 * @import Loader from '/js/admin/loader.js'
 * @import Notifier from '/js/admin/notifier.js'
 */

/**
 * View for creating a new page. Form with path + title, on submit creates
 * via Api and navigates to the new page's editor.
 */
export default class CreatePage {

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

    /**
     * @param {Api}    api
     * @param {Router} router
     * @param {Loader}   loader
     * @param {Notifier} notifier
     */
    constructor (api, router, loader, notifier) {
        this.#api      = api
        this.#router   = router
        this.#loader   = loader
        this.#notifier = notifier

        this.#pathInput  = document.createElement('input')
        this.#titleInput = document.createElement('input')
        this.#submitButton = document.createElement('button')

        this.#element = this.#build()
    }

    /** @returns {HTMLDivElement} */
    get element () {
        return this.#element
    }

    /** @returns {HTMLDivElement} */
    #build () {
        const root = document.createElement('div')
        root.className = 'create-page'

        const heading = document.createElement('h2')
        heading.className   = 'page-summary-title'
        heading.textContent = 'New page'
        root.append(heading)

        const form = document.createElement('form')
        form.addEventListener('submit', event => {
            event.preventDefault()
            this.#submit()
        })

        const pathLabel = document.createElement('label')
        pathLabel.append('Path')
        this.#pathInput.name        = 'path'
        this.#pathInput.required    = true
        this.#pathInput.placeholder = '/about'
        this.#pathInput.value       = '/'
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
        this.#submitButton.textContent = 'Create'
        actions.append(this.#submitButton)
        form.append(actions)

        root.append(form)
        return root
    }

    async #submit () {
        // Store paths without a leading slash. Empty string means homepage ("/").
        const path  = this.#pathInput.value.trim().replace(/^\/+/, '')
        const title = this.#titleInput.value.trim()
        if (title === '')
            return

        const loading = this.#loader.start()
        this.#submitButton.disabled = true
        try {
            const id = await this.#api.createPage({path, title})
            this.#notifier.success(`Created page ${path === '' ? '/' : `/${path}`}`)
            this.#router.go(`/admin/pages/${id}`)
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Failed to create page'
            this.#notifier.error(message, error)
            this.#submitButton.disabled = false
        } finally {
            loading.stop()
        }
    }

}
