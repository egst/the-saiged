import Section    from '/js/admin/sections/section.js'
import {isObject} from '/js/core/types.js'

export default class ThreeColumnSection extends Section {

    /** @type {HTMLDivElement} */
    #element
    /** @type {HTMLDivElement} */
    #itemsList = document.createElement('div')
    /** @type {HTMLButtonElement} */
    #addButton = document.createElement('button')

    /**
     * @param {string} heading
     * @param {Array<{title: string, body: string}>} items
     */
    constructor (heading, items) {
        super()
        this.heading  = heading
        this.items    = items
        this.#element = this.#build()
        this.#renderItems()
    }

    static type () {
        return 'three-column'
    }

    /** @param {unknown} data */
    static fromObject (data) {
        if (
            !isObject(data)
            || typeof data.heading !== 'string'
            || !Array.isArray(data.items)
        )
            throw new Error('Invalid ThreeColumnSection data')

        const items = data.items.map(raw => {
            if (
                !isObject(raw)
                || typeof raw.title !== 'string'
                || typeof raw.body  !== 'string'
            )
                throw new Error('Invalid ThreeColumnSection item')
            return {title: raw.title, body: raw.body}
        })

        return new ThreeColumnSection(data.heading, items)
    }

    static createEmpty () {
        return new ThreeColumnSection('', [])
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

        const heading = bar(55, 'sp-bar sp-bar--heading')
        heading.style.marginBottom = '6px'

        const row = document.createElement('div')
        row.className = 'sp-row'
        for (let i = 0; i < 3; i++) {
            const card = document.createElement('div')
            card.className = 'sp-card-mini'
            card.append(bar(80, 'sp-bar sp-bar--heading'), bar(100), bar(90), bar(75))
            row.append(card)
        }

        root.append(heading, row)
        return root
    }

    toObject () {
        return {
            heading: this.heading,
            items:   this.items.map(item => ({title: item.title, body: item.body})),
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

        this.#itemsList.className = 'carousel-items carousel-items--vertical'

        this.#addButton.type        = 'button'
        this.#addButton.className   = 'carousel-add'
        this.#addButton.textContent = '+ Add column'
        this.#addButton.addEventListener('click', () => this.#onAdd())

        root.append(headingLabel, this.#itemsList)
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
        removeButton.title       = 'Remove column'
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
