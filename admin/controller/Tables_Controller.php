<?php
// admin/controller/Tables_Controller.php
// PHP 8+, MySQLi. Không thay đổi layout/JS; chỉ cung cấp data + API JSON khi ?ajax=1

require_once __DIR__ . '/../../model/db.php';

class TablesController
{
    // Map trạng thái DB (tinyint) <-> text hiển thị trong UI
    // DB: 1=Trống, 2=Đang đặt, 3=Đang sử dụng, 0=Bảo trì
    private const STATUS_TO_TEXT = [
        0 => 'Bảo trì',
        1 => 'Trống',
        2 => 'Đang đặt',
        3 => 'Đang sử dụng',
    ];
    private const TEXT_TO_STATUS = [
        'Bảo trì'       => 0,
        'Trống'         => 1,
        'Đang đặt'      => 2,
        'Đang sử dụng'  => 3,
    ];

    public static function handle(): array
    {
        // Nếu gọi AJAX thì trả JSON luôn
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json; charset=utf-8');
            $action = $_GET['action'] ?? ($_POST['action'] ?? '');
            try {
                switch ($action) {
                    case 'save':     return self::json(self::save());
                    case 'delete':   return self::json(self::delete());
                    case 'change_status':
                    case 'book':
                    case 'cancel':
                    case 'checkout': return self::json(self::quickStatus($action));
                    default:         return self::json(['ok' => false, 'message' => 'Thiếu hoặc sai action']);
                }
            } catch (Throwable $e) {
                return self::json(['ok' => false, 'message' => 'Lỗi: '.$e->getMessage()]);
            }
        }

        // Render trang (không AJAX)
        [$list] = self::fetchList(); // có thể mở rộng phân trang khi cần
        return [
            'table_list' => $list,
        ];
    }

    /* ==================== READ ==================== */

    /**
     * Lấy danh sách bàn, có filter nhẹ theo GET (?search, ?status, ?seats)
     * Trả về: [$rows]
     */
    private static function fetchList(): array
    {
        global $conn;

        $search = trim((string)($_GET['search'] ?? ''));
        $statusText = trim((string)($_GET['status'] ?? 'All'));
        $seats = isset($_GET['seats']) && $_GET['seats'] !== '' ? (int)$_GET['seats'] : null;

        $where = [];
        $params = [];
        $types  = '';

        if ($search !== '') {
            // tìm theo MaBan
            $where[] = 'b.MaBan LIKE ?';
            $params[] = "%$search%";
            $types   .= 's';
        }
        if ($statusText !== '' && $statusText !== 'All') {
            $code = self::TEXT_TO_STATUS[$statusText] ?? null;
            if ($code !== null) {
                $where[] = 'b.TrangThai = ?';
                $params[] = $code;
                $types   .= 'i';
            }
        }
        if ($seats !== null) {
            $where[] = 'b.SoGhe = ?';
            $params[] = $seats;
            $types   .= 'i';
        }

        $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

        // usage_count: đếm đơn hàng có gán bàn này (bạn đang có cột MaBan trong donhang). :contentReference[oaicite:2]{index=2}
        $sql = "
            SELECT 
                b.MaBan AS id,
                b.SoGhe AS seats,
                b.TrangThai AS status_code,
                (
                    SELECT COUNT(*) 
                    FROM donhang d 
                    WHERE d.MaBan = b.MaBan
                ) AS usage_count
            FROM bantrongquan b
            $whereSql
            ORDER BY b.MaBan ASC
        ";

        $stmt = $conn->prepare($sql);
        if ($types) { $stmt->bind_param($types, ...$params); }
        $stmt->execute();
        $res = $stmt->get_result();

        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                'id'          => (int)$r['id'],
                'seats'       => (int)($r['seats'] ?? 0),
                'status'      => self::STATUS_TO_TEXT[(int)$r['status_code']] ?? 'Trống',
                'usage_count' => (int)($r['usage_count'] ?? 0),
            ];
        }
        $stmt->close();

        return [$rows];
    }

    /* ==================== UPSERT ==================== */

    /**
     * POST: id(optional), seats, status
     * - id rỗng  => INSERT
     * - id có    => UPDATE
     */
    private static function save(): array
    {
        global $conn;

        $id     = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
        $seats  = (int)($_POST['seats'] ?? 0);
        $status = trim((string)($_POST['status'] ?? 'Trống'));
        $code   = self::TEXT_TO_STATUS[$status] ?? 1; // default 1=Trống

        if ($seats <= 0) {
            return ['ok' => false, 'message' => 'Số lượng chỗ phải > 0'];
        }

        if ($id) {
            $stmt = $conn->prepare("UPDATE bantrongquan SET SoGhe=?, TrangThai=? WHERE MaBan=?");
            $stmt->bind_param('iii', $seats, $code, $id);
            $ok = $stmt->execute();
            $stmt->close();
            if (!$ok) return ['ok'=>false,'message'=>'Cập nhật thất bại'];
            return ['ok'=>true,'message'=>'Cập nhật thành công'];
        } else {
            $stmt = $conn->prepare("INSERT INTO bantrongquan (SoGhe, TrangThai) VALUES (?,?)");
            $stmt->bind_param('ii', $seats, $code);
            $ok = $stmt->execute();
            if (!$ok) { $stmt->close(); return ['ok'=>false,'message'=>'Thêm mới thất bại']; }
            $newId = (int)$conn->insert_id;
            $stmt->close();
            return ['ok'=>true,'message'=>'Thêm mới thành công','id'=>$newId];
        }
    }

    /* ==================== DELETE ==================== */

    private static function delete(): array
    {
        global $conn;
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) return ['ok'=>false,'message'=>'Thiếu id'];

        // Không xoá nếu đang có đơn hàng gắn bàn (tránh orphan)
        $ck = $conn->prepare("SELECT COUNT(*) AS c FROM donhang WHERE MaBan=?");
        $ck->bind_param('i', $id);
        $ck->execute();
        $c  = (int)($ck->get_result()->fetch_assoc()['c'] ?? 0);
        $ck->close();
        if ($c > 0) return ['ok'=>false,'message'=>'Bàn đang được dùng trong đơn hàng, không thể xoá'];

        $stmt = $conn->prepare("DELETE FROM bantrongquan WHERE MaBan=?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok ? ['ok'=>true,'message'=>'Đã xoá'] : ['ok'=>false,'message'=>'Xoá thất bại'];
    }

    /* ==================== QUICK STATUS ==================== */

    /**
     * Các action nhanh từ UI: book/cancel/checkout/change_status
     * GET: id, (status nếu change_status)
     */
    private static function quickStatus(string $action): array
    {
        global $conn;
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) return ['ok'=>false,'message'=>'Thiếu id'];

        $to = 1; // default Trống
        if ($action === 'book')          $to = self::TEXT_TO_STATUS['Đang đặt'];
        elseif ($action === 'cancel')    $to = self::TEXT_TO_STATUS['Trống'];
        elseif ($action === 'checkout')  $to = self::TEXT_TO_STATUS['Trống'];
        elseif ($action === 'change_status') {
            $statusText = trim((string)($_GET['status'] ?? 'Trống'));
            $to = self::TEXT_TO_STATUS[$statusText] ?? 1;
        }

        $stmt = $conn->prepare("UPDATE bantrongquan SET TrangThai=? WHERE MaBan=?");
        $stmt->bind_param('ii', $to, $id);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok ? ['ok'=>true,'message'=>'Đã cập nhật trạng thái'] : ['ok'=>false,'message'=>'Cập nhật thất bại'];
    }

    /* ==================== Helpers ==================== */

    private static function json(array $payload): array
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        return $payload;
    }
}