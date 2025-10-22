<?php
declare(strict_types=1);

// auth/facebook_auth.php
require_once __DIR__ . '/../config_facebook.php';

final class FacebookOAuth
{
    private string $appId;
    private string $appSecret;
    private string $redirectUri;
    private string $graphVersion;

    public function __construct(
        ?string $appId = null,
        ?string $appSecret = null,
        ?string $redirectUri = null,
        ?string $graphVersion = null
    ) {
        $this->appId        = $appId        ?? (defined('FB_APP_ID') ? FB_APP_ID : '');
        $this->appSecret    = $appSecret    ?? (defined('FB_APP_SECRET') ? FB_APP_SECRET : '');
        $this->redirectUri  = $redirectUri  ?? (defined('FB_REDIRECT_URI') ? FB_REDIRECT_URI : '');
        $this->graphVersion = $graphVersion ?? (defined('FB_GRAPH_VERSION') ? FB_GRAPH_VERSION : 'v20.0');

        if ($this->appId === '' || $this->appSecret === '' || $this->redirectUri === '') {
            throw new RuntimeException('Thiếu FB_APP_ID/FB_APP_SECRET/FB_REDIRECT_URI trong config_facebook.php');
        }
    }

    public function buildAuthUrl(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $_SESSION['fb_state'] = bin2hex(random_bytes(16));

        $authBase = "https://www.facebook.com/{$this->graphVersion}/dialog/oauth";
        $query = [
            'client_id'     => $this->appId,
            'redirect_uri'  => $this->redirectUri,
            'state'         => $_SESSION['fb_state'],
            'response_type' => 'code',
            'scope'         => 'public_profile,email',
        ];
        return $authBase . '?' . http_build_query($query);
    }

    public function exchangeCode(string $code): ?array
    {
        $tokenUrl = "https://graph.facebook.com/{$this->graphVersion}/oauth/access_token";
        $post = [
            'client_id'     => $this->appId,
            'client_secret' => $this->appSecret,
            'redirect_uri'  => $this->redirectUri,
            'code'          => $code,
        ];

        $ch = curl_init($tokenUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $post,
        ]);
        $raw = curl_exec($ch);
        $ok  = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
        curl_close($ch);
        return $ok ? json_decode($raw, true) : null;
    }

    public function fetchUser(string $accessToken): ?array
    {
        $meUrl = "https://graph.facebook.com/{$this->graphVersion}/me?fields=id,name,email&" .
            http_build_query(['access_token' => $accessToken]);

        $ch = curl_init($meUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $raw = curl_exec($ch);
        $ok  = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
        curl_close($ch);
        return $ok ? json_decode($raw, true) : null;
    }
}
