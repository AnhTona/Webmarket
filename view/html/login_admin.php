<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Register Page</title>
	<link rel="stylesheet" href="../css/style_login_admin.css">
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
<<<<<<< HEAD:view/html/register.php
        <form method="POST" action="/Webmarket/controller/controller_register.php"></form>
=======

        <div id="register-alert" class="alert" style="display:none"></div>

        <form method="POST" action="/controller/controller_login_admin.php">
>>>>>>> 3f8c1bd (update google and facebook):view/html/login_admin.php
            <div class="register">
                <h2>Đăng Ký</h2>
                <div class="inputBox">
                    <form method="get" action="/Webmarket/auth/google_register.php">
                        <input type="submit" class="btn-google" value="Đăng ký bằng Google" id="btn">
                    </form>
                </div>
                <div class="group">
                    <a href="/Webmarket/view/html/login.php">Đăng Nhập</a>
                </div>
            </div>
        </form>
	</section>
    <!-- liên kết JS kiểm tra -->
    <script src="/Webmarket/assets/js/register.js"></script>
</body>
</html>
