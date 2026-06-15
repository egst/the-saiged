import Section      from '/js/admin/sections/section.js'
import {isObject}   from '/js/core/types.js'
import UploadPicker from '/js/admin/uploads/upload-picker.js'

/**
 * @import Api    from '/js/admin/api.js'
 * @import Upload from '/js/admin/uploads/upload.js'
 */

/** Matches LinkCarouselSection.php's variant size constants. */
const VARIANT_WIDTH  = 1920
const VARIANT_HEIGHT = 1080

export default class LinkCarouselSection extends Section {

    /** @type {Api} */
    #api
    /** @type {HTMLDivElement} */
    #element
    /** @type {HTMLDivElement} */
    #itemsList = document.createElement('div')
    /** @type {HTMLButtonElement} */
    #addButton = document.createElement('button')

    /**
     * @param {Array<{uploadId: number, eyebrow: string, title: string, buttonText: string, buttonHref: string, thumbUrl: string | null}>} items
     * @param {Api} api
     */
    constructor (items, api) {
        super()
        this.items = items
        this.#api  = api
        this.#element = this.#build()
        this.#renderItems()
    }

    static type () {
        return 'link-carousel'
    }

    /**
     * @param {unknown} data
     * @param {Api}     api
     */
    static fromObject (data, api) {
        if (!isObject(data) || !Array.isArray(data.items))
            throw new Error('Invalid LinkCarouselSection data')

        const items = data.items.map(raw => {
            if (
                !isObject(raw)
                || typeof raw.uploadId   !== 'number'
                || typeof raw.eyebrow    !== 'string'
                || typeof raw.title      !== 'string'
                || typeof raw.buttonText !== 'string'
                || typeof raw.buttonHref !== 'string'
            )
                throw new Error('Invalid LinkCarouselSection item')
            return {
                uploadId:   raw.uploadId,
                eyebrow:    raw.eyebrow,
                title:      raw.title,
                buttonText: raw.buttonText,
                buttonHref: raw.buttonHref,
                thumbUrl:   `/uploads/${raw.uploadId}/thumb-200x200.webp`,
            }
        })
        return new LinkCarouselSection(items, api)
    }

    /** @param {Api} api */
    static createEmpty (api) {
        return new LinkCarouselSection([], api)
    }

    /** @returns {HTMLDivElement} */
    static preview () {
        const root = document.createElement('div')
        root.className = 'sp-preview sp-preview--img'

        const bar = (/** @type {number} */ w, /** @type {string} */ cls = 'sp-bar') => {
            const el = document.createElement('div')
            el.className   = cls
            el.style.width = `${w}%`
            return el
        }

        const content = document.createElement('div')
        content.className = 'sp-slide-content'
        content.append(bar(30, 'sp-bar sp-bar--eyebrow'), bar(72, 'sp-bar sp-bar--heading'), bar(52))

        const stack = document.createElement('div')
        stack.className = 'sp-stack'
        for (let i = 0; i < 3; i++) {
            const edge = document.createElement('div')
            edge.className = 'sp-stack-edge'
            stack.append(edge)
        }

        root.append(content, stack)
        return root
    }

    toObject () {
        return {
            items: this.items.map(item => ({
                uploadId:   item.uploadId,
                eyebrow:    item.eyebrow,
                title:      item.title,
                buttonText: item.buttonText,
                buttonHref: item.buttonHref,
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
        this.#addButton.className   = 'carousel-add link-carousel-add'
        this.#addButton.textContent = '+ Add slide'
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
        card.className = 'carousel-item link-carousel-item'

        const preview = document.createElement('div')
        preview.className = 'carousel-item-preview'
        if (item.thumbUrl !== null) {
            const img = document.createElement('img')
            img.src     = item.thumbUrl
            img.alt     = item.title
            img.loading = 'lazy'
            preview.append(img)
        }
        preview.addEventListener('click', () => this.#onReplace(index))

        const eyebrowLabel = document.createElement('label')
        eyebrowLabel.append('Eyebrow ')
        const eyebrowInput = document.createElement('input')
        eyebrowInput.name  = 'eyebrow'
        eyebrowInput.value = item.eyebrow
        eyebrowInput.addEventListener('input', () => { item.eyebrow = eyebrowInput.value })
        eyebrowLabel.append(eyebrowInput)

        const titleLabel = document.createElement('label')
        titleLabel.append('Title ')
        const titleInput = document.createElement('input')
        titleInput.name  = 'title'
        titleInput.value = item.title
        titleInput.addEventListener('input', () => { item.title = titleInput.value })
        titleLabel.append(titleInput)

        const buttonTextLabel = document.createElement('label')
        buttonTextLabel.append('Button text ')
        const buttonTextInput = document.createElement('input')
        buttonTextInput.name  = 'buttonText'
        buttonTextInput.value = item.buttonText
        buttonTextInput.addEventListener('input', () => { item.buttonText = buttonTextInput.value })
        buttonTextLabel.append(buttonTextInput)

        const buttonHrefLabel = document.createElement('label')
        buttonHrefLabel.append('Button link ')
        const buttonHrefInput = document.createElement('input')
        buttonHrefInput.name  = 'buttonHref'
        buttonHrefInput.value = item.buttonHref
        buttonHrefInput.addEventListener('input', () => { item.buttonHref = buttonHrefInput.value })
        buttonHrefLabel.append(buttonHrefInput)

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
        removeButton.title       = 'Remove slide'
        removeButton.textContent = '×'
        removeButton.addEventListener('click', () => this.#remove(index))

        controls.append(upButton, downButton, removeButton)
        card.append(preview, eyebrowLabel, titleLabel, buttonTextLabel, buttonHrefLabel, controls)
        return card
    }

    async #onAdd () {
        const upload = await new UploadPicker(this.#api).open()
        if (upload === null)
            return

        try {
            await this.#api.ensureVariant(upload.id, VARIANT_WIDTH, VARIANT_HEIGHT)
        } catch {
            // best-effort — slow first hit on public side if this fails
        }

        this.items.push({
            uploadId:   upload.id,
            eyebrow:    '',
            title:      '',
            buttonText: '',
            buttonHref: '',
            thumbUrl:   upload.thumbUrl,
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
     * @param {number} direction  -1 = left, +1 = right
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
