<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Sections;

use TheSaiged\Sections\CookiePreferences\CookiePreferencesSection;
use TheSaiged\Tests\TestCase;

final class CookiePreferencesTest extends TestCase {

    function testTypeReturnsCookiePreferences (): void {
        $this->assertSame('cookie-preferences', CookiePreferencesSection::type());
    }

    function testFromArrayIgnoresInputAndAlwaysSucceeds (): void {
        $section = CookiePreferencesSection::fromArray(['anything' => 'goes']);

        $this->assertInstanceOf(CookiePreferencesSection::class, $section);
    }

    function testToArrayIsEmpty (): void {
        $this->assertSame([], (new CookiePreferencesSection())->toArray());
    }

    function testRenderStartsInTheUndecidedShape (): void {
        $html = (new CookiePreferencesSection())->render();

        $this->assertStringContainsString('data-cookie-choice="accepted"', $html);
        $this->assertStringContainsString('data-cookie-choice="rejected"', $html);
        $this->assertMatchesRegularExpression(
            '/data-cookie-choice="withdraw"[^>]*\bhidden\b/',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/data-cookie-status[^>]*\bhidden\b/',
            $html,
        );
    }

    function testCssAndJsAssets (): void {
        $this->assertSame(['style.css'],              CookiePreferencesSection::cssAssets());
        $this->assertSame(['cookie-preferences.js'],  CookiePreferencesSection::jsAssets());
    }

}
