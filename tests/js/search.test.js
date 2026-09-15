import {describe, test, expect, beforeEach, afterEach, vi} from 'vitest'
import search                                              from '/js/search.js'

/** @param {{ok: boolean, status?: number, body?: unknown}} opts */
const fakeResponse = ({ok, status = ok ? 200 : 500, body}) =>
    /** @type {Response} */ (/** @type {unknown} */ ({
        ok,
        status,
        json: async () => body,
    }))

describe('SearchModal', () => {

    beforeEach(() => {
        vi.restoreAllMocks()
        search.close()
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(fakeResponse({ok: true, body: {suggestions: []}})))
    })

    afterEach(() => {
        vi.useRealTimers()
    })

    test('open() shows the modal and focuses the input', () => {
        search.open()

        expect(document.body.classList.contains('search-modal-open')).toBe(true)
        expect(document.activeElement).toBe(document.querySelector('.search-modal-input'))
    })

    test('close() hides the modal', () => {
        search.open()
        search.close()

        expect(document.body.classList.contains('search-modal-open')).toBe(false)
    })

    test('Escape closes the modal while open', () => {
        search.open()

        window.dispatchEvent(new KeyboardEvent('keydown', {key: 'Escape'}))

        expect(document.body.classList.contains('search-modal-open')).toBe(false)
    })

    test('clicking the backdrop closes the modal', () => {
        search.open()

        const backdrop = /** @type {HTMLElement} */ (document.querySelector('.search-modal-backdrop'))
        backdrop.click()

        expect(document.body.classList.contains('search-modal-open')).toBe(false)
    })

    test('open() clears any previous query and results', () => {
        search.open()
        const input = /** @type {HTMLInputElement} */ (document.querySelector('.search-modal-input'))
        input.value = 'leftover'

        search.close()
        search.open()

        expect(input.value).toBe('')
    })

    test('typing a query fetches results after the debounce and renders them', async () => {
        const fetchMock = vi.fn()
            .mockResolvedValueOnce(fakeResponse({ok: true, body: {suggestions: []}})) // open()'s suggestions fetch
            .mockResolvedValueOnce(fakeResponse({ok: true, body: {results: [
                {path: 'about', title: 'About', snippet: 'A gallery of <mark>art</mark>.'},
            ]}}))
        vi.stubGlobal('fetch', fetchMock)
        vi.useFakeTimers()

        search.open()
        const input = /** @type {HTMLInputElement} */ (document.querySelector('.search-modal-input'))
        input.value = 'art'
        input.dispatchEvent(new Event('input', {bubbles: true}))

        await vi.advanceTimersByTimeAsync(200)

        expect(fetchMock).toHaveBeenCalledWith('/api/search?q=art')
        const result = document.querySelector('.search-modal-result')
        expect(result?.querySelector('.search-modal-result-title')?.textContent).toBe('About')
        // innerHTML, not textContent — the <mark> from the API must survive as real markup.
        expect(result?.querySelector('.search-modal-result-snippet')?.innerHTML).toBe('A gallery of <mark>art</mark>.')
        expect(/** @type {HTMLAnchorElement} */ (result).getAttribute('href')).toBe('/about')
    })

    test('debounces — only the last keystroke within the window fires a request', async () => {
        const fetchMock = vi.fn().mockResolvedValue(fakeResponse({ok: true, body: {suggestions: [], results: []}}))
        vi.stubGlobal('fetch', fetchMock)
        vi.useFakeTimers()

        search.open()
        const input = /** @type {HTMLInputElement} */ (document.querySelector('.search-modal-input'))
        for (const value of ['a', 'ar', 'art']) {
            input.value = value
            input.dispatchEvent(new Event('input', {bubbles: true}))
            await vi.advanceTimersByTimeAsync(50)
        }
        await vi.advanceTimersByTimeAsync(200)

        const searchCalls = fetchMock.mock.calls.filter(call => String(call[0]).startsWith('/api/search?q='))
        expect(searchCalls).toEqual([['/api/search?q=art']])
    })

    test('clearing the query back to empty shows no results and does not search', async () => {
        vi.useFakeTimers()
        search.open()
        const input = /** @type {HTMLInputElement} */ (document.querySelector('.search-modal-input'))

        input.value = ''
        input.dispatchEvent(new Event('input', {bubbles: true}))
        await vi.advanceTimersByTimeAsync(200)

        expect(document.querySelector('.search-modal-results')?.children.length).toBe(0)
    })

    test('no results renders the empty-state message', async () => {
        const fetchMock = vi.fn()
            .mockResolvedValueOnce(fakeResponse({ok: true, body: {suggestions: []}}))
            .mockResolvedValueOnce(fakeResponse({ok: true, body: {results: []}}))
        vi.stubGlobal('fetch', fetchMock)
        vi.useFakeTimers()

        search.open()
        const input = /** @type {HTMLInputElement} */ (document.querySelector('.search-modal-input'))
        input.value = 'zzz'
        input.dispatchEvent(new Event('input', {bubbles: true}))
        await vi.advanceTimersByTimeAsync(200)

        expect(document.querySelector('.search-modal-empty')?.textContent).toBe('No results.')
    })

    test('a stale, slower response does not overwrite a newer one', async () => {
        /** @type {() => void} */
        let resolveFirst = () => {}
        const fetchMock = vi.fn()
            .mockResolvedValueOnce(fakeResponse({ok: true, body: {suggestions: []}}))
            .mockImplementationOnce(() => new Promise(resolve => { resolveFirst = () => resolve(fakeResponse({
                ok: true, body: {results: [{path: 'old', title: 'Old', snippet: 'old'}]},
            }))}))
            .mockResolvedValueOnce(fakeResponse({ok: true, body: {results: [{path: 'new', title: 'New', snippet: 'new'}]}}))
        vi.stubGlobal('fetch', fetchMock)
        vi.useFakeTimers()

        search.open()
        const input = /** @type {HTMLInputElement} */ (document.querySelector('.search-modal-input'))

        input.value = 'old'
        input.dispatchEvent(new Event('input', {bubbles: true}))
        await vi.advanceTimersByTimeAsync(200) // fires the slow "old" request, left pending

        input.value = 'new'
        input.dispatchEvent(new Event('input', {bubbles: true}))
        await vi.advanceTimersByTimeAsync(200) // fires + resolves the "new" request first

        resolveFirst() // the stale "old" response arrives last
        await Promise.resolve()
        await Promise.resolve()

        expect(document.querySelector('.search-modal-result-title')?.textContent).toBe('New')
    })

    test('the placeholder types out a loaded suggestion', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(fakeResponse({ok: true, body: {suggestions: ['find art']}})))
        vi.useFakeTimers()

        search.open()
        await vi.advanceTimersByTimeAsync(0) // let the suggestions fetch's microtasks settle
        await vi.advanceTimersByTimeAsync(45 * 4)

        const input = /** @type {HTMLInputElement} */ (document.querySelector('.search-modal-input'))
        expect(input.placeholder).toBe('find')
    })

    test('typing a real query stops the typing animation from overwriting the placeholder', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(fakeResponse({ok: true, body: {suggestions: ['find art'], results: []}})))
        vi.useFakeTimers()

        search.open()
        await vi.advanceTimersByTimeAsync(0)

        const input = /** @type {HTMLInputElement} */ (document.querySelector('.search-modal-input'))
        input.value = 'x'
        input.dispatchEvent(new Event('input', {bubbles: true}))
        const placeholderAfterTyping = input.placeholder

        await vi.advanceTimersByTimeAsync(500)

        expect(input.placeholder).toBe(placeholderAfterTyping)
    })

})
