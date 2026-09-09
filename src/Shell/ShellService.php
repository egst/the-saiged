<?php declare(strict_types = 1);

namespace TheSaiged\Shell;

use TheSaiged\Core\InvalidDataException;

/**
 * Business operations for Shell. Looks up the concrete class for a type
 * via ShellFactory, then either decodes the stored row or falls back to
 * the type's default() — same "always something to render" guarantee
 * PageService gives Pages, minus the publication-status distinction
 * shells don't have.
 */
final readonly class ShellService {

    function __construct (
        private ShellRepository $repo,
    ) {}

    function get (string $type): Shell {
        $class = ShellFactory::classFor($type);
        $json  = $this->repo->getData($type);
        if ($json === null)
            return $class::default();

        $decoded = json_decode($json, true);
        if (!is_array($decoded))
            throw new InvalidDataException('shell data JSON');
        return $class::fromArray($decoded);
    }

    /**
     * @param array<mixed, mixed> $data
     * @throws InvalidDataException when $type is unknown or $data doesn't
     *         match that type's expected shape
     */
    function save (string $type, array $data): void {
        $class = ShellFactory::classFor($type);
        $shell = $class::fromArray($data);
        $this->repo->save($type, json_encode($shell->toArray(), JSON_THROW_ON_ERROR));
    }

}
