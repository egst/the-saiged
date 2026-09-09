import {describe, test, expect, beforeEach, vi} from 'vitest'
import TypographyView                           from '/typography/Admin/typography-view.js'
import Loader                                   from '/js/admin/loader.js'
import Logger                                   from '/js/core/logger.js'
import Notifier                                 from '/js/admin/notifier.js'

/**
 * TypographyView follows MediaView's shape (self-loading, act-then-reload)
 * rather than Shell's edit-then-Save flow. We mock the Api so we can drive
 * the component through the add/remove flows without a backend.
 */
describe('TypographyView', () => {

    /** @type {any} */
    let api
    /** @type {Loader} */
    let loader
    /** @type {Notifier} */
    let notifier

    const fixtureFace = (overrides = {}) => ({
        id:        1,
        weightMin: 400,
        weightMax: 700,
        style:     'normal',
        upload:    {filename: 'brand.otf'},
        ...overrides,
    })

    /** Wait one microtask + render frame for async work to settle. */
    const flush = async () => { await new Promise(resolve => setTimeout(resolve, 0)) }

    beforeEach(() => {
        document.body.replaceChildren()
        api = {
            getTypography:       vi.fn().mockResolvedValue({heading: [], text: []}),
            addTypographyFace:   vi.fn().mockResolvedValue(undefined),
            removeTypographyFace: vi.fn().mockResolvedValue(undefined),
        }
        loader = new Loader()
        vi.spyOn(Logger.prototype, 'info' ).mockImplementation(() => {})
        vi.spyOn(Logger.prototype, 'error').mockImplementation(() => {})
        notifier = new Notifier(new Logger())
    })

    test('renders empty placeholder for a role with no faces', async () => {
        const view = new TypographyView(api, loader, notifier)
        await flush()

        const groups = view.element.querySelectorAll('.field-group')
        expect(groups).toHaveLength(2)
        expect(view.element.textContent).toContain('No custom faces')
    })

    test('renders one card per face with filename, weight range and style', async () => {
        api.getTypography.mockResolvedValue({
            heading: [],
            text:    [fixtureFace({weightMin: 400, weightMax: 700, style: 'italic'})],
        })
        const view = new TypographyView(api, loader, notifier)
        await flush()

        const card = view.element.querySelector('.section-edit')
        expect(card?.textContent).toContain('brand.otf')
        expect(card?.textContent).toContain('400–700')
        expect(card?.textContent).toContain('italic')
    })

    test('remove button calls api.removeTypographyFace after confirm and reloads', async () => {
        api.getTypography.mockResolvedValueOnce({heading: [], text: [fixtureFace({id: 9})]})
        vi.stubGlobal('confirm', vi.fn().mockReturnValue(true))

        const view = new TypographyView(api, loader, notifier)
        await flush()

        api.getTypography.mockResolvedValueOnce({heading: [], text: []})
        const removeButton = /** @type {HTMLButtonElement} */ (view.element.querySelector('.section-remove'))
        removeButton.click()
        await flush()
        await flush()

        expect(api.removeTypographyFace).toHaveBeenCalledWith(9)
        expect(api.getTypography).toHaveBeenCalledTimes(2)
    })

    test('remove is skipped when confirm dialog cancels', async () => {
        api.getTypography.mockResolvedValueOnce({heading: [], text: [fixtureFace({id: 9})]})
        vi.stubGlobal('confirm', vi.fn().mockReturnValue(false))

        const view = new TypographyView(api, loader, notifier)
        await flush()

        const removeButton = /** @type {HTMLButtonElement} */ (view.element.querySelector('.section-remove'))
        removeButton.click()
        await flush()

        expect(api.removeTypographyFace).not.toHaveBeenCalled()
    })

    test('getTypography failure surfaces via Notifier', async () => {
        api.getTypography.mockRejectedValue(new Error('Failed to load typography (HTTP 500)'))
        new TypographyView(api, loader, notifier)
        await flush()

        const toast = document.querySelector('.toast-error')
        expect(toast?.textContent).toContain('Failed to load typography')
    })

    /**
     * Picking a font requires a mocked UploadPicker (happy-dom has no real
     * file picker) — separate describe so vi.doMock only applies here, via
     * a fresh dynamic import per test (matches header-shell-view.test.js's
     * pattern for the same problem).
     */
    describe('adding a face', () => {

        let _v = 0
        /** @returns {Promise<typeof TypographyView>} */
        const importWithMockedPicker = async () => {
            const v = ++_v
            vi.doMock('/js/admin/uploads/upload-picker.js', () => ({
                default: class {
                    /** @param {string} kind */
                    open (kind) {
                        expect(kind).toBe('font')
                        return Promise.resolve({id: 9, filename: 'brand.otf'})
                    }
                },
            }))
            const mod = await import(`/typography/Admin/typography-view.js?v=${v}`)
            return mod.default
        }

        beforeEach(() => { vi.resetModules() })

        test('picking a font shows an inline weight/style form', async () => {
            const View = await importWithMockedPicker()
            const view = new View(api, loader, notifier)
            await flush()

            const addButton = /** @type {HTMLButtonElement} */ ([...view.element.querySelectorAll('button')].find(b => b.textContent === '+ Add face'))
            addButton.click()
            await flush()

            const cards = view.element.querySelectorAll('.section-edit')
            const pending = [...cards].find(c => c.textContent?.includes('brand.otf'))
            expect(pending?.querySelector('input[type=number]')).not.toBeNull()
            expect(pending?.querySelector('select')).not.toBeNull()
        })

        test('confirming the pending face calls api.addTypographyFace with the chosen role and reloads', async () => {
            const View = await importWithMockedPicker()
            const view = new View(api, loader, notifier)
            await flush()

            const addButtons = [...view.element.querySelectorAll('button')].filter(b => b.textContent === '+ Add face')
            const textAddButton = /** @type {HTMLButtonElement} */ (addButtons[1]) // second group = 'text'
            textAddButton.click()
            await flush()

            const numberInputs = /** @type {HTMLInputElement[]} */ ([...view.element.querySelectorAll('input[type=number]')])
            numberInputs[0].valueAsNumber = 400
            numberInputs[1].valueAsNumber = 700

            const confirmButton = /** @type {HTMLButtonElement} */ ([...view.element.querySelectorAll('button')].find(b => b.textContent === 'Add'))
            confirmButton.click()
            await flush()
            await flush()

            expect(api.addTypographyFace).toHaveBeenCalledWith('text', {
                uploadId:  9,
                weightMin: 400,
                weightMax: 700,
                style:     'normal',
            })
        })

        test('cancel button removes the pending card without calling the api', async () => {
            const View = await importWithMockedPicker()
            const view = new View(api, loader, notifier)
            await flush()

            const addButton = /** @type {HTMLButtonElement} */ ([...view.element.querySelectorAll('button')].find(b => b.textContent === '+ Add face'))
            addButton.click()
            await flush()

            const cancelButton = /** @type {HTMLButtonElement} */ ([...view.element.querySelectorAll('button')].find(b => b.textContent === 'Cancel'))
            cancelButton.click()

            expect(view.element.textContent).not.toContain('brand.otf')
            expect(api.addTypographyFace).not.toHaveBeenCalled()
        })

    })

})
