import {describe, test, expect, beforeEach, vi} from 'vitest'
import SearchSettingsView                       from '/js/admin/content/search-settings-view.js'
import Loader                                   from '/js/admin/loader.js'
import Logger                                   from '/js/core/logger.js'
import Notifier                                 from '/js/admin/notifier.js'

/**
 * SearchSettingsView self-loads on construction (like HeaderShellView)
 * rather than receiving already-fetched data — every test flushes one
 * microtask before asserting.
 */
describe('SearchSettingsView', () => {

    /** @type {any} */
    let api
    /** @type {Loader} */
    let loader
    /** @type {Notifier} */
    let notifier

    beforeEach(() => {
        document.body.replaceChildren()
        api = {
            getSuggestions: vi.fn().mockResolvedValue([]),
            putSuggestions: vi.fn().mockResolvedValue(undefined),
        }
        loader = new Loader()
        vi.spyOn(Logger.prototype, 'info').mockImplementation(() => {})
        vi.spyOn(Logger.prototype, 'error').mockImplementation(() => {})
        notifier = new Notifier(new Logger())
    })

    const flush = async () => { await new Promise(resolve => setTimeout(resolve, 0)) }

    /** @returns {Promise<SearchSettingsView>} */
    const newView = async () => {
        const view = new SearchSettingsView(api, loader, notifier)
        await flush()
        return view
    }

    test('loads suggestions on construction', async () => {
        await newView()
        expect(api.getSuggestions).toHaveBeenCalled()
    })

    test('renders one row per loaded suggestion', async () => {
        api.getSuggestions.mockResolvedValue(['find an artist', 'view the studio'])
        const view = await newView()

        expect(view.element.querySelectorAll('.sections > .section-edit')).toHaveLength(2)
        const firstInput = /** @type {HTMLInputElement} */ (view.element.querySelector('.section-edit input'))
        expect(firstInput.value).toBe('find an artist')
    })

    test('+ Add suggestion appends an empty row and marks dirty', async () => {
        const view = await newView()
        const addButton = /** @type {HTMLButtonElement} */ (view.element.querySelector('.add-section'))

        addButton.click()

        expect(view.element.querySelectorAll('.sections > .section-edit')).toHaveLength(1)
        const status = /** @type {HTMLElement} */ (view.element.querySelector('.shell-save-status'))
        expect(status.textContent).toBe('Unsaved changes')
    })

    test('editing a suggestion marks dirty; Save persists via putSuggestions and clears dirty', async () => {
        api.getSuggestions.mockResolvedValue(['find an artist'])
        const view = await newView()

        const input = /** @type {HTMLInputElement} */ (view.element.querySelector('.section-edit input'))
        input.value = 'find an artist near you'
        input.dispatchEvent(new Event('input', {bubbles: true}))

        const status = /** @type {HTMLElement} */ (view.element.querySelector('.shell-save-status'))
        expect(status.textContent).toBe('Unsaved changes')

        const saveButton = /** @type {HTMLButtonElement} */ ([...view.element.querySelectorAll('.page-actions button')].find(b => b.textContent === 'Save'))
        saveButton.click()
        await flush()

        expect(api.putSuggestions).toHaveBeenCalledWith(['find an artist near you'])
        expect(status.textContent).toBe('Saved')
    })

    test('move up/down reorders suggestions', async () => {
        api.getSuggestions.mockResolvedValue(['first', 'second'])
        const view = await newView()

        const downButton = /** @type {HTMLButtonElement} */ (view.element.querySelector('.section-move-down'))
        downButton.click()

        const inputs = /** @type {HTMLInputElement[]} */ ([...view.element.querySelectorAll('.section-edit input')])
        expect(inputs.map(i => i.value)).toEqual(['second', 'first'])
    })

    test('remove button deletes a suggestion and marks dirty', async () => {
        api.getSuggestions.mockResolvedValue(['first', 'second'])
        const view = await newView()

        const removeButton = /** @type {HTMLButtonElement} */ (view.element.querySelector('.section-remove'))
        removeButton.click()

        expect(view.element.querySelectorAll('.sections > .section-edit')).toHaveLength(1)
        const input = /** @type {HTMLInputElement} */ (view.element.querySelector('.section-edit input'))
        expect(input.value).toBe('second')
    })

    test('save trims and drops blank entries before persisting', async () => {
        api.getSuggestions.mockResolvedValue(['first'])
        const view = await newView()

        const addButton = /** @type {HTMLButtonElement} */ (view.element.querySelector('.add-section'))
        addButton.click()
        const inputs = /** @type {HTMLInputElement[]} */ ([...view.element.querySelectorAll('.section-edit input')])
        inputs[0].value = '  first  '
        inputs[0].dispatchEvent(new Event('input', {bubbles: true}))
        // second input is the newly-added blank one — left empty on purpose

        const saveButton = /** @type {HTMLButtonElement} */ ([...view.element.querySelectorAll('.page-actions button')].find(b => b.textContent === 'Save'))
        saveButton.click()
        await flush()

        expect(api.putSuggestions).toHaveBeenCalledWith(['first'])
    })

    test('load failure surfaces via Notifier', async () => {
        api.getSuggestions.mockRejectedValue(new Error('Failed to load search suggestions (HTTP 500)'))
        await newView()

        const toast = document.querySelector('.toast-error')
        expect(toast?.textContent).toContain('Failed to load search suggestions')
    })

})
