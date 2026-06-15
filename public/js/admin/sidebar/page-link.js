import {escape} from '/js/core/escape.js'

/**
 * @import Router      from '/js/core/router.js'
 * @import PageSummary from '/js/admin/pages/page-summary.js'
 */

/**
 * Sidebar entry for a single page. Owns its DOM element, click behavior
 * (navigates via the router) and active state toggle.
 */
export default class PageLink {

    /** @type {PageSummary} */
    #page
    /** @type {Router} */
    #router
    /** @type {HTMLAnchorElement} */
    #element

    /**
     * @param {PageSummary} page
     * @param {Router}      router
     */
    constructor (page, router) {
        this.#page    = page
        this.#router  = router
        this.#element = this.#build()
    }

    /** @returns {HTMLAnchorElement} */
    get element () {
        return this.#element
    }

    /** @returns {string} */
    get href () {
        return this.#element.getAttribute('href') ?? ''
    }

    /** @param {boolean} active */
    setActive (active) {
        this.#element.classList.toggle('active', active)
    }

    /** @returns {HTMLAnchorElement} */
    #build () {
        const anchor = document.createElement('a')
        anchor.href           = `/admin/pages/${this.#page.id}`
        anchor.dataset.status = this.#page.status
        const publicPath = `/${this.#page.path.replace(/^\/+/, '')}`
        anchor.innerHTML = `
            ${escape(this.#page.title)}
            <small>${escape(publicPath)}</small>
        `
        anchor.addEventListener('click', event => this.#onClick(event))
        return anchor
    }

    /** @param {MouseEvent} event */
    #onClick (event) {
        event.preventDefault()
        this.#router.go(this.href)
    }

}
