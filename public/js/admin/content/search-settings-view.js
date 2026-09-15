/**
 * @import Api      from '/js/admin/api.js'
 * @import Loader   from '/js/admin/loader.js'
 * @import Notifier from '/js/admin/notifier.js'
 */

/**
 * Top-level admin view for search settings — reached via
 * /admin/site/search. Right now just the "naseptávač": the list of
 * placeholder strings the public search input's typing animation
 * cycles through while empty. Follows HeaderShellView's shape exactly
 * (edit-then-Save, not per-item REST) — there's no natural id per
 * entry, just an ordered list of strings, same as Header's links.
 */
export default class SearchSettingsView {

    /** @type {Api} */
    #api
    /** @type {Loader} */
    #loader
    /** @type {Notifier} */
    #notifier

    /** @type {HTMLDivElement} */
    #element
    /** @type {HTMLDivElement} */
    #suggestionsList
    /** @type {HTMLButtonElement} */
    #saveButton
    /** @type {HTMLElement} */
    #statusLabel
    /** @type {HTMLButtonElement} */
    #addButton = document.createElement('button')

    /** @type {string[]} */
    #suggestions = []
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

        this.#suggestionsList = document.createElement('div')
        this.#suggestionsList.className = 'sections'

        this.#addButton.type        = 'button'
        this.#addButton.className   = 'add-section'
        this.#addButton.textContent = '+ Add suggestion'
        this.#addButton.addEventListener('click', () => this.#add())

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
        title.textContent = 'Search'
        const meta = document.createElement('p')
        meta.className = 'page-meta'
        meta.append(this.#statusLabel)
        summary.append(title, meta)

        const actions = document.createElement('div')
        actions.className = 'page-actions'
        actions.append(this.#saveButton)

        const group = document.createElement('div')
        group.className = 'field-group'
        const heading = document.createElement('h3')
        heading.className   = 'field-group-title'
        heading.textContent = 'Search suggestions'
        const hint = document.createElement('p')
        hint.className   = 'page-meta'
        hint.textContent = 'Shown one at a time, typed out, in the search box while it’s empty.'
        group.append(heading, hint, this.#suggestionsList)

        root.append(summary, actions, group)
        return root
    }

    async #load () {
        const loading = this.#loader.start()
        try {
            this.#suggestions = await this.#api.getSuggestions()
            this.#renderSuggestions()
            this.#savedState = this.#serialize()
            this.#refreshDirty()
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Failed to load search settings'
            this.#notifier.error(message, error)
        } finally {
            loading.stop()
        }
    }

    #renderSuggestions () {
        this.#suggestionsList.replaceChildren()
        for (let index = 0; index < this.#suggestions.length; index++)
            this.#suggestionsList.append(this.#renderSuggestion(index))
        this.#suggestionsList.append(this.#addButton)
    }

    /** @param {number} index */
    #renderSuggestion (index) {
        const card = document.createElement('div')
        card.className = 'section-edit'

        const row = document.createElement('div')
        row.className = 'control-row'

        const input = document.createElement('input')
        input.className   = 'control-row-input'
        input.value       = this.#suggestions[index]
        input.placeholder = 'e.g. "find an artist"'
        input.addEventListener('input', () => { this.#suggestions[index] = input.value })

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
        downButton.disabled    = index === this.#suggestions.length - 1
        downButton.addEventListener('click', () => this.#move(index, 1))

        const removeButton = document.createElement('button')
        removeButton.type        = 'button'
        removeButton.className   = 'section-remove'
        removeButton.title       = 'Remove suggestion'
        removeButton.textContent = '×'
        removeButton.addEventListener('click', () => this.#remove(index))

        row.append(input, upButton, downButton, removeButton)
        card.append(row)
        return card
    }

    #add () {
        this.#suggestions.push('')
        this.#renderSuggestions()
        this.#refreshDirty()
    }

    /**
     * @param {number} index
     * @param {number} direction  -1 = up, +1 = down
     */
    #move (index, direction) {
        const target = index + direction
        if (target < 0 || target >= this.#suggestions.length)
            return
        const [moved] = this.#suggestions.splice(index, 1)
        this.#suggestions.splice(target, 0, moved)
        this.#renderSuggestions()
        this.#refreshDirty()
    }

    /** @param {number} index */
    #remove (index) {
        this.#suggestions.splice(index, 1)
        this.#renderSuggestions()
        this.#refreshDirty()
    }

    #serialize () {
        return JSON.stringify(this.#suggestions)
    }

    #refreshDirty () {
        const dirty = this.#serialize() !== this.#savedState
        this.#statusLabel.textContent = dirty ? 'Unsaved changes' : 'Saved'
        this.#statusLabel.classList.toggle('is-dirty', dirty)
        this.#saveButton.classList.toggle('primary', dirty)
    }

    async #save () {
        const cleaned = this.#suggestions.map(s => s.trim()).filter(s => s !== '')
        if (cleaned.length !== this.#suggestions.length) {
            this.#suggestions = cleaned
            this.#renderSuggestions()
        }

        const loading = this.#loader.start()
        try {
            await this.#api.putSuggestions(this.#suggestions)
            this.#savedState = this.#serialize()
            this.#refreshDirty()
            this.#notifier.info('Search settings saved')
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Failed to save search settings'
            this.#notifier.error(message, error)
        } finally {
            loading.stop()
        }
    }

}
