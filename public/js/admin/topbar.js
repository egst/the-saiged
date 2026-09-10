/**
 * @import Api      from '/js/admin/api.js'
 * @import Loader   from '/js/admin/loader.js'
 * @import Router   from '/js/core/router.js'
 * @import Notifier from '/js/admin/notifier.js'
 */

/**
 * Top bar of the admin app. Hosts the hamburger toggle (mobile only,
 * visibility controlled by CSS), the brand (links to /admin), the
 * shared loading indicator, and — once logged in — the current admin's
 * email plus a Log out button.
 *
 * The hamburger toggles `body[data-sidebar-open]`. Sidebar visibility on
 * mobile is driven by that attribute via CSS.
 */
export default class Topbar {

    /** @type {HTMLElement} */
    #element

    /**
     * @param {Loader}                    loader
     * @param {Router}                    router
     * @param {Api}                       api
     * @param {Notifier}                  notifier
     * @param {{email: string, role: string}} me
     */
    constructor (loader, router, api, notifier, me) {
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

        const account = document.createElement('span')
        account.id         = 'topbar-account'
        account.textContent = me.email

        const logout = document.createElement('button')
        logout.type        = 'button'
        logout.id          = 'topbar-logout'
        logout.textContent = 'Log out'
        logout.addEventListener('click', async () => {
            try {
                await api.logout()
            } catch (error) {
                notifier.error(error instanceof Error ? error.message : 'Failed to log out', error)
                return
            }
            window.location.reload()
        })

        this.#element.append(hamburger, brand, loader.element, account, logout)
    }

    get element () {
        return this.#element
    }

}
