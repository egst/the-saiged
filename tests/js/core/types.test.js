import {describe, test, expect} from 'vitest'
import {isObject}              from '/js/core/types.js'

describe('isObject', () => {

    test.each([
        ['plain object',  {},                  true],
        ['object w/ keys', {a: 1},             true],
        ['array (yes — typeof is object)', [1, 2], true],
        ['null',          null,                false],
        ['undefined',     undefined,           false],
        ['string',        'hello',             false],
        ['number',        42,                  false],
        ['boolean',       true,                false],
    ])('%s → %s', (_label, value, expected) => {
        expect(isObject(value)).toBe(expected)
    })

})
