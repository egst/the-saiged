/**
 * Abstract base for admin section renderers. Mirrors the PHP Section
 * interface: each concrete section provides type, fromObject (validating
 * factory) and toObject (inverse). Plus `element` getter — the JS-specific
 * admin editor DOM.
 *
 * TODO: This is a dev note, not documentation. Should be removed in the future.
 * JS limitations vs PHP:
 *   - Static methods can't be enforced "abstract" by the type checker;
 *     subclasses must implement type/fromObject by convention. Direct calls
 *     to the base throw at runtime.
 *   - Properties are mutable here (admin editor mutates on input) whereas
 *     PHP TextSection is readonly. Per-save reconstruction would also work
 *     but is more code for no real win.
 */
export default class Section {

    /** @returns {string} */
    static type () {
        // TODO: If static methods can't be enforced, maybe there's another, more idiomatic way, to enforce the type being specified for each section?
        // Like a property or something... I guess a static property would probably have the same limitation.
        // Can it be non-static? Do we really need a static type here? How do we use this in practice? Do we even use this?
        throw new Error('Section.type not implemented')
    }

    /**
     * @param {unknown} data
     * @param {import('/js/admin/api.js').default} api  shared Api instance
     *        — passed to every section so types that need it (e.g. the
     *        image carousel for picking uploads and ensuring variants)
     *        can call it. Sections that don't need it just ignore the arg.
     * @returns {Section}
     */
    static fromObject (data, api) {
        throw new Error('Section.fromObject not implemented')
    }

    /**
     * Returns a Section with default ("empty") values, used by the admin
     * editor when the user adds a new section.
     *
     * @param {import('/js/admin/api.js').default} api
     * @returns {Section}
     */
    static createEmpty (api) {
        throw new Error('Section.createEmpty not implemented')
    }

    /** @returns {Record<string, unknown>} */
    toObject () {
        // TODO: why no @abstract even here and in the element getter?
        throw new Error('Section#toObject not implemented')
    }

    /** @returns {HTMLElement} */
    get element () {
        throw new Error('Section#element not implemented')
    }

    /** @returns {HTMLDivElement} */
    static preview () {
        const root = document.createElement('div')
        root.className = 'sp-preview'
        const bar = (/** @type {number} */ w) => {
            const el = document.createElement('div')
            el.className   = 'sp-bar'
            el.style.width = `${w}%`
            return el
        }
        root.append(bar(78), bar(64), bar(72))
        return root
    }

}
