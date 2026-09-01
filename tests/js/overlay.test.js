import {describe, test, expect, beforeEach, vi} from 'vitest'
import overlay                                   from '/js/overlay.js'

/** @param {{ok: boolean, status?: number, body?: unknown}} opts */
const fakeResponse = ({ok, status = ok ? 200 : 500, body}) =>
    /** @type {Response} */ (/** @type {unknown} */ ({
        ok,
        status,
        json: async () => body,
    }))

const partial = (overrides = {}) => ({
    title:    'A Page',
    html:     '<p>page content</p>',
    cssLinks: [],
    ...overrides,
})

describe('OverlayManager', () => {

    beforeEach(() => {
        vi.restoreAllMocks()
        // Force-close via the same path popstate uses, regardless of prior test state.
        window.dispatchEvent(new PopStateEvent('popstate', {state: null}))
        history.replaceState(null, '', '/')
    })

    test('renders partial html into the panel and opens it', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(fakeResponse({ok: true, body: partial()})))

        await overlay.open('/about')

        const panel = document.querySelector('.page-overlay')
        expect(panel?.classList.contains('is-open')).toBe(true)
        expect(document.querySelector('.page-overlay-content')?.innerHTML).toBe('<p>page content</p>')
        expect(document.title).toBe('A Page')
    })

    test('requests the partial with the X-Partial header', async () => {
        const fetchMock = vi.fn().mockResolvedValue(fakeResponse({ok: true, body: partial()}))
        vi.stubGlobal('fetch', fetchMock)

        await overlay.open('/about')

        expect(fetchMock).toHaveBeenCalledWith('/about', {headers: {'X-Partial': '1'}})
    })

    test('injects each css link exactly once', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
            fakeResponse({ok: true, body: partial({cssLinks: ['/sections/Text/only-this-test.css']})})
        ))

        await overlay.open('/about')
        await overlay.open('/studio')

        expect(document.head.querySelectorAll('link[href="/sections/Text/only-this-test.css"]').length).toBe(1)
    })

    test('pushes history state carrying the overlay url', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(fakeResponse({ok: true, body: partial()})))
        const pushSpy = vi.spyOn(history, 'pushState')

        await overlay.open('/about')

        expect(pushSpy).toHaveBeenCalledWith({overlay: '/about'}, '', '/about')
    })

    test('falls back to a full navigation when the fetch fails', async () => {
        vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new Error('network down')))
        // happy-dom's window.location has no writable href setter for assignment checks; stub it.
        let assignedHref
        Object.defineProperty(window, 'location', {
            value:        {set href (url) { assignedHref = url }},
            configurable: true,
        })

        await overlay.open('/about')

        expect(assignedHref).toBe('/about')
        expect(document.querySelector('.page-overlay')?.classList.contains('is-open')).toBe(false)
    })

    test('falls back to a full navigation on a non-ok response', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(fakeResponse({ok: false, status: 404})))
        let assignedHref
        Object.defineProperty(window, 'location', {
            value:        {set href (url) { assignedHref = url }},
            configurable: true,
        })

        await overlay.open('/missing')

        expect(assignedHref).toBe('/missing')
    })

    test('clears the panel content when popstate carries no overlay state', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(fakeResponse({ok: true, body: partial()})))
        await overlay.open('/about')

        window.dispatchEvent(new PopStateEvent('popstate', {state: null}))

        const panel = document.querySelector('.page-overlay')
        expect(panel?.classList.contains('is-open')).toBe(false)
        expect(document.querySelector('.page-overlay-content')?.innerHTML).toBe('')
    })

    test('re-renders a different partial when popstate carries an overlay url (forward navigation)', async () => {
        const fetchMock = vi.fn()
            .mockResolvedValueOnce(fakeResponse({ok: true, body: partial({title: 'About', html: '<p>about</p>'})}))
            .mockResolvedValueOnce(fakeResponse({ok: true, body: partial({title: 'Studio', html: '<p>studio</p>'})}))
        vi.stubGlobal('fetch', fetchMock)

        await overlay.open('/about')
        window.dispatchEvent(new PopStateEvent('popstate', {state: {overlay: '/studio'}}))
        await vi.waitFor(() => {
            expect(document.querySelector('.page-overlay-content')?.innerHTML).toBe('<p>studio</p>')
        })

        const panel = document.querySelector('.page-overlay')
        expect(panel?.classList.contains('is-open')).toBe(true)
    })

    test('Escape closes the panel via history.back', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(fakeResponse({ok: true, body: partial()})))
        await overlay.open('/about')
        const backSpy = vi.spyOn(history, 'back').mockImplementation(() => {})

        window.dispatchEvent(new KeyboardEvent('keydown', {key: 'Escape'}))

        expect(backSpy).toHaveBeenCalled()
    })

    test('Escape does nothing while the panel is closed', () => {
        const backSpy = vi.spyOn(history, 'back').mockImplementation(() => {})

        window.dispatchEvent(new KeyboardEvent('keydown', {key: 'Escape'}))

        expect(backSpy).not.toHaveBeenCalled()
    })

})
