<?php
session_start();
require_once __DIR__ . '/../model/database.php';

$db  = new Database();
$pdo = $db->connect();

$config = require __DIR__ . '/../config.php';

// 1) Validate state
if (!isset($_GET['state']) || !hash_equals($_SESSION['fb_oauth_state'] ?? '', $_GET['state'])) {
  http_response_code(400);
  exit('Invalid state');
}
unset($_SESSION['fb_oauth_state']);

if (empty($_GET['code'])) {
  http_response_code(400);
  exit('Missing code');
}

// 2) Exchange code -> access_token
$tokenUrl = 'https://graph.facebook.com/v20.0/oauth/access_token';
$data = [
  'client_id'     => $config['fb_app_id'],
  'redirect_uri'  => $config['fb_redirect_uri'],
  'client_secret' => $config['fb_app_secret'],
  'code'          => $_GET['code'],
];

$ch = curl_init($tokenUrl);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST           => true,
  CURLOPT_POSTFIELDS     => http_build_query($data),
]);
$resp = curl_exec($ch);
if ($resp === false) exit('Curl error: ' . curl_error($ch));
curl_close($ch);

$token = json_decode($resp, true);
if (empty($token['access_token'])) {
  http_response_code(400);
  exit('Token exchange failed: ' . $resp);
}
$accessToken = $token['access_token'];

// 3) Lấy userinfo
$fields = 'id,name,email,picture.width(256)';
$profileUrl = 'https://graph.facebook.com/me?' . http_build_query([
  'fields'       => $fields,
  'access_token' => $accessToken,
]);

$ch = curl_init($profileUrl);
curl_setopt_array($ch, [ CURLOPT_RETURNTRANSFER => true ]);
$uResp = curl_exec($ch);
curl_close($ch);

$userinfo = json_decode($uResp, true);
$fid    = $userinfo['id'] ?? null;
$name   = $userinfo['name'] ?? null;
$email  = $userinfo['email'] ?? null; // có thể null nếu user không share email
$avatar = $userinfo['picture']['data']['url'] ?? null;

if (!$fid) exit('Could not fetch Facebook profile');

// 4) Upsert
try {
  $pdo->beginTransaction();

  if ($email) {
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE facebook_id = :fid OR email = :email LIMIT 1');
    $stmt->execute([':fid' => $fid, ':email' => $email]);
  } else {
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE facebook_id = :fid LIMIT 1');
    $stmt->execute([':fid' => $fid]);
  }
  $user = $stmt->fetch();

  if ($user) {
    $upd = $pdo->prepare('UPDATE users 
                          SET facebook_id = :fid, user_name = :name, avatar = :avatar 
                          WHERE user_id = :uid');
    $upd->execute([
      ':fid' => $fid, ':name' => $name, ':avatar' => $avatar, ':uid' => $user['user_id']
    ]);
    $user_id = (int)$user['user_id'];
  } else {
    $ins = $pdo->prepare('INSERT INTO users (email, facebook_id, user_name, avatar, created_at) 
                          VALUES (:email, :fid, :name, :avatar, NOW())');
    $ins->execute([
      ':email' => $email, ':fid' => $fid, ':name' => $name, ':avatar' => $avatar
    ]);
    $user_id = (int)$pdo->lastInsertId();
  }

  $pdo->commit();
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  exit('DB error: ' . $e->getMessage());
}

// 5) Session
$_SESSION['user_id'] = $user_id;
$_SESSION['email']   = $email; // có thể null
$_SESSION['name']    = $name;

header('Location: /Webmarket/index.php');
exit;
