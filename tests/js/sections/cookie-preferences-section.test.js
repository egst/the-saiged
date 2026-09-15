import {describe, test, expect}   from 'vitest'
import CookiePreferencesSection   from '/sections/CookiePreferences/Admin/cookie-preferences-section.js'

describe('CookiePreferencesSection', () => {

    test('static type is "cookie-preferences"', () => {
        expect(CookiePreferencesSection.type()).toBe('cookie-preferences')
    })

    test('fromObject ignores its input and always succeeds', () => {
        expect(CookiePreferencesSection.fromObject({anything: 'goes'})).toBeInstanceOf(CookiePreferencesSection)
        expect(CookiePreferencesSection.fromObject(null)).toBeInstanceOf(CookiePreferencesSection)
    })

    test('createEmpty yields an instance', () => {
        expect(CookiePreferencesSection.createEmpty()).toBeInstanceOf(CookiePreferencesSection)
    })

    test('toObject is empty — nothing to persist', () => {
        expect(new CookiePreferencesSection().toObject()).toEqual({})
    })

    test('element exposes a static, non-editable description', () => {
        const section = new CookiePreferencesSection()

        expect(section.element.querySelector('input')).toBe(null)
        expect(section.element.querySelector('textarea')).toBe(null)
        expect(section.element.textContent?.length).toBeGreaterThan(0)
    })

})
