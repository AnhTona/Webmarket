<?php
session_start();
$config = require __DIR__ . '/../config.php';

// Tạo state chống CSRF
$_SESSION['oauth2_state'] = bin2hex(random_bytes(16));

// Tham số OAuth
$params = [
  'response_type' => 'code',
  'client_id'     => $config['google_client_id'],
  'redirect_uri'  => $config['google_redirect_uri'],
  'scope'         => 'openid email profile',
  'state'         => $_SESSION['oauth2_state'],
  'access_type'   => 'offline',
  'prompt'        => 'consent'
];

// Redirect sang Google
header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
exit;
