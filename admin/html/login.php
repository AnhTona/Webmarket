<?php
require_once __DIR__ . '/../controller/controller_admin_login.php';
$ctx = AuthController::handle();
$error = $ctx['error'] ?? null;
?>

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập Admin - Hương Trà</title>
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <style>
        body {
            background: #fffaf3;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            overflow: hidden;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .login-container .logo img {
            height: 60px;
            margin-bottom: 20px;
        }
        .login-container h2 {
            font-family: 'Playfair Display', serif;
            color: #8f2c24;
            margin-bottom: 20px;
            font-size: 1.8em;
        }
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .login-form input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            outline: none;
        }
        .login-form input:focus {
            border-color: #8f2c24;
        }
        .login-form button {
            background: #8f2c24;
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background 0.3s ease;
        }
        .login-form button:hover {
            background: #4d0702;
        }
        .error {
            color: #e74c3c;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        .forgot-password {
            color: #8f2c24;
            text-decoration: none;
            font-size: 0.9em;
        }
        .forgot-password:hover {
            text-decoration: underline;
        }
        @media (max-width: 576px) {
            .login-container {
                padding: 20px;
                margin: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="../image/logo.png" alt="Logo Hương Trà">
        </div>
        <h2>Đăng Nhập Quản Trị</h2>

        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
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
