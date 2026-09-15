/**
 * Single source of truth for the "cookie_consent" cookie — the one
 * strictly-necessary cookie this site sets, recording the visitor's choice
 * about every other (optional) cookie. Layout::analyticsTag reads the same
 * cookie server-side (PHP, not this file) to set Google Consent Mode's
 * default before gtag.js loads — keep the cookie name and the
 * 'accepted'/'rejected' values in sync with that.
 *
 * Consumers: cookie-banner.js (the site-wide banner) and the
 * CookiePreferences section (page content) both read/write only through
 * here and react to the 'cookieconsentchange' window event this dispatches,
 * so either one can change the choice and the other stays in sync without
 * knowing the other exists.
 */

export const COOKIE_NAME = 'cookie_consent'

/** ~6 months — long enough to not nag, short enough to re-ask periodically. */
const MAX_AGE_SECONDS = 60 * 60 * 24 * 180

/** @typedef {'accepted' | 'rejected'} Consent */

/** @returns {Consent | null} */
export function getConsent () {
    const match = document.cookie.match(new RegExp(`(?:^|; )${COOKIE_NAME}=([^;]*)`))
    if (match === null)
        return null
    const value = decodeURIComponent(match[1])
    return value === 'accepted' || value === 'rejected' ? value : null
}

/** @param {Consent} value */
export function setConsent (value) {
    document.cookie = `${COOKIE_NAME}=${encodeURIComponent(value)}; Max-Age=${MAX_AGE_SECONDS}; Path=/; SameSite=Lax`
    reportToGtag(value)
    window.dispatchEvent(new CustomEvent('cookieconsentchange', {detail: {consent: value}}))
}

/** @param {Consent} value */
function reportToGtag (value) {
    const gtag = /** @type {{gtag?: (...args: unknown[]) => void}} */ (window).gtag
    if (typeof gtag === 'function')
        gtag('consent', 'update', {analytics_storage: value === 'accepted' ? 'granted' : 'denied'})
}
