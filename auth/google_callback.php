<?php
// auth/google_callback.php
declare(strict_types=1);
session_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');

$config = require __DIR__ . '/../config.php';
require_once __DIR__ . '/../controller/controller_login.php'; // có hàm handleGoogleLogin(array $profile)

if (!isset($_GET['state'], $_GET['code']) ||
    !hash_equals($_SESSION['oauth2_state'] ?? '', $_GET['state'])) {
    http_response_code(400);
    exit('Invalid state');
}

$code         = $_GET['code'];
$client_id    = $config['google_client_id'];
$client_secret= $config['google_client_secret'];
$redirect_uri = $config['google_redirect_uri'];

// 1) Đổi code lấy access_token
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'code'          => $code,
        'client_id'     => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri'  => $redirect_uri,
        'grant_type'    => 'authorization_code',
    ]),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
]);
$res    = curl_exec($ch);
$cerr   = curl_error($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($res === false || $status >= 400) {
    exit('Token exchange failed: ' . ($cerr ?: $res));
}
$token = json_decode($res, true) ?: [];
$access_token = $token['access_token'] ?? null;
if (!$access_token) exit('No access token');

// 2) Lấy thông tin user
$ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $access_token],
]);
$profileJson = curl_exec($ch);
$cerr   = curl_error($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($profileJson === false || $status >= 400) {
    exit('Fetch profile failed: ' . ($cerr ?: $profileJson));
}
$profile = json_decode($profileJson, true) ?: [];
if (empty($profile['email'])) exit('No email in profile');

// 3) Gọi controller để xử lý login (tạo session, cập nhật DB…)
handleGoogleLogin($profile);
