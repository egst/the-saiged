import {isObject} from '/js/core/types.js'

/**
 * Lightweight page record for list views. The constructor takes already-
 * validated fields; use `fromObject` to construct from an unknown wire
 * payload (it validates and throws on bad shape).
 */
export default class PageSummary {

    /**
     * @param {number} id
     * @param {string} path
     * @param {string} title
     * @param {string} status
     */
    constructor (id, path, title, status) {
        this.id     = id
        this.path   = path
        this.title  = title
        this.status = status
    }

    /** @param {unknown} input */
    static fromObject (input) {
        if (!isObject(input)
            || typeof input.id     !== 'number'
            || typeof input.path   !== 'string'
            || typeof input.title  !== 'string'
            || typeof input.status !== 'string')
            throw new Error('Invalid PageSummary shape')

        return new PageSummary(input.id, input.path, input.title, input.status)
    }

}
