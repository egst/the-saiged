<?php declare(strict_types = 1);

namespace TheSaiged\Core;

use DI\Container as DIContainer;
use DI\ContainerBuilder;
use PDO;
use RuntimeException;
use TheSaiged\Core\Database\PdoFactory;

final class Container {

    private static ?DIContainer $container = null;

    /**
     * @template T of object
     * @param class-string<T> $id
     * @return T
     */
    static function get (string $id): object {
        $instance = self::container()->get($id);
        if (!$instance instanceof $id)
            throw new RuntimeException('Container returned ' . get_debug_type($instance) . ", expected $id");
        return $instance;
    }

    static function set (string $id, mixed $value): void {
        self::container()->set($id, $value);
    }

    static function reset (): void {
        self::$container = null;
    }

    private static function container (): DIContainer {
        return self::$container ??= new ContainerBuilder()
            ->addDefinitions(self::definitions())
            ->build();
    }

    /** @return array<string, mixed> */
    private static function definitions (): array {
        return [
            PDO::class => fn () => PdoFactory::create(),
        ];
    }

}
