<?php declare(strict_types = 1);

namespace TheSaiged\Sections\NewsletterSignup;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Sections\Section;

final readonly class NewsletterSignupSection implements Section {

    function __construct (
        public string $heading,
        public string $body,
        public string $formAction,
    ) {}

    static function type (): string {
        return 'newsletter-signup';
    }

    /** @param array<mixed, mixed> $data */
    static function fromArray (array $data): static {
        $heading    = $data['heading']    ?? null;
        $body       = $data['body']       ?? null;
        $formAction = $data['formAction'] ?? null;

        if (!is_string($heading) || !is_string($body) || !is_string($formAction))
            throw new InvalidDataException('newsletter-signup section data');

        return new self(heading: $heading, body: $body, formAction: $formAction);
    }

    /** @return array<string, mixed> */
    function toArray (): array {
        return [
            'heading'    => $this->heading,
            'body'       => $this->body,
            'formAction' => $this->formAction,
        ];
    }

    function render (): string {
        $heading    = htmlspecialchars($this->heading,    ENT_QUOTES);
        $body       = htmlspecialchars($this->body,       ENT_QUOTES);
        $formAction = htmlspecialchars($this->formAction, ENT_QUOTES);
        $bodyHtml = $body !== ''
            ? "<p class=\"newsletter-body\">$body</p>"
            : '';
        return <<<HTML
            <section class="newsletter-signup">
                <h3 class="newsletter-heading">$heading</h3>
                $bodyHtml
                <form class="newsletter-form" action="$formAction" method="post">
                    <input type="email" name="EMAIL" placeholder="Enter your email address" required aria-label="Email address">
                    <button type="submit">Subscribe</button>
                </form>
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
