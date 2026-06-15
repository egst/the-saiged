import SectionPicker from '/js/admin/content/section-picker.js'

/**
 * @import Api            from '/js/admin/api.js'
 * @import Page           from '/js/admin/pages/page.js'
 * @import Router         from '/js/core/router.js'
 * @import Loader         from '/js/admin/loader.js'
 * @import Notifier       from '/js/admin/notifier.js'
 * @import Section        from '/js/admin/sections/section.js'
 * @import SectionFactory from '/js/admin/sections/section-factory.js'
 */

/**
 * View for editing a single page. The page entity is already fully
 * constructed (sections resolved) when handed to this view.
 *
 * Save flow assembles the wire payload from each section's static type +
 * toObject(), matching the PHP AdminController's input shape.
 *
 * Sections can be added (via SectionFactory.createEmpty), removed (× button)
 * and reordered via native HTML5 drag-and-drop.
 */
export default class PageEditor {

    /**
     * @param {Page}           page
     * @param {Api}            api
     * @param {Router}         router
     * @param {SectionFactory} sectionFactory
     * @param {Loader}         loader
     * @param {Notifier}       notifier
     */
    constructor (page, api, router, sectionFactory, loader, notifier) {
        this.#page           = page
        this.#api            = api
        this.#router         = router
        this.#sectionFactory = sectionFactory
        this.#loader         = loader
        this.#notifier       = notifier

        this.#saveButton = document.createElement('button')
        this.#saveButton.type        = 'button'
        this.#saveButton.textContent = 'Save'
        this.#saveButton.addEventListener('click', () => this.#save())

        this.#copyButton = document.createElement('button')
        this.#copyButton.type        = 'button'
        this.#copyButton.textContent = 'Copy'
        this.#copyButton.addEventListener('click', () => this.#copy())

        this.#publishButton = document.createElement('button')
        this.#publishButton.type = 'button'
        this.#publishButton.addEventListener('click', () => this.#togglePublish())
        this.#refreshPublishButton()

        this.#deleteButton = document.createElement('button')
        this.#deleteButton.type        = 'button'
        this.#deleteButton.className   = 'danger'
        this.#deleteButton.textContent = 'Delete'
        this.#deleteButton.addEventListener('click', () => this.#delete())

        this.#openLink = document.createElement('a')
        this.#openLink.className   = 'page-open'
        this.#openLink.target      = '_blank'
        this.#openLink.rel         = 'noopener'
        this.#openLink.textContent = 'Open'
        this.#openLink.href        = this.#publicPath()

        this.#summaryTitle = document.createElement('h2')
        this.#summaryTitle.className   = 'page-summary-title'
        this.#summaryTitle.textContent = this.#page.title

        this.#statusLabel = document.createElement('em')
        this.#statusLabel.className   = 'page-status-label'
        this.#statusLabel.textContent = this.#page.status

        this.#dirtyLabel = document.createElement('em')
        this.#dirtyLabel.className   = 'page-status-dirty'
        this.#dirtyLabel.textContent = 'unsaved'
        this.#dirtyLabel.hidden      = true

        this.#addSectionButton = this.#buildAddButton()
        this.#loadSectionTypes()

        this.#sectionsContainer = document.createElement('div')
        this.#sectionsContainer.className = 'sections'
        this.#sectionsContainer.addEventListener('dragover', event => this.#onDragOver(event))
        this.#sectionsContainer.addEventListener('drop',     event => this.#onDrop(event))

        this.#element = this.#build()
        for (const section of this.#page.sections)
            this.#mountSection(section)

        // Any input/change inside the editor (metadata fields or section
        // inputs) bubbles here and refreshes the dirty indicator. Mutations
        // that don't go through inputs (add/remove/reorder section,
        // publish toggle) call #refreshDirty() explicitly.
        this.#element.addEventListener('input',  () => this.#refreshDirty())
        this.#element.addEventListener('change', () => this.#refreshDirty())

        this.#savedState = this.#serialize()
    }

    /** @type {Page} */
    #page
    /** @type {Api} */
    #api
    /** @type {Router} */
    #router
    /** @type {SectionFactory} */
    #sectionFactory
    /** @type {Loader} */
    #loader
    /** @type {Notifier} */
    #notifier
    /** @type {HTMLDivElement} */
    #element
    /** @type {HTMLDivElement} */
    #sectionsContainer
    /** @type {HTMLButtonElement} */
    #saveButton
    /** @type {HTMLButtonElement} */
    #deleteButton
    /** @type {HTMLButtonElement} */
    #copyButton
    /** @type {HTMLAnchorElement} */
    #openLink
    /** @type {HTMLButtonElement} */
    #publishButton
    /** @type {HTMLHeadingElement} */
    #summaryTitle
    /** @type {HTMLElement} */
    #statusLabel
    /** @type {HTMLElement} */
    #dirtyLabel
    /** Serialized snapshot of the last saved state; compared against current. */
    #savedState = ''
    /** @type {HTMLButtonElement} */
    #addSectionButton
    /** @type {Array<{type: string, label: string}> | null} */
    #sectionTypes = null
    /** @type {Map<Section, HTMLDivElement>} */
    #wrappers = new Map()
    /** @type {HTMLDivElement | null} */
    #draggedWrapper = null
    /** Y offset from the wrapper's top to the cursor at drag start, in px. */
    #grabOffsetY = 0

    get element () {
        return this.#element
    }

    #build () {
        const root = document.createElement('div')
        root.className = 'page-editor'

        const summary = document.createElement('div')
        summary.className = 'page-summary'

        const summaryMeta = document.createElement('p')
        summaryMeta.className = 'page-meta'
        const pathCode = document.createElement('code')
        pathCode.textContent = this.#publicPath()
        summaryMeta.append(pathCode, ' · ', this.#statusLabel, this.#dirtyLabel)

        summary.append(this.#summaryTitle, summaryMeta)

        const actions = document.createElement('div')
        actions.className = 'page-actions'
        actions.append(
            this.#saveButton,
            this.#publishButton,
            this.#copyButton,
            this.#deleteButton,
            this.#openLink,
        )

        root.append(
            summary,
            actions,
            this.#buildMetadataPanel(),
            this.#sectionsContainer,
            this.#addSectionButton,
        )
        return root
    }

    /**
     * Collapsible panel above the sections with editable page metadata:
     * title, meta description. Path is shown as read-only (slug change
     * deliberately disallowed — use Copy instead). Publish/draft lives
     * in the action toolbar, not here.
     */
    #buildMetadataPanel () {
        const panel = document.createElement('details')
        panel.className = 'page-meta-panel'

        const panelSummary = document.createElement('summary')
        panelSummary.textContent = 'Page metadata'
        panel.append(panelSummary)

        const body = document.createElement('div')
        body.className = 'page-meta-body'

        const titleLabel = document.createElement('label')
        titleLabel.append('Title')
        const titleInput = document.createElement('input')
        titleInput.name  = 'title'
        titleInput.value = this.#page.title
        titleInput.addEventListener('input', () => {
            this.#page.title               = titleInput.value
            this.#summaryTitle.textContent = titleInput.value
        })
        titleLabel.append(titleInput)

        const descLabel = document.createElement('label')
        descLabel.append('Meta description')
        const descInput = document.createElement('textarea')
        descInput.name        = 'metaDesc'
        descInput.rows        = 3
        descInput.value       = this.#page.metaDesc ?? ''
        descInput.placeholder = 'Used in <meta name="description"> and search/social snippets'
        descInput.addEventListener('input', () => {
            const trimmed = descInput.value.trim()
            this.#page.metaDesc = trimmed === '' ? null : descInput.value
        })
        descLabel.append(descInput)

        body.append(titleLabel, descLabel)
        panel.append(body)
        return panel
    }

    /** Build the public-URL form of the page's path (always leading slash). */
    #publicPath () {
        return `/${this.#page.path.replace(/^\/+/, '')}`
    }

    #buildAddButton () {
        const button = document.createElement('button')
        button.type      = 'button'
        button.className = 'add-section'
        button.textContent = '+ Add section'
        button.addEventListener('click', () => this.#openSectionPicker())
        return button
    }

    async #loadSectionTypes () {
        try {
            this.#sectionTypes = await this.#api.listSections()
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Failed to load section types'
            this.#notifier.error(message, error)
        }
    }

    async #openSectionPicker () {
        if (this.#sectionTypes === null)
            await this.#loadSectionTypes()
        if (this.#sectionTypes === null)
            return
        const type = await new SectionPicker(this.#sectionTypes).open()
        if (type !== null)
            this.#addSection(type)
    }

    /** @param {Section} section */
    #mountSection (section) {
        const wrapper = this.#createSectionWrapper(section)
        this.#wrappers.set(section, wrapper)
        this.#sectionsContainer.append(wrapper)
    }

    /** @param {Section} section */
    #createSectionWrapper (section) {
        const wrapper = document.createElement('div')
        wrapper.className = 'section-edit'

        const wrapperHeader = document.createElement('div')
        wrapperHeader.className = 'section-edit-header'

        const handle = document.createElement('span')
        handle.className   = 'section-handle'
        handle.textContent = '⋮⋮'
        handle.title       = 'Drag to reorder'

        // Handle-only draggability: wrapper becomes draggable only while the
        // handle is mouse-pressed, so inputs/textareas inside keep their
        // native text behavior.
        handle.addEventListener('mousedown', () => { wrapper.draggable = true })

        const typeLabel = document.createElement('span')
        typeLabel.className = 'section-type'
        typeLabel.textContent = /** @type {typeof import('/js/admin/sections/section.js').default} */ (section.constructor).type()

        const upButton = document.createElement('button')
        upButton.type        = 'button'
        upButton.className   = 'section-move section-move-up'
        upButton.title       = 'Move up'
        upButton.textContent = '▲'
        upButton.addEventListener('click', () => {
            const prev = wrapper.previousElementSibling
            if (prev !== null) {
                this.#sectionsContainer.insertBefore(wrapper, prev)
                this.#syncOrderFromDOM()
            }
        })

        const downButton = document.createElement('button')
        downButton.type        = 'button'
        downButton.className   = 'section-move section-move-down'
        downButton.title       = 'Move down'
        downButton.textContent = '▼'
        downButton.addEventListener('click', () => {
            const next = wrapper.nextElementSibling
            if (next !== null) {
                next.after(wrapper)
                this.#syncOrderFromDOM()
            }
        })

        const removeButton = document.createElement('button')
        removeButton.type        = 'button'
        removeButton.className   = 'section-remove'
        removeButton.title       = 'Remove section'
        removeButton.textContent = '×'
        removeButton.addEventListener('click', () => this.#removeSection(section))

        wrapperHeader.append(handle, upButton, downButton, typeLabel, removeButton)

        wrapper.addEventListener('dragstart', event => {
            if (event.dataTransfer === null) return
            event.dataTransfer.effectAllowed = 'move'
            event.dataTransfer.setData('text/plain', '')
            // Remember where in the wrapper the user grabbed; used later
            // to project the wrapper's visual center from cursor Y.
            this.#grabOffsetY = event.clientY - wrapper.getBoundingClientRect().top
            wrapper.classList.add('dragging')
            this.#draggedWrapper = wrapper
        })
        wrapper.addEventListener('dragend', () => {
            wrapper.classList.remove('dragging')
            wrapper.draggable = false
            this.#draggedWrapper = null
            this.#syncOrderFromDOM()
        })

        const body = document.createElement('div')
        body.className = 'section-body'
        body.append(section.element)

        wrapper.append(wrapperHeader, body)
        return wrapper
    }

    /**
     * Live-shift drag UX, top-edge detection.
     *
     * The dragged box's projected top edge triggers the swap. When the
     * dragged top crosses above a peer's top edge, the dragged item is
     * inserted before that peer. This works well for tall sections where
     * center-based detection requires dragging past the peer's midpoint
     * — often impossible when the dragged section is taller than the peer.
     *
     * @param {DragEvent} event
     */
    #onDragOver (event) {
        const dragged = this.#draggedWrapper
        if (dragged === null)
            return
        event.preventDefault()
        if (event.dataTransfer !== null)
            event.dataTransfer.dropEffect = 'move'

        const peers = /** @type {HTMLElement[]} */ (
            [...this.#sectionsContainer.children].filter(node => node !== dragged)
        )
        if (peers.length === 0)
            return

        const projectedTop = event.clientY - this.#grabOffsetY

        // First peer whose top edge is BELOW the dragged's projected top
        // → dragged goes before that peer. If none, dragged is at the end.
        const beforePeer = peers.find(peer => {
            const rect = peer.getBoundingClientRect()
            return projectedTop < rect.top
        }) ?? null

        if (beforePeer === null) {
            if (dragged.nextElementSibling !== null)
                this.#sectionsContainer.append(dragged)
            return
        }

        if (beforePeer.previousElementSibling === dragged)
            return

        this.#sectionsContainer.insertBefore(dragged, beforePeer)
    }

    /** @param {DragEvent} event */
    #onDrop (event) {
        if (this.#draggedWrapper === null)
            return
        event.preventDefault()
        // Order already correct from live-shift during dragover.
    }

    /** @param {Section} section */
    #removeSection (section) {
        const index = this.#page.sections.indexOf(section)
        if (index === -1) return
        this.#page.sections.splice(index, 1)
        const wrapper = this.#wrappers.get(section)
        if (wrapper !== undefined) {
            wrapper.remove()
            this.#wrappers.delete(section)
        }
        this.#refreshDirty()
    }

    /** @param {string} type */
    async #addSection (type) {
        const loading = this.#loader.start()
        try {
            const section = await this.#sectionFactory.createEmpty(type)
            this.#page.sections.push(section)
            this.#mountSection(section)
            this.#refreshDirty()
        } catch (error) {
            const message = error instanceof Error ? error.message : `Failed to add section "${type}"`
            this.#notifier.error(message, error)
        } finally {
            loading.stop()
        }
    }

    #syncOrderFromDOM () {
        const newOrder = []
        for (const wrapper of this.#sectionsContainer.children) {
            for (const [section, sectionWrapper] of this.#wrappers) {
                if (sectionWrapper === wrapper) {
                    newOrder.push(section)
                    break
                }
            }
        }
        this.#page.sections = newOrder
        this.#refreshDirty()
    }

    /**
     * Confirm publish/unpublish before committing the change. State change
     * is local to the form — user must Save to persist.
     */
    #togglePublish () {
        const willPublish = this.#page.status !== 'published'
        const message     = willPublish
            ? 'Publish this page? It will become publicly accessible.'
            : 'Unpublish this page? Existing links will stop working.'
        if (!window.confirm(message))
            return

        this.#page.status             = willPublish ? 'published' : 'draft'
        this.#statusLabel.textContent = this.#page.status
        this.#refreshPublishButton()
        this.#refreshDirty()
    }

    #refreshPublishButton () {
        const isPublished = this.#page.status === 'published'
        this.#publishButton.textContent = isPublished ? 'Unpublish' : 'Publish'
    }

    /**
     * Snapshot of the persistable state — compared against #savedState to
     * decide whether there are unsaved changes. Shape must match what
     * #save() sends to the API; otherwise dirty detection misses fields.
     */
    #serialize () {
        return JSON.stringify({
            title:    this.#page.title,
            metaDesc: this.#page.metaDesc,
            status:   this.#page.status,
            sections: this.#page.sections.map(section => ({
                type: /** @type {typeof import('/js/admin/sections/section.js').default} */ (section.constructor).type(),
                data: section.toObject(),
            })),
        })
    }

    #refreshDirty () {
        const dirty = this.#serialize() !== this.#savedState
        this.#dirtyLabel.hidden = !dirty
        this.#saveButton.classList.toggle('primary', dirty)
    }

    async #save () {
        const loading = this.#loader.start()
        this.#saveButton.disabled = true
        try {
            const payload = JSON.parse(this.#serialize())
            await this.#api.putPage(this.#page.id, payload)
            this.#savedState = this.#serialize()
            this.#refreshDirty()
            this.#notifier.info(`PageEditor saved page #${this.#page.id}`)
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Failed to save page'
            this.#notifier.error(message, error)
        } finally {
            this.#saveButton.disabled = false
            loading.stop()
        }
    }

    /** Navigate to the copy form view — actual creation happens there. */
    #copy () {
        this.#router.go(`/admin/pages/${this.#page.id}/copy`)
    }

    async #delete () {
        if (!window.confirm(`Delete page "${this.#page.title}"? This cannot be undone.`))
            return

        const loading = this.#loader.start()
        this.#deleteButton.disabled = true
        try {
            await this.#api.deletePage(this.#page.id)
            this.#notifier.info(`PageEditor deleted page #${this.#page.id}`)
            this.#router.go('/admin')
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Failed to delete page'
            this.#notifier.error(message, error)
            this.#deleteButton.disabled = false
        } finally {
            loading.stop()
        }
    }

}
