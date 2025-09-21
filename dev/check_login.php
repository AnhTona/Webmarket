<?php
// /Webmarket/dev/check_login.php (ví dụ)
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: /Webmarket/view/html/login.php');
  exit;
}
