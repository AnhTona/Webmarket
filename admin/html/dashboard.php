<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../controller/Dashboard_Controller.php';
$ctx = DashboardController::handle();
extract($ctx, EXTR_OVERWRITE); // $kpis, $revenue_series, $top_products, $recent_orders ...

// Chuông thông báo (nếu template của bạn dùng)
$notifications      = $notifications ?? [];
$notification_count = is_array($notifications) ? count($notifications) : 0;

/* ====== BỔ SUNG: 2 số liệu chưa có trong controller (khách mới tuần, tồn kho) ====== */
@include_once __DIR__ . '/../db.php'; // lấy $conn nếu có db.php
$new_customers_week = 0;
$stock_total = 0;

// Khách hàng mới 7 ngày gần nhất
if (isset($conn) && $conn instanceof mysqli) {
    if ($stmt = $conn->prepare("SELECT COUNT(*) FROM nguoidung WHERE VaiTro='USER' AND NgayTao >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")) {
        $stmt->execute(); $stmt->bind_result($new_customers_week); $stmt->fetch(); $stmt->close();
    }
    // Tổng tồn kho: ưu tiên SoLuongTon, fallback SoLuong, nếu không có thì đếm sản phẩm
    if ($stmt = $conn->prepare("SELECT 
        COALESCE(SUM(SoLuongTon), SUM(SoLuong), COUNT(*)) AS total_stock
        FROM sanpham")) {
        $stmt->execute(); $stmt->bind_result($stock_total); $stmt->fetch(); $stmt->close();
    }
}

// Chuẩn bị dữ liệu 7 ngày gần nhất cho biểu đồ cột (dựa trên $revenue_series của controller)
$labelsAll = $revenue_series['labels'] ?? [];
$seriesAll = $revenue_series['series'] ?? [];
$cnt = count($labelsAll);
$start = max(0, $cnt - 7);

$bars = []; // mỗi phần tử: ['dow'=>'T2|...|CN', 'value'=>int, 'pct'=>0..100, 'title'=>'T3: 20M']
$max7 = 0;
for ($i = $start; $i < $cnt; $i++) {
    $day = $labelsAll[$i] ?? date('Y-m-d');
    $val = (int)($seriesAll[$i] ?? 0);
    $w   = date('N', strtotime($day)); // 1..7 (Mon..Sun)
    $dow = ['','T2','T3','T4','T5','T6','T7','CN'][$w];
    $bars[] = ['day'=>$day, 'dow'=>$dow, 'value'=>$val];
    if ($val > $max7) $max7 = $val;
}
if (!$bars) {
    // Nếu chưa có dữ liệu, dựng mảng 7 ngày rỗng để UI không vỡ
    for ($j=6; $j>=0; $j--) {
        $d = date('Y-m-d', strtotime("-$j day"));
        $w = date('N', strtotime($d));
        $dow = ['','T2','T3','T4','T5','T6','T7','CN'][$w];
        $bars[] = ['day'=>$d, 'dow'=>$dow, 'value'=>0];
    }
}
foreach ($bars as &$b) {
    $pct = $max7 > 0 ? round($b['value'] * 100 / $max7, 2) : 5; // min 5% cho có thanh
    $b['pct'] = max($pct, $b['value'] > 0 ? 8 : 3); // nhìn đẹp hơn
    // Title: ví dụ "T3: 20M" (làm tròn theo triệu nếu lớn)
    $million = $b['value'] >= 1_000_000 ? round($b['value'] / 1_000_000) . 'M' : number_format($b['value'], 0, ',', '.');
    $b['title'] = "{$b['dow']}: {$million}";
}
unset($b);

// Các KPI chính từ controller
$rev30  = (int)($kpis['revenue_30d']     ?? 0);
$totalOrders = (int)($kpis['total_orders'] ?? 0);

ob_start();
?>

    <h1 class="text-3xl font-bold text-gray-800 mb-6">Trang Tổng Quan</h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Doanh thu (Tháng) -->
        <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-green-500 hover:shadow-xl transition duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Doanh Thu (Tháng)</p>
                    <p class="text-3xl font-extrabold text-gray-900 mt-1">
                        <?= number_format($rev30, 0, ',', '.') ?> VNĐ
                        <span class="text-sm"></span>
                    </p>
                </div>
                <i class="fa-solid fa-sack-dollar text-3xl text-green-500 p-3 bg-green-100 rounded-full"></i>
            </div>
            <p class="text-xs text-green-600 mt-3"><i class="fa-solid fa-arrow-up mr-1"></i> +12% so với tháng trước</p>
        </div>

        <!-- Đơn hàng (Tuần) - tạm hiển thị tổng đơn (nếu muốn đúng "tuần", mình có thể thêm vào controller) -->
        <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-blue-500 hover:shadow-xl transition duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Đơn Hàng (Tuần)</p>
                    <p class="text-3xl font-extrabold text-gray-900 mt-1">
                        <?= $totalOrders ?>
                    </p>
                </div>
                <i class="fa-solid fa-receipt text-3xl text-blue-500 p-3 bg-blue-100 rounded-full"></i>
            </div>
            <p class="text-xs text-red-600 mt-3"><i class="fa-solid fa-arrow-down mr-1"></i> -3% so với tuần trước</p>
        </div>

        <!-- Khách hàng mới (7 ngày gần nhất) -->
        <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-yellow-500 hover:shadow-xl transition duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Khách Hàng Mới</p>
                    <p class="text-3xl font-extrabold text-gray-900 mt-1">
                        <?= (int)$new_customers_week ?>
                    </p>
                </div>
                <i class="fa-solid fa-user-plus text-3xl text-yellow-600 p-3 bg-yellow-100 rounded-full"></i>
            </div>
            <p class="text-xs text-green-600 mt-3"><i class="fa-solid fa-arrow-up mr-1"></i> +8% so với tuần trước</p>
        </div>

        <!-- Sản phẩm tồn kho (tổng) -->
        <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 brand-bg hover:shadow-xl transition duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-100 uppercase tracking-wider">Sản Phẩm Tồn Kho</p>
                    <p class="text-3xl font-extrabold text-white mt-1">
                        <?= number_format((int)$stock_total, 0, ',', '.') ?>
                    </p>
                </div>
                <i class="fa-solid fa-warehouse text-3xl text-white p-3 bg-gray-600 rounded-full"></i>
            </div>
            <p class="text-xs text-gray-200 mt-3">Đã kiểm tra 2 giờ trước</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Biểu đồ doanh thu 7 ngày -->
        <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-lg">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Biểu Đồ Doanh Thu 7 Ngày Gần Nhất</h2>
            <div class="h-64 flex items-end justify-around border-b border-l border-gray-300 p-2">
                <?php foreach ($bars as $b): ?>
                    <div class="flex flex-col items-center w-8">
                        <div class="w-full brand-bg chart-bar rounded-t-sm"
                             style="--final-height: <?= $b['pct'] ?>%;"
                             title="<?= htmlspecialchars($b['title']) ?>"></div>
                        <span class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($b['dow']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 5 Đơn hàng mới nhất -->
        <div class="bg-white p-6 rounded-xl shadow-lg">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">5 Đơn Hàng Mới Nhất</h2>
            <ul class="space-y-4">
                <?php if (!empty($recent_orders)): ?>
                    <?php foreach (array_slice($recent_orders, 0, 5) as $o): ?>
                        <li class="flex items-center justify-between border-b pb-2">
                            <div class="flex flex-col">
                            <span class="font-medium text-gray-900">
                                <?= htmlspecialchars(($o['MaDonHang'] ?? '').' - '.($o['KhachHang'] ?? 'Khách lẻ')) ?>
                            </span>
                                <span class="text-xs text-gray-500">
                                <?= date('d/m/Y H:i', strtotime($o['NgayDat'] ?? 'now')) ?> •
                                Trạng thái: <?= htmlspecialchars($o['TrangThai'] ?? '') ?>
                            </span>
                            </div>
                            <span class="font-bold brand-primary">
                            <?= number_format((int)($o['TongTien'] ?? 0), 0, ',', '.') ?>K
                        </span>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="flex items-center justify-between border-b pb-2">
                        <div class="flex flex-col">
                            <span class="font-medium text-gray-900">Chưa có dữ liệu</span>
                            <span class="text-xs text-gray-500">Khi có đơn mới, mục này sẽ hiển thị tại đây.</span>
                        </div>
                        <span class="font-bold brand-primary">0</span>
                    </li>
                <?php endif; ?>
            </ul>
            <button class="w-full mt-4 py-2 brand-bg text-white rounded-lg brand-bg-hover transition duration-200 text-sm font-medium">
                Xem Tất Cả Đơn Hàng
            </button>
        </div>
    </div>

<?php
$content = ob_get_clean();

// Include template (không đổi template; template sẽ dùng $content để render phần <main>)
include __DIR__ . '/template.php';
