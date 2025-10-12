// admin/js/customers.js
document.addEventListener('DOMContentLoaded', () => {
    // ====== DOM refs ======
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menu-toggle');
    const notificationBell = document.getElementById('notification-bell');
    const notificationDropdown = document.getElementById('notification-dropdown');

    const searchInput = document.getElementById('search-input');
    const btnSearch   = document.getElementById('btn-search');
    const filterStatus= document.getElementById('filter-status');
    const filterRank  = document.getElementById('filter-rank');
    const btnToggleAdvanced = document.getElementById('btn-toggle-advanced-filter');
    const advancedFilters   = document.getElementById('advanced-filters');
    const filterCity   = document.getElementById('filter-city');
    const filterDateFrom = document.getElementById('filter-date-from');
    const filterDateTo   = document.getElementById('filter-date-to');

    const tableBody = document.querySelector('#customer-list-table tbody');
    const btnAddCustomer = document.getElementById('btn-add-customer');

    const customerModal = document.getElementById('customer-modal');
    const modalTitle    = document.getElementById('modal-title');
    const closeButtons  = document.querySelectorAll('.close-button');
    const customerForm  = document.getElementById('customer-form');
    const customerId    = document.getElementById('customer-id');
    const displayRank   = document.getElementById('display-rank');
    const historyNotesSection = document.getElementById('history-notes-section');

    // ====== Sidebar & Notifications ======
    menuToggle?.addEventListener('click', () => {
        sidebar?.classList.toggle('-translate-x-full');
    });
    notificationBell?.addEventListener('click', (e) => {
        notificationDropdown?.classList.toggle('active');
        e.stopPropagation();
    });
    document.body.addEventListener('click', (e) => {
        if (notificationDropdown?.classList.contains('active') &&
            !notificationDropdown.contains(e.target) &&
            !notificationBell.contains(e.target)) {
            notificationDropdown.classList.remove('active');
        }
    });

    // ====== Advanced filter toggle ======
    btnToggleAdvanced?.addEventListener('click', () => {
        advancedFilters?.classList.toggle('hidden');
        const icon = btnToggleAdvanced.querySelector('i');
        if (!icon) return;
        if (advancedFilters?.classList.contains('hidden')) {
            icon.className = 'fas fa-sliders-h';
            btnToggleAdvanced.innerHTML = '<i class="fas fa-sliders-h"></i> Bộ lọc nâng cao';
        } else {
            icon.className = 'fas fa-times';
            btnToggleAdvanced.innerHTML = '<i class="fas fa-times"></i> Đóng bộ lọc';
        }
    });

    // ====== Filter/Search: đẩy query lên URL để server lọc ======
    function applyFiltersToUrl(e) {
        if (e) e.preventDefault();
        const qs = new URLSearchParams(location.search);

        const s = (searchInput?.value || '').trim();
        if (s) qs.set('q', s); else qs.delete('q');

        const st = filterStatus?.value || 'All';
        const rk = filterRank?.value   || 'All';
        const city = (filterCity?.value || '').trim();
        const df   = filterDateFrom?.value || '';
        const dt   = filterDateTo?.value   || '';

        qs.set('status', st);
        qs.set('rank', rk);
        if (city) qs.set('city', city); else qs.delete('city');
        if (df)   qs.set('date_from', df); else qs.delete('date_from');
        if (dt)   qs.set('date_to', dt);   else qs.delete('date_to');

        qs.set('page', '1'); // reset trang khi lọc/tìm kiếm
        location.search = qs.toString();
    }

    btnSearch?.addEventListener('click', applyFiltersToUrl);
    searchInput?.addEventListener('keypress', (e) => { if (e.key === 'Enter') applyFiltersToUrl(e); });
    filterStatus?.addEventListener('change', applyFiltersToUrl);
    filterRank?.addEventListener('change', applyFiltersToUrl);
    filterCity?.addEventListener('input', () => { /* gõ xong hãy bấm tìm */ });
    filterDateFrom?.addEventListener('change', applyFiltersToUrl);
    filterDateTo?.addEventListener('change', applyFiltersToUrl);

    // Hydrate giá trị filter từ URL (nếu có)
    (function hydrateFromUrl(){
        const qs = new URLSearchParams(location.search);
        if (qs.has('q') && searchInput) searchInput.value = qs.get('q');
        if (qs.has('status') && filterStatus) filterStatus.value = qs.get('status');
        if (qs.has('rank') && filterRank) filterRank.value = qs.get('rank');
        if (qs.has('city') && filterCity) filterCity.value = qs.get('city');
        if (qs.has('date_from') && filterDateFrom) filterDateFrom.value = qs.get('date_from');
        if (qs.has('date_to') && filterDateTo) filterDateTo.value = qs.get('date_to');
    })();

    // ====== Modal helpers ======
    function openModal(customer) {
        customerForm?.reset();
        if (customer) {
            modalTitle.textContent = 'CHỈNH SỬA KHÁCH HÀNG: ' + customer.name;
            customerId.value = customer.id;
            document.getElementById('full-name').value = customer.name || '';
            document.getElementById('email').value     = customer.email || '';
            document.getElementById('phone').value     = customer.phone || '';
            document.getElementById('address').value   = customer.address || '';
            document.getElementById('status').value    = customer.status || 'Hoạt động';
            displayRank.textContent = customer.rank || 'Mới';
            displayRank.className = `rank-badge rank-${(customer.rank || 'Mới').toLowerCase()}`;
            historyNotesSection.style.display = 'grid';
        } else {
            modalTitle.textContent = 'THÊM KHÁCH HÀNG MỚI';
            customerId.value = '';
            displayRank.textContent = 'Mới';
            displayRank.className = 'rank-badge rank-moi';
            historyNotesSection.style.display = 'none';
        }
        customerModal.style.display = 'flex';
    }
    function closeModal(){ customerModal.style.display = 'none'; }
    closeButtons.forEach(b => b.addEventListener('click', closeModal));
    window.addEventListener('click', (e) => { if (e.target === customerModal) closeModal(); });
    btnAddCustomer?.addEventListener('click', () => openModal(null));

    // ====== Lấy data từ một dòng trong bảng để Edit nhanh ======
    function getRowData(tr) {
        const tds = tr.querySelectorAll('td');
        return {
            id: tr.dataset.id,
            name: (tds[1]?.textContent || '').trim(),
            email: (tds[2]?.textContent || '').trim(),
            phone: (tds[3]?.textContent || '').trim(),
            address: (tds[4]?.textContent || '').trim(),
            rank: (tds[5]?.textContent || '').trim(),
            status: (tds[6]?.textContent || '').trim(),
            created_at: (tds[7]?.textContent || '').trim(),
        };
    }

    // ====== Delegation: edit / view / toggle / delete ======
    document.getElementById('customer-list-table')?.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-action');
        if (!btn) return;
        const tr = btn.closest('tr'); if (!tr) return;
        const id = tr.dataset.id;

        if (btn.classList.contains('edit-customer') || btn.classList.contains('view-detail')) {
            const row = getRowData(tr);
            openModal(row);
            return;
        }
        if (btn.classList.contains('toggle-status')) {
            if (!confirm('Cập nhật trạng thái khách hàng này?')) return;
            try {
                const res = await fetch('customers.php?action=toggle&ajax=1&id=' + encodeURIComponent(id), { method: 'GET' });
                const j = await res.json();
                alert(j.message);
                if (j.ok) location.reload();
            } catch { alert('Cập nhật trạng thái thất bại'); }
            return;
        }
        if (btn.classList.contains('delete-customer')) {
            if (!confirm('Bạn có chắc chắn muốn xóa khách hàng này?')) return;
            try {
                const res = await fetch('customers.php?action=delete&ajax=1&id=' + encodeURIComponent(id), { method: 'GET' });
                const j = await res.json();
                alert(j.message);
                if (j.ok) location.reload();
            } catch { alert('Xóa thất bại'); }
            return;
        }
    });

    // ====== Submit form (Create/Update) via AJAX ======
    customerForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(customerForm);
        fd.append('action', 'save');
        try {
            const res = await fetch('customers.php?ajax=1', { method: 'POST', body: fd });
            const j = await res.json();
            alert(j.message);
            if (j.ok) location.reload();
        } catch (err) {
            alert('Lưu thất bại');
        }
    });
});
