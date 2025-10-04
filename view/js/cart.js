function number_format(number, decimals, dec_point, thousands_sep) {
    number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
    const n = !isFinite(+number) ? 0 : +number;
    const prec = !isFinite(+decimals) ? 0 : Math.abs(decimals);
    const sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep;
    const dec = (typeof dec_point === 'undefined') ? '.' : dec_point;
    let s = '';
    const toFixedFix = function(n, prec) {
        const k = Math.pow(10, prec);
        return Math.round(n * k) / k;
    };
    s = (prec ? toFixedFix(n, prec) : Math.round(n)).toString().split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }
    return s.join(dec);
}

// Đọc/ghi giỏ hàng trong localStorage
function readCart() {
    return JSON.parse(localStorage.getItem('cart')) || [];
}
function writeCart(cart) {
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartBadge();
}

// Cập nhật số hiển thị trên icon
function updateCartBadge() {
    const cart = readCart();
    const count = cart.reduce((s, it) => s + (it.quantity || 1), 0);
    let badge = document.querySelector('.cart-count');
    if (badge) {
        badge.textContent = count > 0 ? count : '';
        badge.style.display = count > 0 ? 'inline-block' : 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const cartItemsContainer = document.getElementById('cart-items');
    const emptyCart = document.getElementById('empty-cart');
    const cartContent = document.getElementById('cart-content');

    function updateCartDisplay() {
        let cart = readCart();
        cartItemsContainer.innerHTML = '';
        const validCart = cart.filter(item =>
            item.id &&
            item.name && item.name.trim() !== '' &&
            item.price !== undefined && item.price !== null && parseFloat(item.price) > 0
        );
        if (validCart.length !== cart.length) {
            console.warn('Đã tìm thấy các mục giỏ hàng bị lỗi (thiếu tên hoặc giá trị không hợp lệ). Tự động xóa các mục này.');
            writeCart(validCart);
            cart = validCart;
        }
        if (cart.length === 0) {
            cartContent.style.display = 'none';
            emptyCart.style.display = 'block';
        } else {
            cartContent.style.display = 'block';
            emptyCart.style.display = 'none';
            cart.forEach(item => {
                const itemPrice = parseFloat(item.price) || 0;
                const itemName = item.name || 'Sản phẩm không tên';
                const itemImage = item.image || 'placeholder.png';
                const row = document.createElement('tr');
                row.setAttribute('data-id', item.id);
                row.innerHTML = `
                    <td>
                        <img src="${itemImage}" alt="${itemName}" onerror="this.onerror=null;this.src='https://placehold.co/60x60/f0f0f0/666?text=No+Img';">
                        <span>${itemName}</span>
                    </td>
                    <td class="item-price" data-price-value="${itemPrice}">${number_format(itemPrice, 0, ',', '.')}VND</td>
                    <td>
                        <div class="quantity">
                            <button class="qty-minus" aria-label="Giảm số lượng">-</button>
                            <input type="number" value="${item.quantity}" min="1" class="qty-input">
                            <button class="qty-plus" aria-label="Tăng số lượng">+</button>
                        </div>
                    </td>
                    <td><input type="text" class="note-input" placeholder="Nhập ghi chú" value="${item.note || ''}"></td>
                    <td class="item-total">${number_format(itemPrice * item.quantity, 0, ',', '.')}VND</td>
                    <td><i class="fa-solid fa-trash remove-item" aria-label="Xóa sản phẩm"></i></td>
                `;
                cartItemsContainer.appendChild(row);
            });
            updateSummary();
            attachEventListeners();
        }
    }

    function updateSummary() {
        let cart = readCart();
        let subtotal = cart.reduce((sum, item) => sum + ((parseFloat(item.price) || 0) * item.quantity), 0);
        const vatRate = 0.08;
        const vat = subtotal * vatRate;
        const grandTotal = subtotal + vat;
        document.getElementById('subtotal').textContent = number_format(subtotal, 0, ',', '.') + 'VND';
        document.getElementById('vat').textContent = number_format(vat, 0, ',', '.') + 'VND';
        document.getElementById('grand-total').textContent = number_format(grandTotal, 0, ',', '.') + 'VND';
    }

    window.addEventListener('message', function(e) {
        if (e.origin !== window.location.origin) return;
        if (e.data && e.data.id && e.data.name && e.data.price) {
            const itemData = {
                ...e.data,
                price: parseFloat(e.data.price) || 0,
                quantity: parseInt(e.data.quantity) || 1,
                note: e.data.note || ''
            };
            if (itemData.price > 0 && itemData.name.trim() !== '') {
                addToCart(itemData);
            } else {
                console.error("Không thể thêm sản phẩm vào giỏ: Giá trị không hợp lệ.");
            }
        }
    });

    function addToCart(item) {
        let cart = readCart();
        const existingItem = cart.find(i => i.id == item.id);
        if (existingItem) {
            existingItem.quantity += item.quantity;
            existingItem.note = item.note || existingItem.note || '';
        } else {
            cart.push({
                id: item.id,
                name: item.name,
                image: item.image,
                price: parseFloat(item.price) || 0,
                quantity: item.quantity,
                note: item.note || ''
            });
        }
        writeCart(cart);
        updateCartDisplay();
    }

    function attachEventListeners() {
        document.querySelectorAll('.qty-minus, .qty-plus, .qty-input, .note-input, .remove-item').forEach(element => {
            element.removeEventListener('click', handleAction);
            element.removeEventListener('change', handleAction);
        });
        document.querySelectorAll('.qty-minus, .qty-plus, .remove-item').forEach(button => {
            button.addEventListener('click', handleAction);
        });
        document.querySelectorAll('.qty-input, .note-input').forEach(input => {
            input.addEventListener('change', handleAction);
        });
    }

    function handleAction(e) {
        const row = e.target.closest('tr');
        if (!row) return;
        const itemId = row.getAttribute('data-id');
        if (e.target.classList.contains('remove-item')) {
            removeItem(row);
            return;
        }
        const qtyInput = row.querySelector('.qty-input');
        const noteInput = row.querySelector('.note-input');
        const currentQty = parseInt(qtyInput.value);
        let newQty = currentQty;
        let newNote = noteInput.value;
        if (e.target.classList.contains('qty-minus')) {
            newQty = Math.max(1, currentQty - 1);
            if (currentQty === 1) {
                removeItem(row);
                return;
            }
        } else if (e.target.classList.contains('qty-plus')) {
            newQty = currentQty + 1;
        } else if (e.target.classList.contains('qty-input')) {
            newQty = parseInt(e.target.value) || 1;
        }
        if (newQty < 1) {
            removeItem(row);
            return;
        }
        qtyInput.value = newQty;
        updateCartItemInStorage(itemId, newQty, newNote);
    }

    function removeItem(row) {
        const itemId = row.getAttribute('data-id');
        let cart = readCart();
        cart = cart.filter(i => i.id !== itemId);
        writeCart(cart);
        updateCartDisplay();
    }

    function updateCartItemInStorage(itemId, qty, note) {
        let cart = readCart();
        const item = cart.find(i => i.id === itemId);
        if (item) {
            item.quantity = qty;
            item.note = note;
            writeCart(cart);
            const row = document.querySelector(`tr[data-id="${itemId}"]`);
            if (row) {
                const price = parseFloat(item.price) || 0;
                const totalCell = row.querySelector('.item-total');
                totalCell.textContent = number_format(price * qty, 0, ',', '.') + 'VND';
            }
            updateSummary();
        }
    }

    updateCartDisplay();
    updateCartBadge();
});