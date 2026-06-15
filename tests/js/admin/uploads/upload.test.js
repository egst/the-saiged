import {describe, test, expect} from 'vitest'
import Upload                    from '/js/admin/uploads/upload.js'

describe('Upload.fromObject', () => {

    /** A reusable valid wire payload — individual tests mutate one field. */
    const valid = () => ({
        id:          42,
        filename:    'photo.jpg',
        mime:        'image/jpeg',
        kind:        'image',
        size:        1234,
        width:       800,
        height:      600,
        uploadedAt:  '2026-06-16 12:00:00',
        originalUrl: '/uploads/42/original.jpg',
        thumbUrl:    '/uploads/42/thumb-200x200.webp',
    })

    test('builds from a valid wire payload', () => {
        const upload = Upload.fromObject(valid())

        expect(upload.id         ).toBe(42)
        expect(upload.filename   ).toBe('photo.jpg')
        expect(upload.mime       ).toBe('image/jpeg')
        expect(upload.kind       ).toBe('image')
        expect(upload.size       ).toBe(1234)
        expect(upload.width      ).toBe(800)
        expect(upload.height     ).toBe(600)
        expect(upload.uploadedAt ).toBe('2026-06-16 12:00:00')
        expect(upload.originalUrl).toBe('/uploads/42/original.jpg')
        expect(upload.thumbUrl   ).toBe('/uploads/42/thumb-200x200.webp')
    })

    test('accepts null width / height (legitimate for videos)', () => {
        const upload = Upload.fromObject({...valid(), kind: 'video', width: null, height: null})

        expect(upload.width).toBeNull()
        expect(upload.height).toBeNull()
    })

    test('accepts null thumbUrl (videos have no thumb)', () => {
        const upload = Upload.fromObject({...valid(), kind: 'video', thumbUrl: null})

        expect(upload.thumbUrl).toBeNull()
    })

    test.each([
        ['null',                  null],
        ['array',                 []],
        ['non-number id',         {...({}), id: '42'}],
        ['non-string filename',   {...({}), filename: 123}],
        ['non-string mime',       {...({}), mime: 1}],
        ['non-string kind',       {...({}), kind: 0}],
        ['non-number size',       {...({}), size: '1'}],
        ['non-number width',      {...({}), width: 'big'}],
        ['non-number height',     {...({}), height: 'tall'}],
        ['non-string uploadedAt', {...({}), uploadedAt: 0}],
        ['non-string originalUrl', {...({}), originalUrl: false}],
        ['non-string thumbUrl',   {...({}), thumbUrl: 0}],
    ])('throws on invalid shape: %s', (_label, override) => {
        // Each row replaces ONE field on a valid payload to isolate the
        // failure mode; for null/array rows we pass the override as the
        // whole input.
        const input = override === null || Array.isArray(override)
            ? override
            : {...valid(), ...override}

        expect(() => Upload.fromObject(input)).toThrow(/Invalid Upload shape/)
    })

})
