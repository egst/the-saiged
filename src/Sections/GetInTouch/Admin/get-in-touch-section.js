import Section    from '/js/admin/sections/section.js'
import {isObject} from '/js/core/types.js'

export default class GetInTouchSection extends Section {

    /** @type {HTMLDivElement} */
    #element

    /**
     * @param {string} heading
     * @param {string} ctaText
     * @param {string} ctaHref
     */
    constructor (heading, ctaText, ctaHref) {
        super()
        this.heading = heading
        this.ctaText = ctaText
        this.ctaHref = ctaHref
        this.#element = this.#build()
    }

    static type () {
        return 'get-in-touch'
    }

    /** @param {unknown} data */
    static fromObject (data) {
        if (
            !isObject(data)
            || typeof data.heading !== 'string'
            || typeof data.ctaText !== 'string'
            || typeof data.ctaHref !== 'string'
        )
            throw new Error('Invalid PageFooterSection data')
        return new GetInTouchSection(data.heading, data.ctaText, data.ctaHref)
    }

    static createEmpty () {
        return new GetInTouchSection('', '', '')
    }

    /** @returns {HTMLDivElement} */
    static preview () {
        const root = document.createElement('div')
        root.className = 'sp-preview sp-preview--light'

        const bar = (/** @type {number} */ w, /** @type {string} */ cls = 'sp-bar') => {
            const el = document.createElement('div')
            el.className   = cls
            el.style.width = `${w}%`
            return el
        }

        const left = document.createElement('div')
        left.className = 'sp-col'
        left.style.flex = '1'
        left.append(bar(95, 'sp-bar sp-bar--heading'), bar(70))

        const right = document.createElement('div')
        right.style.cssText = 'flex-shrink:0; width:22%; align-self:center'
        right.append(bar(100, 'sp-bar sp-bar--btn'))

        const row = document.createElement('div')
        row.className = 'sp-row'
        row.append(left, right)
        root.append(row)
        return root
    }

    toObject () {
        return {
            heading: this.heading,
            ctaText: this.ctaText,
            ctaHref: this.ctaHref,
        }
    }

    get element () {
        return this.#element
    }

    #build () {
        const root = document.createElement('div')

        const headingLabel = document.createElement('label')
        headingLabel.append('Heading ')
        const headingInput = document.createElement('input')
        headingInput.name  = 'heading'
        headingInput.value = this.heading
        headingInput.addEventListener('input', () => { this.heading = headingInput.value })
        headingLabel.append(headingInput)

        const ctaTextLabel = document.createElement('label')
        ctaTextLabel.append('CTA text ')
        const ctaTextInput = document.createElement('input')
        ctaTextInput.name  = 'ctaText'
        ctaTextInput.value = this.ctaText
        ctaTextInput.addEventListener('input', () => { this.ctaText = ctaTextInput.value })
        ctaTextLabel.append(ctaTextInput)

        const ctaHrefLabel = document.createElement('label')
        ctaHrefLabel.append('CTA link ')
        const ctaHrefInput = document.createElement('input')
        ctaHrefInput.name  = 'ctaHref'
        ctaHrefInput.value = this.ctaHref
        ctaHrefInput.addEventListener('input', () => { this.ctaHref = ctaHrefInput.value })
        ctaHrefLabel.append(ctaHrefInput)

        root.append(headingLabel, ctaTextLabel, ctaHrefLabel)
        return root
    }

}
