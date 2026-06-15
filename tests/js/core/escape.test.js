import {describe, test, expect} from 'vitest'
import {escape}                 from '/js/core/escape.js'

describe('escape', () => {

    test('escapes ampersand', () => {
        expect(escape('a & b')).toBe('a &amp; b')
    })

    test('escapes all html-significant chars', () => {
        expect(escape('<>"\'&')).toBe('&lt;&gt;&quot;&#39;&amp;')
    })

    test('passes through safe text', () => {
        expect(escape('hello world')).toBe('hello world')
    })

    test('coerces non-strings', () => {
        expect(escape(42)).toBe('42')
        expect(escape(null)).toBe('null')
    })

})
