import {defineConfig} from 'vitest/config';
import path           from 'node:path';

/**
 * Source lives in public/js/* and is loaded by the browser with absolute paths
 * like '/js/core/escape.js'. Vitest doesn't see the web server, so we alias
 * '/js' to the source folder so test imports can mirror what runs in browser.
 */
export default defineConfig({
    // Disable Vite's special handling of public/ — our JS source lives there
    // because nginx serves it directly, but for tests we need to import from it.
    publicDir: false,
    test: {
        include:     ['tests/js/**/*.test.js'],
        // happy-dom gives DOM + window globals to tests. Lighter than
        // jsdom and fully sufficient for our vanilla-JS components, which
        // touch document.createElement / events / etc.
        environment: 'happy-dom',
    },
    resolve: {
        alias: {
            '/js':       path.resolve(import.meta.dirname, 'public/js'),
            // Section admin JS lives next to PHP for namespace symmetry; nginx
            // serves it at /sections/* in the browser, mirror that here.
            '/sections': path.resolve(import.meta.dirname, 'src/Sections'),
        },
    },
});
