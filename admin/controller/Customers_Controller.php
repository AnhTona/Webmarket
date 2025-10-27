<?php
declare(strict_types=1);

require_once __DIR__ . '/../../model/database.php';
require_once __DIR__ . '/BaseController.php';

final class CustomersController extends BaseController
{
    private const RANK_WINDOW_DAYS = 365;
    private const RANK_SILVER_MIN = 2_000_000;
    private const RANK_GOLD_MIN = 5_000_000;
    private const PAID_STATUSES = ['CONFIRMED', 'SHIPPING', 'DONE'];

    public static function handle(): array
    {
        self::requireAuth();
        $action = $_GET['action'] ?? $_POST['action'] ?? 'index';

        if (self::isAjax()) {
            try {
                return match($action) {
                    'save', 'create', 'update' => self::save(),
                    'delete' => self::delete(),
                    'toggle' => self::toggleStatus(),
                    'recompute_all' => self::recomputeAllRanks(),
                    default => self::error('Invalid action')
                };
            } catch (Throwable $e) {
                error_log("Customers AJAX Error: " . $e->getMessage());
                self::error($e->getMessage());
            }
        }

        try {
            return self::index();
        } catch (Throwable $e) {
            error_log("Customers List Error: " . $e->getMessage());
            return [
                'customer_list' => [],
                'error' => 'Không thể tải danh sách khách hàng',
            ];
        }
    }

    private static function index(): array
    {
        $perPage = max(1, (int)($_GET['per_page'] ?? 20));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        $search = self::sanitize($_GET['q'] ?? $_GET['search'] ?? '');
        $status = $_GET['status'] ?? 'All';
        $rank = $_GET['rank'] ?? 'All';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';

        [$where, $params] = self::buildWhereClause($search, $status, $rank, $dateFrom, $dateTo);

        $total = (int)self::fetchOne(
            "SELECT COUNT(*) FROM nguoidung nd {$where}",
            $params
        );

        $totalPages = max(1, (int)ceil($total / $perPage));

        // ✅ SỬA: Dùng đúng tên cột Hang, bỏ ThanhPho/GhiChu
        $customers = self::fetchAll(
            "SELECT 
                nd.MaNguoiDung as id,
                nd.HoTen as name,
                nd.Email as email,
                nd.SoDienThoai as phone,
                nd.DiaChi as address,
                nd.Hang as rank,
                IF(nd.TrangThai = 1, 'Hoạt động', 'Ngừng') as status,
                nd.NgayTao as created_at,
                COALESCE(SUM(
                    CASE 
                        WHEN dh.TrangThai IN ('" . implode("','", self::PAID_STATUSES) . "')
                        AND dh.NgayDat >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                        THEN dh.TongTien 
                        ELSE 0 
                    END
                ), 0) as total_spent
             FROM nguoidung nd
             LEFT JOIN donhang dh ON nd.MaNguoiDung = dh.MaNguoiDung
             {$where}
             GROUP BY nd.MaNguoiDung
             ORDER BY nd.NgayTao DESC
             LIMIT ? OFFSET ?",
            [self::RANK_WINDOW_DAYS, ...$params, $perPage, $offset]
        );

        foreach ($customers as &$customer) {
            $computedRank = self::computeRank((float)$customer['total_spent']);
            if ($computedRank !== $customer['rank']) {
                self::updateCustomerRank((int)$customer['id'], $computedRank);
                $customer['rank'] = $computedRank;
            }
        }

        return [
            'customer_list' => $customers,
            'page' => $page,
            'total_pages' => $totalPages,
            'total_customers' => $total,
        ];
    }

    private static function buildWhereClause(
        string $search,
        string $status,
        string $rank,
        string $dateFrom,
        string $dateTo
    ): array {
        // ✅ SỬA: Filter đúng VaiTro (bao gồm empty string)
        $where = ["(nd.VaiTro = '' OR nd.VaiTro IS NULL OR nd.VaiTro = 'USER')"];
        $params = [];

        if ($search !== '') {
            $where[] = "(nd.HoTen LIKE ? OR nd.Email LIKE ? OR nd.SoDienThoai LIKE ?)";
            $searchParam = "%{$search}%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
        }

        if ($status === 'Hoạt động') {
            $where[] = "nd.TrangThai = 1";
        } elseif ($status === 'Ngừng') {
            $where[] = "nd.TrangThai = 0";
        }

        if ($rank !== 'All' && $rank !== '') {
            $where[] = "nd.Hang = ?"; // ✅ SỬA: HangThanhVien -> Hang
            $params[] = $rank;
        }

        if ($dateFrom !== '') {
            $where[] = "DATE(nd.NgayTao) >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $where[] = "DATE(nd.NgayTao) <= ?";
            $params[] = $dateTo;
        }

        return ['WHERE ' . implode(' AND ', $where), $params];
    }

    private static function computeRank(float $amount): string
    {
        return match(true) {
            $amount >= self::RANK_GOLD_MIN => 'Gold',
            $amount >= self::RANK_SILVER_MIN => 'Silver',
            $amount > 0 => 'Bronze',
            default => 'Mới'
        };
    }

    // ✅ SỬA: Update đúng cột Hang
    private static function updateCustomerRank(int $customerId, string $rank): void
    {
        self::query(
            "UPDATE nguoidung SET Hang = ? WHERE MaNguoiDung = ?",
            [$rank, $customerId]
        );
    }

    private static function save(): never
    {
        $id = (int)($_POST['id'] ?? 0);
        $name = self::sanitize($_POST['name'] ?? '');
        $email = self::sanitize($_POST['email'] ?? '');
        $phone = self::sanitize($_POST['phone'] ?? '');
        $address = self::sanitize($_POST['address'] ?? '');
        $status = $_POST['status'] === 'Hoạt động' ? 1 : 0;

        if ($name === '' || $email === '' || $phone === '') {
            self::error('Vui lòng điền đầy đủ thông tin khách hàng');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::error('Email không hợp lệ');
        }

        try {
            self::transaction(function() use ($id, $name, $email, $phone, $address, $status) {
                if ($id > 0) {
                    // ✅ SỬA: Bỏ ThanhPho, GhiChu
                    self::query(
                        "UPDATE nguoidung 
                         SET HoTen = ?, Email = ?, SoDienThoai = ?, DiaChi = ?, TrangThai = ?
                         WHERE MaNguoiDung = ?",
                        [$name, $email, $phone, $address, $status, $id]
                    );

                    self::log('update_customer', ['customer_id' => $id]);
                } else {
                    self::query(
                        "INSERT INTO nguoidung (HoTen, Email, SoDienThoai, DiaChi, TrangThai, VaiTro, NgayTao)
                         VALUES (?, ?, ?, ?, ?, '', NOW())",
                        [$name, $email, $phone, $address, $status]
                    );

                    $id = self::db()->insert_id;
                    self::log('create_customer', ['customer_id' => $id]);
                }
            });

            self::success('Thành công!', ['customer_id' => $id]);

        } catch (Throwable $e) {
            error_log("Save Customer Error: " . $e->getMessage());
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                self::error('Email đã tồn tại');
            }
            self::error($e->getMessage());
        }
    }

    private static function delete(): never
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) self::error('Thiếu ID');

        try {
            self::transaction(function() use ($id) {
                $customer = self::fetchRow(
                    "SELECT HoTen FROM nguoidung WHERE MaNguoiDung = ?",
                    [$id]
                );

                if (!$customer) throw new RuntimeException('Không tìm thấy khách hàng');

                $orderCount = (int)self::fetchOne(
                    "SELECT COUNT(*) FROM donhang WHERE MaNguoiDung = ?",
                    [$id]
                );

                if ($orderCount > 0) {
                    throw new RuntimeException("Khách hàng đã có {$orderCount} đơn hàng");
                }

                self::query("DELETE FROM nguoidung WHERE MaNguoiDung = ?", [$id]);
                self::log('delete_customer', ['id' => $id]);
            });

            self::success('Xóa thành công!');

        } catch (Throwable $e) {
            error_log("Delete Error: " . $e->getMessage());
            self::error($e->getMessage());
        }
    }

    private static function toggleStatus(): never
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) self::error('Thiếu ID');

        try {
            self::transaction(function() use ($id) {
                $current = (int)self::fetchOne(
                    "SELECT TrangThai FROM nguoidung WHERE MaNguoiDung = ?",
                    [$id]
                );

                $new = $current === 1 ? 0 : 1;
                self::query(
                    "UPDATE nguoidung SET TrangThai = ? WHERE MaNguoiDung = ?",
                    [$new, $id]
                );

                self::log('toggle_customer_status', ['id' => $id, 'new' => $new]);
            });

            self::success('Cập nhật thành công!');

        } catch (Throwable $e) {
            error_log("Toggle Error: " . $e->getMessage());
            self::error($e->getMessage());
        }
    }

    private static function recomputeAllRanks(): never
    {
        try {
            $customers = self::fetchAll(
                "SELECT 
                    nd.MaNguoiDung,
                    COALESCE(SUM(
                        CASE 
                            WHEN dh.TrangThai IN ('" . implode("','", self::PAID_STATUSES) . "')
                            AND dh.NgayDat >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                            THEN dh.TongTien 
                            ELSE 0 
                        END
                    ), 0) as total_spent
                 FROM nguoidung nd
                 LEFT JOIN donhang dh ON nd.MaNguoiDung = dh.MaNguoiDung
                 WHERE (nd.VaiTro = '' OR nd.VaiTro IS NULL OR nd.VaiTro = 'USER')
                 GROUP BY nd.MaNguoiDung",
                [self::RANK_WINDOW_DAYS]
            );

            $updated = 0;
            self::transaction(function() use ($customers, &$updated) {
                foreach ($customers as $customer) {
                    $rank = self::computeRank((float)$customer['total_spent']);
                    self::updateCustomerRank((int)$customer['MaNguoiDung'], $rank);
                    $updated++;
                }
            });

            self::log('recompute_all_ranks', ['count' => $updated]);
            self::success("Đã cập nhật {$updated} khách hàng!");

        } catch (Throwable $e) {
            error_log("Recompute Error: " . $e->getMessage());
            self::error($e->getMessage());
        }
    }

    public static function getStatistics(): array
    {
        // ✅ SỬA: Dùng đúng tên cột Hang
        $vaiTroFilter = "(VaiTro = '' OR VaiTro IS NULL OR VaiTro = 'USER')";

        return [
            'total_customers' => (int)self::fetchOne("SELECT COUNT(*) FROM nguoidung WHERE {$vaiTroFilter}"),
            'active' => (int)self::fetchOne("SELECT COUNT(*) FROM nguoidung WHERE {$vaiTroFilter} AND TrangThai = 1"),
            'inactive' => (int)self::fetchOne("SELECT COUNT(*) FROM nguoidung WHERE {$vaiTroFilter} AND TrangThai = 0"),
            'gold' => (int)self::fetchOne("SELECT COUNT(*) FROM nguoidung WHERE {$vaiTroFilter} AND Hang = 'Gold'"),
            'silver' => (int)self::fetchOne("SELECT COUNT(*) FROM nguoidung WHERE {$vaiTroFilter} AND Hang = 'Silver'"),
            'bronze' => (int)self::fetchOne("SELECT COUNT(*) FROM nguoidung WHERE {$vaiTroFilter} AND Hang = 'Bronze'"),
            'new' => (int)self::fetchOne("SELECT COUNT(*) FROM nguoidung WHERE {$vaiTroFilter} AND Hang = 'Mới'"),
        ];
    }
}