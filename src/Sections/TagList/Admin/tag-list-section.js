import Section    from '/js/admin/sections/section.js'
import {isObject} from '/js/core/types.js'

export default class TagListSection extends Section {

    /** @type {HTMLDivElement} */
    #element

    /**
     * @param {string} heading
     * @param {string} body
     * @param {string} tags
     */
    constructor (heading, body, tags) {
        super()
        this.heading  = heading
        this.body     = body
        this.tags     = tags
        this.#element = this.#build()
    }

    static type () {
        return 'tag-list'
    }

    /** @param {unknown} data */
    static fromObject (data) {
        if (
            !isObject(data)
            || typeof data.heading !== 'string'
            || typeof data.body    !== 'string'
            || typeof data.tags    !== 'string'
        )
            throw new Error('Invalid TagListSection data')
        return new TagListSection(data.heading, data.body, data.tags)
    }

    static createEmpty () {
        return new TagListSection('', '', '')
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

        const chip = (/** @type {number} */ w) => {
            const el = document.createElement('div')
            el.style.cssText = `width:${w}%; height:10px; background:rgba(0,0,0,0.12); border:1px solid rgba(0,0,0,0.18); border-radius:1px; flex-shrink:0`
            return el
        }

        const tags = document.createElement('div')
        tags.style.cssText = 'display:flex; gap:5px; flex-wrap:wrap; margin-top:4px'
        tags.append(chip(18), chip(14), chip(22), chip(16), chip(12))

        root.append(bar(55, 'sp-bar sp-bar--heading'), bar(85), tags)
        return root
    }

    toObject () {
        return {
            heading: this.heading,
            body:    this.body,
            tags:    this.tags,
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
        bodyArea.rows  = 3
        bodyArea.value = this.body
        bodyArea.addEventListener('input', () => { this.body = bodyArea.value })
        bodyLabel.append(bodyArea)

        const tagsLabel = document.createElement('label')
        tagsLabel.append('Tags (comma-separated) ')
        const tagsInput = document.createElement('input')
        tagsInput.name  = 'tags'
        tagsInput.value = this.tags
        tagsInput.addEventListener('input', () => { this.tags = tagsInput.value })
        tagsLabel.append(tagsInput)

        root.append(headingLabel, bodyLabel, tagsLabel)
        return root
    }

}
