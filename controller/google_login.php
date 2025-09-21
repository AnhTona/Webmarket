<?php
declare(strict_types=1);
session_start();

require_once __DIR__.'/controller_auth.php';
require_once __DIR__.'/../auth/google_auth.php';

if (!isset($db) || !($db instanceof PDO)) { $db = (new Database())->connect(); }

$action = $_GET['action'] ?? 'start';

if ($action === 'start') {
  header('Location: '.g_auth_url()); exit;
}
if ($action === 'callback') {
  if (!isset($_GET['state'], $_SESSION['g_state']) || $_GET['state'] !== $_SESSION['g_state']) {
    $_SESSION['error']='Sai state (CSRF).'; header('Location: /Webmarket/view/html/login.php'); exit;
  }
  unset($_SESSION['g_state']);
  if (!isset($_GET['code'])) { $_SESSION['error']='Thiếu code.'; header('Location: /Webmarket/view/html/login.php'); exit; }

  $tok = g_exchange_token($_GET['code']);
  if (!$tok || empty($tok['access_token'])) { $_SESSION['error']='Đổi token thất bại.'; header('Location: /Webmarket/view/html/login.php'); exit; }

  $u = g_userinfo($tok['access_token']);
  if (!$u || empty($u['email'])) { $_SESSION['error']='Không lấy được email Google.'; header('Location: /Webmarket/view/html/login.php'); exit; }

  handleOAuthLogin($db, [
    'id'       => $u['sub']   ?? '',
    'email'    => $u['email'] ?? null,
    'name'     => $u['name']  ?? '',
    'provider' => 'google',
  ]);
  exit;
}
header('Location: /Webmarket/controller/google_login.php?action=start'); exit;
