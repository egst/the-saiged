<?php declare(strict_types = 1);

namespace TheSaiged\Sections\CookiePreferences;

use TheSaiged\Sections\Section;

/**
 * Fixed-content cookie preferences panel: explains the site's one
 * necessary cookie and its one optional (analytics) cookie, and exposes
 * buttons that let a visitor grant, decline, or withdraw consent. No
 * admin-editable fields — the copy and the necessary/optional split are
 * intentionally hardcoded.
 *
 * Server-rendered markup always starts in the "undecided" shape (Accept
 * and Reject visible, no status line, Withdraw hidden) since the actual
 * consent choice lives in a cookie this class never reads — Sections stay
 * request-unaware, same as every other section. The co-located
 * cookie-preferences.js corrects the DOM to match the real stored choice
 * on load, and keeps it in sync via cookie-consent.js's
 * 'cookieconsentchange' event (shared with the site-wide banner).
 */
final readonly class CookiePreferencesSection implements Section {

    static function type (): string {
        return 'cookie-preferences';
    }

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): static {
        return new self();
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return [];
    }

    function render (): string {
        return <<<HTML
            <section class="cookie-preferences" data-cookie-preferences>
                <h2 class="cookie-preferences-heading">Cookie Preferences</h2>
                <div class="cookie-preferences-body">
                    <p>
                        We use cookies to run this website and, with your consent, to understand how
                        it's used. You can change your choice at any time on this page.
                    </p>
                    <h3 class="cookie-preferences-subheading">Strictly necessary</h3>
                    <p>
                        One cookie remembers the choice you make below. It's required for that choice
                        to persist and isn't itself covered by the consent it records.
                    </p>
                    <h3 class="cookie-preferences-subheading">Optional — analytics</h3>
                    <p>
                        With your consent, Google Analytics helps us understand how the site is used:
                        pages visited, time spent, approximate location and device type, and how
                        visitors found the site. Without consent, Google Analytics still runs, but
                        only in a cookieless mode that doesn't identify you or your device.
                    </p>
                </div>
                <p class="cookie-preferences-status" data-cookie-status hidden></p>
                <div class="cookie-preferences-actions">
                    <button type="button" class="cookie-preferences-button" data-cookie-choice="accepted">Accept</button>
                    <button type="button" class="cookie-preferences-button cookie-preferences-button--secondary" data-cookie-choice="rejected">Reject</button>
                    <button type="button" class="cookie-preferences-button cookie-preferences-button--secondary" data-cookie-choice="withdraw" hidden>Withdraw consent</button>
                </div>
            </section>
            HTML;
    }

    /** @return list<string> */
    static function cssAssets (): array {
        return ['style.css'];
    }

    /** @return list<string> */
    static function jsAssets (): array {
        return ['cookie-preferences.js'];
    }

}
