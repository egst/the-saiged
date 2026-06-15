import {describe, test, expect} from 'vitest'
import TagListSection            from '/sections/TagList/Admin/tag-list-section.js'

describe('TagListSection', () => {

    test('static type is "tag-list"', () => {
        expect(TagListSection.type()).toBe('tag-list')
    })

    test('fromObject builds with valid data', () => {
        const section = TagListSection.fromObject({
            heading: 'Market Access',
            body:    'Through selected relationships.',
            tags:    'Post-War, Contemporary, Emerging',
        })

        expect(section.heading).toBe('Market Access')
        expect(section.body   ).toBe('Through selected relationships.')
        expect(section.tags   ).toBe('Post-War, Contemporary, Emerging')
    })

    test.each([
        ['null',            null],
        ['missing heading', {body: 'b', tags: 't'}],
        ['missing body',    {heading: 'h', tags: 't'}],
        ['missing tags',    {heading: 'h', body: 'b'}],
        ['non-string tags', {heading: 'h', body: 'b', tags: 123}],
    ])('fromObject throws on invalid shape: %s', (_label, input) => {
        expect(() => TagListSection.fromObject(input)).toThrow()
    })

    test('toObject roundtrips through fromObject', () => {
        const original = new TagListSection('H', 'B', 'A, B')
        const clone    = TagListSection.fromObject(original.toObject())

        expect(clone.heading).toBe(original.heading)
        expect(clone.tags   ).toBe(original.tags)
    })

    test('createEmpty yields empty strings', () => {
        const empty = TagListSection.createEmpty()

        expect(empty.heading).toBe('')
        expect(empty.body   ).toBe('')
        expect(empty.tags   ).toBe('')
    })

    test('element exposes inputs pre-filled', () => {
        const section = new TagListSection('H', 'B', 'A, B')

        const heading = /** @type {HTMLInputElement | null}    */ (section.element.querySelector('input[name=heading]'))
        const body    = /** @type {HTMLTextAreaElement | null} */ (section.element.querySelector('textarea[name=body]'))
        const tags    = /** @type {HTMLInputElement | null}    */ (section.element.querySelector('input[name=tags]'))

        expect(heading?.value).toBe('H')
        expect(body?.value   ).toBe('B')
        expect(tags?.value   ).toBe('A, B')
    })

    test('typing in tags input mutates the instance property', () => {
        const section = TagListSection.createEmpty()
        const input   = /** @type {HTMLInputElement} */ (section.element.querySelector('input[name=tags]'))

        input.value = 'Contemporary, Emerging'
        input.dispatchEvent(new Event('input'))

        expect(section.tags).toBe('Contemporary, Emerging')
    })

})
