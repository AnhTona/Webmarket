<?php
declare(strict_types=1);

use Webmarket\model\Database;

session_start();

require_once __DIR__.'/controller_auth.php';
require_once __DIR__.'/../auth/facebook_auth.php';

if (!isset($db) || !($db instanceof PDO)) { $db = (new Database())->connect(); }

$action = $_GET['action'] ?? 'start';

if ($action === 'start') {
  header('Location: '.fb_auth_url()); exit;
}
if ($action === 'callback') {
  if (!isset($_GET['state'], $_SESSION['fb_state']) || $_GET['state'] !== $_SESSION['fb_state']) {
    $_SESSION['error']='Sai state (CSRF).'; header('Location: /Webmarket/view/html/login.php'); exit;
  }
  unset($_SESSION['fb_state']);
  if (!isset($_GET['code'])) { $_SESSION['error']='Thiếu code.'; header('Location: /Webmarket/view/html/login.php'); exit; }

  $tok = fb_exchange_token($_GET['code']);
  if (!$tok || empty($tok['access_token'])) { $_SESSION['error']='Đổi token thất bại.'; header('Location: /Webmarket/view/html/login.php'); exit; }

  $u = fb_userinfo($tok['access_token']);
  if (!$u || empty($u['id'])) { $_SESSION['error']='Không lấy được user Facebook.'; header('Location: /Webmarket/view/html/login.php'); exit; }

  handleOAuthLogin($db, [
    'id'       => $u['id'],
    'email'    => $u['email'] ?? null,   // FB có thể không trả email
    'name'     => $u['name']  ?? '',
    'provider' => 'facebook',
  ]);
  exit;
}
header('Location: /Webmarket/controller/facebook_login.php?action=start'); exit;
