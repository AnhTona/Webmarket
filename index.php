<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: /Webmarket/view/html/login.php'); // hoặc tùy flow
  exit;
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Home</title></head>
<body>
  <h1>Xin chào, <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></h1>
  <p>Đăng nhập OK.</p>
</body>
</html>
