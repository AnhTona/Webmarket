class CustomersPage {
    constructor() {
        // Elements
        this.searchInput = document.getElementById('search-input');
        this.btnSearch = document.getElementById('btn-search');
        this.filterStatus = document.getElementById('filter-status');
        this.filterRank = document.getElementById('filter-rank');
        this.btnToggleAdvanced = document.getElementById('btn-toggle-advanced-filter');
        this.advancedFilters = document.getElementById('advanced-filters');
        this.filterCity = document.getElementById('filter-city');
        this.filterDateFrom = document.getElementById('filter-date-from');
        this.filterDateTo = document.getElementById('filter-date-to');
        this.table = document.getElementById('customer-list-table');
        this.btnAddCustomer = document.getElementById('btn-add-customer');

        // Modal
        this.modal = new AdminModal('customer-modal', 'Quản lý khách hàng');
        this.customerForm = document.getElementById('customer-form');
        this.customerId = document.getElementById('customer-id');
        this.displayRank = document.getElementById('display-rank');
        this.historyNotesSection = document.getElementById('history-notes-section');

        // Init
        this.init();
    }

    init() {
        this.bindEvents();
        this.bindTableActions();
        this.hydrateFromUrl();
    }

    bindEvents() {
        // Toggle advanced filters
        this.btnToggleAdvanced?.addEventListener('click', () => {
            this.advancedFilters?.classList.toggle('hidden');
        });

        // Add customer button
        this.btnAddCustomer?.addEventListener('click', () => this.openAddModal());

        // Search with debounce
        this.searchInput?.addEventListener('input',
            AdminUtils.debounce(() => this.applyFilters(), 300)
        );

        this.btnSearch?.addEventListener('click', () => this.applyFilters());

        // Filters
        this.filterStatus?.addEventListener('change', () => this.applyFilters());
        this.filterRank?.addEventListener('change', () => this.applyFilters());
        this.filterDateFrom?.addEventListener('change', () => this.applyFilters());
        this.filterDateTo?.addEventListener('change', () => this.applyFilters());

        // Enter key to search
        this.searchInput?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.applyFilters();
            }
        });

        // Form submit
        this.customerForm?.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    applyFilters() {
        const params = {
            q: this.searchInput?.value?.trim() || null,
            status: this.filterStatus?.value !== 'All' ? this.filterStatus?.value : null,
            rank: this.filterRank?.value !== 'All' ? this.filterRank?.value : null,
            city: this.filterCity?.value?.trim() || null,
            date_from: this.filterDateFrom?.value || null,
            date_to: this.filterDateTo?.value || null,
            page: 1
        };

        AdminUtils.updateURLParams(params);
        window.location.reload();
    }

    hydrateFromUrl() {
        if (this.searchInput) {
            this.searchInput.value = AdminUtils.getURLParam('q', '');
        }
        if (this.filterStatus) {
            this.filterStatus.value = AdminUtils.getURLParam('status', 'All');
        }
        if (this.filterRank) {
            this.filterRank.value = AdminUtils.getURLParam('rank', 'All');
        }
        if (this.filterCity) {
            this.filterCity.value = AdminUtils.getURLParam('city', '');
        }
        if (this.filterDateFrom) {
            this.filterDateFrom.value = AdminUtils.getURLParam('date_from', '');
        }
        if (this.filterDateTo) {
            this.filterDateTo.value = AdminUtils.getURLParam('date_to', '');
        }
    }

    openAddModal() {
        this.customerForm?.reset();
        this.customerId.value = '';
        this.displayRank.textContent = 'Mới';
        this.displayRank.className = 'rank-badge rank-moi';
        this.historyNotesSection.style.display = 'none';
        this.modal.open();
    }

    openEditModal(customer) {
        this.customerForm?.reset();

        this.customerId.value = customer.id || '';
        document.getElementById('full-name').value = customer.name || '';
        document.getElementById('email').value = customer.email || '';
        document.getElementById('phone').value = customer.phone || '';
        document.getElementById('address').value = customer.address || '';
        document.getElementById('status').value = customer.status || 'Hoạt động';

        const rank = customer.rank || 'Mới';
        this.displayRank.textContent = rank;
        this.displayRank.className = `rank-badge rank-${rank.toLowerCase() === 'mới' ? 'moi' : rank.toLowerCase()}`;
        this.historyNotesSection.style.display = 'grid';

        this.modal.open();
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

    async handleSubmit(e) {
        e.preventDefault();

        const formData = new FormData(this.customerForm);
        formData.append('action', 'save');
        const isEdit = !!formData.get('id');

        const confirmed = await AdminUtils.confirm(
            isEdit ? 'Xác nhận cập nhật thông tin khách hàng?' : 'Xác nhận thêm khách hàng mới?',
            'Xác nhận'
        );

        if (!confirmed) return;

        try {
            const result = await AdminUtils.ajax('customers.php?ajax=1', {
                method: 'POST',
                body: formData
            });

            if (!result.success || !result.data.ok) {
                throw new Error(result.data?.message || 'Lưu thất bại');
            }

            AdminUtils.showToast(
                isEdit ? 'Cập nhật khách hàng thành công!' : 'Thêm khách hàng thành công!',
                'success'
            );

            this.modal.close();
            setTimeout(() => window.location.reload(), 1000);

        } catch (error) {
            AdminUtils.showToast(error.message, 'error');
        }
    }

    bindTableActions() {
        this.table?.addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-action');
            if (!btn) return;

            const tr = btn.closest('tr');
            if (!tr) return;

            const id = tr.dataset.id;
            const customerName = btn.dataset.name || tr.querySelector('td:nth-child(2)')?.textContent?.trim() || 'khách hàng này';

            // Edit customer
            if (btn.classList.contains('edit-customer')) {
                const rowData = this.getRowData(tr);
                this.openEditModal(rowData);
                return;
            }

            // Delete customer
            if (btn.classList.contains('delete-customer')) {
                await this.handleDelete(id, customerName);
                return;
            }

            // Toggle status
            if (btn.classList.contains('toggle-status')) {
                await this.handleToggleStatus(id, customerName);
                return;
            }
        });
    }

    async handleDelete(id, name) {
        const confirmed = await AdminUtils.confirm(
            `Bạn có chắc chắn muốn xóa khách hàng "${name}" không?\nHành động này không thể hoàn tác.`,
            'Xác nhận xóa'
        );

        if (!confirmed) return;

        try {
            const result = await AdminUtils.ajax(
                `customers.php?action=delete&ajax=1&id=${encodeURIComponent(id)}`
            );

            if (!result.success || !result.data.ok) {
                throw new Error(result.data?.message || 'Xóa thất bại');
            }

            AdminUtils.showToast('Xóa khách hàng thành công!', 'success');
            setTimeout(() => window.location.reload(), 1000);

        } catch (error) {
            AdminUtils.showToast(error.message, 'error');
        }
    }

    async handleToggleStatus(id, name) {
        const confirmed = await AdminUtils.confirm(
            `Cập nhật trạng thái khách hàng "${name}"?`,
            'Xác nhận'
        );

        if (!confirmed) return;

        try {
            const result = await AdminUtils.ajax(
                `customers.php?action=toggle&ajax=1&id=${encodeURIComponent(id)}`
            );

            if (!result.success || !result.data.ok) {
                throw new Error(result.data?.message || 'Cập nhật thất bại');
            }

            AdminUtils.showToast('Cập nhật trạng thái thành công!', 'success');
            setTimeout(() => window.location.reload(), 1000);

        } catch (error) {
            AdminUtils.showToast(error.message, 'error');
        }
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    window.customersPage = new CustomersPage();
});