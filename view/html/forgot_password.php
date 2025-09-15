<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu</title>
    <link rel="stylesheet" href="../css/style_forgot.css">
    <link rel="stylesheet" href="../css/captcha.css">
</head>
<body>
    <section>
        <!-- background -->
        <img src="image/bg.jpg" class="bg" alt="background">
        <img src="image/trees.png" class="trees" alt="trees">
        <img src="image/girl.png" class="girl" alt="girl">

        <!-- falling leaves -->
        <div class="leaves">
            <div class="set">
                <div><img src="image/leaf_01.png" width="50"></div>
                <div><img src="image/leaf_02.png" width="50"></div>
                <div><img src="image/leaf_03.png" width="50"></div>
                <div><img src="image/leaf_04.png" width="50"></div>
                <div><img src="image/leaf_01.png" width="50"></div>
                <div><img src="image/leaf_02.png" width="50"></div>
                <div><img src="image/leaf_03.png" width="50"></div>
                <div><img src="image/leaf_04.png" width="50"></div>
            </div>
        </div>

        <!-- forgot form -->
        <div class="forgot">
            <h2>Quên mật khẩu</h2>
            <p>Nhập email của bạn để nhận liên kết đặt lại mật khẩu</p>

            <div class="inputBox">
                <input type="email" id="emailInput" placeholder="Email của bạn">
                <span id="errorMessage" class="error-message">Email/Số điện thoại không hợp lệ</span>
            </div>

            <div class="inputBox">
                <input type="button" id="btn" value="Gửi yêu cầu">
            </div>

            <div class="group">
                <a href="login.php">← Quay lại đăng nhập</a>
            </div>
        </div>

        <!-- Model cho CAPTCHA kéo trượt -->
        <div id="captchaModal" class="modal">
            <div class="modal-content">
                <a href="#" class="back-button">&larr;</a>
                <div class="modal-header">
                    <h3>Xác nhận để tiếp tục</h3>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="cursor: pointer; color: #888;" id="refreshCaptcha">&#x21bb;</span>
                        <span class="close-btn" id="closeModal">&times;</span>
                    </div>
                </div>
                <div class="captcha-box" id="captchaBox">
                    <div id="puzzleCutout" class="puzzle-cutout"></div>
                    <div id="puzzlePiece" class="puzzle-piece"></div>
                </div>
                <div class="drag-box" id="dragBox">
                    <p id="dragText">Kéo qua để hoàn thiện bức hình</p>
                    <div class="drag-slider" id="dragSlider">
                        <span>&#x27A4;</span>
                    </div>
                </div>
                <p style="font-size: 0.9em; color: #aaa; margin-top: 10px;">ID: Mtco4JZyeiZplil4ssvpx6KRKJVgIMmnw</p>
                <div id="captchaSuccess" class="success-message">Xác minh thành công!</div>
                <div id="captchaFailure" class="failure-message">Xác minh không thành công, hãy thử lại!</div>
            </div>
        </div>
    </section>

    <script src="../js/captcha.js"></script>
</body>
</html>
