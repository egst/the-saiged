<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Admins;

use TheSaiged\Admins\AdminGuard;
use TheSaiged\Admins\AdminRecord;
use TheSaiged\Admins\AdminRole;
use TheSaiged\Admins\AdminService;
use TheSaiged\Core\Container;
use TheSaiged\Core\Http\Method;
use TheSaiged\Core\Http\Path;
use TheSaiged\Core\Http\Query;
use TheSaiged\Core\Http\Request;
use TheSaiged\Core\Http\Response;
use TheSaiged\Core\Http\Session;
use TheSaiged\Tests\TestCase;

final class AdminGuardTest extends TestCase {

    protected function setUp (): void {
        parent::setUp();
        $_SESSION = [];
    }

    function testAnyRejectsWhenNotLoggedIn (): void {
        $response = AdminGuard::any(self::neverCalled())($this->request());

        $this->assertSame(401, $response->status);
    }

    function testAnyAllowsAnyLoggedInRole (): void {
        Session::set('adminEmail', 'a@x.com');

        $response = AdminGuard::any(self::alwaysOk())($this->request());

        $this->assertSame(200, $response->status);
    }

    function testAdminRejectsWhenNotLoggedIn (): void {
        Container::set(AdminService::class, $this->createMock(AdminService::class));

        $response = AdminGuard::admin(self::neverCalled())($this->request());

        $this->assertSame(401, $response->status);
    }

    function testAdminRejectsEditorRoleEvenIfSessionSaysOtherwise (): void {
        Session::set('adminEmail', 'a@x.com');
        $service = $this->createMock(AdminService::class);
        $service->method('isAuthorized')->with('a@x.com')->willReturn(new AdminRecord('a@x.com', AdminRole::Editor));
        Container::set(AdminService::class, $service);

        $response = AdminGuard::admin(self::neverCalled())($this->request());

        $this->assertSame(403, $response->status);
    }

    function testAdminRejectsWhenNoLongerAnAdminInTheDb (): void {
        Session::set('adminEmail', 'a@x.com');
        $service = $this->createMock(AdminService::class);
        $service->method('isAuthorized')->willReturn(null);
        Container::set(AdminService::class, $service);

        $response = AdminGuard::admin(self::neverCalled())($this->request());

        $this->assertSame(401, $response->status);
    }

    function testAdminAllowsCurrentAdminRole (): void {
        Session::set('adminEmail', 'a@x.com');
        $service = $this->createMock(AdminService::class);
        $service->method('isAuthorized')->willReturn(new AdminRecord('a@x.com', AdminRole::Admin));
        Container::set(AdminService::class, $service);

        $response = AdminGuard::admin(self::alwaysOk())($this->request());

        $this->assertSame(200, $response->status);
    }

    private function request (): Request {
        return new Request(Method::GET, new Path('/api/admin/whatever'), new Query());
    }

    private static function alwaysOk (): \Closure {
        return fn (Request $request): Response => Response::json(['ok' => true]);
    }

    private static function neverCalled (): \Closure {
        return fn (Request $request): Response => throw new \RuntimeException('handler should not have been called');
    }

}
