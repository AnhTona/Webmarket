<?php
session_start();
$config = require __DIR__ . '/../config.php';

// tạo state để chống CSRF
$_SESSION['fb_oauth_state'] = bin2hex(random_bytes(16));

$params = [
  'client_id'     => $config['fb_app_id'],
  'redirect_uri'  => $config['fb_redirect_uri'],
  'state'         => $_SESSION['fb_oauth_state'],
  'response_type' => 'code',
  'scope'         => 'email,public_profile',
];

// redirect tới Facebook OAuth dialog
header('Location: https://www.facebook.com/v20.0/dialog/oauth?' . http_build_query($params));
exit;
