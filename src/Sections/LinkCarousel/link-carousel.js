/*
 * Public-side link carousel behavior — auto-advances slides every 4 s
 * (matching the CSS progress-bar animation), updates the shared content
 * area (eyebrow, title, button) from data attributes on each slide div,
 * and loops back to the first slide.
 *
 * Supports multiple independent carousels on a single page.
 */
const SLIDE_INTERVAL_MS = 4000

const initCarousel = root => {
    const slides   = [...root.querySelectorAll('.link-carousel-slide')]
    const bars     = [...root.querySelectorAll('.link-carousel-progress-line')]
    const eyebrow  = root.querySelector('.link-carousel-eyebrow')
    const title    = root.querySelector('.link-carousel-title')
    const button   = root.querySelector('.link-carousel-button')
    const total    = slides.length

    if (total === 0 || eyebrow === null || title === null || button === null)
        return

    let current = 0

    const updateContent = slide => {
        eyebrow.textContent  = slide.dataset.eyebrow   ?? ''
        title.textContent    = slide.dataset.title     ?? ''
        button.textContent   = slide.dataset.buttonText ?? ''
        button.href          = slide.dataset.buttonHref ?? ''
        button.hidden        = (slide.dataset.buttonText ?? '') === ''
    }

    const advance = index => {
        current = (index + total) % total

        for (const slide of slides)
            slide.classList.remove('is-active')
        slides[current].classList.add('is-active')

        for (const bar of bars) {
            bar.classList.remove('is-active')
            // force reflow so the animation restarts cleanly on loop-back
            void bar.offsetWidth
        }
        bars[current].classList.add('is-active')

        updateContent(slides[current])
    }

    advance(0)
    setInterval(() => advance(current + 1), SLIDE_INTERVAL_MS)
}

for (const carousel of document.querySelectorAll('.link-carousel'))
    initCarousel(carousel)
