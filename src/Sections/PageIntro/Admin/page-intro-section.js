import Section    from '/js/admin/sections/section.js'
import {isObject} from '/js/core/types.js'

export default class PageIntroSection extends Section {

    /** @type {HTMLDivElement} */
    #element

    /**
     * @param {string} heading
     * @param {string} body
     */
    constructor (heading, body) {
        super()
        this.heading  = heading
        this.body     = body
        this.#element = this.#build()
    }

    static type () {
        return 'page-intro'
    }

    /** @param {unknown} data */
    static fromObject (data) {
        if (
            !isObject(data)
            || typeof data.heading !== 'string'
            || typeof data.body    !== 'string'
        )
            throw new Error('Invalid PageIntroSection data')
        return new PageIntroSection(data.heading, data.body)
    }

    static createEmpty () {
        return new PageIntroSection('', '')
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
        left.append(bar(90, 'sp-bar sp-bar--heading'), bar(80, 'sp-bar sp-bar--heading'))

        const right = document.createElement('div')
        right.className = 'sp-col'
        right.append(bar(95), bar(88), bar(80), bar(70))

        const row = document.createElement('div')
        row.className = 'sp-row'
        row.append(left, right)

        root.append(row)
        return root
    }

    toObject () {
        return {
            heading: this.heading,
            body:    this.body,
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

        root.append(headingLabel, bodyLabel)
        return root
    }

}
