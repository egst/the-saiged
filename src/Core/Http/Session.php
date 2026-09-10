<?php declare(strict_types = 1);

namespace TheSaiged\Core\Http;

use TheSaiged\Core\Env;

/**
 * Thin wrapper over native PHP sessions — same rationale as Request
 * wrapping superglobals: callers touch one small surface instead of
 * $_SESSION directly, and it's swappable in tests. Only the admin login
 * (AuthController/AdminGuard) uses this; the public site is stateless.
 */
final class Session {

    static function get (string $key): mixed {
        self::start();
        return $_SESSION[$key] ?? null;
    }

    static function set (string $key, mixed $value): void {
        self::start();
        $_SESSION[$key] = $value;
    }

    static function remove (string $key): void {
        self::start();
        unset($_SESSION[$key]);
    }

    static function destroy (): void {
        self::start();
        $_SESSION = [];
        session_destroy();
    }

    private static function start (): void {
        if (session_status() === PHP_SESSION_ACTIVE)
            return;
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => Env::optional('APP_ENV', 'development') === 'production',
        ]);
        session_start();
    }

}
