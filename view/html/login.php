<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Glassmorphism Login Page</title>
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
                <h2>Sign In</h2>
                <div class="inputBox">
                    <input type="text" placeholder="Username"
                </div>
                <div class="inputBox">
                    <input type="password" placeholder="Password"
                </div>
                <div class="inputBox">
                    <input type="submit" value="Login" id="btn">
                </div>
                <div class="inputBox">
                    <input type="submit" value="Đăng Nhập bằng Google" href="/Webmarket/auth/google_login.php" id="btn">
                </div>

                <div class="group">
                    <a href="#">Forget Password</a>
                    <a href="#">Signup</a>
                </div>
            </div>
	</section>
</body>
</html>