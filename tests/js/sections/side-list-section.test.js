import {describe, test, expect} from 'vitest'
import SideListSection          from '/sections/SideList/Admin/side-list-section.js'

const validData = () => ({
    heading:      'The Saiged Advisory',
    body:         'First paragraph.',
    linkText:     'Enquire',
    linkHref:     'mailto:hello@thesaiged.com',
    panelHeading: 'Integrated Approach',
    items: [
        {title: 'Studio',   body: 'Brand partnerships.'},
        {title: 'Advisory', body: 'Private art sales.'},
    ],
})

describe('SideListSection', () => {

    test('static type is "side-list"', () => {
        expect(SideListSection.type()).toBe('side-list')
    })

    test('fromObject builds with valid data', () => {
        const section = SideListSection.fromObject(validData())

        expect(section.heading     ).toBe('The Saiged Advisory')
        expect(section.panelHeading).toBe('Integrated Approach')
        expect(section.linkText    ).toBe('Enquire')
        expect(section.items       ).toHaveLength(2)
        expect(section.items[0].title).toBe('Studio')
    })

    test.each([
        ['null',                   null],
        ['missing heading',        {body: 'b', linkText: '', linkHref: '', panelHeading: 'P', items: []}],
        ['missing body',           {heading: 'h', linkText: '', linkHref: '', panelHeading: 'P', items: []}],
        ['missing panelHeading',   {heading: 'h', body: 'b', linkText: '', linkHref: '', items: []}],
        ['missing items',          {heading: 'h', body: 'b', linkText: '', linkHref: '', panelHeading: 'P'}],
        ['item missing title',     {heading: 'h', body: 'b', linkText: '', linkHref: '', panelHeading: 'P', items: [{body: 'b'}]}],
        ['item missing body',      {heading: 'h', body: 'b', linkText: '', linkHref: '', panelHeading: 'P', items: [{title: 't'}]}],
        ['non-string heading',     {heading: 1,   body: 'b', linkText: '', linkHref: '', panelHeading: 'P', items: []}],
    ])('fromObject throws on invalid shape: %s', (_label, input) => {
        expect(() => SideListSection.fromObject(input)).toThrow()
    })

    test('toObject roundtrips through fromObject', () => {
        const original = SideListSection.fromObject(validData())
        const clone    = SideListSection.fromObject(original.toObject())

        expect(clone.heading     ).toBe(original.heading)
        expect(clone.panelHeading).toBe(original.panelHeading)
        expect(clone.items       ).toHaveLength(original.items.length)
    })

    test('createEmpty yields an instance with empty strings and no items', () => {
        const empty = SideListSection.createEmpty()

        expect(empty.heading     ).toBe('')
        expect(empty.body        ).toBe('')
        expect(empty.linkText    ).toBe('')
        expect(empty.panelHeading).toBe('')
        expect(empty.items       ).toHaveLength(0)
    })

    test('element exposes inputs pre-filled with data', () => {
        const section = SideListSection.fromObject(validData())

        const heading      = /** @type {HTMLInputElement | null}    */ (section.element.querySelector('input[name=heading]'))
        const body         = /** @type {HTMLTextAreaElement | null} */ (section.element.querySelector('textarea[name=body]'))
        const linkText     = /** @type {HTMLInputElement | null}    */ (section.element.querySelector('input[name=linkText]'))
        const panelHeading = /** @type {HTMLInputElement | null}    */ (section.element.querySelector('input[name=panelHeading]'))

        expect(heading?.value     ).toBe('The Saiged Advisory')
        expect(body?.value        ).toBe('First paragraph.')
        expect(linkText?.value    ).toBe('Enquire')
        expect(panelHeading?.value).toBe('Integrated Approach')
    })

    test('typing in heading input mutates the instance property', () => {
        const section = SideListSection.createEmpty()
        const input   = /** @type {HTMLInputElement} */ (section.element.querySelector('input[name=heading]'))

        input.value = 'New heading'
        input.dispatchEvent(new Event('input'))

        expect(section.heading).toBe('New heading')
    })

    test('add item button appends an item and fires input event', () => {
        const section  = SideListSection.createEmpty()
        const addBtn   = /** @type {HTMLButtonElement} */ (section.element.querySelector('.carousel-add'))
        const events   = /** @type {Event[]} */ ([])
        section.element.addEventListener('input', e => events.push(e))

        addBtn.click()

        expect(section.items).toHaveLength(1)
        expect(events).toHaveLength(1)
    })

    test('remove button removes the item and fires input event', () => {
        const section  = new SideListSection('H', 'B', '', '', 'P', [{title: 'T', body: 'B'}])
        const events   = /** @type {Event[]} */ ([])
        section.element.addEventListener('input', e => events.push(e))

        const remove = /** @type {HTMLButtonElement} */ (section.element.querySelector('.carousel-item-remove'))
        remove.click()

        expect(section.items).toHaveLength(0)
        expect(events).toHaveLength(1)
    })

})
