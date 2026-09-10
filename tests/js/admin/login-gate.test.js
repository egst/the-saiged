import {describe, test, expect, afterEach} from 'vitest'
import LoginGate                           from '/js/admin/login-gate.js'

describe('LoginGate', () => {

    afterEach(() => {
        window.history.replaceState(null, '', '/admin')
    })

    test('renders a plain sign-in link with no error when ?login is absent', () => {
        const gate = new LoginGate()

        expect(gate.element.querySelector('.login-gate-error')).toBeNull()
        const link = /** @type {HTMLAnchorElement} */ (gate.element.querySelector('a'))
        expect(link.getAttribute('href')).toBe('/auth/google')
    })

    test('shows a denied message for ?login=denied', () => {
        window.history.replaceState(null, '', '/admin?login=denied')

        const gate = new LoginGate()

        expect(gate.element.querySelector('.login-gate-error')?.textContent).toMatch(/not registered/)
    })

    test('shows a generic error message for ?login=error', () => {
        window.history.replaceState(null, '', '/admin?login=error')

        const gate = new LoginGate()

        expect(gate.element.querySelector('.login-gate-error')?.textContent).toMatch(/Sign-in failed/)
    })

})
