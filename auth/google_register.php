<?php
session_start();
$config = require __DIR__ . '/../config.php';

// tạo state riêng cho register
$_SESSION['google_register_state'] = bin2hex(random_bytes(16));

$params = [
  'response_type' => 'code',
  'client_id'     => $config['google_client_id'],
  'redirect_uri'  => $config['google_register_redirect'], // URI callback riêng
  'scope'         => 'openid email profile',
  'state'         => $_SESSION['google_register_state'],
  'access_type'   => 'offline',
  'prompt'        => 'consent'
];

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
exit;
