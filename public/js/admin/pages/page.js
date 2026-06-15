import {isObject} from '/js/core/types.js'

/**
 * @import Section        from '/js/admin/sections/section.js'
 * @import SectionFactory from '/js/admin/sections/section-factory.js'
 */

/**
 * Full page entity used by the editor. Mirrors PHP Page: positional ctor
 * with public fields, sections array of validated Section instances.
 *
 * `fromObject` is async because constructing each Section requires a
 * dynamic import of its admin module (handled by SectionFactory).
 *
 * Properties are mutable — admin editor mutates title and section data
 * directly on user input.
 */
export default class Page {

    /**
     * @param {number}      id
     * @param {string}      path
     * @param {string}      title
     * @param {string|null} metaDesc
     * @param {string}      status
     * @param {Section[]}   sections
     */
    constructor (id, path, title, metaDesc, status, sections) {
        this.id       = id
        this.path     = path
        this.title    = title
        this.metaDesc = metaDesc
        // TODO: Can we enforce the status enum statically?
        this.status   = status
        this.sections = sections
    }

    /**
     * @param {unknown}        input
     * @param {SectionFactory} sectionFactory
     */
    static async fromObject (input, sectionFactory) {
        if (!isObject(input)
            || typeof input.id     !== 'number'
            || typeof input.path   !== 'string'
            || typeof input.title  !== 'string'
            || typeof input.status !== 'string'
            || !(input.metaDesc === null || typeof input.metaDesc === 'string')
            || !Array.isArray(input.sections))
            throw new Error('Invalid Page shape')

        const sections = await Promise.all(
            input.sections.map(row => sectionFactory.create(row))
        )

        return new Page(input.id, input.path, input.title, input.metaDesc, input.status, sections)
    }

}
