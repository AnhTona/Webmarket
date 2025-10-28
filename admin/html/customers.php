<?php
require_once __DIR__ . '/../controller/Customers_Controller.php';
$ctx = CustomersController::handle();
extract($ctx, EXTR_OVERWRITE);

// Thiết lập tiêu đề trang
$page_title = 'Quản Lý Khách Hàng';

// Bắt đầu output buffering
ob_start();
?>

    <!-- CSS cho trang Customers -->
    <link rel="stylesheet" href="../css/customers.css">

    <h1 class="text-3xl font-bold text-gray-800 mb-6">Quản Lý Khách Hàng</h1>

    <!-- Stats Summary -->
    <div class="mb-6 stats-card bg-white p-4 rounded-lg shadow-sm border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-100 font-medium">Tổng số khách hàng</p>
                <p class="text-3xl font-bold text-white"><?= number_format($total_customers, 0, ',', '.') ?></p>
            </div>
            <i class="fas fa-users text-5xl text-white opacity-20"></i>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar mb-6">
        <div class="search-group">
            <input type="text" id="search-input" placeholder="Tìm theo: Họ tên, SĐT, Email, Mã KH...">
            <button id="btn-search" class="btn-search">
                <i class="fas fa-search"></i> Tìm kiếm
            </button>
        </div>
        <div class="filter-group">
            <label for="filter-status">Trạng thái:</label>
            <select id="filter-status">
                <option value="All">Tất cả</option>
                <option value="Hoạt động">Hoạt động</option>
                <option value="Ngừng">Ngừng</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="filter-rank">Hạng KH:</label>
            <select id="filter-rank">
                <option value="All">Tất cả</option>
                <option value="Gold">Gold</option>
                <option value="Silver">Silver</option>
                <option value="Bronze">Bronze</option>
                <option value="Mới">Mới</option>
            </select>
        </div>
    </div>

    <!-- Customer Table -->
    <div class="customer-table-container bg-white rounded-lg shadow-sm overflow-hidden">
        <table id="customer-list-table" class="customer-table w-full">
            <thead>
            <tr>
                <th>Mã KH</th>
                <th>Họ tên</th>
                <th>Email</th>
                <th>SĐT</th>
                <th>Hạng</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!empty($customer_list)): ?>
                <?php foreach ($customer_list as $customer): ?>
                    <tr data-id="<?= $customer['id'] ?>">
                        <td class="font-semibold text-gray-700">#<?= $customer['id'] ?></td>
                        <td><?= htmlspecialchars($customer['name']) ?></td>
                        <td><?= htmlspecialchars($customer['email']) ?></td>
                        <td><?= htmlspecialchars($customer['phone']) ?></td>
                        <td>
                            <span class="rank-badge rank-<?= strtolower($customer['rank']) ?>">
                                <?php if ($customer['rank'] === 'Gold'): ?>
                                    <i class="fas fa-crown"></i>
                                <?php elseif ($customer['rank'] === 'Silver'): ?>
                                    <i class="fas fa-medal"></i>
                                <?php elseif ($customer['rank'] === 'Bronze'): ?>
                                    <i class="fas fa-award"></i>
                                <?php else: ?>
                                    <i class="fas fa-star"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars($customer['rank']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-<?= ($customer['status'] == 'Hoạt động' ? 'active' : 'inactive') ?>">
                                <i class="fas fa-circle text-xs mr-1"></i>
                                <?= htmlspecialchars($customer['status']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($customer['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center py-12">
                        <div class="text-gray-400">
                            <i class="fa-solid fa-users-slash text-5xl mb-3 opacity-50"></i>
                            <p class="text-lg font-medium">Không có khách hàng nào</p>
                            <p class="text-sm mt-1">Thử điều chỉnh bộ lọc hoặc tìm kiếm</p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <div class="pagination-container mt-6 flex items-center justify-between">
        <!-- Page Info -->
        <div class="pagination-info">
            Hiển thị <?= min(($page - 1) * $per_page + 1, $total_customers) ?> - <?= min($page * $per_page, $total_customers) ?>
            trong tổng số <strong><?= number_format($total_customers, 0, ',', '.') ?></strong> khách hàng
        </div>

        <!-- Pagination Buttons -->
        <div class="pagination-buttons">
            <?php
            $current_query = $_GET;

            // Previous button
            if ($page > 1):
                $current_query['page'] = $page - 1;
                $prev_url = '?' . http_build_query($current_query);
                ?>
                <a href="<?= htmlspecialchars($prev_url) ?>">
                    <i class="fas fa-chevron-left"></i> Trước
                </a>
            <?php endif; ?>

            <?php
            // Page numbers
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);

            if ($start > 1):
                ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
                <?php if ($start > 2): ?>
                <span class="dots">...</span>
            <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start; $i <= $end; $i++):
                $current_query['page'] = $i;
                $url = '?' . http_build_query($current_query);
                $isActive = ($i === $page);
                ?>
                <a href="<?= htmlspecialchars($url) ?>" class="<?= $isActive ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($end < $total_pages): ?>
                <?php if ($end < $total_pages - 1): ?>
                    <span class="dots">...</span>
                <?php endif; ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>"><?= $total_pages ?></a>
            <?php endif; ?>

            <!-- Next button -->
            <?php if ($page < $total_pages):
                $current_query['page'] = $page + 1;
                $next_url = '?' . http_build_query($current_query);
                ?>
                <a href="<?= htmlspecialchars($next_url) ?>">
                    Sau <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

    <!-- JavaScript -->
    <script>
        window.customersData = <?= json_encode($customer_list ?? [], JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="../js/customers.js"></script>

<?php
// Lấy nội dung đã capture
$content = ob_get_clean();

// Include template
require_once __DIR__ . '/template.php';
?>