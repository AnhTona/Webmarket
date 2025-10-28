<?php
require_once __DIR__ . '/../controller/Reports_Controller.php';
$ctx = ReportsController::handle();
extract($ctx, EXTR_OVERWRITE);

$page_title = 'Báo Cáo & Thống Kê';
ob_start();
?>

    <link rel="stylesheet" href="../css/reports.css">

    <h1 class="text-3xl font-bold text-gray-800 mb-6">Báo Cáo & Thống Kê</h1>

    <!-- Date Filter -->
    <div class="bg-white p-6 rounded-lg shadow-sm mb-6">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-2">Từ ngày</label>
                <input type="date" name="from_date" value="<?= htmlspecialchars($from_date) ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-2">Đến ngày</label>
                <input type="date" name="to_date" value="<?= htmlspecialchars($to_date) ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
            </div>
            <button type="submit" class="px-6 py-2 brand-bg text-white rounded-lg brand-bg-hover transition">
                <i class="fas fa-filter"></i> Lọc dữ liệu
            </button>
        </form>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-green-500">
            <p class="text-sm text-gray-600 mb-1">Tổng doanh thu</p>
            <p class="text-2xl font-bold text-gray-900"><?= number_format($summary_stats['total_revenue'], 0, ',', '.') ?> ₫</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-blue-500">
            <p class="text-sm text-gray-600 mb-1">Tổng đơn hàng</p>
            <p class="text-2xl font-bold text-gray-900"><?= number_format($summary_stats['total_orders']) ?></p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-yellow-500">
            <p class="text-sm text-gray-600 mb-1">Giá trị trung bình/đơn</p>
            <p class="text-2xl font-bold text-gray-900"><?= number_format($summary_stats['avg_order_value'], 0, ',', '.') ?> ₫</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-purple-500">
            <p class="text-sm text-gray-600 mb-1">Giá trị tồn kho</p>
            <p class="text-2xl font-bold text-gray-900"><?= number_format($summary_stats['stock_value'], 0, ',', '.') ?> ₫</p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex space-x-4 px-6" role="tablist">
                <button class="tab-button active" data-tab="daily-revenue">
                    <i class="fas fa-chart-line mr-2"></i>Doanh thu theo ngày
                </button>
                <button class="tab-button" data-tab="inventory">
                    <i class="fas fa-boxes mr-2"></i>Tồn kho
                </button>
                <button class="tab-button" data-tab="top-products">
                    <i class="fas fa-star mr-2"></i>Sản phẩm bán chạy
                </button>
                <button class="tab-button" data-tab="category">
                    <i class="fas fa-layer-group mr-2"></i>Theo danh mục
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Doanh thu theo ngày -->
            <div id="daily-revenue" class="tab-content active">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">Doanh thu theo ngày</h2>
                    <a href="?export=daily_revenue&from_date=<?= $from_date ?>&to_date=<?= $to_date ?>"
                       class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-file-excel mr-2"></i>Xuất Excel
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Ngày</th>
                            <th class="px-4 py-3 text-right">Số đơn</th>
                            <th class="px-4 py-3 text-right">Doanh thu</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y">
                        <?php foreach ($daily_revenue as $row): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3"><?= date('d/m/Y', strtotime($row['date'])) ?></td>
                                <td class="px-4 py-3 text-right"><?= $row['total_orders'] ?></td>
                                <td class="px-4 py-3 text-right font-semibold text-green-600">
                                    <?= number_format($row['total_revenue'], 0, ',', '.') ?> ₫
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tồn kho -->
            <div id="inventory" class="tab-content">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">Báo cáo tồn kho</h2>
                    <a href="?export=inventory"
                       class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-file-excel mr-2"></i>Xuất Excel
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Sản phẩm</th>
                            <th class="px-4 py-3 text-left">Danh mục</th>
                            <th class="px-4 py-3 text-right">Tồn kho</th>
                            <th class="px-4 py-3 text-right">Đơn giá</th>
                            <th class="px-4 py-3 text-right">Giá trị tồn</th>
                            <th class="px-4 py-3 text-center">Trạng thái</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y">
                        <?php foreach ($inventory_stock as $row): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium"><?= htmlspecialchars($row['product_name']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($row['category']) ?></td>
                                <td class="px-4 py-3 text-right"><?= $row['quantity'] ?></td>
                                <td class="px-4 py-3 text-right"><?= number_format($row['price'], 0, ',', '.') ?> ₫</td>
                                <td class="px-4 py-3 text-right font-semibold"><?= number_format($row['stock_value'], 0, ',', '.') ?> ₫</td>
                                <td class="px-4 py-3 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-medium
                                    <?php
                                echo $row['status'] === 'Hết hàng' ? 'bg-red-100 text-red-700' :
                                        ($row['status'] === 'Sắp hết' ? 'bg-yellow-100 text-yellow-700' :
                                                ($row['status'] === 'Tồn ít' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'));
                                ?>">
                                    <?= $row['status'] ?>
                                </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top products -->
            <div id="top-products" class="tab-content">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Top 10 sản phẩm bán chạy</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">STT</th>
                            <th class="px-4 py-3 text-left">Sản phẩm</th>
                            <th class="px-4 py-3 text-right">Đã bán</th>
                            <th class="px-4 py-3 text-right">Doanh thu</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y">
                        <?php foreach ($top_products as $index => $row): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-bold text-gray-600">#<?= $index + 1 ?></td>
                                <td class="px-4 py-3 font-medium"><?= htmlspecialchars($row['product_name']) ?></td>
                                <td class="px-4 py-3 text-right"><?= $row['total_sold'] ?></td>
                                <td class="px-4 py-3 text-right font-semibold text-green-600">
                                    <?= number_format($row['total_revenue'], 0, ',', '.') ?> ₫
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Category revenue -->
            <div id="category" class="tab-content">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Doanh thu theo danh mục</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Danh mục</th>
                            <th class="px-4 py-3 text-right">Số đơn</th>
                            <th class="px-4 py-3 text-right">Số lượng bán</th>
                            <th class="px-4 py-3 text-right">Doanh thu</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y">
                        <?php foreach ($revenue_by_category as $row): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium"><?= htmlspecialchars($row['category']) ?></td>
                                <td class="px-4 py-3 text-right"><?= $row['total_orders'] ?></td>
                                <td class="px-4 py-3 text-right"><?= $row['total_quantity'] ?></td>
                                <td class="px-4 py-3 text-right font-semibold text-green-600">
                                    <?= number_format($row['total_revenue'], 0, ',', '.') ?> ₫
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/reports.js"></script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/template.php';
?>