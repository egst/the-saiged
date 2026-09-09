/*
 * Public-side header behavior: toggles .is-scrolled past a small
 * threshold (this is what actually paints the white background + text
 * color flip + hairline — see style.css), and drives the scroll-progress
 * bar's width from how far down the page the reader has scrolled.
 */
const SCROLLED_THRESHOLD_PX = 8

const header       = document.querySelector('.site-header')
const progressBar  = document.querySelector('.scroll-progress-bar')

if (header !== null) {
    let ticking = false

    const update = () => {
        ticking = false

        header.classList.toggle('is-scrolled', window.scrollY > SCROLLED_THRESHOLD_PX)

        if (progressBar === null)
            return
        const scrollable = document.documentElement.scrollHeight - window.innerHeight
        const progress    = scrollable > 0 ? Math.min(100, Math.max(0, (window.scrollY / scrollable) * 100)) : 0
        progressBar.style.width = `${progress}%`
    }

    window.addEventListener('scroll', () => {
        if (ticking)
            return
        ticking = true
        requestAnimationFrame(update)
    }, {passive: true})

    update()
}
