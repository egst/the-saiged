import Section      from '/js/admin/sections/section.js'
import {isObject}   from '/js/core/types.js'
import UploadPicker from '/js/admin/uploads/upload-picker.js'

/**
 * @import Api    from '/js/admin/api.js'
 * @import Upload from '/js/admin/uploads/upload.js'
 */

const VARIANT_WIDTH  = 460
const VARIANT_HEIGHT = 400

export default class ArtistGridSection extends Section {

    /** @type {Api} */
    #api
    /** @type {HTMLDivElement} */
    #element
    /** @type {HTMLDivElement} */
    #itemsList = document.createElement('div')
    /** @type {HTMLButtonElement} */
    #addButton = document.createElement('button')

    /**
     * @param {string} heading
     * @param {Array<{uploadId: number | null, name: string, birthYear: string, thumbUrl: string | null}>} items
     * @param {Api} api
     */
    constructor (heading, items, api) {
        super()
        this.heading  = heading
        this.items    = items
        this.#api     = api
        this.#element = this.#build()
        this.#renderItems()
    }

    static type () {
        return 'artist-grid'
    }

    /**
     * @param {unknown} data
     * @param {Api}     api
     */
    static fromObject (data, api) {
        if (
            !isObject(data)
            || typeof data.heading !== 'string'
            || !Array.isArray(data.items)
        )
            throw new Error('Invalid ArtistGridSection data')

        const items = data.items.map(raw => {
            if (
                !isObject(raw)
                || (raw.uploadId !== null && typeof raw.uploadId !== 'number')
                || typeof raw.name      !== 'string'
                || typeof raw.birthYear !== 'string'
            )
                throw new Error('Invalid ArtistGridSection item')
            return {
                uploadId:  raw.uploadId,
                name:      raw.name,
                birthYear: raw.birthYear,
                thumbUrl:  typeof raw.uploadId === 'number'
                    ? `/uploads/${raw.uploadId}/thumb-200x200.webp`
                    : null,
            }
        })

        return new ArtistGridSection(data.heading, items, api)
    }

    /** @param {Api} api */
    static createEmpty (api) {
        return new ArtistGridSection('', [], api)
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
        for (let i = 0; i < 4; i++) {
            const col = document.createElement('div')
            col.className = 'sp-col'

            const img = document.createElement('div')
            img.className = 'sp-img-tile'
            img.style.cssText = 'width:100%; aspect-ratio:1.15/1; margin-bottom:4px'

            col.append(img, bar(90, 'sp-bar sp-bar--heading'), bar(55))
            row.append(col)
        }

        root.append(heading, row)
        return root
    }

    toObject () {
        return {
            heading: this.heading,
            items:   this.items.map(item => ({
                uploadId:  item.uploadId,
                name:      item.name,
                birthYear: item.birthYear,
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

        this.#itemsList.className = 'carousel-items'

        this.#addButton.type        = 'button'
        this.#addButton.className   = 'carousel-add artist-grid-add'
        this.#addButton.textContent = '+ Add artist'
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
        card.className = 'carousel-item artist-grid-item'

        const preview = document.createElement('div')
        preview.className = 'carousel-item-preview'
        if (item.thumbUrl !== null) {
            const img = document.createElement('img')
            img.src     = item.thumbUrl
            img.alt     = item.name
            img.loading = 'lazy'
            preview.append(img)
        }
        preview.addEventListener('click', () => this.#onReplace(index))

        const nameLabel = document.createElement('label')
        nameLabel.append('Name ')
        const nameInput = document.createElement('input')
        nameInput.name  = 'name'
        nameInput.value = item.name
        nameInput.addEventListener('input', () => { item.name = nameInput.value })
        nameLabel.append(nameInput)

        const birthYearLabel = document.createElement('label')
        birthYearLabel.append('Birth year ')
        const birthYearInput = document.createElement('input')
        birthYearInput.name  = 'birthYear'
        birthYearInput.value = item.birthYear
        birthYearInput.addEventListener('input', () => { item.birthYear = birthYearInput.value })
        birthYearLabel.append(birthYearInput)

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
        removeButton.title       = 'Remove artist'
        removeButton.textContent = '×'
        removeButton.addEventListener('click', () => this.#remove(index))

        controls.append(upButton, downButton, removeButton)
        card.append(preview, nameLabel, birthYearLabel, controls)
        return card
    }

    async #onAdd () {
        const upload = await new UploadPicker(this.#api).open()
        if (upload === null)
            return

        const newIndex = this.items.length
        this.items.push({
            uploadId:  upload.id,
            name:      '',
            birthYear: '',
            thumbUrl:  upload.thumbUrl,
        })
        this.#renderItems()
        this.#fireInput()
        this.#trackImageLoad(newIndex)

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
        this.#trackImageLoad(index)

        this.#api.ensureVariant(upload.id, VARIANT_WIDTH, VARIANT_HEIGHT).catch(() => {})
    }

    /** @param {number} index */
    #trackImageLoad (index) {
        const cards   = this.#itemsList.querySelectorAll('.carousel-item')
        const preview = /** @type {HTMLElement | null} */ (cards[index]?.querySelector('.carousel-item-preview') ?? null)
        if (!preview) return

        const img = /** @type {HTMLImageElement | null} */ (preview.querySelector('img'))
        if (!img) return

        const src     = img.src
        img.removeAttribute('src')

        const spinner = document.createElement('div')
        spinner.className = 'img-spinner'
        preview.append(spinner)
        this._fireImageLoading()

        const done = () => { spinner.remove(); this._fireImageLoaded() }
        img.addEventListener('load',  done, {once: true})
        img.addEventListener('error', done, {once: true})
        requestAnimationFrame(() => requestAnimationFrame(() => { img.src = src }))
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
