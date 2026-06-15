import {describe, test, expect, beforeEach, afterEach, vi} from 'vitest'
import Notifier                                              from '/js/admin/notifier.js'
import Logger                                                from '/js/core/logger.js'

describe('Notifier', () => {

    beforeEach(() => {
        // Notifier mounts a `<div id="toasts">` into document.body. Reset
        // between tests so toasts from a previous test don't bleed over.
        document.body.replaceChildren()
        vi.useFakeTimers()
        // Silence Logger's console output (Notifier delegates to it).
        vi.spyOn(Logger.prototype, 'info' ).mockImplementation(() => {})
        vi.spyOn(Logger.prototype, 'warn' ).mockImplementation(() => {})
        vi.spyOn(Logger.prototype, 'error').mockImplementation(() => {})
    })

    afterEach(() => {
        vi.useRealTimers()
        vi.restoreAllMocks()
    })

    test('error() shows a toast that persists past the auto-dismiss window', () => {
        const notifier = new Notifier(new Logger())

        notifier.error('Boom')

        expect(document.querySelectorAll('.toast'      )).toHaveLength(1)
        expect(document.querySelector  ('.toast-error' )?.textContent).toContain('Boom')

        vi.advanceTimersByTime(60_000)
        expect(document.querySelectorAll('.toast')).toHaveLength(1)
    })

    test('success() auto-dismisses after the timeout', () => {
        const notifier = new Notifier(new Logger())

        notifier.success('Saved')

        expect(document.querySelectorAll('.toast')).toHaveLength(1)

        vi.advanceTimersByTime(4000)
        expect(document.querySelectorAll('.toast')).toHaveLength(0)
    })

    test('[×] button dismisses a toast on click', () => {
        const notifier = new Notifier(new Logger())
        notifier.error('Boom')

        /** @type {HTMLButtonElement} */
        const close = /** @type {any} */ (document.querySelector('.toast-close'))
        close.click()

        expect(document.querySelectorAll('.toast')).toHaveLength(0)
    })

    test('toasts stack as siblings in the container', () => {
        const notifier = new Notifier(new Logger())

        notifier.error  ('First')
        notifier.error  ('Second')
        notifier.success('Third')

        expect(document.querySelectorAll('#toasts > .toast')).toHaveLength(3)
    })

    test('error message text is rendered as text content, not HTML', () => {
        const notifier = new Notifier(new Logger())

        notifier.error('<script>alert(1)</script>')

        const text = document.querySelector('.toast-text')
        expect(text?.textContent      ).toBe('<script>alert(1)</script>')
        expect(text?.querySelector('script')).toBeNull()
    })

    test('info() / warn() do not produce toasts — console only', () => {
        const notifier = new Notifier(new Logger())

        notifier.info('hello')
        notifier.warn('careful')

        expect(document.querySelectorAll('.toast')).toHaveLength(0)
    })

    test('error() also logs to the Logger (dev console)', () => {
        const logger = new Logger()
        const spy    = vi.spyOn(logger, 'error')
        const notifier = new Notifier(logger)

        notifier.error('Boom', new Error('cause'))

        expect(spy).toHaveBeenCalledWith('Boom', expect.any(Error))
    })

})
