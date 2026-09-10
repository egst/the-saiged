<?php declare(strict_types = 1);

namespace TheSaiged\Admins;

use Closure;
use TheSaiged\Core\Container;
use TheSaiged\Core\Http\Request;
use TheSaiged\Core\Http\Response;
use TheSaiged\Core\Http\Session;

/**
 * Wraps a route handler Closure(Request):Response with a login check —
 * the Router has no middleware concept, so this is applied per-route at
 * registration time in Entry::routes() instead.
 */
final class AdminGuard {

    /**
     * Any logged-in admin (either role). Session-only check — cheap, and
     * good enough for routes where a role downgrade taking effect only on
     * the next login is an acceptable lag.
     *
     * @param Closure(Request):Response $handler
     * @return Closure(Request):Response
     */
    static function any (Closure $handler): Closure {
        return function (Request $request) use ($handler): Response {
            return is_string(Session::get('adminEmail'))
                ? $handler($request)
                : Response::json(['error' => 'Unauthorized'], 401);
        };
    }

    /**
     * Logged in AND currently role Admin, re-checked against the DB on
     * every call (not just the session) — so demoting an admin takes
     * effect immediately, not just after their next login. Reserved for
     * the admin-management endpoints themselves.
     *
     * @param Closure(Request):Response $handler
     * @return Closure(Request):Response
     */
    static function admin (Closure $handler): Closure {
        return function (Request $request) use ($handler): Response {
            $email  = Session::get('adminEmail');
            $record = is_string($email) ? Container::get(AdminService::class)->isAuthorized($email) : null;

            if ($record === null)
                return Response::json(['error' => 'Unauthorized'], 401);
            if ($record->role !== AdminRole::Admin)
                return Response::json(['error' => 'Forbidden'], 403);
            return $handler($request);
        };
    }

}
