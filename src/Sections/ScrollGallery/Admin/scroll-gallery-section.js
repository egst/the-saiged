import Section      from '/js/admin/sections/section.js'
import {isObject}   from '/js/core/types.js'
import UploadPicker from '/js/admin/uploads/upload-picker.js'

/**
 * @import Api    from '/js/admin/api.js'
 * @import Upload from '/js/admin/uploads/upload.js'
 */

const VARIANT_WIDTH  = 680
const VARIANT_HEIGHT = 800

export default class ScrollGallerySection extends Section {

    /** @type {Api} */
    #api
    /** @type {HTMLDivElement} */
    #element
    /** @type {HTMLDivElement} */
    #itemsList = document.createElement('div')
    /** @type {HTMLButtonElement} */
    #addButton = document.createElement('button')

    /**
     * @param {Array<{uploadId: number, caption: string, thumbUrl: string | null}>} items
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
        return 'scroll-gallery'
    }

    /**
     * @param {unknown} data
     * @param {Api}     api
     */
    static fromObject (data, api) {
        if (!isObject(data) || !Array.isArray(data.items))
            throw new Error('Invalid ScrollGallerySection data')

        const items = data.items.map(raw => {
            if (
                !isObject(raw)
                || typeof raw.uploadId !== 'number'
                || typeof raw.caption  !== 'string'
            )
                throw new Error('Invalid ScrollGallerySection item')
            return {
                uploadId: raw.uploadId,
                caption:  raw.caption,
                thumbUrl: `/uploads/${raw.uploadId}/thumb-200x200.webp`,
            }
        })

        return new ScrollGallerySection(items, api)
    }

    /** @param {Api} api */
    static createEmpty (api) {
        return new ScrollGallerySection([], api)
    }

    /** @returns {HTMLDivElement} */
    static preview () {
        const root = document.createElement('div')
        root.className = 'sp-preview sp-preview--light'
        root.style.cssText += '; flex-direction: row; gap: 4px; padding: 6px'

        for (let index = 0; index < 3; index++) {
            const tile = document.createElement('div')
            tile.className    = 'sp-img-tile'
            tile.style.cssText = 'flex: 0 0 28%; aspect-ratio: 0.85/1'
            root.append(tile)
        }

        return root
    }

    toObject () {
        return {
            items: this.items.map(item => ({
                uploadId: item.uploadId,
                caption:  item.caption,
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
        this.#addButton.className   = 'carousel-add scroll-gallery-add'
        this.#addButton.textContent = '+ Add image'
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
        card.className = 'carousel-item scroll-gallery-item-admin'

        const preview = document.createElement('div')
        preview.className = 'carousel-item-preview'
        const img = document.createElement('img')
        if (item.thumbUrl !== null)
            img.src = item.thumbUrl
        img.alt = item.caption
        img.loading = 'lazy'
        preview.append(img)
        preview.addEventListener('click', () => this.#onReplace(index))

        const captionLabel = document.createElement('label')
        captionLabel.append('Caption ')
        const captionInput = document.createElement('input')
        captionInput.name  = 'caption'
        captionInput.value = item.caption
        captionInput.addEventListener('input', () => { item.caption = captionInput.value })
        captionLabel.append(captionInput)

        const controls = document.createElement('div')
        controls.className = 'carousel-item-controls'

        const upButton = document.createElement('button')
        upButton.type        = 'button'
        upButton.className   = 'carousel-item-move'
        upButton.title       = 'Move left'
        upButton.textContent = '◀'
        upButton.disabled    = index === 0
        upButton.addEventListener('click', () => this.#move(index, -1))

        const downButton = document.createElement('button')
        downButton.type        = 'button'
        downButton.className   = 'carousel-item-move'
        downButton.title       = 'Move right'
        downButton.textContent = '▶'
        downButton.disabled    = index === this.items.length - 1
        downButton.addEventListener('click', () => this.#move(index, 1))

        const removeButton = document.createElement('button')
        removeButton.type        = 'button'
        removeButton.className   = 'carousel-item-remove'
        removeButton.title       = 'Remove image'
        removeButton.textContent = '×'
        removeButton.addEventListener('click', () => this.#remove(index))

        controls.append(upButton, downButton, removeButton)
        card.append(preview, captionLabel, controls)
        return card
    }

    async #onAdd () {
        const upload = await new UploadPicker(this.#api).open()
        if (upload === null)
            return

        this.items.push({
            uploadId: upload.id,
            caption:  '',
            thumbUrl: upload.thumbUrl,
        })
        this.#renderItems()
        this.#fireInput()

        this.#api.ensureVariant(upload.id, VARIANT_WIDTH, VARIANT_HEIGHT).catch(() => {})
    }

    /** @param {number} index */
    async #onReplace (index) {
        const upload = await new UploadPicker(this.#api).open()
        if (upload === null)
            return

        this.items[index].uploadId = upload.id
        this.items[index].thumbUrl = upload.thumbUrl
        this.#renderItems()
        this.#fireInput()

        this.#api.ensureVariant(upload.id, VARIANT_WIDTH, VARIANT_HEIGHT).catch(() => {})
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
