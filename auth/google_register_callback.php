<?php
session_start();
require_once __DIR__ . '/../model/database.php';

$db  = new Database();
$pdo = $db->connect();

$config = require __DIR__ . '/../config.php';

// Validate state
if (!isset($_GET['state']) || !hash_equals($_SESSION['google_register_state'] ?? '', $_GET['state'])) {
  http_response_code(400);
  exit('Invalid state');
}
unset($_SESSION['google_register_state']);

if (empty($_GET['code'])) {
  http_response_code(400);
  exit('Missing code');
}

// Đổi code -> token
$tokenUrl = 'https://oauth2.googleapis.com/token';
$data = [
  'code'          => $_GET['code'],
  'client_id'     => $config['google_client_id'],
  'client_secret' => $config['google_client_secret'],
  'redirect_uri'  => $config['google_register_redirect'],
  'grant_type'    => 'authorization_code',
];
$ch = curl_init($tokenUrl);
curl_setopt_array($ch, [
  CURLOPT_POST           => true,
  CURLOPT_POSTFIELDS     => http_build_query($data),
  CURLOPT_RETURNTRANSFER => true,
]);
$resp = curl_exec($ch);
curl_close($ch);

$token = json_decode($resp, true);
if (empty($token['access_token'])) {
  exit('Token exchange failed');
}

// Lấy userinfo
$ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
curl_setopt_array($ch, [
  CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token['access_token']],
  CURLOPT_RETURNTRANSFER => true,
]);
$userinfo = json_decode(curl_exec($ch), true);
curl_close($ch);

$email  = $userinfo['email']   ?? null;
$gid    = $userinfo['sub']     ?? null;
$name   = $userinfo['name']    ?? null;
$avatar = $userinfo['picture'] ?? null;

if (!$email || !$gid) exit('Could not fetch Google profile');

// Check nếu user đã tồn tại thì báo lỗi
$stmt = $pdo->prepare('SELECT user_id FROM users WHERE google_id = :gid OR email = :email LIMIT 1');
$stmt->execute([':gid' => $gid, ':email' => $email]);
$exists = $stmt->fetch();

if ($exists) {
  exit('Tài khoản đã tồn tại, vui lòng dùng Đăng nhập Google');
}

// Insert user mới
$ins = $pdo->prepare('INSERT INTO users (email, google_id, user_name, avatar, created_at, last_login)
                      VALUES (:email, :gid, :name, :avatar, NOW(), NOW())');
$ins->execute([
  ':email' => $email, ':gid' => $gid, ':name' => $name, ':avatar' => $avatar
]);
$user_id = (int)$pdo->lastInsertId();

// Tạo session
$_SESSION['user_id'] = $user_id;
$_SESSION['email']   = $email;
$_SESSION['name']    = $name;

header('Location: /Webmarket/index.php');
exit;
