<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <title>Login Page</title>
    <!-- ✅ THÊM /Webmarket/ -->
    <link rel="stylesheet" href="/Webmarket/view/css/style_login.css" />
</head>
<body>
<section>
    <div class="leaves">
        <div class="set">
            <div><img src="/Webmarket/image/leaf-01.webp" /></div>
            <div><img src="/Webmarket/image/leaf-02.webp" /></div>
            <div><img src="/Webmarket/image/leaf-03.webp" /></div>
            <div><img src="/Webmarket/image/leaf-04.webp" /></div>
            <div><img src="/Webmarket/image/leaf-01.webp" /></div>
            <div><img src="/Webmarket/image/leaf-02.webp" /></div>
            <div><img src="/Webmarket/image/leaf-03.webp" /></div>
            <div><img src="/Webmarket/image/leaf-04.webp" /></div>
        </div>
    </div>

    <img src="/Webmarket/image/bg.webp" class="bg" />
    <img src="/Webmarket/image/girl.webp" class="girl" />
    <img src="/Webmarket/image/trees.webp" class="trees" />

    <div class="login">
        <h2>Đăng nhập</h2>

        <!-- Google -->
        <div class="inputBox">
            <form method="POST" action="/Webmarket/controller/google_login.php">
                <input type="submit" class="btn-google" value="Đăng nhập bằng Google" id="btn">
            </form>
        </div>

        <!-- Facebook -->
        <div class="inputBox">
            <form method="POST" action="/Webmarket/controller/facebook_login.php">
                <input type="submit" id="btn" class="btn-facebook" value="Đăng nhập bằng Facebook">
            </form>
        </div>
    </div>
</section>
</body>
</html>