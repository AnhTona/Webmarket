<?php
session_start();
require_once __DIR__ . '/../model/database.php';

$db  = new Database();
$pdo = $db->connect();

$config = require __DIR__ . '/../config.php';
$client_id     = $config['google_client_id'];
$client_secret = $config['google_client_secret'];
$redirect_uri  = $config['google_redirect_uri'];

// 1) Validate state (dùng 1 lần)
if (!isset($_GET['state']) || !hash_equals($_SESSION['oauth2_state'] ?? '', $_GET['state'])) {
  http_response_code(400);
  exit('Invalid state');
}
unset($_SESSION['oauth2_state']);

// 2) Phải có code
if (empty($_GET['code'])) {
  http_response_code(400);
  exit('Missing code');
}

// 3) Đổi code -> token
$tokenUrl = 'https://oauth2.googleapis.com/token';
$data = [
  'code'          => $_GET['code'],
  'client_id'     => $client_id,
  'client_secret' => $client_secret,
  'redirect_uri'  => $redirect_uri,
  'grant_type'    => 'authorization_code',
];

$ch = curl_init($tokenUrl);
curl_setopt_array($ch, [
  CURLOPT_POST           => true,
  CURLOPT_POSTFIELDS     => http_build_query($data),
  CURLOPT_RETURNTRANSFER => true,
]);
$resp = curl_exec($ch);
if ($resp === false) {
  exit('Curl error: ' . curl_error($ch));
}
curl_close($ch);

$token = json_decode($resp, true);
if (empty($token['access_token'])) {
  http_response_code(400);
  exit('Token exchange failed');
}

// 4) Lấy userinfo
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

if (!$email || !$gid) {
  exit('Could not fetch Google profile');
}

// 5) Upsert vào DB (chuẩn hóa: bảng `users`, PK `user_id`)
try {
  $pdo->beginTransaction();

  $stmt = $pdo->prepare('SELECT user_id FROM users WHERE google_id = :gid OR email = :email LIMIT 1');
  $stmt->execute([':gid' => $gid, ':email' => $email]);
  $user = $stmt->fetch();

  if ($user) {
    $upd = $pdo->prepare('UPDATE users
                          SET google_id = :gid, user_name = :name, avatar = :avatar, last_login = NOW()
                          WHERE user_id = :uid');
    $upd->execute([
      ':gid' => $gid, ':name' => $name, ':avatar' => $avatar, ':uid' => $user['user_id']
    ]);
    $user_id = (int)$user['user_id'];
  } else {
    $ins = $pdo->prepare('INSERT INTO users (email, google_id, user_name, avatar, created_at, last_login)
                          VALUES (:email, :gid, :name, :avatar, NOW(), NOW())');
    $ins->execute([
      ':email' => $email, ':gid' => $gid, ':name' => $name, ':avatar' => $avatar
    ]);
    $user_id = (int)$pdo->lastInsertId();
  }

  $pdo->commit();
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  exit('DB error: ' . $e->getMessage());
}

// 6) Tạo session và chuyển hướng
$_SESSION['user_id'] = $user_id;
$_SESSION['email']   = $email;
$_SESSION['name']    = $name;

header('Location: /Webmarket/index.php');
exit;
