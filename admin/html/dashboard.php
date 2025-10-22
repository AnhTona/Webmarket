<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../controller/Dashboard_Controller.php';
$ctx = DashboardController::handle();
extract($ctx, EXTR_OVERWRITE);

// Không cần khai báo $notifications và $notification_count nữa
// Vì template.php sẽ tự động load từ Notifications_Controller

ob_start();
?>

    <h1 class="text-3xl font-bold text-gray-800 mb-6">Trang Tổng Quan</h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- DOANH THU (THÁNG) -->
        <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-green-500 hover:shadow-xl transition duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Doanh Thu (Tháng)</p>
                    <p class="text-3xl font-extrabold text-gray-900 mt-1">
                        <?= number_format($kpis['revenue_month'], 0, ',', '.') ?> <span class="text-sm">VNĐ</span>
                    </p>
                </div>
                <i class="fa-solid fa-sack-dollar text-3xl text-green-500 p-3 bg-green-100 rounded-full"></i>
            </div>
            <p class="text-xs <?= $kpis['revenue_month_change_pct'] >= 0 ? 'text-green-600' : 'text-red-600' ?> mt-3">
                <i class="fa-solid fa-arrow-<?= $kpis['revenue_month_change_pct'] >= 0 ? 'up' : 'down' ?> mr-1"></i>
                <?= $kpis['revenue_month_pct_text'] ?> so với tháng trước
            </p>
        </div>

        <!-- ĐƠN HÀNG (TUẦN) -->
        <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-blue-500 hover:shadow-xl transition duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Đơn Hàng (Tuần)</p>
                    <p class="text-3xl font-extrabold text-gray-900 mt-1">
                        <?= $kpis['orders_7d_current'] ?>
                    </p>
                </div>
                <i class="fa-solid fa-receipt text-3xl text-blue-500 p-3 bg-blue-100 rounded-full"></i>
            </div>
            <p class="text-xs <?= $kpis['orders_7d_change_pct'] >= 0 ? 'text-green-600' : 'text-red-600' ?> mt-3">
                <i class="fa-solid fa-arrow-<?= $kpis['orders_7d_change_pct'] >= 0 ? 'up' : 'down' ?> mr-1"></i>
                <?= $kpis['orders_7d_pct_text'] ?> so với tuần trước
            </p>
        </div>

        <!-- KHÁCH HÀNG MỚI -->
        <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-yellow-500 hover:shadow-xl transition duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Khách Hàng Mới</p>
                    <p class="text-3xl font-extrabold text-gray-900 mt-1">
                        <?= $kpis['new_customers_7d'] ?>
                    </p>
                </div>
                <i class="fa-solid fa-user-plus text-3xl text-yellow-600 p-3 bg-yellow-100 rounded-full"></i>
            </div>
            <p class="text-xs <?= $kpis['customers_7d_change_pct'] >= 0 ? 'text-green-600' : 'text-red-600' ?> mt-3">
                <i class="fa-solid fa-arrow-<?= $kpis['customers_7d_change_pct'] >= 0 ? 'up' : 'down' ?> mr-1"></i>
                <?= $kpis['customers_7d_pct_text'] ?> so với tuần trước
            </p>
        </div>

        <!-- SẢN PHẨM TỒN KHO -->
        <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 brand-bg hover:shadow-xl transition duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-100 uppercase tracking-wider">Sản Phẩm Tồn Kho</p>
                    <p class="text-3xl font-extrabold text-white mt-1">
                        <?= number_format($kpis['stock_total'], 0, ',', '.') ?>
                    </p>
                </div>
                <i class="fa-solid fa-warehouse text-3xl text-white p-3 bg-gray-600 rounded-full"></i>
            </div>
            <p class="text-xs text-gray-200 mt-3">Đã kiểm tra vừa xong</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- BIỂU ĐỒ DOANH THU 7 NGÀY -->
        <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-lg">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Biểu Đồ Doanh Thu 7 Ngày Gần Nhất</h2>
            <div class="h-64 flex items-end justify-around border-b border-l border-gray-300 p-2">
                <?php foreach ($bars_7d as $b): ?>
                    <div class="flex flex-col items-center w-8">
                        <div class="w-full brand-bg chart-bar rounded-t-sm"
                             style="--final-height: <?= $b['pct'] ?>%;"
                             title="<?= htmlspecialchars($b['title']) ?>"></div>
                        <span class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($b['dow']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 5 ĐƠN HÀNG MỚI NHẤT -->
        <div class="bg-white p-6 rounded-xl shadow-lg">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">5 Đơn Hàng Mới Nhất</h2>
            <ul class="space-y-4">
                <?php if (!empty($recent_orders)): ?>
                    <?php foreach ($recent_orders as $o): ?>
                        <li class="flex items-center justify-between border-b pb-2">
                            <div class="flex flex-col">
                            <span class="font-medium text-gray-900">
                                #<?= htmlspecialchars($o['MaDonHang']) ?> - <?= htmlspecialchars($o['KhachHang']) ?>
                            </span>
                                <span class="text-xs text-gray-500">
                                <?= date('d/m/Y H:i', strtotime($o['NgayDat'])) ?> •
                                <span class="<?= $o['TrangThai'] === 'DONE' ? 'text-green-600' : 'text-blue-600' ?>">
                                    <?= $o['TrangThai'] ?>
                                </span>
                            </span>
                            </div>
                            <span class="font-bold brand-primary">
                            <?= number_format((int)$o['TongTien'] / 1000, 0) ?>K
                        </span>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="flex items-center justify-center py-8 text-gray-400">
                        <div class="text-center">
                            <i class="fa-solid fa-inbox text-4xl mb-2"></i>
                            <p>Chưa có đơn hàng nào</p>
                        </div>
                    </li>
                <?php endif; ?>
            </ul>
            <a href="orders.php" class="block w-full mt-4 py-2 brand-bg text-white rounded-lg text-center brand-bg-hover transition duration-200 text-sm font-medium">
                Xem Tất Cả Đơn Hàng
            </a>
        </div>
    </div>

    <script src="../js/dashboard.js"></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/template.php';