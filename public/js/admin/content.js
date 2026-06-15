import {escape}   from '/js/core/escape.js'
import PageEditor from '/js/admin/content/page-editor.js'
import CreatePage from '/js/admin/content/create-page.js'
import CopyPage   from '/js/admin/content/copy-page.js'
import MediaView  from '/js/admin/content/media-view.js'

/**
 * @import Router         from '/js/core/router.js'
 * @import Api            from '/js/admin/api.js'
 * @import Loader         from '/js/admin/loader.js'
 * @import Notifier       from '/js/admin/notifier.js'
 * @import SectionFactory from '/js/admin/sections/section-factory.js'
 */

/**
 * Hosts the currently visible view. Listens to the router and swaps the
 * inner view based on the path. Each view is a self-contained component
 * exposing `.element` — Content just clears and appends.
 */
export default class Content {

    /** @type {HTMLElement} */
    #element
    /** @type {Router} */
    #router
    /** @type {Api} */
    #api
    /** @type {SectionFactory} */
    #sectionFactory
    /** @type {Loader} */
    #loader
    /** @type {Notifier} */
    #notifier

    /**
     * @param {Router}         router
     * @param {Api}            api
     * @param {SectionFactory} sectionFactory
     * @param {Loader}         loader
     * @param {Notifier}       notifier
     */
    constructor (router, api, sectionFactory, loader, notifier) {
        this.#router         = router
        this.#api            = api
        this.#sectionFactory = sectionFactory
        this.#loader         = loader
        this.#notifier       = notifier
        this.#element        = document.createElement('main')
        this.#element.id     = 'content'

        router.onChange(path => this.#switchView(path))

        // TODO: Consider the following router improvement:
        // router.onChange({
        //     'admin/pages/{id}': path => this.#switchView(path)
        //     '*': () => this.#defaultView()
        // })
        // And then in the "controller methods":
        // async #switchView (path) {
        //     const id = path.getNumber('id')
        // }
        // So basically mimicking what we do in the PHP router but with just the path for now.
        // We might need query params in the future, so we might either combine it into a Request object,
        // or pass two args - a Path object and a Query object.
        // When a function is passed directly (not an object of functions):
        // router.onChange(path => this.foo(path))
        // it's equivalent to:
        // router.onChange({'*': path => this.foo(path)})
    }

    get element () {
        return this.#element
    }

    /** @param {string} path */
    async #switchView (path) {
        this.#element.replaceChildren()

        if (path === '/admin/pages/new') {
            const view = new CreatePage(this.#api, this.#router, this.#loader, this.#notifier)
            this.#element.append(view.element)
            return
        }

        if (path === '/admin/uploads') {
            const view = new MediaView(this.#api, this.#router, this.#loader, this.#notifier)
            this.#element.append(view.element)
            return
        }

        const copyMatch = path.match(/^\/admin\/pages\/(\d+)\/copy$/)
        if (copyMatch !== null) {
            const sourceId = Number(copyMatch[1])
            const view = new CopyPage(sourceId, this.#api, this.#router, this.#loader, this.#notifier)
            this.#element.append(view.element)
            return
        }

        const pageMatch = path.match(/^\/admin\/pages\/(\d+)$/)
        if (pageMatch !== null) {
            const id      = Number(pageMatch[1])
            const loading = this.#loader.start()
            try {
                const page = await this.#api.getPage(id)
                if (page === null) {
                    this.#element.append(this.#message(`Page #${id} not found`))
                    return
                }
                const view = new PageEditor(page, this.#api, this.#router, this.#sectionFactory, this.#loader, this.#notifier)
                this.#element.append(view.element)
            } catch (error) {
                const message = error instanceof Error ? error.message : String(error)
                this.#notifier.error(message, error)
                this.#element.append(this.#error(`Failed to load page #${id}: ${message}`))
            } finally {
                loading.stop()
            }
            return
        }

        this.#element.append(this.#message('Select a page from the sidebar.', 'Admin'))
    }

    /**
     * @param {string} body
     * @param {string} [title]
     */
    #message (body, title = '') {
        const wrap = document.createElement('div')
        wrap.className = 'placeholder'
        wrap.innerHTML = (title === '' ? '' : `<h2>${escape(title)}</h2>`)
            + `<p>${escape(body)}</p>`
        return wrap
    }

    /** @param {string} message */
    #error (message) {
        const wrap = document.createElement('div')
        wrap.className = 'placeholder error'
        const paragraph = document.createElement('p')
        paragraph.textContent = message
        wrap.appendChild(paragraph)
        return wrap
    }

}
