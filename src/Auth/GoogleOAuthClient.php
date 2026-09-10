<?php declare(strict_types = 1);

namespace TheSaiged\Auth;

use RuntimeException;
use TheSaiged\Core\Env;

/**
 * Hand-rolled Google OAuth2 authorization-code flow — no OAuth/JWT
 * library dependency, consistent with this project's preference for
 * small own-implementations (see Core/Database's own query builder).
 * "Login with Google" is pure identity verification here: we never look
 * at scopes/permissions beyond the account's verified email, and we
 * fetch it from Google's userinfo endpoint (over HTTPS, directly) rather
 * than parsing/verifying the id_token JWT ourselves.
 */
final readonly class GoogleOAuthClient {

    private const AUTH_URL     = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL    = 'https://oauth2.googleapis.com/token';
    private const USERINFO_URL = 'https://www.googleapis.com/oauth2/v3/userinfo';

    function __construct (
        private string $clientId,
        private string $clientSecret,
        private string $redirectUri,
    ) {}

    /**
     * Reads env vars but doesn't require them — AuthController is
     * constructed (via DI, eagerly) for every /auth/* and /api/admin/me
     * request, including ones (me, logout) that never touch this client.
     * Missing config only becomes an error where it's actually used —
     * see isConfigured() — not at construction time.
     */
    static function fromEnv (): self {
        return new self(
            clientId:     Env::optional('GOOGLE_CLIENT_ID', ''),
            clientSecret: Env::optional('GOOGLE_CLIENT_SECRET', ''),
            redirectUri:  Env::optional('GOOGLE_REDIRECT_URI', ''),
        );
    }

    function isConfigured (): bool {
        return $this->clientId !== '' && $this->clientSecret !== '' && $this->redirectUri !== '';
    }

    /** @throws RuntimeException when GOOGLE_CLIENT_ID/SECRET/REDIRECT_URI aren't set */
    function authorizeUrl (string $state): string {
        if (!$this->isConfigured())
            throw new RuntimeException('Google OAuth is not configured (GOOGLE_CLIENT_ID/SECRET/REDIRECT_URI)');

        $query = http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'response_type' => 'code',
            'scope'         => 'openid email',
            'state'         => $state,
            'prompt'        => 'select_account',
        ]);
        return self::AUTH_URL . '?' . $query;
    }

    /**
     * Exchanges an authorization code for the account's verified email.
     *
     * @throws RuntimeException on any network/response failure, or an
     *         unverified email
     */
    function resolveEmail (string $code): string {
        if (!$this->isConfigured())
            throw new RuntimeException('Google OAuth is not configured (GOOGLE_CLIENT_ID/SECRET/REDIRECT_URI)');

        $accessToken = $this->exchangeCode($code);
        return $this->fetchVerifiedEmail($accessToken);
    }

    private function exchangeCode (string $code): string {
        $body = http_build_query([
            'code'          => $code,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUri,
            'grant_type'    => 'authorization_code',
        ]);

        $decoded = self::decodeJson(self::post(self::TOKEN_URL, $body));
        $token   = $decoded['access_token'] ?? null;
        if (!is_string($token))
            throw new RuntimeException('Google token exchange did not return an access_token');
        return $token;
    }

    private function fetchVerifiedEmail (string $accessToken): string {
        $context = stream_context_create(['http' => [
            'method'  => 'GET',
            'header'  => "Authorization: Bearer $accessToken",
            'timeout' => 10,
        ]]);

        $response = file_get_contents(self::USERINFO_URL, false, $context);
        if ($response === false)
            throw new RuntimeException('Failed to fetch Google userinfo');

        $decoded  = self::decodeJson($response);
        $email    = $decoded['email']          ?? null;
        $verified = $decoded['email_verified'] ?? null;
        if (!is_string($email) || $verified !== true)
            throw new RuntimeException('Google userinfo did not return a verified email');
        return $email;
    }

    private static function post (string $url, string $body): string {
        $context = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $body,
            'timeout' => 10,
        ]]);

        $response = file_get_contents($url, false, $context);
        if ($response === false)
            throw new RuntimeException("POST to $url failed");
        return $response;
    }

    /** @return array<string, mixed> */
    private static function decodeJson (string $json): array {
        $decoded = json_decode($json, true);
        if (!is_array($decoded))
            return [];
        $result = [];
        foreach ($decoded as $key => $value)
            $result[(string) $key] = $value;
        return $result;
    }

}
