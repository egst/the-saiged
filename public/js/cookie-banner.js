import {getConsent, setConsent} from '/js/cookie-consent.js'

/**
 * Site-wide cookie consent banner. Self-contained like OverlayManager/
 * SearchModal (builds its own DOM, appends to document.body once, exports
 * a singleton) but simpler still: no open()/close() API, it just shows
 * itself on construction when there's no stored choice yet, and hides
 * itself the moment one exists — including a choice made elsewhere (e.g.
 * the CookiePreferences section on the same page), via the shared
 * 'cookieconsentchange' event from cookie-consent.js.
 */
class CookieBanner {

    /** @type {HTMLDivElement} */
    #banner

    constructor () {
        this.#banner = this.#build()
        document.body.append(this.#banner)

        window.addEventListener('cookieconsentchange', () => this.#hide())

        if (getConsent() === null)
            this.#show()
    }

    #show () {
        this.#banner.classList.add('is-visible')
        this.#banner.setAttribute('aria-hidden', 'false')
    }

    #hide () {
        this.#banner.classList.remove('is-visible')
        this.#banner.setAttribute('aria-hidden', 'true')
    }

    /** @returns {HTMLDivElement} */
    #build () {
        const banner = document.createElement('div')
        banner.className = 'cookie-banner'
        banner.setAttribute('role', 'dialog')
        banner.setAttribute('aria-modal', 'false')
        banner.setAttribute('aria-hidden', 'true')
        banner.setAttribute('aria-labelledby', 'cookie-banner-text')

        const text = document.createElement('p')
        text.className = 'cookie-banner-text'
        text.id        = 'cookie-banner-text'
        text.textContent = 'We use a necessary cookie to remember your choice and, if you agree, ' +
            'analytics cookies to understand how visitors use this site. See our '
        const link = document.createElement('a')
        link.href = '/cookies'
        link.textContent = 'Cookie Preferences'
        text.append(link, '.')

        const actions = document.createElement('div')
        actions.className = 'cookie-banner-actions'

        const accept = document.createElement('button')
        accept.type      = 'button'
        accept.className = 'cookie-banner-button'
        accept.textContent = 'Accept'
        accept.addEventListener('click', () => setConsent('accepted'))

        const reject = document.createElement('button')
        reject.type      = 'button'
        reject.className = 'cookie-banner-button cookie-banner-button--secondary'
        reject.textContent = 'Reject'
        reject.addEventListener('click', () => setConsent('rejected'))

        actions.append(accept, reject)
        banner.append(text, actions)
        return banner
    }

}

export default new CookieBanner()
