<?php declare(strict_types = 1);

namespace TheSaiged\Sections;

use TheSaiged\Core\ClassDiscovery;
use TheSaiged\Core\InvalidDataException;

final class SectionFactory {

    /** @var ?array<string, class-string<Section>> */
    private static ?array $map = null;

    /** @param array<mixed, mixed> $row Expected shape: {type: string, data: array<string, mixed>} */
    static function fromArray (array $row): Section {
        $type = $row['type'] ?? null;
        $data = $row['data'] ?? null;

        if (!is_string($type) || !is_array($data))
            throw new InvalidDataException('section row');

        $class = self::map()[$type] ?? throw new InvalidDataException('section row', "unknown type: $type");
        return $class::fromArray($data);
    }

    /**
     * Inverse of fromArray: wraps a Section instance into the canonical
     * {type, data} dict used by storage and the API. Lives here so Page's
     * toArray can map sections without a closure literal at the call site.
     *
     * @return array<string, mixed>
     */
    static function toArray (Section $section): array {
        return [
            'type' => $section::type(),
            'data' => $section->toArray(),
        ];
    }

    /**
     * The section types currently available, each with a human-readable
     * label derived from its type (kebab-case → capitalized words), for
     * the admin's "add section" menu.
     *
     * @return list<array{type: string, label: string}>
     */
    static function list (): array {
        $list = [];
        foreach (array_keys(self::map()) as $type)
            $list[] = ['type' => $type, 'label' => self::label($type)];
        return $list;
    }

    private static function label (string $type): string {
        return ucfirst(str_replace('-', ' ', $type));
    }

    /** @return array<string, class-string<Section>> */
    private static function map (): array {
        if (self::$map !== null)
            return self::$map;
        $map = [];
        foreach (ClassDiscovery::inSubdirectories(__DIR__, __NAMESPACE__, Section::class) as $class)
            $map[$class::type()] = $class;
        return self::$map = $map;
    }

}
