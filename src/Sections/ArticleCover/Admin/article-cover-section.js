import Section      from '/js/admin/sections/section.js'
import {isObject}   from '/js/core/types.js'
import UploadPicker from '/js/admin/uploads/upload-picker.js'

/**
 * @import Api    from '/js/admin/api.js'
 * @import Upload from '/js/admin/uploads/upload.js'
 */

const VARIANT_WIDTH  = 1920
const VARIANT_HEIGHT = 1080

export default class ArticleCoverSection extends Section {

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
     * @param {string}        eyebrow
     * @param {string}        heading
     * @param {string}        body
     * @param {string | null} thumbUrl
     * @param {Api}           api
     */
    constructor (uploadId, eyebrow, heading, body, thumbUrl, api) {
        super()
        this.uploadId  = uploadId
        this.eyebrow   = eyebrow
        this.heading   = heading
        this.body      = body
        this.#thumbUrl = thumbUrl
        this.#api      = api
        this.#element  = this.#build()
    }

    static type () {
        return 'article-cover'
    }

    /**
     * @param {unknown} data
     * @param {Api}     api
     */
    static fromObject (data, api) {
        if (
            !isObject(data)
            || typeof data.uploadId !== 'number'
            || typeof data.eyebrow  !== 'string'
            || typeof data.heading  !== 'string'
            || typeof data.body     !== 'string'
        )
            throw new Error('Invalid ArticleCoverSection data')
        return new ArticleCoverSection(
            data.uploadId,
            data.eyebrow,
            data.heading,
            data.body,
            `/uploads/${data.uploadId}/thumb-200x200.webp`,
            api,
        )
    }

    /** @param {Api} api */
    static createEmpty (api) {
        return new ArticleCoverSection(null, '', '', '', null, api)
    }

    /** @returns {HTMLDivElement} */
    static preview () {
        const root = document.createElement('div')
        root.className = 'sp-preview sp-preview--dark sp-preview--img'
        root.style.cssText += '; justify-content: center; align-items: center; flex-direction: column; gap: 4px'
        const bar = (/** @type {number} */ w, /** @type {string} */ cls = 'sp-bar') => {
            const el = document.createElement('div')
            el.className   = cls
            el.style.width = `${w}%`
            return el
        }
        root.append(bar(30, 'sp-bar sp-bar--eyebrow'), bar(75, 'sp-bar sp-bar--heading'), bar(55, 'sp-bar sp-bar--heading'), bar(60))
        return root
    }

    toObject () {
        return {
            uploadId: this.uploadId,
            eyebrow:  this.eyebrow,
            heading:  this.heading,
            body:     this.body,
        }
    }

    get element () {
        return this.#element
    }

    #build () {
        const root = document.createElement('div')

        this.#addButton.type        = 'button'
        this.#addButton.className   = 'carousel-add image-break-add'
        this.#addButton.textContent = '+ Add background image'
        this.#addButton.addEventListener('click', () => this.#pick())

        this.#pickedPreview.className = 'image-break-picked'
        this.#pickedPreview.addEventListener('click', () => this.#pick())

        const eyebrowLabel = document.createElement('label')
        eyebrowLabel.append('Eyebrow ')
        const eyebrowInput = document.createElement('input')
        eyebrowInput.name  = 'eyebrow'
        eyebrowInput.value = this.eyebrow
        eyebrowInput.addEventListener('input', () => { this.eyebrow = eyebrowInput.value })
        eyebrowLabel.append(eyebrowInput)

        const headingLabel = document.createElement('label')
        headingLabel.append('Heading ')
        const headingInput = document.createElement('input')
        headingInput.name  = 'heading'
        headingInput.value = this.heading
        headingInput.addEventListener('input', () => { this.heading = headingInput.value })
        headingLabel.append(headingInput)

        const bodyLabel = document.createElement('label')
        bodyLabel.append('Subtitle ')
        const bodyArea = document.createElement('textarea')
        bodyArea.name  = 'body'
        bodyArea.rows  = 3
        bodyArea.value = this.body
        bodyArea.addEventListener('input', () => { this.body = bodyArea.value })
        bodyLabel.append(bodyArea)

        this.#syncPickState()

        root.append(this.#addButton, this.#pickedPreview, eyebrowLabel, headingLabel, bodyLabel)
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
        img.alt = this.heading

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
