<?php
require_once __DIR__ . '/../controller/Admin_Staff_Controller.php';
$ctx = AdminStaffController::handle();
extract($ctx, EXTR_OVERWRITE);

// Thiết lập tiêu đề trang
$page_title = 'Quản Lý Admin & Staff';

// Bắt đầu output buffering
ob_start();
?>

    <!-- CSS cho trang Admin Staff -->
    <link rel="stylesheet" href="../css/customers.css">

    <h1 class="text-3xl font-bold text-gray-800 mb-6">Quản Lý Admin & Staff</h1>

    <!-- Stats Summary -->
    <div class="mb-6 stats-card bg-white p-4 rounded-lg shadow-sm border-l-4 border-purple-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-100 font-medium">Tổng số tài khoản</p>
                <p class="text-3xl font-bold text-white"><?= number_format($total_admins, 0, ',', '.') ?></p>
            </div>
            <i class="fas fa-user-shield text-5xl text-white opacity-20"></i>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar mb-6">
        <div class="search-group">
            <input type="text" id="search-input" placeholder="Tìm theo: Username, Họ tên, Email, SĐT...">
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
            <label for="filter-role">Vai trò:</label>
            <select id="filter-role">
                <option value="All">Tất cả</option>
                <option value="ADMIN">Admin</option>
                <option value="STAFF">Staff</option>
            </select>
        </div>
    </div>

    <!-- Admin/Staff Table -->
    <div class="customer-table-container bg-white rounded-lg shadow-sm overflow-hidden">
        <table id="admin-list-table" class="customer-table w-full">
            <thead>
            <tr>
                <th>Mã</th>
                <th>Username</th>
                <th>Họ tên</th>
                <th>Email</th>
                <th>SĐT</th>
                <th>Vai trò</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!empty($admin_list)): ?>
                <?php foreach ($admin_list as $admin): ?>
                    <tr data-id="<?= $admin['id'] ?>">
                        <td class="font-semibold text-gray-700">#<?= $admin['id'] ?></td>
                        <td class="font-medium text-indigo-600"><?= htmlspecialchars($admin['username']) ?></td>
                        <td><?= htmlspecialchars($admin['name']) ?></td>
                        <td><?= htmlspecialchars($admin['email']) ?></td>
                        <td><?= htmlspecialchars($admin['phone'] ?? '-') ?></td>
                        <td>
                            <?php if ($admin['role'] === 'ADMIN'): ?>
                                <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-bold">
                                    <i class="fas fa-crown"></i> ADMIN
                                </span>
                            <?php else: ?>
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-bold">
                                    <i class="fas fa-user-tie"></i> STAFF
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?= ($admin['status'] == 'Hoạt động' ? 'active' : 'inactive') ?>">
                                <i class="fas fa-circle text-xs mr-1"></i>
                                <?= htmlspecialchars($admin['status']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($admin['created_at']) ?></td>
                        <td class="text-center whitespace-nowrap space-x-2">
                            <button
                                    type="button"
                                    class="edit-admin px-3 py-2 rounded-lg bg-yellow-500 text-white hover:bg-yellow-600 transition"
                                    data-id="<?= $admin['id'] ?>"
                                    data-username="<?= htmlspecialchars($admin['username']) ?>"
                                    data-name="<?= htmlspecialchars($admin['name']) ?>"
                                    data-email="<?= htmlspecialchars($admin['email']) ?>"
                                    data-phone="<?= htmlspecialchars($admin['phone'] ?? '') ?>"
                                    data-role="<?= htmlspecialchars($admin['role']) ?>"
                                    data-status="<?= htmlspecialchars($admin['status']) ?>"
                            >
                                <i class="fa-solid fa-pen"></i> Sửa
                            </button>
                            <?php
                            // ✅ FIX: Kiểm tra session tồn tại trước khi dùng
                            $currentUserId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
                            if ($currentUserId != $admin['id']):
                                ?>
                                <button
                                        type="button"
                                        class="toggle-status px-3 py-2 rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition"
                                        data-id="<?= $admin['id'] ?>"
                                >
                                    <i class="fa-solid fa-toggle-on"></i> Bật/Tắt
                                </button>
                                <button
                                        type="button"
                                        class="delete-admin px-3 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 transition"
                                        data-id="<?= $admin['id'] ?>"
                                        data-name="<?= htmlspecialchars($admin['name']) ?>"
                                >
                                    <i class="fa-solid fa-trash"></i> Xóa
                                </button>
                            <?php else: ?>
                                <span class="px-3 py-2 text-gray-400 text-xs">(Tài khoản hiện tại)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center py-12">
                        <div class="text-gray-400">
                            <i class="fa-solid fa-user-slash text-5xl mb-3 opacity-50"></i>
                            <p class="text-lg font-medium">Không có tài khoản nào</p>
                            <p class="text-sm mt-1">Thử điều chỉnh bộ lọc hoặc thêm tài khoản mới</p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <div class="mt-6">
        <div class="pagination-info text-center mb-3">
            Hiển thị <?= min(($page - 1) * $per_page + 1, $total_admins) ?> - <?= min($page * $per_page, $total_admins) ?>
            trong tổng số <strong><?= number_format($total_admins, 0, ',', '.') ?></strong> tài khoản
        </div>

        <div class="pagination-buttons flex justify-center">
            <?php
            $current_query = $_GET;

            if ($page > 1):
                $current_query['page'] = $page - 1;
                $prev_url = '?' . http_build_query($current_query);
                ?>
                <a href="<?= htmlspecialchars($prev_url) ?>">
                    <i class="fas fa-chevron-left"></i> Trước
                </a>
            <?php endif; ?>

            <?php
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

    <!-- Button Add Admin/Staff -->
    <div class="mt-6 text-center">
        <button id="btn-add-admin"
                class="py-3 px-6 brand-bg text-white rounded-lg brand-bg-hover transition duration-200 font-medium shadow-md hover:shadow-lg">
            <i class="fas fa-user-plus"></i> Thêm Tài Khoản Mới
        </button>
    </div>

    <!-- Modal Add/Edit Admin/Staff -->
    <div id="admin-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
        <div class="modal-card" style="width:min(600px,95vw); background:white; border-radius:16px; overflow:hidden;">
            <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; padding:16px 20px; background:linear-gradient(90deg, var(--brand-primary), #6d1810); color:white;">
                <h3 id="modal-title" style="font-size:1.2rem; font-weight:700;">THÊM TÀI KHOẢN MỚI</h3>
                <button class="close-modal" style="background:transparent; border:0; color:white; font-size:24px; cursor:pointer;">×</button>
            </div>

            <form id="admin-form" style="padding:20px;">
                <input type="hidden" id="admin-id" name="id">

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <!-- Username & Họ tên -->
                    <div style="display:flex; flex-direction:column; gap:6px;">
                        <label style="font-weight:600;">Username</label>
                        <input id="admin-username" name="username" type="text" required
                               style="padding:10px 12px; border:1px solid #dee2e6; border-radius:8px;">
                    </div>
                    <div style="display:flex; flex-direction:column; gap:6px;">
                        <label style="font-weight:600;">Họ tên</label>
                        <input id="admin-name" name="name" type="text" required
                               style="padding:10px 12px; border:1px solid #dee2e6; border-radius:8px;">
                    </div>

                    <!-- Email & SĐT -->
                    <div style="display:flex; flex-direction:column; gap:6px;">
                        <label style="font-weight:600;">Email</label>
                        <input id="admin-email" name="email" type="email" required
                               style="padding:10px 12px; border:1px solid #dee2e6; border-radius:8px;">
                    </div>
                    <div style="display:flex; flex-direction:column; gap:6px;">
                        <label style="font-weight:600;">Số điện thoại</label>
                        <input id="admin-phone" name="phone" type="tel"
                               style="padding:10px 12px; border:1px solid #dee2e6; border-radius:8px;">
                    </div>

                    <!-- Mật khẩu & Vai trò -->
                    <div style="display:flex; flex-direction:column; gap:6px;">
                        <label style="font-weight:600;">Mật khẩu <span class="text-xs text-gray-500" id="password-hint">(Bắt buộc khi thêm mới)</span></label>
                        <input id="admin-password" name="password" type="password"
                               style="padding:10px 12px; border:1px solid #dee2e6; border-radius:8px;">
                    </div>
                    <div style="display:flex; flex-direction:column; gap:6px;">
                        <label style="font-weight:600;">Vai trò</label>
                        <select id="admin-role" name="role" required
                                style="padding:10px 12px; border:1px solid #dee2e6; border-radius:8px;">
                            <option value="STAFF">Staff</option>
                            <option value="ADMIN">Admin</option>
                        </select>
                    </div>

                    <!-- Trạng thái -->
                    <div style="display:flex; flex-direction:column; gap:6px;">
                        <label style="font-weight:600;">Trạng thái</label>
                        <select id="admin-status" name="status"
                                style="padding:10px 12px; border:1px solid #dee2e6; border-radius:8px;">
                            <option value="Hoạt động">Hoạt động</option>
                            <option value="Ngừng">Ngừng</option>
                        </select>
                    </div>
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:20px;">
                    <button type="submit" style="padding:10px 20px; background:var(--brand-primary); color:white; border:none; border-radius:8px; cursor:pointer; font-weight:500;">
                        <i class="fas fa-save"></i> Lưu
                    </button>
                    <button type="button" class="close-modal" style="padding:10px 20px; background:#e9ecef; color:#495057; border:none; border-radius:8px; cursor:pointer;">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        window.adminData = <?= json_encode($admin_list ?? [], JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="../js/admin_staff.js"></script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/template.php';
?>