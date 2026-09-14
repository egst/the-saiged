import {describe, test, expect, beforeEach, vi} from 'vitest'
import UsersView                                from '/js/admin/content/users-view.js'
import Loader                                   from '/js/admin/loader.js'
import Logger                                   from '/js/core/logger.js'
import Notifier                                 from '/js/admin/notifier.js'

/**
 * UsersView self-loads and acts immediately on add/remove — same things
 * worth pinning: API calls, DOM effects, notifier behavior. The
 * Admin-only visibility gate itself lives in Content (this view has no
 * opinion on who's allowed to see it).
 */
describe('UsersView', () => {

    /** @type {any} */
    let api
    /** @type {Loader} */
    let loader
    /** @type {Notifier} */
    let notifier

    beforeEach(() => {
        document.body.replaceChildren()
        api = {
            listAdmins:     vi.fn().mockResolvedValue([]),
            addAdmin:       vi.fn(),
            updateAdminRole: vi.fn(),
            removeAdmin:    vi.fn(),
        }
        loader = new Loader()
        vi.spyOn(Logger.prototype, 'info' ).mockImplementation(() => {})
        vi.spyOn(Logger.prototype, 'error').mockImplementation(() => {})
        notifier = new Notifier(new Logger())
    })

    const flush = () => new Promise(resolve => setTimeout(resolve, 0))

    test('loads and renders admins on construction', async () => {
        api.listAdmins.mockResolvedValue([
            {email: 'a@x.com', role: 'editor'},
            {email: 'b@x.com', role: 'admin'},
        ])

        const view = new UsersView(api, loader, notifier)
        await flush()

        expect(api.listAdmins).toHaveBeenCalledTimes(1)
        expect(view.element.textContent).toContain('a@x.com')
        expect(view.element.textContent).toContain('b@x.com')
    })

    test('add admin calls the API with the entered email and selected role, then reloads', async () => {
        const view = new UsersView(api, loader, notifier)
        await flush()

        const emailInput  = /** @type {HTMLInputElement} */ (view.element.querySelector('input[type="email"]'))
        const addButton   = /** @type {HTMLButtonElement} */ (
            [...view.element.querySelectorAll('button')].find(b => b.textContent === '+ Add user')
        )
        emailInput.value = 'new@x.com'
        api.addAdmin.mockResolvedValue(undefined)

        addButton.click()
        await flush()

        expect(api.addAdmin).toHaveBeenCalledWith({email: 'new@x.com', role: 'editor'})
        expect(api.listAdmins).toHaveBeenCalledTimes(2)  // initial load + reload after add
    })

    test('add admin with an empty email notifies an error and does not call the API', async () => {
        const view = new UsersView(api, loader, notifier)
        await flush()

        const addButton = /** @type {HTMLButtonElement} */ (
            [...view.element.querySelectorAll('button')].find(b => b.textContent === '+ Add user')
        )
        addButton.click()
        await flush()

        expect(api.addAdmin).not.toHaveBeenCalled()
    })

    test('remove admin confirms, calls the API, and reloads', async () => {
        api.listAdmins.mockResolvedValue([{email: 'a@x.com', role: 'editor'}])
        vi.stubGlobal('confirm', vi.fn().mockReturnValue(true))
        api.removeAdmin.mockResolvedValue(undefined)

        const view = new UsersView(api, loader, notifier)
        await flush()

        const removeButton = /** @type {HTMLButtonElement} */ (view.element.querySelector('.section-remove'))
        removeButton.click()
        await flush()

        expect(api.removeAdmin).toHaveBeenCalledWith('a@x.com')
        expect(api.listAdmins).toHaveBeenCalledTimes(2)
    })

    test('remove admin does nothing when the confirm dialog is declined', async () => {
        api.listAdmins.mockResolvedValue([{email: 'a@x.com', role: 'editor'}])
        vi.stubGlobal('confirm', vi.fn().mockReturnValue(false))

        const view = new UsersView(api, loader, notifier)
        await flush()

        const removeButton = /** @type {HTMLButtonElement} */ (view.element.querySelector('.section-remove'))
        removeButton.click()
        await flush()

        expect(api.removeAdmin).not.toHaveBeenCalled()
    })

    test('changing the role select calls updateAdminRole', async () => {
        api.listAdmins.mockResolvedValue([{email: 'a@x.com', role: 'editor'}])
        api.updateAdminRole.mockResolvedValue(undefined)

        const view = new UsersView(api, loader, notifier)
        await flush()

        const roleSelect = /** @type {HTMLSelectElement} */ (view.element.querySelector('.section-edit select'))
        roleSelect.value = 'admin'
        roleSelect.dispatchEvent(new Event('change'))
        await flush()

        expect(api.updateAdminRole).toHaveBeenCalledWith('a@x.com', 'admin')
    })

})
