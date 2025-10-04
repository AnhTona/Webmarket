document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menu-toggle');
    const contentArea = document.getElementById('content-area');
    const mainLayout = document.getElementById('main-layout');
    const notificationBell = document.getElementById('notification-bell');
    const notificationDropdown = document.getElementById('notification-dropdown');
    const searchInput = document.getElementById('search-input');
    const btnSearch = document.getElementById('btn-search');
    const filterStatus = document.getElementById('filter-status');
    const btnToggleAdvanced = document.getElementById('btn-toggle-advanced-filter');
    const advancedFilters = document.getElementById('advanced-filters');
    const filterSeats = document.getElementById('filter-seats');
    const btnAddTable = document.getElementById('btn-add-table');
    const tableModal = document.getElementById('table-modal');
    const closeButton = document.querySelector('.close-button');
    const tableForm = document.getElementById('table-form');
    const tableId = document.getElementById('table-id');
    const seats = document.getElementById('seats');
    const status = document.getElementById('status');
    const btnSaveTable = document.getElementById('btn-save-table');
    const tableListTable = document.getElementById('table-list-table');
    const noResultsMessage = document.getElementById('no-results-message');

    // Kiểm tra sự tồn tại của các phần tử
    if (!sidebar || !menuToggle || !contentArea || !mainLayout || !notificationBell || !notificationDropdown ||
        !searchInput || !btnSearch || !filterStatus || !btnToggleAdvanced || !advancedFilters ||
        !filterSeats || !btnAddTable || !tableModal || !closeButton || !tableForm || !tableId ||
        !seats || !status || !btnSaveTable || !tableListTable || !noResultsMessage) {
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
    function filterTables() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusFilter = filterStatus.value;
        const seatsFilter = filterSeats.value;

        const rows = tableListTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        let hasResults = false;

        for (let row of rows) {
            const id = row.getAttribute('data-id');
            const table = window.tablesData.find(t => t.id == id);
            if (!table) continue;

            const matchesSearch = id.toString().includes(searchTerm);
            const matchesStatus = statusFilter === 'All' || table.status === statusFilter;
            const matchesSeats = !seatsFilter || table.seats == seatsFilter;

            if (matchesSearch && matchesStatus && matchesSeats) {
                row.style.display = '';
                hasResults = true;
            } else {
                row.style.display = 'none';
            }
        }

        noResultsMessage.style.display = hasResults ? 'none' : 'block';
    }

    searchInput.addEventListener('input', filterTables);
    btnSearch.addEventListener('click', filterTables);
    filterStatus.addEventListener('change', filterTables);
    filterSeats.addEventListener('input', filterTables);

    // Add Table
    btnAddTable.addEventListener('click', () => {
        tableId.value = '';
        seats.value = '';
        status.value = 'Trống';
        tableModal.classList.remove('hidden');
        document.getElementById('modal-title').textContent = 'THÊM BÀN MỚI';
    });

    // View/Edit Table
    tableListTable.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-action');
        if (!btn) return;

        const id = btn.closest('tr').getAttribute('data-id');
        const table = window.tablesData.find(t => t.id == id);

        if (btn.classList.contains('edit-table')) {
            tableId.value = table.id;
            seats.value = table.seats;
            status.value = table.status;
            tableModal.classList.remove('hidden');
            document.getElementById('modal-title').textContent = 'CHỈNH SỬA BÀN';
        } else if (btn.classList.contains('book-table')) {
            if (confirm('Bạn có muốn đặt bàn này?')) {
                const index = window.tablesData.findIndex(t => t.id == id);
                window.tablesData[index].status = 'Đang đặt';
                filterTables();
                alert('Bàn đã được đặt (giả lập)');
            }
        } else if (btn.classList.contains('cancel-booking')) {
            if (confirm('Bạn có muốn hủy đặt bàn này?')) {
                const index = window.tablesData.findIndex(t => t.id == id);
                window.tablesData[index].status = 'Trống';
                filterTables();
                alert('Đặt bàn đã bị hủy (giả lập)');
            }
        } else if (btn.classList.contains('checkout')) {
            if (confirm('Thanh toán cho bàn này?')) {
                const index = window.tablesData.findIndex(t => t.id == id);
                window.tablesData[index].status = 'Trống';
                window.tablesData[index].usage_count += 1;
                filterTables();
                alert('Thanh toán thành công (giả lập)');
            }
        } else if (btn.classList.contains('change-status')) {
            const newStatus = prompt('Nhập trạng thái mới (Trống, Đang đặt, Đang sử dụng, Bảo trì):');
            if (newStatus && ['Trống', 'Đang đặt', 'Đang sử dụng', 'Bảo trì'].includes(newStatus)) {
                const index = window.tablesData.findIndex(t => t.id == id);
                window.tablesData[index].status = newStatus;
                filterTables();
                alert('Trạng thái đã được cập nhật (giả lập)');
            }
        } else if (btn.classList.contains('delete-table')) {
            if (confirm('Bạn có chắc muốn xóa bàn này?')) {
                const index = window.tablesData.findIndex(t => t.id == id);
                window.tablesData.splice(index, 1);
                btn.closest('tr').remove();
                filterTables();
            }
        }
    });

    // Close Modal
    closeButton.addEventListener('click', () => tableModal.classList.add('hidden'));
    window.addEventListener('click', (e) => {
        if (e.target === tableModal) tableModal.classList.add('hidden');
    });

    // Save Table
    tableForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const id = tableId.value || Math.max(...window.tablesData.map(t => t.id), 0) + 1;
        const data = {
            id: id,
            seats: parseInt(seats.value),
            status: status.value,
            usage_count: tableId.value ? window.tablesData.find(t => t.id == id).usage_count : 0
        };
        if (!tableId.value) {
            window.tablesData.push(data);
            const newRow = tableListTable.tBodies[0].insertRow();
            newRow.setAttribute('data-id', id);
            newRow.innerHTML = `
                <td>${id}</td>
                <td>${data.seats}</td>
                <td><span class="status-badge status-${data.status.toLowerCase().replace(' ', '-')}">${data.status}</span></td>
                <td>
                    <button class="btn-action book-table" title="Đặt bàn"><i class="fas fa-calendar-check"></i></button>
                    <button class="btn-action edit-table" title="Chỉnh sửa"><i class="fas fa-edit"></i></button>
                    <button class="btn-action delete-table" title="Xóa"><i class="fas fa-trash"></i></button>
                </td>
            `;
        } else {
            const index = window.tablesData.findIndex(t => t.id == id);
            window.tablesData[index] = data;
            const row = tableListTable.querySelector(`tr[data-id="${id}"]`);
            row.cells[1].textContent = data.seats;
            row.cells[2].querySelector('.status-badge').className = `status-badge status-${data.status.toLowerCase().replace(' ', '-')}`;
            row.cells[2].querySelector('.status-badge').textContent = data.status;
            row.cells[3].innerHTML = `
                <button class="btn-action ${data.status === 'Trống' ? 'book-table' : ''}" title="${data.status === 'Trống' ? 'Đặt bàn' : ''}"><i class="fas fa-calendar-check"></i></button>
                <button class="btn-action edit-table" title="Chỉnh sửa"><i class="fas fa-edit"></i></button>
                <button class="btn-action ${data.status !== 'Bảo trì' ? 'delete-table' : ''}" title="${data.status !== 'Bảo trì' ? 'Xóa' : ''}"><i class="fas fa-trash"></i></button>
            `;
        }
        tableModal.classList.add('hidden');
        filterTables();
        alert('Dữ liệu đã được lưu (giả lập)');
    });
});