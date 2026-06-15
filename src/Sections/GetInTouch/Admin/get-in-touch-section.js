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
        root.className = 'sp-preview'

        const bar = (/** @type {number} */ w, /** @type {string} */ cls = 'sp-bar') => {
            const el = document.createElement('div')
            el.className   = cls
            el.style.width = `${w}%`
            return el
        }

        root.append(bar(72, 'sp-bar sp-bar--h'), bar(55, 'sp-bar sp-bar--h'), bar(22, 'sp-bar sp-bar--btn'))
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
