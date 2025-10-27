/**
 * orders.js - Optimized version
 * Quản lý đơn hàng với AdminUtils và better UX
 */

class OrdersPage {
    constructor() {
        // Elements
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

        // Modal (sử dụng AdminModal)
        this.modal = new AdminModal('order-detail-modal', 'Chi tiết đơn hàng');

        // State
        this.state = {
            orders: Array.isArray(window.ordersData) ? window.ordersData : [],
        };

        this.fmt = new Intl.NumberFormat('vi-VN');

        // Init
        this.init();
    }

    init() {
        this.bindEvents();
        this.bindTableActions();
        // PHP đã render rows, không cần renderRows() nữa
    }

    bindEvents() {
        // Toggle advanced filters
        this.btnToggleAdvanced?.addEventListener('click', () => {
            this.advancedFilters?.classList.toggle('hidden');
        });

        // Search with debounce
        this.searchInput?.addEventListener('input',
            AdminUtils.debounce(() => this.applyFilter(), 300)
        );

        this.btnSearch?.addEventListener('click', () => this.applyFilter());

        // Filters
        this.filterDate?.addEventListener('change', () => this.applyFilter());
        this.filterStatus?.addEventListener('change', () => this.applyFilter());
        this.filterDateFrom?.addEventListener('change', () => this.applyFilter());
        this.filterDateTo?.addEventListener('change', () => this.applyFilter());

        // Enter key to search
        this.searchInput?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.applyFilter();
            }
        });
    }

    applyFilter() {
        const params = {
            search: this.searchInput?.value?.trim() || null,
            date: this.filterDate?.value || null,
            status: this.filterStatus?.value !== 'All' ? this.filterStatus?.value : null,
            date_from: this.filterDateFrom?.value || null,
            date_to: this.filterDateTo?.value || null,
            page: 1
        };

        AdminUtils.updateURLParams(params);
        window.location.reload();
    }

    async viewOrderDetails(orderId) {
        try {
            const result = await AdminUtils.ajax(
                `orders.php?ajax=1&action=view&id=${encodeURIComponent(orderId)}`
            );

            if (!result.success || !result.data.ok) {
                throw new Error(result.data?.message || 'Không thể tải chi tiết đơn hàng');
            }

            this.displayOrderDetails(result.data);

        } catch (error) {
            AdminUtils.showToast(error.message, 'error');
        }
    }

    displayOrderDetails(data) {
        const order = data.order || {};
        const items = data.items || [];
        const calc = data.calculations || {};

        let html = `
            <div style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 8px;">
                <h3 style="margin: 0 0 15px 0; color: #333; border-bottom: 2px solid #8f2c24; padding-bottom: 8px;">Thông tin đơn hàng</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div><strong>Mã đơn:</strong> #${AdminUtils.escapeHTML(order.MaDon || '-')}</div>
                    <div><strong>Khách hàng:</strong> ${AdminUtils.escapeHTML(order.KhachHang || '-')}</div>
                    <div><strong>Email:</strong> ${AdminUtils.escapeHTML(order.Email || '-')}</div>
                    <div><strong>Hạng TV:</strong> <span style="padding: 2px 8px; border-radius: 12px; background: #fff3e0; color: #e65100;">${AdminUtils.escapeHTML(order.HangTV || 'Mới')}</span></div>
                    <div><strong>Bàn:</strong> ${AdminUtils.escapeHTML(order.Ban || '-')}</div>
                    <div><strong>Ngày đặt:</strong> ${AdminUtils.escapeHTML(order.NgayDat || '-')}</div>
                    <div><strong>Phương thức:</strong> ${this.getPaymentMethodName(order.PhuongThuc)}</div>
                    <div><strong>Trạng thái:</strong> ${AdminUtils.escapeHTML(order.TrangThai || '-')}</div>
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
                    <td style="padding: 10px;">${AdminUtils.escapeHTML(item.TenSanPham || '-')}</td>
                    <td style="padding: 10px; text-align: center;">${item.SoLuong || 0}</td>
                    <td style="padding: 10px; text-align: right;">${AdminUtils.formatCurrency(+item.Gia || 0)}</td>
                    <td style="padding: 10px; text-align: right;">${AdminUtils.formatCurrency(+item.Tong || 0)}</td>
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
                    <span style="font-weight: 600;">${AdminUtils.formatCurrency(+calc.subtotal || 0)}</span>
                </div>
        `;

        if (calc.discount_amount && calc.discount_amount > 0) {
            const discountPercent = ((calc.discount_rate || 0) * 100).toFixed(0);
            html += `
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #ddd; background: #fff3e0; margin: 5px -15px; padding-left: 15px; padding-right: 15px;">
                    <span style="color: #e65100;">Giảm giá (Hạng ${AdminUtils.escapeHTML(order.HangTV || 'Mới')} - ${discountPercent}%):</span>
                    <span style="font-weight: 600; color: #d84315;">- ${AdminUtils.formatCurrency(+calc.discount_amount || 0)}</span>
                </div>
            `;
        }

        html += `
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #ddd;">
                    <span>VAT (8%):</span>
                    <span style="font-weight: 600;">${AdminUtils.formatCurrency(+calc.vat || 0)}</span>
                </div>

                <div style="display: flex; justify-content: space-between; padding: 15px 0; margin-top: 10px; background: #8f2c24; color: white; margin: 10px -15px 0; padding-left: 15px; padding-right: 15px; border-radius: 8px;">
                    <span style="font-size: 18px; font-weight: 700;">TỔNG THANH TOÁN:</span>
                    <span style="font-size: 18px; font-weight: 700;">${AdminUtils.formatCurrency(+calc.grand_total || 0)}</span>
                </div>
            </div>
        `;

        const detailsDiv = document.getElementById('order-details');
        if (detailsDiv) {
            AdminUtils.setInnerHTML(detailsDiv, html);
        }

        this.modal.open();
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

            // View order details
            if (btn.classList.contains('view-detail') || btn.classList.contains('view-order')) {
                const base = location.pathname.replace(/\/[^\/]*$/, '/');
                window.open(base + 'invoice.php?id=' + encodeURIComponent(id), '_blank');
                return;
            }

            // Confirm order
            if (btn.classList.contains('confirm-order')) {
                const confirmed = await AdminUtils.confirm(
                    'Xác nhận đơn hàng này?',
                    'Xác nhận đơn hàng'
                );
                if (!confirmed) return;

                try {
                    const result = await AdminUtils.ajax(
                        `orders.php?ajax=1&action=confirm&id=${encodeURIComponent(id)}`
                    );

                    if (!result.success || !result.data.ok) {
                        throw new Error(result.data?.message || 'Xác nhận thất bại');
                    }

                    AdminUtils.showToast('Đã xác nhận đơn hàng!', 'success');
                    setTimeout(() => location.reload(), 1000);

                } catch (error) {
                    AdminUtils.showToast(error.message, 'error');
                }
                return;
            }

            // Complete order
            if (btn.classList.contains('complete-order')) {
                const confirmed = await AdminUtils.confirm(
                    'Đánh dấu hoàn thành đơn hàng này?',
                    'Hoàn thành đơn hàng'
                );
                if (!confirmed) return;

                try {
                    const result = await AdminUtils.ajax(
                        `orders.php?ajax=1&action=complete&id=${encodeURIComponent(id)}`
                    );

                    if (!result.success || !result.data.ok) {
                        throw new Error(result.data?.message || 'Hoàn thành thất bại');
                    }

                    AdminUtils.showToast('Đã hoàn thành đơn hàng!', 'success');
                    setTimeout(() => location.reload(), 1000);

                } catch (error) {
                    AdminUtils.showToast(error.message, 'error');
                }
                return;
            }

            // Cancel order
            if (btn.classList.contains('cancel-order')) {
                const confirmed = await AdminUtils.confirm(
                    'Hủy đơn hàng này?\nHành động này không thể hoàn tác.',
                    'Xác nhận hủy đơn'
                );
                if (!confirmed) return;

                try {
                    const result = await AdminUtils.ajax(
                        `orders.php?ajax=1&action=cancel&id=${encodeURIComponent(id)}`
                    );

                    if (!result.success || !result.data.ok) {
                        throw new Error(result.data?.message || 'Hủy đơn thất bại');
                    }

                    AdminUtils.showToast('Đã hủy đơn hàng!', 'success');
                    setTimeout(() => location.reload(), 1000);

                } catch (error) {
                    AdminUtils.showToast(error.message, 'error');
                }
                return;
            }
        });
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    window.ordersPage = new OrdersPage();
});