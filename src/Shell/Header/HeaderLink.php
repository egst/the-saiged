<?php declare(strict_types = 1);

namespace TheSaiged\Shell\Header;

use TheSaiged\Core\InvalidDataException;

final readonly class HeaderLink {

    function __construct (
        public string $label,
        public string $href,
    ) {}

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): self {
        $label = $data['label'] ?? null;
        $href  = $data['href']  ?? null;

        if (!is_string($label) || !is_string($href))
            throw new InvalidDataException('header link', 'label and href must be strings');

        return new self(label: $label, href: $href);
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return ['label' => $this->label, 'href' => $this->href];
    }

}
