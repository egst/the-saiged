<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Controllers;

use TheSaiged\Admins\AdminRecord;
use TheSaiged\Admins\AdminRole;
use TheSaiged\Admins\AdminService;
use TheSaiged\Auth\GoogleOAuthClient;
use TheSaiged\Controllers\AuthController;
use TheSaiged\Core\Container;
use TheSaiged\Core\Http\Method;
use TheSaiged\Core\Http\Path;
use TheSaiged\Core\Http\Query;
use TheSaiged\Core\Http\Request;
use TheSaiged\Core\Http\Response;
use TheSaiged\Core\Http\Session;
use TheSaiged\Tests\TestCase;

final class AuthControllerTest extends TestCase {

    protected function setUp (): void {
        parent::setUp();
        $_SESSION = [];
        Container::set(GoogleOAuthClient::class, $this->createMock(GoogleOAuthClient::class));
    }

    function testLoginRedirectsToGoogleAndStashesState (): void {
        $google = $this->createMock(GoogleOAuthClient::class);
        $google->method('authorizeUrl')->willReturn('https://accounts.google.com/o/oauth2/v2/auth?state=abc');
        Container::set(GoogleOAuthClient::class, $google);
        Container::set(AdminService::class, $this->createMock(AdminService::class));

        $response = $this->invoke('login', $this->request(Method::GET, '/auth/google'));

        $this->assertSame(302, $response->status);
        $this->assertSame('https://accounts.google.com/o/oauth2/v2/auth?state=abc', $response->headers['Location']);
        $this->assertNotNull(Session::get('oauthState'));
    }

    function testCallbackRedirectsToAdminOnAuthorizedEmail (): void {
        Session::set('oauthState', 'expected-state');

        $google = $this->createMock(GoogleOAuthClient::class);
        $google->method('resolveEmail')->with('the-code')->willReturn('a@x.com');
        Container::set(GoogleOAuthClient::class, $google);

        $service = $this->createMock(AdminService::class);
        $service->method('isAuthorized')->with('a@x.com')->willReturn(new AdminRecord('a@x.com', AdminRole::Editor));
        Container::set(AdminService::class, $service);

        $response = $this->invoke('callback', $this->request(
            Method::GET, '/auth/google/callback', ['state' => 'expected-state', 'code' => 'the-code'],
        ));

        $this->assertSame(302, $response->status);
        $this->assertSame('/admin', $response->headers['Location']);
        $this->assertSame('a@x.com', Session::get('adminEmail'));
    }

    function testCallbackRejectsMismatchedState (): void {
        Session::set('oauthState', 'expected-state');
        Container::set(GoogleOAuthClient::class, $this->createMock(GoogleOAuthClient::class));
        Container::set(AdminService::class, $this->createMock(AdminService::class));

        $response = $this->invoke('callback', $this->request(
            Method::GET, '/auth/google/callback', ['state' => 'wrong', 'code' => 'the-code'],
        ));

        $this->assertSame(302, $response->status);
        $this->assertSame('/admin?login=error', $response->headers['Location']);
        $this->assertNull(Session::get('adminEmail'));
    }

    function testCallbackRedirectsWithDeniedForUnauthorizedEmail (): void {
        Session::set('oauthState', 'expected-state');

        $google = $this->createMock(GoogleOAuthClient::class);
        $google->method('resolveEmail')->willReturn('nobody@x.com');
        Container::set(GoogleOAuthClient::class, $google);

        $service = $this->createMock(AdminService::class);
        $service->method('isAuthorized')->willReturn(null);
        Container::set(AdminService::class, $service);

        $response = $this->invoke('callback', $this->request(
            Method::GET, '/auth/google/callback', ['state' => 'expected-state', 'code' => 'the-code'],
        ));

        $this->assertSame('/admin?login=denied', $response->headers['Location']);
        $this->assertNull(Session::get('adminEmail'));
    }

    function testMeReturns401WhenNotLoggedIn (): void {
        Container::set(AdminService::class, $this->createMock(AdminService::class));

        $response = $this->invoke('me', $this->request(Method::GET, '/api/admin/me'));

        $this->assertSame(401, $response->status);
    }

    function testMeReturnsCurrentAccountWhenLoggedIn (): void {
        Session::set('adminEmail', 'a@x.com');

        $service = $this->createMock(AdminService::class);
        $service->method('isAuthorized')->with('a@x.com')->willReturn(new AdminRecord('a@x.com', AdminRole::Admin));
        Container::set(AdminService::class, $service);

        $response = $this->invoke('me', $this->request(Method::GET, '/api/admin/me'));

        $this->assertSame(200, $response->status);
        $body = json_decode($response->body, true);
        $this->assertSame('a@x.com', $body['email']);
        $this->assertSame('admin', $body['role']);
    }

    function testLogoutDestroysSession (): void {
        Session::set('adminEmail', 'a@x.com');
        Container::set(AdminService::class, $this->createMock(AdminService::class));

        $response = $this->invoke('logout', $this->request(Method::POST, '/auth/logout'));

        $this->assertSame(200, $response->status);
        $this->assertNull(Session::get('adminEmail'));
    }

    private function invoke (string $method, Request $request): Response {
        return (AuthController::handler($method))($request);
    }

    /** @param array<string, string> $query */
    private function request (Method $method, string $path, array $query = []): Request {
        return new Request($method, new Path($path), new Query($query));
    }

}
