import {describe, test, expect, beforeEach, vi} from 'vitest'

const MOCK_UPLOAD = {id: 9, thumbUrl: '/uploads/9/thumb-200x200.webp'}

/** @returns {any} */
function makeApi () {
    return {
        listUploads:   vi.fn().mockResolvedValue([]),
        ensureVariant: vi.fn().mockResolvedValue(null),
    }
}

const VALID = {
    uploadId:   5,
    label:      'Feature',
    heading:    'Venice Biennale',
    date:       'May 9 — Nov 22',
    buttonText: 'Read now',
    buttonHref: '/venice',
}

beforeEach(() => {
    document.body.replaceChildren()
    vi.resetModules()
})

describe('SplitBannerSection', () => {

    test('static type is "split-banner"', async () => {
        const {default: S} = await import('/sections/SplitBanner/Admin/split-banner-section.js')
        expect(S.type()).toBe('split-banner')
    })

    test('fromObject builds with valid data', async () => {
        const {default: S} = await import('/sections/SplitBanner/Admin/split-banner-section.js')
        const section = S.fromObject(VALID, makeApi())

        expect(section.uploadId  ).toBe(5)
        expect(section.label     ).toBe('Feature')
        expect(section.heading   ).toBe('Venice Biennale')
        expect(section.date      ).toBe('May 9 — Nov 22')
        expect(section.buttonText).toBe('Read now')
        expect(section.buttonHref).toBe('/venice')
    })

    test('fromObject accepts null uploadId', async () => {
        const {default: S} = await import('/sections/SplitBanner/Admin/split-banner-section.js')
        const section = S.fromObject({...VALID, uploadId: null}, makeApi())

        expect(section.uploadId).toBeNull()
    })

    test.each([
        ['null',                null],
        ['empty object',        {}],
        ['missing label',       {uploadId: null, heading: 'H', date: '', buttonText: '', buttonHref: ''}],
        ['missing heading',     {uploadId: null, label: 'L', date: '', buttonText: '', buttonHref: ''}],
        ['non-string heading',  {uploadId: null, label: 'L', heading: 1, date: '', buttonText: '', buttonHref: ''}],
        ['bad uploadId type',   {uploadId: 'x', label: 'L', heading: 'H', date: '', buttonText: '', buttonHref: ''}],
    ])('fromObject throws on invalid shape: %s', async (_label, input) => {
        const {default: S} = await import('/sections/SplitBanner/Admin/split-banner-section.js')
        expect(() => S.fromObject(input, makeApi())).toThrow()
    })

    test('toObject roundtrips through fromObject', async () => {
        const {default: S} = await import('/sections/SplitBanner/Admin/split-banner-section.js')
        const original = S.fromObject(VALID, makeApi())
        const clone    = S.fromObject(original.toObject(), makeApi())

        expect(clone.uploadId  ).toBe(VALID.uploadId)
        expect(clone.label     ).toBe(VALID.label)
        expect(clone.heading   ).toBe(VALID.heading)
        expect(clone.buttonText).toBe(VALID.buttonText)
    })

    test('createEmpty yields nulls and empty strings', async () => {
        const {default: S} = await import('/sections/SplitBanner/Admin/split-banner-section.js')
        const empty = S.createEmpty(makeApi())

        expect(empty.uploadId  ).toBeNull()
        expect(empty.label     ).toBe('')
        expect(empty.heading   ).toBe('')
        expect(empty.date      ).toBe('')
        expect(empty.buttonText).toBe('')
        expect(empty.buttonHref).toBe('')
    })

    test('element exposes inputs pre-filled with data', async () => {
        const {default: S} = await import('/sections/SplitBanner/Admin/split-banner-section.js')
        const section = S.fromObject(VALID, makeApi())

        const heading    = /** @type {HTMLInputElement | null} */ (section.element.querySelector('input[name=heading]'))
        const label      = /** @type {HTMLInputElement | null} */ (section.element.querySelector('input[name=label]'))
        const date       = /** @type {HTMLInputElement | null} */ (section.element.querySelector('input[name=date]'))
        const buttonText = /** @type {HTMLInputElement | null} */ (section.element.querySelector('input[name=buttonText]'))
        const buttonHref = /** @type {HTMLInputElement | null} */ (section.element.querySelector('input[name=buttonHref]'))

        expect(heading?.value   ).toBe(VALID.heading)
        expect(label?.value     ).toBe(VALID.label)
        expect(date?.value      ).toBe(VALID.date)
        expect(buttonText?.value).toBe(VALID.buttonText)
        expect(buttonHref?.value).toBe(VALID.buttonHref)
    })

    test('typing in an input mutates the matching instance property', async () => {
        const {default: S} = await import('/sections/SplitBanner/Admin/split-banner-section.js')
        const section = S.createEmpty(makeApi())
        const input   = /** @type {HTMLInputElement} */ (section.element.querySelector('input[name=heading]'))

        input.value = 'New Heading'
        input.dispatchEvent(new Event('input'))

        expect(section.heading).toBe('New Heading')
    })

    test('spinner visible after pick; gone after load event', async () => {
        let _v = 0
        vi.doMock('/js/admin/uploads/upload-picker.js', () => ({
            default: class { open () { return Promise.resolve(MOCK_UPLOAD) } },
        }))
        const mod = await import(`/sections/SplitBanner/Admin/split-banner-section.js?v=${++_v}`)
        const S   = mod.default

        const section = S.createEmpty(makeApi())
        document.body.append(section.element)

        const events = /** @type {string[]} */ ([])
        section.element.addEventListener('imageloading', () => events.push('imageloading'), {capture: true})
        section.element.addEventListener('imageloaded',  () => events.push('imageloaded'),  {capture: true})

        section.element.querySelector('.image-break-add').click()

        await vi.waitFor(() => expect(events).toContain('imageloading'))

        expect(section.element.querySelector('.img-spinner')).not.toBeNull()
        expect(events).not.toContain('imageloaded')

        await new Promise(resolve => requestAnimationFrame(resolve))
        await new Promise(resolve => requestAnimationFrame(resolve))

        section.element.querySelector('img')?.dispatchEvent(new Event('load'))

        expect(events).toContain('imageloaded')
        expect(section.element.querySelector('.img-spinner')).toBeNull()
    })

})
