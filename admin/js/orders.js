// admin/js/orders.js – Sửa lỗi modal không hiển thị

class OrdersPage {
    constructor() {
        // ===== Elements =====
        this.searchInput   = document.getElementById('search-input');
        this.btnSearch     = document.getElementById('btn-search');
        this.filterDate    = document.getElementById('filter-date');
        this.filterStatus  = document.getElementById('filter-status');

        this.orderListTable = document.getElementById('order-list-table');
        this.noResultsMsg   = document.getElementById('no-results-message');
        this.modal          = document.getElementById('order-detail-modal');

        // ===== State (khởi tạo 1 lần, KHÔNG ghi đè sau đó) =====
        const data = Array.isArray(window.ordersData) ? window.ordersData : [];
        this.state = {
            orders: data,          // all rows
            filtered: data,        // rows sau khi lọc
            pageSize: 10,          // 10 đơn/trang
            currentPage: 1,
            totalPages: 1,
        };

        this.fmt = new Intl.NumberFormat('vi-VN');

        // ===== Init =====
        this.initUI();
        this.bindFilters();
        this.bindTableActions();
        this.paginateAndRender();
    }


    initUI() {
        this.btnToggleAdvanced?.addEventListener('click', () => {
            this.advancedFilters?.classList.toggle('hidden');
        });

        // Modal close buttons
        document.querySelectorAll('.close-button').forEach(btn => {
            btn.addEventListener('click', () => {
                this.closeModal();
            });
        });

        // Click outside modal to close
        window.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.closeModal();
            }
        });
    }
    paginateAndRender() {
        const { filtered, pageSize, currentPage } = this.state;
        const total = filtered.length;
        const start = (currentPage - 1) * pageSize;
        const pageRows = filtered.slice(start, start + pageSize);
        this.renderRows(pageRows);
        this.renderPagination(total);
    }

    renderPagination(total) {
        const wrap = document.getElementById('pagination');
        const info = document.getElementById('page-info');
        if (!wrap) return;

        const pages = Math.max(1, Math.ceil(total / this.state.pageSize));
        this.state.totalPages = pages;
        const cur = this.state.currentPage;

        const mkBtn = (label, page, disabled = false, active = false) => {
            const b = document.createElement('button');
            b.className = 'page-btn' + (active ? ' active' : '');
            b.textContent = label;
            b.disabled = disabled;
            b.addEventListener('click', () => { this.state.currentPage = page; this.paginateAndRender(); });
            return b;
        };

        wrap.innerHTML = '';
        // Prev
        wrap.appendChild(mkBtn('‹', Math.max(1, cur - 1), cur === 1));

        // Số trang (tối đa 7 nút quanh trang hiện tại)
        const start = Math.max(1, cur - 3);
        const end = Math.min(pages, cur + 3);
        for (let i = start; i <= end; i++) {
            wrap.appendChild(mkBtn(String(i), i, false, i === cur));
        }

        // Next
        wrap.appendChild(mkBtn('›', Math.min(pages, cur + 1), cur === pages));

        // Info
        if (info) {
            const from = total ? ( (cur - 1) * this.state.pageSize + 1 ) : 0;
            const to = Math.min(total, cur * this.state.pageSize);
            info.textContent = `Hiển thị ${from}–${to} / ${total} đơn`;
        }
    }

    closeModal() {
        if (this.modal) {
            this.modal.classList.remove('active');
            this.modal.style.display = 'none';
        }
    }

    renderRows(rows) {
        if (!this.orderListTable) return;
        const tbody = this.orderListTable.tBodies[0] || this.orderListTable.createTBody();
        tbody.innerHTML = '';
        rows.forEach((o) => {
            const tr = document.createElement('tr');
            tr.dataset.id = o.MaDon;

            // Xác định nút nào hiển thị dựa trên trạng thái
            let actionButtons = '';
            const status = (o.TrangThai || '').trim();

            if (status === 'Chờ xác nhận') {
                actionButtons = `
                    <button class="btn-action view-detail" title="Xem chi tiết"><i class="fas fa-eye"></i> Xem</button>
                    <button class="btn-action confirm-order" title="Xác nhận"><i class="fas fa-check"></i> Xác nhận</button>
                    <button class="btn-action cancel-order" title="Hủy"><i class="fas fa-times"></i> Hủy</button>
                `;
            } else if (status === 'Đang chuẩn bị') {
                actionButtons = `
                    <button class="btn-action view-detail" title="Xem chi tiết"><i class="fas fa-eye"></i> Xem</button>
                    <button class="btn-action complete-order" title="Hoàn thành"><i class="fas fa-check-double"></i> Hoàn thành</button>
                    <button class="btn-action cancel-order" title="Hủy"><i class="fas fa-times"></i> Hủy</button>
                `;
            } else {
                // Trạng thái Hoàn thành, Đã hủy, hoặc khác
                actionButtons = `
                    <button class="btn-action view-detail" title="Xem chi tiết"><i class="fas fa-eye"></i> Xem</button>
                `;
            }

            tr.innerHTML = `
                <td>${o.MaDon}</td>
                <td>${o.KhachHang ?? '-'}</td>
                <td>${o.Ban ?? '-'}</td>
                <td>${o.NgayDat ?? '-'}</td>
                <td>${this.fmt.format(+o.TongTien || 0)} VNĐ</td>
                <td>${o.TrangThai ?? '-'}</td>
                <td>${actionButtons}</td>
            `;
            tbody.appendChild(tr);
        });
        if (this.noResultsMsg) this.noResultsMsg.style.display = rows.length ? 'none' : 'block';
    }

    applyFilter() {
        const raw = (this.searchInput?.value || '').trim();
        const kw = raw.toLowerCase().replace(/^#/, '');    // bỏ # nếu có
        const kwDigits = kw.replace(/\D/g, '');            // phần số để so với mã đơn

        const day  = (this.filterDate?.value || '').trim();      // YYYY-MM-DD
        const st   = (this.filterStatus?.value || 'All').trim();
        const dFrom = (this.filterDateFrom?.value || '').trim();
        const dTo   = (this.filterDateTo?.value || '').trim();

        const okStatus = (txt) => st === 'All' || (txt || '').toLowerCase() === st.toLowerCase();

        const filtered = this.state.orders.filter((o) => {
            const idStr   = String(o.MaDon ?? '').toLowerCase();
            const nameStr = String(o.KhachHang ?? '').toLowerCase();
            const dateStr = String(o.NgayDat ?? '').slice(0, 10);  // YYYY-MM-DD

            // Tìm đồng thời: mã đơn (theo số) HOẶC tên khách hàng (theo chữ)
            const hitKw = !kw
                ? true
                : (kwDigits && idStr.includes(kwDigits)) || nameStr.includes(kw);

            const hitDay  = !day || dateStr === day;
            const hitFrom = !dFrom || dateStr >= dFrom;
            const hitTo   = !dTo   || dateStr <= dTo;
            const hitSt   = okStatus(o.TrangThai);

            return hitKw && hitDay && hitFrom && hitTo && hitSt;
        });

        this.state.filtered = filtered;
        this.state.currentPage = 1;     // về trang đầu mỗi lần lọc
        this.paginateAndRender();
    }

    bindFilters() {
        const run = (e) => {
            if (e?.preventDefault) e.preventDefault();
            this.state.currentPage = 1;   // quan trọng!
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
            console.log('Fetching order details for ID:', id);
            const res = await fetch(
                `orders.php?ajax=1&action=view&id=${encodeURIComponent(id)}`
            );
            const data = await res.json();
            console.log('Received data:', data);
            return data.ok ? data : null;
        } catch (e) {
            console.error('Error fetching order details:', e);
            return null;
        }
    }

    openDetailModal(data) {
        console.log('Opening modal with data:', data);

        if (!data) {
            alert('Không thể tải chi tiết đơn hàng');
            return;
        }

        const detailsDiv = document.getElementById('order-details');

        if (!this.modal || !detailsDiv) {
            alert('Modal không tồn tại');
            console.error('Modal or details div not found');
            return;
        }

        const order = data.order || {};
        const items = data.items || [];
        const calc = data.calculations || {};

        let html = `
            <div style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 8px;">
                <h3 style="margin: 0 0 15px 0; color: #333; border-bottom: 2px solid #8f2c24; padding-bottom: 8px;">Thông tin đơn hàng</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div><strong>Mã đơn:</strong> #${order.MaDon || '-'}</div>
                    <div><strong>Khách hàng:</strong> ${order.KhachHang || '-'}</div>
                    <div><strong>Email:</strong> ${order.Email || '-'}</div>
                    <div><strong>Hạng TV:</strong> <span style="padding: 2px 8px; border-radius: 12px; background: #fff3e0; color: #e65100;">${order.HangTV || 'Mới'}</span></div>
                    <div><strong>Bàn:</strong> ${order.Ban || '-'}</div>
                    <div><strong>Ngày đặt:</strong> ${order.NgayDat || '-'}</div>
                    <div><strong>Phương thức:</strong> ${this.getPaymentMethodName(order.PhuongThuc)}</div>
                    <div><strong>Trạng thái:</strong> ${order.TrangThai || '-'}</div>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <h3 style="margin: 0 0 10px 0; color: #333; border-bottom: 2px solid #8f2c24; padding-bottom: 8px;">Chi tiết sản phẩm</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #8f2c24; color: white;">
                            <th style="padding: 10px; text-align: left;">Sản phẩm</th>
                            <th style="padding: 10px; text-align: center;">SL</th>
                            <th style="padding: 10px; text-align: right;">Đơn giá</th>
                            <th style="padding: 10px; text-align: right;">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        items.forEach(item => {
            html += `
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 10px;">${item.TenSanPham || '-'}</td>
                    <td style="padding: 10px; text-align: center;">${item.SoLuong || 0}</td>
                    <td style="padding: 10px; text-align: right;">${this.fmt.format(+item.Gia || 0)} đ</td>
                    <td style="padding: 10px; text-align: right;">${this.fmt.format(+item.Tong || 0)} đ</td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>

            <div style="padding: 15px; background: #f9f9f9; border-radius: 8px;">
                <h3 style="margin: 0 0 15px 0; color: #333; border-bottom: 2px solid #8f2c24; padding-bottom: 8px;">Chi tiết thanh toán</h3>
                
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #ddd;">
                    <span>Tạm tính:</span>
                    <span style="font-weight: 600;">${this.fmt.format(+calc.subtotal || 0)} đ</span>
                </div>
        `;

        if (calc.discount_amount && calc.discount_amount > 0) {
            const discountPercent = ((calc.discount_rate || 0) * 100).toFixed(0);
            html += `
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #ddd; background: #fff3e0; margin: 5px -15px; padding-left: 15px; padding-right: 15px;">
                    <span style="color: #e65100;">Giảm giá (Hạng ${order.HangTV || 'Mới'} - ${discountPercent}%):</span>
                    <span style="font-weight: 600; color: #d84315;">- ${this.fmt.format(+calc.discount_amount || 0)} đ</span>
                </div>
            `;
        }

        html += `
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #ddd;">
                    <span>VAT (8%):</span>
                    <span style="font-weight: 600;">${this.fmt.format(+calc.vat || 0)} đ</span>
                </div>

                <div style="display: flex; justify-content: space-between; padding: 15px 0; margin-top: 10px; background: #8f2c24; color: white; margin: 10px -15px 0; padding-left: 15px; padding-right: 15px; border-radius: 6px;">
                    <span style="font-size: 18px; font-weight: 700;">TỔNG THANH TOÁN:</span>
                    <span style="font-size: 18px; font-weight: 700;">${this.fmt.format(+calc.grand_total || 0)} đ</span>
                </div>
            </div>
        `;

        detailsDiv.innerHTML = html;
        this.modal.style.display = 'flex';
        this.modal.classList.add('active');

        console.log('Modal opened successfully');
    }

    getPaymentMethodName(method) {
        const methods = {
            'CASH': 'Tiền mặt',
            'TRANSFER': 'Chuyển khoản',
            'CARD': 'Thẻ ngân hàng',
            'BANKING': 'Chuyển khoản ngân hàng',
            'EWALLET': 'Ví điện tử',
            'MOMO': 'Ví MoMo',
            'ZALOPAY': 'ZaloPay'
        };
        return methods[method] || 'Tiền mặt';
    }

    bindTableActions() {
        this.orderListTable?.addEventListener('click', async (e) => {
            const btn = e.target.closest('button');
            if (!btn) return;
            const tr = btn.closest('tr');
            const id = tr?.dataset?.id;
            if (!id) return;

            console.log('Button clicked:', btn.className, 'ID:', id);

            if (btn.classList.contains('view-detail') || btn.classList.contains('view-order') || btn.classList.contains('btn-view')) {
                const base = location.pathname.replace(/\/[^\/]*$/, '/');
                window.open(base + 'invoice.php?id=' + encodeURIComponent(id), '_blank');
                return;
            }

            if (btn.classList.contains('confirm-order')) {
                if (!confirm('Xác nhận đơn hàng này?')) return;
                const j = await this.api('confirm', id);
                if (j.ok) {
                    alert(j.message || 'Đã xác nhận');
                    // Tự động reload trang
                    location.reload();
                } else {
                    alert(j.message || 'Lỗi khi xác nhận đơn hàng');
                }
                return;
            }

            if (btn.classList.contains('complete-order')) {
                if (!confirm('Đánh dấu Hoàn thành đơn này?')) return;
                const j = await this.api('complete', id);
                if (j.ok) {
                    alert(j.message || 'Đã hoàn thành');
                    // Tự động reload trang
                    location.reload();
                } else {
                    alert(j.message || 'Lỗi khi hoàn thành đơn hàng');
                }
                return;
            }

            if (btn.classList.contains('cancel-order')) {
                if (!confirm('Hủy đơn hàng này?')) return;
                const j = await this.api('cancel', id);
                if (j.ok) {
                    alert(j.message || 'Đã hủy');
                    // Tự động reload trang
                    location.reload();
                } else {
                    alert(j.message || 'Lỗi khi hủy đơn hàng');
                }
                return;
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.__ordersPage = new OrdersPage();
    console.log('OrdersPage initialized');
});