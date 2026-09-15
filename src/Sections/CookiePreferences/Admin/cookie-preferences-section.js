import Section from '/js/admin/sections/section.js'

/**
 * Admin editor for CookiePreferencesSection — nothing to edit; the panel's
 * copy and behavior are fixed (see the PHP section's docblock). Exists so
 * this type shows up in the "Add section" picker and round-trips through
 * the page editor like every other section.
 */
export default class CookiePreferencesSection extends Section {

    /** @type {HTMLParagraphElement} */
    #element

    constructor () {
        super()
        this.#element = this.#build()
    }

    static type () {
        return 'cookie-preferences'
    }

    /** @param {unknown} data */
    static fromObject (data) {
        return new CookiePreferencesSection()
    }

    static createEmpty () {
        return new CookiePreferencesSection()
    }

    /** @returns {HTMLDivElement} */
    static preview () {
        const root = document.createElement('div')
        root.className = 'sp-preview sp-preview--light'
        /** @param {number} w */
        const bar = w => {
            const el = document.createElement('div')
            el.className   = 'sp-bar'
            el.style.width = `${w}%`
            return el
        }
        root.append(bar(70), bar(50), bar(30))
        return root
    }

    /** @returns {Record<string, unknown>} */
    toObject () {
        return {}
    }

    get element () {
        return this.#element
    }

    /** @returns {HTMLParagraphElement} */
    #build () {
        const root = document.createElement('p')
        root.textContent = 'Cookie Preferences — fixed content, nothing to edit here.'
        return root
    }

}
