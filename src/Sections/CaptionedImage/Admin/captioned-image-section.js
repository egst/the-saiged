import Section      from '/js/admin/sections/section.js'
import {isObject}   from '/js/core/types.js'
import UploadPicker from '/js/admin/uploads/upload-picker.js'

/**
 * @import Api    from '/js/admin/api.js'
 * @import Upload from '/js/admin/uploads/upload.js'
 */

const VARIANT_WIDTH  = 1920
const VARIANT_HEIGHT = 1280

export default class CaptionedImageSection extends Section {

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
        return 'captioned-image'
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
            throw new Error('Invalid CaptionedImageSection data')
        return new CaptionedImageSection(
            data.uploadId,
            data.caption,
            `/uploads/${data.uploadId}/thumb-200x200.webp`,
            api,
        )
    }

    /** @param {Api} api */
    static createEmpty (api) {
        return new CaptionedImageSection(null, '', null, api)
    }

    /** @returns {HTMLDivElement} */
    static preview () {
        const root = document.createElement('div')
        root.className = 'sp-preview sp-preview--light'
        root.style.cssText += '; padding: 0; gap: 0; justify-content: flex-start'

        const imgArea = document.createElement('div')
        imgArea.className = 'sp-img-tile'
        imgArea.style.cssText = 'width: 100%; flex: 1; border-radius: 3px 3px 0 0'

        const captionArea = document.createElement('div')
        captionArea.style.cssText = 'flex: 0 0 22px; padding: 0 12px; display: flex; align-items: center'

        const captionBar = document.createElement('div')
        captionBar.className = 'sp-bar'
        captionBar.style.width = '58%'
        captionArea.append(captionBar)

        root.append(imgArea, captionArea)
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

        this.#addButton.type        = 'button'
        this.#addButton.className   = 'carousel-add image-break-add'
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

        this.uploadId  = upload.id
        this.#thumbUrl = upload.thumbUrl
        this.#syncPickState(true)
        this.#fireInput()

        this.#api.ensureVariant(upload.id, VARIANT_WIDTH, VARIANT_HEIGHT).catch(() => {})
    }

    /** @param {boolean} trackLoad */
    #syncPickState (trackLoad = false) {
        if (this.#thumbUrl === null) {
            this.#addButton.hidden     = false
            this.#pickedPreview.hidden = true
            return
        }

        this.#addButton.hidden     = true
        this.#pickedPreview.hidden = false
        this.#pickedPreview.replaceChildren()

        const img = document.createElement('img')
        img.alt = this.caption

        if (trackLoad) {
            const thumbUrl = this.#thumbUrl
            const spinner  = document.createElement('div')
            spinner.className = 'img-spinner'
            this.#pickedPreview.append(spinner, img)
            this._fireImageLoading()
            const done = () => { spinner.remove(); this._fireImageLoaded() }
            img.addEventListener('load',  done, {once: true})
            img.addEventListener('error', done, {once: true})
            requestAnimationFrame(() => requestAnimationFrame(() => { img.src = thumbUrl }))
        } else {
            img.src = this.#thumbUrl
            this.#pickedPreview.append(img)
        }
    }

    #fireInput () {
        this.#element.dispatchEvent(new Event('input', {bubbles: true}))
    }

}
