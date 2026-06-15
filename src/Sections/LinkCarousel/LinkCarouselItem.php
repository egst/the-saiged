<?php declare(strict_types = 1);

namespace TheSaiged\Sections\LinkCarousel;

use TheSaiged\Core\InvalidDataException;

final readonly class LinkCarouselItem {

    function __construct (
        public int    $uploadId,
        public string $eyebrow,
        public string $title,
        public string $buttonText,
        public string $buttonHref,
    ) {}

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): self {
        $uploadId   = $data['uploadId']   ?? null;
        $eyebrow    = $data['eyebrow']    ?? null;
        $title      = $data['title']      ?? null;
        $buttonText = $data['buttonText'] ?? null;
        $buttonHref = $data['buttonHref'] ?? null;

        if (!is_int($uploadId) || $uploadId <= 0)
            throw new InvalidDataException('link-carousel item', 'uploadId must be a positive integer');
        if (!is_string($eyebrow))
            throw new InvalidDataException('link-carousel item', 'eyebrow must be a string');
        if (!is_string($title))
            throw new InvalidDataException('link-carousel item', 'title must be a string');
        if (!is_string($buttonText))
            throw new InvalidDataException('link-carousel item', 'buttonText must be a string');
        if (!is_string($buttonHref))
            throw new InvalidDataException('link-carousel item', 'buttonHref must be a string');

        return new self(
            uploadId:   $uploadId,
            eyebrow:    $eyebrow,
            title:      $title,
            buttonText: $buttonText,
            buttonHref: $buttonHref,
        );
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return [
            'uploadId'   => $this->uploadId,
            'eyebrow'    => $this->eyebrow,
            'title'      => $this->title,
            'buttonText' => $this->buttonText,
            'buttonHref' => $this->buttonHref,
        ];
    }

}
