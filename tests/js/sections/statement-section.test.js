import {describe, test, expect} from 'vitest'
import StatementSection         from '/sections/Statement/Admin/statement-section.js'

describe('StatementSection', () => {

    test('static type is "statement"', () => {
        expect(StatementSection.type()).toBe('statement')
    })

    test('fromObject builds with valid data', () => {
        const section = StatementSection.fromObject({
            heading:    'H',
            body:       'B',
            buttonText: 'Explore',
            buttonHref: '/services',
        })

        expect(section.heading   ).toBe('H')
        expect(section.body      ).toBe('B')
        expect(section.buttonText).toBe('Explore')
        expect(section.buttonHref).toBe('/services')
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
        expect(() => StatementSection.fromObject(input)).toThrow()
    })

    test('toObject roundtrips through fromObject', () => {
        const original = new StatementSection('H', 'B', 'Explore', '/services')
        const clone    = StatementSection.fromObject(original.toObject())

        expect(clone.heading   ).toBe(original.heading)
        expect(clone.body      ).toBe(original.body)
        expect(clone.buttonText).toBe(original.buttonText)
        expect(clone.buttonHref).toBe(original.buttonHref)
    })

    test('createEmpty yields an instance with empty strings', () => {
        const empty = StatementSection.createEmpty()

        expect(empty.heading   ).toBe('')
        expect(empty.body      ).toBe('')
        expect(empty.buttonText).toBe('')
        expect(empty.buttonHref).toBe('')
    })

    test('element exposes inputs pre-filled with data', () => {
        const section = new StatementSection('H', 'B', 'Explore', '/services')

        const heading    = /** @type {HTMLInputElement | null}    */ (section.element.querySelector('input[name=heading]'))
        const body       = /** @type {HTMLTextAreaElement | null} */ (section.element.querySelector('textarea[name=body]'))
        const buttonText = /** @type {HTMLInputElement | null}    */ (section.element.querySelector('input[name=buttonText]'))
        const buttonHref = /** @type {HTMLInputElement | null}    */ (section.element.querySelector('input[name=buttonHref]'))

        expect(heading?.value   ).toBe('H')
        expect(body?.value      ).toBe('B')
        expect(buttonText?.value).toBe('Explore')
        expect(buttonHref?.value).toBe('/services')
    })

    test('typing in an input mutates the matching instance property', () => {
        const section    = new StatementSection('H', 'B', '', '')
        const buttonText = /** @type {HTMLInputElement} */ (section.element.querySelector('input[name=buttonText]'))

        buttonText.value = 'Learn more'
        buttonText.dispatchEvent(new Event('input'))

        expect(section.buttonText).toBe('Learn more')
    })

})
