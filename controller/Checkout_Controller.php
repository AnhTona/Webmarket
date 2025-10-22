<?php
declare(strict_types=1);

/**
 * Checkout_Controller.php (OOP)
 * - GET ?action=prefill
 *     => Prefill user (name/email), rank+discount theo Email, danh sách bàn (bantrongquan)
 * - GET ?action=check_rank_email&email=...
 *     => Trả rank+discount theo Email
 *
 * Ghi chú:
 * - Bỏ hoàn toàn logic SĐT
 * - Bảng bàn hiện có: bantrongquan(MaBan BIGINT, SoGhe INT, TrangThai TINYINT, SoLanSuDung INT)
 *   + coi TrangThai=1 là còn trống; nếu kiểu chuỗi: 'trống','trong','active','available','hoạt động'
 */

header('Content-Type: application/json; charset=UTF-8');
session_start();

/* =========================
 * DB Resolver (khớp database.php)
 * ========================= */
final class DbResolver
{
    public static function includeDatabase(): void
    {
        $candidates = [
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

    public static function conn(): mysqli
    {
        self::includeDatabase();

        if (class_exists('Database')) {
            // Singleton: Database::getInstance()->getConnection()
            if (method_exists('Database', 'getInstance')) {
                $inst = @Database::getInstance();
                if (is_object($inst)) {
                    if (method_exists($inst, 'getConnection')) {
                        $c = @$inst->getConnection();
                        if ($c instanceof mysqli) { @$c->set_charset('utf8mb4'); return $c; }
                    }
                    foreach (['conn','connection','db','mysqli'] as $prop) {
                        if (property_exists($inst, $prop) && $inst->{$prop} instanceof mysqli) {
                            @$inst->{$prop}->set_charset('utf8mb4');
                            return $inst->{$prop};
                        }
                    }
                }
            }
            // Static: Database::getConnection()
            if (method_exists('Database', 'getConnection')) {
                try {
                    $rm = new ReflectionMethod('Database', 'getConnection');
                    if ($rm->isStatic()) {
                        $c = Database::getConnection();
                        if ($c instanceof mysqli) { @$c->set_charset('utf8mb4'); return $c; }
                    }
                } catch (\Throwable $__) { /* ignore */ }
            }
        }

        // Globals
        if (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli'] instanceof mysqli) { @$GLOBALS['mysqli']->set_charset('utf8mb4'); return $GLOBALS['mysqli']; }
        if (isset($GLOBALS['conn'])   && $GLOBALS['conn']   instanceof mysqli) { @$GLOBALS['conn']->set_charset('utf8mb4');   return $GLOBALS['conn']; }

        // Constants
        $host = defined('DB_HOST') ? DB_HOST : null;
        $user = defined('DB_USER') ? DB_USER : null;
        $pass = defined('DB_PASS') ? DB_PASS : (defined('DB_PASSWORD') ? DB_PASSWORD : null);
        $name = defined('DB_NAME') ? DB_NAME : null;
        $port = defined('DB_PORT') ? (int)DB_PORT : 3306;

        if ($host && $user && $name !== null) {
            $c = @new mysqli($host, $user, $pass ?? '', $name, $port);
            if ($c->connect_errno === 0) { @$c->set_charset('utf8mb4'); return $c; }
        }

        throw new RuntimeException('Không lấy được connection từ database.php');
    }
}

/* =========================
 * Schema utils
 * ========================= */
final class SchemaUtil
{
    public static function tableExists(mysqli $db, string $table): bool
    {
        $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?";
        $st  = $db->prepare($sql);
        $st->bind_param('s', $table);
        $st->execute();
        $one = 0; $st->bind_result($one);
        $ok  = $st->fetch();
        $st->close();
        return (bool)$ok;
    }

    public static function columnExists(mysqli $db, string $table, string $col): bool
    {
        $sql = "SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?";
        $st  = $db->prepare($sql);
        $st->bind_param('ss', $table, $col);
        $st->execute();
        $one = 0; $st->bind_result($one);
        $ok  = $st->fetch();
        $st->close();
        return (bool)$ok;
    }
}

/* =========================
 * Rank service
 * ========================= */
final class RankService
{
    public static function loadTiers(mysqli $db): array
    {
        $tiers = [];
        if (SchemaUtil::tableExists($db, 'cauhinh_hang')) {
            $st = $db->prepare("SELECT TenHang, MinChiTieu FROM cauhinh_hang ORDER BY MinChiTieu DESC");
            $st->execute();
            $rs = $st->get_result();
            while ($r = $rs->fetch_assoc()) {
                $tiers[] = ['name' => (string)$r['TenHang'], 'min' => (int)floor((float)$r['MinChiTieu'])];
            }
            $st->close();
        }
        usort($tiers, fn($a,$b)=> $b['min'] <=> $a['min']);
        $hasZero = false; foreach ($tiers as $t) { if ($t['min'] === 0) { $hasZero = true; break; } }
        if (!$hasZero) $tiers[] = ['name'=>'Mới','min'=>0];
        return $tiers;
    }

    public static function decideFromSpent(mysqli $db, float $spent): string
    {
        $tiers = self::loadTiers($db);
        $rank = 'Mới';
        foreach ($tiers as $t) { if ($spent >= $t['min']) { $rank = $t['name']; break; } }
        return $rank;
    }

    public static function discountOf(string $rank): float
    {
        // Có thể tùy chỉnh map này theo chính sách của bạn
        $map = [
            'Mới'    => 0.00,
            'Bronze' => 0.02,
            'Silver' => 0.05,
            'Gold'   => 0.10,
            'Kim cương' => 0.10,
        ];
        return $map[$rank] ?? 0.00;
    }
}

/* =========================
 * User service (bỏ hoàn toàn phone)
 * ========================= */
final class UserService
{
    public static function getLoggedUser(mysqli $db): ?array
    {
        if (empty($_SESSION['user_id'])) return null;
        $userId = (int)$_SESSION['user_id'];

        $hasHoTen = SchemaUtil::columnExists($db, 'nguoidung', 'HoTen');
        $hasEmail = SchemaUtil::columnExists($db, 'nguoidung', 'Email');
        $hasHang  = SchemaUtil::columnExists($db, 'nguoidung', 'Hang');
        $hasSpent = SchemaUtil::columnExists($db, 'nguoidung', 'TongChiTieu');

        $sel = ["MaNguoiDung"];
        if ($hasHoTen) $sel[] = "HoTen";
        if ($hasEmail) $sel[] = "Email";
        if ($hasHang)  $sel[] = "Hang";
        if ($hasSpent) $sel[] = "TongChiTieu";

        $sql = "SELECT ".implode(",", $sel)." FROM nguoidung WHERE MaNguoiDung=? LIMIT 1";
        $st  = $db->prepare($sql);
        $st->bind_param('i', $userId);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        if (!$row) return null;

        $name  = $hasHoTen ? (string)($row['HoTen'] ?? '') : '';
        $email = $hasEmail ? (string)($row['Email'] ?? '') : '';
        $rank  = $hasHang  ? (string)($row['Hang']  ?? '') : '';
        $spent = $hasSpent ? (float)($row['TongChiTieu'] ?? 0) : 0.0;

        if ($rank === '' || !in_array($rank, ['Mới','Bronze','Silver','Gold','Kim cương'], true)) {
            $rank = RankService::decideFromSpent($db, $spent);
        }

        return [
            'id'       => $userId,
            'name'     => $name,
            'email'    => $email,
            'rank'     => $rank,
            'spent'    => $spent,
            'discount' => RankService::discountOf($rank),
        ];
    }
}

/* =========================
 * Table service (không dùng SoLuongGhe)
 * ========================= */
final class TableService
{
    public static function listTables(mysqli $db, bool $onlyFree = true): array
    {
        if (!SchemaUtil::tableExists($db, 'bantrongquan')) return [];
        $hasSoGhe     = SchemaUtil::columnExists($db, 'bantrongquan', 'SoGhe');
        $hasTrangThai = SchemaUtil::columnExists($db, 'bantrongquan', 'TrangThai');

        $sel = ["MaBan"];
        if ($hasSoGhe)     $sel[] = "SoGhe";
        if ($hasTrangThai) $sel[] = "TrangThai";

        $sql = "SELECT ".implode(',', $sel)." FROM bantrongquan ORDER BY MaBan ASC";
        $rs  = $db->query($sql);

        $out = [];
        while ($r = $rs->fetch_assoc()) {
            $id    = (int)$r['MaBan'];
            $seats = $hasSoGhe ? (int)($r['SoGhe'] ?? 0) : 0;

            $available = true;
            if ($hasTrangThai) {
                $raw = $r['TrangThai'];
                if (is_numeric($raw)) {
                    $available = ((int)$raw) === 1; // 1 = trống
                } else {
                    $st = mb_strtolower((string)$raw);
                    $available = in_array($st, ['1','trống','trong','active','available','hoạt động'], true);
                }
            }

            if ($onlyFree && !$available) continue;

            $label = "Bàn #$id" . ($seats > 0 ? " ($seats ghế)" : '');
            $out[] = [
                'id'        => $id,
                'label'     => $label,
                'seats'     => $seats,
                'status'    => $hasTrangThai ? $r['TrangThai'] : null,
                'available' => $available
            ];
        }
        return $out;
    }
}

/* =========================
 * Controller
 * ========================= */
final class CheckoutController
{
    public function __construct(private mysqli $db) {}

    public function handle(): void
    {
        $action = $_GET['action'] ?? 'prefill';
        try {
            switch ($action) {
                case 'prefill':
                    $this->prefill();
                    break;
                case 'check_rank_email':
                    $this->checkRankByEmail();
                    break;
                default:
                    $this->json(['ok'=>false,'error'=>'Unknown action']);
            }
        } catch (\Throwable $e) {
            $this->json(['ok'=>false,'error'=>$e->getMessage()]);
        }
    }

    private function prefill(): void
    {
        $user   = UserService::getLoggedUser($this->db);
        $tables = TableService::listTables($this->db, true);

        $this->json([
            'ok'        => true,
            'logged_in' => $user !== null,
            'user'      => $user ? [
                'name'  => $user['name'],
                'email' => $user['email'],
            ] : null,
            'rank'      => $user ? [
                'name'     => $user['rank'],
                'discount' => $user['discount'],
                'spent'    => $user['spent'],
            ] : ['name'=>'Mới','discount'=>0,'spent'=>0],
            'tables'    => $tables,
            'suggested_table_id' => $tables[0]['id'] ?? null
        ]);
    }

    private function checkRankByEmail(): void
    {
        $email = strtolower(trim((string)($_GET['email'] ?? '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['ok'=>false,'error'=>'Email không hợp lệ']); return;
        }

        $rankName = 'Mới';
        $spent    = 0.0;

        if (SchemaUtil::tableExists($this->db, 'nguoidung') && SchemaUtil::columnExists($this->db, 'nguoidung', 'Email')) {
            $sql = "SELECT Hang, TongChiTieu FROM nguoidung WHERE Email=? LIMIT 1";
            $st  = $this->db->prepare($sql);
            $st->bind_param('s', $email);
            $st->execute();
            $r = $st->get_result()->fetch_assoc();
            $st->close();

            if ($r) {
                $rankName = (string)($r['Hang'] ?? '');
                $spent    = (float)($r['TongChiTieu'] ?? 0);
                if ($rankName === '' || !in_array($rankName, ['Mới','Bronze','Silver','Gold','Kim cương'], true)) {
                    $rankName = RankService::decideFromSpent($this->db, $spent);
                }
            }
        }

        $this->json([
            'ok'   => true,
            'rank' => [
                'name'     => $rankName,
                'discount' => RankService::discountOf($rankName),
                'spent'    => $spent
            ]
        ]);
    }

    private function json(array $payload): void
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/* =========================
 * Bootstrap
 * ========================= */
$db  = DbResolver::conn();
$ctl = new CheckoutController($db);
$ctl->handle();
