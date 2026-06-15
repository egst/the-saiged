import {describe, test, expect, beforeEach, vi} from 'vitest'
import PageEditor                                from '/js/admin/content/page-editor.js'
import Page                                      from '/js/admin/pages/page.js'
import Loader                                    from '/js/admin/loader.js'
import Logger                                    from '/js/core/logger.js'
import Notifier                                  from '/js/admin/notifier.js'
import ArticleSection                            from '/sections/Article/Admin/article-section.js'

/**
 * PageEditor's dirty-tracking is the highest-value invariant to pin: the
 * Save button highlights and the "· unsaved" label show up iff the
 * serialized page differs from the last-saved snapshot. If
 * `#serialize` ever drifts from what `#save` actually PUTs to the API
 * (e.g., field added in payload but missed in serialize), dirty
 * detection breaks silently — these tests catch that.
 */
describe('PageEditor dirty tracking', () => {

    /** @type {Page} */
    let page
    /** @type {any} */
    let api
    /** @type {any} */
    let router
    /** @type {any} */
    let sectionFactory
    /** @type {Loader} */
    let loader
    /** @type {Notifier} */
    let notifier

    beforeEach(() => {
        document.body.replaceChildren()

        page = new Page(
            /* id        */ 7,
            /* path      */ 'about',
            /* title     */ 'About',
            /* metaDesc  */ null,
            /* status    */ 'draft',
            /* sections  */ [new ArticleSection('Content')],
        )

        api    = {
            putPage:      vi.fn().mockResolvedValue(undefined),
            copyPage:     vi.fn(),
            deletePage:   vi.fn(),
            listSections: vi.fn().mockResolvedValue([
                {type: 'article',   label: 'Article'},
                {type: 'statement', label: 'Statement'},
            ]),
        }
        router = {go: vi.fn()}
        sectionFactory = {
            createEmpty: vi.fn().mockResolvedValue(new ArticleSection('')),
        }
        loader   = new Loader()
        // Silence logger output and skip toast DOM noise — we don't
        // assert on toasts in this file.
        vi.spyOn(Logger.prototype,  'info' ).mockImplementation(() => {})
        vi.spyOn(Logger.prototype,  'error').mockImplementation(() => {})
        notifier = new Notifier(new Logger())
    })

    /** @returns {PageEditor} */
    const newEditor = () => new PageEditor(page, api, router, sectionFactory, loader, notifier)

    /** Element with the unsaved-changes label (hidden if not dirty). */
    const dirtyLabel = (/** @type {HTMLElement} */ root) => /** @type {HTMLElement} */ (root.querySelector('.page-status-dirty'))

    /** The save button. */
    const saveButton = (/** @type {HTMLElement} */ root) => /** @type {HTMLButtonElement} */ ([...root.querySelectorAll('.page-actions button')].find(b => b.textContent === 'Save'))

    test('initial state is clean — save button not primary, unsaved label hidden', () => {
        const editor = newEditor()

        expect(saveButton(editor.element).classList.contains('primary')).toBe(false)
        expect(dirtyLabel(editor.element).hidden                       ).toBe(true)
    })

    test('typing in the title metadata input marks dirty', () => {
        const editor = newEditor()

        const titleInput = /** @type {HTMLInputElement} */ (editor.element.querySelector('.page-meta-body input[name=title]'))
        titleInput.value = 'About — edited'
        titleInput.dispatchEvent(new Event('input', {bubbles: true}))

        expect(saveButton(editor.element).classList.contains('primary')).toBe(true)
        expect(dirtyLabel(editor.element).hidden                       ).toBe(false)
    })

    test('typing in a section input bubbles up and marks dirty', () => {
        const editor = newEditor()

        const sectionContent = /** @type {HTMLTextAreaElement} */ (editor.element.querySelector('.section-edit textarea[name=content]'))
        sectionContent.value = 'New Content'
        sectionContent.dispatchEvent(new Event('input', {bubbles: true}))

        expect(saveButton(editor.element).classList.contains('primary')).toBe(true)
    })

    test('typing back to the original value clears dirty', () => {
        const editor = newEditor()

        const titleInput = /** @type {HTMLInputElement} */ (editor.element.querySelector('.page-meta-body input[name=title]'))

        titleInput.value = 'About — edited'
        titleInput.dispatchEvent(new Event('input', {bubbles: true}))
        expect(dirtyLabel(editor.element).hidden).toBe(false)

        titleInput.value = 'About'
        titleInput.dispatchEvent(new Event('input', {bubbles: true}))
        expect(dirtyLabel(editor.element).hidden).toBe(true)
    })

    test('after a successful save, dirty clears and snapshot resets', async () => {
        const editor = newEditor()

        const titleInput = /** @type {HTMLInputElement} */ (editor.element.querySelector('.page-meta-body input[name=title]'))
        titleInput.value = 'About — edited'
        titleInput.dispatchEvent(new Event('input', {bubbles: true}))
        expect(saveButton(editor.element).classList.contains('primary')).toBe(true)

        saveButton(editor.element).click()
        // Wait for the async #save to complete (putPage is mocked → microtask).
        await vi.waitFor(() => expect(api.putPage).toHaveBeenCalled())

        expect(saveButton(editor.element).classList.contains('primary')).toBe(false)
        expect(dirtyLabel(editor.element).hidden                       ).toBe(true)
    })

    test('failed save keeps dirty state set', async () => {
        api.putPage = vi.fn().mockRejectedValue(new Error('Internal error'))
        const editor = newEditor()

        const titleInput = /** @type {HTMLInputElement} */ (editor.element.querySelector('.page-meta-body input[name=title]'))
        titleInput.value = 'About — edited'
        titleInput.dispatchEvent(new Event('input', {bubbles: true}))

        saveButton(editor.element).click()
        await vi.waitFor(() => expect(api.putPage).toHaveBeenCalled())

        expect(saveButton(editor.element).classList.contains('primary')).toBe(true)
        expect(dirtyLabel(editor.element).hidden                       ).toBe(false)
    })

    test('publish toggle marks dirty', () => {
        const editor = newEditor()
        // togglePublish opens a window.confirm — stub it to return true.
        vi.stubGlobal('confirm', vi.fn().mockReturnValue(true))

        const publishButton = /** @type {HTMLButtonElement} */ ([...editor.element.querySelectorAll('.page-actions button')].find(b => /Publish|Unpublish/.test(b.textContent ?? '')))
        publishButton.click()

        expect(saveButton(editor.element).classList.contains('primary')).toBe(true)
        expect(page.status                                             ).toBe('published')
    })

    test('canceling publish dialog leaves status + dirty unchanged', () => {
        const editor = newEditor()
        vi.stubGlobal('confirm', vi.fn().mockReturnValue(false))

        const publishButton = /** @type {HTMLButtonElement} */ ([...editor.element.querySelectorAll('.page-actions button')].find(b => /Publish|Unpublish/.test(b.textContent ?? '')))
        publishButton.click()

        expect(page.status                                             ).toBe('draft')
        expect(saveButton(editor.element).classList.contains('primary')).toBe(false)
    })

})

/**
 * The "+ Add section" menu is populated from the backend's section list
 * (api.listSections) rather than a hardcoded array — pin that the menu
 * reflects whatever the API returns and that clicking an item adds a
 * section of the matching type.
 */
describe('PageEditor add-section menu', () => {

    /** @type {Page} */
    let page
    /** @type {any} */
    let api
    /** @type {any} */
    let router
    /** @type {any} */
    let sectionFactory
    /** @type {Loader} */
    let loader
    /** @type {Notifier} */
    let notifier

    beforeEach(() => {
        document.body.replaceChildren()

        page = new Page(7, 'about', 'About', null, 'draft', [new ArticleSection('Content')])

        api = {
            putPage:      vi.fn().mockResolvedValue(undefined),
            listSections: vi.fn().mockResolvedValue([
                {type: 'article',   label: 'Article'},
                {type: 'statement', label: 'Statement'},
            ]),
        }
        router         = {go: vi.fn()}
        sectionFactory = {createEmpty: vi.fn().mockResolvedValue(new ArticleSection(''))}
        loader         = new Loader()
        vi.spyOn(Logger.prototype, 'info' ).mockImplementation(() => {})
        vi.spyOn(Logger.prototype, 'error').mockImplementation(() => {})
        notifier = new Notifier(new Logger())
    })

    /** @returns {PageEditor} */
    const newEditor = () => new PageEditor(page, api, router, sectionFactory, loader, notifier)

    /** @returns {HTMLButtonElement[]} */
    const pickerCards = () => /** @type {HTMLButtonElement[]} */ ([...document.body.querySelectorAll('.sp-card')])

    test('shows one card per section type in the picker modal', async () => {
        const editor = newEditor()
        const addButton = /** @type {HTMLButtonElement} */ (editor.element.querySelector('.add-section'))
        addButton.click()
        await vi.waitFor(() => expect(document.body.querySelector('.sp-overlay')).toBeTruthy())

        expect(pickerCards()).toHaveLength(2)
        expect(pickerCards().map(c => c.querySelector('.sp-label')?.textContent)).toEqual(['Article', 'Statement'])
    })

    test('clicking a picker card adds a section of the matching type', async () => {
        const editor = newEditor()
        const addButton = /** @type {HTMLButtonElement} */ (editor.element.querySelector('.add-section'))
        addButton.click()
        await vi.waitFor(() => expect(document.body.querySelector('.sp-overlay')).toBeTruthy())

        const card = /** @type {HTMLButtonElement} */ (document.body.querySelector('.sp-card[data-type="statement"]'))
        card.click()

        await vi.waitFor(() => expect(sectionFactory.createEmpty).toHaveBeenCalledWith('statement'))
    })

    test('reports an error and does not open the picker when the API fails', async () => {
        api.listSections = vi.fn().mockRejectedValue(new Error('boom'))
        const errorSpy   = vi.spyOn(notifier, 'error')

        newEditor()

        await vi.waitFor(() => expect(errorSpy).toHaveBeenCalledWith('boom', expect.anything()))
        expect(document.body.querySelector('.sp-overlay')).toBeNull()
        expect(pickerCards()).toHaveLength(0)
    })

})
