/**
 * @import Api      from '/js/admin/api.js'
 * @import Loader   from '/js/admin/loader.js'
 * @import Notifier from '/js/admin/notifier.js'
 */

const ROLES = [
    {value: 'editor', label: 'Editor'},
    {value: 'admin',  label: 'Admin'},
]

/**
 * Admin-account management at /admin/site/users — Admin role only (see
 * Sidebar/Content, which both gate this behind the current user's own
 * role; the real enforcement is server-side, AdminGuard::admin on every
 * /api/admin/admins/* route).
 *
 * Acts immediately on add/change-role/remove (follows MediaView's
 * shape) — there's no draft state worth tracking.
 */
export default class UsersView {

    /** @type {Api} */
    #api
    /** @type {Loader} */
    #loader
    /** @type {Notifier} */
    #notifier

    /** @type {HTMLDivElement} */
    #element
    /** @type {HTMLDivElement} */
    #list
    /** @type {HTMLInputElement} */
    #emailInput
    /** @type {HTMLSelectElement} */
    #roleSelect

    /**
     * @param {Api}      api
     * @param {Loader}   loader
     * @param {Notifier} notifier
     */
    constructor (api, loader, notifier) {
        this.#api      = api
        this.#loader   = loader
        this.#notifier = notifier

        this.#list = document.createElement('div')
        this.#list.className = 'sections'

        this.#emailInput             = document.createElement('input')
        this.#emailInput.type        = 'email'
        this.#emailInput.className   = 'control-row-input'
        this.#emailInput.placeholder = 'name@example.com'

        this.#roleSelect = document.createElement('select')
        for (const role of ROLES) {
            const option = document.createElement('option')
            option.value       = role.value
            option.textContent = role.label
            this.#roleSelect.append(option)
        }

        this.#element = this.#build()
        void this.#load()
    }

    /** @returns {HTMLDivElement} */
    get element () {
        return this.#element
    }

    #build () {
        const root = document.createElement('div')
        root.className = 'page-editor'

        const summary = document.createElement('div')
        summary.className = 'page-summary'
        const title = document.createElement('h2')
        title.className   = 'page-summary-title'
        title.textContent = 'Users'
        const meta = document.createElement('p')
        meta.className   = 'page-meta'
        meta.textContent = 'Users sign in with Google — there are no passwords to manage here.'
        summary.append(title, meta)

        const group = document.createElement('div')
        group.className = 'field-group'
        const heading = document.createElement('h3')
        heading.className   = 'field-group-title'
        heading.textContent = 'Admin accounts'
        group.append(heading, this.#list, this.#buildAddRow())

        root.append(summary, group)
        return root
    }

    #buildAddRow () {
        const addButton = document.createElement('button')
        addButton.type        = 'button'
        addButton.className   = 'primary'
        addButton.textContent = '+ Add user'
        addButton.addEventListener('click', () => this.#addAdmin())

        const actions = document.createElement('div')
        actions.className = 'page-actions'
        actions.append(addButton)

        const row = document.createElement('div')
        row.className = 'control-row'
        row.append(this.#emailInput, this.#roleSelect, actions)

        const card = document.createElement('div')
        card.className = 'section-edit'
        card.append(row)
        return card
    }

    async #load () {
        const loading = this.#loader.start()
        try {
            const admins = await this.#api.listAdmins()
            this.#renderAdmins(admins)
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Failed to load admins'
            this.#notifier.error(message, error)
        } finally {
            loading.stop()
        }
    }

    /** @param {{email: string, role: string}[]} admins */
    #renderAdmins (admins) {
        this.#list.replaceChildren()
        for (const admin of admins)
            this.#list.append(this.#renderAdmin(admin))
    }

    /** @param {{email: string, role: string}} admin */
    #renderAdmin (admin) {
        const card = document.createElement('div')
        card.className = 'section-edit'

        const row = document.createElement('div')
        row.className = 'control-row'

        const label = document.createElement('span')
        label.className   = 'control-row-label'
        label.textContent = admin.email

        const roleSelect = document.createElement('select')
        for (const role of ROLES) {
            const option = document.createElement('option')
            option.value       = role.value
            option.textContent = role.label
            option.selected    = role.value === admin.role
            roleSelect.append(option)
        }
        roleSelect.addEventListener('change', () => this.#updateRole(admin.email, roleSelect.value))

        const removeButton = document.createElement('button')
        removeButton.type        = 'button'
        removeButton.className   = 'section-remove'
        removeButton.title       = 'Remove admin'
        removeButton.textContent = '×'
        removeButton.addEventListener('click', () => this.#removeAdmin(admin.email))

        row.append(label, roleSelect, removeButton)
        card.append(row)
        return card
    }

    async #addAdmin () {
        const email = this.#emailInput.value.trim()
        if (email === '') {
            this.#notifier.error('Enter an email address.')
            return
        }

        const loading = this.#loader.start()
        try {
            await this.#api.addAdmin({email, role: this.#roleSelect.value})
            this.#emailInput.value = ''
            await this.#load()
            this.#notifier.success('Admin added')
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Failed to add admin'
            this.#notifier.error(message, error)
        } finally {
            loading.stop()
        }
    }

    /**
     * @param {string} email
     * @param {string} role
     */
    async #updateRole (email, role) {
        const loading = this.#loader.start()
        try {
            await this.#api.updateAdminRole(email, role)
            this.#notifier.success('Role updated')
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Failed to update role'
            this.#notifier.error(message, error)
            await this.#load()
        } finally {
            loading.stop()
        }
    }

    /** @param {string} email */
    async #removeAdmin (email) {
        if (!window.confirm(`Remove admin ${email}?`))
            return

        const loading = this.#loader.start()
        try {
            await this.#api.removeAdmin(email)
            await this.#load()
            this.#notifier.success('Admin removed')
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Failed to remove admin'
            this.#notifier.error(message, error)
        } finally {
            loading.stop()
        }
    }

}
