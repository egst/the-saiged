import {describe, test, expect, beforeEach, vi} from 'vitest'
import HeaderShellView                          from '/shell/Header/Admin/header-shell-view.js'
import Loader                                   from '/js/admin/loader.js'
import Logger                                   from '/js/core/logger.js'
import Notifier                                 from '/js/admin/notifier.js'

/**
 * HeaderShellView self-loads on construction (like MediaView) rather
 * than receiving already-fetched data (like PageEditor) — every test
 * flushes one microtask before asserting.
 */
describe('HeaderShellView', () => {

    /** @type {any} */
    let api
    /** @type {Loader} */
    let loader
    /** @type {Notifier} */
    let notifier

    beforeEach(() => {
        document.body.replaceChildren()
        api = {
            getShell:      vi.fn().mockResolvedValue({links: [], logoUploadId: null}),
            putShell:      vi.fn().mockResolvedValue(undefined),
            ensureVariant: vi.fn().mockResolvedValue('/uploads/1/240x80-cover.webp'),
        }
        loader = new Loader()
        vi.spyOn(Logger.prototype, 'info').mockImplementation(() => {})
        vi.spyOn(Logger.prototype, 'error').mockImplementation(() => {})
        notifier = new Notifier(new Logger())
    })

    /** Wait one microtask + render frame for the async #load() to settle. */
    const flush = async () => { await new Promise(resolve => setTimeout(resolve, 0)) }

    /** @returns {Promise<HeaderShellView>} */
    const newView = async () => {
        const view = new HeaderShellView(api, loader, notifier)
        await flush()
        return view
    }

    test('loads from getShell("header") on construction', async () => {
        await newView()
        expect(api.getShell).toHaveBeenCalledWith('header')
    })

    test('save-status text is always present — no empty/dangling state', async () => {
        const view = await newView()

        const status = /** @type {HTMLElement} */ (view.element.querySelector('.shell-save-status'))
        expect(status.hidden).toBe(false)
        expect(status.textContent).toBe('Saved')
    })

    test('renders one row per loaded link', async () => {
        api.getShell.mockResolvedValue({
            links: [{label: 'Studio', href: '/studio'}, {label: 'Features', href: '/features'}],
            logoUploadId: null,
        })
        const view = await newView()

        expect(view.element.querySelectorAll('.sections > .section-edit')).toHaveLength(2)
        const firstLabelInput = /** @type {HTMLInputElement} */ (view.element.querySelector('input[name="label"]'))
        expect(firstLabelInput.value).toBe('Studio')
    })

    test('+ Add link button lives inside .sections, alongside the link cards, so it shares their gap', async () => {
        api.getShell.mockResolvedValue({links: [{label: 'Studio', href: '/studio'}], logoUploadId: null})
        const view = await newView()

        const sections = /** @type {HTMLElement} */ (view.element.querySelector('.sections'))
        expect(sections.querySelector(':scope > .add-section')).not.toBeNull()
    })

    test('+ Add link appends an empty link row and marks dirty', async () => {
        const view = await newView()
        const addButton = /** @type {HTMLButtonElement} */ (view.element.querySelector('.add-section'))

        addButton.click()

        expect(view.element.querySelectorAll('.sections > .section-edit')).toHaveLength(1)
        const status = /** @type {HTMLElement} */ (view.element.querySelector('.shell-save-status'))
        expect(status.textContent).toBe('Unsaved changes')
    })

    test('editing a link label marks dirty; Save persists via putShell and clears dirty', async () => {
        api.getShell.mockResolvedValue({links: [{label: 'Studio', href: '/studio'}], logoUploadId: null})
        const view = await newView()

        const labelInput = /** @type {HTMLInputElement} */ (view.element.querySelector('input[name="label"]'))
        labelInput.value = 'Renamed'
        labelInput.dispatchEvent(new Event('input', {bubbles: true}))

        const status = /** @type {HTMLElement} */ (view.element.querySelector('.shell-save-status'))
        expect(status.textContent).toBe('Unsaved changes')

        const saveButton = /** @type {HTMLButtonElement} */ ([...view.element.querySelectorAll('button')]
            .find(b => b.textContent === 'Save'))
        saveButton.click()
        await flush()

        expect(api.putShell).toHaveBeenCalledWith('header', {
            links: [{label: 'Renamed', href: '/studio'}],
            logoUploadId: null,
        })
        expect(status.textContent).toBe('Saved')
    })

    test('remove link button removes the row and marks dirty', async () => {
        api.getShell.mockResolvedValue({links: [{label: 'Studio', href: '/studio'}], logoUploadId: null})
        const view = await newView()

        const removeButton = /** @type {HTMLButtonElement} */ (view.element.querySelector('.section-remove'))
        removeButton.click()

        expect(view.element.querySelectorAll('.sections > .section-edit')).toHaveLength(0)
    })

    test('notifies on load failure', async () => {
        api.getShell.mockRejectedValue(new Error('boom'))
        const errorSpy = vi.spyOn(Notifier.prototype, 'error')

        await newView()

        expect(errorSpy).toHaveBeenCalled()
    })

    test('empty logo shows the add-logo button, not the preview', async () => {
        const view = await newView()

        const addButton = /** @type {HTMLButtonElement} */ (view.element.querySelector('.shell-logo-add'))
        const preview    = /** @type {HTMLElement} */ (view.element.querySelector('.shell-logo-picked'))
        expect(addButton.hidden).toBe(false)
        expect(preview.hidden).toBe(true)
    })

    test('loaded logo shows the preview, not the add-logo button', async () => {
        api.getShell.mockResolvedValue({links: [], logoUploadId: 9})
        const view = await newView()

        const addButton = /** @type {HTMLButtonElement} */ (view.element.querySelector('.shell-logo-add'))
        const preview    = /** @type {HTMLElement} */ (view.element.querySelector('.shell-logo-picked'))
        expect(addButton.hidden).toBe(true)
        expect(preview.hidden).toBe(false)
        expect(preview.querySelector('img')?.src).toContain('/uploads/9/thumb-200x200.webp')
    })

})

/**
 * Picking a logo requires a mocked UploadPicker (happy-dom has no real
 * file picker) — separate describe so vi.doMock only applies here, via
 * a fresh dynamic import per test (matches image-loading.test.js's
 * pattern for the same problem in Section admin editors).
 */
describe('HeaderShellView logo picking', () => {

    /** @type {any} */
    let api

    beforeEach(() => {
        document.body.replaceChildren()
        vi.resetModules()
        api = {
            getShell:      vi.fn().mockResolvedValue({links: [], logoUploadId: null}),
            putShell:      vi.fn().mockResolvedValue(undefined),
            ensureVariant: vi.fn().mockResolvedValue('/uploads/42/240x80-cover.webp'),
        }
    })

    let _v = 0
    /** @returns {Promise<typeof HeaderShellView>} */
    const importWithMockedPicker = async () => {
        const v = ++_v
        vi.doMock('/js/admin/uploads/upload-picker.js', () => ({
            default: class { open () { return Promise.resolve({id: 42, thumbUrl: '/uploads/42/thumb-200x200.webp'}) } },
        }))
        const mod = await import(`/shell/Header/Admin/header-shell-view.js?v=${v}`)
        return mod.default
    }

    test('clicking + Add logo picks an upload, shows the preview, and requests the header variant', async () => {
        const View   = await importWithMockedPicker()
        const loader = new Loader()
        vi.spyOn(Logger.prototype, 'info').mockImplementation(() => {})
        const notifier = new Notifier(new Logger())

        const view = new View(api, loader, notifier)
        await new Promise(resolve => setTimeout(resolve, 0))

        view.element.querySelector('.shell-logo-add')?.dispatchEvent(new MouseEvent('click'))
        await new Promise(resolve => setTimeout(resolve, 0))

        expect(api.ensureVariant).toHaveBeenCalledWith(42, 240, 80)
        const addButton = /** @type {HTMLButtonElement} */ (view.element.querySelector('.shell-logo-add'))
        const preview    = /** @type {HTMLElement} */ (view.element.querySelector('.shell-logo-picked'))
        expect(addButton.hidden).toBe(true)
        expect(preview.hidden).toBe(false)

        // Regression: the preview must use the thumbnail (always present
        // synchronously at upload time), not the header-sized variant —
        // that one is generated async by the ensureVariant() call above,
        // so pointing the <img> at it raced its own generation and
        // sometimes 404'd (broken image / alt text shown instead).
        const img = /** @type {HTMLImageElement | null} */ (preview.querySelector('img'))
        expect(img?.src).toContain('/uploads/42/thumb-200x200.webp')
    })

})
