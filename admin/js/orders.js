document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menu-toggle');
    const contentArea = document.getElementById('content-area');
    const mainLayout = document.getElementById('main-layout');
    const notificationBell = document.getElementById('notification-bell');
    const notificationDropdown = document.getElementById('notification-dropdown');
    const searchInput = document.getElementById('search-input');
    const btnSearch = document.getElementById('btn-search');
    const filterDate = document.getElementById('filter-date');
    const filterStatus = document.getElementById('filter-status');
    const btnToggleAdvanced = document.getElementById('btn-toggle-advanced-filter');
    const advancedFilters = document.getElementById('advanced-filters');
    const filterDateFrom = document.getElementById('filter-date-from');
    const filterDateTo = document.getElementById('filter-date-to');
    const orderListTable = document.getElementById('order-list-table');
    const noResultsMessage = document.getElementById('no-results-message');
    const orderDetailModal = document.getElementById('order-detail-modal');
    const closeButton = document.querySelector('.close-button');
    const orderDetails = document.getElementById('order-details');
    const btnExportCSV = document.getElementById('btn-export-csv');
    const btnExportExcel = document.getElementById('btn-export-excel');
    const btnExportPDF = document.getElementById('btn-export-pdf');

    // Kiểm tra sự tồn tại của các phần tử
    if (!sidebar || !menuToggle || !contentArea || !mainLayout || !notificationBell || !notificationDropdown ||
        !searchInput || !btnSearch || !filterDate || !filterStatus || !btnToggleAdvanced || !advancedFilters ||
        !filterDateFrom || !filterDateTo || !orderListTable || !noResultsMessage || !orderDetailModal ||
        !closeButton || !orderDetails || !btnExportCSV || !btnExportExcel || !btnExportPDF) {
        console.error('Một hoặc nhiều phần tử DOM không tồn tại!');
        return;
    }

    // Toggle Sidebar
    function toggleSidebar() {
        sidebar.classList.toggle('-translate-x-full');
    }

    menuToggle.addEventListener('click', toggleSidebar);
    mainLayout.addEventListener('click', function(event) {
        if (window.innerWidth < 768 && !sidebar.contains(event.target) && !menuToggle.contains(event.target) && !sidebar.classList.contains('-translate-x-full')) {
            toggleSidebar();
        }
    });
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            sidebar.classList.remove('-translate-x-full');
        }
    });
    if (window.innerWidth >= 768) {
        sidebar.classList.remove('fixed', 'shadow-xl');
        sidebar.classList.add('relative');
    }

    // Xử lý thông báo
    notificationBell.addEventListener('click', function(e) {
        e.stopPropagation();
        notificationDropdown.classList.toggle('active');
    });
    document.addEventListener('click', function(e) {
        if (!notificationBell.contains(e.target) && !notificationDropdown.contains(e.target)) {
            notificationDropdown.classList.remove('active');
        }
    });

    // Toggle Advanced Filters
    btnToggleAdvanced.addEventListener('click', () => {
        advancedFilters.classList.toggle('hidden');
    });

    // Filter and Search
    function filterOrders() {
        const searchTerm = searchInput.value.toLowerCase();
        const dateFilter = filterDate.value;
        const statusFilter = filterStatus.value;
        const dateFrom = filterDateFrom.value;
        const dateTo = filterDateTo.value;

        const rows = orderListTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        let hasResults = false;

        for (let row of rows) {
            const id = row.getAttribute('data-id');
            const order = window.ordersData.find(o => o.MaDon == id);
            if (!order) continue;

            const matchesSearch = order.KhachHang.toLowerCase().includes(searchTerm);
            const matchesDate = !dateFilter || order.NgayDat.startsWith(dateFilter);
            const matchesStatus = statusFilter === 'All' || order.TrangThai === statusFilter;
            const matchesDateRange = (!dateFrom || new Date(order.NgayDat) >= new Date(dateFrom)) &&
                                    (!dateTo || new Date(order.NgayDat) <= new Date(dateTo));

            if (matchesSearch && matchesDate && matchesStatus && matchesDateRange) {
                row.style.display = '';
                hasResults = true;
            } else {
                row.style.display = 'none';
            }
        }

        noResultsMessage.style.display = hasResults ? 'none' : 'block';
    }

    searchInput.addEventListener('input', filterOrders);
    btnSearch.addEventListener('click', filterOrders);
    filterDate.addEventListener('change', filterOrders);
    filterStatus.addEventListener('change', filterOrders);
    filterDateFrom.addEventListener('change', filterOrders);
    filterDateTo.addEventListener('change', filterOrders);

    // Handle Order Actions
    orderListTable.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-action');
        if (!btn) return;

        const id = btn.closest('tr').getAttribute('data-id');
        const order = window.ordersData.find(o => o.MaDon == id);

        if (btn.classList.contains('confirm-order')) {
            if (confirm('Xác nhận đơn hàng này?')) {
                order.TrangThai = 'Đang chuẩn bị';
                filterOrders();
                alert('Đơn hàng đã được xác nhận (giả lập)');
            }
        } else if (btn.classList.contains('cancel-order')) {
            if (confirm('Hủy đơn hàng này?')) {
                order.TrangThai = 'Đã hủy';
                filterOrders();
                alert('Đơn hàng đã bị hủy (giả lập)');
            }
        } else if (btn.classList.contains('complete-order')) {
            if (confirm('Đánh dấu hoàn thành đơn hàng này?')) {
                order.TrangThai = 'Hoàn thành';
                filterOrders();
                alert('Đơn hàng đã hoàn thành (giả lập)');
            }
        } else if (btn.classList.contains('view-order')) {
            const details = getOrderDetails(id);
            orderDetails.innerHTML = `
                <table>
                    <thead><tr><th>Sản phẩm</th><th>Số lượng</th><th>Giá</th><th>Tổng</th></tr></thead>
                    <tbody>
                        ${details.map(d => `
                            <tr>
                                <td>${d.TenSanPham}</td>
                                <td>${d.SoLuong}</td>
                                <td>${numberFormat(d.Gia)}đ</td>
                                <td>${numberFormat(d.Tong)}đ</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                <p class="mt-2 font-bold">Tổng cộng: ${numberFormat(order.TongTien)}đ</p>
            `;
            orderDetailModal.classList.remove('hidden');
        }
    });

    // Close Modal
    closeButton.addEventListener('click', () => orderDetailModal.classList.add('hidden'));
    window.addEventListener('click', (e) => {
        if (e.target === orderDetailModal) orderDetailModal.classList.add('hidden');
    });

    // Export Reports (Giả lập)
    function exportReport(format) {
        const data = window.ordersData.map(o => ({
            'Mã đơn': o.MaDon,
            'Khách hàng': o.KhachHang,
            'Bàn': o.Ban,
            'Ngày đặt': o.NgayDat,
            'Tổng tiền': numberFormat(o.TongTien) + 'đ',
            'Trạng thái': o.TrangThai
        }));
        const csv = [
            Object.keys(data[0]).join(','),
            ...data.map(row => Object.values(row).join(','))
        ].join('\n');
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `orders_report_${format}_${new Date().toISOString().split('T')[0]}.${format}`;
        a.click();
        alert(`Đã xuất báo cáo dạng ${format} (giả lập)`);
    }

    btnExportCSV.addEventListener('click', () => exportReport('csv'));
    btnExportExcel.addEventListener('click', () => exportReport('xls'));
    btnExportPDF.addEventListener('click', () => exportReport('pdf'));

    // Helper function to format number
    function numberFormat(number) {
        return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    // Helper function to get order details (giả lập)
    function getOrderDetails(maDon) {
        return {
            'DH001': [
                { TenSanPham: 'Trà Đào', SoLuong: 2, Gia: 35000, Tong: 70000 },
                { TenSanPham: 'Bánh Mì', SoLuong: 1, Gia: 20000, Tong: 20000 },
            ],
            'DH002': [
                { TenSanPham: 'Trà Sữa Đặc Biệt', SoLuong: 1, Gia: 45000, Tong: 45000 },
                { TenSanPham: 'Bánh Mì', SoLuong: 2, Gia: 20000, Tong: 40000 },
            ],
            'DH003': [
                { TenSanPham: 'Trà Đào', SoLuong: 1, Gia: 35000, Tong: 35000 },
            ],
        }[maDon] || [];
    }
});