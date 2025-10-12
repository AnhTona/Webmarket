document.addEventListener('DOMContentLoaded', () => {
    /* ===== Elements (tên id bám theo layout hiện có) ===== */
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menu-toggle');
    const contentArea = document.getElementById('content-area');
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
    const noResultsMsg = document.getElementById('no-results-message');

    /* ===== State ===== */
    const state = {
        orders: Array.isArray(window.ordersData) ? window.ordersData : [],
    };

    /* ===== Utilities ===== */
    const fmt = new Intl.NumberFormat('vi-VN');

    function renderRows(rows) {
        if (!orderListTable) return;
        const tbody = orderListTable.tBodies[0] || orderListTable.createTBody();
        tbody.innerHTML = '';
        rows.forEach(o => {
            const tr = document.createElement('tr');
            tr.dataset.id = o.MaDon;
            tr.innerHTML = `
        <td>${o.MaDon}</td>
        <td>${o.KhachHang ?? '-'}</td>
        <td>${o.Ban ?? '-'}</td>
        <td>${o.NgayDat ?? '-'}</td>
        <td>${fmt.format(+o.TongTien || 0)} VNĐ</td>
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
        if (noResultsMsg) noResultsMsg.style.display = rows.length ? 'none' : 'block';
    }

    function applyFilter() {
        const kw = (searchInput?.value || '').toLowerCase().trim();
        const ym = filterDate?.value || '';
        const st = filterStatus?.value || 'All';
        const dFrom = filterDateFrom?.value || '';
        const dTo = filterDateTo?.value || '';

        const okStatus = (txt) => (st === 'All' || (txt || '').toLowerCase() === st.toLowerCase());

        const filtered = state.orders.filter(o => {
            const hitKw =
                !kw ||
                (o.MaDon || '').toLowerCase().includes(kw) ||
                (o.KhachHang || '').toLowerCase().includes(kw);
            const hitYm = !ym || (o.NgayDat || '').slice(0, 7) === ym;
            const hitSt = okStatus(o.TrangThai);
            let hitFrom = true, hitTo = true;
            if (dFrom) hitFrom = (o.NgayDat || '').slice(0, 10) >= dFrom;
            if (dTo)   hitTo   = (o.NgayDat || '').slice(0, 10) <= dTo;
            return hitKw && hitYm && hitSt && hitFrom && hitTo;
        });

        renderRows(filtered);
    }

    async function api(action, id) {
        const res = await fetch(`orders.php?ajax=1&action=${encodeURIComponent(action)}&id=${encodeURIComponent(id)}`);
        return res.json();
    }

    async function fetchOrderDetails(id) {
        try {
            const res = await fetch(`orders.php?ajax=1&action=view&id=${encodeURIComponent(id)}`);
            const data = await res.json();
            return data.ok ? (data.items || []) : [];
        } catch (e) {
            console.error(e);
            return [];
        }
    }

    function openDetailModal(items) {
        // Bạn đã có sẵn modal thì populate theo id của bạn:
        const modal = document.getElementById('order-detail-modal');
        const body = document.getElementById('order-detail-body');
        if (modal && body) {
            body.innerHTML = items.map(it => `
        <tr>
          <td>${it.TenSanPham}</td>
          <td>${it.SoLuong}</td>
          <td>${fmt.format(+it.Gia || 0)} VNĐ</td>
          <td>${fmt.format(+it.Tong || 0)} VNĐ</td>
        </tr>
      `).join('');
            modal.style.display = 'flex';
        } else {
            // fallback nếu bạn chưa làm modal
            alert(items.map(it => `• ${it.TenSanPham} x${it.SoLuong} = ${fmt.format(+it.Tong||0)}đ`).join('\n') || 'Không có chi tiết.');
        }
    }

    /* ===== Events ===== */
    menuToggle?.addEventListener('click', () => {
        sidebar?.classList.toggle('-translate-x-full');
    });

    notificationBell?.addEventListener('click', () => {
        notificationDropdown?.classList.toggle('active');
    });

    btnToggleAdvanced?.addEventListener('click', () => {
        advancedFilters?.classList.toggle('hidden');
    });

    btnSearch?.addEventListener('click', applyFilter);
    searchInput?.addEventListener('input', (e) => {
        if ((e.target.value || '').length === 0) applyFilter();
    });
    filterDate?.addEventListener('change', applyFilter);
    filterStatus?.addEventListener('change', applyFilter);
    filterDateFrom?.addEventListener('change', applyFilter);
    filterDateTo?.addEventListener('change', applyFilter);

    // Event delegation cho các nút hành động
    orderListTable?.addEventListener('click', async (e) => {
        const btn = e.target.closest('button');
        if (!btn) return;
        const tr = btn.closest('tr');
        const id = tr?.dataset?.id;
        if (!id) return;

        if (btn.classList.contains('view-detail') || btn.dataset.action === 'view') {
            const items = await fetchOrderDetails(id);
            openDetailModal(items);
            return;
        }

        if (btn.classList.contains('confirm-order') || btn.dataset.action === 'confirm') {
            const ok = confirm('Xác nhận đơn hàng này?');
            if (!ok) return;
            const j = await api('confirm', id);
            alert(j.message || (j.ok ? 'Đã xác nhận' : 'Lỗi'));
            if (j.ok) location.reload();
            return;
        }

        if (btn.classList.contains('complete-order') || btn.dataset.action === 'complete') {
            const ok = confirm('Đánh dấu Hoàn thành đơn này?');
            if (!ok) return;
            const j = await api('complete', id);
            alert(j.message || (j.ok ? 'Đã hoàn thành' : 'Lỗi'));
            if (j.ok) location.reload();
            return;
        }

        if (btn.classList.contains('cancel-order') || btn.dataset.action === 'cancel') {
            const ok = confirm('Hủy đơn hàng này?');
            if (!ok) return;
            const j = await api('cancel', id);
            alert(j.message || (j.ok ? 'Đã hủy' : 'Lỗi'));
            if (j.ok) location.reload();
            return;
        }
    });

    /* ===== First render ===== */
    renderRows(state.orders);
});
