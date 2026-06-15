/** @type {Record<string, string>} */
const HTML_ENTITIES = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
}

/**
 * Escape a value for safe insertion into HTML text content / attribute values.
 * Use for any user-controlled string interpolated into innerHTML.
 *
 * @param {unknown} value
 * @returns {string}
 */
export const escape = value => String(value).replace(/[&<>"']/g, char => HTML_ENTITIES[char] ?? char)
