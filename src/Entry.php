<?php declare(strict_types = 1);

namespace TheSaiged;

use Throwable;
use TheSaiged\Admins\AdminGuard;
use TheSaiged\Controllers\AdminController;
use TheSaiged\Controllers\AdminsController;
use TheSaiged\Controllers\AuthController;
use TheSaiged\Controllers\MediaController;
use TheSaiged\Controllers\PublicController;
use TheSaiged\Controllers\ShellController;
use TheSaiged\Controllers\TypographyController;
use TheSaiged\Core\Singleton;
use TheSaiged\Core\Http\Request;
use TheSaiged\Core\Http\Route;
use TheSaiged\Core\Http\Router;

final class Entry {

    use Singleton;

    function run (): void {
        try {
            $request  = Request::fromGlobals();
            $response = (new Router($this->routes()))->dispatch($request);
            $response->respond();
        } catch (Throwable $exception) {
            self::hardFail($exception);
        }
    }

    /**
     * Last-resort renderer for unhandled errors. Uses only PHP builtins and
     * PublicController's pure static errorPage — must not throw.
     */
    private static function hardFail (Throwable $exception): void {
        error_log((string) $exception);
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
        echo PublicController::errorPage();
    }

    /** @return list<Route> */
    private function routes (): array {
        return [
            # Google OAuth2 login + whoami/logout — not guarded, since these
            # are exactly how a not-yet-logged-in admin becomes logged in.
            Route::get    ('/auth/google',                     AuthController   ::handler('login')),
            Route::get    ('/auth/google/callback',            AuthController   ::handler('callback')),
            Route::post   ('/auth/logout',                     AuthController   ::handler('logout')),
            Route::get    ('/api/admin/me',                    AuthController   ::handler('me')),

            # Admin accounts — Admin role only, re-checked from the DB on
            # every request (see AdminGuard::admin).
            Route::get    ('/api/admin/admins',                AdminGuard::admin(AdminsController::handler('list'))),
            Route::post   ('/api/admin/admins',                AdminGuard::admin(AdminsController::handler('create'))),
            Route::put    ('/api/admin/admins/{email}',        AdminGuard::admin(AdminsController::handler('updateRole'))),
            Route::delete ('/api/admin/admins/{email}',        AdminGuard::admin(AdminsController::handler('remove'))),

            # Everything else under /api/admin — any logged-in admin.
            Route::get    ('/api/admin/sections',              AdminGuard::any(AdminController  ::handler('listSections'))),
            Route::get    ('/api/admin/pages',                 AdminGuard::any(AdminController  ::handler('listPages'))),
            Route::post   ('/api/admin/pages',                 AdminGuard::any(AdminController  ::handler('createPage'))),
            Route::get    ('/api/admin/pages/{id}',            AdminGuard::any(AdminController  ::handler('getPage'))),
            Route::put    ('/api/admin/pages/{id}',            AdminGuard::any(AdminController  ::handler('updatePage'))),
            Route::delete ('/api/admin/pages/{id}',            AdminGuard::any(AdminController  ::handler('deletePage'))),
            Route::post   ('/api/admin/pages/{id}/copy',       AdminGuard::any(AdminController  ::handler('copyPage'))),
            Route::get    ('/api/admin/uploads',               AdminGuard::any(MediaController  ::handler('listUploads'))),
            Route::post   ('/api/admin/uploads',               AdminGuard::any(MediaController  ::handler('createUpload'))),
            Route::delete ('/api/admin/uploads/{id}',          AdminGuard::any(MediaController  ::handler('deleteUpload'))),
            Route::post   ('/api/admin/uploads/{id}/variants', AdminGuard::any(MediaController  ::handler('ensureVariant'))),
            Route::get    ('/api/admin/shell/{type}',          AdminGuard::any(ShellController  ::handler('get'))),
            Route::put    ('/api/admin/shell/{type}',          AdminGuard::any(ShellController  ::handler('put'))),
            Route::get    ('/api/admin/typography',              AdminGuard::any(TypographyController ::handler('get'))),
            Route::post   ('/api/admin/typography/{role}/faces', AdminGuard::any(TypographyController ::handler('addFace'))),
            Route::delete ('/api/admin/typography/faces/{id}',   AdminGuard::any(TypographyController ::handler('removeFace'))),
            Route::any    ('/api/*',                           AdminController  ::handler('notFound')),
            Route::any    ('/*',                               PublicController ::handler('page')),
        ];
    }

}
