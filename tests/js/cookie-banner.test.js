import {describe, test, expect, beforeEach, vi} from 'vitest'
import {COOKIE_NAME}                             from '/js/cookie-consent.js'

/** @param {string} value */
const setCookie = value => { document.cookie = `${COOKIE_NAME}=${value}; Path=/` }
const clearCookie = () => { document.cookie = `${COOKIE_NAME}=; Max-Age=0; Path=/` }

/** cookie-banner.js self-initializes on import (singleton pattern), so a
 * fresh module instance per test — via resetModules + a fresh dynamic
 * import — is the only way to exercise different starting cookie states. */
const loadBanner = async () => {
    vi.resetModules()
    await import('/js/cookie-banner.js')
}

describe('cookie-banner', () => {

    beforeEach(() => {
        clearCookie()
        document.body.replaceChildren()
    })

    test('shows the banner when there is no stored consent', async () => {
        await loadBanner()

        expect(document.querySelector('.cookie-banner')?.classList.contains('is-visible')).toBe(true)
    })

    test('does not show the banner when consent was already given', async () => {
        setCookie('accepted')
        await loadBanner()

        expect(document.querySelector('.cookie-banner')?.classList.contains('is-visible')).toBe(false)
    })

    test('does not show the banner when consent was already declined', async () => {
        setCookie('rejected')
        await loadBanner()

        expect(document.querySelector('.cookie-banner')?.classList.contains('is-visible')).toBe(false)
    })

    test('clicking Accept records consent and hides the banner', async () => {
        await loadBanner()

        const accept = /** @type {HTMLButtonElement} */ (
            document.querySelector('.cookie-banner-button:not(.cookie-banner-button--secondary)')
        )
        accept.click()

        expect(document.cookie).toContain(`${COOKIE_NAME}=accepted`)
        expect(document.querySelector('.cookie-banner')?.classList.contains('is-visible')).toBe(false)
    })

    test('clicking Reject records consent and hides the banner', async () => {
        await loadBanner()

        const reject = /** @type {HTMLButtonElement} */ (
            document.querySelector('.cookie-banner-button--secondary')
        )
        reject.click()

        expect(document.cookie).toContain(`${COOKIE_NAME}=rejected`)
        expect(document.querySelector('.cookie-banner')?.classList.contains('is-visible')).toBe(false)
    })

    test('hides when consent changes elsewhere (e.g. the CookiePreferences section)', async () => {
        await loadBanner()
        expect(document.querySelector('.cookie-banner')?.classList.contains('is-visible')).toBe(true)

        window.dispatchEvent(new CustomEvent('cookieconsentchange', {detail: {consent: 'accepted'}}))

        expect(document.querySelector('.cookie-banner')?.classList.contains('is-visible')).toBe(false)
    })

})
