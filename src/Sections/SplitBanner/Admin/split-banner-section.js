import Section      from '/js/admin/sections/section.js'
import {isObject}   from '/js/core/types.js'
import UploadPicker from '/js/admin/uploads/upload-picker.js'

/**
 * @import Api    from '/js/admin/api.js'
 * @import Upload from '/js/admin/uploads/upload.js'
 */

const VARIANT_WIDTH  = 960
const VARIANT_HEIGHT = 800

export default class SplitBannerSection extends Section {

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
     * @param {string}        label
     * @param {string}        heading
     * @param {string}        date
     * @param {string}        buttonText
     * @param {string}        buttonHref
     * @param {string | null} thumbUrl
     * @param {Api}           api
     */
    constructor (uploadId, label, heading, date, buttonText, buttonHref, thumbUrl, api) {
        super()
        this.uploadId   = uploadId
        this.label      = label
        this.heading    = heading
        this.date       = date
        this.buttonText = buttonText
        this.buttonHref = buttonHref
        this.#thumbUrl  = thumbUrl
        this.#api       = api
        this.#element   = this.#build()
    }

    static type () {
        return 'split-banner'
    }

    /**
     * @param {unknown} data
     * @param {Api}     api
     */
    static fromObject (data, api) {
        if (
            !isObject(data)
            || (data.uploadId !== null && typeof data.uploadId !== 'number')
            || typeof data.label      !== 'string'
            || typeof data.heading    !== 'string'
            || typeof data.date       !== 'string'
            || typeof data.buttonText !== 'string'
            || typeof data.buttonHref !== 'string'
        )
            throw new Error('Invalid SplitBannerSection data')

        const thumbUrl = typeof data.uploadId === 'number'
            ? `/uploads/${data.uploadId}/thumb-200x200.webp`
            : null

        return new SplitBannerSection(
            data.uploadId,
            data.label,
            data.heading,
            data.date,
            data.buttonText,
            data.buttonHref,
            thumbUrl,
            api,
        )
    }

    /** @param {Api} api */
    static createEmpty (api) {
        return new SplitBannerSection(null, '', '', '', '', '', null, api)
    }

    /** @returns {HTMLDivElement} */
    static preview () {
        const root = document.createElement('div')
        root.className = 'sp-preview sp-preview--split'

        const bar = (/** @type {number} */ w, /** @type {string} */ cls = 'sp-bar') => {
            const el = document.createElement('div')
            el.className   = cls
            el.style.width = `${w}%`
            return el
        }

        const content = document.createElement('div')
        content.className = 'sp-split-content sp-split-content--light'
        content.append(
            bar(30, 'sp-bar sp-bar--eyebrow'),
            bar(72, 'sp-bar sp-bar--heading'),
            bar(40),
            bar(22, 'sp-bar sp-bar--btn'),
        )

        const image = document.createElement('div')
        image.className = 'sp-split-image'

        root.append(content, image)
        return root
    }

    toObject () {
        return {
            uploadId:   this.uploadId,
            label:      this.label,
            heading:    this.heading,
            date:       this.date,
            buttonText: this.buttonText,
            buttonHref: this.buttonHref,
        }
    }

    get element () {
        return this.#element
    }

    #build () {
        const root = document.createElement('div')

        const labelLabel = document.createElement('label')
        labelLabel.append('Label ')
        const labelInput = document.createElement('input')
        labelInput.name  = 'label'
        labelInput.value = this.label
        labelInput.addEventListener('input', () => { this.label = labelInput.value })
        labelLabel.append(labelInput)

        const headingLabel = document.createElement('label')
        headingLabel.append('Heading ')
        const headingInput = document.createElement('input')
        headingInput.name  = 'heading'
        headingInput.value = this.heading
        headingInput.addEventListener('input', () => { this.heading = headingInput.value })
        headingLabel.append(headingInput)

        const dateLabel = document.createElement('label')
        dateLabel.append('Date ')
        const dateInput = document.createElement('input')
        dateInput.name  = 'date'
        dateInput.value = this.date
        dateInput.addEventListener('input', () => { this.date = dateInput.value })
        dateLabel.append(dateInput)

        const buttonTextLabel = document.createElement('label')
        buttonTextLabel.append('Button text ')
        const buttonTextInput = document.createElement('input')
        buttonTextInput.name  = 'buttonText'
        buttonTextInput.value = this.buttonText
        buttonTextInput.addEventListener('input', () => { this.buttonText = buttonTextInput.value })
        buttonTextLabel.append(buttonTextInput)

        const buttonHrefLabel = document.createElement('label')
        buttonHrefLabel.append('Button link ')
        const buttonHrefInput = document.createElement('input')
        buttonHrefInput.name  = 'buttonHref'
        buttonHrefInput.value = this.buttonHref
        buttonHrefInput.addEventListener('input', () => { this.buttonHref = buttonHrefInput.value })
        buttonHrefLabel.append(buttonHrefInput)

        this.#addButton.type        = 'button'
        this.#addButton.className   = 'carousel-add image-break-add'
        this.#addButton.textContent = '+ Add image'
        this.#addButton.addEventListener('click', () => this.#pick())

        this.#pickedPreview.className = 'image-break-picked'
        this.#pickedPreview.addEventListener('click', () => this.#pick())

        this.#syncPickState()

        root.append(labelLabel, headingLabel, dateLabel, buttonTextLabel, buttonHrefLabel, this.#addButton, this.#pickedPreview)
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
