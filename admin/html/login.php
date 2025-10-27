<?php
require_once __DIR__ . '/config.php';
// ✅ THÊM LOGIC KIỂM TRA NẾU ĐÃ LOGIN THÌ REDIRECT ĐẾN DASHBOARD
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    header("Location: dashboard.php");
    exit();
}

require_once __DIR__ . '/../controller/AuthController.php';
$ctx = AuthController::handle();
$error = $ctx['error'] ?? null;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập Admin - Hương Trà</title>
    <link rel="stylesheet" href="../css/base.css">
    <link rel="stylesheet" href="/../Webmarket/view/css/home.css">
    <link rel="stylesheet" href="../css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
</head>
<body>
<div class="login-container">
    <div class="logo">
        <img src="../../image/logo.webp" alt="Logo Hương Trà">
    </div>
    <h2>Đăng Nhập Quản Trị</h2>

    <?php if (isset($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form class="login-form" action="" method="POST">
        <input type="text" name="username" placeholder="Tên đăng nhập" required>
        <input type="password" name="password" placeholder="Mật khẩu" required>
        <button type="submit">Đăng Nhập <i class="fas fa-sign-in-alt"></i></button>
        <a href="forgot_password.php" class="forgot-password">Quên mật khẩu?</a>
    </form>
</div>
</body>
</html>