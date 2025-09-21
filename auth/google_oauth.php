<?php
$config = require __DIR__ . '/config.php';

$action = $_GET['action'] ?? 'login';
$redirect = $action === 'register'
    ? $config['google_register_redirect']
    : $config['google_redirect_uri'];

$params = [
    'client_id'     => $config['google_client_id'],
    'redirect_uri'  => $redirect,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'access_type'   => 'offline',
    'prompt'        => 'consent'
];

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
exit;
