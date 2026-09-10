/**
 * Shown in place of the whole admin app when Api#me() comes back null —
 * no session, or the account it names is no longer in `admins`. A plain
 * "Sign in with Google" link straight into the backend's redirect dance
 * (AuthController::login); there's no client-side form or state here to
 * component-ize.
 *
 * ?login=denied / ?login=error on the URL (set by AuthController's
 * callback on failure) get a one-line explanation above the button.
 */
export default class LoginGate {

    /** @type {HTMLDivElement} */
    #element

    constructor () {
        this.#element = document.createElement('div')
        this.#element.id = 'login-gate'

        const card = document.createElement('div')
        card.className = 'login-gate-card'

        const title = document.createElement('h1')
        title.textContent = 'The Saiged Admin'

        const reason = this.#reasonFromQuery()
        if (reason !== null) {
            const message = document.createElement('p')
            message.className   = 'login-gate-error'
            message.textContent = reason
            card.append(message)
        }

        const link = document.createElement('a')
        link.className   = 'login-gate-signin'
        link.href        = '/auth/google'
        link.textContent = 'Sign in with Google'

        card.append(title, link)
        this.#element.append(card)
    }

    get element () {
        return this.#element
    }

    /** @returns {string | null} */
    #reasonFromQuery () {
        const login = new URLSearchParams(window.location.search).get('login')
        if (login === 'denied')
            return 'That Google account is not registered as an admin.'
        if (login === 'error')
            return 'Sign-in failed — please try again.'
        return null
    }

}
