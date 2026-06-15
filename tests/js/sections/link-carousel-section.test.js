import {describe, test, expect, beforeEach, vi} from 'vitest'
import LinkCarouselSection                      from '/sections/LinkCarousel/Admin/link-carousel-section.js'

describe('LinkCarouselSection', () => {

    /** Mock Api with just the methods this section calls. @type {any} */
    let api

    beforeEach(() => {
        document.body.replaceChildren()
        api = {
            listUploads:   vi.fn().mockResolvedValue([]),
            ensureVariant: vi.fn().mockResolvedValue('/uploads/1/1920x1080-cover.webp'),
        }
    })

    test('static type is "link-carousel"', () => {
        expect(LinkCarouselSection.type()).toBe('link-carousel')
    })

    test('fromObject builds with valid data', () => {
        const section = LinkCarouselSection.fromObject({
            items: [
                {uploadId: 1, eyebrow: 'Studio', title: 'Project A', buttonText: 'View', buttonHref: '/a'},
                {uploadId: 2, eyebrow: 'Work',   title: 'Project B', buttonText: '',     buttonHref: ''},
            ],
        }, api)

        expect(section.items).toHaveLength(2)
        expect(section.items[0].uploadId  ).toBe(1)
        expect(section.items[0].eyebrow   ).toBe('Studio')
        expect(section.items[0].thumbUrl  ).toBe('/uploads/1/thumb-200x200.webp')
    })

    test('fromObject accepts empty items list', () => {
        const section = LinkCarouselSection.fromObject({items: []}, api)
        expect(section.items).toHaveLength(0)
    })

    test.each([
        ['missing items',            {}],
        ['items not array',          {items: 'no'}],
        ['item missing uploadId',    {items: [{eyebrow: 'e', title: 't', buttonText: '', buttonHref: ''}]}],
        ['item missing eyebrow',     {items: [{uploadId: 1, title: 't', buttonText: '', buttonHref: ''}]}],
        ['item missing title',       {items: [{uploadId: 1, eyebrow: 'e', buttonText: '', buttonHref: ''}]}],
        ['item missing buttonText',  {items: [{uploadId: 1, eyebrow: 'e', title: 't', buttonHref: ''}]}],
        ['item missing buttonHref',  {items: [{uploadId: 1, eyebrow: 'e', title: 't', buttonText: ''}]}],
        ['null',                     null],
    ])('fromObject throws on invalid shape: %s', (_label, input) => {
        expect(() => LinkCarouselSection.fromObject(input, api)).toThrow()
    })

    test('toObject strips thumbUrl and keeps only persisted fields', () => {
        const section = LinkCarouselSection.fromObject({
            items: [{uploadId: 7, eyebrow: 'E', title: 'T', buttonText: 'B', buttonHref: '/x'}],
        }, api)

        const obj  = section.toObject()
        const item = /** @type {Record<string, unknown>} */ (obj.items[0])
        expect(item['thumbUrl']        ).toBeUndefined()
        expect(obj.items[0].uploadId  ).toBe(7)
        expect(obj.items[0].eyebrow   ).toBe('E')
        expect(obj.items[0].title     ).toBe('T')
        expect(obj.items[0].buttonText).toBe('B')
        expect(obj.items[0].buttonHref).toBe('/x')
    })

    test('toObject roundtrips through fromObject', () => {
        const original = LinkCarouselSection.fromObject({
            items: [{uploadId: 3, eyebrow: 'E', title: 'T', buttonText: 'B', buttonHref: '/b'}],
        }, api)
        const clone = LinkCarouselSection.fromObject(original.toObject(), api)

        expect(clone.items[0].uploadId  ).toBe(original.items[0].uploadId)
        expect(clone.items[0].eyebrow   ).toBe(original.items[0].eyebrow)
        expect(clone.items[0].title     ).toBe(original.items[0].title)
        expect(clone.items[0].buttonText).toBe(original.items[0].buttonText)
        expect(clone.items[0].buttonHref).toBe(original.items[0].buttonHref)
    })

    test('createEmpty yields empty items list', () => {
        const empty = LinkCarouselSection.createEmpty(api)
        expect(empty.items).toHaveLength(0)
    })

    test('element exposes inputs pre-filled with item data', () => {
        const section = LinkCarouselSection.fromObject({
            items: [{uploadId: 1, eyebrow: 'Studio', title: 'Project', buttonText: 'View', buttonHref: '/p'}],
        }, api)

        const eyebrow    = /** @type {HTMLInputElement | null} */ (section.element.querySelector('input[name=eyebrow]'))
        const title      = /** @type {HTMLInputElement | null} */ (section.element.querySelector('input[name=title]'))
        const buttonText = /** @type {HTMLInputElement | null} */ (section.element.querySelector('input[name=buttonText]'))
        const buttonHref = /** @type {HTMLInputElement | null} */ (section.element.querySelector('input[name=buttonHref]'))

        expect(eyebrow?.value   ).toBe('Studio')
        expect(title?.value     ).toBe('Project')
        expect(buttonText?.value).toBe('View')
        expect(buttonHref?.value).toBe('/p')
    })

    test('typing in an input mutates the matching item property', () => {
        const section    = LinkCarouselSection.fromObject({
            items: [{uploadId: 1, eyebrow: '', title: '', buttonText: '', buttonHref: ''}],
        }, api)
        const titleInput = /** @type {HTMLInputElement} */ (section.element.querySelector('input[name=title]'))

        titleInput.value = 'New Title'
        titleInput.dispatchEvent(new Event('input'))

        expect(section.items[0].title).toBe('New Title')
    })

})
