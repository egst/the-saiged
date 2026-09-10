<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Controllers;

use TheSaiged\Admins\AdminCreate;
use TheSaiged\Admins\AdminRecord;
use TheSaiged\Admins\AdminRole;
use TheSaiged\Admins\AdminService;
use TheSaiged\Admins\DuplicateEmailException;
use TheSaiged\Admins\LastAdminException;
use TheSaiged\Controllers\AdminsController;
use TheSaiged\Core\Container;
use TheSaiged\Core\Http\Method;
use TheSaiged\Core\Http\Path;
use TheSaiged\Core\Http\Query;
use TheSaiged\Core\Http\Request;
use TheSaiged\Core\Http\Response;
use TheSaiged\Tests\TestCase;

final class AdminsControllerTest extends TestCase {

    function testListReturnsSerializedAdmins (): void {
        $service = $this->createMock(AdminService::class);
        $service->method('list')->willReturn([new AdminRecord('a@x.com', AdminRole::Editor)]);
        Container::set(AdminService::class, $service);

        $response = $this->invoke('list', new Request(Method::GET, new Path('/api/admin/admins'), new Query()));

        $this->assertSame(200, $response->status);
        $body = json_decode($response->body, true);
        $this->assertSame([['email' => 'a@x.com', 'role' => 'editor']], $body['admins']);
    }

    function testCreateReturns201WithNewAdmin (): void {
        $service = $this->createMock(AdminService::class);
        $service->expects($this->once())->method('addAdmin')
            ->with($this->callback(fn (AdminCreate $c) => $c->email === 'a@x.com' && $c->role === AdminRole::Editor))
            ->willReturn(new AdminRecord('a@x.com', AdminRole::Editor));
        Container::set(AdminService::class, $service);

        $response = $this->invoke('create', $this->requestWithBody(Method::POST, '/api/admin/admins', [
            'email' => 'a@x.com',
            'role'  => 'editor',
        ]));

        $this->assertSame(201, $response->status);
        $body = json_decode($response->body, true);
        $this->assertTrue($body['ok']);
    }

    function testCreatePropagatesDuplicateEmailAs409 (): void {
        $service = $this->createMock(AdminService::class);
        $service->method('addAdmin')->willThrowException(new DuplicateEmailException('a@x.com'));
        Container::set(AdminService::class, $service);

        $response = $this->invoke('create', $this->requestWithBody(Method::POST, '/api/admin/admins', [
            'email' => 'a@x.com',
            'role'  => 'editor',
        ]));

        $this->assertSame(409, $response->status);
    }

    function testUpdateRoleReturns404ForUnknownEmail (): void {
        $service = $this->createMock(AdminService::class);
        $service->method('updateRole')->willReturn(false);
        Container::set(AdminService::class, $service);

        $response = $this->invoke('updateRole', $this->requestWithBody(
            Method::PUT, '/api/admin/admins/nobody@x.com', ['role' => 'admin'], ['email' => 'nobody@x.com'],
        ));

        $this->assertSame(404, $response->status);
    }

    function testUpdateRolePropagatesLastAdminAs409 (): void {
        $service = $this->createMock(AdminService::class);
        $service->method('updateRole')->willThrowException(new LastAdminException('a@x.com'));
        Container::set(AdminService::class, $service);

        $response = $this->invoke('updateRole', $this->requestWithBody(
            Method::PUT, '/api/admin/admins/a@x.com', ['role' => 'editor'], ['email' => 'a@x.com'],
        ));

        $this->assertSame(409, $response->status);
    }

    function testRemoveReturns404ForUnknownEmail (): void {
        $service = $this->createMock(AdminService::class);
        $service->method('removeAdmin')->willReturn(false);
        Container::set(AdminService::class, $service);

        $response = $this->invoke('remove', new Request(
            Method::DELETE, new Path('/api/admin/admins/nobody@x.com', ['email' => 'nobody@x.com']), new Query(),
        ));

        $this->assertSame(404, $response->status);
    }

    function testRemovePropagatesLastAdminAs409 (): void {
        $service = $this->createMock(AdminService::class);
        $service->method('removeAdmin')->willThrowException(new LastAdminException('a@x.com'));
        Container::set(AdminService::class, $service);

        $response = $this->invoke('remove', new Request(
            Method::DELETE, new Path('/api/admin/admins/a@x.com', ['email' => 'a@x.com']), new Query(),
        ));

        $this->assertSame(409, $response->status);
    }

    private function invoke (string $method, Request $request): Response {
        return (AdminsController::handler($method))($request);
    }

    /** @param array<string, mixed> $body @param array<string, string> $pathParams */
    private function requestWithBody (Method $method, string $path, array $body, array $pathParams = []): Request {
        return new Request($method, new Path($path, $pathParams), new Query(), body: $body);
    }

}
