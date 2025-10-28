<?php
// admin/html/logout.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hủy tất cả session
$_SESSION = [];

// Hủy session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hủy session
session_destroy();

// Chuyển về login
header('Location: login.php');
exit;