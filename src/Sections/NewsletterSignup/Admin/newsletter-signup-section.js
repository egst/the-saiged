import Section    from '/js/admin/sections/section.js'
import {isObject} from '/js/core/types.js'

export default class NewsletterSignupSection extends Section {

    /** @type {HTMLDivElement} */
    #element

    /**
     * @param {string} heading
     * @param {string} body
     * @param {string} formAction
     */
    constructor (heading, body, formAction) {
        super()
        this.heading    = heading
        this.body       = body
        this.formAction = formAction
        this.#element   = this.#build()
    }

    static type () {
        return 'newsletter-signup'
    }

    /** @param {unknown} data */
    static fromObject (data) {
        if (
            !isObject(data)
            || typeof data.heading    !== 'string'
            || typeof data.body       !== 'string'
            || typeof data.formAction !== 'string'
        )
            throw new Error('Invalid NewsletterSignupSection data')
        return new NewsletterSignupSection(data.heading, data.body, data.formAction)
    }

    static createEmpty () {
        return new NewsletterSignupSection('', '', '')
    }

    /** @returns {HTMLDivElement} */
    static preview () {
        const root = document.createElement('div')
        root.className = 'sp-preview sp-preview--light'
        root.style.cssText += '; align-items: center; gap: 4px'
        const bar = (/** @type {number} */ w, /** @type {string} */ cls = 'sp-bar') => {
            const el = document.createElement('div')
            el.className   = cls
            el.style.width = `${w}%`
            return el
        }
        const inputRow = document.createElement('div')
        inputRow.style.cssText = 'display:flex; gap:4px; width:70%'
        const inputBar = bar(75)
        inputBar.style.borderBottom = '1px solid rgba(0,0,0,0.25)'
        inputBar.style.background   = 'transparent'
        const btnBar = bar(25, 'sp-bar sp-bar--btn')
        inputRow.append(inputBar, btnBar)
        root.append(bar(45, 'sp-bar sp-bar--heading'), bar(60), inputRow)
        return root
    }

    toObject () {
        return {
            heading:    this.heading,
            body:       this.body,
            formAction: this.formAction,
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
        const bodyInput = document.createElement('input')
        bodyInput.name  = 'body'
        bodyInput.value = this.body
        bodyInput.addEventListener('input', () => { this.body = bodyInput.value })
        bodyLabel.append(bodyInput)

        const actionLabel = document.createElement('label')
        actionLabel.append('Form action URL ')
        const actionInput = document.createElement('input')
        actionInput.type  = 'url'
        actionInput.name  = 'formAction'
        actionInput.value = this.formAction
        actionInput.addEventListener('input', () => { this.formAction = actionInput.value })
        actionLabel.append(actionInput)

        root.append(headingLabel, bodyLabel, actionLabel)
        return root
    }

}
