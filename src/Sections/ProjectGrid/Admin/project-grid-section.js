import Section      from '/js/admin/sections/section.js'
import {isObject}   from '/js/core/types.js'
import UploadPicker from '/js/admin/uploads/upload-picker.js'

/**
 * @import Api    from '/js/admin/api.js'
 * @import Upload from '/js/admin/uploads/upload.js'
 */

const VARIANT_WIDTH  = 1200
const VARIANT_HEIGHT = 800

export default class ProjectGridSection extends Section {

    /** @type {Api} */
    #api
    /** @type {HTMLDivElement} */
    #element
    /** @type {HTMLDivElement} */
    #itemsList = document.createElement('div')
    /** @type {HTMLButtonElement} */
    #addButton = document.createElement('button')

    /**
     * @param {Array<{uploadId: number | null, type: string, heading: string, body: string, thumbUrl: string | null}>} items
     * @param {Api} api
     */
    constructor (items, api) {
        super()
        this.items    = items
        this.#api     = api
        this.#element = this.#build()
        this.#renderItems()
    }

    static type () {
        return 'project-grid'
    }

    /**
     * @param {unknown} data
     * @param {Api}     api
     */
    static fromObject (data, api) {
        if (!isObject(data) || !Array.isArray(data.items))
            throw new Error('Invalid ProjectGridSection data')

        const items = data.items.map(raw => {
            if (
                !isObject(raw)
                || typeof raw.uploadId !== 'number'
                || typeof raw.type     !== 'string'
                || typeof raw.heading  !== 'string'
                || typeof raw.body     !== 'string'
            )
                throw new Error('Invalid ProjectGridSection item')
            return {
                uploadId: raw.uploadId,
                type:     raw.type,
                heading:  raw.heading,
                body:     raw.body,
                thumbUrl: `/uploads/${raw.uploadId}/thumb-200x200.webp`,
            }
        })
        return new ProjectGridSection(items, api)
    }

    /** @param {Api} api */
    static createEmpty (api) {
        return new ProjectGridSection([], api)
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

        const card = () => {
            const wrap = document.createElement('div')
            wrap.className = 'sp-col'

            const img = document.createElement('div')
            img.className        = 'sp-block'
            img.style.height     = '28px'
            img.style.marginBottom = '4px'

            wrap.append(img, bar(40), bar(85, 'sp-bar sp-bar--heading'), bar(95), bar(80))
            return wrap
        }

        const row = document.createElement('div')
        row.className = 'sp-row'
        row.append(card(), card())

        root.append(row)
        return root
    }

    toObject () {
        return {
            items: this.items.map(item => ({
                uploadId: item.uploadId,
                type:     item.type,
                heading:  item.heading,
                body:     item.body,
            })),
        }
    }

    get element () {
        return this.#element
    }

    #build () {
        const root = document.createElement('div')

        this.#itemsList.className = 'carousel-items'

        this.#addButton.type        = 'button'
        this.#addButton.className   = 'carousel-add project-grid-add'
        this.#addButton.textContent = '+ Add project'
        this.#addButton.addEventListener('click', () => this.#onAdd())

        root.append(this.#itemsList)
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
        card.className = 'carousel-item project-grid-item'

        const preview = document.createElement('div')
        preview.className = 'carousel-item-preview'
        if (item.thumbUrl !== null) {
            const img = document.createElement('img')
            img.src     = item.thumbUrl
            img.alt     = item.heading
            img.loading = 'lazy'
            preview.append(img)
        }
        preview.addEventListener('click', () => this.#onReplace(index))

        const typeLabel = document.createElement('label')
        typeLabel.append('Type ')
        const typeInput = document.createElement('input')
        typeInput.name  = 'type'
        typeInput.value = item.type
        typeInput.addEventListener('input', () => { item.type = typeInput.value })
        typeLabel.append(typeInput)

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

        const controls = document.createElement('div')
        controls.className = 'carousel-item-controls'

        const upButton = document.createElement('button')
        upButton.type        = 'button'
        upButton.className   = 'carousel-item-move'
        upButton.title       = 'Move up'
        upButton.textContent = '◀'
        upButton.disabled    = index === 0
        upButton.addEventListener('click', () => this.#move(index, -1))

        const downButton = document.createElement('button')
        downButton.type        = 'button'
        downButton.className   = 'carousel-item-move'
        downButton.title       = 'Move down'
        downButton.textContent = '▶'
        downButton.disabled    = index === this.items.length - 1
        downButton.addEventListener('click', () => this.#move(index, 1))

        const removeButton = document.createElement('button')
        removeButton.type        = 'button'
        removeButton.className   = 'carousel-item-remove'
        removeButton.title       = 'Remove project'
        removeButton.textContent = '×'
        removeButton.addEventListener('click', () => this.#remove(index))

        controls.append(upButton, downButton, removeButton)
        card.append(preview, typeLabel, headingLabel, bodyLabel, controls)
        return card
    }

    async #onAdd () {
        const upload = await new UploadPicker(this.#api).open()
        if (upload === null)
            return

        try {
            await this.#api.ensureVariant(upload.id, VARIANT_WIDTH, VARIANT_HEIGHT)
        } catch {}

        this.items.push({
            uploadId: upload.id,
            type:     '',
            heading:  '',
            body:     '',
            thumbUrl: upload.thumbUrl,
        })
        this.#renderItems()
        this.#fireInput()
    }

    /** @param {number} index */
    async #onReplace (index) {
        const upload = await new UploadPicker(this.#api).open()
        if (upload === null)
            return

        try {
            await this.#api.ensureVariant(upload.id, VARIANT_WIDTH, VARIANT_HEIGHT)
        } catch {}

        this.items[index].uploadId = upload.id
        this.items[index].thumbUrl = upload.thumbUrl
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
