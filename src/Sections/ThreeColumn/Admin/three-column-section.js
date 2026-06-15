import Section    from '/js/admin/sections/section.js'
import {isObject} from '/js/core/types.js'

export default class ThreeColumnSection extends Section {

    /** @type {HTMLDivElement} */
    #element

    /**
     * @param {string} heading
     * @param {string} col1Title
     * @param {string} col1Body
     * @param {string} col2Title
     * @param {string} col2Body
     * @param {string} col3Title
     * @param {string} col3Body
     */
    constructor (heading, col1Title, col1Body, col2Title, col2Body, col3Title, col3Body) {
        super()
        this.heading   = heading
        this.col1Title = col1Title
        this.col1Body  = col1Body
        this.col2Title = col2Title
        this.col2Body  = col2Body
        this.col3Title = col3Title
        this.col3Body  = col3Body
        this.#element  = this.#build()
    }

    static type () {
        return 'three-column'
    }

    /** @param {unknown} data */
    static fromObject (data) {
        if (
            !isObject(data)
            || typeof data.heading   !== 'string'
            || typeof data.col1Title !== 'string'
            || typeof data.col1Body  !== 'string'
            || typeof data.col2Title !== 'string'
            || typeof data.col2Body  !== 'string'
            || typeof data.col3Title !== 'string'
            || typeof data.col3Body  !== 'string'
        )
            throw new Error('Invalid ThreeColumnSection data')
        return new ThreeColumnSection(
            data.heading,
            data.col1Title, data.col1Body,
            data.col2Title, data.col2Body,
            data.col3Title, data.col3Body,
        )
    }

    static createEmpty () {
        return new ThreeColumnSection('', '', '', '', '', '', '')
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

        const heading = bar(55, 'sp-bar sp-bar--heading')
        heading.style.marginBottom = '6px'

        const row = document.createElement('div')
        row.className = 'sp-row'
        for (let i = 0; i < 3; i++) {
            const card = document.createElement('div')
            card.className = 'sp-card-mini'
            card.append(bar(80, 'sp-bar sp-bar--h'), bar(100), bar(90), bar(75))
            row.append(card)
        }

        root.append(heading, row)
        return root
    }

    toObject () {
        return {
            heading:   this.heading,
            col1Title: this.col1Title,
            col1Body:  this.col1Body,
            col2Title: this.col2Title,
            col2Body:  this.col2Body,
            col3Title: this.col3Title,
            col3Body:  this.col3Body,
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

        root.append(headingLabel)

        for (const col of /** @type {Array<{key: 'col1'|'col2'|'col3', label: string}>} */ ([
            {key: 'col1', label: 'Column 1'},
            {key: 'col2', label: 'Column 2'},
            {key: 'col3', label: 'Column 3'},
        ])) {
            const titleKey = /** @type {'col1Title'|'col2Title'|'col3Title'} */ (`${col.key}Title`)
            const bodyKey  = /** @type {'col1Body'|'col2Body'|'col3Body'}    */ (`${col.key}Body`)

            const titleLabel = document.createElement('label')
            titleLabel.append(`${col.label} title `)
            const titleInput = document.createElement('input')
            titleInput.name  = titleKey
            titleInput.value = this[titleKey]
            titleInput.addEventListener('input', () => { this[titleKey] = titleInput.value })
            titleLabel.append(titleInput)

            const bodyLabel = document.createElement('label')
            bodyLabel.append(`${col.label} body `)
            const bodyArea = document.createElement('textarea')
            bodyArea.name  = bodyKey
            bodyArea.value = this[bodyKey]
            bodyArea.addEventListener('input', () => { this[bodyKey] = bodyArea.value })
            bodyLabel.append(bodyArea)

            root.append(titleLabel, bodyLabel)
        }

        return root
    }

}
