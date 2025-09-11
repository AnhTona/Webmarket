<?php
    function check_login($username, $password) {
        $error = [];
        $username = htmlspecialchars(trim($username));
        $password = trim($password);

        if($_SERVER["REQUEST_METHOD"] == "POST") {
            // kiểm tra tên đăng nhập và mật khẩu và email
            if(empty($_POST["username"]) || empty($_POST["password"])) {
                error_log("Vui lòng kiểm tra lại tên đăng nhập và mật khẩu!!!");
                return $error;
            }
            else{
                return[
                    'model' => ['username' => $username, 'password' => $password],
                ];
            }
        }
    }