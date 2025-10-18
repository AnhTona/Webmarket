<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');

final class GetProductsController
{
    public static function handle(): void
    {
        $conn = self::connect();

        // ---- inputs ----
        $q       = trim((string)($_GET['q'] ?? ''));
        $cat     = trim((string)($_GET['cat'] ?? ''));                 // "Trà", "Bánh", "Combo" hoặc tên Loai
        $promo   = ($_GET['promo'] ?? '') !== '' ? (int)$_GET['promo'] : null; // 0|1|null
        $sort    = (string)($_GET['sort'] ?? 'newest');
        
        // BỎ PHÂN TRANG - không cần page, perPage, offset nữa

        [$where, $types, $params] = self::buildFilters($q, $cat, $promo);
        $orderBy = self::orderBy($sort);

        $rows = self::fetchProducts($conn, $where, $types, $params, $orderBy);

        // Chuẩn hoá cho UI cũ (mảng top-level)
        $list = array_map(fn($r) => self::normalizeForUI($r), $rows);
        echo json_encode($list, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ---- DB ----
    private static function connect(): mysqli
    {
        $dbPath = __DIR__ . '/../model/db.php';
        if (!file_exists($dbPath)) {
            http_response_code(500);
            echo json_encode(['error' => "Không tìm thấy db.php: $dbPath"], JSON_UNESCAPED_UNICODE);
            exit;
        }
        require $dbPath; // phải tạo $conn (mysqli)
        if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
            http_response_code(500);
            echo json_encode(['error' => 'Kết nối DB lỗi'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $conn->set_charset('utf8mb4');
        return $conn;
    }

    // ---- filters ----
    private static function buildFilters(string $kw, string $cat, ?int $promo): array
    {
        $w = ['s.TrangThai = 1']; // CHỈ HIỆN SẢN PHẨM ĐANG HOẠT ĐỘNG (TrangThai = 1)
        $params = [];
        $types  = '';

        if ($kw !== '') {
            $w[] = 's.TenSanPham LIKE ?';
            $params[] = "%$kw%"; $types .= 's';
        }

        if ($cat !== '') {
            // Lọc theo Loai (sub) hoặc theo TenDanhMuc (category lớn)
            $w[] = '(s.Loai = ? OR d.TenDanhMuc = ?)';
            $params[] = $cat; $params[] = $cat; $types .= 'ss';
        }

        if ($promo !== null) {
            $w[] = 's.IsPromo = ?';
            $params[] = $promo; $types .= 'i';
        }

        return ['WHERE ' . implode(' AND ', $w), $types, $params];
    }

    private static function orderBy(string $sort): string
    {
        return match ($sort) {
            'price_asc'  => 's.Gia ASC',
            'price_desc' => 's.Gia DESC',
            'name_asc'   => 's.TenSanPham ASC',
            default      => 's.NgayTao DESC', // newest
        };
    }

    private static function fetchProducts(
        mysqli $conn,
        string $where,
        string $types,
        array  $params,
        string $orderBy
        // BỎ $perPage và $offset
    ): array {
        // Join đúng bảng danh mục: danhmucsanpham (match bằng Loai)
        $sql = "
            SELECT
                s.MaSanPham,
                s.TenSanPham,
                s.Gia,
                s.GiaCu,
                s.HinhAnh,
                s.SoLuongTon,
                s.TrangThai,
                s.NgayTao,
                s.IsPromo,
                s.Loai AS subCategory,
                COALESCE(d.TenDanhMuc, '') AS TenDanhMuc
            FROM sanpham s
            LEFT JOIN danhmucsanpham d ON d.Loai = s.Loai
            $where
            ORDER BY $orderBy
        ";
        // BỎ LIMIT ? OFFSET ?

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => $conn->error], JSON_UNESCAPED_UNICODE);
            exit;
        }
        self::bind($stmt, $types, $params);
        $stmt->execute();
        $res = $stmt->get_result();

        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
        $stmt->close();
        return $rows;
    }

    private static function bind(mysqli_stmt $stmt, string $types, array $params): void
    {
        if ($types === '') return;
        $refs = [];
        foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
        array_unshift($refs, $types);
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }

    // ---- UI normalize ----
    private static function normalizeForUI(array $row): array
    {
        $id    = (int)$row['MaSanPham'];
        $name  = (string)$row['TenSanPham'];
        $price = isset($row['Gia'])   ? (float)$row['Gia']   : 0.0;
        $old   = ($row['GiaCu'] ?? null) !== null ? (float)$row['GiaCu'] : null;
        $img   = self::normalizeImage((string)($row['HinhAnh'] ?? ''));

        $sub   = trim((string)($row['subCategory'] ?? ''));
        if ($sub === '') $sub = self::inferSubCategory($name);

        // Ưu tiên TenDanhMuc từ bảng danhmucsanpham; nếu trống, map theo sub/name
        $catFromDB = trim((string)($row['TenDanhMuc'] ?? ''));
        $cat = $catFromDB !== '' ? $catFromDB : self::mapMainCategory($sub, $name);

        return [
            'id'         => $id,
            'name'       => $name,
            'price'      => $price,
            'oldPrice'   => $old,
            'image'      => $img,
            'subCategory'=> $sub,
            'category'   => $cat,
            'isPromo'    => (bool)((int)($row['IsPromo'] ?? 0) || ($old !== null && $old > 0)),
            'newProduct' => strtotime((string)($row['NgayTao'] ?? '')) ?: 0,
            'popularity' => 0,
        ];
    }

    private static function normalizeImage(string $src): string
    {
        if ($src === '') return '/image/sp1.jpg';
        if ($src[0] !== '/') $src = '/' . ltrim($src, '/');
        return $src;
    }

    private static function mapMainCategory(string $sub, string $name): string
    {
        $s = mb_strtolower(trim($sub . ' ' . $name), 'UTF-8');

        // Nhóm trà: kể cả khi không có chữ "trà" trong tên (vd: Phổ Nhĩ)
        $teaSubs = ['lục trà','luc tra','trà xanh','tra xanh','hồng trà','hong tra',
            'bạch trà','bach tra','oolong','ô long','phổ nhĩ','pho nhi','trà sen','tra sen'];
        foreach ($teaSubs as $t) { if (str_contains($s, $t)) return 'Trà'; }

        if (str_contains($s, 'combo')) return 'Combo';
        if (str_contains($s, 'bánh') || str_contains($s, 'banh')) return 'Bánh';
        return 'All';
    }

    private static function inferSubCategory(string $name): string
    {
        $x = mb_strtolower($name, 'UTF-8');
        if (str_contains($x, 'lục trà') || str_contains($x, 'luc tra') || str_contains($x, 'trà xanh')) return 'Lục Trà';
        if (str_contains($x, 'hồng trà') || str_contains($x, 'hong tra')) return 'Hồng Trà';
        if (str_contains($x, 'bạch trà') || str_contains($x, 'bach tra')) return 'Bạch Trà';
        if (str_contains($x, 'oolong') || str_contains($x, 'ô long'))     return 'Oolong Trà';
        if (str_contains($x, 'phổ nh') || str_contains($x, 'pho nhi'))    return 'Phổ Nhĩ';
        if (str_contains($x, 'combo'))                                     return 'Combo';
        if (str_contains($x, 'bánh') || str_contains($x, 'banh'))         return 'Bánh';
        return '';
    }
}

// run
GetProductsController::handle();
