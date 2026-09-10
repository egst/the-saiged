<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Core\Http;

use PHPUnit\Framework\Attributes\TestWith;
use TheSaiged\Tests\TestCase;
use TheSaiged\Core\Http\Method;
use TheSaiged\Core\Http\Path;
use TheSaiged\Core\Http\Query;
use TheSaiged\Core\Http\Request;
use TheSaiged\Core\Http\Response;
use TheSaiged\Core\Http\Route;

final class RouteTest extends TestCase {

    #[TestWith(['/users',      '/users',         true])]
    #[TestWith(['/users',      '/posts',         false])]
    #[TestWith(['/users/{id}', '/users/42',      true])]
    #[TestWith(['/users/{id}', '/users/42/x',    false])]
    #[TestWith(['/api/*',      '/api/x/y/z',     true])]
    #[TestWith(['/api/*',      '/admin',         false])]
    #[TestWith(['/*',          '/anything/here', true])]
    function testPatternMatching (string $pattern, string $path, bool $shouldMatch): void {
        $route   = Route::get($pattern, fn (Request $r) => Response::text('ok'));
        $request = new Request(Method::GET, new Path($path), new Query());

        $this->assertSame($shouldMatch, $route->try($request) !== null);
    }

    function testNoMatchOnDifferentMethod (): void {
        $route   = Route::get('/users', fn (Request $r) => Response::text('ok'));
        $request = new Request(Method::POST, new Path('/users'), new Query());

        $this->assertNull($route->try($request));
    }

    function testCapturesNamedParam (): void {
        $route   = Route::get('/users/{id}', fn (Request $r) => Response::text(
            $r->path->get('id') ?? '',
        ));
        $request = new Request(Method::GET, new Path('/users/42'), new Query());

        $this->assertSame('42', $route->try($request)?->body);
    }

    #[TestWith([Method::GET])]
    #[TestWith([Method::POST])]
    #[TestWith([Method::PUT])]
    #[TestWith([Method::DELETE])]
    #[TestWith([Method::PATCH])]
    function testAnyMatchesAllMethods (Method $method): void {
        $route   = Route::any('/api/*', fn (Request $r) => Response::text('ok'));
        $request = new Request($method, new Path('/api/x'), new Query());

        $this->assertNotNull($route->try($request));
    }

    function testHandlerReceivesRequestWithPathParams (): void {
        $route = Route::get('/users/{id}', fn (Request $r) => Response::text(
            (string) $r->path->getInt('id'),
        ));
        $request = new Request(Method::GET, new Path('/users/42'), new Query());

        $this->assertSame('42', $route->try($request)?->body);
    }

    /**
     * A path segment carrying reserved characters (e.g. an email used as
     * an id — see /api/admin/admins/{email}) arrives percent-encoded on
     * the wire; the captured param must come back decoded, or a lookup
     * by the decoded value (e.g. AdminRepository::findByEmail) silently
     * misses.
     */
    function testCapturedParamIsUrlDecoded (): void {
        $route = Route::get('/users/{email}', fn (Request $r) => Response::text(
            $r->path->get('email') ?? '',
        ));
        $request = new Request(Method::GET, new Path('/users/a%40x.com'), new Query());

        $this->assertSame('a@x.com', $route->try($request)?->body);
    }

}
