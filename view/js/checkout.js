// checkout.js (đã bỏ SĐT, dùng email để check rank; giữ nguyên các chức năng khác)

// ===== Helpers format & cart =====
function number_format(number) {
    return new Intl.NumberFormat('vi-VN', { style: 'decimal' }).format(number);
}
function readCart() {
    return JSON.parse(localStorage.getItem('cart')) || [];
}

// ===== Check rank theo EMAIL =====
async function checkMembershipRankByEmail(email) {
    const membershipStatusEl = document.getElementById('membership-status');
    const rankDisplaySummaryEl = document.getElementById('rank-display-summary');

    // validate email cơ bản
    const okEmail = !!email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    if (!okEmail) {
        if (membershipStatusEl) {
            membershipStatusEl.className = 'membership-status rank-default';
            membershipStatusEl.textContent = 'Nhập email hợp lệ để áp dụng ưu đãi.';
        }
        if (rankDisplaySummaryEl) rankDisplaySummaryEl.textContent = 'Mới';
        calculateAndRenderSummary(readCart(), 0, 'Mới');
        return;
    }

    if (membershipStatusEl) {
        membershipStatusEl.className = 'membership-status';
        membershipStatusEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang kiểm tra hạng...';
    }

    try {
        const res = await fetch('/Webmarket/controller/Checkout_Controller.php?action=check_rank_email&email=' + encodeURIComponent(email));
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || 'Lỗi kiểm tra hạng');

        const rank = data.rank?.name || 'Mới';
        const discount = data.rank?.discount || 0;

        if (membershipStatusEl) {
            membershipStatusEl.className = 'membership-status';
            membershipStatusEl.innerHTML = `Hạng hiện tại: <strong>${rank}</strong>`;
        }
        if (rankDisplaySummaryEl) rankDisplaySummaryEl.textContent = rank;

        calculateAndRenderSummary(readCart(), discount, rank);
    } catch (err) {
        console.error(err);
        if (membershipStatusEl) {
            membershipStatusEl.className = 'membership-status rank-default';
            membershipStatusEl.textContent = 'Không kiểm tra được hạng.';
        }
        if (rankDisplaySummaryEl) rankDisplaySummaryEl.textContent = 'Mới';
        calculateAndRenderSummary(readCart(), 0, 'Mới');
    }
}

// ===== Tính & render tóm tắt đơn =====
function calculateAndRenderSummary(cart, discountRate = 0, rankName = 'Mới') {
    const subtotalEl = document.getElementById('summary-subtotal');
    const vatEl = document.getElementById('summary-vat');
    const totalEl = document.getElementById('summary-grand-total');
    const countEl = document.getElementById('summary-item-count');
    const reviewEl = document.getElementById('order-items-review');
    const discountLineEl = document.querySelector('.discount-line');
    const discountEl = document.getElementById('summary-discount');
    const rankDisplaySummaryEl = document.getElementById('rank-display-summary');

    let subtotal = 0;
    let totalQuantity = 0;

    if (reviewEl) reviewEl.innerHTML = '';

    if (cart.length === 0) {
        if (reviewEl) {
            reviewEl.innerHTML = '<p class="empty-message">Giỏ hàng trống. Vui lòng quay lại Giỏ hàng để thêm sản phẩm.</p>';
        }
        const btn = document.getElementById('btn-submit-order');
        if (btn) btn.disabled = true;
    } else {
        cart.forEach(item => {
            const itemTotal = item.price * item.quantity;
            subtotal += itemTotal;
            totalQuantity += item.quantity;

            if (reviewEl) {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'review-item';
                const noteText = item.note ? ` (${item.note})` : '';
                itemDiv.innerHTML = `
          <span class="item-name">${item.name} ${noteText}</span>
          <span class="item-qty-price">${number_format(itemTotal)} VNĐ x ${item.quantity}</span>
        `;
                reviewEl.appendChild(itemDiv);
            }
        });
    }

    const VAT_RATE = 0.08;
    const vat = subtotal * VAT_RATE;
    const totalBeforeDiscount = subtotal + vat;
    const discountAmount = subtotal * discountRate;
    const grandTotal = totalBeforeDiscount - discountAmount;

    if (countEl) countEl.textContent = totalQuantity;
    if (subtotalEl) subtotalEl.textContent = `${number_format(subtotal)} VNĐ`;
    if (vatEl) vatEl.textContent = `${number_format(vat)} VNĐ`;

    if (rankDisplaySummaryEl) rankDisplaySummaryEl.textContent = rankName;
    if (discountLineEl && discountEl) {
        if (discountRate > 0) {
            discountLineEl.style.display = 'flex';
            discountEl.textContent = `- ${number_format(discountAmount)} VNĐ (${discountRate * 100}%)`;
        } else {
            discountLineEl.style.display = 'none';
        }
    }

    if (totalEl) totalEl.textContent = `${number_format(grandTotal)} VNĐ`;

    checkFormValidity();
}

// ===== Validate submit (bỏ yêu cầu SĐT) =====
function checkFormValidity() {
    const tableInput = document.getElementById('table_number');
    const submitButton = document.getElementById('btn-submit-order');

    const isCartEmpty = readCart().length === 0;
    const isTableInputFilled = tableInput && String(tableInput.value).trim() !== '';

    if (submitButton) {
        submitButton.disabled = !(isTableInputFilled && !isCartEmpty);
    }
}

// ===== Prefill: name/email, danh sách bàn (select), auto check rank =====
async function prefillCheckoutFromServer() {
    try {
        const res = await fetch('/Webmarket/controller/Checkout_Controller.php?action=prefill', { credentials: 'same-origin' });
        const data = await res.json();
        if (!data.ok) return;

        // Điền tên + email nếu có (linh hoạt selector)
        const setVal = (selList, val) => {
            const el = selList.map(s => document.querySelector(s)).find(Boolean);
            if (el && val != null) el.value = val;
        };
        setVal(['#customer_name','input[name="customer_name"]','input[name="hoten"]','input[name="name"]'], data.user?.name || '');
        setVal(['#email','input[name="email"]','input[type="email"]'], (data.user?.email || '').trim().toLowerCase());

        // Bàn: đổi input -> select (roll) nếu cần
        const container = document.getElementById('table_number');
        if (container) {
            let selectEl = container.tagName === 'SELECT' ? container : null;
            if (!selectEl) {
                selectEl = document.createElement('select');
                selectEl.id = 'table_number';
                selectEl.name = container.name || 'table_number';
                selectEl.className = container.className || 'form-control';
                container.replaceWith(selectEl);
                selectEl.addEventListener('change', checkFormValidity);
            }
            selectEl.innerHTML = '<option value="">-- Chọn bàn --</option>';
            (data.tables || []).forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.id;
                opt.textContent = t.label;
                opt.disabled = !t.available;
                selectEl.appendChild(opt);
            });
            if (data.suggested_table_id) selectEl.value = String(data.suggested_table_id);
            if (typeof checkFormValidity === 'function') checkFormValidity();
        }

        // Auto check rank nếu đã có email
        const emailEl = document.querySelector('#email, input[name="email"], input[type="email"]');
        const emailVal = (data.user?.email || '').trim().toLowerCase();
        if (emailEl && emailVal) {
            emailEl.value = emailVal; // đồng bộ UI
            checkMembershipRankByEmail(emailVal);
        }
    } catch (e) {
        console.warn('Prefill checkout failed:', e);
    }
}

// ===== Khởi động =====
document.addEventListener('DOMContentLoaded', () => {
    // Render tóm tắt lần đầu
    calculateAndRenderSummary(readCart());

    // Listener email (debounce)
    const emailInput = document.querySelector('#email, input[name="email"], input[type="email"]');
    if (emailInput) {
        let debounceEmailTimer = null;
        emailInput.addEventListener('input', () => {
            const v = emailInput.value.trim().toLowerCase();
            clearTimeout(debounceEmailTimer);
            debounceEmailTimer = setTimeout(() => {
                if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) {
                    checkMembershipRankByEmail(v);
                } else {
                    const membershipStatusEl = document.getElementById('membership-status');
                    const rankDisplaySummaryEl = document.getElementById('rank-display-summary');
                    if (membershipStatusEl) {
                        membershipStatusEl.className = 'membership-status rank-default';
                        membershipStatusEl.textContent = 'Nhập email hợp lệ để áp dụng ưu đãi.';
                    }
                    if (rankDisplaySummaryEl) rankDisplaySummaryEl.textContent = 'Mới';
                    calculateAndRenderSummary(readCart(), 0, 'Mới');
                }
                checkFormValidity();
            }, 500);
        });
    }

    // Toggle chi tiết chuyển khoản & biên lai (giữ nguyên)
    const transferDetailsBox = document.getElementById('transfer-details');
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    paymentMethods.forEach(radio => {
        radio.addEventListener('change', (e) => {
            document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected'));
            e.target.closest('.payment-option')?.classList.add('selected');
            if (e.target.value === 'transfer') {
                if (transferDetailsBox) transferDetailsBox.style.display = 'block';
            } else {
                if (transferDetailsBox) transferDetailsBox.style.display = 'none';
            }
        });
    });

    // Validate số bàn
    const tableInput = document.getElementById('table_number');
    if (tableInput) {
        const ev = tableInput.tagName === 'SELECT' ? 'change' : 'input';
        tableInput.addEventListener(ev, checkFormValidity);
    }

    // Prefill server (tên/email, bàn, auto-rank theo email)
    prefillCheckoutFromServer();

    // Nếu đã có email sẵn trong DOM, kiểm hạng luôn
    if (emailInput && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim().toLowerCase())) {
        checkMembershipRankByEmail(emailInput.value.trim().toLowerCase());
    }

    // Kiểm tra điều kiện submit ban đầu
    checkFormValidity();
});
// Submit form xử lý đơn hàng
document.getElementById('checkout-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    const cart = JSON.parse(localStorage.getItem('cart') || '[]');

    if (cart.length === 0) {
        alert('Giỏ hàng trống!');
        return;
    }

    const formData = new FormData(this);
    formData.append('cart_data', JSON.stringify(cart));

    console.log('Sending cart:', cart); // Debug

    try {
        const response = await fetch('/Webmarket/controller/process_order.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        console.log('Response:', result); // Debug

        if (result.success) {
            localStorage.removeItem('cart');
            window.location.href = result.redirect;
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Lỗi kết nối server');
    }
});