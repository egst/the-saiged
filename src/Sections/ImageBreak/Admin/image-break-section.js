import Section      from '/js/admin/sections/section.js'
import {isObject}   from '/js/core/types.js'
import UploadPicker from '/js/admin/uploads/upload-picker.js'

/**
 * @import Api    from '/js/admin/api.js'
 * @import Upload from '/js/admin/uploads/upload.js'
 */

/** Matches ImageBreakSection.php's variant size constants. */
const VARIANT_WIDTH  = 1920
const VARIANT_HEIGHT = 1080

export default class ImageBreakSection extends Section {

    /** @type {Api} */
    #api
    /** @type {HTMLDivElement} */
    #element
    /** @type {string | null} */
    #thumbUrl = null
    /** @type {HTMLButtonElement} */
    #addButton = document.createElement('button')
    /** @type {HTMLDivElement} */
    #pickedPreview = document.createElement('div')

    /**
     * @param {number | null} uploadId
     * @param {string}        caption
     * @param {string | null} thumbUrl
     * @param {Api}           api
     */
    constructor (uploadId, caption, thumbUrl, api) {
        super()
        this.uploadId  = uploadId
        this.caption   = caption
        this.#thumbUrl = thumbUrl
        this.#api      = api
        this.#element  = this.#build()
    }

    static type () {
        return 'image-break'
    }

    /**
     * @param {unknown} data
     * @param {Api}     api
     */
    static fromObject (data, api) {
        if (
            !isObject(data)
            || typeof data.uploadId !== 'number'
            || typeof data.caption  !== 'string'
        )
            throw new Error('Invalid ImageBreakSection data')
        return new ImageBreakSection(
            data.uploadId,
            data.caption,
            `/uploads/${data.uploadId}/thumb-200x200.webp`,
            api,
        )
    }

    /** @param {Api} api */
    static createEmpty (api) {
        return new ImageBreakSection(null, '', null, api)
    }

    /** @returns {HTMLDivElement} */
    static preview () {
        const root = document.createElement('div')
        root.className = 'sp-preview sp-preview--dark sp-preview--img'
        const caption = document.createElement('div')
        caption.className   = 'sp-bar sp-bar--caption'
        caption.style.width = '45%'
        root.append(caption)
        return root
    }

    toObject () {
        return {
            uploadId: this.uploadId,
            caption:  this.caption,
        }
    }

    get element () {
        return this.#element
    }

    #build () {
        const root = document.createElement('div')

        this.#addButton.type      = 'button'
        this.#addButton.className = 'carousel-add image-break-add'
        this.#addButton.textContent = '+ Add image'
        this.#addButton.addEventListener('click', () => this.#pick())

        this.#pickedPreview.className = 'image-break-picked'
        this.#pickedPreview.addEventListener('click', () => this.#pick())

        const captionLabel = document.createElement('label')
        captionLabel.append('Caption ')
        const captionInput = document.createElement('input')
        captionInput.name  = 'caption'
        captionInput.value = this.caption
        captionInput.addEventListener('input', () => { this.caption = captionInput.value })
        captionLabel.append(captionInput)

        this.#syncPickState()

        root.append(this.#addButton, this.#pickedPreview, captionLabel)
        return root
    }

    async #pick () {
        const upload = await new UploadPicker(this.#api).open()
        if (upload === null)
            return

        try {
            await this.#api.ensureVariant(upload.id, VARIANT_WIDTH, VARIANT_HEIGHT)
        } catch {
            // best-effort: variant generated on first public request if this fails
        }

        this.uploadId  = upload.id
        this.#thumbUrl = upload.thumbUrl
        this.#syncPickState()
        this.#fireInput()
    }

    #syncPickState () {
        if (this.#thumbUrl === null) {
            this.#addButton.hidden    = false
            this.#pickedPreview.hidden = true
            return
        }

        this.#addButton.hidden    = true
        this.#pickedPreview.hidden = false
        this.#pickedPreview.replaceChildren()

        const img = document.createElement('img')
        img.src     = this.#thumbUrl
        img.alt     = this.caption
        img.loading = 'lazy'
        this.#pickedPreview.append(img)
    }

    #fireInput () {
        this.#element.dispatchEvent(new Event('input', {bubbles: true}))
    }

}
