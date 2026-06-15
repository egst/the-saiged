import {describe, test, expect, beforeEach, vi} from 'vitest'
import Api                                       from '/js/admin/api.js'

/**
 * Api tests focus on response shape handling — happy-path is exercised
 * end-to-end by PHP integration tests; here we pin the error-message
 * extraction and shape validation, which are the bits that have bitten
 * us before (HTTP 409 surfacing as "createPage failed: 409" instead of
 * the backend's "path 'X' already exists").
 */
describe('Api error message extraction', () => {

    const sectionFactory = /** @type {any} */ ({})  // never reached on error paths

    beforeEach(() => {
        vi.restoreAllMocks()
    })

    /** @param {{ok: boolean, status?: number, body?: unknown}} opts */
    const fakeResponse = ({ok, status = ok ? 200 : 500, body}) =>
        /** @type {Response} */ (/** @type {unknown} */ ({
            ok,
            status,
            json: async () => body,
        }))

    test('surfaces backend {error: "..."} message verbatim', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
            fakeResponse({ok: false, status: 409, body: {error: "path 'about' already exists"}})
        ))

        const api = new Api(sectionFactory)

        await expect(api.createPage({path: 'about', title: 'About'}))
            .rejects.toThrow("path 'about' already exists")
    })

    test('falls back to a generic message + status code when body has no `error` key', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
            fakeResponse({ok: false, status: 500, body: {}})
        ))

        const api = new Api(sectionFactory)

        await expect(api.createPage({path: 'x', title: 'X'}))
            .rejects.toThrow(/Failed to create page \(HTTP 500\)/)
    })

    test('falls back when body is non-JSON / parsing throws', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(/** @type {Response} */ (/** @type {unknown} */ ({
            ok:     false,
            status: 503,
            json:   async () => { throw new Error('Unexpected token') },
        }))))

        const api = new Api(sectionFactory)

        await expect(api.createPage({path: 'x', title: 'X'}))
            .rejects.toThrow(/Failed to create page \(HTTP 503\)/)
    })

    test('falls back when body.error is empty string', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
            fakeResponse({ok: false, status: 400, body: {error: ''}})
        ))

        const api = new Api(sectionFactory)

        await expect(api.createPage({path: 'x', title: 'X'}))
            .rejects.toThrow(/HTTP 400/)
    })

    test('error path applies to every mutating endpoint', async () => {
        // Each endpoint has its own fallback prefix; the extracted backend
        // message replaces it. Pin that the wiring is consistent across
        // methods, not just createPage.
        const cases = [
            {method: 'putPage',      args: [1, {title: 't', metaDesc: null, status: 'draft', sections: []}], msg: 'oops put'},
            {method: 'copyPage',     args: [1, {path: 'p', title: 't'}],                                     msg: 'oops copy'},
            {method: 'deletePage',   args: [1],                                                              msg: 'oops del'},
            {method: 'uploadFile',   args: [new File(['x'], 'x.jpg', {type: 'image/jpeg'})],                 msg: 'oops upload'},
            {method: 'deleteUpload', args: [1],                                                              msg: 'oops del upload'},
        ]
        for (const {method, args, msg} of cases) {
            vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
                fakeResponse({ok: false, status: 400, body: {error: msg}})
            ))
            const api = /** @type {any} */ (new Api(sectionFactory))
            await expect(api[method](...args)).rejects.toThrow(msg)
        }
    })

})

describe('Api listSections', () => {

    const sectionFactory = /** @type {any} */ ({})

    beforeEach(() => {
        vi.restoreAllMocks()
    })

    /** @param {{ok: boolean, status?: number, body?: unknown}} opts */
    const fakeResponse = ({ok, status = ok ? 200 : 500, body}) =>
        /** @type {Response} */ (/** @type {unknown} */ ({
            ok,
            status,
            json: async () => body,
        }))

    test('returns the parsed {type, label} list', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
            fakeResponse({ok: true, body: {sections: [
                {type: 'text',           label: 'Text'},
                {type: 'image-carousel', label: 'Image carousel'},
            ]}})
        ))

        const api      = new Api(sectionFactory)
        const sections = await api.listSections()

        expect(sections).toEqual([
            {type: 'text',           label: 'Text'},
            {type: 'image-carousel', label: 'Image carousel'},
        ])
    })

    test('throws on missing sections key', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
            fakeResponse({ok: true, body: {wrong: 'key'}})
        ))

        const api = new Api(sectionFactory)
        await expect(api.listSections()).rejects.toThrow(/invalid response shape/)
    })

    test('throws when an entry is missing type or label', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
            fakeResponse({ok: true, body: {sections: [{type: 'text'}]}})
        ))

        const api = new Api(sectionFactory)
        await expect(api.listSections()).rejects.toThrow(/invalid section shape/)
    })

    test('surfaces backend error message on failure', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
            fakeResponse({ok: false, status: 500, body: {}})
        ))

        const api = new Api(sectionFactory)
        await expect(api.listSections()).rejects.toThrow(/Failed to load section types \(HTTP 500\)/)
    })

})

describe('Api uploads', () => {

    const sectionFactory = /** @type {any} */ ({})

    beforeEach(() => {
        vi.restoreAllMocks()
    })

    /** @param {{ok: boolean, status?: number, body?: unknown}} opts */
    const fakeResponse = ({ok, status = ok ? 200 : 500, body}) =>
        /** @type {Response} */ (/** @type {unknown} */ ({
            ok,
            status,
            json: async () => body,
        }))

    /** A reusable valid upload payload returned by the API. */
    const fixtureUploadJson = () => ({
        id:          7,
        filename:    'photo.jpg',
        mime:        'image/jpeg',
        kind:        'image',
        size:        1234,
        width:       800,
        height:      600,
        uploadedAt:  '2026-06-16 12:00:00',
        originalUrl: '/uploads/7/original.jpg',
        thumbUrl:    '/uploads/7/thumb-200x200.webp',
    })

    test('listUploads returns parsed Upload instances', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
            fakeResponse({ok: true, body: {uploads: [fixtureUploadJson()]}})
        ))

        const api = new Api(sectionFactory)
        const uploads = await api.listUploads()

        expect(uploads).toHaveLength(1)
        expect(uploads[0].id      ).toBe(7)
        expect(uploads[0].filename).toBe('photo.jpg')
    })

    test('listUploads throws on invalid response shape', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
            fakeResponse({ok: true, body: {wrong: 'key'}})
        ))

        const api = new Api(sectionFactory)
        await expect(api.listUploads()).rejects.toThrow(/invalid response shape/)
    })

    test('uploadFile POSTs multipart and returns parsed Upload', async () => {
        const fetchSpy = vi.fn().mockResolvedValue(
            fakeResponse({ok: true, status: 201, body: {ok: true, upload: fixtureUploadJson()}})
        )
        vi.stubGlobal('fetch', fetchSpy)

        const file = new File(['hello'], 'photo.jpg', {type: 'image/jpeg'})
        const api  = new Api(sectionFactory)
        const upload = await api.uploadFile(file)

        expect(upload.id).toBe(7)
        const [url, init] = fetchSpy.mock.calls[0]
        expect(url).toBe('/api/admin/uploads')
        expect(init.method).toBe('POST')
        expect(init.body).toBeInstanceOf(FormData)
        // CRITICAL: do NOT manually set Content-Type — the browser adds the
        // multipart boundary. Manual header would break the upload silently.
        expect(init.headers).toBeUndefined()
        // The FormData body must carry the file under the `file` field
        // (matches MediaController::createUpload's $_FILES['file']).
        expect(init.body.get('file')).toBe(file)
    })

    test('uploadFile dispatches uploads.changed on success', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
            fakeResponse({ok: true, status: 201, body: {ok: true, upload: fixtureUploadJson()}})
        ))

        const api      = new Api(sectionFactory)
        const listener = vi.fn()
        api.addEventListener('uploads.changed', listener)

        await api.uploadFile(new File(['x'], 'x.jpg', {type: 'image/jpeg'}))

        expect(listener).toHaveBeenCalled()
    })

    test('deleteUpload dispatches uploads.changed on success', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
            fakeResponse({ok: true, body: {ok: true, id: 7}})
        ))

        const api      = new Api(sectionFactory)
        const listener = vi.fn()
        api.addEventListener('uploads.changed', listener)

        await api.deleteUpload(7)

        expect(listener).toHaveBeenCalled()
    })

})
