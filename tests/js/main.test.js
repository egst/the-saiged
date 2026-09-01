import {describe, test, expect, beforeEach, vi} from 'vitest'
import overlay                                   from '/js/overlay.js'
import '/js/main.js'

describe('main click delegation', () => {

    beforeEach(() => {
        vi.restoreAllMocks()
        document.body.querySelectorAll('a').forEach(a => a.remove())
    })

    test('intercepts clicks on [data-overlay] links and routes them through the overlay', () => {
        const openSpy = vi.spyOn(overlay, 'open').mockResolvedValue(undefined)
        const link = document.createElement('a')
        link.href = '/about'
        link.setAttribute('data-overlay', '')
        link.textContent = 'About'
        document.body.append(link)

        link.dispatchEvent(new MouseEvent('click', {bubbles: true, cancelable: true}))

        expect(openSpy).toHaveBeenCalledWith(link.href)
    })

    test('prevents the default navigation for [data-overlay] links', () => {
        vi.spyOn(overlay, 'open').mockResolvedValue(undefined)
        const link = document.createElement('a')
        link.href = '/about'
        link.setAttribute('data-overlay', '')
        document.body.append(link)

        const event = new MouseEvent('click', {bubbles: true, cancelable: true})
        link.dispatchEvent(event)

        expect(event.defaultPrevented).toBe(true)
    })

    test('leaves plain links alone', () => {
        const openSpy = vi.spyOn(overlay, 'open').mockResolvedValue(undefined)
        const link = document.createElement('a')
        link.href = '/about'
        document.body.append(link)

        const event = new MouseEvent('click', {bubbles: true, cancelable: true})
        link.dispatchEvent(event)

        expect(openSpy).not.toHaveBeenCalled()
        expect(event.defaultPrevented).toBe(false)
    })

    test('ignores clicks on non-anchor elements carrying [data-overlay]', () => {
        const openSpy = vi.spyOn(overlay, 'open').mockResolvedValue(undefined)
        const span = document.createElement('span')
        span.setAttribute('data-overlay', '')
        span.textContent = 'not a link'
        document.body.append(span)

        span.dispatchEvent(new MouseEvent('click', {bubbles: true, cancelable: true}))

        expect(openSpy).not.toHaveBeenCalled()
    })

})
