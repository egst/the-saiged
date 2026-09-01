import overlay from '/js/overlay.js'

document.addEventListener('click', e => {
    const link = e.target instanceof Element ? e.target.closest('[data-overlay]') : null
    if (!(link instanceof HTMLAnchorElement))
        return
    e.preventDefault()
    overlay.open(link.href)
})
