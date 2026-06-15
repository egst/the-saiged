import PageLink from '/js/admin/sidebar/page-link.js'

/**
 * @import Router      from '/js/core/router.js'
 * @import PageSummary from '/js/admin/pages/page-summary.js'
 */

/**
 * Builds a nested directory tree from a flat list of pages by splitting
 * each page's path on `/`. The final path segment is the page's leaf;
 * preceding segments form directory groups.
 *
 *   /about              → about
 *   /work/project-a     → work/ → project-a
 *   /work/project-b     → work/ → project-b
 *
 * Within each directory, entries (subdirs + pages) are sorted alphabetically.
 *
 * Renders as <details>/<summary> for directories and PageLink for leaves.
 *
 * setActive() marks the link whose href matches the current path active,
 * auto-expands its ancestor directories (so the user sees where they are
 * on first navigation), and tags those ancestors with `.has-active` so
 * CSS can style the path-to-current-page (bold ancestors; accent
 * background when the ancestor is collapsed and the active page is
 * therefore hidden under it).
 */
export default class PageTree {

    /** @type {Router} */
    #router
    /** @type {HTMLElement} */
    #element
    /** @type {PageLink[]} */
    #links = []
    /** @type {Map<PageLink, HTMLDetailsElement[]>} */
    #ancestors = new Map()
    /** @type {HTMLDetailsElement[]} */
    #dirs = []

    /**
     * @param {PageSummary[]} pages
     * @param {Router}        router
     */
    constructor (pages, router) {
        this.#router  = router
        this.#element = document.createElement('div')
        this.#element.className = 'page-tree'

        const root = this.#buildTree(pages)
        this.#renderNode(root, this.#element, [], '')
    }

    /** @returns {HTMLElement} */
    get element () {
        return this.#element
    }

    /** @returns {PageLink[]} */
    get links () {
        return this.#links
    }

    /**
     * Snapshot of which dirs are currently expanded, keyed by their full
     * slash-joined path. Used by Sidebar to preserve expanded state
     * across rebuilds (after save / create / delete / copy).
     *
     * @returns {Set<string>}
     */
    openPaths () {
        const set = new Set()
        for (const dir of this.#dirs)
            if (dir.open && dir.dataset.path !== undefined)
                set.add(dir.dataset.path)
        return set
    }

    /**
     * Re-open dirs whose paths appear in the given set. Called by Sidebar
     * after a rebuild to restore user-expanded state. setActive() runs
     * afterwards and additionally opens the ancestors of the active page
     * — both effects combine.
     *
     * @param {Iterable<string>} paths
     */
    restoreOpen (paths) {
        const set = new Set(paths)
        for (const dir of this.#dirs)
            if (dir.dataset.path !== undefined && set.has(dir.dataset.path))
                dir.open = true
    }

    /**
     * Marks the link whose href matches currentPath as active. Ancestor
     * directories are auto-expanded so the active page is visible, and
     * tagged with `.has-active` so CSS can style the path (bold while
     * open, accent background when collapsed).
     *
     * @param {string} currentPath
     */
    setActive (currentPath) {
        for (const dir of this.#dirs)
            dir.classList.remove('has-active')

        for (const link of this.#links) {
            const active = link.href === currentPath
            link.setActive(active)
            if (!active)
                continue
            for (const dir of this.#ancestors.get(link) ?? []) {
                dir.open = true
                dir.classList.add('has-active')
            }
        }
    }

    /**
     * @typedef {{
     *     dirs:  Map<string, TreeNode>,
     *     pages: PageSummary[],
     * }} TreeNode
     *
     * @param {PageSummary[]} pages
     * @returns {TreeNode}
     */
    #buildTree (pages) {
        /** @type {TreeNode} */
        const root = {dirs: new Map(), pages: []}
        for (const page of pages) {
            const parts = page.path.split('/').filter(part => part !== '')
            let cursor = root
            for (let i = 0; i < parts.length - 1; i++) {
                const segment = parts[i]
                let next = cursor.dirs.get(segment)
                if (next === undefined) {
                    next = {dirs: new Map(), pages: []}
                    cursor.dirs.set(segment, next)
                }
                cursor = next
            }
            cursor.pages.push(page)
        }
        return root
    }

    /**
     * @param {TreeNode}                node
     * @param {HTMLElement}             container
     * @param {HTMLDetailsElement[]}    ancestorChain   ancestors of entries we're about to render
     * @param {string}                  parentPath      slash-joined path of `node` itself
     */
    #renderNode (node, container, ancestorChain, parentPath) {
        const dirEntries = [...node.dirs.entries()].sort(([a], [b]) => a.localeCompare(b))
        for (const [name, child] of dirEntries) {
            const details = document.createElement('details')
            details.className    = 'page-tree-dir'
            details.dataset.path = parentPath === '' ? name : `${parentPath}/${name}`
            // Collapsed by default; setActive() expands the ancestors of the
            // currently active page, and Sidebar may additionally restore
            // user-expanded state from before a rebuild.
            this.#dirs.push(details)

            const summary = document.createElement('summary')
            summary.textContent = name
            details.append(summary)

            const inner = document.createElement('div')
            inner.className = 'page-tree-children'
            this.#renderNode(child, inner, [...ancestorChain, details], details.dataset.path)
            details.append(inner)

            container.append(details)
        }

        const pageEntries = [...node.pages].sort((a, b) => a.title.localeCompare(b.title))
        for (const page of pageEntries) {
            const link = new PageLink(page, this.#router)
            this.#links.push(link)
            this.#ancestors.set(link, ancestorChain)
            container.append(link.element)
        }
    }

}
