<?php declare(strict_types = 1);

namespace TheSaiged\Shell\Footer;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Shell\Shell;
use TheSaiged\Uploads\Upload;

/**
 * Site footer: a free-form list of columns (heading + items), each item
 * a link, text, or newsletter signup — see FooterItem — plus one large
 * logo below the columns.
 */
final readonly class FooterShell implements Shell {

    /**
     * Deliberately NOT a small fixed logo box like Header's — this variant
     * is generated at the logo's own real aspect ratio (~2.51:1, matching
     * the client's actual footer-logo.png), so ensureVariant's cover-crop
     * is a pure resize with zero cropping. The CSS treats the file as a
     * plain full-width block (width:100%, height:auto, no cropping at
     * all); sizing here just needs to be large enough not to look soft
     * when stretched to the footer's content width, not to match any
     * particular box shape.
     */
    const LOGO_WIDTH  = 1920;
    const LOGO_HEIGHT = 764;

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
            ? '<div class="contact-footer-logo" aria-hidden="true"><img src="'
                . htmlspecialchars(self::logoUrl($this->logoUploadId), ENT_QUOTES) . '" alt="The Saiged"></div>'
            : '';

        return <<<HTML
            <footer class="contact-footer">
                <div class="contact-footer-columns">
                    $columns
                </div>
                $logo
            </footer>
            HTML;
    }

    static function logoUrl (int $uploadId): string {
        return Upload::coverImageVariantUrlFor($uploadId, self::LOGO_WIDTH, self::LOGO_HEIGHT);
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
