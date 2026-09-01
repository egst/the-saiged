/**
 * @typedef {{ title: string, html: string, cssLinks: string[] }} PartialResponse
 */

class OverlayManager {

    /** @type {HTMLDivElement} */
    #panel
    /** @type {HTMLElement} */
    #content
    /** @type {Set<string>} */
    #loadedCss = new Set()
    #isOpen = false

    constructor () {
        this.#panel = document.createElement('div')
        this.#panel.className = 'page-overlay'

        const topbar = document.createElement('div')
        topbar.className = 'page-topbar'

        const kicker = document.createElement('span')
        kicker.className = 'page-kicker'

        const brand = document.createElement('a')
        brand.className = 'page-brand'
        brand.href = '/'
        brand.textContent = 'Saiged'

        const closeBtn = document.createElement('button')
        closeBtn.type = 'button'
        closeBtn.className = 'page-close'
        closeBtn.textContent = 'Close'
        closeBtn.addEventListener('click', () => history.back())

        topbar.append(kicker, brand, closeBtn)

        this.#content = document.createElement('div')
        this.#content.className = 'page-overlay-content'

        this.#panel.append(topbar, this.#content)
        document.body.append(this.#panel)

        window.addEventListener('popstate', e => {
            const url = e.state?.overlay
            if (typeof url === 'string')
                this.#render(url)
            else if (this.#isOpen)
                this.#hide()
        })

        window.addEventListener('keydown', e => {
            if (e.key === 'Escape' && this.#isOpen)
                history.back()
        })
    }

    /** @param {string} url */
    async open (url) {
        const rendered = await this.#render(url)
        if (rendered)
            history.pushState({overlay: url}, '', url)
    }

    /** @param {string} url @return {Promise<boolean>} whether the partial rendered */
    async #render (url) {
        let data
        try {
            const res = await fetch(url, {headers: {'X-Partial': '1'}})
            if (!res.ok)
                throw new Error(`HTTP ${res.status}`)
            data = /** @type {PartialResponse} */ (await res.json())
        } catch {
            window.location.href = url
            return false
        }

        this.#injectCss(data.cssLinks)
        this.#content.innerHTML = data.html
        this.#panel.scrollTop = 0
        document.title = data.title
        this.#show()
        return true
    }

    /** @param {string[]} links */
    #injectCss (links) {
        for (const href of links) {
            if (this.#loadedCss.has(href))
                continue
            this.#loadedCss.add(href)
            const link = document.createElement('link')
            link.rel  = 'stylesheet'
            link.href = href
            document.head.append(link)
        }
    }

    #show () {
        this.#isOpen = true
        this.#panel.classList.add('is-open')
    }

    #hide () {
        this.#isOpen = false
        this.#panel.classList.remove('is-open')
        this.#content.innerHTML = ''
    }

}

export default new OverlayManager()
