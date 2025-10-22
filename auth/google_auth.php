<?php
declare(strict_types=1);

require_once __DIR__ . '/../config_google.php';

final class GoogleOAuth
{
    private const OAUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const USERINFO  = 'https://www.googleapis.com/oauth2/v3/userinfo';

    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct(
        ?string $clientId = null,
        ?string $clientSecret = null,
        ?string $redirectUri = null
    ) {
        $this->clientId     = $clientId     ?? (defined('CLIENT_ID') ? CLIENT_ID : '');
        $this->clientSecret = $clientSecret ?? (defined('CLIENT_SECRET') ? CLIENT_SECRET : '');
        $this->redirectUri  = $redirectUri  ?? (defined('REDIRECT_URI') ? REDIRECT_URI : '');

        if ($this->clientId === '' || $this->redirectUri === '') {
            throw new RuntimeException('Thiếu CLIENT_ID/REDIRECT_URI trong config_google.php');
        }
    }

    public function buildAuthUrl(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $_SESSION['g_state'] = bin2hex(random_bytes(16));

        $q = [
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $_SESSION['g_state'],
            'access_type'   => 'online'
        ];
        return self::OAUTH_URL . '?' . http_build_query($q);
    }

    public function exchangeCode(string $code): ?array
    {
        $post = [
            'code'          => $code,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUri,
            'grant_type'    => 'authorization_code'
        ];
        $ch = curl_init(self::TOKEN_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $post,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15
        ]);
        $raw = curl_exec($ch);
        $ok  = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
        curl_close($ch);
        return $ok ? json_decode($raw, true) : null;
    }

    public function fetchUserInfo(string $accessToken): ?array
    {
        $ch = curl_init(self::USERINFO . '?' . http_build_query(['access_token' => $accessToken]));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15
        ]);
        $raw = curl_exec($ch);
        $ok  = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
        curl_close($ch);
        return $ok ? json_decode($raw, true) : null;
    }
}
