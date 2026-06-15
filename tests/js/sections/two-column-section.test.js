import {describe, test, expect} from 'vitest'
import TwoColumnSection         from '/sections/TwoColumn/Admin/two-column-section.js'

/**
 * Mirrors PHP TwoColumnTest plus admin-only concerns (element, input
 * events). The `/sections` alias matches the browser path so admin code
 * imports identically here and in production.
 */
describe('TwoColumnSection', () => {

    test('static type is "two-column"', () => {
        expect(TwoColumnSection.type()).toBe('two-column')
    })

    test('fromObject builds with valid data', () => {
        const section = TwoColumnSection.fromObject({
            heading:    'H',
            body:       'B',
            buttonText: 'Click',
            buttonHref: '/contact',
        })

        expect(section.heading   ).toBe('H')
        expect(section.body      ).toBe('B')
        expect(section.buttonText).toBe('Click')
        expect(section.buttonHref).toBe('/contact')
    })

    test.each([
        ['missing heading',     {body: 'b', buttonText: '', buttonHref: ''}],
        ['missing body',        {heading: 'h', buttonText: '', buttonHref: ''}],
        ['missing buttonText',  {heading: 'h', body: 'b', buttonHref: ''}],
        ['missing buttonHref',  {heading: 'h', body: 'b', buttonText: ''}],
        ['non-string heading',  {heading: 1, body: 'b', buttonText: '', buttonHref: ''}],
        ['null',                null],
        ['empty object',        {}],
    ])('fromObject throws on invalid shape: %s', (_label, input) => {
        expect(() => TwoColumnSection.fromObject(input)).toThrow()
    })

    test('toObject roundtrips through fromObject', () => {
        const original = new TwoColumnSection('H', 'B', 'Click', '/contact')
        const clone    = TwoColumnSection.fromObject(original.toObject())

        expect(clone.heading   ).toBe(original.heading)
        expect(clone.body      ).toBe(original.body)
        expect(clone.buttonText).toBe(original.buttonText)
        expect(clone.buttonHref).toBe(original.buttonHref)
    })

    test('createEmpty yields an instance with empty strings', () => {
        const empty = TwoColumnSection.createEmpty()

        expect(empty.heading   ).toBe('')
        expect(empty.body      ).toBe('')
        expect(empty.buttonText).toBe('')
        expect(empty.buttonHref).toBe('')
    })

    test('element exposes inputs pre-filled with data', () => {
        const section = new TwoColumnSection('H', 'B', 'Click', '/contact')

        const heading    = /** @type {HTMLInputElement | null}    */ (section.element.querySelector('input[name=heading]'))
        const body       = /** @type {HTMLTextAreaElement | null} */ (section.element.querySelector('textarea[name=body]'))
        const buttonText = /** @type {HTMLInputElement | null}    */ (section.element.querySelector('input[name=buttonText]'))
        const buttonHref = /** @type {HTMLInputElement | null}    */ (section.element.querySelector('input[name=buttonHref]'))

        expect(heading?.value   ).toBe('H')
        expect(body?.value      ).toBe('B')
        expect(buttonText?.value).toBe('Click')
        expect(buttonHref?.value).toBe('/contact')
    })

    test('typing in an input mutates the matching instance property', () => {
        const section    = new TwoColumnSection('H', 'B', '', '')
        const buttonText = /** @type {HTMLInputElement} */ (section.element.querySelector('input[name=buttonText]'))

        buttonText.value = 'Learn more'
        buttonText.dispatchEvent(new Event('input'))

        expect(section.buttonText).toBe('Learn more')
    })

})
