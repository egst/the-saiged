import {describe, test, expect} from 'vitest'
import PageSummary               from '/js/admin/pages/page-summary.js'

describe('PageSummary.fromObject', () => {

    test('builds from a valid wire payload', () => {
        const summary = PageSummary.fromObject({
            id:     7,
            path:   'about',
            title:  'About',
            status: 'draft',
        })

        expect(summary.id    ).toBe(7)
        expect(summary.path  ).toBe('about')
        expect(summary.title ).toBe('About')
        expect(summary.status).toBe('draft')
    })

    test.each([
        ['null',                  null],
        ['array (not a plain object)', []],
        ['missing id',            {path: 'a', title: 'A', status: 'draft'}],
        ['non-number id',         {id: '1', path: 'a', title: 'A', status: 'draft'}],
        ['missing path',          {id: 1, title: 'A', status: 'draft'}],
        ['non-string path',       {id: 1, path: 2, title: 'A', status: 'draft'}],
        ['missing title',         {id: 1, path: 'a', status: 'draft'}],
        ['non-string title',      {id: 1, path: 'a', title: 2, status: 'draft'}],
        ['missing status',        {id: 1, path: 'a', title: 'A'}],
        ['non-string status',     {id: 1, path: 'a', title: 'A', status: 2}],
    ])('throws on invalid shape: %s', (_label, input) => {
        expect(() => PageSummary.fromObject(input)).toThrow(/Invalid PageSummary shape/)
    })

})
