<?php
declare(strict_types=1);

/**
 * Customers_Controller.php
 * Customer management controller with PHP 8.4 features
 *
 * @package Admin\Controller
 * @author AnhTona
 * @version 2.0.0
 * @since PHP 8.4
 */

require_once __DIR__ . '/../../model/database.php';
require_once __DIR__ . '/BaseController.php';

final class CustomersController extends BaseController
{
    /**
     * Membership rank thresholds and time window
     */
    private const RANK_WINDOW_DAYS = 365; // 12 months
    private const RANK_SILVER_MIN = 2_000_000;
    private const RANK_GOLD_MIN = 5_000_000;

    /**
     * Order statuses counted for revenue
     */
    private const PAID_STATUSES = ['CONFIRMED', 'SHIPPING', 'DONE'];

    /**
     * Handle customer requests
     *
     * @return array<string, mixed>
     */
    public static function handle(): array
    {
        self::requireAuth();

        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? $_POST['action'] ?? 'index';

        // AJAX requests
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

        // List view
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

    /**
     * List customers with filters
     *
     * @return array<string, mixed>
     */
    private static function index(): array
    {
        $perPage = max(1, (int)($_GET['per_page'] ?? 20));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        // Filters
        $search = self::sanitize($_GET['q'] ?? $_GET['search'] ?? '');
        $status = $_GET['status'] ?? 'All';
        $rank = $_GET['rank'] ?? 'All';
        $city = self::sanitize($_GET['city'] ?? '');
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';

        [$where, $params] = self::buildWhereClause($search, $status, $rank, $city, $dateFrom, $dateTo);

        // Count total
        $total = (int)self::fetchOne(
            "SELECT COUNT(*) FROM nguoidung nd {$where}",
            $params
        );

        $totalPages = max(1, (int)ceil($total / $perPage));

        // Fetch customers with computed rank
        $customers = self::fetchAll(
            "SELECT 
                nd.MaNguoiDung as id,
                nd.HoTen as name,
                nd.Email as email,
                nd.SoDienThoai as phone,
                nd.DiaChi as address,
                nd.ThanhPho as city,
                nd.HangThanhVien as rank,
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

        // Update ranks if needed
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

    /**
     * Build WHERE clause for filters
     *
     * @return array{string, array<int, mixed>}
     */
    private static function buildWhereClause(
        string $search,
        string $status,
        string $rank,
        string $city,
        string $dateFrom,
        string $dateTo
    ): array {
        $where = ["nd.VaiTro = 'CUSTOMER'"]; // Only customers
        $params = [];

        if ($search !== '') {
            $where[] = "(nd.HoTen LIKE ? OR nd.Email LIKE ? OR nd.SoDienThoai LIKE ? OR CAST(nd.MaNguoiDung AS CHAR) LIKE ?)";
            $searchParam = "%{$search}%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        }

        if ($status === 'Hoạt động') {
            $where[] = "nd.TrangThai = 1";
        } elseif ($status === 'Ngừng') {
            $where[] = "nd.TrangThai = 0";
        }

        if ($rank !== 'All' && $rank !== '') {
            $where[] = "nd.HangThanhVien = ?";
            $params[] = $rank;
        }

        if ($city !== '') {
            $where[] = "nd.ThanhPho LIKE ?";
            $params[] = "%{$city}%";
        }

        if ($dateFrom !== '') {
            $where[] = "DATE(nd.NgayTao) >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $where[] = "DATE(nd.NgayTao) <= ?";
            $params[] = $dateTo;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        return [$whereClause, $params];
    }

    /**
     * Compute membership rank from spending amount
     */
    private static function computeRank(float $amount): string
    {
        return match(true) {
            $amount >= self::RANK_GOLD_MIN => 'Gold',
            $amount >= self::RANK_SILVER_MIN => 'Silver',
            $amount > 0 => 'Bronze',
            default => 'Mới'
        };
    }

    /**
     * Update customer rank in database
     */
    private static function updateCustomerRank(int $customerId, string $rank): void
    {
        self::query(
            "UPDATE nguoidung SET HangThanhVien = ? WHERE MaNguoiDung = ?",
            [$rank, $customerId]
        );
    }

    /**
     * Save customer (create or update)
     *
     * @return never
     */
    private static function save(): never
    {
        $id = (int)($_POST['id'] ?? 0);
        $name = self::sanitize($_POST['name'] ?? '');
        $email = self::sanitize($_POST['email'] ?? '');
        $phone = self::sanitize($_POST['phone'] ?? '');
        $address = self::sanitize($_POST['address'] ?? '');
        $city = self::sanitize($_POST['city'] ?? '');
        $status = $_POST['status'] === 'Hoạt động' ? 1 : 0;
        $notes = self::sanitize($_POST['notes'] ?? '');

        // Validate
        if ($name === '' || $email === '' || $phone === '') {
            self::error('Vui lòng điền đầy đủ thông tin khách hàng');
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::error('Email không hợp lệ');
        }

        try {
            self::transaction(function() use ($id, $name, $email, $phone, $address, $city, $status, $notes) {
                if ($id > 0) {
                    // Update existing customer
                    self::query(
                        "UPDATE nguoidung 
                         SET HoTen = ?, Email = ?, SoDienThoai = ?, DiaChi = ?, ThanhPho = ?, TrangThai = ?, GhiChu = ?
                         WHERE MaNguoiDung = ?",
                        [$name, $email, $phone, $address, $city, $status, $notes, $id]
                    );

                    self::log('update_customer', ['customer_id' => $id, 'name' => $name]);
                } else {
                    // Create new customer
                    self::query(
                        "INSERT INTO nguoidung (HoTen, Email, SoDienThoai, DiaChi, ThanhPho, TrangThai, GhiChu, VaiTro, NgayTao)
                         VALUES (?, ?, ?, ?, ?, ?, ?, 'CUSTOMER', NOW())",
                        [$name, $email, $phone, $address, $city, $status, $notes]
                    );

                    $id = self::db()->insert_id;
                    self::log('create_customer', ['customer_id' => $id, 'name' => $name]);
                }
            });

            self::success(
                $id > 0 ? 'Cập nhật khách hàng thành công!' : 'Thêm khách hàng thành công!',
                ['customer_id' => $id]
            );

        } catch (Throwable $e) {
            error_log("Save Customer Error: " . $e->getMessage());

            // Check for duplicate email
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                self::error('Email đã tồn tại trong hệ thống');
            }

            self::error($e->getMessage());
        }
    }

    /**
     * Delete customer
     *
     * @return never
     */
    private static function delete(): never
    {
        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            self::error('Thiếu ID khách hàng');
        }

        try {
            self::transaction(function() use ($id) {
                // Get customer info for logging
                $customer = self::fetchRow(
                    "SELECT HoTen, Email FROM nguoidung WHERE MaNguoiDung = ?",
                    [$id]
                );

                if (!$customer) {
                    throw new RuntimeException('Không tìm thấy khách hàng');
                }

                // Check if customer has orders
                $orderCount = (int)self::fetchOne(
                    "SELECT COUNT(*) FROM donhang WHERE MaNguoiDung = ?",
                    [$id]
                );

                if ($orderCount > 0) {
                    throw new RuntimeException(
                        "Không thể xóa khách hàng đã có {$orderCount} đơn hàng. Hãy vô hiệu hóa thay vì xóa."
                    );
                }

                // Delete customer
                self::query("DELETE FROM nguoidung WHERE MaNguoiDung = ?", [$id]);

                self::log('delete_customer', [
                    'customer_id' => $id,
                    'name' => $customer['HoTen'],
                    'email' => $customer['Email'],
                ]);
            });

            self::success('Xóa khách hàng thành công!');

        } catch (Throwable $e) {
            error_log("Delete Customer Error: " . $e->getMessage());
            self::error($e->getMessage());
        }
    }

    /**
     * Toggle customer status (active/inactive)
     *
     * @return never
     */
    private static function toggleStatus(): never
    {
        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            self::error('Thiếu ID khách hàng');
        }

        try {
            self::transaction(function() use ($id) {
                $currentStatus = (int)self::fetchOne(
                    "SELECT TrangThai FROM nguoidung WHERE MaNguoiDung = ?",
                    [$id]
                );

                $newStatus = $currentStatus === 1 ? 0 : 1;

                self::query(
                    "UPDATE nguoidung SET TrangThai = ? WHERE MaNguoiDung = ?",
                    [$newStatus, $id]
                );

                self::log('toggle_customer_status', [
                    'customer_id' => $id,
                    'from_status' => $currentStatus,
                    'to_status' => $newStatus,
                ]);
            });

            self::success('Cập nhật trạng thái thành công!');

        } catch (Throwable $e) {
            error_log("Toggle Status Error: " . $e->getMessage());
            self::error($e->getMessage());
        }
    }

    /**
     * Recompute ranks for all customers
     *
     * @return never
     */
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
                 WHERE nd.VaiTro = 'CUSTOMER'
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

            self::log('recompute_all_ranks', ['customers_updated' => $updated]);

            self::success("Đã cập nhật hạng cho {$updated} khách hàng!");

        } catch (Throwable $e) {
            error_log("Recompute Ranks Error: " . $e->getMessage());
            self::error($e->getMessage());
        }
    }

    /**
     * Get customer statistics
     *
     * @return array<string, mixed>
     */
    public static function getStatistics(): array
    {
        return [
            'total_customers' => (int)self::fetchOne("SELECT COUNT(*) FROM nguoidung WHERE VaiTro = 'CUSTOMER'"),
            'active' => (int)self::fetchOne("SELECT COUNT(*) FROM nguoidung WHERE VaiTro = 'CUSTOMER' AND TrangThai = 1"),
            'inactive' => (int)self::fetchOne("SELECT COUNT(*) FROM nguoidung WHERE VaiTro = 'CUSTOMER' AND TrangThai = 0"),
            'gold' => (int)self::fetchOne("SELECT COUNT(*) FROM nguoidung WHERE VaiTro = 'CUSTOMER' AND HangThanhVien = 'Gold'"),
            'silver' => (int)self::fetchOne("SELECT COUNT(*) FROM nguoidung WHERE VaiTro = 'CUSTOMER' AND HangThanhVien = 'Silver'"),
            'bronze' => (int)self::fetchOne("SELECT COUNT(*) FROM nguoidung WHERE VaiTro = 'CUSTOMER' AND HangThanhVien = 'Bronze'"),
            'new' => (int)self::fetchOne("SELECT COUNT(*) FROM nguoidung WHERE VaiTro = 'CUSTOMER' AND HangThanhVien = 'Mới'"),
        ];
    }
}