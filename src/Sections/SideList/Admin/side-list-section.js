import Section    from '/js/admin/sections/section.js'
import {isObject} from '/js/core/types.js'

export default class SideListSection extends Section {

    /** @type {HTMLDivElement} */
    #element
    /** @type {HTMLDivElement} */
    #itemsList = document.createElement('div')
    /** @type {HTMLButtonElement} */
    #addButton = document.createElement('button')

    /**
     * @param {string} heading
     * @param {string} body
     * @param {string} linkText
     * @param {string} linkHref
     * @param {string} panelHeading
     * @param {Array<{title: string, body: string}>} items
     */
    constructor (heading, body, linkText, linkHref, panelHeading, items) {
        super()
        this.heading      = heading
        this.body         = body
        this.linkText     = linkText
        this.linkHref     = linkHref
        this.panelHeading = panelHeading
        this.items        = items
        this.#element     = this.#build()
        this.#renderItems()
    }

    static type () {
        return 'side-list'
    }

    /** @param {unknown} data */
    static fromObject (data) {
        if (
            !isObject(data)
            || typeof data.heading      !== 'string'
            || typeof data.body         !== 'string'
            || typeof data.linkText     !== 'string'
            || typeof data.linkHref     !== 'string'
            || typeof data.panelHeading !== 'string'
            || !Array.isArray(data.items)
        )
            throw new Error('Invalid SideListSection data')

        const items = data.items.map(raw => {
            if (
                !isObject(raw)
                || typeof raw.title !== 'string'
                || typeof raw.body  !== 'string'
            )
                throw new Error('Invalid SideListSection item')
            return {title: raw.title, body: raw.body}
        })

        return new SideListSection(
            data.heading,
            data.body,
            data.linkText,
            data.linkHref,
            data.panelHeading,
            items,
        )
    }

    static createEmpty () {
        return new SideListSection('', '', '', '', '', [])
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
        left.append(bar(70, 'sp-bar sp-bar--heading'), bar(95), bar(82), bar(45, 'sp-bar sp-bar--btn'))

        const panel = document.createElement('div')
        panel.style.cssText = 'flex:1; border-left:2px solid rgba(0,0,0,0.35); padding-left:8px; display:flex; flex-direction:column; gap:5px'
        panel.append(bar(60, 'sp-bar sp-bar--heading'), bar(90), bar(75), bar(85), bar(65))

        const row = document.createElement('div')
        row.className = 'sp-row'
        row.append(left, panel)

        root.append(row)
        return root
    }

    toObject () {
        return {
            heading:      this.heading,
            body:         this.body,
            linkText:     this.linkText,
            linkHref:     this.linkHref,
            panelHeading: this.panelHeading,
            items:        this.items.map(item => ({title: item.title, body: item.body})),
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
        bodyLabel.append('Body (blank line = new paragraph) ')
        const bodyArea = document.createElement('textarea')
        bodyArea.name  = 'body'
        bodyArea.rows  = 6
        bodyArea.value = this.body
        bodyArea.addEventListener('input', () => { this.body = bodyArea.value })
        bodyLabel.append(bodyArea)

        const linkTextLabel = document.createElement('label')
        linkTextLabel.append('Link text ')
        const linkTextInput = document.createElement('input')
        linkTextInput.name  = 'linkText'
        linkTextInput.value = this.linkText
        linkTextInput.addEventListener('input', () => { this.linkText = linkTextInput.value })
        linkTextLabel.append(linkTextInput)

        const linkHrefLabel = document.createElement('label')
        linkHrefLabel.append('Link URL ')
        const linkHrefInput = document.createElement('input')
        linkHrefInput.name  = 'linkHref'
        linkHrefInput.value = this.linkHref
        linkHrefInput.addEventListener('input', () => { this.linkHref = linkHrefInput.value })
        linkHrefLabel.append(linkHrefInput)

        const panelHeadingLabel = document.createElement('label')
        panelHeadingLabel.append('Panel heading ')
        const panelHeadingInput = document.createElement('input')
        panelHeadingInput.name  = 'panelHeading'
        panelHeadingInput.value = this.panelHeading
        panelHeadingInput.addEventListener('input', () => { this.panelHeading = panelHeadingInput.value })
        panelHeadingLabel.append(panelHeadingInput)

        this.#itemsList.className = 'carousel-items carousel-items--vertical'

        this.#addButton.type        = 'button'
        this.#addButton.className   = 'carousel-add'
        this.#addButton.textContent = '+ Add item'
        this.#addButton.addEventListener('click', () => this.#onAdd())

        root.append(headingLabel, bodyLabel, linkTextLabel, linkHrefLabel, panelHeadingLabel, this.#itemsList)
        return root
    }

    #renderItems () {
        this.#itemsList.replaceChildren()
        for (let index = 0; index < this.items.length; index++)
            this.#itemsList.append(this.#renderItem(index))
        this.#itemsList.append(this.#addButton)
    }

    /** @param {number} index */
    #renderItem (index) {
        const item = this.items[index]
        const card = document.createElement('div')
        card.className = 'carousel-item'

        const titleLabel = document.createElement('label')
        titleLabel.append('Title ')
        const titleInput = document.createElement('input')
        titleInput.name  = 'title'
        titleInput.value = item.title
        titleInput.addEventListener('input', () => { item.title = titleInput.value })
        titleLabel.append(titleInput)

        const bodyLabel = document.createElement('label')
        bodyLabel.append('Body ')
        const bodyArea = document.createElement('textarea')
        bodyArea.name  = 'body'
        bodyArea.value = item.body
        bodyArea.addEventListener('input', () => { item.body = bodyArea.value })
        bodyLabel.append(bodyArea)

        const controls = document.createElement('div')
        controls.className = 'carousel-item-controls'

        const upButton = document.createElement('button')
        upButton.type        = 'button'
        upButton.className   = 'carousel-item-move'
        upButton.title       = 'Move up'
        upButton.textContent = '▲'
        upButton.disabled    = index === 0
        upButton.addEventListener('click', () => this.#move(index, -1))

        const downButton = document.createElement('button')
        downButton.type        = 'button'
        downButton.className   = 'carousel-item-move'
        downButton.title       = 'Move down'
        downButton.textContent = '▼'
        downButton.disabled    = index === this.items.length - 1
        downButton.addEventListener('click', () => this.#move(index, 1))

        const removeButton = document.createElement('button')
        removeButton.type        = 'button'
        removeButton.className   = 'carousel-item-remove'
        removeButton.title       = 'Remove item'
        removeButton.textContent = '×'
        removeButton.addEventListener('click', () => this.#remove(index))

        controls.append(upButton, downButton, removeButton)
        card.append(titleLabel, bodyLabel, controls)
        return card
    }

    #onAdd () {
        this.items.push({title: '', body: ''})
        this.#renderItems()
        this.#fireInput()
    }

    /**
     * @param {number} index
     * @param {number} direction
     */
    #move (index, direction) {
        const target = index + direction
        if (target < 0 || target >= this.items.length)
            return
        const [moved] = this.items.splice(index, 1)
        this.items.splice(target, 0, moved)
        this.#renderItems()
        this.#fireInput()
    }

    /** @param {number} index */
    #remove (index) {
        this.items.splice(index, 1)
        this.#renderItems()
        this.#fireInput()
    }

    #fireInput () {
        this.#element.dispatchEvent(new Event('input', {bubbles: true}))
    }

}
