/**
 * Type guard for "plain object" (not null, not primitive). After this check
 * TS narrows the value to `Record<string, unknown>` so property access is
 * allowed and each property is `unknown` (still narrowable by `typeof`).
 *
 * The cast to `Record<string, unknown>` lives here rather than at every use
 * site because TS' built-in `object` type doesn't allow property access by
 * design — `Record<string, unknown>` does, and gives strict `unknown` per
 * property (vs `any`).
 *
 * @param {unknown} x
 * @returns {x is Record<string, unknown>}
 */
export const isObject = x => typeof x === 'object' && x !== null
