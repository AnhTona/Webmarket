<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');

final class SearchController
{
    /**
     * Main handler: phân biệt giữa suggestions và full search
     */
    public static function handle(): void
    {
        $action = $_GET['action'] ?? 'search'; // 'search' hoặc 'suggestions'

        if ($action === 'suggestions') {
            self::handleSuggestions();
        } else {
            self::handleFullSearch();
        }
    }

    /**
     * Autocomplete suggestions (dùng cho dropdown search)
     */
    private static function handleSuggestions(): void
    {
        $conn = self::connect();
        $keyword = trim((string)($_GET['keyword'] ?? ''));

        if ($keyword === '') {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $sql = "SELECT MaSanPham AS id, TenSanPham AS name 
                FROM sanpham 
                WHERE TrangThai = 1 AND TenSanPham LIKE ? 
                LIMIT 10";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi prepare statement'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $likeKeyword = "%{$keyword}%";
        $stmt->bind_param("s", $likeKeyword);
        $stmt->execute();
        $result = $stmt->get_result();

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }

        $stmt->close();
        echo json_encode($products, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Full search results (dùng cho trang search_results.php)
     */
    private static function handleFullSearch(): void
    {
        $conn = self::connect();
        $keyword = trim((string)($_GET['keyword'] ?? ''));

        if ($keyword === '') {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $sql = "SELECT 
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
                WHERE s.TrangThai = 1 AND s.TenSanPham LIKE ?
                ORDER BY s.NgayTao DESC";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi prepare statement'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $likeKeyword = "%{$keyword}%";
        $stmt->bind_param("s", $likeKeyword);
        $stmt->execute();
        $result = $stmt->get_result();

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = self::normalizeForUI($row);
        }

        $stmt->close();
        echo json_encode($products, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ---- DB Connection ----
    private static function connect(): mysqli
    {
        $dbPath = __DIR__ . '/../model/db.php';
        if (!file_exists($dbPath)) {
            http_response_code(500);
            echo json_encode(['error' => "Không tìm thấy db.php"], JSON_UNESCAPED_UNICODE);
            exit;
        }

        require $dbPath;

        if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
            http_response_code(500);
            echo json_encode(['error' => 'Kết nối DB lỗi'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $conn->set_charset('utf8mb4');
        return $conn;
    }

    // ---- Normalize data for UI ----
    private static function normalizeForUI(array $row): array
    {
        $id    = (int)$row['MaSanPham'];
        $name  = (string)$row['TenSanPham'];
        $price = isset($row['Gia']) ? (float)$row['Gia'] : 0.0;
        $old   = ($row['GiaCu'] ?? null) !== null ? (float)$row['GiaCu'] : null;
        $img   = self::normalizeImage((string)($row['HinhAnh'] ?? ''));

        $sub = trim((string)($row['subCategory'] ?? ''));
        if ($sub === '') $sub = self::inferSubCategory($name);

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

        $teaSubs = ['lục trà','luc tra','trà xanh','tra xanh','hồng trà','hong tra',
            'bạch trà','bach tra','oolong','ô long','phổ nhĩ','pho nhi','trà sen','tra sen'];
        foreach ($teaSubs as $t) {
            if (str_contains($s, $t)) return 'Trà';
        }

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

// Execute
SearchController::handle();