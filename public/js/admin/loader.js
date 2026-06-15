import LoadingHandle from '/js/admin/loader/loading-handle.js'

/**
 * Shared loading indicator. Two usage modes:
 *
 *   const loading = loader.start()         // auto-id, stop via handle
 *   loading.stop()
 *
 *   loader.start('sidebar.list')           // explicit id, stop elsewhere
 *   loader.stop('sidebar.list')
 *
 * The handle is always returned; ignoring it when using explicit ids is fine.
 * The element is visible while the active set is non-empty.
 *
 * Visual is CSS-driven via the `data-active` attribute (see admin/main.css).
 */
export default class Loader {

    /** @type {HTMLSpanElement} */
    #element

    /** @type {Set<string>} */
    #active = new Set()

    /** @type {number} */
    #autoId = 0

    constructor () {
        this.#element = document.createElement('span')
        this.#element.className = 'loader'
    }

    /** @returns {HTMLSpanElement} */
    get element () {
        return this.#element
    }

    /**
     * Begin a loading scope. Returns a handle whose `stop()` ends this scope.
     * If `id` is omitted, an internal id is generated.
     *
     * @param {string} [id]
     * @returns {LoadingHandle}
     */
    start (id) {
        const key = id ?? `loader.auto.${++this.#autoId}`
        this.#active.add(key)
        this.#render()
        return new LoadingHandle(() => this.stop(key))
    }

    /** @param {string} id */
    stop (id) {
        this.#active.delete(id)
        this.#render()
    }

    #render () {
        if (this.#active.size > 0)
            this.#element.dataset.active = ''
        else
            delete this.#element.dataset.active
    }

}
