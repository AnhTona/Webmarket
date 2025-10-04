<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include __DIR__ . '/../../db.php'; // Đảm bảo đường dẫn đúng đến db.php

// Giả lập dữ liệu thông báo (thay bằng query thực tế)
$notifications = [
    ['id' => 1, 'type' => 'new_order', 'message' => 'Đơn hàng mới từ Nguyễn Văn A (HD00125)', 'time' => '12:30 AM', 'order_id' => 'HD00125'],
    ['id' => 2, 'type' => 'payment_received', 'message' => 'Khách hàng Chị Mai đã thanh toán (HD00124)', 'time' => '12:15 AM', 'order_id' => 'HD00124'],
    ['id' => 3, 'type' => 'pending_order', 'message' => 'Đơn hàng HD00123 chờ xác nhận', 'time' => '11:50 PM', 'order_id' => 'HD00123']
];
$notification_count = count($notifications);

// Hàm tìm kiếm (giả lập, thay bằng query thực tế)
function searchData($keyword) {
    $results = [];
    // Giả lập dữ liệu
    $mock_data = [
        ['type' => 'product', 'name' => 'Trà Sen Vàng', 'id' => 'SP001', 'price' => '50,000 VNĐ'],
        ['type' => 'customer', 'name' => 'Nguyễn Văn A', 'id' => 'KH001', 'order' => 'HD00125'],
        ['type' => 'order', 'name' => 'HD12345', 'customer' => 'Chị Mai', 'amount' => '2,800,000 VNĐ']
    ];
    foreach ($mock_data as $item) {
        if (stripos($item['name'], $keyword) !== false || stripos($item['id'], $keyword) !== false) {
            $results[] = $item;
        }
    }
    return $results;
}

$search_results = isset($_GET['search']) ? searchData($_GET['search']) : [];

// Định nghĩa tiêu đề trang
$page_title = "Dashboard Quản Lý";

// Nội dung chính
ob_start();
?>
<h1 class="text-3xl font-bold text-gray-800 mb-6">Trang Tổng Quan</h1>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-green-500 hover:shadow-xl transition duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Doanh Thu (Tháng)</p>
                <p class="text-3xl font-extrabold text-gray-900 mt-1">45.250.000 <span class="text-sm">VNĐ</span></p>
            </div>
            <i class="fa-solid fa-sack-dollar text-3xl text-green-500 p-3 bg-green-100 rounded-full"></i>
        </div>
        <p class="text-xs text-green-600 mt-3"><i class="fa-solid fa-arrow-up mr-1"></i> +12% so với tháng trước</p>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-blue-500 hover:shadow-xl transition duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Đơn Hàng (Tuần)</p>
                <p class="text-3xl font-extrabold text-gray-900 mt-1">1,250</p>
            </div>
            <i class="fa-solid fa-receipt text-3xl text-blue-500 p-3 bg-blue-100 rounded-full"></i>
        </div>
        <p class="text-xs text-red-600 mt-3"><i class="fa-solid fa-arrow-down mr-1"></i> -3% so với tuần trước</p>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-yellow-500 hover:shadow-xl transition duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Khách Hàng Mới</p>
                <p class="text-3xl font-extrabold text-gray-900 mt-1">180</p>
            </div>
            <i class="fa-solid fa-user-plus text-3xl text-yellow-600 p-3 bg-yellow-100 rounded-full"></i>
        </div>
        <p class="text-xs text-green-600 mt-3"><i class="fa-solid fa-arrow-up mr-1"></i> +8% so với tuần trước</p>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 brand-bg hover:shadow-xl transition duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-100 uppercase tracking-wider">Sản Phẩm Tồn Kho</p>
                <p class="text-3xl font-extrabold text-white mt-1">3,400</p>
            </div>
            <i class="fa-solid fa-warehouse text-3xl text-white p-3 bg-gray-600 rounded-full"></i>
        </div>
        <p class="text-xs text-gray-200 mt-3">Đã kiểm tra 2 giờ trước</p>
    </div>
</div>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-lg">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Biểu Đồ Doanh Thu 7 Ngày Gần Nhất</h2>
        <div class="h-64 flex items-end justify-around border-b border-l border-gray-300 p-2">
            <div class="flex flex-col items-center w-8">
                <div class="w-full brand-bg chart-bar rounded-t-sm" style="--final-height: 40%;" title="T2: 12M"></div>
                <span class="text-xs text-gray-500 mt-1">T2</span>
            </div>
            <div class="flex flex-col items-center w-8">
                <div class="w-full brand-bg chart-bar rounded-t-sm" style="--final-height: 75%;" title="T3: 20M"></div>
                <span class="text-xs text-gray-500 mt-1">T3</span>
            </div>
            <div class="flex flex-col items-center w-8">
                <div class="w-full brand-bg chart-bar rounded-t-sm" style="--final-height: 55%;" title="T4: 15M"></div>
                <span class="text-xs text-gray-500 mt-1">T4</span>
            </div>
            <div class="flex flex-col items-center w-8">
                <div class="w-full brand-bg chart-bar rounded-t-sm" style="--final-height: 90%;" title="T5: 25M"></div>
                <span class="text-xs text-gray-500 mt-1">T5</span>
            </div>
            <div class="flex flex-col items-center w-8">
                <div class="w-full brand-bg chart-bar rounded-t-sm" style="--final-height: 60%;" title="T6: 18M"></div>
                <span class="text-xs text-gray-500 mt-1">T6</span>
            </div>
            <div class="flex flex-col items-center w-8">
                <div class="w-full brand-bg chart-bar rounded-t-sm" style="--final-height: 85%;" title="T7: 23M"></div>
                <span class="text-xs text-gray-500 mt-1">T7</span>
            </div>
            <div class="flex flex-col items-center w-8">
                <div class="w-full brand-bg chart-bar rounded-t-sm" style="--final-height: 70%;" title="CN: 19M"></div>
                <span class="text-xs text-gray-500 mt-1">CN</span>
            </div>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-lg">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">5 Đơn Hàng Mới Nhất</h2>
        <ul class="space-y-4">
            <?php if (!empty($search_results)): ?>
                <?php foreach ($search_results as $result): ?>
                    <li class="flex items-center justify-between border-b pb-2">
                        <div class="flex flex-col">
                            <span class="font-medium text-gray-900"><?php echo htmlspecialchars($result['id'] . ' - ' . $result['name']); ?></span>
                            <span class="text-xs text-gray-500"><?php echo $result['type'] === 'product' ? 'Giá: ' . $result['price'] : ($result['type'] === 'customer' ? 'Đơn: ' . $result['order'] : 'Khách: ' . $result['customer']); ?></span>
                        </div>
                        <span class="font-bold brand-primary"><?php echo $result['type'] === 'order' ? $result['amount'] : ''; ?></span>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="flex items-center justify-between border-b pb-2">
                    <div class="flex flex-col">
                        <span class="font-medium text-gray-900">HD00125 - Anh Tuấn</span>
                        <span class="text-xs text-gray-500">2 Bánh, 1 Trà. Chiều nay</span>
                    </div>
                    <span class="font-bold brand-primary">1.250K</span>
                </li>
                <li class="flex items-center justify-between border-b pb-2">
                    <div class="flex flex-col">
                        <span class="font-medium text-gray-900">HD00124 - Chị Mai</span>
                        <span class="text-xs text-gray-500">Đã thanh toán. Giao HCM</span>
                    </div>
                    <span class="font-bold text-green-500">2.800K</span>
                </li>
                <li class="flex items-center justify-between border-b pb-2">
                    <div class="flex flex-col">
                        <span class="font-medium text-gray-900">HD00123 - V.I.P</span>
                        <span class="text-xs text-gray-500">Đã đặt trước bàn 5</span>
                    </div>
                    <span class="font-bold brand-primary">5.000K</span>
                </li>
                <li class="flex items-center justify-between border-b pb-2">
                    <div class="flex flex-col">
                        <span class="font-medium text-gray-900">HD00122 - Anh Khoa</span>
                        <span class="text-xs text-gray-500">2 Trà. Giao nhanh</span>
                    </div>
                    <span class="font-bold text-green-500">950K</span>
                </li>
                <li class="flex items-center justify-between">
                    <div class="flex flex-col">
                        <span class="font-medium text-graya-900">HD00121 - C.ty A</span>
                        <span class="text-xs text-gray-500">Đơn hàng lớn. HĐ đỏ</span>
                    </div>a
                    <span class="font-bold brand-primary">15.000K</span>
                </li>
            <?php endif; ?>
        </ul>
        <button class="w-full mt-4 py-2 brand-bg text-white rounded-lg brand-bg-hover transition duration-200 text-sm font-medium">Xem Tất Cả Đơn Hàng</button>
    </div>
</div>
<?php
$content = ob_get_clean();

// Include template
include __DIR__ . '/template.php';
?>