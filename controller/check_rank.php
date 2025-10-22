<?php
/**
 * check_rank.php — Recalculate customer ranks by total spending.
 * - Auto-detect table/column names (VN/EN variants)
 * - Update total spending + rank
 * - HTTP JSON & CLI both supported
 */

declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/db.php';
$mysqli = db();

// ---------------- Config (you can tweak) ----------------
$DEFAULT_TIERS = [
    ['name' => 'Bạch kim', 'min' => 20000000],
    ['name' => 'Vàng',     'min' => 10000000],
    ['name' => 'Bạc',      'min' =>  5000000],
    ['name' => 'Đồng',     'min' =>  1000000],
    ['name' => 'Mới',      'min' =>         0],
];

$STATUS_DONE = ['DONE', 'COMPLETED', 'Hoàn thành', 'DA_GIAO', 'ĐÃ GIAO'];

// --------------- Helpers: schema detection ---------------
function db_name(mysqli $db): string {
    $r = $db->query("SELECT DATABASE() AS db");
    $row = $r ? $r->fetch_assoc() : null;
    return $row['db'] ?? '';
}

function table_exists(mysqli $db, string $table): bool {
    $table = $db->real_escape_string($table);
    $sql = "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$table}'";
    return (bool) ($db->query($sql)?->num_rows);
}

function column_exists(mysqli $db, string $table, string $col): bool {
    $table = $db->real_escape_string($table);
    $col   = $db->real_escape_string($col);
    $sql = "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$table}' AND COLUMN_NAME = '{$col}'";
    return (bool) ($db->query($sql)?->num_rows);
}

/** return first existing candidate or '' */
function pick_col(mysqli $db, string $table, array $candidates): string {
    foreach ($candidates as $c) {
        if (column_exists($db, $table, $c)) return $c;
    }
    return '';
}

/** return first existing table from candidates or '' */
function pick_table(mysqli $db, array $candidates): string {
    foreach ($candidates as $t) {
        if (table_exists($db, $t)) return $t;
    }
    return '';
}

// --------------- Detect schema ---------------
$tblCustomers = pick_table($mysqli, ['khachhang','customers','nguoidung','nguoi_dung']);
$tblOrders    = pick_table($mysqli, ['donhang','orders','hoadon','hoa_don']);

if (!$tblCustomers) {
    echo json_encode(['ok' => false, 'error' => 'Không tìm thấy bảng khách hàng (khachhang/customers/nguoidung)'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!$tblOrders) {
    echo json_encode(['ok' => false, 'error' => 'Không tìm thấy bảng đơn hàng (donhang/orders/hoadon)'], JSON_UNESCAPED_UNICODE);
    exit;
}

// customer columns
$colCusId   = pick_col($mysqli, $tblCustomers, ['MaKhachHang','id','MaNguoiDung','UserID']);
$colCusRank = pick_col($mysqli, $tblCustomers, ['Hang','Rank','CapBac','LoaiKH']);
$colCusSum  = pick_col($mysqli, $tblCustomers, ['TongChiTieu','TongTien','TongTichLuy','TongMua','TotalSpend']);

if (!$colCusId) {
    echo json_encode(['ok' => false, 'error' => "Bảng {$tblCustomers} không có cột khoá (MaKhachHang/id)"], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!$colCusRank) {
    // nếu chưa có cột hạng, tự thêm LoaiKH
    $mysqli->query("ALTER TABLE `{$tblCustomers}` ADD COLUMN `LoaiKH` VARCHAR(50) NULL");
    $colCusRank = 'LoaiKH';
}
if (!$colCusSum) {
    // nếu chưa có tổng chi tiêu, tự thêm TongChiTieu
    $mysqli->query("ALTER TABLE `{$tblCustomers}` ADD COLUMN `TongChiTieu` BIGINT NOT NULL DEFAULT 0");
    $colCusSum = 'TongChiTieu';
}

// order columns
$colOrdCus  = pick_col($mysqli, $tblOrders, ['MaKhachHang','KhachHangID','customer_id','CustomerID']);
$colOrdSum  = pick_col($mysqli, $tblOrders, ['TongTien','ThanhTien','Total']);
$colOrdStat = pick_col($mysqli, $tblOrders, ['TrangThai','status','tinhtrang']);

if (!$colOrdCus || !$colOrdSum) {
    echo json_encode(['ok' => false, 'error' => "Bảng {$tblOrders} thiếu cột liên kết/tổng tiền (MaKhachHang/*, TongTien/*)"], JSON_UNESCAPED_UNICODE);
    exit;
}

// --------------- Load tiers (DB override if any) ---------------
$tiers = $DEFAULT_TIERS;
if (table_exists($mysqli, 'rank_thresholds')) {
    $rows = Database::fetchAll("SELECT name, min_total FROM rank_thresholds ORDER BY min_total DESC");
    if ($rows) {
        $tiers = array_map(fn($r) => ['name' => (string)$r['name'], 'min' => (int)$r['min_total']], $rows);
        // bảo đảm có bậc thấp nhất
        usort($tiers, fn($a,$b) => $b['min'] <=> $a['min']);
        $hasZero = array_reduce($tiers, fn($c,$x) => $c || $x['min'] === 0, false);
        if (!$hasZero) $tiers[] = ['name' => 'Mới', 'min' => 0];
    }
}

// --------------- Inputs ---------------
$id = null;
// HTTP GET ?id= / CLI --id=
if (PHP_SAPI === 'cli') {
    foreach ($argv as $a) {
        if (str_starts_with($a, '--id=')) {
            $id = trim(substr($a, 5));
        }
    }
} else {
    $id = isset($_GET['id']) ? trim((string)$_GET['id']) : null;
}

// --------------- Compute totals (per customer) ---------------
$details = [];
$updated = 0; $skipped = 0;

// Build base SQL
$baseSQL = "SELECT `{$colOrdCus}` AS cid, SUM(`{$colOrdSum}`) AS total
            FROM `{$tblOrders}`";

// filter by status if column exists
$where = [];
$params = []; $types = '';

if ($colOrdStat) {
    // chỉ cộng đơn đã hoàn thành
    $place = implode(',', array_fill(0, count($STATUS_DONE), '?'));
    $where[] = "`{$colOrdStat}` IN ($place)";
    $types  .= str_repeat('s', count($STATUS_DONE));
    array_push($params, ...$STATUS_DONE);
}

if ($id !== null && $id !== '') {
    $where[] = "`{$colOrdCus}` = ?";
    $types  .= 's';
    $params[] = $id;
}

if ($where) $baseSQL .= " WHERE " . implode(' AND ', $where);
$baseSQL .= " GROUP BY `{$colOrdCus}`";

$rows = Database::fetchAll($baseSQL, $types, $params);

// --------------- Apply to customers ---------------
foreach ($rows as $r) {
    $cid   = (string)$r['cid'];
    $total = (int)$r['total'];

    // chọn bậc theo tiers
    $rank = $tiers[array_key_last($tiers)]['name']; // mặc định bậc thấp nhất
    foreach ($tiers as $t) {
        if ($total >= (int)$t['min']) { $rank = $t['name']; break; }
    }

    // UPDATE customers
    try {
        $u = Database::exec(
            "UPDATE `{$tblCustomers}` SET `{$colCusSum}`=?, `{$colCusRank}`=? WHERE `{$colCusId}`=?",
            "iss",
            [$total, $rank, $cid]
        );
        $updated += $u['affected'] >= 0 ? 1 : 0;
        $details[] = ['id' => $cid, 'total' => $total, 'rank' => $rank, 'affected' => $u['affected']];
    } catch (Throwable $e) {
        $skipped++;
        $details[] = ['id' => $cid, 'error' => $e->getMessage()];
    }
}

// Nếu không có đơn nào (hoặc id không khớp)
if (!$rows && $id !== null && $id !== '') {
    // vẫn trả ok để phía client không coi là lỗi
    echo json_encode([
        'ok' => true,
        'message' => "Không tìm thấy đơn hàng hoàn thành cho khách id={$id}",
        'updated' => 0,
        'skipped' => 0,
        'details' => [],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok'      => true,
    'updated' => $updated,
    'skipped' => $skipped,
    'details' => $details,
    'schema'  => [
        'customers' => [$tblCustomers, 'id' => $colCusId, 'sum' => $colCusSum, 'rank' => $colCusRank],
        'orders'    => [$tblOrders, 'fk' => $colOrdCus, 'sum' => $colOrdSum, 'status' => $colOrdStat],
    ],
], JSON_UNESCAPED_UNICODE);
