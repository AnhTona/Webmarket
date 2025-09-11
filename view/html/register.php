<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Register Page</title>
	<link rel="stylesheet" href="../css/style_register.css">
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
        <form method="POST" action="/controller/controller_register.php"></form>
            <div class="register">
                <h2>Đăng Ký</h2>
                <div class="inputBox">
                    <input type="text" placeholder="Tên Đăng Nhập..."
                </div>
                <div class="inputBox">
                    <input type="password" placeholder="Mật Khẩu..."
                </div>
                <div class="inputBox">
                    <input type="password" placeholder="Xác Nhận Mật Khẩu..."
                </div>
                <div class="inputBox">
                    <input type="submit" value="Đăng Ký" id="btn">
                </div>
                <div class="inputBox">
                    <input type="submit" value="Đăng ký bằng Google" href="/Webmarket/auth/google_register.php" id="btn">
                </div>
                <div class="group">
                    <a href="/Webmarket/view/html/login.php">Đăng Nhập</a>
                </div>
            </div>
	</section>
</body>
</html>