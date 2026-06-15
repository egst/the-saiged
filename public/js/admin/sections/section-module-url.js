/**
 * Derives the browser URL of a section's admin JS module from its type string.
 * Mirrors the kebab-case → PascalCase folder convention used by SectionFactory.
 *
 * @param {string} type  e.g. 'image-carousel'
 * @returns {string}     e.g. '/sections/ImageCarousel/Admin/image-carousel-section.js'
 */
export const sectionModuleUrl = type => {
    const folder = type.split('-').map(word => word[0].toUpperCase() + word.slice(1)).join('')
    const kebab  = folder.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase()
    return `/sections/${folder}/Admin/${kebab}-section.js`
}
