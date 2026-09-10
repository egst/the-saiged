<?php declare(strict_types = 1);

namespace TheSaiged\Controllers;

use Throwable;
use TheSaiged\Admins\AdminService;
use TheSaiged\Auth\GoogleOAuthClient;
use TheSaiged\Core\Controller;
use TheSaiged\Core\Http\Request;
use TheSaiged\Core\Http\Response;
use TheSaiged\Core\Http\Session;

/**
 * Google "Login with Google" flow — pure identity verification, no
 * passwords/hashing/custom sessions to build: Google authenticates,
 * AdminService::isAuthorized decides authorization, this controller just
 * wires the redirect dance and the session it results in.
 */
final class AuthController {

    use Controller;

    function __construct (
        private AdminService      $admins,
        private GoogleOAuthClient $google,
    ) {}

    /** Kicks off the flow: redirect to Google's consent screen. */
    function login (Request $request): Response {
        $state = bin2hex(random_bytes(16));
        Session::set('oauthState', $state);
        return Response::redirect($this->google->authorizeUrl($state));
    }

    /**
     * Google redirects back here with ?code=&state=. The state round-trip
     * guards against CSRF (a third party can't forge a callback carrying
     * the random value only we handed out).
     */
    function callback (Request $request): Response {
        $state       = $request->query->getString('state');
        $code        = $request->query->getString('code');
        $expectedState = Session::get('oauthState');
        Session::remove('oauthState');

        if ($state === null || $code === null || $state !== $expectedState)
            return Response::redirect('/admin?login=error');

        $email  = $this->google->resolveEmail($code);
        $record = $this->admins->isAuthorized($email);
        if ($record === null)
            return Response::redirect('/admin?login=denied');

        Session::set('adminEmail', $record->email);
        return Response::redirect('/admin');
    }

    function logout (Request $request): Response {
        Session::destroy();
        return Response::json(['ok' => true]);
    }

    /** Tells the admin SPA who (if anyone) is currently logged in. */
    function me (Request $request): Response {
        $email  = Session::get('adminEmail');
        $record = is_string($email) ? $this->admins->isAuthorized($email) : null;
        if ($record === null)
            return Response::json(['error' => 'Unauthorized'], 401);
        return Response::json($record->toArray());
    }

    function onError (Throwable $exception, Request $request): Response {
        error_log((string) $exception);
        return Response::redirect('/admin?login=error');
    }

}
