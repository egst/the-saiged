import Section    from '/js/admin/sections/section.js'
import {isObject} from '/js/core/types.js'

export default class StatementSection extends Section {

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
        return 'statement'
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
            throw new Error('Invalid StatementSection data')
        return new StatementSection(data.heading, data.body, data.buttonText, data.buttonHref)
    }

    static createEmpty () {
        return new StatementSection('', '', '', '')
    }

    /** @returns {HTMLDivElement} */
    static preview () {
        const root = document.createElement('div')
        root.className = 'sp-preview sp-preview--dark sp-preview--center'
        const bar = (/** @type {number} */ w, /** @type {string} */ cls = 'sp-bar') => {
            const el = document.createElement('div')
            el.className   = cls
            el.style.width = `${w}%`
            return el
        }
        root.append(bar(56, 'sp-bar sp-bar--heading'), bar(42), bar(28, 'sp-bar sp-bar--btn'))
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
