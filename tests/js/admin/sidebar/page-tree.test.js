import {describe, test, expect, beforeEach}     from 'vitest'
import PageTree                                  from '/js/admin/sidebar/page-tree.js'
import PageSummary                               from '/js/admin/pages/page-summary.js'

/** Minimal Router duck. PageLink only calls `.go(href)` on click. */
const router = /** @type {any} */ ({go: () => {}})

/** @param {Array<{id: number, path: string, title: string, status?: string}>} rows */
const pages = rows => rows.map(r => new PageSummary(r.id, r.path, r.title, r.status ?? 'draft'))

describe('PageTree', () => {

    /** @type {HTMLElement} */
    let host

    beforeEach(() => {
        host = document.createElement('div')
        document.body.replaceChildren(host)
    })

    test('renders homepage (empty path) at top level alongside other pages', () => {
        const tree = new PageTree(pages([
            {id: 1, path: '',      title: 'Home'},
            {id: 2, path: 'about', title: 'About'},
        ]), router)
        host.append(tree.element)

        expect(host.querySelectorAll('.page-tree-dir')).toHaveLength(0)
        expect(host.querySelectorAll('.page-tree > a')).toHaveLength(2)
    })

    test('renders flat top-level pages with no directories', () => {
        const tree = new PageTree(pages([
            {id: 1, path: 'about', title: 'About'},
            {id: 2, path: 'hello', title: 'Hello'},
        ]), router)
        host.append(tree.element)

        expect(host.querySelectorAll('.page-tree-dir')).toHaveLength(0)
        expect(host.querySelectorAll('.page-tree > a')).toHaveLength(2)
    })

    test('builds a directory for shared path prefixes', () => {
        const tree = new PageTree(pages([
            {id: 1, path: 'work/project-a', title: 'Project A'},
            {id: 2, path: 'work/project-b', title: 'Project B'},
        ]), router)
        host.append(tree.element)

        const dirs = host.querySelectorAll('.page-tree-dir')
        expect(dirs).toHaveLength(1)
        expect(dirs[0].querySelector('summary')?.textContent).toBe('work')
        expect(dirs[0].querySelectorAll('.page-tree-children > a')).toHaveLength(2)
    })

    test('nests directories for deep paths', () => {
        const tree = new PageTree(pages([
            {id: 1, path: 'work/foo/proj-x', title: 'Project X'},
        ]), router)
        host.append(tree.element)

        // Two nested dir levels: work → foo → page
        const work = host.querySelector('.page-tree > .page-tree-dir')
        const foo  = work?.querySelector(':scope > .page-tree-children > .page-tree-dir')
        const link = foo?.querySelector(':scope > .page-tree-children > a')

        expect(work?.querySelector(':scope > summary')?.textContent).toBe('work')
        expect(foo?.querySelector( ':scope > summary')?.textContent).toBe('foo')
        expect(link?.textContent                                  ).toContain('Project X')
    })

    test('sorts directories and pages alphabetically within each level', () => {
        const tree = new PageTree(pages([
            {id: 1, path: 'banana',         title: 'Banana'},
            {id: 2, path: 'apple',          title: 'Apple'},
            {id: 3, path: 'work/zulu',      title: 'Zulu'},
            {id: 4, path: 'work/alpha',     title: 'Alpha'},
        ]), router)
        host.append(tree.element)

        // Top level: dirs sorted before pages by name. Within pages: alphabetical by title.
        // (Implementation places dirs first, then pages — that's still the design contract.)
        const topNames = [...tree.element.children].map(node => {
            if (node.matches('.page-tree-dir'))
                return node.querySelector('summary')?.textContent
            return node.textContent?.trim().split('\n')[0].trim()
        })
        expect(topNames).toEqual(['work', 'Apple', 'Banana'])

        const nestedNames = [...host.querySelectorAll('.page-tree-children > a')].map(a =>
            a.textContent?.trim().split('\n')[0].trim()
        )
        expect(nestedNames).toEqual(['Alpha', 'Zulu'])
    })

    test('each <details> carries the full dir path on dataset', () => {
        const tree = new PageTree(pages([
            {id: 1, path: 'work/foo/proj-x', title: 'Project X'},
        ]), router)
        host.append(tree.element)

        const work = host.querySelector('.page-tree > .page-tree-dir')
        const foo  = work?.querySelector(':scope > .page-tree-children > .page-tree-dir')

        expect(/** @type {HTMLDetailsElement} */ (work).dataset.path).toBe('work')
        expect(/** @type {HTMLDetailsElement} */ (foo) .dataset.path).toBe('work/foo')
    })

    test('all directories are collapsed by default', () => {
        const tree = new PageTree(pages([
            {id: 1, path: 'work/project-a', title: 'Project A'},
            {id: 2, path: 'about',          title: 'About'},
        ]), router)
        host.append(tree.element)

        for (const dir of host.querySelectorAll('.page-tree-dir'))
            expect(/** @type {HTMLDetailsElement} */ (dir).open).toBe(false)
    })

    test('setActive marks the right link and expands its ancestor chain', () => {
        const tree = new PageTree(pages([
            {id: 1, path: 'work/project-a', title: 'Project A'},
            {id: 2, path: 'work/project-b', title: 'Project B'},
            {id: 3, path: 'about',          title: 'About'},
        ]), router)
        host.append(tree.element)

        tree.setActive('/admin/pages/1')

        const activeLink = host.querySelector('a.active')
        expect(activeLink?.getAttribute('href')).toBe('/admin/pages/1')

        const workDir = /** @type {HTMLDetailsElement} */ (host.querySelector('[data-path="work"]'))
        expect(workDir.open).toBe(true)
        expect(workDir.classList.contains('has-active')).toBe(true)
    })

    test('setActive does not mark unrelated dirs as has-active', () => {
        const tree = new PageTree(pages([
            {id: 1, path: 'work/project-a', title: 'Project A'},
            {id: 2, path: 'play/game-a',    title: 'Game A'},
        ]), router)
        host.append(tree.element)

        tree.setActive('/admin/pages/1')

        const playDir = /** @type {HTMLDetailsElement} */ (host.querySelector('[data-path="play"]'))
        expect(playDir.classList.contains('has-active')).toBe(false)
        expect(playDir.open).toBe(false)
    })

    test('setActive switching path moves has-active to the new ancestor chain', () => {
        const tree = new PageTree(pages([
            {id: 1, path: 'work/project-a', title: 'Project A'},
            {id: 2, path: 'play/game-a',    title: 'Game A'},
        ]), router)
        host.append(tree.element)

        tree.setActive('/admin/pages/1')
        tree.setActive('/admin/pages/2')

        const workDir = /** @type {HTMLDetailsElement} */ (host.querySelector('[data-path="work"]'))
        const playDir = /** @type {HTMLDetailsElement} */ (host.querySelector('[data-path="play"]'))
        expect(workDir.classList.contains('has-active')).toBe(false)
        expect(playDir.classList.contains('has-active')).toBe(true)
    })

    test('openPaths returns slash-joined paths of currently open dirs', () => {
        const tree = new PageTree(pages([
            {id: 1, path: 'work/foo/proj-x', title: 'Project X'},
            {id: 2, path: 'about',           title: 'About'},
        ]), router)
        host.append(tree.element)

        const work = /** @type {HTMLDetailsElement} */ (host.querySelector('[data-path="work"]'))
        const foo  = /** @type {HTMLDetailsElement} */ (host.querySelector('[data-path="work/foo"]'))
        work.open = true
        foo.open  = true

        expect(tree.openPaths()).toEqual(new Set(['work', 'work/foo']))
    })

    test('restoreOpen reopens dirs by path', () => {
        const tree = new PageTree(pages([
            {id: 1, path: 'work/foo/proj-x', title: 'Project X'},
            {id: 2, path: 'about',           title: 'About'},
        ]), router)
        host.append(tree.element)

        tree.restoreOpen(new Set(['work/foo']))

        const foo  = /** @type {HTMLDetailsElement} */ (host.querySelector('[data-path="work/foo"]'))
        const work = /** @type {HTMLDetailsElement} */ (host.querySelector('[data-path="work"]'))
        expect(foo.open ).toBe(true)
        expect(work.open).toBe(false) // not in the restore set
    })

})
