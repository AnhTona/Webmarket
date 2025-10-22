class CustomersPage {
    constructor() {
        // ====== DOM refs ======
        this.sidebar = document.getElementById('sidebar');
        this.menuToggle = document.getElementById('menu-toggle');
        this.notificationBell = document.getElementById('notification-bell');
        this.notificationDropdown = document.getElementById('notification-dropdown');

        this.searchInput = document.getElementById('search-input');
        this.btnSearch   = document.getElementById('btn-search');
        this.filterStatus= document.getElementById('filter-status');
        this.filterRank  = document.getElementById('filter-rank');
        this.btnToggleAdvanced = document.getElementById('btn-toggle-advanced-filter');
        this.advancedFilters   = document.getElementById('advanced-filters');
        this.filterCity   = document.getElementById('filter-city');
        this.filterDateFrom = document.getElementById('filter-date-from');
        this.filterDateTo   = document.getElementById('filter-date-to');

        this.table = document.getElementById('customer-list-table');
        this.tableBody = document.querySelector('#customer-list-table tbody');
        this.btnAddCustomer = document.getElementById('btn-add-customer');

        this.customerModal = document.getElementById('customer-modal');
        this.modalTitle    = document.getElementById('modal-title');
        this.closeButtons  = document.querySelectorAll('.close-button');
        this.customerForm  = document.getElementById('customer-form');
        this.customerId    = document.getElementById('customer-id');
        this.displayRank   = document.getElementById('display-rank');
        this.historyNotesSection = document.getElementById('history-notes-section');

        // Init all
        this.initSidebarAndNotifications();
        this.initFilterControls();
        this.hydrateFromUrl();
        this.initModal();
        this.initTableDelegation();
        this.initSubmitForm();
    }

    // ====== Sidebar & Notifications ======
    initSidebarAndNotifications() {
        this.menuToggle?.addEventListener('click', () => {
            this.sidebar?.classList.toggle('-translate-x-full');
        });

        this.notificationBell?.addEventListener('click', (e) => {
            this.notificationDropdown?.classList.toggle('active');
            e.stopPropagation();
        });

        document.body.addEventListener('click', (e) => {
            if (this.notificationDropdown?.classList.contains('active') &&
                !this.notificationDropdown.contains(e.target) &&
                !this.notificationBell.contains(e.target)) {
                this.notificationDropdown.classList.remove('active');
            }
        });
    }

    // ====== Filters ======
    initFilterControls() {
        const apply = (e) => this.applyFiltersToUrl(e);

        this.btnSearch?.addEventListener('click', apply);
        this.searchInput?.addEventListener('keypress', (e) => { if (e.key === 'Enter') apply(e); });
        this.filterStatus?.addEventListener('change', apply);
        this.filterRank?.addEventListener('change', apply);
        this.filterCity?.addEventListener('input', () => { /* gõ xong hãy bấm tìm */ });
        this.filterDateFrom?.addEventListener('change', apply);
        this.filterDateTo?.addEventListener('change', apply);

        this.btnToggleAdvanced?.addEventListener('click', () => {
            this.advancedFilters?.classList.toggle('hidden');
            const icon = this.btnToggleAdvanced.querySelector('i');
            if (!icon) return;
            if (this.advancedFilters?.classList.contains('hidden')) {
                icon.className = 'fas fa-sliders-h';
                this.btnToggleAdvanced.innerHTML = '<i class="fas fa-sliders-h"></i> Bộ lọc nâng cao';
            } else {
                icon.className = 'fas fa-times';
                this.btnToggleAdvanced.innerHTML = '<i class="fas fa-times"></i> Đóng bộ lọc';
            }
        });
    }

    applyFiltersToUrl(e) {
        if (e) e.preventDefault();
        const qs = new URLSearchParams(location.search);

        const s = (this.searchInput?.value || '').trim();
        if (s) qs.set('q', s); else qs.delete('q');

        const st = this.filterStatus?.value || 'All';
        const rk = this.filterRank?.value   || 'All';
        const city = (this.filterCity?.value || '').trim();
        const df   = this.filterDateFrom?.value || '';
        const dt   = this.filterDateTo?.value   || '';

        qs.set('status', st);
        qs.set('rank', rk);
        if (city) qs.set('city', city); else qs.delete('city');
        if (df)   qs.set('date_from', df); else qs.delete('date_from');
        if (dt)   qs.set('date_to', dt);   else qs.delete('date_to');

        qs.set('page', '1'); // reset trang khi lọc/tìm kiếm
        location.search = qs.toString();
    }

    hydrateFromUrl() {
        const qs = new URLSearchParams(location.search);
        if (qs.has('q') && this.searchInput) this.searchInput.value = qs.get('q');
        if (qs.has('status') && this.filterStatus) this.filterStatus.value = qs.get('status');
        if (qs.has('rank') && this.filterRank) this.filterRank.value = qs.get('rank');
        if (qs.has('city') && this.filterCity) this.filterCity.value = qs.get('city');
        if (qs.has('date_from') && this.filterDateFrom) this.filterDateFrom.value = qs.get('date_from');
        if (qs.has('date_to') && this.filterDateTo) this.filterDateTo.value = qs.get('date_to');
    }

    // ====== Modal ======
    initModal() {
        this.btnAddCustomer?.addEventListener('click', () => this.openModal(null));
        this.closeButtons.forEach(b => b.addEventListener('click', () => this.closeModal()));
        window.addEventListener('click', (e) => { if (e.target === this.customerModal) this.closeModal(); });
    }

    openModal(customer) {
        this.customerForm?.reset();
        if (customer) {
            this.modalTitle.textContent = 'CHỈNH SỬA KHÁCH HÀNG: ' + (customer.name || '');
            this.customerId.value = customer.id || '';
            document.getElementById('full-name').value = customer.name || '';
            document.getElementById('email').value     = customer.email || '';
            document.getElementById('phone').value     = customer.phone || '';
            document.getElementById('address').value   = customer.address || '';
            document.getElementById('status').value    = customer.status || 'Hoạt động';

            const rank = customer.rank || 'Mới';
            this.displayRank.textContent = rank;
            this.displayRank.className = `rank-badge rank-${rank.toLowerCase() === 'mới' ? 'moi' : rank.toLowerCase()}`;
            this.historyNotesSection.style.display = 'grid';
        } else {
            this.modalTitle.textContent = 'THÊM KHÁCH HÀNG MỚI';
            this.customerId.value = '';
            this.displayRank.textContent = 'Mới';
            this.displayRank.className = 'rank-badge rank-moi';
            this.historyNotesSection.style.display = 'none';
        }
        this.customerModal.style.display = 'flex';
    }

    closeModal() {
        this.customerModal.style.display = 'none';
    }

    // ====== Table delegation ======
    initTableDelegation() {
        this.table?.addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-action');
            if (!btn) return;
            const tr = btn.closest('tr'); if (!tr) return;
            const id = tr.dataset.id;

            if (btn.classList.contains('edit-customer') || btn.classList.contains('view-detail')) {
                const row = this.getRowData(tr);
                this.openModal(row);
                return;
            }
            if (btn.classList.contains('toggle-status')) {
                if (!confirm('Cập nhật trạng thái khách hàng này?')) return;
                await this.handleToggleStatus(id);
                return;
            }
            if (btn.classList.contains('delete-customer')) {
                if (!confirm('Bạn có chắc chắn muốn xóa khách hàng này?')) return;
                await this.handleDelete(id);
                return;
            }
        });
    }

    getRowData(tr) {
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

    async handleToggleStatus(id) {
        try {
            const res = await fetch('customers.php?action=toggle&ajax=1&id=' + encodeURIComponent(id), { method: 'GET' });
            const j = await res.json();
            alert(j.message);
            if (j.ok) location.reload();
        } catch {
            alert('Cập nhật trạng thái thất bại');
        }
    }

    async handleDelete(id) {
        try {
            const res = await fetch('customers.php?action=delete&ajax=1&id=' + encodeURIComponent(id), { method: 'GET' });
            const j = await res.json();
            alert(j.message);
            if (j.ok) location.reload();
        } catch {
            alert('Xóa thất bại');
        }
    }

    // ====== Submit form (Create/Update) via AJAX ======
    initSubmitForm() {
        this.customerForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(this.customerForm);
            fd.append('action', 'save');
            try {
                const res = await fetch('customers.php?ajax=1', { method: 'POST', body: fd });
                const j = await res.json();
                alert(j.message);
                if (j.ok) location.reload();
            } catch {
                alert('Lưu thất bại');
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', () => new CustomersPage());
