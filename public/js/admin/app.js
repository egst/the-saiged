import Api            from '/js/admin/api.js'
import Router         from '/js/core/router.js'
import Logger         from '/js/core/logger.js'
import Notifier       from '/js/admin/notifier.js'
import Topbar         from '/js/admin/topbar.js'
import Sidebar        from '/js/admin/sidebar.js'
import Content        from '/js/admin/content.js'
import Loader         from '/js/admin/loader.js'
import SectionFactory from '/js/admin/sections/section-factory.js'

/**
 * Admin app composition root. Builds the static layout and wires shared
 * services into components. Components self-load their own data — App
 * doesn't orchestrate fetches.
 */
export default class App {

    run () {
        const router         = new Router()
        const logger         = new Logger()
        const notifier       = new Notifier(logger)
        const loader         = new Loader()
        const sectionFactory = new SectionFactory()
        const api            = new Api(sectionFactory)
        sectionFactory.setApi(api)

        const topbar  = new Topbar(loader, router)
        const sidebar = new Sidebar(router, api, loader, notifier)
        const content = new Content(router, api, sectionFactory, loader, notifier)

        const overlay = document.createElement('div')
        overlay.id = 'sidebar-overlay'
        overlay.addEventListener('click', () => {
            document.body.removeAttribute('data-sidebar-open')
        })

        document.body.append(
            topbar.element,
            sidebar.element,
            content.element,
            overlay,
        )

        router.onChange(() => {
            document.body.removeAttribute('data-sidebar-open')
        })

        router.fire()
    }

}
