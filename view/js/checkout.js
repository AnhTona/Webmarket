// checkout.js

// Hàm định dạng tiền tệ (được lấy từ cart.js)
function number_format(number) {
    // Giả sử number_format đã được định nghĩa trong cart.js
    // Nếu không, bạn cần định nghĩa nó lại ở đây hoặc đảm bảo nó được load.
    return new Intl.NumberFormat('vi-VN', { style: 'decimal' }).format(number);
}

// Hàm đọc giỏ hàng (được lấy từ cart.js)
function readCart() {
    return JSON.parse(localStorage.getItem('cart')) || [];
}

// **Hàm mô phỏng kiểm tra hạng thành viên (Cần thay thế bằng AJAX thật)**
function checkMembershipRank(phoneNumber) {
    const membershipStatusEl = document.getElementById('membership-status');
    const rankDisplaySummaryEl = document.getElementById('rank-display-summary');
    
    if (phoneNumber.length < 9) {
        membershipStatusEl.className = 'membership-status rank-default';
        membershipStatusEl.innerHTML = 'Vui lòng nhập SĐT hợp lệ (10 số).';
        rankDisplaySummaryEl.textContent = 'Mới';
        // Gọi lại tính toán tổng tiền với ưu đãi = 0 khi SĐT không hợp lệ
        calculateAndRenderSummary(readCart(), 0, 'Mới'); 
        return;
    }

    membershipStatusEl.className = 'membership-status';
    membershipStatusEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang kiểm tra hạng...';
    
    // --- DỮ LIỆU GIẢ ĐỊNH TỪ SERVER (THAY THẾ BẰNG AJAX THỰC TẾ) ---
    let mockData = {
        rank: 'Mới', rankClass: 'rank-moi', orders: 0, spent: 0, discount: 0
    };

    if (phoneNumber.startsWith('090')) { 
        mockData = {
            rank: 'Vàng', rankClass: 'rank-vang', orders: 8, spent: 6200000, discount: 0.05 
        };
    } else if (phoneNumber.startsWith('098')) { 
        mockData = {
            rank: 'Kim cương', rankClass: 'rank-kim-cuong', orders: 20, spent: 12500000, discount: 0.10 
        };
    }
    
    // Cập nhật giao diện sau khi có kết quả
    setTimeout(() => {
        membershipStatusEl.className = `membership-status ${mockData.rankClass}`;
        membershipStatusEl.innerHTML = `Khách hàng ${phoneNumber.slice(0, 4)}xxx${phoneNumber.slice(-3)} hiện đang ở hạng <strong>${mockData.rank}</strong> (${mockData.orders} đơn, ${number_format(mockData.spent)}đ).`;
        
        // Cập nhật tóm tắt đơn hàng với chiết khấu
        calculateAndRenderSummary(readCart(), mockData.discount, mockData.rank);

    }, 500); 
}

// Cập nhật lại hàm calculateAndRenderSummary để tính thêm giảm giá
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
    
    reviewEl.innerHTML = '';
    
    if (cart.length === 0) {
        reviewEl.innerHTML = '<p class="empty-message">Giỏ hàng trống. Vui lòng quay lại Giỏ hàng để thêm sản phẩm.</p>';
        document.getElementById('btn-submit-order').disabled = true;
    } else {
        // Lặp qua giỏ hàng để tính toán và hiển thị Review
        cart.forEach(item => {
            const itemTotal = item.price * item.quantity;
            subtotal += itemTotal;
            totalQuantity += item.quantity;

            const itemDiv = document.createElement('div');
            itemDiv.className = 'review-item';
            // Cần lấy ghi chú nếu có
            const noteText = item.note ? ` (${item.note})` : '';
            itemDiv.innerHTML = `
                <span class="item-name">${item.name} ${noteText}</span>
                <span class="item-qty-price">${number_format(itemTotal)} VNĐ x ${item.quantity}</span>
            `;
            reviewEl.appendChild(itemDiv);
        });
    }

    const VAT_RATE = 0.08;
    const vat = subtotal * VAT_RATE;
    const totalBeforeDiscount = subtotal + vat;
    
    // Tính giảm giá
    const discountAmount = subtotal * discountRate;
    const grandTotal = totalBeforeDiscount - discountAmount;

    // Cập nhật Summary
    countEl.textContent = totalQuantity;
    subtotalEl.textContent = `${number_format(subtotal)} VNĐ`;
    vatEl.textContent = `${number_format(vat)} VNĐ`;
    
    // Hiển thị Giảm giá
    rankDisplaySummaryEl.textContent = rankName;
    if (discountRate > 0) {
        discountLineEl.style.display = 'flex';
        discountEl.textContent = `- ${number_format(discountAmount)} VNĐ (${discountRate * 100}%)`;
    } else {
        discountLineEl.style.display = 'none';
    }

    totalEl.textContent = `${number_format(grandTotal)} VNĐ`;
    
    // Luôn gọi checkFormValidity sau khi cập nhật giỏ hàng
    checkFormValidity();
}


// Khai báo ngoài scope để hàm calculateAndRenderSummary có thể gọi
function checkFormValidity() {
    const tableInput = document.getElementById('table_number');
    const phoneInput = document.getElementById('phone_number');
    const submitButton = document.getElementById('btn-submit-order');
    
    const isCartEmpty = readCart().length === 0;
    const isTableInputFilled = tableInput && tableInput.value.trim() !== '';
    const isPhoneInputValid = phoneInput && phoneInput.value.trim().length >= 9;

    // Kích hoạt nút chỉ khi: Giỏ hàng có sản phẩm VÀ Số bàn VÀ SĐT đã điền
    if (isTableInputFilled && isPhoneInputValid && !isCartEmpty) {
        submitButton.disabled = false;
    } else {
        submitButton.disabled = true;
    }
}


document.addEventListener('DOMContentLoaded', () => {
    // Load lần đầu tiên
    calculateAndRenderSummary(readCart()); 

    const tableInput = document.getElementById('table_number');
    const phoneInput = document.getElementById('phone_number');
    const transferDetailsBox = document.getElementById('transfer-details');
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    
    // Lắng nghe sự kiện input
    if (tableInput) {
        tableInput.addEventListener('input', checkFormValidity);
    }
    
    if (phoneInput) {
        let debounceTimer;
        phoneInput.addEventListener('input', () => {
            checkFormValidity();
            clearTimeout(debounceTimer);
            // Chờ 500ms sau khi dừng gõ mới kiểm tra hạng
            debounceTimer = setTimeout(() => {
                checkMembershipRank(phoneInput.value.trim());
            }, 500);
        });
    }
    
    // Logic hiển thị chi tiết Chuyển khoản và Upload Biên lai
    paymentMethods.forEach(radio => {
        radio.addEventListener('change', (e) => {
            // Cập nhật class 'selected' cho lựa chọn hiện tại
            document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected'));
            e.target.closest('.payment-option').classList.add('selected');

            // Hiển thị chi tiết chuyển khoản
            if (e.target.value === 'transfer') {
                if (transferDetailsBox) {
                    transferDetailsBox.style.display = 'block';
                }
            } else {
                if (transferDetailsBox) {
                    transferDetailsBox.style.display = 'none';
                }
            }
        });
    });

    // Kiểm tra lần đầu khi tải trang
    checkFormValidity();
    if (phoneInput && phoneInput.value.trim().length > 0) {
        checkMembershipRank(phoneInput.value.trim());
    }
});