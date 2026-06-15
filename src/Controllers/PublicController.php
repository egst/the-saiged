<?php declare(strict_types = 1);

namespace TheSaiged\Controllers;

use Throwable;
use TheSaiged\Core\Controller;
use TheSaiged\Core\Http\Exception\HttpException;
use TheSaiged\Core\Http\Request;
use TheSaiged\Core\Http\Response;
use TheSaiged\Pages\PageService;

final class PublicController {

    use Controller;

    function __construct (
        private PageService $pages,
    ) {}

    function page (Request $request): Response {
        # TODO: consider adding a page path VO that will take care of the leading slash consistently
        $path = ltrim($request->path->value, '/');
        $page = $this->pages->findPublishedByPath($path);
        if ($page === null)
            return $this->notFound($request);
        return Response::html($page->render());
    }

    function notFound (Request $request): Response {
        return Response::html(
            <<<HTML
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="utf-8">
                    <title>Page not found</title>
                </head>
                <body>
                    <h1>Page not found</h1>
                    <p>The page you're looking for doesn't exist.</p>
                </body>
                </html>
                HTML,
            404
        );
    }

    function onError (Throwable $exception, Request $request): Response {
        if ($exception instanceof HttpException && $exception->status === 404)
            return $this->notFound($request);
        error_log((string) $exception);
        return Response::html(self::errorPage(), 500);
    }

    /**
     * Pure static, dependency-free body used by Entry's hardFail when no
     * controller instance can be safely constructed. Must not throw.
     */
    static function errorPage (): string {
        return <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="utf-8">
                <title>Something went wrong</title>
            </head>
            <body>
                <h1>Oops</h1>
                <p>Something went wrong.</p>
            </body>
            </html>
            HTML;
    }

}
