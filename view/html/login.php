<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <title>Login Page</title>
    <link rel="stylesheet" href="../css/style_login.css" />
</head>
<body>
<section>
    <div class="leaves">
        <div class="set">
            <div><img src="image/leaf_01.png" /></div>
            <div><img src="image/leaf_02.png" /></div>
            <div><img src="image/leaf_03.png" /></div>
            <div><img src="image/leaf_04.png" /></div>
            <div><img src="image/leaf_01.png" /></div>
            <div><img src="image/leaf_02.png" /></div>
            <div><img src="image/leaf_03.png" /></div>
            <div><img src="image/leaf_04.png" /></div>
        </div>
    </div>

    <img src="image/bg.jpg" class="bg" />
    <img src="image/girl.png" class="girl" />
    <img src="image/trees.png" class="trees" />

    <div class="login">
        <h2>Đăng nhập</h2>

        <!-- Google -->
        <div class="inputBox">
            <form method="GET" action="/Webmarket/controller/google_login.php">
                <input type="submit" class="btn-google" value="Đăng nhập bằng Google" id="btn">
            </form>
        </div>

        <!-- Facebook -->
        <div class="inputBox">
            <form method="GET" action="/Webmarket/controller/facebook_login.php">
                <input type="submit" id="btn" class="btn-facebook" value="Đăng nhập bằng Facebook">
            </form>
        </div>
    </div>
</section>
</body>
</html>