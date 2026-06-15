import {describe, test, expect, beforeEach} from 'vitest'
import ContactListSection                   from '/sections/ContactList/Admin/contact-list-section.js'

const ITEM = {heading: 'Studio', body: 'Email us at studio@example.com', note: 'Prague'}

beforeEach(() => {
    document.body.replaceChildren()
})

describe('ContactListSection', () => {

    test('static type is "contact-list"', () => {
        expect(ContactListSection.type()).toBe('contact-list')
    })

    test('fromObject builds with valid data', () => {
        const section = ContactListSection.fromObject({heading: 'Contact Us', items: [ITEM]})

        expect(section.heading         ).toBe('Contact Us')
        expect(section.items           ).toHaveLength(1)
        expect(section.items[0].heading).toBe('Studio')
        expect(section.items[0].body   ).toBe('Email us at studio@example.com')
        expect(section.items[0].note   ).toBe('Prague')
    })

    test.each([
        ['null',                    null],
        ['no heading or items',     {}],
        ['missing heading',         {items: []}],
        ['items not array',         {heading: 'H', items: 'x'}],
        ['item missing note',       {heading: 'H', items: [{heading: 'h', body: 'b'}]}],
        ['item missing body',       {heading: 'H', items: [{heading: 'h', note: ''}]}],
        ['item missing heading',    {heading: 'H', items: [{body: 'b', note: ''}]}],
        ['item heading non-string', {heading: 'H', items: [{heading: 1, body: 'b', note: ''}]}],
    ])('fromObject throws on invalid shape: %s', (_label, input) => {
        expect(() => ContactListSection.fromObject(input)).toThrow()
    })

    test('toObject roundtrips through fromObject', () => {
        const original = new ContactListSection('Contact Us', [ITEM])
        const clone    = ContactListSection.fromObject(original.toObject())

        expect(clone.heading         ).toBe('Contact Us')
        expect(clone.items[0].heading).toBe(ITEM.heading)
        expect(clone.items[0].body   ).toBe(ITEM.body)
        expect(clone.items[0].note   ).toBe(ITEM.note)
    })

    test('createEmpty yields empty heading and items', () => {
        const empty = ContactListSection.createEmpty()

        expect(empty.heading).toBe('')
        expect(empty.items  ).toHaveLength(0)
    })

    test('element exposes heading input pre-filled', () => {
        const section      = new ContactListSection('Contact Us', [])
        const headingInput = /** @type {HTMLInputElement | null} */ (section.element.querySelector('input[name=heading]'))

        expect(headingInput?.value).toBe('Contact Us')
    })

    test('element exposes item inputs pre-filled with data', () => {
        const section = new ContactListSection('H', [ITEM])

        const cards   = section.element.querySelectorAll('.contact-list-editor-item')
        const heading = /** @type {HTMLInputElement | null}    */ (cards[0].querySelector('input[name=heading]'))
        const body    = /** @type {HTMLTextAreaElement | null} */ (cards[0].querySelector('textarea[name=body]'))
        const note    = /** @type {HTMLInputElement | null}    */ (cards[0].querySelector('input[name=note]'))

        expect(heading?.value).toBe(ITEM.heading)
        expect(body?.value   ).toBe(ITEM.body)
        expect(note?.value   ).toBe(ITEM.note)
    })

    test('typing in heading input mutates section heading', () => {
        const section = new ContactListSection('', [])
        const input   = /** @type {HTMLInputElement} */ (section.element.querySelector('input[name=heading]'))

        input.value = 'Get in Touch'
        input.dispatchEvent(new Event('input'))

        expect(section.heading).toBe('Get in Touch')
    })

    test('typing in an item input mutates the matching item property', () => {
        const section = new ContactListSection('H', [{heading: '', body: '', note: ''}])
        const cards   = section.element.querySelectorAll('.contact-list-editor-item')
        const input   = /** @type {HTMLInputElement} */ (cards[0].querySelector('input[name=heading]'))

        input.value = 'Advisory'
        input.dispatchEvent(new Event('input'))

        expect(section.items[0].heading).toBe('Advisory')
    })

    test('add button appends a new empty item', () => {
        const section = ContactListSection.createEmpty()
        document.body.append(section.element)

        section.element.querySelector('.carousel-add').click()

        expect(section.items).toHaveLength(1)
        expect(section.items[0]).toEqual({heading: '', body: '', note: ''})
        expect(section.element.querySelectorAll('.contact-list-editor-item')).toHaveLength(1)
    })

    test('remove button deletes the item', () => {
        const section = new ContactListSection('H', [ITEM, {heading: 'B', body: 'b', note: ''}])
        document.body.append(section.element)

        /** @type {NodeListOf<HTMLButtonElement>} */
        const removes = section.element.querySelectorAll('.carousel-item-remove')
        removes[0].click()

        expect(section.items).toHaveLength(1)
        expect(section.items[0].heading).toBe('B')
    })

    test('move down swaps item with next', () => {
        const section = new ContactListSection('H', [
            {heading: 'A', body: '', note: ''},
            {heading: 'B', body: '', note: ''},
        ])
        document.body.append(section.element)

        const cards   = section.element.querySelectorAll('.contact-list-editor-item')
        const buttons = cards[0].querySelectorAll('.carousel-item-move')
        const downBtn = /** @type {HTMLButtonElement} */ (buttons[1])
        downBtn.click()

        expect(section.items[0].heading).toBe('B')
        expect(section.items[1].heading).toBe('A')
    })

    test('add button fires input event on the root element', () => {
        const section = ContactListSection.createEmpty()
        document.body.append(section.element)

        let fired = false
        section.element.addEventListener('input', () => { fired = true })

        section.element.querySelector('.carousel-add').click()

        expect(fired).toBe(true)
    })

})
