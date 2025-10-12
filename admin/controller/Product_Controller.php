<?php
// admin/controller/Product_Controller.php
// KHÔNG dùng framework/router

class Product_Controller
{
    public const UPLOAD_DIR = '/../uploads/products'; // thư mục vật lý: admin/uploads/products
    public const UPLOAD_URL = 'uploads/products';     // URL tương đối để <img src="...">

    public static function handle(): array
    {
        $conn = self::connect();

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $action = $_GET['action'] ?? ($_POST['action'] ?? 'index');

        if ($method === 'POST' && in_array($action, ['create','update','save'], true)) {
            return self::save($conn);   // sẽ JSON hoặc redirect rồi exit
        }
        if ($method === 'GET' && $action === 'delete') {
            return self::delete($conn); // sẽ JSON hoặc redirect rồi exit
        }
        return self::index($conn);      // trả dữ liệu cho view
    }

    /** Kết nối DB: ưu tiên file kết nối sẵn (model/database.php hoặc db.php) */
    private static function connect(): mysqli
    {
        $candidates = [
            dirname(__DIR__)    . '/model/db.php',
            dirname(__DIR__,2)  . '/model/db.php',
        ];
        foreach ($candidates as $f) {
            if (is_file($f)) { include_once $f; }
        }

        if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
            $GLOBALS['conn']->set_charset('utf8mb4');
            return $GLOBALS['conn'];
        }

        // Fallback dev – đổi thông số cho khớp máy bạn nếu cần
        $conn = @new mysqli('127.0.0.1', 'root', '', 'webmarket');
        if ($conn->connect_error) {
            http_response_code(500);
            die('Không kết nối được MySQL: ' . $conn->connect_error);
        }
        $conn->set_charset('utf8mb4');
        return $conn;
    }

    /** Danh sách + tìm kiếm + lọc + phân trang */
    private static function index(mysqli $conn): array
    {
        $per_page = max(1, (int)($_GET['per_page'] ?? 5));
        $page     = max(1, (int)($_GET['page']     ?? 1));
        $search   = trim($_GET['search']   ?? '');

        // Bộ lọc từ UI (không đổi layout)
        $category = $_GET['category'] ?? 'All'; // 'All' | 'Trà' | 'Bánh' | ...
        $promo    = $_GET['promo']    ?? 'All'; // 'All' | '1' (Có) | '0' (Không)

        $params = [];
        $wheres = [];

        if ($search !== '') {
            $wheres[] = 'sp.TenSanPham LIKE ?';
            $params[] = '%' . $search . '%';
        }
        if ($category !== 'All') {
            $wheres[] = 'dm.TenDanhMuc = ?';
            $params[] = $category;
        }

        // Join khuyến mãi còn hiệu lực để suy ra cờ isPromo
        $promoJoin = "
            LEFT JOIN sanpham_khuyenmai spkm ON sp.MaSanPham = spkm.MaSanPham
            LEFT JOIN khuyenmai km ON spkm.MaKhuyenMai = km.MaKhuyenMai
               AND km.TrangThai = 1
               AND (km.NgayBatDau IS NULL OR km.NgayBatDau <= CURRENT_DATE())
               AND (km.NgayKetThuc IS NULL OR km.NgayKetThuc >= CURRENT_DATE())
        ";

        if ($promo === '1') {        // Chỉ sản phẩm đang có khuyến mãi
            $wheres[] = 'km.MaKhuyenMai IS NOT NULL';
        } elseif ($promo === '0') {  // Không có khuyến mãi
            $wheres[] = 'km.MaKhuyenMai IS NULL';
        }

        $whereSql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';

        // Tổng hàng (DISTINCT tránh đếm trùng do JOIN)
        $sqlCount = "
            SELECT COUNT(DISTINCT sp.MaSanPham) AS total
            FROM sanpham sp
            LEFT JOIN sanpham_danhmuc spdm ON sp.MaSanPham = spdm.MaSanPham
            LEFT JOIN danhmucsanpham dm     ON spdm.MaDanhMuc = dm.MaDanhMuc
            $promoJoin
            $whereSql
        ";
        $total_products = (int) self::fetchValue($conn, $sqlCount, $params);

        $total_pages = max(1, (int)ceil($total_products / $per_page));
        $page        = min($page, $total_pages);
        $offset      = ($page - 1) * $per_page;

        // Danh sách trang hiện tại
        $sqlList = "
            SELECT
                sp.MaSanPham,
                sp.TenSanPham,
                sp.Gia,
                sp.HinhAnh,
                sp.MoTa,
                sp.SoLuongTon,
                COALESCE(dm.TenDanhMuc, '-') AS TenDanhMuc,
                (km.MaKhuyenMai IS NOT NULL)  AS isPromo
            FROM sanpham sp
            LEFT JOIN sanpham_danhmuc spdm ON sp.MaSanPham = spdm.MaSanPham
            LEFT JOIN danhmucsanpham dm     ON spdm.MaDanhMuc = dm.MaDanhMuc
            $promoJoin
            $whereSql
            GROUP BY sp.MaSanPham
            ORDER BY sp.NgayTao DESC, sp.MaSanPham DESC
            LIMIT ?, ?
        ";
        $listParams = array_merge($params, [$offset, $per_page]);
        $product_list_paginated = self::fetchAll($conn, $sqlList, $listParams) ?? [];

        foreach ($product_list_paginated as &$p) {
            $p['DanhMuc'] = $p['TenDanhMuc'];
            // Nếu HinhAnh là đường dẫn tương đối, cứ để nguyên (uploads/products/...)
        }

        // (tuỳ chọn) danh mục để dùng nếu cần render từ DB
        $category_options = self::fetchAll($conn, "SELECT TenDanhMuc FROM danhmucsanpham ORDER BY TenDanhMuc");

        // Notifications tránh undefined
        $notifications = [];
        $notification_count = 0;

        return compact(
            'product_list_paginated','total_products','per_page','page','total_pages',
            'notifications','notification_count',
            'category','promo','category_options'
        );
    }

    /** Thêm/Sửa sản phẩm */
    private static function save(mysqli $conn): array
    {
        $id    = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
        $name  = trim($_POST['name']  ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $desc  = trim($_POST['description'] ?? '');
        $qty   = (int)($_POST['quantity'] ?? 0);
        $cat   = trim($_POST['category']   ?? '');

        if ($name === '' || $price < 0) {
            return self::respond(false, 'Tên hoặc giá không hợp lệ!');
        }

        // Upload ảnh (nếu có)
        $imgPath = null;
        if (!empty($_FILES['image']['name'])) {
            $up = self::handleUpload($_FILES['image']);
            if (!$up['ok']) return self::respond(false, $up['message']);
            $imgPath = $up['path']; // uploads/products/xxx.jpg
        }

        if ($id) {
            if ($imgPath !== null) {
                $ok = self::exec($conn,
                    "UPDATE sanpham SET TenSanPham=?, Gia=?, MoTa=?, SoLuongTon=?, HinhAnh=? WHERE MaSanPham=?",
                    [$name,$price,$desc,$qty,$imgPath,$id]
                );
            } else {
                $ok = self::exec($conn,
                    "UPDATE sanpham SET TenSanPham=?, Gia=?, MoTa=?, SoLuongTon=? WHERE MaSanPham=?",
                    [$name,$price,$desc,$qty,$id]
                );
            }
            if (!$ok) return self::respond(false, 'Cập nhật sản phẩm thất bại!');
        } else {
            $ok = self::exec($conn,
                "INSERT INTO sanpham (TenSanPham, Gia, MoTa, HinhAnh, SoLuongTon, TrangThai) VALUES (?,?,?,?,?,1)",
                [$name,$price,$desc,$imgPath,$qty]
            );
            if (!$ok) return self::respond(false, 'Thêm sản phẩm thất bại!');
            $id = (int)$conn->insert_id;
        }

        // Gắn danh mục 1-1 theo tên từ dropdown
        if ($cat !== '') {
            $dmId = self::ensureCategory($conn, $cat);
            if ($dmId) {
                self::exec($conn, "DELETE FROM sanpham_danhmuc WHERE MaSanPham=?", [$id]);
                self::exec($conn, "INSERT INTO sanpham_danhmuc (MaSanPham, MaDanhMuc) VALUES (?,?)", [$id,$dmId]);
            }
        }

        return self::respond(true, 'Lưu sản phẩm thành công!', ['id'=>$id]);
    }

    /** Xoá sản phẩm */
    private static function delete(mysqli $conn): array
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) return self::respond(false, 'Thiếu mã sản phẩm!');
        $ok = self::exec($conn, "DELETE FROM sanpham WHERE MaSanPham=?", [$id]);
        if (!$ok) return self::respond(false, 'Xoá thất bại!');
        return self::respond(true, 'Đã xoá sản phẩm!');
    }

    /** Đảm bảo có danh mục, trả về MaDanhMuc */
    private static function ensureCategory(mysqli $conn, string $name): ?int
    {
        $name = trim($name);
        $row = self::fetchOne($conn, "SELECT MaDanhMuc FROM danhmucsanpham WHERE TenDanhMuc=?", [$name]);
        if ($row) return (int)$row['MaDanhMuc'];
        if (!self::exec($conn, "INSERT INTO danhmucsanpham (TenDanhMuc) VALUES (?)", [$name])) return null;
        return (int)$conn->insert_id;
    }

    /** Upload ảnh an toàn */
    private static function handleUpload(array $file): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['ok'=>false,'message'=>'Upload ảnh lỗi (code '.$file['error'].')'];
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']); finfo_close($finfo);
        if (!in_array($mime, ['image/jpeg','image/png','image/webp','image/gif'], true)) {
            return ['ok'=>false,'message'=>'Chỉ cho phép JPEG/PNG/WebP/GIF'];
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) ?: 'jpg';
        $basename = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

        $dir = __DIR__ . self::UPLOAD_DIR;
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
        $dest = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $basename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['ok'=>false,'message'=>'Không thể lưu file ảnh'];
        }
        return ['ok'=>true,'path'=> rtrim(self::UPLOAD_URL,'/\\') . '/' . $basename];
    }

    /* ================== Helper DB (MySQLi + prepared) ================== */
    private static function fetchValue(mysqli $conn, string $sql, array $params=[]){
        $r=self::fetchOne($conn,$sql,$params); return $r?array_values($r)[0]:null;
    }
    private static function fetchOne(mysqli $conn, string $sql, array $params=[]): ?array {
        $rows=self::fetchAll($conn,$sql,$params); return $rows[0]??null;
    }
    private static function fetchAll(mysqli $conn, string $sql, array $params=[]): array {
        $stmt=$conn->prepare($sql); if(!$stmt) return [];
        if ($params) self::bindParams($stmt,$params);
        $stmt->execute(); $res=$stmt->get_result(); $rows=$res?$res->fetch_all(MYSQLI_ASSOC):[];
        $stmt->close(); return $rows;
    }
    private static function exec(mysqli $conn, string $sql, array $params=[]): bool {
        $stmt=$conn->prepare($sql); if(!$stmt) return false;
        if ($params) self::bindParams($stmt,$params);
        $ok=$stmt->execute(); $stmt->close(); return (bool)$ok;
    }
    private static function bindParams(mysqli_stmt $stmt, array $params): void {
        $types=''; $bind=[];
        foreach($params as $p){ $types .= is_int($p)?'i':(is_float($p)?'d':'s'); $bind[]=$p; }
        $stmt->bind_param($types, ...self::ref($bind));
    }
    private static function ref(array $a): array { foreach($a as $k=>$v){ $a[$k]=&$a[$k]; } return $a; }

    /* ================== Response ================== */
    private static function isAjax(): bool {
        return (($_GET['ajax'] ?? '') === '1') ||
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])==='xmlhttprequest');
    }
    private static function respond(bool $ok, string $message, array $extra=[]): array
    {
        $payload = array_merge(['ok'=>$ok,'message'=>$message], $extra);
        if (self::isAjax()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($payload, JSON_UNESCAPED_UNICODE);
            exit;
        }
        header('Location: products.php?'. ($ok ? 'success=' : 'error=') . urlencode($message));
        exit;
    }
}
