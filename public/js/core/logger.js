/**
 * Thin wrapper over `console`. Centralized so we can later swap behavior
 * (silence in production, ship to remote, mock in tests) without touching
 * call sites.
 */
export default class Logger {

    /**
     * @param {string} message
     * @param {...unknown} args
     */
    info (message, ...args) {
        console.log(`[info] ${message}`, ...args)
    }

    /**
     * @param {string} message
     * @param {...unknown} args
     */
    warn (message, ...args) {
        console.warn(`[warn] ${message}`, ...args)
    }

    /**
     * @param {string} message
     * @param {...unknown} args
     */
    error (message, ...args) {
        console.error(`[error] ${message}`, ...args)
    }

}
