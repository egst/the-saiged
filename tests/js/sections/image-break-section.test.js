import {describe, test, expect, beforeEach, vi} from 'vitest'
import ImageBreakSection                        from '/sections/ImageBreak/Admin/image-break-section.js'

describe('ImageBreakSection', () => {

    /** Mock Api with just the methods this section calls. @type {any} */
    let api

    beforeEach(() => {
        document.body.replaceChildren()
        api = {
            listUploads:   vi.fn().mockResolvedValue([]),
            ensureVariant: vi.fn().mockResolvedValue('/uploads/1/1920x1080-cover.webp'),
        }
    })

    test('static type is "image-break"', () => {
        expect(ImageBreakSection.type()).toBe('image-break')
    })

    test('fromObject builds with valid data', () => {
        const section = ImageBreakSection.fromObject({uploadId: 5, caption: 'Studio'}, api)

        expect(section.uploadId).toBe(5)
        expect(section.caption ).toBe('Studio')
    })

    test.each([
        ['missing uploadId', {caption: 'c'}],
        ['missing caption',  {uploadId: 5}],
        ['non-number id',    {uploadId: '5', caption: 'c'}],
        ['non-string cap',   {uploadId: 5, caption: 9}],
        ['null',             null],
        ['empty object',     {}],
    ])('fromObject throws on invalid shape: %s', (_label, input) => {
        expect(() => ImageBreakSection.fromObject(input, api)).toThrow()
    })

    test('toObject roundtrips through fromObject', () => {
        const original = ImageBreakSection.fromObject({uploadId: 5, caption: 'Studio'}, api)
        const clone    = ImageBreakSection.fromObject(original.toObject(), api)

        expect(clone.uploadId).toBe(original.uploadId)
        expect(clone.caption ).toBe(original.caption)
    })

    test('createEmpty yields null uploadId and empty caption', () => {
        const empty = ImageBreakSection.createEmpty(api)

        expect(empty.uploadId).toBeNull()
        expect(empty.caption ).toBe('')
    })

    test('element exposes caption input pre-filled', () => {
        const section = ImageBreakSection.fromObject({uploadId: 5, caption: 'Studio'}, api)

        const captionInput = /** @type {HTMLInputElement | null} */ (section.element.querySelector('input[name=caption]'))

        expect(captionInput?.value).toBe('Studio')
    })

    test('typing in caption input mutates the caption property', () => {
        const section      = ImageBreakSection.createEmpty(api)
        const captionInput = /** @type {HTMLInputElement} */ (section.element.querySelector('input[name=caption]'))

        captionInput.value = 'Cultural Studio'
        captionInput.dispatchEvent(new Event('input'))

        expect(section.caption).toBe('Cultural Studio')
    })

    test('shows add button and hides preview when no image', () => {
        const section = ImageBreakSection.createEmpty(api)

        const addButton     = /** @type {HTMLElement | null} */ (section.element.querySelector('.image-break-add'))
        const pickedPreview = /** @type {HTMLElement | null} */ (section.element.querySelector('.image-break-picked'))

        expect(addButton?.hidden   ).toBe(false)
        expect(pickedPreview?.hidden).toBe(true)
    })

    test('hides add button and shows preview with thumbnail when image is set', () => {
        const section = ImageBreakSection.fromObject({uploadId: 5, caption: 'Studio'}, api)

        const addButton     = /** @type {HTMLElement | null} */ (section.element.querySelector('.image-break-add'))
        const pickedPreview = /** @type {HTMLElement | null} */ (section.element.querySelector('.image-break-picked'))
        const img           = /** @type {HTMLImageElement | null} */ (pickedPreview?.querySelector('img') ?? null)

        expect(addButton?.hidden   ).toBe(true)
        expect(pickedPreview?.hidden).toBe(false)
        expect(img?.src            ).toContain('/uploads/5/thumb-200x200.webp')
    })

})
