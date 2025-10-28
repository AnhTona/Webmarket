class AdminStaffPage {
    constructor() {
        this.searchInput = document.getElementById('search-input');
        this.btnSearch   = document.getElementById('btn-search');
        this.filterStatus= document.getElementById('filter-status');
        this.filterRole  = document.getElementById('filter-role');

        this.table = document.getElementById('admin-list-table');
        this.btnAddAdmin = document.getElementById('btn-add-admin');
        this.modal = document.getElementById('admin-modal');
        this.form = document.getElementById('admin-form');

        this.initFilterControls();
        this.hydrateFromUrl();
        this.initModal();
        this.initTableActions();
    }

    initFilterControls() {
        const apply = (e) => this.applyFiltersToUrl(e);

        this.btnSearch?.addEventListener('click', apply);
        this.searchInput?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') apply(e);
        });
        this.filterStatus?.addEventListener('change', apply);
        this.filterRole?.addEventListener('change', apply);
    }

    applyFiltersToUrl(e) {
        if (e) e.preventDefault();
        const qs = new URLSearchParams(location.search);

        const s = (this.searchInput?.value || '').trim();
        if (s) qs.set('q', s);
        else qs.delete('q');

        const st = this.filterStatus?.value || 'All';
        const role = this.filterRole?.value || 'All';

        qs.set('status', st);
        qs.set('role', role);
        qs.set('page', '1');
        location.search = qs.toString();
    }

    hydrateFromUrl() {
        const qs = new URLSearchParams(location.search);
        if (qs.has('q') && this.searchInput) this.searchInput.value = qs.get('q');
        if (qs.has('status') && this.filterStatus) this.filterStatus.value = qs.get('status');
        if (qs.has('role') && this.filterRole) this.filterRole.value = qs.get('role');
    }

    initModal() {
        this.btnAddAdmin?.addEventListener('click', () => this.openModal());

        document.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', () => this.closeModal());
        });

        this.modal?.addEventListener('click', (e) => {
            if (e.target === this.modal) this.closeModal();
        });

        this.form?.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    initTableActions() {
        this.table?.addEventListener('click', async (e) => {
            const btn = e.target.closest('button');
            if (!btn) return;

            const id = btn.dataset.id;

            if (btn.classList.contains('edit-admin')) {
                this.openModal({
                    id: btn.dataset.id,
                    username: btn.dataset.username,
                    name: btn.dataset.name,
                    email: btn.dataset.email,
                    phone: btn.dataset.phone,
                    role: btn.dataset.role,
                    status: btn.dataset.status
                });
            } else if (btn.classList.contains('toggle-status')) {
                if (!confirm('Bạn có chắc muốn thay đổi trạng thái tài khoản này?')) return;
                await this.toggleStatus(id);
            } else if (btn.classList.contains('delete-admin')) {
                const name = btn.dataset.name;
                if (!confirm(`Bạn có chắc chắn muốn xóa tài khoản "${name}"?`)) return;
                await this.deleteAdmin(id);
            }
        });
    }

    openModal(admin = null) {
        this.form.reset();
        const title = document.getElementById('modal-title');
        const passwordHint = document.getElementById('password-hint');

        if (admin) {
            title.textContent = 'SỬA TÀI KHOẢN: ' + (admin.name || '');
            document.getElementById('admin-id').value = admin.id || '';
            document.getElementById('admin-username').value = admin.username || '';
            document.getElementById('admin-name').value = admin.name || '';
            document.getElementById('admin-email').value = admin.email || '';
            document.getElementById('admin-phone').value = admin.phone || '';
            document.getElementById('admin-role').value = admin.role || 'STAFF';
            document.getElementById('admin-status').value = admin.status || 'Hoạt động';
            passwordHint.textContent = '(Để trống nếu không đổi mật khẩu)';
            document.getElementById('admin-password').removeAttribute('required');
        } else {
            title.textContent = 'THÊM TÀI KHOẢN MỚI';
            document.getElementById('admin-id').value = '';
            passwordHint.textContent = '(Bắt buộc khi thêm mới)';
            document.getElementById('admin-password').setAttribute('required', 'required');
        }

        this.modal.style.display = 'flex';
    }

    closeModal() {
        this.modal.style.display = 'none';
    }

    async handleSubmit(e) {
        e.preventDefault();
        const fd = new FormData(this.form);
        fd.append('action', 'save');

        try {
            const res = await fetch('admin_staff.php?ajax=1', { method: 'POST', body: fd });
            const j = await res.json();
            alert(j.message);
            if (j.ok) location.reload();
        } catch {
            alert('Lưu thất bại');
        }
    }

    async toggleStatus(id) {
        try {
            const res = await fetch(`admin_staff.php?action=toggle&ajax=1&id=${id}`, { method: 'GET' });
            const j = await res.json();
            alert(j.message);
            if (j.ok) location.reload();
        } catch {
            alert('Cập nhật trạng thái thất bại');
        }
    }

    async deleteAdmin(id) {
        try {
            const res = await fetch(`admin_staff.php?action=delete&ajax=1&id=${id}`, { method: 'GET' });
            const j = await res.json();
            alert(j.message);
            if (j.ok) location.reload();
        } catch {
            alert('Xóa thất bại');
        }
    }
}

document.addEventListener('DOMContentLoaded', () => new AdminStaffPage());