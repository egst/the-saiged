import {describe, test, expect} from 'vitest'
import ThreeColumnSection        from '/sections/ThreeColumn/Admin/three-column-section.js'

const validData = () => ({
    heading: 'Services',
    items: [
        {title: 'Brand Strategy',        body: 'Cultural positioning.'},
        {title: 'Editorial Production',  body: 'Commissioned interviews.'},
        {title: 'Partnership Development', body: 'Strategic introductions.'},
    ],
})

describe('ThreeColumnSection', () => {

    test('static type is "three-column"', () => {
        expect(ThreeColumnSection.type()).toBe('three-column')
    })

    test('fromObject builds with valid data', () => {
        const section = ThreeColumnSection.fromObject(validData())

        expect(section.heading       ).toBe('Services')
        expect(section.items         ).toHaveLength(3)
        expect(section.items[0].title).toBe('Brand Strategy')
        expect(section.items[2].title).toBe('Partnership Development')
    })

    test.each([
        ['null',              null],
        ['missing heading',   {items: []}],
        ['missing items',     {heading: 'H'}],
        ['item missing title', {heading: 'H', items: [{body: 'b'}]}],
        ['item missing body',  {heading: 'H', items: [{title: 't'}]}],
        ['non-string heading', {heading: 1, items: []}],
    ])('fromObject throws on invalid shape: %s', (_label, input) => {
        expect(() => ThreeColumnSection.fromObject(input)).toThrow()
    })

    test('toObject roundtrips through fromObject', () => {
        const original = ThreeColumnSection.fromObject(validData())
        const clone    = ThreeColumnSection.fromObject(original.toObject())

        expect(clone.heading       ).toBe(original.heading)
        expect(clone.items         ).toHaveLength(original.items.length)
        expect(clone.items[0].title).toBe(original.items[0].title)
    })

    test('createEmpty yields heading "" and no items', () => {
        const empty = ThreeColumnSection.createEmpty()

        expect(empty.heading).toBe('')
        expect(empty.items  ).toHaveLength(0)
    })

    test('element exposes heading input pre-filled', () => {
        const section = ThreeColumnSection.fromObject(validData())
        const input   = /** @type {HTMLInputElement | null} */ (section.element.querySelector('input[name=heading]'))

        expect(input?.value).toBe('Services')
    })

    test('typing in heading input mutates the instance property', () => {
        const section = ThreeColumnSection.createEmpty()
        const input   = /** @type {HTMLInputElement} */ (section.element.querySelector('input[name=heading]'))

        input.value = 'Updated'
        input.dispatchEvent(new Event('input'))

        expect(section.heading).toBe('Updated')
    })

    test('add column button appends item and fires input event', () => {
        const section = ThreeColumnSection.createEmpty()
        const addBtn  = /** @type {HTMLButtonElement} */ (section.element.querySelector('.carousel-add'))
        const events  = /** @type {Event[]} */ ([])
        section.element.addEventListener('input', e => events.push(e))

        addBtn.click()

        expect(section.items).toHaveLength(1)
        expect(events        ).toHaveLength(1)
    })

    test('remove button removes the item and fires input event', () => {
        const section = new ThreeColumnSection('H', [{title: 'T', body: 'B'}])
        const events  = /** @type {Event[]} */ ([])
        section.element.addEventListener('input', e => events.push(e))

        const remove = /** @type {HTMLButtonElement} */ (section.element.querySelector('.carousel-item-remove'))
        remove.click()

        expect(section.items).toHaveLength(0)
        expect(events        ).toHaveLength(1)
    })

})
