import {isObject} from '/js/core/types.js'

/**
 * Lightweight wire-shape wrapper for uploads as returned by the admin
 * API. Mirrors the PHP Upload entity's `toArray` shape so the JS code
 * can rely on validated fields rather than `data.uploads[i].whatever`.
 *
 * `thumbUrl` is null for non-image uploads (videos) — the API derives
 * it server-side and the JS surface follows. Original URL is always
 * present.
 */
export default class Upload {

    /**
     * @param {number}      id
     * @param {string}      filename
     * @param {string}      mime
     * @param {string}      kind        'image' | 'video'
     * @param {number}      size        bytes
     * @param {number|null} width       only for images
     * @param {number|null} height      only for images
     * @param {string}      uploadedAt  "YYYY-MM-DD HH:MM:SS"
     * @param {string}      originalUrl
     * @param {string|null} thumbUrl    null for videos
     */
    constructor (id, filename, mime, kind, size, width, height, uploadedAt, originalUrl, thumbUrl) {
        this.id          = id
        this.filename    = filename
        this.mime        = mime
        this.kind        = kind
        this.size        = size
        this.width       = width
        this.height      = height
        this.uploadedAt  = uploadedAt
        this.originalUrl = originalUrl
        this.thumbUrl    = thumbUrl
    }

    /** @param {unknown} input */
    static fromObject (input) {
        if (!isObject(input)
            || typeof input.id          !== 'number'
            || typeof input.filename    !== 'string'
            || typeof input.mime        !== 'string'
            || typeof input.kind        !== 'string'
            || typeof input.size        !== 'number'
            || !(input.width  === null || typeof input.width  === 'number')
            || !(input.height === null || typeof input.height === 'number')
            || typeof input.uploadedAt  !== 'string'
            || typeof input.originalUrl !== 'string'
            || !(input.thumbUrl === null || typeof input.thumbUrl === 'string'))
            throw new Error('Invalid Upload shape')

        return new Upload(
            input.id, input.filename, input.mime, input.kind,
            input.size, input.width, input.height,
            input.uploadedAt, input.originalUrl, input.thumbUrl,
        )
    }

}
