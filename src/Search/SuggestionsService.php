<?php declare(strict_types = 1);

namespace TheSaiged\Search;

use TheSaiged\Core\InvalidDataException;

/**
 * Admin-managed "naseptávač" — placeholder strings the public search
 * input cycles through (typing animation) while empty. Edited as one
 * whole list (add/remove/reorder locally, one Save) rather than
 * per-item REST — there's no natural id per entry, just an ordered
 * list of strings, same shape as HeaderShell's links.
 */
final readonly class SuggestionsService {

    private const MAX_LENGTH = 200;

    function __construct (
        private SuggestionsRepository $repo,
    ) {}

    /** @return list<string> */
    function list (): array {
        $json = $this->repo->getData();
        if ($json === null)
            return [];

        $decoded = json_decode($json, true);
        if (!is_array($decoded))
            throw new InvalidDataException('search suggestions JSON');

        $suggestions = [];
        foreach ($decoded as $item) {
            if (!is_string($item))
                throw new InvalidDataException('search suggestions JSON', 'each entry must be a string');
            $suggestions[] = $item;
        }
        return $suggestions;
    }

    /**
     * @param list<string> $suggestions
     * @throws InvalidDataException on an empty or overlong entry
     */
    function save (array $suggestions): void {
        $trimmed = [];
        foreach ($suggestions as $suggestion) {
            $value = trim($suggestion);
            if ($value === '')
                throw new InvalidDataException('search suggestions', 'entries cannot be empty');
            if (mb_strlen($value) > self::MAX_LENGTH)
                throw new InvalidDataException('search suggestions', 'entry too long');
            $trimmed[] = $value;
        }

        $this->repo->save(json_encode($trimmed, JSON_THROW_ON_ERROR));
    }

}
