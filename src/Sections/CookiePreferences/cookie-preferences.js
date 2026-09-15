import {getConsent, setConsent} from '/js/cookie-consent.js'

/*
 * Enhances every CookiePreferencesSection instance on the page (in
 * practice, at most one) to reflect the actual stored consent choice —
 * the server-rendered markup always starts in the "undecided" shape since
 * Sections never read cookies. Stays in sync with the site-wide
 * cookie-banner.js (and any other instance of this section) via
 * cookie-consent.js's 'cookieconsentchange' event.
 *
 * Supports multiple independent instances on a single page.
 */
const initPanel = root => {
    const status   = root.querySelector('[data-cookie-status]')
    const accept   = root.querySelector('[data-cookie-choice="accepted"]')
    const reject   = root.querySelector('[data-cookie-choice="rejected"]')
    const withdraw = root.querySelector('[data-cookie-choice="withdraw"]')

    if (status === null || accept === null || reject === null || withdraw === null)
        return

    const render = () => {
        const consent = getConsent()

        status.hidden = consent === null
        if (consent === 'accepted')
            status.textContent = 'You have granted consent for analytics cookies.'
        else if (consent === 'rejected')
            status.textContent = 'You have declined analytics cookies.'

        accept.hidden   = consent === 'accepted'
        reject.hidden   = consent !== null
        withdraw.hidden = consent !== 'accepted'
    }

    root.addEventListener('click', event => {
        const button = event.target.closest('[data-cookie-choice]')
        if (button === null)
            return
        const choice = button.getAttribute('data-cookie-choice')
        setConsent(choice === 'withdraw' ? 'rejected' : choice)
        render()
    })

    window.addEventListener('cookieconsentchange', render)

    render()
}

for (const panel of document.querySelectorAll('[data-cookie-preferences]'))
    initPanel(panel)
