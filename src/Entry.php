<?php declare(strict_types = 1);

namespace TheSaiged;

use Throwable;
use TheSaiged\Controllers\AdminController;
use TheSaiged\Controllers\MediaController;
use TheSaiged\Controllers\PublicController;
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
            Route::get    ('/api/admin/sections',        AdminController  ::handler('listSections')),
            Route::get    ('/api/admin/pages',           AdminController  ::handler('listPages')),
            Route::post   ('/api/admin/pages',           AdminController  ::handler('createPage')),
            Route::get    ('/api/admin/pages/{id}',      AdminController  ::handler('getPage')),
            Route::put    ('/api/admin/pages/{id}',      AdminController  ::handler('updatePage')),
            Route::delete ('/api/admin/pages/{id}',      AdminController  ::handler('deletePage')),
            Route::post   ('/api/admin/pages/{id}/copy', AdminController  ::handler('copyPage')),
            Route::get    ('/api/admin/uploads',                  MediaController  ::handler('listUploads')),
            Route::post   ('/api/admin/uploads',                  MediaController  ::handler('createUpload')),
            Route::delete ('/api/admin/uploads/{id}',             MediaController  ::handler('deleteUpload')),
            Route::post   ('/api/admin/uploads/{id}/variants',    MediaController  ::handler('ensureVariant')),
            Route::any    ('/api/*',                     AdminController  ::handler('notFound')),
            Route::any    ('/*',                         PublicController ::handler('page')),
        ];
    }

}
