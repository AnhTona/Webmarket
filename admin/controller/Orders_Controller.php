<?php
declare(strict_types=1);

/**
 * Orders_Controller.php
 * Order management controller with PHP 8.4 features
 *
 * @package Admin\Controller
 * @author AnhTona
 * @version 2.0.0
 * @since PHP 8.4
 */

require_once __DIR__ . '/../../model/database.php';
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../html/Invoice_Generator.php';

final class OrdersController extends BaseController
{
    /**
     * Order status mapping (DB ↔ UI)
     * Using PHP 8.4 property hooks
     */
    private const array DB_TO_UI = [
        'DRAFT' => 'Nháp',
        'PLACED' => 'Chờ xác nhận',
        'CONFIRMED' => 'Đang chuẩn bị',
        'SHIPPING' => 'Đang giao',
        'DONE' => 'Hoàn thành',
        'CANCELLED' => 'Đã hủy',
    ];

    private const array UI_TO_DB = [
        'Nháp' => 'DRAFT',
        'Chờ xác nhận' => 'PLACED',
        'Đang chuẩn bị' => 'CONFIRMED',
        'Đang giao' => 'SHIPPING',
        'Hoàn thành' => 'DONE',
        'Đã hủy' => 'CANCELLED',
    ];

    /**
     * Membership discount rates
     */
    private const array DISCOUNT_RATES = [
        'Gold' => 0.15,
        'Silver' => 0.10,
        'Bronze' => 0.05,
        'Mới' => 0.00,
    ];

    /**
     * Handle order requests
     *
     * @return array<string, mixed>
     */
    public static function handle(): array
    {
        self::requireAuth();

        // AJAX requests
        if (self::isAjax()) {
            $action = $_GET['action'] ?? $_POST['action'] ?? '';

            try {
                match($action) {
                    'confirm' => self::updateStatus('CONFIRMED'),
                    'cancel' => self::updateStatus('CANCELLED'),
                    'complete' => self::updateStatus('DONE'),
                    'view' => self::viewInvoice(),
                    'generate_invoice' => self::generateInvoice(),
                    default => self::error('Invalid action')
                };
            } catch (Throwable $e) {
                error_log("Orders AJAX Error: " . $e->getMessage());
                self::error($e->getMessage());
            }
        }

        // View invoice HTML
        if (isset($_GET['view_invoice'])) {
            self::displayInvoice();
        }

        // List view
        try {
            [$orders, $page, $totalPages] = self::fetchList();
            return [
                'order_list' => $orders,
                'page' => $page,
                'total_pages' => $totalPages,
            ];
        } catch (Throwable $e) {
            error_log("Orders List Error: " . $e->getMessage());
            return [
                'order_list' => [],
                'page' => 1,
                'total_pages' => 1,
                'error' => 'Không thể tải danh sách đơn hàng',
            ];
        }
    }

    /**
     * Update order status with transaction
     */
    private static function updateStatus(string $newStatus): never
    {
        $orderId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

        if ($orderId <= 0) {
            self::error('Thiếu ID đơn hàng');
        }

        try {
            self::transaction(function() use ($orderId, $newStatus) {
                // Check if order exists
                $currentStatus = self::fetchOne(
                    "SELECT TrangThai FROM donhang WHERE MaDonHang = ?",
                    [$orderId]
                );

                if (!$currentStatus) {
                    throw new RuntimeException('Không tìm thấy đơn hàng');
                }

                // Validate status transition
                self::validateStatusTransition($currentStatus, $newStatus);

                // Update status
                self::query(
                    "UPDATE donhang SET TrangThai = ?, NgayCapNhat = NOW() WHERE MaDonHang = ?",
                    [$newStatus, $orderId]
                );

                // Create status history
                self::query(
                    "INSERT INTO donhang_lichsu (MaDonHang, TrangThaiCu, TrangThaiMoi, NguoiThayDoi, NgayThayDoi)
                     VALUES (?, ?, ?, ?, NOW())",
                    [$orderId, $currentStatus, $newStatus, $_SESSION['user_id']]
                );

                self::log('update_order_status', [
                    'order_id' => $orderId,
                    'from_status' => $currentStatus,
                    'to_status' => $newStatus,
                ]);
            });

            $statusText = self::DB_TO_UI[$newStatus] ?? $newStatus;
            self::success(
                message: "Đã cập nhật trạng thái thành: {$statusText}",
                data: [
                    'order_id' => $orderId,
                    'new_status' => $newStatus,
                    'status_text' => $statusText,
                ]
            );

        } catch (Throwable $e) {
            error_log("Update Status Error: " . $e->getMessage());
            self::error($e->getMessage());
        }
    }

    /**
     * Validate status transition
     */
    private static function validateStatusTransition(string $from, string $to): void
    {
        $validTransitions = [
            'DRAFT' => ['PLACED', 'CANCELLED'],
            'PLACED' => ['CONFIRMED', 'CANCELLED'],
            'CONFIRMED' => ['SHIPPING', 'DONE', 'CANCELLED'],
            'SHIPPING' => ['DONE', 'CANCELLED'],
            'DONE' => [],
            'CANCELLED' => [],
        ];

        if (!isset($validTransitions[$from])) {
            throw new RuntimeException("Trạng thái hiện tại không hợp lệ: {$from}");
        }

        if (!in_array($to, $validTransitions[$from], true)) {
            $fromText = self::DB_TO_UI[$from] ?? $from;
            $toText = self::DB_TO_UI[$to] ?? $to;
            throw new RuntimeException(
                "Không thể chuyển từ '{$fromText}' sang '{$toText}'"
            );
        }
    }

    /**
     * View invoice details for AJAX
     */
    private static function viewInvoice(): never
    {
        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            self::error('Thiếu ID đơn hàng');
        }

        try {
            // Get order info
            $order = self::fetchRow(
                "SELECT 
                    dh.*,
                    nd.HoTen AS KhachHang,
                    nd.Email,
                    nd.SoDienThoai,
                    nd.HangThanhVien AS HangTV,
                    pt.TenPhuongThuc AS PhuongThuc
                 FROM donhang dh
                 LEFT JOIN nguoidung nd ON dh.MaNguoiDung = nd.MaNguoiDung
                 LEFT JOIN phuongthucthanhtoan pt ON dh.MaPhuongThuc = pt.MaPhuongThuc
                 WHERE dh.MaDonHang = ?",
                [$id]
            );

            if (!$order) {
                self::error('Không tìm thấy đơn hàng');
            }

            // Get order items
            $items = self::fetchAll(
                "SELECT 
                    sp.TenSanPham,
                    sp.HinhAnh,
                    ctdh.SoLuong,
                    ctdh.GiaBan AS Gia,
                    (ctdh.SoLuong * ctdh.GiaBan) AS Tong
                 FROM chitietdonhang ctdh
                 LEFT JOIN sanpham sp ON ctdh.MaSanPham = sp.MaSanPham
                 WHERE ctdh.MaDonHang = ?",
                [$id]
            );

            // Calculate totals
            $subtotal = array_sum(array_column($items, 'Tong'));
            $discountRate = self::DISCOUNT_RATES[$order['HangTV']] ?? 0.00;
            $discountAmount = $subtotal * $discountRate;
            $afterDiscount = $subtotal - $discountAmount;
            $vat = $afterDiscount * 0.08;
            $grandTotal = $afterDiscount + $vat;

            self::success(
                message: 'Chi tiết đơn hàng',
                data: [
                    'order' => $order,
                    'items' => $items,
                    'calculations' => [
                        'subtotal' => $subtotal,
                        'discount_rate' => $discountRate,
                        'discount_amount' => $discountAmount,
                        'after_discount' => $afterDiscount,
                        'vat' => $vat,
                        'grand_total' => $grandTotal,
                    ],
                ]
            );

        } catch (Throwable $e) {
            error_log("View Invoice Error: " . $e->getMessage());
            self::error($e->getMessage());
        }
    }

    /**
     * Generate invoice PDF/HTML
     */
    private static function generateInvoice(): never
    {
        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            self::error('Thiếu ID đơn hàng');
        }

        try {
            // Check if invoice exists
            if (InvoiceGenerator::invoiceExists($id)) {
                self::success(
                    message: 'Invoice đã tồn tại',
                    data: [
                        'order_id' => $id,
                        'invoice_url' => "/Webmarket/admin/html/invoice.php?id={$id}",
                    ]
                );
            }

            // Generate new invoice
            $generated = InvoiceGenerator::generateInvoice($id);

            if (!$generated) {
                throw new RuntimeException('Không thể tạo invoice');
            }

            self::log('generate_invoice', ['order_id' => $id]);

            self::success(
                message: 'Tạo invoice thành công',
                data: [
                    'order_id' => $id,
                    'invoice_url' => "/Webmarket/admin/html/invoice.php?id={$id}",
                ]
            );

        } catch (Throwable $e) {
            error_log("Generate Invoice Error: " . $e->getMessage());
            self::error($e->getMessage());
        }
    }

    /**
     * Display invoice HTML
     */
    private static function displayInvoice(): never
    {
        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            die('Invalid order ID');
        }

        try {
            // Auto-generate if not exists
            if (!InvoiceGenerator::invoiceExists($id)) {
                InvoiceGenerator::generateInvoice($id);
            }

            // Render invoice
            InvoiceGenerator::displayInvoice($id);
            exit;

        } catch (Throwable $e) {
            error_log("Display Invoice Error: " . $e->getMessage());
            die('Không thể hiển thị invoice: ' . htmlspecialchars($e->getMessage()));
        }
    }

    /**
     * Fetch orders list with filters
     *
     * @return array{array<int, array<string, mixed>>, int, int}
     */
    private static function fetchList(): array
    {
        $perPage = max(1, (int)($_GET['per_page'] ?? 20));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        // Filters
        $search = self::sanitize($_GET['search'] ?? '');
        $status = $_GET['status'] ?? 'All';
        $date = $_GET['date'] ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';

        [$where, $params] = self::buildWhereClause($search, $status, $date, $dateFrom, $dateTo);

        // Count total
        $total = (int)self::fetchOne(
            "SELECT COUNT(*) 
             FROM donhang dh
             LEFT JOIN nguoidung nd ON dh.MaNguoiDung = nd.MaNguoiDung
             {$where}",
            $params
        );

        $totalPages = max(1, (int)ceil($total / $perPage));

        // Fetch data
        $orders = self::fetchAll(
            "SELECT 
                dh.MaDonHang AS MaDon,
                nd.HoTen AS KhachHang,
                dh.BanPhucVu AS Ban,
                dh.NgayDat,
                dh.TongTien,
                dh.TrangThai
             FROM donhang dh
             LEFT JOIN nguoidung nd ON dh.MaNguoiDung = nd.MaNguoiDung
             {$where}
             ORDER BY dh.NgayDat DESC
             LIMIT ? OFFSET ?",
            [...$params, $perPage, $offset]
        );

        // Convert status to UI format
        foreach ($orders as &$order) {
            $order['TrangThai'] = self::DB_TO_UI[$order['TrangThai']] ?? $order['TrangThai'];
        }

        return [$orders, $page, $totalPages];
    }

    /**
     * Build WHERE clause for filters
     *
     * @return array{string, array<int, mixed>}
     */
    private static function buildWhereClause(
        string $search,
        string $status,
        string $date,
        string $dateFrom,
        string $dateTo
    ): array {
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = "(CAST(dh.MaDonHang AS CHAR) LIKE ? OR nd.HoTen LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        if ($status !== 'All' && $status !== '') {
            $dbStatus = self::UI_TO_DB[$status] ?? $status;
            $where[] = "dh.TrangThai = ?";
            $params[] = $dbStatus;
        }

        if ($date !== '') {
            $where[] = "DATE(dh.NgayDat) = ?";
            $params[] = $date;
        }

        if ($dateFrom !== '') {
            $where[] = "DATE(dh.NgayDat) >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $where[] = "DATE(dh.NgayDat) <= ?";
            $params[] = $dateTo;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        return [$whereClause, $params];
    }

    /**
     * Get order statistics
     *
     * @return array<string, mixed>
     */
    public static function getStatistics(): array
    {
        return [
            'total_orders' => (int)self::fetchOne("SELECT COUNT(*) FROM donhang"),
            'pending' => (int)self::fetchOne("SELECT COUNT(*) FROM donhang WHERE TrangThai = 'PLACED'"),
            'processing' => (int)self::fetchOne("SELECT COUNT(*) FROM donhang WHERE TrangThai = 'CONFIRMED'"),
            'completed' => (int)self::fetchOne("SELECT COUNT(*) FROM donhang WHERE TrangThai = 'DONE'"),
            'cancelled' => (int)self::fetchOne("SELECT COUNT(*) FROM donhang WHERE TrangThai = 'CANCELLED'"),
            'total_revenue' => (float)self::fetchOne(
                "SELECT COALESCE(SUM(TongTien), 0) FROM donhang WHERE TrangThai = 'DONE'"
            ),
        ];
    }
}