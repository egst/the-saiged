<?php declare(strict_types = 1);

namespace TheSaiged\Shell\Footer;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Shell\Shell;

/**
 * Site footer: a free-form list of columns (heading + items), each item
 * a link, text, or newsletter signup — see FooterItem — plus one large
 * logo below the columns.
 */
final readonly class FooterShell implements Shell {

    const LOGO_WIDTH  = 480;
    const LOGO_HEIGHT = 160;

    /** @param list<FooterColumn> $columns */
    function __construct (
        public array $columns,
        public ?int  $logoUploadId,
    ) {}

    static function type (): string {
        return 'footer';
    }

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): static {
        $columns      = $data['columns'] ?? null;
        $logoUploadId = $data['logoUploadId'] ?? null;

        if (!is_array($columns) || !array_is_list($columns))
            throw new InvalidDataException('footer data', 'columns must be a list');
        if ($logoUploadId !== null && !is_int($logoUploadId))
            throw new InvalidDataException('footer data', 'logoUploadId must be an integer or null');

        $parsed = [];
        foreach ($columns as $raw) {
            if (!is_array($raw))
                throw new InvalidDataException('footer data', 'each column must be an object');
            $parsed[] = FooterColumn::fromArray($raw);
        }

        return new self(columns: $parsed, logoUploadId: $logoUploadId);
    }

    static function default (): static {
        return new self(columns: [], logoUploadId: null);
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return [
            'columns'      => array_map(fn (FooterColumn $column): array => $column->toArray(), $this->columns),
            'logoUploadId' => $this->logoUploadId,
        ];
    }

    function render (): string {
        $columns = '';
        foreach ($this->columns as $column)
            $columns .= $column->render() . "\n";

        $logo = $this->logoUploadId !== null
            ? '<img src="' . htmlspecialchars(self::logoUrl($this->logoUploadId), ENT_QUOTES) . '" alt="The Saiged">'
            : '';

        return <<<HTML
            <footer class="contact-footer">
                <div class="contact-footer-columns">
                    $columns
                </div>
                <div class="contact-footer-logo" aria-hidden="true">$logo</div>
            </footer>
            HTML;
    }

    static function logoUrl (int $uploadId): string {
        return sprintf('/uploads/%d/%dx%d-cover.webp', $uploadId, self::LOGO_WIDTH, self::LOGO_HEIGHT);
    }

    /** @return list<string> */
    static function cssAssets (): array {
        return ['style.css'];
    }

    /** @return list<string> */
    static function jsAssets (): array {
        return [];
    }

}
