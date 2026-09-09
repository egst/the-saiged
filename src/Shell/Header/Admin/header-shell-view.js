import UploadPicker from '/js/admin/uploads/upload-picker.js'
import {isObject}   from '/js/core/types.js'

/**
 * @import Api      from '/js/admin/api.js'
 * @import Loader   from '/js/admin/loader.js'
 * @import Notifier from '/js/admin/notifier.js'
 */

/** Matches HeaderShell.php's LOGO_WIDTH/LOGO_HEIGHT constants. */
const LOGO_WIDTH  = 240
const LOGO_HEIGHT = 80

/**
 * Top-level admin view for the site header — reached via /admin/site/header.
 * Not a Section: this isn't mounted inside PageEditor's list, it's its own
 * routed page, so it follows MediaView's shape (self-loading, save button)
 * rather than Section's fromObject/toObject/element contract.
 *
 * Link list reuses PageEditor's own section-list classes (.sections /
 * .section-edit) so it reads as the same admin, not a bespoke one-off.
 * The logo picker follows the same add-button/picked-preview interaction
 * as ImageBreak's single-image field, but with its own box sizing — a
 * logo is small and shouldn't crop, unlike ImageBreak's 16:9 hero photo.
 */
export default class HeaderShellView {

    /** @type {Api} */
    #api
    /** @type {Loader} */
    #loader
    /** @type {Notifier} */
    #notifier

    /** @type {HTMLDivElement} */
    #element
    /** @type {HTMLDivElement} */
    #linksList
    /** @type {HTMLButtonElement} */
    #saveButton
    /** @type {HTMLElement} */
    #statusLabel
    /** @type {HTMLButtonElement} */
    #addLinkButton = document.createElement('button')
    /** @type {HTMLButtonElement} */
    #addLogoButton = document.createElement('button')
    /** @type {HTMLDivElement} */
    #logoPreview = document.createElement('div')

    /** @type {{label: string, href: string}[]} */
    #links = []
    /** @type {number | null} */
    #logoUploadId = null
    /** Serialized snapshot of the last saved state. */
    #savedState = ''

    /**
     * @param {Api}      api
     * @param {Loader}   loader
     * @param {Notifier} notifier
     */
    constructor (api, loader, notifier) {
        this.#api      = api
        this.#loader   = loader
        this.#notifier = notifier

        this.#linksList = document.createElement('div')
        this.#linksList.className = 'sections'

        this.#addLinkButton.type        = 'button'
        this.#addLinkButton.className   = 'add-section'
        this.#addLinkButton.textContent = '+ Add link'
        this.#addLinkButton.addEventListener('click', () => this.#addLink())

        this.#addLogoButton.type        = 'button'
        this.#addLogoButton.className   = 'carousel-add shell-logo-add'
        this.#addLogoButton.textContent = '+ Add logo'
        this.#addLogoButton.addEventListener('click', () => this.#pickLogo())

        this.#logoPreview.className = 'shell-logo-picked'
        this.#logoPreview.addEventListener('click', () => this.#pickLogo())

        this.#saveButton = document.createElement('button')
        this.#saveButton.type        = 'button'
        this.#saveButton.textContent = 'Save'
        this.#saveButton.addEventListener('click', () => this.#save())

        this.#statusLabel = document.createElement('em')
        this.#statusLabel.className   = 'shell-save-status'
        this.#statusLabel.textContent = 'Saved'

        this.#element = this.#build()
        this.#element.addEventListener('input', () => this.#refreshDirty())
        void this.#load()
    }

    /** @returns {HTMLDivElement} */
    get element () {
        return this.#element
    }

    #build () {
        const root = document.createElement('div')
        root.className = 'page-editor'

        const summary = document.createElement('div')
        summary.className = 'page-summary'
        const title = document.createElement('h2')
        title.className   = 'page-summary-title'
        title.textContent = 'Header'
        const meta = document.createElement('p')
        meta.className = 'page-meta'
        meta.append(this.#statusLabel)
        summary.append(title, meta)

        const actions = document.createElement('div')
        actions.className = 'page-actions'
        actions.append(this.#saveButton)

        const logoGroup = document.createElement('div')
        logoGroup.className = 'field-group'
        const logoHeading = document.createElement('h3')
        logoHeading.className   = 'field-group-title'
        logoHeading.textContent = 'Logo'
        logoGroup.append(logoHeading, this.#addLogoButton, this.#logoPreview)

        const linksGroup = document.createElement('div')
        linksGroup.className = 'field-group'
        const linksHeading = document.createElement('h3')
        linksHeading.className   = 'field-group-title'
        linksHeading.textContent = 'Links'
        linksGroup.append(linksHeading, this.#linksList)

        root.append(summary, actions, logoGroup, linksGroup)
        return root
    }

    async #load () {
        const loading = this.#loader.start()
        try {
            const data = await this.#api.getShell('header')
            this.#applyData(data)
            this.#savedState = this.#serialize()
            this.#refreshDirty()
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Failed to load header'
            this.#notifier.error(message, error)
        } finally {
            loading.stop()
        }
    }

    /** @param {Record<string, unknown>} data */
    #applyData (data) {
        const links = Array.isArray(data.links) ? data.links : []
        this.#links = links.map(raw => {
            if (!isObject(raw) || typeof raw.label !== 'string' || typeof raw.href !== 'string')
                throw new Error('Invalid header link shape')
            return {label: raw.label, href: raw.href}
        })
        this.#logoUploadId = typeof data.logoUploadId === 'number' ? data.logoUploadId : null
        this.#renderLinks()
        this.#syncLogoState()
    }

    #renderLinks () {
        this.#linksList.replaceChildren()
        for (let index = 0; index < this.#links.length; index++)
            this.#linksList.append(this.#renderLink(index))
        this.#linksList.append(this.#addLinkButton)
    }

    /** @param {number} index */
    #renderLink (index) {
        const link = this.#links[index]
        const card = document.createElement('div')
        card.className = 'section-edit'

        const row = document.createElement('div')
        row.className = 'section-edit-header'

        const labelInput = document.createElement('input')
        labelInput.name        = 'label'
        labelInput.value       = link.label
        labelInput.placeholder = 'Label'
        labelInput.style.flex  = '1'
        labelInput.addEventListener('input', () => { link.label = labelInput.value })

        const hrefInput = document.createElement('input')
        hrefInput.name        = 'href'
        hrefInput.value       = link.href
        hrefInput.placeholder = '/path or https://…'
        hrefInput.style.flex  = '2'
        hrefInput.addEventListener('input', () => { link.href = hrefInput.value })

        const upButton = document.createElement('button')
        upButton.type        = 'button'
        upButton.className   = 'section-move section-move-up'
        upButton.title       = 'Move up'
        upButton.textContent = '▲'
        upButton.disabled    = index === 0
        upButton.addEventListener('click', () => this.#move(index, -1))

        const downButton = document.createElement('button')
        downButton.type        = 'button'
        downButton.className   = 'section-move section-move-down'
        downButton.title       = 'Move down'
        downButton.textContent = '▼'
        downButton.disabled    = index === this.#links.length - 1
        downButton.addEventListener('click', () => this.#move(index, 1))

        const removeButton = document.createElement('button')
        removeButton.type        = 'button'
        removeButton.className   = 'section-remove'
        removeButton.title       = 'Remove link'
        removeButton.textContent = '×'
        removeButton.addEventListener('click', () => this.#remove(index))

        row.append(labelInput, hrefInput, upButton, downButton, removeButton)
        card.append(row)
        return card
    }

    /** @param {boolean} trackLoad */
    #syncLogoState (trackLoad = false) {
        if (this.#logoUploadId === null) {
            this.#addLogoButton.hidden = false
            this.#logoPreview.hidden   = true
            return
        }

        this.#addLogoButton.hidden = true
        this.#logoPreview.hidden   = false
        this.#logoPreview.replaceChildren()

        const img = document.createElement('img')
        img.alt = 'Logo'
        // Cover-cropped to the same LOGO_WIDTH x LOGO_HEIGHT box the public
        // site renders, so the preview actually reflects what gets shown —
        // a generic square thumbnail here looked cropped-wrong in this wide
        // box. #pickLogo() awaits ensureVariant() before calling this with
        // a freshly-picked upload, so the file is guaranteed to already
        // exist by the time we point the <img> at it (previously this
        // pointed straight at the not-yet-generated variant and raced it,
        // 404ing until generation finished).
        const src = `/uploads/images/${this.#logoUploadId}/${LOGO_WIDTH}x${LOGO_HEIGHT}-cover.webp`

        if (trackLoad) {
            const spinner = document.createElement('div')
            spinner.className = 'img-spinner'
            this.#logoPreview.append(spinner, img)
            this.#element.dispatchEvent(new CustomEvent('imageloading', {bubbles: true}))
            const done = () => {
                spinner.remove()
                this.#element.dispatchEvent(new CustomEvent('imageloaded', {bubbles: true}))
            }
            img.addEventListener('load',  done, {once: true})
            img.addEventListener('error', done, {once: true})
            requestAnimationFrame(() => requestAnimationFrame(() => { img.src = src }))
        } else {
            img.src = src
            this.#logoPreview.append(img)
        }
    }

    async #pickLogo () {
        const upload = await new UploadPicker(this.#api).open()
        if (upload === null)
            return
        this.#logoUploadId = upload.id
        this.#refreshDirty()

        this.#addLogoButton.hidden = true
        this.#logoPreview.hidden   = false
        const spinner = document.createElement('div')
        spinner.className = 'img-spinner'
        this.#logoPreview.replaceChildren(spinner)

        try {
            await this.#api.ensureVariant(upload.id, LOGO_WIDTH, LOGO_HEIGHT)
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Failed to generate logo preview'
            this.#notifier.error(message, error)
        }
        this.#syncLogoState(true)
    }

    #addLink () {
        this.#links.push({label: '', href: ''})
        this.#renderLinks()
        this.#refreshDirty()
    }

    /**
     * @param {number} index
     * @param {number} direction  -1 = up, +1 = down
     */
    #move (index, direction) {
        const target = index + direction
        if (target < 0 || target >= this.#links.length)
            return
        const [moved] = this.#links.splice(index, 1)
        this.#links.splice(target, 0, moved)
        this.#renderLinks()
        this.#refreshDirty()
    }

    /** @param {number} index */
    #remove (index) {
        this.#links.splice(index, 1)
        this.#renderLinks()
        this.#refreshDirty()
    }

    #serialize () {
        return JSON.stringify({links: this.#links, logoUploadId: this.#logoUploadId})
    }

    #refreshDirty () {
        const dirty = this.#serialize() !== this.#savedState
        this.#statusLabel.textContent = dirty ? 'Unsaved changes' : 'Saved'
        this.#statusLabel.classList.toggle('is-dirty', dirty)
        this.#saveButton.classList.toggle('primary', dirty)
    }

    async #save () {
        const loading = this.#loader.start()
        try {
            const payload = JSON.parse(this.#serialize())
            await this.#api.putShell('header', payload)
            this.#savedState = this.#serialize()
            this.#refreshDirty()
            this.#notifier.info('Header saved')
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Failed to save header'
            this.#notifier.error(message, error)
        } finally {
            loading.stop()
        }
    }

}
