import Section    from '/js/admin/sections/section.js'
import {isObject} from '/js/core/types.js'

/**
 * Admin editor for the TwoColumn section. Mirrors PHP TwoColumnSection:
 * heading + body + buttonText + buttonHref. An empty button text means
 * no button is rendered on the public side.
 */
export default class TwoColumnSection extends Section {

    /** @type {HTMLDivElement} */
    #element

    /**
     * @param {string} heading
     * @param {string} body
     * @param {string} buttonText
     * @param {string} buttonHref
     */
    constructor (heading, body, buttonText, buttonHref) {
        super()
        this.heading    = heading
        this.body       = body
        this.buttonText = buttonText
        this.buttonHref = buttonHref
        this.#element   = this.#build()
    }

    static type () {
        return 'two-column'
    }

    /** @param {unknown} data */
    static fromObject (data) {
        if (
            !isObject(data)
            || typeof data.heading    !== 'string'
            || typeof data.body       !== 'string'
            || typeof data.buttonText !== 'string'
            || typeof data.buttonHref !== 'string'
        )
            throw new Error('Invalid TwoColumnSection data')
        return new TwoColumnSection(data.heading, data.body, data.buttonText, data.buttonHref)
    }

    static createEmpty () {
        return new TwoColumnSection('', '', '', '')
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

        const left = document.createElement('div')
        left.className = 'sp-col'
        left.append(bar(92), bar(85), bar(78), bar(65))

        const right = document.createElement('div')
        right.className = 'sp-col'
        right.append(bar(88), bar(72), bar(60), bar(35, 'sp-bar sp-bar--btn'))

        const row = document.createElement('div')
        row.className = 'sp-row'
        row.append(left, right)

        root.append(row)
        return root
    }

    toObject () {
        return {
            heading:    this.heading,
            body:       this.body,
            buttonText: this.buttonText,
            buttonHref: this.buttonHref,
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

        const bodyLabel = document.createElement('label')
        bodyLabel.append('Body ')
        const bodyArea = document.createElement('textarea')
        bodyArea.name  = 'body'
        bodyArea.value = this.body
        bodyArea.addEventListener('input', () => { this.body = bodyArea.value })
        bodyLabel.append(bodyArea)

        const buttonTextLabel = document.createElement('label')
        buttonTextLabel.append('Button text ')
        const buttonTextInput = document.createElement('input')
        buttonTextInput.name  = 'buttonText'
        buttonTextInput.value = this.buttonText
        buttonTextInput.addEventListener('input', () => { this.buttonText = buttonTextInput.value })
        buttonTextLabel.append(buttonTextInput)

        const buttonHrefLabel = document.createElement('label')
        buttonHrefLabel.append('Button link ')
        const buttonHrefInput = document.createElement('input')
        buttonHrefInput.name  = 'buttonHref'
        buttonHrefInput.value = this.buttonHref
        buttonHrefInput.addEventListener('input', () => { this.buttonHref = buttonHrefInput.value })
        buttonHrefLabel.append(buttonHrefInput)

        root.append(headingLabel, bodyLabel, buttonTextLabel, buttonHrefLabel)
        return root
    }

}
