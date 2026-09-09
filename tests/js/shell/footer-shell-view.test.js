import {describe, test, expect, beforeEach, vi} from 'vitest'
import FooterShellView                          from '/shell/Footer/Admin/footer-shell-view.js'
import Loader                                   from '/js/admin/loader.js'
import Logger                                   from '/js/core/logger.js'
import Notifier                                 from '/js/admin/notifier.js'

describe('FooterShellView', () => {

    /** @type {any} */
    let api
    /** @type {Loader} */
    let loader
    /** @type {Notifier} */
    let notifier

    beforeEach(() => {
        document.body.replaceChildren()
        api = {
            getShell:      vi.fn().mockResolvedValue({columns: [], logoUploadId: null}),
            putShell:      vi.fn().mockResolvedValue(undefined),
            ensureVariant: vi.fn().mockResolvedValue('/uploads/1/480x160-cover.webp'),
        }
        loader = new Loader()
        vi.spyOn(Logger.prototype, 'info').mockImplementation(() => {})
        vi.spyOn(Logger.prototype, 'error').mockImplementation(() => {})
        notifier = new Notifier(new Logger())
    })

    const flush = async () => { await new Promise(resolve => setTimeout(resolve, 0)) }

    /** @returns {Promise<FooterShellView>} */
    const newView = async () => {
        const view = new FooterShellView(api, loader, notifier)
        await flush()
        return view
    }

    const findButton = (/** @type {Element} */ root, /** @type {string} */ text) =>
        /** @type {HTMLButtonElement} */ ([...root.querySelectorAll('button')].find(b => b.textContent === text))

    test('loads from getShell("footer") on construction', async () => {
        await newView()
        expect(api.getShell).toHaveBeenCalledWith('footer')
    })

    test('renders one column card per loaded column, each with its items', async () => {
        api.getShell.mockResolvedValue({
            columns: [
                {
                    heading: 'SOCIAL',
                    items: [
                        {kind: 'link', label: 'Instagram', href: '/ig'},
                        {kind: 'text', content: 'Prague / London'},
                        {kind: 'newsletter'},
                    ],
                },
            ],
            logoUploadId: null,
        })
        const view = await newView()

        expect(view.element.querySelectorAll('.footer-column-edit')).toHaveLength(1)
        expect(view.element.querySelectorAll('.carousel-item')).toHaveLength(3)
    })

    test('link and text items expose labeled fields; newsletter items expose none', async () => {
        api.getShell.mockResolvedValue({
            columns: [{
                heading: 'SOCIAL',
                items: [
                    {kind: 'link', label: 'Instagram', href: '/ig'},
                    {kind: 'text', content: 'Prague / London'},
                    {kind: 'newsletter'},
                ],
            }],
            logoUploadId: null,
        })
        const view = await newView()
        const items = view.element.querySelectorAll('.carousel-item')

        expect(items[0].querySelectorAll('label')).toHaveLength(2)
        expect(items[0].querySelector('label')?.textContent).toContain('Label')
        expect(items[1].querySelectorAll('label')).toHaveLength(1)
        expect(items[1].querySelector('label')?.textContent).toContain('Text')
        expect(items[2].querySelectorAll('label')).toHaveLength(0)
    })

    test('columns list is a fixed-width horizontal strip, add-column button sized like a column', async () => {
        api.getShell.mockResolvedValue({columns: [{heading: 'SOCIAL', items: []}], logoUploadId: null})
        const view = await newView()

        const list = /** @type {HTMLElement} */ (view.element.querySelector('.carousel-items'))
        expect(list).not.toBeNull()
        const addButton = /** @type {HTMLElement} */ (list.querySelector(':scope > .carousel-add'))
        expect(addButton).not.toBeNull()
        expect(addButton.textContent).toBe('+ Add column')
    })

    test('+ Add column appends an empty column', async () => {
        const view = await newView()
        findButton(view.element, '+ Add column').click()

        expect(view.element.querySelectorAll('.footer-column-edit')).toHaveLength(1)
        const status = /** @type {HTMLElement} */ (view.element.querySelector('.shell-save-status'))
        expect(status.textContent).toBe('Unsaved changes')
    })

    test('choosing a kind from the add-item select appends that item to the column', async () => {
        api.getShell.mockResolvedValue({columns: [{heading: 'SOCIAL', items: []}], logoUploadId: null})
        const view = await newView()

        const select = /** @type {HTMLSelectElement} */ (view.element.querySelector('.footer-add-item'))
        select.value = 'link'
        select.dispatchEvent(new Event('change', {bubbles: true}))

        const items = view.element.querySelectorAll('.carousel-item')
        expect(items).toHaveLength(1)
        expect(items[0].querySelector('.footer-item-kind')?.textContent).toBe('link')
    })

    test('editing a link item field, then Save persists the full column/item shape', async () => {
        api.getShell.mockResolvedValue({
            columns: [{heading: 'SOCIAL', items: [{kind: 'link', label: 'Instagram', href: '/ig'}]}],
            logoUploadId: null,
        })
        const view = await newView()

        const [labelInput, hrefInput] = /** @type {HTMLInputElement[]} */ (
            [...view.element.querySelectorAll('.carousel-item input')]
        )
        hrefInput.value = '/instagram'
        hrefInput.dispatchEvent(new Event('input', {bubbles: true}))

        findButton(view.element, 'Save').click()
        await flush()

        expect(api.putShell).toHaveBeenCalledWith('footer', {
            columns: [{heading: 'SOCIAL', items: [{kind: 'link', label: 'Instagram', href: '/instagram'}]}],
            logoUploadId: null,
        })
    })

    test('remove item button removes just that item', async () => {
        api.getShell.mockResolvedValue({
            columns: [{
                heading: 'SOCIAL',
                items: [{kind: 'link', label: 'A', href: '/a'}, {kind: 'link', label: 'B', href: '/b'}],
            }],
            logoUploadId: null,
        })
        const view = await newView()

        view.element.querySelector('.carousel-item .carousel-item-remove')?.dispatchEvent(
            new MouseEvent('click', {bubbles: true}),
        )

        expect(view.element.querySelectorAll('.carousel-item')).toHaveLength(1)
    })

    test('item move buttons reorder within the column; edges stay disabled', async () => {
        api.getShell.mockResolvedValue({
            columns: [{
                heading: 'SOCIAL',
                items: [{kind: 'link', label: 'A', href: '/a'}, {kind: 'link', label: 'B', href: '/b'}],
            }],
            logoUploadId: null,
        })
        const view = await newView()
        const items = () => view.element.querySelectorAll('.carousel-item')

        const firstMoveButtons = items()[0].querySelectorAll('.carousel-item-move')
        const lastMoveButtons  = items()[1].querySelectorAll('.carousel-item-move')
        expect(/** @type {HTMLButtonElement} */ (firstMoveButtons[0]).disabled).toBe(true)  // up, first item
        expect(/** @type {HTMLButtonElement} */ (lastMoveButtons[1]).disabled).toBe(true)   // down, last item

        const moveDownButton = /** @type {HTMLButtonElement} */ (firstMoveButtons[1])
        moveDownButton.click() // move A down

        const firstLabelInput = /** @type {HTMLInputElement} */ (items()[0].querySelector('input'))
        expect(firstLabelInput.value).toBe('B')
    })

    test('remove column button removes the whole column', async () => {
        api.getShell.mockResolvedValue({columns: [{heading: 'SOCIAL', items: []}], logoUploadId: null})
        const view = await newView()

        findButton(view.element, '×').click()

        expect(view.element.querySelectorAll('.footer-column-edit')).toHaveLength(0)
    })

    test('notifies on load failure', async () => {
        api.getShell.mockRejectedValue(new Error('boom'))
        const errorSpy = vi.spyOn(Notifier.prototype, 'error')

        await newView()

        expect(errorSpy).toHaveBeenCalled()
    })

    test('empty logo shows the add-logo button; a loaded one shows the preview', async () => {
        api.getShell.mockResolvedValue({columns: [], logoUploadId: null})
        const empty = await newView()
        expect(/** @type {HTMLElement} */ (empty.element.querySelector('.shell-logo-add')).hidden).toBe(false)
        expect(/** @type {HTMLElement} */ (empty.element.querySelector('.shell-logo-picked')).hidden).toBe(true)

        api.getShell.mockResolvedValue({columns: [], logoUploadId: 3})
        const loaded = await newView()
        expect(/** @type {HTMLElement} */ (loaded.element.querySelector('.shell-logo-add')).hidden).toBe(true)
        expect(/** @type {HTMLElement} */ (loaded.element.querySelector('.shell-logo-picked')).hidden).toBe(false)
        const img = /** @type {HTMLImageElement | null} */ (loaded.element.querySelector('.shell-logo-picked img'))
        expect(img?.src).toContain('/uploads/3/thumb-200x200.webp')
    })

})
