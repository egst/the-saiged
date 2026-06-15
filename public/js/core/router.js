/** @typedef {(path: string) => void} PathListener */

/**
 * Path-based router for SPA navigation.
 *
 * - `go(path)` pushes a new history entry and notifies listeners.
 * - `fire()` notifies listeners with the current path (used after components
 *   are set up so they render the initial view).
 * - `onChange(listener)` returns an unsubscribe function.
 *
 * Listeners are also invoked on browser back/forward via the popstate event.
 - Initial page load does NOT fire popstate. Call `fire()` from the App to bootstrap.
 */
export default class Router {

    /** @type {Set<PathListener>} */
    #listeners = new Set()

    constructor () {
        window.addEventListener('popstate', () => this.#emit(window.location.pathname))
    }

    /** @param {string} path */
    go (path) {
        history.pushState({}, '', path)
        this.#emit(path)
    }

    /**
     * @param {PathListener} listener
     * @returns {() => boolean}
     */
    onChange (listener) {
        this.#listeners.add(listener)
        // TODO: K cemu je tento return?
        return () => this.#listeners.delete(listener)
    }

    fire () {
        this.#emit(window.location.pathname)
    }

    /** @returns {string} */
    currentPath () {
        return window.location.pathname
    }

    /** @param {string} path */
    #emit (path) {
        for (const listener of this.#listeners)
            listener(path)
    }

}
