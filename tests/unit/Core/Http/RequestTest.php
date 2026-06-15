<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Core\Http;

use TheSaiged\Core\Http\Method;
use TheSaiged\Core\Http\Path;
use TheSaiged\Core\Http\Query;
use TheSaiged\Core\Http\Request;
use TheSaiged\Tests\TestCase;

final class RequestTest extends TestCase {

    function testWithPathParamsReturnsNewInstance (): void {
        $original = new Request(Method::GET, new Path('/users/42'), new Query());
        $with     = $original->withPathParams(['id' => '42']);

        $this->assertNotSame($original, $with);
        $this->assertNull($original->path->get('id'));
        $this->assertSame('42', $with->path->get('id'));
    }

    function testBodyObjectReturnsAssocArray (): void {
        $req = new Request(Method::POST, new Path('/'), new Query(), body: ['name' => 'Foo']);
        $this->assertSame(['name' => 'Foo'], $req->bodyObject());
    }

    function testBodyObjectReturnsNullForList (): void {
        $req = new Request(Method::POST, new Path('/'), new Query(), body: [1, 2, 3]);
        $this->assertNull($req->bodyObject());
    }

    function testBodyObjectReturnsNullForScalar (): void {
        $req = new Request(Method::POST, new Path('/'), new Query(), body: 'string');
        $this->assertNull($req->bodyObject());
    }

    function testBodyListReturnsList (): void {
        $req = new Request(Method::POST, new Path('/'), new Query(), body: [1, 2, 3]);
        $this->assertSame([1, 2, 3], $req->bodyList());
    }

    function testBodyListReturnsNullForAssoc (): void {
        $req = new Request(Method::POST, new Path('/'), new Query(), body: ['a' => 1]);
        $this->assertNull($req->bodyList());
    }

    function testHeaderReturnsValueCaseInsensitive (): void {
        $req = new Request(Method::GET, new Path('/'), new Query(), headers: ['content-type' => 'application/json']);
        $this->assertSame('application/json', $req->header('Content-Type'));
        $this->assertSame('application/json', $req->header('CONTENT-TYPE'));
    }

    function testHeaderReturnsNullForMissing (): void {
        $req = new Request(Method::GET, new Path('/'), new Query());
        $this->assertNull($req->header('X-Missing'));
    }

}
