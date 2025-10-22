// admin/js/orders.js — OOP, giữ nguyên hành vi & selector cũ

class OrdersPage {
    constructor() {
        // ===== Elements =====
        this.sidebar = document.getElementById('sidebar');
        this.menuToggle = document.getElementById('menu-toggle');
        this.notificationBell = document.getElementById('notification-bell');
        this.notificationDropdown = document.getElementById('notification-dropdown');

        this.searchInput = document.getElementById('search-input');
        this.btnSearch = document.getElementById('btn-search');
        this.filterDate = document.getElementById('filter-date');
        this.filterStatus = document.getElementById('filter-status');
        this.btnToggleAdvanced = document.getElementById('btn-toggle-advanced-filter');
        this.advancedFilters = document.getElementById('advanced-filters');
        this.filterDateFrom = document.getElementById('filter-date-from');
        this.filterDateTo = document.getElementById('filter-date-to');

        this.orderListTable = document.getElementById('order-list-table');
        this.noResultsMsg = document.getElementById('no-results-message');

        // ===== State =====
        this.state = {
            orders: Array.isArray(window.ordersData) ? window.ordersData : [],
        };

        this.fmt = new Intl.NumberFormat('vi-VN');

        // init
        this.initUI();
        this.bindFilters();
        this.bindTableActions();
        this.renderRows(this.state.orders);
    }

    initUI() {
        this.menuToggle?.addEventListener('click', () => {
            this.sidebar?.classList.toggle('-translate-x-full');
        });

        this.notificationBell?.addEventListener('click', () => {
            this.notificationDropdown?.classList.toggle('active');
        });

        this.btnToggleAdvanced?.addEventListener('click', () => {
            this.advancedFilters?.classList.toggle('hidden');
        });
    }

    renderRows(rows) {
        if (!this.orderListTable) return;
        const tbody = this.orderListTable.tBodies[0] || this.orderListTable.createTBody();
        tbody.innerHTML = '';
        rows.forEach((o) => {
            const tr = document.createElement('tr');
            tr.dataset.id = o.MaDon;
            tr.innerHTML = `
        <td>${o.MaDon}</td>
        <td>${o.KhachHang ?? '-'}</td>
        <td>${o.Ban ?? '-'}</td>
        <td>${o.NgayDat ?? '-'}</td>
        <td>${this.fmt.format(+o.TongTien || 0)} VNĐ</td>
        <td>${o.TrangThai ?? '-'}</td>
        <td class="whitespace-nowrap">
          <button class="btn-action view-detail" title="Xem chi tiết"><i class="fas fa-eye"></i></button>
          <button class="btn-action confirm-order" title="Xác nhận"><i class="fas fa-check"></i></button>
          <button class="btn-action complete-order" title="Hoàn thành"><i class="fas fa-flag-checkered"></i></button>
          <button class="btn-action cancel-order" title="Hủy"><i class="fas fa-times"></i></button>
        </td>
      `;
            tbody.appendChild(tr);
        });
        if (this.noResultsMsg) this.noResultsMsg.style.display = rows.length ? 'none' : 'block';
    }

    applyFilter() {
        const kw = (this.searchInput?.value || '').toLowerCase().trim();
        const ym = this.filterDate?.value || '';
        const st = this.filterStatus?.value || 'All';
        const dFrom = this.filterDateFrom?.value || '';
        const dTo = this.filterDateTo?.value || '';

        const okStatus = (txt) => st === 'All' || (txt || '').toLowerCase() === st.toLowerCase();

        const filtered = this.state.orders.filter((o) => {
            const hitKw =
                !kw ||
                (o.MaDon || '').toLowerCase().includes(kw) ||
                (o.KhachHang || '').toLowerCase().includes(kw);
            const hitYm = !ym || (o.NgayDat || '').slice(0, 7) === ym;
            const hitSt = okStatus(o.TrangThai);
            let hitFrom = true,
                hitTo = true;
            if (dFrom) hitFrom = (o.NgayDat || '').slice(0, 10) >= dFrom;
            if (dTo) hitTo = (o.NgayDat || '').slice(0, 10) <= dTo;
            return hitKw && hitYm && hitSt && hitFrom && hitTo;
        });

        this.renderRows(filtered);
    }

    bindFilters() {
        const run = (e) => {
            if (e?.preventDefault) e.preventDefault();
            this.applyFilter();
        };

        this.btnSearch?.addEventListener('click', run);
        this.searchInput?.addEventListener('input', (e) => {
            if ((e.target.value || '').length === 0) this.applyFilter();
        });
        this.searchInput?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') run(e);
        });
        this.filterDate?.addEventListener('change', run);
        this.filterStatus?.addEventListener('change', run);
        this.filterDateFrom?.addEventListener('change', run);
        this.filterDateTo?.addEventListener('change', run);
    }

    async api(action, id) {
        const res = await fetch(
            `orders.php?ajax=1&action=${encodeURIComponent(action)}&id=${encodeURIComponent(id)}`
        );
        return res.json();
    }

    async fetchOrderDetails(id) {
        try {
            const res = await fetch(
                `orders.php?ajax=1&action=view&id=${encodeURIComponent(id)}`
            );
            const data = await res.json();
            return data.ok ? data.items || [] : [];
        } catch (e) {
            console.error(e);
            return [];
        }
    }

    openDetailModal(items) {
        const modal = document.getElementById('order-detail-modal');
        const body = document.getElementById('order-detail-body');
        if (modal && body) {
            body.innerHTML = items
                .map(
                    (it) => `
        <tr>
          <td>${it.TenSanPham}</td>
          <td>${it.SoLuong}</td>
          <td>${this.fmt.format(+it.Gia || 0)} VNĐ</td>
          <td>${this.fmt.format(+it.Tong || 0)} VNĐ</td>
        </tr>`
                )
                .join('');
            modal.style.display = 'flex';
        } else {
            alert(
                items
                    .map(
                        (it) =>
                            `• ${it.TenSanPham} x${it.SoLuong} = ${this.fmt.format(+it.Tong || 0)}đ`
                    )
                    .join('\n') || 'Không có chi tiết.'
            );
        }
    }

    bindTableActions() {
        this.orderListTable?.addEventListener('click', async (e) => {
            const btn = e.target.closest('button');
            if (!btn) return;
            const tr = btn.closest('tr');
            const id = tr?.dataset?.id;
            if (!id) return;

            if (btn.classList.contains('view-detail') || btn.dataset.action === 'view') {
                const items = await this.fetchOrderDetails(id);
                this.openDetailModal(items);
                return;
            }

            if (btn.classList.contains('confirm-order') || btn.dataset.action === 'confirm') {
                if (!confirm('Xác nhận đơn hàng này?')) return;
                const j = await this.api('confirm', id);
                alert(j.message || (j.ok ? 'Đã xác nhận' : 'Lỗi'));
                if (j.ok) location.reload();
                return;
            }

            if (btn.classList.contains('complete-order') || btn.dataset.action === 'complete') {
                if (!confirm('Đánh dấu Hoàn thành đơn này?')) return;
                const j = await this.api('complete', id);
                alert(j.message || (j.ok ? 'Đã hoàn thành' : 'Lỗi'));
                if (j.ok) location.reload();
                return;
            }

            if (btn.classList.contains('cancel-order') || btn.dataset.action === 'cancel') {
                if (!confirm('Hủy đơn hàng này?')) return;
                const j = await this.api('cancel', id);
                alert(j.message || (j.ok ? 'Đã hủy' : 'Lỗi'));
                if (j.ok) location.reload();
                return;
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.__ordersPage = new OrdersPage();
});
