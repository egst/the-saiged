import {describe, test, expect, vi} from 'vitest'
import ArtistGridSection             from '/sections/ArtistGrid/Admin/artist-grid-section.js'

const makeApi = () => ({
    ensureVariant: vi.fn().mockResolvedValue(undefined),
})

const validData = () => ({
    heading: 'Artists Recently Represented',
    items: [
        {uploadId: 1,    name: 'Nicolas Party', birthYear: '1980'},
        {uploadId: null, name: 'Mark Rothko',   birthYear: '1903'},
    ],
})

describe('ArtistGridSection', () => {

    test('static type is "artist-grid"', () => {
        expect(ArtistGridSection.type()).toBe('artist-grid')
    })

    test('fromObject builds with valid data', () => {
        const section = ArtistGridSection.fromObject(validData(), makeApi())

        expect(section.heading       ).toBe('Artists Recently Represented')
        expect(section.items         ).toHaveLength(2)
        expect(section.items[0].name ).toBe('Nicolas Party')
        expect(section.items[1].uploadId).toBeNull()
    })

    test('fromObject derives thumbUrl from uploadId', () => {
        const section = ArtistGridSection.fromObject(validData(), makeApi())

        expect(section.items[0].thumbUrl).toBe('/uploads/1/thumb-200x200.webp')
        expect(section.items[1].thumbUrl).toBeNull()
    })

    test.each([
        ['null',                    null],
        ['missing heading',         {items: []}],
        ['missing items',           {heading: 'H'}],
        ['item missing name',       {heading: 'H', items: [{uploadId: 1, birthYear: '1980'}]}],
        ['item missing birthYear',  {heading: 'H', items: [{uploadId: 1, name: 'N'}]}],
        ['item bad uploadId',       {heading: 'H', items: [{uploadId: 'bad', name: 'N', birthYear: '1980'}]}],
    ])('fromObject throws on invalid shape: %s', (_label, input) => {
        expect(() => ArtistGridSection.fromObject(input, makeApi())).toThrow()
    })

    test('toObject roundtrips (strips thumbUrl)', () => {
        const original = ArtistGridSection.fromObject(validData(), makeApi())
        const obj      = original.toObject()

        expect(obj.heading        ).toBe('Artists Recently Represented')
        expect(obj.items[0]       ).not.toHaveProperty('thumbUrl')
        expect(obj.items[0].name  ).toBe('Nicolas Party')
        expect(obj.items[1].uploadId).toBeNull()
    })

    test('createEmpty yields heading "" and no items', () => {
        const empty = ArtistGridSection.createEmpty(makeApi())

        expect(empty.heading).toBe('')
        expect(empty.items  ).toHaveLength(0)
    })

    test('element exposes heading input pre-filled', () => {
        const section = ArtistGridSection.fromObject(validData(), makeApi())
        const input   = /** @type {HTMLInputElement | null} */ (section.element.querySelector('input[name=heading]'))

        expect(input?.value).toBe('Artists Recently Represented')
    })

    test('add artist button does not add item when picker is cancelled', () => {
        const api = {...makeApi(), openUploadPicker: vi.fn().mockResolvedValue(null)}
        const section = ArtistGridSection.createEmpty(makeApi())

        expect(section.items).toHaveLength(0)
    })

    test('remove button removes the artist and fires input event', () => {
        const section = new ArtistGridSection('H', [{uploadId: null, name: 'N', birthYear: '1980', thumbUrl: null}], makeApi())
        const events  = /** @type {Event[]} */ ([])
        section.element.addEventListener('input', e => events.push(e))

        const remove = /** @type {HTMLButtonElement} */ (section.element.querySelector('.carousel-item-remove'))
        remove.click()

        expect(section.items).toHaveLength(0)
        expect(events        ).toHaveLength(1)
    })

})
