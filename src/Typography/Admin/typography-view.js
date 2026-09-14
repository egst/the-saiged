import UploadPicker from '/js/admin/uploads/upload-picker.js'
import {isObject}   from '/js/core/types.js'

/**
 * @import Api      from '/js/admin/api.js'
 * @import Loader   from '/js/admin/loader.js'
 * @import Notifier from '/js/admin/notifier.js'
 */

/**
 * @typedef {{
 *     id: number, weightMin: number, weightMax: number, style: string,
 *     upload: Record<string, unknown> | null,
 * }} Face
 */

const ROLES = [
    {value: 'heading', label: 'Heading'},
    {value: 'text',    label: 'Text'},
]

/**
 * Top-level admin view for custom font faces — reached via
 * /admin/site/typography. Each role (Heading/Text, mirroring the
 * --serif/--sans CSS variables) holds any number of uploaded font faces,
 * each tagged with the weight range + style it covers (see
 * TypographyFace.php) — a role with none falls back to the site's
 * default font, handled entirely by Layout::typographyStyleTag(), not
 * anything this view needs to represent.
 *
 * Acts immediately on add/remove (follows MediaView's shape) rather than
 * Shell's edit-then-Save flow — there's no draft state worth tracking
 * for a list of add/remove operations.
 */
export default class TypographyView {

    /** @type {Api} */
    #api
    /** @type {Loader} */
    #loader
    /** @type {Notifier} */
    #notifier

    /** @type {HTMLDivElement} */
    #element
    /** @type {Record<string, HTMLDivElement>} */
    #lists = {}
    /** @type {Record<string, HTMLButtonElement>} */
    #addButtons = {}

    /**
     * @param {Api}      api
     * @param {Loader}   loader
     * @param {Notifier} notifier
     */
    constructor (api, loader, notifier) {
        this.#api      = api
        this.#loader   = loader
        this.#notifier = notifier

        this.#element = this.#build()
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
        title.textContent = 'Typography'
        const meta = document.createElement('p')
        meta.className   = 'page-meta'
        meta.textContent = 'A role with no faces uses the site’s default font.'
        summary.append(title, meta)

        root.append(summary)
        for (const role of ROLES)
            root.append(this.#buildRoleGroup(role.value, role.label))

        return root
    }

    /**
     * @param {string} role
     * @param {string} label
     */
    #buildRoleGroup (role, label) {
        const list = document.createElement('div')
        list.className = 'sections'
        this.#lists[role] = list

        // Lives as the last child of `list` itself (see #renderFaces),
        // not a sibling after it — same shape as Header/Footer's own
        // link/column lists, so cards, the empty-state message, and the
        // add button all share one gap-large rhythm instead of two
        // nested ones.
        const addButton = document.createElement('button')
        addButton.type        = 'button'
        addButton.className   = 'add-section'
        addButton.textContent = '+ Add face'
        addButton.addEventListener('click', () => this.#addFace(role))
        this.#addButtons[role] = addButton

        const group = document.createElement('div')
        group.className = 'field-group'
        const heading = document.createElement('h3')
        heading.className   = 'field-group-title'
        heading.textContent = label
        group.append(heading, list)
        return group
    }

    async #load () {
        const loading = this.#loader.start()
        try {
            const data = await this.#api.getTypography()
            for (const role of ROLES)
                this.#renderFaces(role.value, /** @type {Face[]} */ (data[role.value]))
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Failed to load typography'
            this.#notifier.error(message, error)
        } finally {
            loading.stop()
        }
    }

    /**
     * @param {string} role
     * @param {Face[]} faces
     */
    #renderFaces (role, faces) {
        const list = this.#lists[role]
        list.replaceChildren()
        if (faces.length === 0) {
            const empty = document.createElement('p')
            empty.className   = 'placeholder'
            empty.textContent = 'No custom faces — using the default font.'
            list.append(empty)
        } else {
            for (const face of faces)
                list.append(this.#renderFace(face))
        }
        list.append(this.#addButtons[role])
    }

    /** @param {Face} face */
    #renderFace (face) {
        const card = document.createElement('div')
        card.className = 'section-edit'

        const row = document.createElement('div')
        row.className = 'control-row'

        const filename = isObject(face.upload) && typeof face.upload.filename === 'string'
            ? face.upload.filename
            : 'Unknown file'
        const weight = face.weightMin === face.weightMax
            ? `${face.weightMin}`
            : `${face.weightMin}–${face.weightMax}`

        const label = document.createElement('span')
        label.className    = 'control-row-label'
        label.textContent  = `${filename} — weight ${weight}, ${face.style}`

        const removeButton = document.createElement('button')
        removeButton.type        = 'button'
        removeButton.className   = 'section-remove'
        removeButton.title       = 'Remove face'
        removeButton.textContent = '×'
        removeButton.addEventListener('click', () => this.#removeFace(face.id))

        row.append(label, removeButton)
        card.append(row)
        return card
    }

    /** @param {string} role */
    async #addFace (role) {
        const upload = await new UploadPicker(this.#api).open('font')
        if (upload === null)
            return

        const list = this.#lists[role]
        list.insertBefore(this.#renderPendingFace(role, upload), this.#addButtons[role])
    }

    /**
     * @param {string} role
     * @param {{id: number, filename: string}} upload
     */
    #renderPendingFace (role, upload) {
        const card = document.createElement('div')
        card.className = 'section-edit'

        const row = document.createElement('div')
        row.className = 'control-row'

        const label = document.createElement('span')
        label.className   = 'control-row-label'
        label.textContent = upload.filename

        const minInput = document.createElement('input')
        minInput.type        = 'number'
        minInput.className   = 'weight-input'
        minInput.min         = '1'
        minInput.max         = '1000'
        minInput.value       = '400'
        minInput.title       = 'Weight from'

        const maxInput = document.createElement('input')
        maxInput.type        = 'number'
        maxInput.className   = 'weight-input'
        maxInput.min         = '1'
        maxInput.max         = '1000'
        maxInput.value       = '400'
        maxInput.title       = 'Weight to'

        const styleSelect = document.createElement('select')
        for (const value of ['normal', 'italic']) {
            const option = document.createElement('option')
            option.value       = value
            option.textContent = value
            styleSelect.append(option)
        }

        const confirmButton = document.createElement('button')
        confirmButton.type        = 'button'
        confirmButton.className   = 'primary'
        confirmButton.textContent = 'Add'
        confirmButton.addEventListener('click', () => this.#confirmFace(role, card, {
            uploadId:  upload.id,
            weightMin: minInput.valueAsNumber,
            weightMax: maxInput.valueAsNumber,
            style:     styleSelect.value,
        }))

        const cancelButton = document.createElement('button')
        cancelButton.type        = 'button'
        cancelButton.textContent = 'Cancel'
        cancelButton.addEventListener('click', () => card.remove())

        const actions = document.createElement('div')
        actions.className = 'page-actions'
        actions.append(confirmButton, cancelButton)

        row.append(label, minInput, maxInput, styleSelect, actions)
        card.append(row)
        return card
    }

    /**
     * @param {string}      role
     * @param {HTMLElement} pendingCard
     * @param {{uploadId: number, weightMin: number, weightMax: number, style: string}} payload
     */
    async #confirmFace (role, pendingCard, payload) {
        if (!Number.isInteger(payload.weightMin) || !Number.isInteger(payload.weightMax)
            || payload.weightMin < 1 || payload.weightMax > 1000 || payload.weightMin > payload.weightMax) {
            this.#notifier.error('Weight must be a range between 1 and 1000 (from ≤ to).')
            return
        }

        const loading = this.#loader.start()
        try {
            await this.#api.addTypographyFace(role, payload)
            pendingCard.remove()
            await this.#load()
            this.#notifier.success('Font face added')
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Failed to add font face'
            this.#notifier.error(message, error)
        } finally {
            loading.stop()
        }
    }

    /** @param {number} id */
    async #removeFace (id) {
        if (!window.confirm('Remove this font face?'))
            return

        const loading = this.#loader.start()
        try {
            await this.#api.removeTypographyFace(id)
            await this.#load()
            this.#notifier.success('Font face removed')
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Failed to remove font face'
            this.#notifier.error(message, error)
        } finally {
            loading.stop()
        }
    }

}
