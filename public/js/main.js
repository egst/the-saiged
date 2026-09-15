import overlay from '/js/overlay.js'
import search  from '/js/search.js'
import '/js/cookie-banner.js'

document.addEventListener('click', e => {
    const target = e.target instanceof Element ? e.target : null

    const link = target?.closest('[data-overlay]') ?? null
    if (link instanceof HTMLAnchorElement) {
        e.preventDefault()
        overlay.open(link.href)
        return
    }

    // .header-search-bar is HeaderShell's own markup (see
    // HeaderShell::render()) — delegated here, same as [data-overlay]
    // above, rather than search.js querying for it itself, so search.js
    // stays unaware of where its trigger button happens to live in the DOM.
    if (target?.closest('.header-search-bar') !== null) {
        e.preventDefault()
        search.open()
    }
})
