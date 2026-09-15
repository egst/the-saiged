import {describe, test, expect, beforeEach, vi} from 'vitest'
import {getConsent, setConsent, COOKIE_NAME}    from '/js/cookie-consent.js'

const clearCookie = () => {
    document.cookie = `${COOKIE_NAME}=; Max-Age=0; Path=/`
}

describe('cookie-consent', () => {

    beforeEach(() => {
        clearCookie()
        // @ts-ignore — test-only stub, not a real gtag
        delete window.gtag
    })

    test('getConsent returns null when no cookie is set', () => {
        expect(getConsent()).toBe(null)
    })

    test('setConsent("accepted") then getConsent round-trips', () => {
        setConsent('accepted')
        expect(getConsent()).toBe('accepted')
    })

    test('setConsent("rejected") then getConsent round-trips', () => {
        setConsent('rejected')
        expect(getConsent()).toBe('rejected')
    })

    test('getConsent ignores an unrelated cookie with a similar prefix', () => {
        document.cookie = `${COOKIE_NAME}_other=accepted; Path=/`
        expect(getConsent()).toBe(null)
    })

    test('setConsent dispatches a cookieconsentchange event with the choice', () => {
        const handler = vi.fn()
        window.addEventListener('cookieconsentchange', handler)

        setConsent('accepted')

        expect(handler).toHaveBeenCalledTimes(1)
        expect(handler.mock.calls[0][0].detail).toEqual({consent: 'accepted'})
    })

    test('setConsent calls window.gtag with the mapped consent state when gtag exists', () => {
        const gtag = vi.fn()
        // @ts-ignore — test-only stub, not a real gtag
        window.gtag = gtag

        setConsent('accepted')
        expect(gtag).toHaveBeenCalledWith('consent', 'update', {analytics_storage: 'granted'})

        setConsent('rejected')
        expect(gtag).toHaveBeenCalledWith('consent', 'update', {analytics_storage: 'denied'})
    })

    test('setConsent does not throw when window.gtag is not defined', () => {
        expect(() => setConsent('accepted')).not.toThrow()
    })

})
