import Section           from '/js/admin/sections/section.js'
import {isObject}        from '/js/core/types.js'
import {sectionModuleUrl} from '/js/admin/sections/section-module-url.js'

/** @import Api from '/js/admin/api.js' */

/**
 * Mirrors PHP SectionFactory: dispatch by type, delegate to the concrete
 * Section's static fromObject (which validates its own data).
 *
 * Folder name is derived from type via convention (kebab → PascalCase),
 * matching the server-side folder layout under src/Sections/.
 *
 * The shared Api instance is captured in the constructor and forwarded
 * to every section's factory — sections that need backend access (e.g.
 * the image carousel for picking uploads + ensuring variants) use it;
 * others ignore it. Async because module imports are async in JS.
 */
export default class SectionFactory {

    /** @type {Api | null} */
    #api = null
    /** @type {Map<string, typeof Section>} */
    #classes = new Map()

    /**
     * Late-wire the shared Api instance. Has to be a setter rather than a
     * constructor argument because of the SectionFactory ↔ Api cycle
     * (Api takes the factory in its constructor). App.run() instantiates
     * factory first, then Api, then calls this.
     *
     * @param {Api} api
     */
    setApi (api) {
        this.#api = api
    }

    /** @param {unknown} row */
    async create (row) {
        if (!isObject(row) || typeof row.type !== 'string' || !isObject(row.data))
            throw new Error('Invalid section row')

        const SectionClass = await this.#resolveClass(row.type)
        const instance     = SectionClass.fromObject(row.data, this.#api)
        if (!(instance instanceof Section)) // TODO: Tohle by nemelo byt potreba, kdybychom nemeli ten problem s abstract static metodami.
            throw new Error(`Section ${row.type}: fromObject did not return a Section instance`)
        return instance
    }

    /** @param {string} type */
    async createEmpty (type) {
        const SectionClass = await this.#resolveClass(type)
        const instance     = SectionClass.createEmpty(this.#api)
        if (!(instance instanceof Section)) // TODO: Tohle by nemelo byt potreba, kdybychom nemeli ten problem s abstract static metodami.
            throw new Error(`Section ${type}: createEmpty did not return a Section instance`)
        return instance
    }

    /** @param {string} type */
    async #resolveClass (type) {
        const cached = this.#classes.get(type)
        if (cached !== undefined)
            return cached

        const url    = sectionModuleUrl(type)
        const module = await import(url)

        const SectionClass = module.default
        if (typeof SectionClass !== 'function')
            throw new Error(`SectionFactory: ${url} has no default-exported class`)
        if (SectionClass.prototype instanceof Section === false && SectionClass !== Section)
            throw new Error(`SectionFactory: ${url} default export does not extend Section`)

        this.#classes.set(type, SectionClass)
        return SectionClass
    }

}
