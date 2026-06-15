<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Core\Http;

use TheSaiged\Tests\TestCase;
use TheSaiged\Core\Http\Exception\NotFoundException;
use TheSaiged\Core\Http\Method;
use TheSaiged\Core\Http\Path;
use TheSaiged\Core\Http\Query;
use TheSaiged\Core\Http\Request;
use TheSaiged\Core\Http\Response;
use TheSaiged\Core\Http\Route;
use TheSaiged\Core\Http\Router;

final class RouterTest extends TestCase {

    function testReturnsResponseFromMatchedRoute (): void {
        $router = new Router([
            Route::get('/users', fn (Request $r) => Response::text('users')),
        ]);
        $request = new Request(Method::GET, new Path('/users'), new Query());

        $response = $router->dispatch($request);

        $this->assertSame('users', $response->body);
    }

    function testFirstMatchWins (): void {
        $router = new Router([
            Route::get('/users', fn (Request $r) => Response::text('first')),
            Route::get('/users', fn (Request $r) => Response::text('second')),
        ]);
        $request = new Request(Method::GET, new Path('/users'), new Query());

        $this->assertSame('first', $router->dispatch($request)->body);
    }

    function testRoutesTriedInOrder (): void {
        $router = new Router([
            Route::get('/api/admin/pages',      fn (Request $r) => Response::text('specific')),
            Route::any('/api/*',                fn (Request $r) => Response::text('catch-api')),
            Route::any('/*',                    fn (Request $r) => Response::text('catch-all')),
        ]);

        $this->assertSame(
            'specific',
            $router->dispatch(new Request(Method::GET, new Path('/api/admin/pages'), new Query()))->body,
        );
        $this->assertSame(
            'catch-api',
            $router->dispatch(new Request(Method::GET, new Path('/api/typo'), new Query()))->body,
        );
        $this->assertSame(
            'catch-all',
            $router->dispatch(new Request(Method::GET, new Path('/anything'), new Query()))->body,
        );
    }

    function testThrowsNotFoundWhenNoRouteMatches (): void {
        $router  = new Router([
            Route::get('/users', fn (Request $r) => Response::text('ok')),
        ]);
        $request = new Request(Method::GET, new Path('/posts'), new Query());

        $this->expectException(NotFoundException::class);
        $router->dispatch($request);
    }

}
