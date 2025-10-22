<?php
declare(strict_types=1);

/**
 * Products_Filler_Controller.php
 * - KHỚP HOÀN TOÀN VỚI database.php (không sửa file khác)
 * - Mặc định trả tối đa 1000 sp để bạn phân trang ở JS
 * - Sửa cảnh báo: undefined $one + gọi static getConnection an toàn
 */

header('Content-Type: application/json; charset=UTF-8');
session_start();

/* =========================================================
 * 1) Nạp database.php (không đổi cấu trúc dự án của bạn)
 * ========================================================= */
function include_database_php(): void {
    $candidates = [
        __DIR__ . '/database.php',
        __DIR__ . '/../database.php',
        __DIR__ . '/../../database.php',
        __DIR__ . '/../../../database.php',
        dirname(__DIR__, 1) . '/model/database.php',
        dirname(__DIR__, 2) . '/model/database.php',
    ];
    foreach ($candidates as $p) {
        if (is_file($p)) { require_once $p; return; }
    }
}
include_database_php();

/* =========================================================
 * 2) Lấy kết nối mysqli từ database.php theo nhiều pattern
 * ========================================================= */
function resolve_mysqli(): mysqli {
    // 2.1) Class Database (Singleton)
    if (class_exists('Database')) {
        // Database::getInstance()->getConnection()
        if (method_exists('Database', 'getInstance')) {
            $inst = @Database::getInstance();
            if (is_object($inst)) {
                if (method_exists($inst, 'getConnection')) {
                    $c = @$inst->getConnection();
                    if ($c instanceof mysqli) return $c;
                }
                foreach (['conn','connection','db','mysqli'] as $prop) {
                    if (property_exists($inst, $prop)) {
                        $c = $inst->{$prop};
                        if ($c instanceof mysqli) return $c;
                    }
                }
            }
        }
        // Database::getConnection() (nếu là static thật sự)
        if (method_exists('Database', 'getConnection')) {
            try {
                $rm = new ReflectionMethod('Database', 'getConnection');
                if ($rm->isStatic()) {
                    $c = Database::getConnection();
                    if ($c instanceof mysqli) return $c;
                }
            } catch (Throwable $__) {
                // ignore
            }
        }
    }

    // 2.2) Biến global phổ biến
    if (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli'] instanceof mysqli) return $GLOBALS['mysqli'];
    if (isset($GLOBALS['conn'])   && $GLOBALS['conn']   instanceof mysqli) return $GLOBALS['conn'];

    // 2.3) Hằng số kết nối (nếu database.php define)
    $host = defined('DB_HOST') ? DB_HOST : null;
    $user = defined('DB_USER') ? DB_USER : null;
    $pass = defined('DB_PASS') ? DB_PASS : (defined('DB_PASSWORD') ? DB_PASSWORD : null);
    $name = defined('DB_NAME') ? DB_NAME : null;
    $port = defined('DB_PORT') ? (int)DB_PORT : 3306;

    if ($host && $user && $name !== null) {
        $c = @new mysqli($host, $user, $pass ?? '', $name, $port);
        if ($c->connect_errno === 0) return $c;
    }

    throw new RuntimeException('Không lấy được kết nối MySQL từ database.php.');
}

function ensure_charset(mysqli $db): void {
    @ $db->set_charset('utf8mb4');
}

/* =========================================================
 * 3) Helpers kiểm tra bảng/cột (không phụ thuộc schema cứng)
 * ========================================================= */
function table_exists(mysqli $db, string $table): bool {
    $sql  = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $one = 0; // tránh cảnh báo IDE
    $stmt->bind_result($one);
    $ok = $stmt->fetch();
    $stmt->close();
    return (bool)$ok;
}

function column_exists(mysqli $db, string $table, string $col): bool {
    $sql  = "SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('ss', $table, $col);
    $stmt->execute();
    $one = 0; // tránh cảnh báo IDE
    $stmt->bind_result($one);
    $ok = $stmt->fetch();
    $stmt->close();
    return (bool)$ok;
}

/* =========================================================
 * 4) Handler chính: trả JSON sản phẩm cho home
 *    (giữ logic đơn giản, bạn phân trang ở JS)
 * ========================================================= */
try {
    $db = resolve_mysqli();
    ensure_charset($db);

    // Mặc định trả 1000 sp để JS tự paginate
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = max(1, min(1000, (int)($_GET['per_page'] ?? 1000)));

    // Dò schema: có gì lấy nấy
    $hasGiaCu      = column_exists($db, 'sanpham', 'GiaCu');
    $hasIsPromo    = column_exists($db, 'sanpham', 'IsPromo');
    $hasNgayTao    = column_exists($db, 'sanpham', 'NgayTao');
    $hasLoai       = column_exists($db, 'sanpham', 'Loai');
    $hasMaDanhMuc  = column_exists($db, 'sanpham', 'MaDanhMuc');
    $hasDanhmucTbl = table_exists($db, 'danhmuc');
    $hasHinhAnh    = column_exists($db, 'sanpham', 'HinhAnh');

    // SELECT linh hoạt theo cột có tồn tại
    $sel = [
        "s.MaSanPham AS id",
        "s.TenSanPham AS name",
        "s.Gia AS price"
    ];
    if ($hasGiaCu)   $sel[] = "s.GiaCu AS oldPrice";
    if ($hasHinhAnh) $sel[] = "s.HinhAnh AS image";
    if ($hasNgayTao) $sel[] = "s.NgayTao AS createdAt";
    if ($hasIsPromo) $sel[] = "s.IsPromo AS isPromo";

    $join = '';
    if ($hasMaDanhMuc && $hasDanhmucTbl) {
        $sel[] = "d.TenDanhMuc AS subCategory";
        $join  = " LEFT JOIN danhmuc d ON d.MaDanhMuc = s.MaDanhMuc ";
    } elseif ($hasLoai) {
        $sel[] = "s.Loai AS subCategory";
    }
    // category để trống, sẽ fallback = subCategory
    $sel[] = "'' AS category";

    $selectSql = implode(', ', $sel);

    // Đếm tổng (không filter)
    $countSql = "SELECT COUNT(*) AS cnt FROM sanpham s $join";
    $cnt = 0;
    if ($st = $db->prepare($countSql)) {
        $st->execute();
        $r = $st->get_result()->fetch_assoc();
        $st->close();
        $cnt = (int)($r['cnt'] ?? 0);
    }

    $totalPages = max(1, (int)ceil($cnt / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    // Lấy dữ liệu (mặc định sort mới nhất theo MaSanPham)
    $sql = "SELECT $selectSql
            FROM sanpham s
            $join
            ORDER BY s.MaSanPham DESC
            LIMIT ? OFFSET ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('ii', $perPage, $offset);
    $stmt->execute();
    $rs = $stmt->get_result();

    $items = [];
    while ($row = $rs->fetch_assoc()) {
        $id    = (int)$row['id'];
        $name  = (string)$row['name'];
        $price = (float)$row['price'];

        $old   = $hasGiaCu   ? (isset($row['oldPrice']) ? (float)$row['oldPrice'] : null) : null;
        $img   = $hasHinhAnh ? (string)($row['image'] ?? '') : '';
        $sub   = (string)($row['subCategory'] ?? '');
        $cat   = (string)($row['category'] ?? '');
        if ($cat === '' && $sub !== '') $cat = $sub;

        $newTs = 0;
        if ($hasNgayTao && !empty($row['createdAt'])) {
            $ts = strtotime($row['createdAt']);
            if ($ts !== false) $newTs = $ts;
        }

        $isPromo = false;
        if ($hasIsPromo) {
            // dùng alias isPromo nếu có
            $isPromo = (bool)($row['isPromo'] ?? false);
        }

        $items[] = [
            'id'          => $id,
            'name'        => $name,
            'price'       => $price,
            'oldPrice'    => $old,
            'image'       => $img,
            'subCategory' => $sub,
            'category'    => $cat,
            'isPromo'     => $isPromo,
            'newProduct'  => $newTs,
            'popularity'  => 0
        ];
    }
    $stmt->close();

    echo json_encode([
        'ok'          => true,
        'total'       => $cnt,
        'per_page'    => $perPage,
        'page'        => $page,
        'total_pages' => $totalPages,
        'items'       => $items
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
