<?php
// Dùng chung cho Google & Facebook
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

/* load Database */
$paths = [ __DIR__.'/../model/database.php', __DIR__.'/../database.php' ];
foreach ($paths as $p) { if (file_exists($p)) { require_once $p; break; } }
if (!class_exists('Database')) { http_response_code(500); exit('database.php not found'); }

/* PDO dùng chung */
$db = $db ?? (new Database())->connect();

/* helpers */
function redirect(string $url): never { header("Location: $url"); exit; }
function flash(string $type, string $msg): void { $_SESSION[$type] = $msg; }
function random_password(int $len=24): string { return bin2hex(random_bytes((int)($len/2))); }
function make_unique_username(PDO $db, string $base): string {
  $base = preg_replace('/[^a-z0-9._-]+/i','', $base);
  $base = $base !== '' ? strtolower($base) : 'user';
  $u = $base; $i = 1;
  $st = $db->prepare("SELECT 1 FROM NguoiDung WHERE Username = ? LIMIT 1");
  while (true) { $st->execute([$u]); if (!$st->fetch()) return $u; $u = $base.$i; $i++; }
}

/**
 * Login/Auto-register qua OAuth.
 * $profile = ['id'=>..., 'email'=>?string, 'name'=>?string, 'provider'=>'google'|'facebook']
 */
function handleOAuthLogin(PDO $db, array $profile): never {
  $provider = strtolower((string)($profile['provider'] ?? 'oauth'));
  $extId    = (string)($profile['id'] ?? '');
  $email    = $profile['email'] ?? null;
  $name     = trim((string)($profile['name'] ?? ''));

  if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $email = null;
  if (!$email && $extId === '') { http_response_code(400); exit('OAuth profile thiếu email/id'); }

  // 1) tìm theo email trước
  $user = null;
  if ($email) {
    $st = $db->prepare("SELECT MaNguoiDung, Username, HoTen, Email, VaiTro, TrangThai
                        FROM NguoiDung WHERE Email = ? LIMIT 1");
    $st->execute([$email]); $user = $st->fetch(PDO::FETCH_ASSOC);
  }

  if ($user) {
    if (isset($user['TrangThai']) && (int)$user['TrangThai'] === 0) {
      flash('error','Tài khoản đã bị khóa.'); redirect('/Webmarket/view/html/login.php');
    }
    // optional: cập nhật tên nếu còn trống
    if ($name && empty($user['HoTen'])) {
      $upd = $db->prepare("UPDATE NguoiDung SET HoTen = ? WHERE MaNguoiDung = ?");
      $upd->execute([$name, (int)$user['MaNguoiDung']]);
      $user['HoTen'] = $name;
    }
    $_SESSION['user_id']  = (int)$user['MaNguoiDung'];
    $_SESSION['username'] = (string)$user['Username'];
    $_SESSION['email']    = $user['Email'] ?? null;
    $_SESSION['name']     = $user['HoTen'] ?? null;
    $_SESSION['role']     = $user['VaiTro'] ?? 'USER';
    redirect('/Webmarket/index.php');
  }

  // 2) chưa có -> INSERT
  $base     = $email ? explode('@',$email)[0] : ($provider.'_'.$extId);
  $username = make_unique_username($db, $base);
  $pwdHash  = password_hash(random_password(), PASSWORD_DEFAULT);
  $fullName = $name ?: $username;

  $ins = $db->prepare("INSERT INTO NguoiDung (Username, HoTen, Email, MatKhau, VaiTro, TrangThai, NgayTao)
                       VALUES (:u, :n, :e, :p, 'USER', 1, NOW())");
  $ins->execute([':u'=>$username, ':n'=>$fullName, ':e'=>$email, ':p'=>$pwdHash]);

  $_SESSION['user_id']  = (int)$db->lastInsertId();
  $_SESSION['username'] = $username;
  $_SESSION['email']    = $email;
  $_SESSION['name']     = $fullName;
  $_SESSION['role']     = 'USER';
  redirect('/Webmarket/index.php');
}
