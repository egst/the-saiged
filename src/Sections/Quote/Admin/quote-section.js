import Section    from '/js/admin/sections/section.js'
import {isObject} from '/js/core/types.js'

export default class QuoteSection extends Section {

    /** @type {HTMLDivElement} */
    #element

    /**
     * @param {string} quote
     * @param {string} cite
     */
    constructor (quote, cite) {
        super()
        this.quote    = quote
        this.cite     = cite
        this.#element = this.#build()
    }

    static type () {
        return 'quote'
    }

    /** @param {unknown} data */
    static fromObject (data) {
        if (
            !isObject(data)
            || typeof data.quote !== 'string'
            || typeof data.cite  !== 'string'
        )
            throw new Error('Invalid QuoteSection data')
        return new QuoteSection(data.quote, data.cite)
    }

    static createEmpty () {
        return new QuoteSection('', '')
    }

    /** @returns {HTMLDivElement} */
    static preview () {
        const root = document.createElement('div')
        root.className  = 'sp-preview sp-preview--light'
        root.style.borderLeft = '3px solid rgba(0,0,0,0.5)'
        const bar = (/** @type {number} */ w, /** @type {string} */ cls = 'sp-bar') => {
            const el = document.createElement('div')
            el.className   = cls
            el.style.width = `${w}%`
            return el
        }
        const line1 = bar(88, 'sp-bar sp-bar--heading')
        line1.style.marginBottom = '3px'
        root.append(line1, bar(65, 'sp-bar sp-bar--heading'), bar(30))
        return root
    }

    toObject () {
        return {quote: this.quote, cite: this.cite}
    }

    get element () {
        return this.#element
    }

    #build () {
        const root = document.createElement('div')

        const quoteLabel = document.createElement('label')
        quoteLabel.append('Quote ')
        const quoteArea = document.createElement('textarea')
        quoteArea.name  = 'quote'
        quoteArea.rows  = 4
        quoteArea.value = this.quote
        quoteArea.addEventListener('input', () => { this.quote = quoteArea.value })
        quoteLabel.append(quoteArea)

        const citeLabel = document.createElement('label')
        citeLabel.append('Attribution ')
        const citeInput = document.createElement('input')
        citeInput.name  = 'cite'
        citeInput.value = this.cite
        citeInput.addEventListener('input', () => { this.cite = citeInput.value })
        citeLabel.append(citeInput)

        root.append(quoteLabel, citeLabel)
        return root
    }

}
