import Section    from '/js/admin/sections/section.js'
import {isObject} from '/js/core/types.js'

export default class ContactListSection extends Section {

    /** @type {HTMLDivElement} */
    #element
    /** @type {HTMLDivElement} */
    #itemsList = document.createElement('div')
    /** @type {HTMLButtonElement} */
    #addButton = document.createElement('button')

    /**
     * @param {string} heading
     * @param {Array<{heading: string, body: string, note: string}>} items
     */
    constructor (heading, items) {
        super()
        this.heading  = heading
        this.items    = items
        this.#element = this.#build()
        this.#renderItems()
    }

    static type () {
        return 'contact-list'
    }

    /** @param {unknown} data */
    static fromObject (data) {
        if (!isObject(data) || typeof data.heading !== 'string' || !Array.isArray(data.items))
            throw new Error('Invalid ContactListSection data')

        const items = data.items.map(raw => {
            if (
                !isObject(raw)
                || typeof raw.heading !== 'string'
                || typeof raw.body    !== 'string'
                || typeof raw.note    !== 'string'
            )
                throw new Error('Invalid ContactListSection item')
            return {heading: raw.heading, body: raw.body, note: raw.note}
        })
        return new ContactListSection(data.heading, items)
    }

    static createEmpty () {
        return new ContactListSection('', [])
    }

    /** @returns {HTMLDivElement} */
    static preview () {
        const root = document.createElement('div')
        root.className = 'sp-preview sp-preview--light'
        root.style.gap = '0'
        root.style.justifyContent = 'space-around'

        const bar = (/** @type {number} */ w, /** @type {string} */ cls = 'sp-bar') => {
            const el = document.createElement('div')
            el.className   = cls
            el.style.width = `${w}%`
            return el
        }

        for (let i = 0; i < 3; i++) {
            if (i > 0) {
                const sep = document.createElement('div')
                sep.style.cssText = 'width:100%; height:1px; background:rgba(0,0,0,0.10); flex-shrink:0'
                root.append(sep)
            }
            const row = document.createElement('div')
            row.className = 'sp-row'
            row.style.cssText = 'gap:10px; padding:6px 0; flex-shrink:0'
            row.append(bar(28, 'sp-bar sp-bar--heading'), bar(55))
            root.append(row)
        }

        return root
    }

    toObject () {
        return {
            heading: this.heading,
            items:   this.items.map(item => ({
                heading: item.heading,
                body:    item.body,
                note:    item.note,
            })),
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
        this.#addButton.textContent = '+ Add entry'
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
        card.className = 'carousel-item contact-list-editor-item'

        const headingLabel = document.createElement('label')
        headingLabel.append('Heading ')
        const headingInput = document.createElement('input')
        headingInput.name  = 'heading'
        headingInput.value = item.heading
        headingInput.addEventListener('input', () => { item.heading = headingInput.value })
        headingLabel.append(headingInput)

        const bodyLabel = document.createElement('label')
        bodyLabel.append('Body ')
        const bodyArea = document.createElement('textarea')
        bodyArea.name  = 'body'
        bodyArea.value = item.body
        bodyArea.addEventListener('input', () => { item.body = bodyArea.value })
        bodyLabel.append(bodyArea)

        const noteLabel = document.createElement('label')
        noteLabel.append('Note ')
        const noteInput = document.createElement('input')
        noteInput.name  = 'note'
        noteInput.value = item.note
        noteInput.addEventListener('input', () => { item.note = noteInput.value })
        noteLabel.append(noteInput)

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
        removeButton.title       = 'Remove entry'
        removeButton.textContent = '×'
        removeButton.addEventListener('click', () => this.#remove(index))

        controls.append(upButton, downButton, removeButton)
        card.append(headingLabel, bodyLabel, noteLabel, controls)
        return card
    }

    #onAdd () {
        this.items.push({heading: '', body: '', note: ''})
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
