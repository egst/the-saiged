import Section    from '/js/admin/sections/section.js'
import {isObject} from '/js/core/types.js'

export default class ArticleSection extends Section {

    /** @type {HTMLDivElement} */
    #element

    /** @param {string} content */
    constructor (content) {
        super()
        this.content  = content
        this.#element = this.#build()
    }

    static type () {
        return 'article'
    }

    /** @param {unknown} data */
    static fromObject (data) {
        if (!isObject(data) || typeof data.content !== 'string')
            throw new Error('Invalid ArticleSection data')
        return new ArticleSection(data.content)
    }

    static createEmpty () {
        return new ArticleSection('')
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
        root.append(bar(50, 'sp-bar sp-bar--heading'), bar(90), bar(83), bar(88), bar(62))
        return root
    }

    toObject () {
        return {content: this.content}
    }

    get element () {
        return this.#element
    }

    #build () {
        const root = document.createElement('div')

        const label = document.createElement('label')
        label.append('Content (Markdown: ## heading, **bold**, blank line = new paragraph) ')
        const area = document.createElement('textarea')
        area.name  = 'content'
        area.rows  = 16
        area.value = this.content
        area.addEventListener('input', () => { this.content = area.value })
        label.append(area)

        root.append(label)
        return root
    }

}
