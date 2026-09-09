import UploadPicker from '/js/admin/uploads/upload-picker.js'
import {isObject}   from '/js/core/types.js'

/**
 * @import Api      from '/js/admin/api.js'
 * @import Loader   from '/js/admin/loader.js'
 * @import Notifier from '/js/admin/notifier.js'
 */

/** Matches FooterShell.php's LOGO_WIDTH/LOGO_HEIGHT constants. */
const LOGO_WIDTH  = 480
const LOGO_HEIGHT = 160

/** @typedef {{kind: 'link', label: string, href: string} | {kind: 'text', content: string} | {kind: 'newsletter'}} FooterItemData */
/** @typedef {{heading: string, items: FooterItemData[]}} FooterColumnData */

/**
 * Top-level admin view for the site footer — reached via /admin/site/footer.
 * Same shape as HeaderShellView: self-loading, own save button, not a
 * Section (columns/items here aren't a per-page thing).
 */
export default class FooterShellView {

    /** @type {Api} */
    #api
    /** @type {Loader} */
    #loader
    /** @type {Notifier} */
    #notifier

    /** @type {HTMLDivElement} */
    #element
    /** @type {HTMLDivElement} */
    #columnsList
    /** @type {HTMLButtonElement} */
    #saveButton
    /** @type {HTMLElement} */
    #statusLabel
    /** @type {HTMLButtonElement} */
    #addColumnButton = document.createElement('button')
    /** @type {HTMLButtonElement} */
    #addLogoButton = document.createElement('button')
    /** @type {HTMLDivElement} */
    #logoPreview = document.createElement('div')

    /** @type {FooterColumnData[]} */
    #columns = []
    /** @type {number | null} */
    #logoUploadId = null
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

        this.#columnsList = document.createElement('div')
        this.#columnsList.className = 'carousel-items'

        this.#addColumnButton.type        = 'button'
        this.#addColumnButton.className   = 'carousel-add footer-column-add'
        this.#addColumnButton.textContent = '+ Add column'
        this.#addColumnButton.addEventListener('click', () => this.#addColumn())

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
        title.textContent = 'Footer'
        const meta = document.createElement('p')
        meta.className = 'page-meta'
        meta.append(this.#statusLabel)
        summary.append(title, meta)

        const actions = document.createElement('div')
        actions.className = 'page-actions'
        actions.append(this.#saveButton)

        const columnsGroup = document.createElement('div')
        columnsGroup.className = 'field-group'
        const columnsHeading = document.createElement('h3')
        columnsHeading.className   = 'field-group-title'
        columnsHeading.textContent = 'Columns'
        columnsGroup.append(columnsHeading, this.#columnsList)

        const logoGroup = document.createElement('div')
        logoGroup.className = 'field-group'
        const logoHeading = document.createElement('h3')
        logoHeading.className   = 'field-group-title'
        logoHeading.textContent = 'Logo'
        logoGroup.append(logoHeading, this.#addLogoButton, this.#logoPreview)

        root.append(summary, actions, logoGroup, columnsGroup)
        return root
    }

    async #load () {
        const loading = this.#loader.start()
        try {
            const data = await this.#api.getShell('footer')
            this.#applyData(data)
            this.#savedState = this.#serialize()
            this.#refreshDirty()
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Failed to load footer'
            this.#notifier.error(message, error)
        } finally {
            loading.stop()
        }
    }

    /** @param {Record<string, unknown>} data */
    #applyData (data) {
        const columns = Array.isArray(data.columns) ? data.columns : []
        this.#columns = columns.map(raw => {
            if (!isObject(raw) || typeof raw.heading !== 'string' || !Array.isArray(raw.items))
                throw new Error('Invalid footer column shape')
            return {heading: raw.heading, items: raw.items.map(FooterShellView.#parseItem)}
        })
        this.#logoUploadId = typeof data.logoUploadId === 'number' ? data.logoUploadId : null
        this.#renderColumns()
        this.#syncLogoState()
    }

    /**
     * @param {unknown} raw
     * @returns {FooterItemData}
     */
    static #parseItem (raw) {
        if (!isObject(raw))
            throw new Error('Invalid footer item shape')
        if (raw.kind === 'link' && typeof raw.label === 'string' && typeof raw.href === 'string')
            return {kind: 'link', label: raw.label, href: raw.href}
        if (raw.kind === 'text' && typeof raw.content === 'string')
            return {kind: 'text', content: raw.content}
        if (raw.kind === 'newsletter')
            return {kind: 'newsletter'}
        throw new Error('Invalid footer item shape')
    }

    /**
     * @param {string}  label
     * @param {string}  value
     * @param {boolean} [selected]
     */
    static #option (label, value, selected = false) {
        const option = document.createElement('option')
        option.textContent = label
        option.value       = value
        option.selected    = selected
        return option
    }

    #renderColumns () {
        this.#columnsList.replaceChildren()
        for (let index = 0; index < this.#columns.length; index++)
            this.#columnsList.append(this.#renderColumn(index))
        this.#columnsList.append(this.#addColumnButton)
    }

    /** @param {number} index */
    #renderColumn (index) {
        const column = this.#columns[index]
        const card = document.createElement('div')
        card.className = 'section-edit footer-column-edit'

        const header = document.createElement('div')
        header.className = 'section-edit-header'

        const headingInput = document.createElement('input')
        headingInput.name        = 'heading'
        headingInput.value       = column.heading
        headingInput.placeholder = 'Column heading'
        headingInput.addEventListener('input', () => { column.heading = headingInput.value })

        const upButton = document.createElement('button')
        upButton.type        = 'button'
        upButton.className   = 'section-move section-move-up'
        upButton.title       = 'Move column left'
        upButton.textContent = '◀'
        upButton.disabled    = index === 0
        upButton.addEventListener('click', () => this.#moveColumn(index, -1))

        const downButton = document.createElement('button')
        downButton.type        = 'button'
        downButton.className   = 'section-move section-move-down'
        downButton.title       = 'Move column right'
        downButton.textContent = '▶'
        downButton.disabled    = index === this.#columns.length - 1
        downButton.addEventListener('click', () => this.#moveColumn(index, 1))

        const removeButton = document.createElement('button')
        removeButton.type        = 'button'
        removeButton.className   = 'section-remove'
        removeButton.title       = 'Remove column'
        removeButton.textContent = '×'
        removeButton.addEventListener('click', () => this.#removeColumn(index))

        header.append(headingInput, upButton, downButton, removeButton)

        const itemsList = document.createElement('div')
        itemsList.className = 'carousel-items carousel-items--vertical'
        for (let itemIndex = 0; itemIndex < column.items.length; itemIndex++)
            itemsList.append(this.#renderItem(index, itemIndex))

        const addItemSelect = document.createElement('select')
        addItemSelect.className = 'carousel-add footer-add-item'
        addItemSelect.append(
            FooterShellView.#option('+ Add item…', '', true),
            FooterShellView.#option('Link', 'link'),
            FooterShellView.#option('Text', 'text'),
            FooterShellView.#option('Newsletter form', 'newsletter'),
        )
        addItemSelect.addEventListener('change', () => {
            const value = addItemSelect.value
            addItemSelect.value = ''
            if (value === 'link' || value === 'text' || value === 'newsletter')
                this.#addItem(index, value)
        })
        itemsList.append(addItemSelect)

        card.append(header, itemsList)
        return card
    }

    /**
     * @param {number} columnIndex
     * @param {number} itemIndex
     */
    #renderItem (columnIndex, itemIndex) {
        const item     = this.#columns[columnIndex].items[itemIndex]
        const itemCount = this.#columns[columnIndex].items.length
        const card     = document.createElement('div')
        card.className = 'carousel-item'

        if (item.kind === 'link') {
            const labelField = document.createElement('label')
            labelField.append('Label ')
            const labelInput = document.createElement('input')
            labelInput.value = item.label
            labelInput.addEventListener('input', () => { item.label = labelInput.value })
            labelField.append(labelInput)

            const hrefField = document.createElement('label')
            hrefField.append('Link ')
            const hrefInput = document.createElement('input')
            hrefInput.value       = item.href
            hrefInput.placeholder = '/path or https://…'
            hrefInput.addEventListener('input', () => { item.href = hrefInput.value })
            hrefField.append(hrefInput)

            card.append(labelField, hrefField)
        } else if (item.kind === 'text') {
            const textField = document.createElement('label')
            textField.append('Text ')
            const textArea = document.createElement('textarea')
            textArea.rows        = 2
            textArea.value       = item.content
            textArea.placeholder = 'Use [label](url) for a link'
            textArea.addEventListener('input', () => { item.content = textArea.value })
            textField.append(textArea)

            card.append(textField)
        }
        // Newsletter items have no fields — the controls row below is the whole card.

        const controls = document.createElement('div')
        controls.className = 'carousel-item-controls'

        const upButton = document.createElement('button')
        upButton.type        = 'button'
        upButton.className   = 'carousel-item-move'
        upButton.title       = 'Move up'
        upButton.textContent = '▲'
        upButton.disabled    = itemIndex === 0
        upButton.addEventListener('click', () => this.#moveItem(columnIndex, itemIndex, -1))

        const downButton = document.createElement('button')
        downButton.type        = 'button'
        downButton.className   = 'carousel-item-move'
        downButton.title       = 'Move down'
        downButton.textContent = '▼'
        downButton.disabled    = itemIndex === itemCount - 1
        downButton.addEventListener('click', () => this.#moveItem(columnIndex, itemIndex, 1))

        const kindLabel = document.createElement('span')
        kindLabel.className   = 'footer-item-kind'
        kindLabel.textContent = item.kind

        const removeButton = document.createElement('button')
        removeButton.type        = 'button'
        removeButton.className   = 'carousel-item-remove'
        removeButton.title       = 'Remove item'
        removeButton.textContent = '×'
        removeButton.addEventListener('click', () => this.#removeItem(columnIndex, itemIndex))

        controls.append(upButton, downButton, kindLabel, removeButton)
        card.append(controls)
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

    #addColumn () {
        this.#columns.push({heading: '', items: []})
        this.#renderColumns()
        this.#refreshDirty()
    }

    /**
     * @param {number} index
     * @param {number} direction  -1 = left, +1 = right
     */
    #moveColumn (index, direction) {
        const target = index + direction
        if (target < 0 || target >= this.#columns.length)
            return
        const [moved] = this.#columns.splice(index, 1)
        this.#columns.splice(target, 0, moved)
        this.#renderColumns()
        this.#refreshDirty()
    }

    /** @param {number} index */
    #removeColumn (index) {
        this.#columns.splice(index, 1)
        this.#renderColumns()
        this.#refreshDirty()
    }

    /**
     * @param {number} columnIndex
     * @param {'link' | 'text' | 'newsletter'} kind
     */
    #addItem (columnIndex, kind) {
        /** @type {FooterItemData} */
        const item = kind === 'link' ? {kind: 'link', label: '', href: ''}
            : kind === 'text' ? {kind: 'text', content: ''}
            : {kind: 'newsletter'}
        this.#columns[columnIndex].items.push(item)
        this.#renderColumns()
        this.#refreshDirty()
    }

    /**
     * @param {number} columnIndex
     * @param {number} itemIndex
     * @param {number} direction  -1 = up, +1 = down
     */
    #moveItem (columnIndex, itemIndex, direction) {
        const items  = this.#columns[columnIndex].items
        const target = itemIndex + direction
        if (target < 0 || target >= items.length)
            return
        const [moved] = items.splice(itemIndex, 1)
        items.splice(target, 0, moved)
        this.#renderColumns()
        this.#refreshDirty()
    }

    /**
     * @param {number} columnIndex
     * @param {number} itemIndex
     */
    #removeItem (columnIndex, itemIndex) {
        this.#columns[columnIndex].items.splice(itemIndex, 1)
        this.#renderColumns()
        this.#refreshDirty()
    }

    #serialize () {
        return JSON.stringify({columns: this.#columns, logoUploadId: this.#logoUploadId})
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
            await this.#api.putShell('footer', payload)
            this.#savedState = this.#serialize()
            this.#refreshDirty()
            this.#notifier.info('Footer saved')
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Failed to save footer'
            this.#notifier.error(message, error)
        } finally {
            loading.stop()
        }
    }

}
