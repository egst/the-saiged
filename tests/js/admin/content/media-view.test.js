import {describe, test, expect, beforeEach, vi} from 'vitest'
import MediaView                                from '/js/admin/content/media-view.js'
import Upload                                   from '/js/admin/uploads/upload.js'
import Loader                                   from '/js/admin/loader.js'
import Logger                                   from '/js/core/logger.js'
import Notifier                                 from '/js/admin/notifier.js'

/**
 * MediaView is the highest-leverage frontend piece for uploads — it
 * owns the grid render, the upload-button → file-picker → POST flow,
 * and the delete confirm + reload. We mock the Api so we can drive
 * the component through both happy and error paths without a backend.
 *
 * Sections we don't try to assert: exact CSS classes, layout, the
 * native file-picker dialog itself (jsdom/happy-dom doesn't open one).
 * What we DO pin: the API methods called, the data flow, dirty-side
 * effects on the DOM, and notifier behavior on failure.
 */
describe('MediaView', () => {

    /** @type {any} */
    let api
    /** @type {any} */
    let router
    /** @type {Loader} */
    let loader
    /** @type {Notifier} */
    let notifier

    beforeEach(() => {
        document.body.replaceChildren()
        api    = {
            listUploads:  vi.fn().mockResolvedValue([]),
            uploadFile:   vi.fn(),
            deleteUpload: vi.fn(),
        }
        router = {go: vi.fn()}
        loader = new Loader()
        // Silence logger; Notifier mounts toasts in document.body and we
        // assert on those below, so don't suppress its DOM side effects.
        vi.spyOn(Logger.prototype, 'info' ).mockImplementation(() => {})
        vi.spyOn(Logger.prototype, 'error').mockImplementation(() => {})
        notifier = new Notifier(new Logger())
    })

    /** @returns {MediaView} */
    const newView = () => new MediaView(api, router, loader, notifier)

    const fixtureUpload = (overrides = {}) => Upload.fromObject({
        id:          1,
        filename:    'photo.jpg',
        mime:        'image/jpeg',
        kind:        'image',
        size:        1234,
        width:       800,
        height:      600,
        uploadedAt:  '2026-06-16 12:00:00',
        originalUrl: '/uploads/1/original.jpg',
        thumbUrl:    '/uploads/1/thumb-200x200.webp',
        ...overrides,
    })

    /** Wait one microtask + render frame for the async #load() to settle. */
    const flush = async () => { await new Promise(resolve => setTimeout(resolve, 0)) }

    test('renders empty placeholder when no uploads', async () => {
        api.listUploads.mockResolvedValue([])
        const view = newView()
        await flush()

        const placeholder = view.element.querySelector('.placeholder')
        expect(placeholder?.textContent).toContain('No uploads')
    })

    test('renders one tile per upload with thumbnail and meta', async () => {
        api.listUploads.mockResolvedValue([
            fixtureUpload({id: 1, filename: 'a.jpg'}),
            fixtureUpload({id: 2, filename: 'b.jpg'}),
        ])
        const view = newView()
        await flush()

        const tiles = view.element.querySelectorAll('.media-tile')
        expect(tiles).toHaveLength(2)

        const firstTile = /** @type {HTMLElement} */ (tiles[0])
        expect(firstTile.dataset.id).toBe('1')
        const img = firstTile.querySelector('img')
        expect(img?.getAttribute('src')).toBe('/uploads/1/thumb-200x200.webp')
        expect(firstTile.textContent).toContain('a.jpg')
    })

    test('renders kind label for video uploads (no thumb)', async () => {
        api.listUploads.mockResolvedValue([
            fixtureUpload({kind: 'video', thumbUrl: null, filename: 'clip.mp4'}),
        ])
        const view = newView()
        await flush()

        const tile = view.element.querySelector('.media-tile')
        expect(tile?.querySelector('img')).toBeNull()
        expect(tile?.querySelector('.media-tile-kind')?.textContent).toBe('video')
    })

    test('upload button click triggers the hidden file input', async () => {
        const view = newView()
        await flush()

        const uploadButton = /** @type {HTMLButtonElement} */ ([...view.element.querySelectorAll('button')].find(b => b.textContent === 'Upload'))
        const fileInput    = /** @type {HTMLInputElement} */  (view.element.querySelector('input[type=file]'))
        const clickSpy = vi.spyOn(fileInput, 'click')

        uploadButton.click()

        expect(clickSpy).toHaveBeenCalled()
    })

    test('picking a file calls api.uploadFile and re-loads the grid', async () => {
        const view = newView()
        await flush()

        const file = new File(['x'], 'photo.jpg', {type: 'image/jpeg'})
        api.uploadFile.mockResolvedValue(fixtureUpload({filename: 'photo.jpg'}))
        api.listUploads.mockResolvedValueOnce([fixtureUpload({filename: 'photo.jpg'})])

        const fileInput = /** @type {HTMLInputElement} */ (view.element.querySelector('input[type=file]'))
        // happy-dom: assign FileList-like via Object.defineProperty since
        // .files is normally readonly. The change event triggers the
        // component's #onFilePicked flow.
        Object.defineProperty(fileInput, 'files', {value: [file], configurable: true})
        fileInput.dispatchEvent(new Event('change'))

        await flush()
        await flush() // second tick — #onFilePicked then awaits #load

        expect(api.uploadFile).toHaveBeenCalledWith(file)
        expect(api.listUploads).toHaveBeenCalledTimes(2) // initial + after upload
    })

    test('upload failure surfaces error via Notifier', async () => {
        const view = newView()
        await flush()

        api.uploadFile.mockRejectedValue(new Error('A page with path... wait, file too big'))

        const fileInput = /** @type {HTMLInputElement} */ (view.element.querySelector('input[type=file]'))
        Object.defineProperty(fileInput, 'files', {
            value: [new File(['x'], 'big.jpg', {type: 'image/jpeg'})],
            configurable: true,
        })
        fileInput.dispatchEvent(new Event('change'))

        await flush()
        await flush()

        const toast = document.querySelector('.toast-error')
        expect(toast?.textContent).toContain('file too big')
    })

    test('delete button calls api.deleteUpload after confirm', async () => {
        api.listUploads.mockResolvedValueOnce([fixtureUpload({id: 7})])
        api.deleteUpload.mockResolvedValue(undefined)
        vi.stubGlobal('confirm', vi.fn().mockReturnValue(true))

        const view = newView()
        await flush()

        const removeButton = /** @type {HTMLButtonElement} */ (view.element.querySelector('.media-tile-remove'))
        removeButton.click()

        await flush()
        await flush()

        expect(api.deleteUpload).toHaveBeenCalledWith(7)
    })

    test('delete is skipped when confirm dialog cancels', async () => {
        api.listUploads.mockResolvedValueOnce([fixtureUpload({id: 7})])
        vi.stubGlobal('confirm', vi.fn().mockReturnValue(false))

        const view = newView()
        await flush()

        const removeButton = /** @type {HTMLButtonElement} */ (view.element.querySelector('.media-tile-remove'))
        removeButton.click()

        await flush()
        expect(api.deleteUpload).not.toHaveBeenCalled()
    })

    test('listUploads failure surfaces via Notifier', async () => {
        api.listUploads.mockRejectedValue(new Error('Failed to load uploads (HTTP 500)'))
        newView()
        await flush()

        const toast = document.querySelector('.toast-error')
        expect(toast?.textContent).toContain('Failed to load uploads')
    })

})
