/**
 * Handle returned from `Loader#start()`. Calling `stop()` ends the loading
 * scope it represents. The handle holds a closure over the Loader's stop
 * callback — it doesn't reference the Loader directly.
 */
export default class LoadingHandle {

    /** @type {() => void} */
    #stop

    /** @param {() => void} stop */
    constructor (stop) {
        this.#stop = stop
    }

    stop () {
        this.#stop()
    }

}
