/**
 * @typedef {{path: string, title: string, snippet: string}} SearchResult
 */

/**
 * Site-wide search modal. Self-contained like OverlayManager (builds its
 * own DOM, appends to document.body once, exports a singleton) — but
 * simpler: no history/URL integration, this is a transient widget, not
 * an addressable page. Opening is wired externally (main.js delegates
 * clicks on .header-search-bar, HeaderShell's own markup), same pattern
 * main.js already uses for [data-overlay] links — keeps this class
 * unaware of where its trigger button happens to live in the DOM.
 *
 * While the input is empty, the placeholder types out (and deletes) each
 * admin-configured suggestion in turn — see #typeSuggestion. Results
 * come back from the API already HTML-escaped with <mark> wrapping any
 * matched query words (SearchService::highlight on the PHP side), so
 * the snippet is inserted via innerHTML rather than textContent.
 */
class SearchModal {

    /** Typing-animation timing, ms. */
    static #TYPE_DELAY   = 45
    static #DELETE_DELAY = 25
    static #HOLD_DELAY   = 1400
    static #NEXT_DELAY   = 300
    /** Debounce between the last keystroke and firing the search request. */
    static #SEARCH_DEBOUNCE = 200

    /** @type {HTMLDivElement} */
    #backdrop
    /** @type {HTMLDivElement} */
    #panel
    /** @type {HTMLInputElement} */
    #input
    /** @type {HTMLDivElement} */
    #resultsList
    #isOpen = false

    /** @type {string[]} */
    #suggestions = []
    #suggestionIndex = 0
    /** @type {ReturnType<typeof setTimeout> | null} */
    #typingTimer = null
    /** @type {ReturnType<typeof setTimeout> | null} */
    #debounceTimer = null
    /** Guards against a slower, earlier request overwriting a newer one. */
    #requestSeq = 0

    constructor () {
        this.#backdrop = document.createElement('div')
        this.#backdrop.className = 'search-modal-backdrop'
        this.#backdrop.addEventListener('click', () => this.close())

        this.#panel = document.createElement('div')
        this.#panel.className = 'search-modal'
        this.#panel.setAttribute('role', 'dialog')
        this.#panel.setAttribute('aria-modal', 'true')
        this.#panel.setAttribute('aria-label', 'Search')

        const header = document.createElement('div')
        header.className = 'search-modal-header'

        this.#input = document.createElement('input')
        this.#input.type = 'text'
        this.#input.className = 'search-modal-input'
        this.#input.placeholder = 'Search…'
        this.#input.setAttribute('aria-label', 'Search')
        this.#input.addEventListener('input', () => this.#onInput())

        const closeButton = document.createElement('button')
        closeButton.type = 'button'
        closeButton.className = 'search-modal-close'
        closeButton.setAttribute('aria-label', 'Close search')
        closeButton.textContent = '×'
        closeButton.addEventListener('click', () => this.close())

        header.append(this.#input, closeButton)

        this.#resultsList = document.createElement('div')
        this.#resultsList.className = 'search-modal-results'

        this.#panel.append(header, this.#resultsList)
        document.body.append(this.#backdrop, this.#panel)

        window.addEventListener('keydown', e => {
            if (e.key === 'Escape' && this.#isOpen)
                this.close()
        })
    }

    open () {
        this.#isOpen = true
        document.body.classList.add('search-modal-open')
        this.#input.value = ''
        this.#resultsList.replaceChildren()
        this.#input.focus()
        // Re-fetched on every open rather than cached — infrequent user
        // action, small payload, and it means an admin's edit to the
        // list shows up next time someone opens search, not just on
        // the next full page load.
        void this.#loadSuggestions()
    }

    close () {
        this.#isOpen = false
        document.body.classList.remove('search-modal-open')
        this.#stopTypingAnimation()
    }

    async #loadSuggestions () {
        try {
            const response = await fetch('/api/search/suggestions')
            if (!response.ok)
                return
            const data = await response.json()
            if (Array.isArray(data.suggestions))
                this.#suggestions = data.suggestions.filter(
                    (/** @type {unknown} */ s) => typeof s === 'string' && s !== '',
                )
        } catch {
            // Degrades to a plain "Search…" placeholder — not worth surfacing to the visitor.
        } finally {
            if (this.#isOpen)
                this.#startTypingAnimation()
        }
    }

    #onInput () {
        const query = this.#input.value.trim()
        clearTimeout(this.#debounceTimer ?? undefined)

        if (query === '') {
            this.#resultsList.replaceChildren()
            this.#startTypingAnimation()
            return
        }

        this.#stopTypingAnimation()
        this.#debounceTimer = setTimeout(() => this.#search(query), SearchModal.#SEARCH_DEBOUNCE)
    }

    /** @param {string} query */
    async #search (query) {
        const seq = ++this.#requestSeq
        try {
            const response = await fetch(`/api/search?q=${encodeURIComponent(query)}`)
            if (!response.ok)
                return
            const data = await response.json()
            if (seq !== this.#requestSeq)
                return // a newer query already superseded this response
            this.#renderResults(Array.isArray(data.results) ? data.results : [])
        } catch {
            // Network hiccup — leave whatever was already showing.
        }
    }

    /** @param {SearchResult[]} results */
    #renderResults (results) {
        this.#resultsList.replaceChildren()

        if (results.length === 0) {
            const empty = document.createElement('p')
            empty.className   = 'search-modal-empty'
            empty.textContent = 'No results.'
            this.#resultsList.append(empty)
            return
        }

        for (const result of results)
            this.#resultsList.append(this.#renderResult(result))
    }

    /** @param {SearchResult} result */
    #renderResult (result) {
        const link = document.createElement('a')
        link.className = 'search-modal-result'
        link.href      = `/${result.path.replace(/^\/+/, '')}`

        const title = document.createElement('span')
        title.className   = 'search-modal-result-title'
        title.textContent = result.title

        const snippet = document.createElement('span')
        snippet.className = 'search-modal-result-snippet'
        // Server-escaped with only <mark> as real markup — see SearchService::highlight.
        snippet.innerHTML = result.snippet

        link.append(title, snippet)
        return link
    }

    #startTypingAnimation () {
        if (this.#typingTimer !== null || this.#suggestions.length === 0)
            return
        this.#typeSuggestion(this.#suggestions[this.#suggestionIndex], 0, 'typing')
    }

    #stopTypingAnimation () {
        if (this.#typingTimer !== null) {
            clearTimeout(this.#typingTimer)
            this.#typingTimer = null
        }
        this.#input.placeholder = 'Search…'
    }

    /**
     * One step of a small typewriter state machine — always exactly one
     * pending timer, tracked in #typingTimer so #stopTypingAnimation can
     * always cleanly cancel it regardless of which phase it's in.
     *
     * @param {string}                            text
     * @param {number}                             pos
     * @param {'typing' | 'holding' | 'deleting'} phase
     */
    #typeSuggestion (text, pos, phase) {
        if (!this.#isOpen || this.#input.value !== '') {
            this.#typingTimer = null
            return
        }

        if (phase === 'typing') {
            this.#input.placeholder = text.slice(0, pos)
            this.#typingTimer = pos < text.length
                ? setTimeout(() => this.#typeSuggestion(text, pos + 1, 'typing'), SearchModal.#TYPE_DELAY)
                : setTimeout(() => this.#typeSuggestion(text, pos, 'holding'), SearchModal.#HOLD_DELAY)
            return
        }

        if (phase === 'holding') {
            this.#typeSuggestion(text, pos, 'deleting')
            return
        }

        // deleting
        this.#input.placeholder = text.slice(0, pos)
        if (pos > 0) {
            this.#typingTimer = setTimeout(() => this.#typeSuggestion(text, pos - 1, 'deleting'), SearchModal.#DELETE_DELAY)
            return
        }

        this.#suggestionIndex = (this.#suggestionIndex + 1) % this.#suggestions.length
        const next = this.#suggestions[this.#suggestionIndex]
        this.#typingTimer = setTimeout(() => this.#typeSuggestion(next, 0, 'typing'), SearchModal.#NEXT_DELAY)
    }

}

export default new SearchModal()
