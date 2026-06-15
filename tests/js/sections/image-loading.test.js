/**
 * Verifies image loading UX:
 * - spinner appears immediately after image is picked (before img.src is set)
 * - spinner stays visible until img fires load/error
 * - imageloading / imageloaded events fire correctly
 * - no spinner fires on initial fromObject render
 *
 * Implementation uses requestAnimationFrame to defer img.src so the spinner
 * paints in the current frame before the image starts loading. Tests flush
 * the pending rAF before firing the load event manually.
 */
import {describe, test, expect, beforeEach, vi} from 'vitest'

const MOCK_UPLOAD = {id: 42, thumbUrl: '/uploads/42/thumb-200x200.webp'}

/** @returns {any} */
function makeApi () {
    return {
        listUploads:   vi.fn().mockResolvedValue([]),
        ensureVariant: vi.fn().mockResolvedValue(null),
    }
}

/** Collect custom event names as they bubble from el. */
function watchEvents (/** @type {Element} */ el, ...names) {
    const fired = /** @type {string[]} */ ([])
    for (const name of names)
        el.addEventListener(name, () => fired.push(name), {capture: true})
    return fired
}

/**
 * Flush two levels of requestAnimationFrame.
 * The implementation uses double-rAF: the first frame paints the spinner,
 * the second sets img.src. Both must fire before we expect the spinner to
 * still be present and before we manually fire the load event.
 */
async function flushRaf () {
    await new Promise(resolve => requestAnimationFrame(resolve))
    await new Promise(resolve => requestAnimationFrame(resolve))
}

/** Fire the load event on the first img inside root. */
function fireLoad (/** @type {Element} */ root) {
    root.querySelector('img')?.dispatchEvent(new Event('load'))
}

let _v = 0
/**
 * Import a section module with a mocked UploadPicker that immediately
 * resolves to MOCK_UPLOAD. Uses a unique query param per call so
 * vi.resetModules() takes effect between tests.
 */
async function importWithMockedPicker (/** @type {string} */ path) {
    const v = ++_v
    vi.doMock('/js/admin/uploads/upload-picker.js', () => ({
        default: class { open () { return Promise.resolve(MOCK_UPLOAD) } },
    }))
    const mod = await import(`${path}?v=${v}`)
    return mod.default
}

beforeEach(() => {
    document.body.replaceChildren()
    vi.resetModules()
})

// ─── ImageBreakSection ────────────────────────────────────────────────────────

describe('ImageBreakSection image loading', () => {

    test('no imageloading event on initial fromObject render', async () => {
        const {default: S} = await import('/sections/ImageBreak/Admin/image-break-section.js')
        const section = S.fromObject({uploadId: 5, caption: 'c'}, makeApi())
        const events  = watchEvents(section.element, 'imageloading')
        expect(events).toHaveLength(0)
    })

    test('spinner visible before img.src loads; gone after load event', async () => {
        const S       = await importWithMockedPicker('/sections/ImageBreak/Admin/image-break-section.js')
        const section = S.createEmpty(makeApi())
        document.body.append(section.element)

        const events = watchEvents(section.element, 'imageloading', 'imageloaded')

        section.element.querySelector('.image-break-add').click()

        // imageloading fires synchronously when spinner is added (before rAF)
        await vi.waitFor(() => expect(events).toContain('imageloading'))

        // Spinner must be in DOM now, imageloaded must NOT have fired yet
        expect(section.element.querySelector('.img-spinner')).not.toBeNull()
        expect(events).not.toContain('imageloaded')

        // Flush the pending rAF so img.src is set
        await flushRaf()

        // Spinner still present — image hasn't "loaded" yet (no load event)
        expect(section.element.querySelector('.img-spinner')).not.toBeNull()

        // Simulate image finishing load
        fireLoad(section.element)

        expect(events).toContain('imageloaded')
        expect(section.element.querySelector('.img-spinner')).toBeNull()
    })

})

// ─── PageCoverSection ─────────────────────────────────────────────────────────

describe('PageCoverSection image loading', () => {

    test('no imageloading event on initial fromObject render', async () => {
        const {default: S} = await import('/sections/PageCover/Admin/page-cover-section.js')
        const section = S.fromObject({uploadId: 3, eyebrow: 'e', heading: 'h'}, makeApi())
        const events  = watchEvents(section.element, 'imageloading')
        expect(events).toHaveLength(0)
    })

    test('spinner visible before img.src loads; gone after load event', async () => {
        const S       = await importWithMockedPicker('/sections/PageCover/Admin/page-cover-section.js')
        const section = S.createEmpty(makeApi())
        document.body.append(section.element)

        const events = watchEvents(section.element, 'imageloading', 'imageloaded')

        section.element.querySelector('.image-break-add').click()

        await vi.waitFor(() => expect(events).toContain('imageloading'))

        expect(section.element.querySelector('.img-spinner')).not.toBeNull()
        expect(events).not.toContain('imageloaded')

        await flushRaf()

        expect(section.element.querySelector('.img-spinner')).not.toBeNull()

        fireLoad(section.element)

        expect(events).toContain('imageloaded')
        expect(section.element.querySelector('.img-spinner')).toBeNull()
    })

})

// ─── LinkCarouselSection ──────────────────────────────────────────────────────

describe('LinkCarouselSection image loading', () => {

    test('no imageloading event on initial fromObject render', async () => {
        const {default: S} = await import('/sections/LinkCarousel/Admin/link-carousel-section.js')
        const section = S.fromObject({
            items: [{uploadId: 1, eyebrow: 'e', title: 't', buttonText: 'b', buttonHref: '/x'}],
        }, makeApi())
        const events = watchEvents(section.element, 'imageloading')
        expect(events).toHaveLength(0)
    })

    test('spinner visible before img.src loads; gone after load event', async () => {
        const S       = await importWithMockedPicker('/sections/LinkCarousel/Admin/link-carousel-section.js')
        const section = S.createEmpty(makeApi())
        document.body.append(section.element)

        const events = watchEvents(section.element, 'imageloading', 'imageloaded')

        section.element.querySelector('.link-carousel-add').click()

        await vi.waitFor(() => expect(events).toContain('imageloading'))

        expect(section.element.querySelector('.img-spinner')).not.toBeNull()
        expect(events).not.toContain('imageloaded')

        await flushRaf()

        expect(section.element.querySelector('.img-spinner')).not.toBeNull()

        fireLoad(section.element)

        expect(events).toContain('imageloaded')
        expect(section.element.querySelector('.img-spinner')).toBeNull()
    })

})

// ─── ProjectGridSection ───────────────────────────────────────────────────────

describe('ProjectGridSection image loading', () => {

    test('no imageloading event on initial fromObject render', async () => {
        const {default: S} = await import('/sections/ProjectGrid/Admin/project-grid-section.js')
        const section = S.fromObject({
            items: [{uploadId: 7, type: 'Editorial', heading: 'h', body: 'b'}],
        }, makeApi())
        const events = watchEvents(section.element, 'imageloading')
        expect(events).toHaveLength(0)
    })

    test('spinner visible before img.src loads; gone after load event', async () => {
        const S       = await importWithMockedPicker('/sections/ProjectGrid/Admin/project-grid-section.js')
        const section = S.createEmpty(makeApi())
        document.body.append(section.element)

        const events = watchEvents(section.element, 'imageloading', 'imageloaded')

        section.element.querySelector('.project-grid-add').click()

        await vi.waitFor(() => expect(events).toContain('imageloading'))

        expect(section.element.querySelector('.img-spinner')).not.toBeNull()
        expect(events).not.toContain('imageloaded')

        await flushRaf()

        expect(section.element.querySelector('.img-spinner')).not.toBeNull()

        fireLoad(section.element)

        expect(events).toContain('imageloaded')
        expect(section.element.querySelector('.img-spinner')).toBeNull()
    })

})
