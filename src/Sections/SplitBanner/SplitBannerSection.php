<?php declare(strict_types = 1);

namespace TheSaiged\Sections\SplitBanner;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\Section;

final readonly class SplitBannerSection implements Section {

    private const VARIANT_WIDTH  = 960;
    private const VARIANT_HEIGHT = 800;

    function __construct (
        public ?int   $uploadId,
        public string $label,
        public string $heading,
        public string $date,
        public string $buttonText,
        public string $buttonHref,
    ) {}

    static function type (): string {
        return 'split-banner';
    }

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): static {
        $uploadId   = $data['uploadId']   ?? null;
        $label      = $data['label']      ?? null;
        $heading    = $data['heading']    ?? null;
        $date       = $data['date']       ?? null;
        $buttonText = $data['buttonText'] ?? null;
        $buttonHref = $data['buttonHref'] ?? null;

        if (
            ($uploadId !== null && !is_int($uploadId))
            || !is_string($label)
            || !is_string($heading)
            || !is_string($date)
            || !is_string($buttonText)
            || !is_string($buttonHref)
        )
            throw new InvalidDataException('split-banner section data');

        return new self(
            uploadId:   $uploadId,
            label:      $label,
            heading:    $heading,
            date:       $date,
            buttonText: $buttonText,
            buttonHref: $buttonHref,
        );
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return [
            'uploadId'   => $this->uploadId,
            'label'      => $this->label,
            'heading'    => $this->heading,
            'date'       => $this->date,
            'buttonText' => $this->buttonText,
            'buttonHref' => $this->buttonHref,
        ];
    }

    function render (): string {
        $label   = htmlspecialchars($this->label,   ENT_QUOTES);
        $heading = htmlspecialchars($this->heading, ENT_QUOTES);

        $labelHtml  = $label !== ''  ? "<div class=\"split-banner-label\">$label</div>"   : '';
        $dateHtml   = '';
        if ($this->date !== '') {
            $date     = htmlspecialchars($this->date, ENT_QUOTES);
            $dateHtml = "<div class=\"split-banner-date\">$date</div>";
        }
        $buttonHtml = '';
        if ($this->buttonText !== '') {
            $text       = htmlspecialchars($this->buttonText, ENT_QUOTES);
            $href       = htmlspecialchars($this->buttonHref, ENT_QUOTES);
            $buttonHtml = "<a class=\"split-banner-button\" href=\"$href\">$text</a>";
        }

        $imageHtml = '';
        if ($this->uploadId !== null) {
            $url       = sprintf(
                '/uploads/%d/%dx%d-cover.webp',
                $this->uploadId,
                self::VARIANT_WIDTH,
                self::VARIANT_HEIGHT,
            );
            $src       = htmlspecialchars($url,            ENT_QUOTES);
            $alt       = htmlspecialchars($this->heading,  ENT_QUOTES);
            $imageHtml = "<img class=\"split-banner-image\" src=\"$src\" alt=\"$alt\" loading=\"lazy\">";
        }

        return <<<HTML
            <section class="split-banner">
                <div class="split-banner-content">
                    $labelHtml
                    <h2 class="split-banner-heading">$heading</h2>
                    $dateHtml
                    $buttonHtml
                </div>
                <div class="split-banner-media">
                    $imageHtml
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
        return [];
    }

}
