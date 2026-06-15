<?php declare(strict_types = 1);

namespace TheSaiged\Sections\ThreeColumn;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\Section;

final readonly class ThreeColumnSection implements Section {

    function __construct (
        public string $heading,
        public string $col1Title,
        public string $col1Body,
        public string $col2Title,
        public string $col2Body,
        public string $col3Title,
        public string $col3Body,
    ) {}

    static function type (): string {
        return 'three-column';
    }

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): static {
        $heading   = $data['heading']   ?? null;
        $col1Title = $data['col1Title'] ?? null;
        $col1Body  = $data['col1Body']  ?? null;
        $col2Title = $data['col2Title'] ?? null;
        $col2Body  = $data['col2Body']  ?? null;
        $col3Title = $data['col3Title'] ?? null;
        $col3Body  = $data['col3Body']  ?? null;

        if (
            !is_string($heading)
            || !is_string($col1Title) || !is_string($col1Body)
            || !is_string($col2Title) || !is_string($col2Body)
            || !is_string($col3Title) || !is_string($col3Body)
        )
            throw new InvalidDataException('three-column section data');

        return new self(
            heading:   $heading,
            col1Title: $col1Title,
            col1Body:  $col1Body,
            col2Title: $col2Title,
            col2Body:  $col2Body,
            col3Title: $col3Title,
            col3Body:  $col3Body,
        );
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return [
            'heading'   => $this->heading,
            'col1Title' => $this->col1Title,
            'col1Body'  => $this->col1Body,
            'col2Title' => $this->col2Title,
            'col2Body'  => $this->col2Body,
            'col3Title' => $this->col3Title,
            'col3Body'  => $this->col3Body,
        ];
    }

    function render (): string {
        $heading = htmlspecialchars($this->heading, ENT_QUOTES);
        $cols    = '';
        foreach ([
            [$this->col1Title, $this->col1Body],
            [$this->col2Title, $this->col2Body],
            [$this->col3Title, $this->col3Body],
        ] as [$title, $body]) {
            $t     = htmlspecialchars($title, ENT_QUOTES);
            $b     = htmlspecialchars($body,  ENT_QUOTES);
            $cols .= <<<HTML
                <div class="three-column-item">
                    <h3 class="three-column-item-title">$t</h3>
                    <p class="three-column-item-body">$b</p>
                </div>
                HTML;
        }
        return <<<HTML
            <section class="three-column">
                <h2 class="three-column-heading">$heading</h2>
                <div class="three-column-grid">$cols</div>
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
