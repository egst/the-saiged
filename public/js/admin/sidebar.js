import PageTree from '/js/admin/sidebar/page-tree.js'

/**
 * @import Router      from '/js/core/router.js'
 * @import Api         from '/js/admin/api.js'
 * @import Loader      from '/js/admin/loader.js'
 * @import Notifier    from '/js/admin/notifier.js'
 * @import PageSummary from '/js/admin/pages/page-summary.js'
 */

/**
 * Sidebar layout:
 *
 *   [ Uploads ]
 *   [ … other static admin views ]
 *   -----  visual separator  -----
 *   [ + New page ]
 *   [ page tree (dirs + page leaves) ]
 *
 * The top "static" section holds admin views that aren't backed by the
 * dynamic Pages list — Uploads now, header/footer/menu config later.
 * They look like tree leaves visually (same paddings + hover + active
 * styling) but without the indicator dot, since they're not Pages.
 *
 * The bottom section is the live page tree (self-loaded via Api).
 */
export default class Sidebar {

    /** @type {HTMLElement} */
    #element
    /** @type {HTMLElement} */
    #nav
    /** @type {Router} */
    #router
    /** @type {Api} */
    #api
    /** @type {Loader} */
    #loader
    /** @type {Notifier} */
    #notifier
    /** @type {PageTree | null} */
    #tree = null
    /** @type {HTMLAnchorElement[]} */
    #staticLinks = []

    /**
     * @param {Router}   router
     * @param {Api}      api
     * @param {Loader}   loader
     * @param {Notifier} notifier
     */
    constructor (router, api, loader, notifier) {
        this.#router   = router
        this.#api      = api
        this.#loader   = loader
        this.#notifier = notifier

        this.#element    = document.createElement('aside')
        this.#element.id = 'sidebar'

        const staticSection = document.createElement('div')
        staticSection.className = 'sidebar-static'
        staticSection.append(this.#staticLink('/admin/uploads', 'Uploads'))

        const divider = document.createElement('hr')
        divider.className = 'sidebar-divider'

        const newPageLink = document.createElement('a')
        newPageLink.className   = 'new-page'
        newPageLink.href        = '/admin/pages/new'
        newPageLink.textContent = '+ New page'
        newPageLink.addEventListener('click', event => {
            event.preventDefault()
            this.#router.go('/admin/pages/new')
        })

        this.#nav    = document.createElement('nav')
        this.#nav.id = 'pages'

        this.#element.append(staticSection, divider, newPageLink, this.#nav)

        router.onChange(path => this.#markActive(path))
        api.addEventListener('pages.changed', () => this.#load())
        this.#load()
    }

    /** @returns {HTMLElement} */
    get element () {
        return this.#element
    }

    /**
     * Build a static admin link (Uploads, future header/footer/menu).
     * Look-and-feel matches page tree leaves so the sidebar reads as a
     * single coherent list; only the indicator is absent.
     *
     * @param {string} href
     * @param {string} label
     */
    #staticLink (href, label) {
        const anchor = document.createElement('a')
        anchor.className   = 'sidebar-static-link'
        anchor.href        = href
        anchor.textContent = label
        anchor.addEventListener('click', event => {
            event.preventDefault()
            this.#router.go(href)
        })
        this.#staticLinks.push(anchor)
        return anchor
    }

    async #load () {
        // Capture the user's expanded-dirs state before the tree is rebuilt,
        // so a save / create / delete / copy doesn't visually reset the
        // sidebar back to "all collapsed except active path".
        const previousOpen = this.#tree?.openPaths() ?? null
        const loading      = this.#loader.start()
        try {
            const pages = await this.#api.listPages()
            this.#renderPages(pages, previousOpen)
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Failed to load pages'
            this.#notifier.error(message, error)
            this.#renderError(error)
        } finally {
            loading.stop()
        }
    }

    /** @param {unknown} error */
    #renderError (error) {
        const paragraph = document.createElement('p')
        paragraph.className   = 'sidebar-error'
        paragraph.textContent = `Failed to load pages: ${error instanceof Error ? error.message : String(error)}`
        this.#nav.replaceChildren(paragraph)
    }

    /**
     * @param {PageSummary[]}      pages
     * @param {Set<string> | null} restoreOpen   dirs to re-open after rebuild
     */
    #renderPages (pages, restoreOpen = null) {
        this.#tree = new PageTree(pages, this.#router)
        if (restoreOpen !== null)
            this.#tree.restoreOpen(restoreOpen)
        this.#tree.setActive(this.#router.currentPath())
        this.#nav.replaceChildren(this.#tree.element)
    }

    /** @param {string} path */
    #markActive (path) {
        for (const link of this.#staticLinks)
            link.classList.toggle('active', link.getAttribute('href') === path)
        this.#tree?.setActive(path)
    }

}
