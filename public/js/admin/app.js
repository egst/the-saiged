import Api            from '/js/admin/api.js'
import Router         from '/js/core/router.js'
import Logger         from '/js/core/logger.js'
import Notifier       from '/js/admin/notifier.js'
import Topbar         from '/js/admin/topbar.js'
import Sidebar        from '/js/admin/sidebar.js'
import Content        from '/js/admin/content.js'
import Loader         from '/js/admin/loader.js'
import LoginGate      from '/js/admin/login-gate.js'
import SectionFactory from '/js/admin/sections/section-factory.js'

/**
 * Admin app composition root. Builds the static layout and wires shared
 * services into components. Components self-load their own data — App
 * doesn't orchestrate fetches.
 *
 * Before building the real layout, checks who (if anyone) is logged in
 * (Api#me, backed by the session AuthController::callback sets) — a
 * server-side session is the actual security boundary (every
 * /api/admin/* route is guarded there too); this is just what decides
 * whether the SPA shows the app or a "Sign in with Google" screen.
 */
export default class App {

    async run () {
        const logger         = new Logger()
        const sectionFactory = new SectionFactory()
        const api            = new Api(sectionFactory)
        sectionFactory.setApi(api)

        const me = await api.me().catch(error => {
            logger.error('Failed to check current admin', error)
            return null
        })

        if (me === null) {
            document.body.append(new LoginGate().element)
            return
        }

        this.#runApp(api, sectionFactory, me)
    }

    /**
     * @param {Api}                            api
     * @param {SectionFactory}                 sectionFactory
     * @param {{email: string, role: string}}  me
     */
    #runApp (api, sectionFactory, me) {
        const router   = new Router()
        const notifier = new Notifier(new Logger())
        const loader   = new Loader()

        const topbar  = new Topbar(loader, router, api, notifier, me)
        const sidebar = new Sidebar(router, api, loader, notifier, me.role)
        const content = new Content(router, api, sectionFactory, loader, notifier, me.role)

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
