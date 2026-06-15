import {sectionModuleUrl} from '/js/admin/sections/section-module-url.js'

/**
 * Modal that presents every available section type as a visual preview
 * card. Resolves with the chosen type string, or null if cancelled.
 *
 * Each section's preview thumbnail comes from that section's own static
 * preview() method — adding a new section requires no changes here.
 *
 * Single-use per open() call — built fresh, mounted to document.body,
 * removed when a choice is made or cancelled.
 */
export default class SectionPicker {

    /** @type {Array<{type: string, label: string}>} */
    #sections

    /** @param {Array<{type: string, label: string}>} sections */
    constructor (sections) {
        this.#sections = sections
    }

    /** @returns {Promise<string | null>} */
    async open () {
        const cards = await Promise.all(this.#sections.map(async ({type, label}) => {
            const url    = sectionModuleUrl(type)
            const module = await import(url)
            /** @type {typeof import('/js/admin/sections/section.js').default} */
            const SectionClass = module.default
            return buildCard(type, label, SectionClass.preview())
        }))

        const overlay = document.createElement('div')
        overlay.className = 'sp-overlay'

        const modal = document.createElement('div')
        modal.className   = 'sp-modal'
        modal.setAttribute('role', 'dialog')
        modal.setAttribute('aria-label', 'Add section')

        const header = document.createElement('header')
        header.className = 'sp-header'
        const title = document.createElement('h2')
        title.textContent = 'Add section'
        const closeButton = document.createElement('button')
        closeButton.type        = 'button'
        closeButton.className   = 'sp-close'
        closeButton.title       = 'Cancel'
        closeButton.textContent = '×'
        header.append(title, closeButton)

        const grid = document.createElement('div')
        grid.className = 'sp-grid'
        grid.append(...cards)

        modal.append(header, grid)
        overlay.append(modal)
        document.body.append(overlay)

        return new Promise(resolve => {
            /** @param {string | null} type */
            const finish = type => {
                overlay.remove()
                resolve(type)
            }

            closeButton.addEventListener('click', () => finish(null))
            overlay.addEventListener('click', event => {
                if (event.target === overlay)
                    finish(null)
            })
            overlay.addEventListener('keydown', event => {
                if (event.key === 'Escape')
                    finish(null)
            })
            grid.addEventListener('click', event => {
                const card = /** @type {Element} */ (event.target)
                    .closest('.sp-card')
                if (card instanceof HTMLButtonElement && card.dataset.type)
                    finish(card.dataset.type)
            })
        })
    }

}

/**
 * @param {string}     type
 * @param {string}     label
 * @param {HTMLElement} preview
 * @returns {HTMLButtonElement}
 */
function buildCard (type, label, preview) {
    const card = document.createElement('button')
    card.type         = 'button'
    card.className    = 'sp-card'
    card.dataset.type = type

    const labelEl = document.createElement('span')
    labelEl.className   = 'sp-label'
    labelEl.textContent = label

    card.append(preview, labelEl)
    return card
}
