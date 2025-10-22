<?php
// admin/controller/Product_Controller.php
require_once __DIR__ . '/../../model/database.php';

class Product_Controller
{
    public const UPLOAD_DIR = '/../../Webmarket/image';
    public const UPLOAD_URL = '/../../Webmarket/image';

    public static function handle(): array
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $action = $_GET['action'] ?? ($_POST['action'] ?? 'index');

        if ($method === 'POST' && in_array($action, ['create','update','save'], true)) {
            return self::save();
        }
        if ($method === 'GET' && $action === 'delete') {
            return self::delete();
        }
        if ($method === 'GET' && $action === 'get') {
            return self::getOne();
        }
        return self::index();
    }

    /* ===== DB Connect - OOP Version ===== */
    private static function getConnection(): mysqli
    {
        $db = Database::getInstance();
        return $db->getConnection();
    }

    /** Danh sách + tìm kiếm + lọc + phân trang */
    private static function index(): array
    {
        $conn = self::getConnection();

        $per_page = max(1, (int)($_GET['per_page'] ?? 5));
        $page     = max(1, (int)($_GET['page']     ?? 1));
        $search   = trim($_GET['search']   ?? '');

        $cat = $_GET['category'] ?? 'All';
        $promo    = trim($_GET['promo']    ?? '');

        $params = [];
        $wheres = [];

        if ($search !== '') {
            $wheres[] = 's.TenSanPham LIKE ?';
            $params[] = '%' . $search . '%';
        }

        if ($cat !== '' && $cat !== 'All') {
            if (ctype_digit((string)$cat)) {
                $wheres[] = 'dm.MaDanhMuc = ?';
                $params[] = (int)$cat;
            } else {
                $wheres[] = 'dm.TenDanhMuc = ?';
                $params[] = $cat;
            }
        }

        $promoJoin = "
            LEFT JOIN sanpham_khuyenmai kmsp ON kmsp.MaSanPham = s.MaSanPham
            LEFT JOIN khuyenmai km ON km.MaKhuyenMai = kmsp.MaKhuyenMai
                AND NOW() BETWEEN km.NgayBatDau AND km.NgayKetThuc
        ";

        if ($promo === '1') {
            $wheres[] = 'km.MaKhuyenMai IS NOT NULL';
        } elseif ($promo === '0') {
            $wheres[] = 'km.MaKhuyenMai IS NULL';
        }

        $whereSql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';

        $sql = "
            SELECT COUNT(DISTINCT s.MaSanPham) AS total
            FROM sanpham s
            LEFT JOIN sanpham_danhmuc sdm ON s.MaSanPham = sdm.MaSanPham
            LEFT JOIN danhmucsanpham   dm  ON sdm.MaDanhMuc = dm.MaDanhMuc
            $promoJoin
            $whereSql
        ";
        $total_products = (int) self::fetchValue($conn, $sql, $params);

        $total_pages = max(1, (int)ceil($total_products / $per_page));
        $page        = min($page, $total_pages);
        $offset      = ($page - 1) * $per_page;

        $sqlList = "
            SELECT
                s.MaSanPham,
                s.TenSanPham,
                s.Gia,
                s.GiaCu,
                s.HinhAnh,
                s.SoLuongTon,
                s.TrangThai,
                s.NgayTao,
                GREATEST(
                    s.IsPromo,
                    CASE WHEN EXISTS (
                        SELECT 1
                        FROM sanpham_khuyenmai skm
                        JOIN khuyenmai k ON k.MaKhuyenMai = skm.MaKhuyenMai
                        WHERE skm.MaSanPham = s.MaSanPham
                          AND k.TrangThai = 1
                          AND NOW() BETWEEN k.NgayBatDau AND k.NgayKetThuc
                    ) THEN 1 ELSE 0 END
                ) AS IsPromo,
                s.Loai AS subCategory,
                s.Popularity,
                s.NewProduct,
                COALESCE(GROUP_CONCAT(DISTINCT dm.TenDanhMuc SEPARATOR ', '), '-') AS TenDanhMuc
            FROM sanpham s
            LEFT JOIN sanpham_danhmuc sdm ON s.MaSanPham = sdm.MaSanPham
            LEFT JOIN danhmucsanpham   dm  ON sdm.MaDanhMuc = dm.MaDanhMuc
            $promoJoin
            $whereSql
            GROUP BY s.MaSanPham
            ORDER BY s.NgayTao DESC, s.MaSanPham DESC
            LIMIT ?, ?
        ";

        $listParams = array_merge($params, [$offset, $per_page]);
        $product_list_paginated = self::fetchAll($conn, $sqlList, $listParams) ?? [];

        foreach ($product_list_paginated as &$p) {
            $p['DanhMuc'] = $p['TenDanhMuc'];
        }

        $category_options = self::fetchAll($conn, "SELECT TenDanhMuc FROM danhmucsanpham ORDER BY TenDanhMuc");

        $notifications = [];
        $notification_count = 0;

        return compact(
            'product_list_paginated','total_products','per_page','page','total_pages',
            'notifications','notification_count',
            'cat','promo','category_options'
        );
    }

    /** Thêm/Sửa sản phẩm */
    private static function save(): array
    {
        $conn = self::getConnection();

        $id       = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
        $name     = trim($_POST['name'] ?? '');
        $price    = (float)($_POST['price'] ?? 0);
        $oldPrice = ($_POST['old-price'] ?? '') !== '' ? (float)$_POST['old-price'] : null;
        $desc     = trim($_POST['description'] ?? '');
        $qty      = (int)($_POST['quantity'] ?? 0);
        $cat      = trim($_POST['category'] ?? '');
        $promo    = isset($_POST['promo']) ? (int)$_POST['promo'] : 0;

        if ($name === '' || $price < 0) {
            return self::respond(false, 'Tên hoặc giá không hợp lệ!');
        }

        // Xử lý ảnh
        $imgCurrent = trim($_POST['image_old'] ?? '');
        $imgPath    = $imgCurrent;

        if (isset($_FILES['image']) && is_array($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = str_replace('\\', '/', realpath(__DIR__ . '/../../')) . '/image/';
            if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);

            $appBase = rtrim(
                preg_replace('#/admin(?:/.*)?$#', '', str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']))),
                '/'
            );
            $uploadUrl = $appBase . '/image/';

            $allow = ['jpg','jpeg','png','gif','webp'];
            $ext   = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allow, true)) {
                return self::respond(false, 'Chỉ cho phép JPG/JPEG/PNG/GIF/WebP');
            }

            $base = pathinfo($_FILES['image']['name'], PATHINFO_FILENAME);
            $slug = preg_replace('~[^a-z0-9._-]+~i', '-', $base);
            if ($slug === '') $slug = 'image';

            $file   = $slug . '.' . $ext;
            $target = $uploadDir . $file;

            if ($imgCurrent && strpos($imgCurrent, '/image/') !== false && basename($imgCurrent) !== $file) {
                @unlink($uploadDir . basename($imgCurrent));
            }

            if (is_file($target)) @unlink($target);

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                return self::respond(false, 'Không thể lưu ảnh mới');
            }

            $imgPath = $uploadUrl . $file;
        }

        // Lưu sản phẩm
        if ($id) {
            $ok = self::exec(
                $conn,
                "UPDATE sanpham
                 SET TenSanPham=?, Gia=?, GiaCu=?, MoTa=?, SoLuongTon=?, HinhAnh=?, IsPromo=?
                 WHERE MaSanPham=?",
                [$name, $price, $oldPrice, $desc, $qty, $imgPath, $promo, $id]
            );
            if (!$ok) return self::respond(false, 'Cập nhật sản phẩm thất bại!');
        } else {
            $ok = self::exec(
                $conn,
                "INSERT INTO sanpham (TenSanPham, Gia, GiaCu, MoTa, HinhAnh, SoLuongTon, TrangThai, IsPromo)
                 VALUES (?,?,?,?,?,?,1,?)",
                [$name, $price, $oldPrice, $desc, $imgPath, $qty, $promo]
            );
            if (!$ok) return self::respond(false, 'Thêm sản phẩm thất bại!');
            $id = (int)$conn->insert_id;
        }

        // Gán danh mục
        if ($cat !== '') {
            $dmId = self::ensureCategory($conn, $cat);
            if ($dmId) {
                self::exec($conn, "DELETE FROM sanpham_danhmuc WHERE MaSanPham=?", [$id]);
                self::exec($conn, "INSERT INTO sanpham_danhmuc (MaSanPham, MaDanhMuc) VALUES (?,?)", [$id, $dmId]);
            }
        }

        return self::respond(true, 'Lưu sản phẩm thành công!', [
            'id'      => $id,
            'product' => [
                'MaSanPham'  => $id,
                'TenSanPham' => $name,
                'Gia'        => $price,
                'GiaCu'      => $oldPrice,
                'MoTa'       => $desc,
                'SoLuongTon' => $qty,
                'IsPromo'    => $promo,
                'HinhAnh'    => $imgPath,
            ]
        ]);
    }

    /** Xoá sản phẩm */
    private static function delete(): array
    {
        $conn = self::getConnection();
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) return self::respond(false, 'Thiếu mã sản phẩm!');
        $ok = self::exec($conn, "DELETE FROM sanpham WHERE MaSanPham=?", [$id]);
        if (!$ok) return self::respond(false, 'Xoá thất bại!');
        return self::respond(true, 'Đã xoá sản phẩm!');
    }

    /** Đảm bảo có danh mục */
    private static function ensureCategory(mysqli $conn, string $name): ?int
    {
        $name = trim($name);
        $row = self::fetchOne($conn, "SELECT MaDanhMuc FROM danhmucsanpham WHERE TenDanhMuc=?", [$name]);
        if ($row) return (int)$row['MaDanhMuc'];
        if (!self::exec($conn, "INSERT INTO danhmucsanpham (TenDanhMuc) VALUES (?)", [$name])) return null;
        return (int)$conn->insert_id;
    }

    /** Get one product */
    private static function getOne(): array
    {
        $conn = self::getConnection();
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) return self::respond(false, 'Thiếu id');

        $sql = "SELECT
              s.MaSanPham,
              s.TenSanPham,
              s.Gia,
              s.GiaCu,
              s.SoLuongTon,
              s.HinhAnh,
              IFNULL(s.IsPromo, 0)           AS IsPromo,
              IFNULL(dm.TenDanhMuc, '')       AS TenDanhMuc
            FROM sanpham s
            LEFT JOIN sanpham_danhmuc spdm ON spdm.MaSanPham = s.MaSanPham
            LEFT JOIN danhmucsanpham dm          ON dm.MaDanhMuc   = spdm.MaDanhMuc
            WHERE s.MaSanPham = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) return self::respond(false, 'DB error');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if (!$res) return self::respond(false, 'Không tìm thấy sản phẩm');

        return self::respond(true, 'OK', ['product' => $res]);
    }

    /* ================== Helper DB ================== */
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
        $stmt->bind_param($types, ...$bind);
    }

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