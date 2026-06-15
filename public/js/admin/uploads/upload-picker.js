/**
 * @import Api    from '/js/admin/api.js'
 * @import Upload from '/js/admin/uploads/upload.js'
 */

/**
 * Modal upload picker. Calls open() to show a grid of all uploads and
 * returns a promise that resolves with the picked Upload (or null if
 * the user closes the dialog without picking).
 *
 * Single-use per open() call — the modal is built fresh, mounted to
 * document.body, and removed when a choice is made or canceled. State
 * doesn't leak across uses.
 */
export default class UploadPicker {

    /** @type {Api} */
    #api

    /** @param {Api} api */
    constructor (api) {
        this.#api = api
    }

    /**
     * Open the picker and return the chosen upload, or null if canceled.
     *
     * @returns {Promise<Upload | null>}
     */
    async open () {
        const overlay = document.createElement('div')
        overlay.className = 'upload-picker-overlay'

        const modal = document.createElement('div')
        modal.className = 'upload-picker'

        const header = document.createElement('header')
        header.className = 'upload-picker-header'
        const title = document.createElement('h2')
        title.textContent = 'Pick an upload'
        const close = document.createElement('button')
        close.type        = 'button'
        close.className   = 'upload-picker-close'
        close.title       = 'Cancel'
        close.textContent = '×'
        header.append(title, close)

        const grid = document.createElement('div')
        grid.className = 'upload-picker-grid'
        grid.textContent = 'Loading…'

        modal.append(header, grid)
        overlay.append(modal)
        document.body.append(overlay)

        return new Promise((resolve) => {
            /** @param {Upload | null} value */
            const finish = (value) => {
                overlay.remove()
                resolve(value)
            }

            close.addEventListener('click', () => finish(null))
            overlay.addEventListener('click', event => {
                if (event.target === overlay)
                    finish(null)
            })

            this.#api.listUploads().then(uploads => {
                grid.replaceChildren()
                if (uploads.length === 0) {
                    const empty = document.createElement('p')
                    empty.className   = 'placeholder'
                    empty.textContent = 'No uploads yet. Upload some via the Uploads view first.'
                    grid.append(empty)
                    return
                }
                for (const upload of uploads.filter(u => u.kind === 'image'))
                    grid.append(this.#renderTile(upload, finish))
            }).catch(error => {
                grid.replaceChildren()
                const err = document.createElement('p')
                err.className   = 'placeholder error'
                err.textContent = `Failed to load uploads: ${error instanceof Error ? error.message : String(error)}`
                grid.append(err)
            })
        })
    }

    /**
     * @param {Upload} upload
     * @param {(value: Upload | null) => void} finish
     */
    #renderTile (upload, finish) {
        const tile = document.createElement('button')
        tile.type        = 'button'
        tile.className   = 'upload-picker-tile'
        tile.title       = upload.filename
        tile.addEventListener('click', () => finish(upload))

        if (upload.thumbUrl !== null) {
            const img = document.createElement('img')
            img.src         = upload.thumbUrl
            img.alt         = upload.filename
            img.loading     = 'lazy'
            tile.append(img)
        }
        const label = document.createElement('span')
        label.className   = 'upload-picker-tile-label'
        label.textContent = upload.filename
        tile.append(label)

        return tile
    }

}
