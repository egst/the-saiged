<?php declare(strict_types = 1);

namespace TheSaiged\Sections;

interface Section {

    static function type (): string;

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): static;

    /** @return array<string, mixed> Inverse of fromArray; the shape it expects in $data. */
    function toArray (): array;

    /** @return list<string> Asset filenames relative to the section folder. */
    static function cssAssets (): array;

    /** @return list<string> Asset filenames relative to the section folder. */
    static function jsAssets (): array;

    function render (): string;

}
