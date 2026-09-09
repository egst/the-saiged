<?php declare(strict_types = 1);

namespace TheSaiged\Shell;

use TheSaiged\Core\ClassDiscovery;
use TheSaiged\Core\InvalidDataException;

/**
 * Discovery for Shell types, mirroring SectionFactory. Simpler than its
 * Section counterpart because a shell is always looked up individually by
 * a known type (never decoded from a heterogeneous {type, data} list), so
 * there's no fromArray/toArray wrapping to do here — just the type → class
 * lookup that ShellService and ShellController need.
 */
final class ShellFactory {

    /** @var ?array<string, class-string<Shell>> */
    private static ?array $map = null;

    /** @return class-string<Shell> */
    static function classFor (string $type): string {
        return self::map()[$type] ?? throw new InvalidDataException('shell type', "unknown type: $type");
    }

    /** @return array<string, class-string<Shell>> */
    private static function map (): array {
        if (self::$map !== null)
            return self::$map;
        $map = [];
        foreach (ClassDiscovery::inSubdirectories(__DIR__, __NAMESPACE__, Shell::class) as $class)
            $map[$class::type()] = $class;
        return self::$map = $map;
    }

}
