<?php
require_once __DIR__ . '/../../model/database.php';

class AuthController
{
    public static function handle(): array
    {
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if (empty($username) || empty($password)) {
                $error = 'Vui lòng nhập đầy đủ thông tin!';
            } else {
                $db = Database::getInstance();
                $conn = $db->getConnection();

                // Lấy thông tin user
                $stmt = $conn->prepare("SELECT MaNguoiDung, Username, HoTen, Email, MatKhau, VaiTro, TrangThai 
                                        FROM nguoidung 
                                        WHERE Username = ? 
                                        LIMIT 1");
                $stmt->bind_param('s', $username);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $stmt->close();

                if (!$user) {
                    $error = 'Tên đăng nhập không tồn tại!';
                } elseif ($user['TrangThai'] != 1) {
                    $error = 'Tài khoản đã bị khóa!';
                } else {
                    // Kiểm tra mật khẩu - Hỗ trợ cả plain text và hash
                    $isPasswordCorrect = false;

                    if (password_verify($password, $user['MatKhau'])) {
                        $isPasswordCorrect = true;
                    } elseif ($password === $user['MatKhau']) {
                        $isPasswordCorrect = true;

                        // Tự động mã hóa mật khẩu plain text
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $updateStmt = $conn->prepare("UPDATE nguoidung SET MatKhau = ? WHERE MaNguoiDung = ?");
                        $updateStmt->bind_param('si', $hashedPassword, $user['MaNguoiDung']);
                        $updateStmt->execute();
                        $updateStmt->close();
                    }

                    if (!$isPasswordCorrect) {
                        $error = 'Mật khẩu không đúng!';
                    } elseif (!in_array($user['VaiTro'], ['ADMIN', 'STAFF'])) {
                        $error = 'Bạn không có quyền truy cập trang quản trị!';
                    } else {
                        // ✅ LƯU SESSION - THỐNG NHẤT TÊN BIẾN
                        $_SESSION['user_id'] = $user['MaNguoiDung'];
                        $_SESSION['username'] = $user['Username'];
                        $_SESSION['user_name'] = $user['HoTen'];
                        $_SESSION['user_email'] = $user['Email'];
                        $_SESSION['role'] = $user['VaiTro']; // ✅ ĐỔI TỪ user_role → role
                        $_SESSION['user_role'] = $user['VaiTro']; // ✅ GIỮ CẢ 2 ĐỂ TƯ TƯỞNG
                        $_SESSION['logged_in'] = true;

                        // Chuyển hướng
                        if ($user['VaiTro'] === 'ADMIN') {
                            header('Location: dashboard.php');
                        } else {
                            header('Location: orders.php');
                        }
                        exit;
                    }
                }
            }
        }

        return ['error' => $error];
    }
}