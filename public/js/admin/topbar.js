/**
 * @import Loader from '/js/admin/loader.js'
 * @import Router from '/js/core/router.js'
 */

/**
 * Top bar of the admin app. Hosts the hamburger toggle (mobile only,
 * visibility controlled by CSS), the brand (links to /admin) and the
 * shared loading indicator.
 *
 * The hamburger toggles `body[data-sidebar-open]`. Sidebar visibility on
 * mobile is driven by that attribute via CSS.
 */
export default class Topbar {

    /** @type {HTMLElement} */
    #element

    /**
     * @param {Loader} loader
     * @param {Router} router
     */
    constructor (loader, router) {
        this.#element    = document.createElement('header')
        this.#element.id = 'topbar'

        const hamburger = document.createElement('button')
        hamburger.type        = 'button'
        hamburger.className   = 'hamburger'
        hamburger.title       = 'Toggle menu'
        hamburger.textContent = '☰'
        hamburger.addEventListener('click', () => {
            document.body.toggleAttribute('data-sidebar-open')
        })

        const brand = document.createElement('a')
        brand.id          = 'brand'
        brand.href        = '/admin'
        brand.textContent = 'The Saiged'
        brand.addEventListener('click', event => {
            event.preventDefault()
            router.go('/admin')
        })

        this.#element.append(hamburger, brand, loader.element)
    }

    get element () {
        return this.#element
    }

}
