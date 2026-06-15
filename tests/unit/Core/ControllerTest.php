<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Core;

use RuntimeException;
use Throwable;
use TheSaiged\Core\Container;
use TheSaiged\Core\Controller;
use TheSaiged\Core\Http\Method;
use TheSaiged\Core\Http\Path;
use TheSaiged\Core\Http\Query;
use TheSaiged\Core\Http\Request;
use TheSaiged\Core\Http\Response;
use TheSaiged\Tests\TestCase;

final class ControllerTest extends TestCase {

    function testHandlerReturnsClosureInvokingTheNamedMethod (): void {
        $handler = ControllerFixture::handler('greet');
        $request = new Request(Method::GET, new Path('/'), new Query());

        $response = $handler($request);

        $this->assertSame('hello', $response->body);
    }

    function testHandlerUsesContainerOverride (): void {
        $fixture = new ControllerFixture();
        Container::set(ControllerFixture::class, $fixture);

        $handler  = ControllerFixture::handler('greet');
        $response = $handler(new Request(Method::GET, new Path('/'), new Query()));

        $this->assertSame('hello', $response->body);
    }

    function testHandlerRoutesThrownExceptionToOnError (): void {
        $handler  = ControllerFixture::handler('boom');
        $response = $handler(new Request(Method::GET, new Path('/'), new Query()));

        $this->assertSame(500, $response->status);
        $this->assertStringContainsString('handled: boom!', $response->body);
    }

}

final class ControllerFixture {

    use Controller;

    function greet (Request $request): Response {
        return Response::text('hello');
    }

    function boom (Request $request): Response {
        throw new RuntimeException('boom!');
    }

    function onError (Throwable $e, Request $request): Response {
        return Response::text('handled: ' . $e->getMessage(), 500);
    }

}
