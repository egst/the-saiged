<?php declare(strict_types = 1);

namespace TheSaiged\Core;

trait Singleton {

    static function get (): static {
        return Container::get(static::class);
    }

}
