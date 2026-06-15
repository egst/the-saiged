<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Core\Http;

use TheSaiged\Core\Http\Response;
use TheSaiged\Tests\TestCase;

final class ResponseTest extends TestCase {

    function testHtmlFactory (): void {
        $response = Response::html('<p>hi</p>');
        $this->assertSame(200, $response->status);
        $this->assertSame('<p>hi</p>', $response->body);
        $this->assertSame('text/html; charset=utf-8', $response->headers['Content-Type']);
    }

    function testHtmlCustomStatus (): void {
        $response = Response::html('<h1>404</h1>', 404);
        $this->assertSame(404, $response->status);
    }

    function testJsonFactory (): void {
        $response = Response::json(['ok' => true]);
        $this->assertSame(200, $response->status);
        $this->assertSame('{"ok":true}', $response->body);
        $this->assertSame('application/json; charset=utf-8', $response->headers['Content-Type']);
    }

    function testJsonPreservesUnicode (): void {
        $response = Response::json(['name' => 'Žluťoučký']);
        $this->assertStringContainsString('Žluťoučký', $response->body);
    }

    function testJsonPreservesSlashes (): void {
        $response = Response::json(['path' => '/api/admin']);
        $this->assertStringContainsString('/api/admin', $response->body);
        $this->assertStringNotContainsString('\/api', $response->body);
    }

    function testTextFactory (): void {
        $response = Response::text('plain');
        $this->assertSame('plain', $response->body);
        $this->assertSame('text/plain; charset=utf-8', $response->headers['Content-Type']);
    }

    function testRedirectFactory (): void {
        $response = Response::redirect('/login');
        $this->assertSame(302, $response->status);
        $this->assertSame('/login', $response->headers['Location']);
    }

}
