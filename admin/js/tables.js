// admin/js/tables.js
document.addEventListener('DOMContentLoaded', () => {
    // ===== DOM refs (tùy trang bạn có hay không; code đều check null an toàn) =====
    const sidebar   = document.getElementById('sidebar');
    const menuToggle= document.getElementById('menu-toggle');
    const mainLayout= document.getElementById('main-layout');

    const notificationBell = document.getElementById('notification-bell');
    const notificationDropdown = document.getElementById('notification-dropdown');

    const searchInput = document.getElementById('search-input');
    const btnSearch   = document.getElementById('btn-search');
    const filterStatus= document.getElementById('filter-status');
    const filterSeats = document.getElementById('filter-seats');
    const btnToggleAdvanced = document.getElementById('btn-toggle-advanced-filter');
    const advancedFilters   = document.getElementById('advanced-filters');

    const tableEl    = document.getElementById('table-list-table');
    const btnAddTable= document.getElementById('btn-add-table');

    const tableModal = document.getElementById('table-modal');
    const modalTitle = document.getElementById('modal-title');
    const closeButtons = document.querySelectorAll('.close-button');
    const tableForm  = document.getElementById('table-form');
    const tableId    = document.getElementById('table-id');
    const seats      = document.getElementById('seats');
    const status     = document.getElementById('status');

    // ===== Helpers =====
    const API = {
        async call(url, opts) {
            const res = await fetch(url, opts);
            let json;
            try { json = await res.json(); } catch { json = { ok:false, message:'Lỗi JSON' }; }
            if (!json.ok) throw new Error(json.message || 'Thao tác thất bại');
            return json;
        },
        base: 'tables.php?ajax=1',
    };

    function reloadWith(qs) {
        const s = qs.toString();
        if (s) location.href = location.pathname + '?' + s;
        else   location.href = location.pathname; // đảm bảo reload khi xóa hết param
    }

    // Lấy dữ liệu 1 dòng từ DOM (không phụ thuộc dữ liệu giả lập)
    function readRow(tr) {
        // Ưu tiên data-* nếu bạn có đặt trong tables.php
        const data = {
            id: tr.dataset.id || '',
            seats: tr.dataset.seats || '',
            status: tr.dataset.status || '',
        };
        // Fallback: đọc từ ô theo thứ tự cột (Mã bàn | Số ghế | Số lần | Trạng thái | Hành động)
        if (!data.seats)  data.seats  = (tr.cells[1]?.textContent || '').trim();
        if (!data.status) data.status = (tr.cells[3]?.textContent || tr.cells[2]?.textContent || '').trim();
        return data;
    }

    function openModal(row) {
        tableForm?.reset();
        if (row) {
            modalTitle.textContent = 'CHỈNH SỬA BÀN #' + row.id;
            tableId.value = row.id || '';
            seats.value   = row.seats || '';
            status.value  = row.status || 'Trống';
        } else {
            modalTitle.textContent = 'THÊM BÀN MỚI';
            tableId.value = '';
            status.value  = 'Trống';
        }
        tableModal?.classList.remove('hidden');
    }
    function closeModal() { tableModal?.classList.add('hidden'); }

    // ===== UI chrome (optional) =====
    if (menuToggle && sidebar) {
        const toggleSidebar = () => sidebar.classList.toggle('-translate-x-full');
        menuToggle.addEventListener('click', toggleSidebar);
        mainLayout?.addEventListener('click', (e) => {
            if (innerWidth < 768 && !sidebar.contains(e.target) && !menuToggle.contains(e.target) && !sidebar.classList.contains('-translate-x-full')) {
                toggleSidebar();
            }
        });
        addEventListener('resize', () => { if (innerWidth >= 768) sidebar.classList.remove('-translate-x-full'); });
    }
    if (notificationBell && notificationDropdown) {
        notificationBell.addEventListener('click', (e) => { e.stopPropagation(); notificationDropdown.classList.toggle('active'); });
        document.addEventListener('click', (e) => { if (!notificationBell.contains(e.target) && !notificationDropdown.contains(e.target)) notificationDropdown.classList.remove('active'); });
    }
    btnToggleAdvanced?.addEventListener('click', () => advancedFilters?.classList.toggle('hidden'));

    // ===== Search/Filter: đẩy param lên URL để backend lọc =====
    function applyFilters(e) {
        if (e) e.preventDefault();
        const qs = new URLSearchParams(location.search);
        const q  = (searchInput?.value || '').trim();
        const st = filterStatus?.value || 'All';
        const se = (filterSeats?.value || '').trim();

        if (q) qs.set('q', q); else qs.delete('q');
        if (st && st !== 'All') qs.set('status', st); else qs.delete('status');
        if (se) qs.set('seats', se); else qs.delete('seats');

        reloadWith(qs);
    }
    btnSearch?.addEventListener('click', applyFilters);
    filterStatus?.addEventListener('change', applyFilters);
    filterSeats?.addEventListener('input', (e) => { /* enter mới lọc hoặc blur: tùy bạn */ });

    // ===== Modal open/close =====
    btnAddTable?.addEventListener('click', () => openModal(null));
    closeButtons.forEach(b => b.addEventListener('click', closeModal));
    window.addEventListener('click', (e) => { if (e.target === tableModal) closeModal(); });

    // ===== Delegation: các nút Hành động =====
    tableEl?.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-action');
        if (!btn) return;
        const tr  = btn.closest('tr'); if (!tr) return;
        const id  = tr.dataset.id;

        try {
            if (btn.classList.contains('view-detail') || btn.classList.contains('edit-table')) {
                const row = readRow(tr);
                openModal(row);
                return;
            }

            if (btn.classList.contains('book-table')) {
                if (!confirm('Bạn có muốn đặt bàn này?')) return;
                await API.call(`${API.base}&action=book&id=${encodeURIComponent(id)}`);
                location.reload();
                return;
            }

            if (btn.classList.contains('cancel-booking')) {
                if (!confirm('Bạn có muốn hủy đặt bàn này?')) return;
                await API.call(`${API.base}&action=cancel&id=${encodeURIComponent(id)}`);
                location.reload();
                return;
            }

            if (btn.classList.contains('checkout')) {
                if (!confirm('Thanh toán cho bàn này?')) return;
                await API.call(`${API.base}&action=checkout&id=${encodeURIComponent(id)}`);
                location.reload();
                return;
            }

            // Hỗ trợ cả .toggle-status lẫn .change-status
            if (btn.classList.contains('toggle-status') || btn.classList.contains('change-status')) {
                const current = readRow(tr).status;
                const next = prompt('Nhập trạng thái mới (Trống, Đang đặt, Đang sử dụng, Bảo trì):', current === 'Bảo trì' ? 'Trống' : current);
                if (!next) return;
                const fd = new FormData();
                fd.append('action', 'change_status');
                fd.append('status', next);
                await API.call(`${API.base}&id=${encodeURIComponent(id)}`, { method: 'POST', body: fd });
                location.reload();
                return;
            }

            if (btn.classList.contains('delete-table')) {
                if (!confirm('Xóa bàn này?')) return;
                await API.call(`${API.base}&action=delete&id=${encodeURIComponent(id)}`);
                // reload để đồng bộ cả thống kê “Số lần sử dụng”
                location.reload();
                return;
            }
        } catch (err) {
            alert(err.message || 'Có lỗi xảy ra');
        }
    });

    // ===== Submit form (Thêm/Sửa) =====
    tableForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(tableForm);
        fd.append('action', 'save');

        // kiểm tra nhanh
        const s = parseInt(fd.get('seats') || '0', 10);
        if (Number.isNaN(s) || s <= 0) { alert('Số ghế phải > 0'); return; }

        try {
            await API.call(API.base, { method: 'POST', body: fd });
            closeModal();
            location.reload();
        } catch (err) {
            alert(err.message || 'Lưu thất bại');
        }
    });

    // ===== Hydrate filter từ URL (nếu bạn cần show lại UI) =====
    (function hydrateFromUrl(){
        const qs = new URLSearchParams(location.search);
        if (qs.has('q') && searchInput)   searchInput.value = qs.get('q');
        if (qs.has('status') && filterStatus) filterStatus.value = qs.get('status');
        if (qs.has('seats') && filterSeats)   filterSeats.value = qs.get('seats');
    })();
});
