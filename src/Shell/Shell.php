<?php declare(strict_types = 1);

namespace TheSaiged\Shell;

/**
 * A singleton piece of site-wide chrome — exactly one instance per type,
 * shared by every page (Header, Footer). Mirrors Sections\Section, with
 * one addition: default(), because a shell has no "add" step in the admin —
 * it must render correctly before anyone has ever saved one.
 */
interface Shell {

    static function type (): string;

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): static;

    /** Fallback used when no row exists yet for this type. */
    static function default (): static;

    /** @return array<string, mixed> Inverse of fromArray. */
    function toArray (): array;

    /** @return list<string> Asset filenames relative to the shell's folder. */
    static function cssAssets (): array;

    /** @return list<string> Asset filenames relative to the shell's folder. */
    static function jsAssets (): array;

    function render (): string;

}
