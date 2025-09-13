<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Login Page</title>
	<link rel="stylesheet" href="../css/style_login.css">
</head>
<body>
	<section>
		<div class="leaves">
			<div class="set">
				<div><img src="image/leaf_01.png"></div>
				<div><img src="image/leaf_02.png"></div>
				<div><img src="image/leaf_03.png"></div>
				<div><img src="image/leaf_04.png"></div>
				<div><img src="image/leaf_01.png"></div>
				<div><img src="image/leaf_02.png"></div>
				<div><img src="image/leaf_03.png"></div>
				<div><img src="image/leaf_04.png"></div>
			</div>
		</div>
		<img src="image/bg.jpg" class="bg">
		<img src="image/girl.png" class="girl">
		<img src="image/trees.png" class="trees">
        <form method="POST" action="/controller/controller_login.php"></form>
            <div class="login">
                <h2>Đăng Nhập</h2>
                <div class="inputBox">
                    <input type="text" placeholder="Tên đăng nhập..."
                </div>
                <div class="inputBox">
                    <input type="password" placeholder="Mật khẩu..."
                </div>
                <div class="inputBox">
                    <input type="submit" value="Đăng nhập" id="btn">
                </div>
                <div class="inputBox">
                    <input type="submit" value="Đăng nhập bằng Google" href="/Webmarket/auth/google_login.php" id="btn">
                </div>

                <div class="group">
                   <a href="/Webmarket/view/html/forgot_password.php">Quên mật khẩu?</a>
                    <a href="/Webmarket/view/html/register.php">Đăng Ký</a>
                </div>
            </div>
	</section>
</body>
</html>