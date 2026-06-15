/**
 * @import Logger from '/js/core/logger.js'
 */

/**
 * Global notification surface. Each call logs to the console via Logger
 * (kept for dev debugging) AND mounts a toast in a fixed top-right stack
 * for the user.
 *
 * Errors don't auto-dismiss — the user must click [×]. Info / success
 * auto-dismiss after ~4s.
 */
export default class Notifier {

    /** @type {Logger} */
    #logger
    /** @type {HTMLDivElement} */
    #container

    /** @param {Logger} logger */
    constructor (logger) {
        this.#logger       = logger
        this.#container    = document.createElement('div')
        this.#container.id = 'toasts'
        document.body.append(this.#container)
    }

    /**
     * Report an error visible to the user. `cause` is attached to the
     * console log for debugging (typically an Error instance).
     *
     * @param {string}  message       shown to the user
     * @param {unknown} [cause]       attached to the console log only
     */
    error (message, cause) {
        if (cause !== undefined)
            this.#logger.error(message, cause)
        else
            this.#logger.error(message)
        this.#show('error', message, null)
    }

    /**
     * User-visible positive confirmation. Auto-dismisses.
     *
     * @param {string} message
     */
    success (message) {
        this.#logger.info(message)
        this.#show('success', message, 4000)
    }

    /**
     * Dev-only info — console log, no toast. Keeps Notifier a drop-in
     * replacement for Logger in views that already used `logger.info`.
     *
     * @param {string}     message
     * @param {...unknown} args
     */
    info (message, ...args) {
        this.#logger.info(message, ...args)
    }

    /**
     * Dev-only warning — console log, no toast.
     *
     * @param {string}     message
     * @param {...unknown} args
     */
    warn (message, ...args) {
        this.#logger.warn(message, ...args)
    }

    /**
     * @param {'error' | 'success' | 'info'} kind
     * @param {string}                       message
     * @param {number | null}                autoDismissMs   null = stays until user dismisses
     */
    #show (kind, message, autoDismissMs) {
        const toast = document.createElement('div')
        toast.className = `toast toast-${kind}`

        const text = document.createElement('p')
        text.className   = 'toast-text'
        text.textContent = message

        const close = document.createElement('button')
        close.type        = 'button'
        close.className   = 'toast-close'
        close.title       = 'Dismiss'
        close.textContent = '×'
        close.addEventListener('click', () => toast.remove())

        toast.append(text, close)
        this.#container.append(toast)

        if (autoDismissMs !== null)
            setTimeout(() => toast.remove(), autoDismissMs)
    }

}
