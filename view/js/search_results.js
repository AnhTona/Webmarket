// ============================================
// SEARCH RESULTS PAGE SCRIPT
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    const SEARCH_API = '/Webmarket/controller/Search_Controller.php';
    const productGrid = document.getElementById('product-grid');
    const noProductsMessage = document.getElementById('no-products-message');

    // Lấy keyword từ URL
    const urlParams = new URLSearchParams(window.location.search);
    const keyword = urlParams.get('keyword') || '';

    // ===== FETCH & DISPLAY PRODUCTS =====
    if (keyword) {
        fetchSearchResults(keyword);
    } else {
        noProductsMessage.style.display = 'block';
    }

    function fetchSearchResults(searchKeyword) {
        fetch(`${SEARCH_API}?action=search&keyword=${encodeURIComponent(searchKeyword)}`)
            .then(res => {
                if (!res.ok) throw new Error('Lỗi mạng: ' + res.status);
                return res.json();
            })
            .then(products => {
                displayProducts(products);
            })
            .catch(err => {
                console.error('Lỗi fetch kết quả tìm kiếm:', err);
                noProductsMessage.style.display = 'block';
            });
    }

    function displayProducts(products) {
        productGrid.innerHTML = '';

        if (products.length === 0) {
            noProductsMessage.style.display = 'block';
            return;
        }

        noProductsMessage.style.display = 'none';

        products.forEach(product => {
            const discount = product.oldPrice ? calculateDiscount(product.oldPrice, product.price) : null;

            productGrid.innerHTML += `
                <div class="product-card">
                    ${discount ? `<div class="discount-badge">${discount}</div>` : ''}
                    <img src="${product.image}" alt="${product.name}" onerror="this.src='/image/sp1.webp';">
                    <h3>${product.name}</h3>
                    <div class="product-info">
                        ${product.oldPrice ? `<p class="old-price">${product.oldPrice.toLocaleString('vi-VN')} VNĐ</p>` : ''}
                        <p class="price ${product.oldPrice ? 'promo-price' : ''}">${product.price.toLocaleString('vi-VN')} VNĐ</p>
                        <a href="#" class="btn-add" 
                           data-id="${product.id}"
                           data-name="${product.name}"
                           data-price="${product.price}"
                           data-image="${product.image}">
                           Thêm vào giỏ hàng <i class="fa-solid fa-basket-shopping"></i>
                        </a>
                    </div>
                </div>
            `;
        });
    }

    function calculateDiscount(oldPrice, newPrice) {
        if (!oldPrice || oldPrice <= newPrice) return null;
        return `-${((1 - newPrice / oldPrice) * 100).toFixed(0)}%`;
    }

    // ===== CART FUNCTIONS =====
    function readCart() {
        return JSON.parse(localStorage.getItem('cart')) || [];
    }

    function writeCart(cart) {
        localStorage.setItem('cart', JSON.stringify(cart));
        updateCartBadge();
    }

    function updateCartBadge() {
        const cart = readCart();
        const count = cart.reduce((sum, item) => sum + (item.quantity || 1), 0);
        const badge = document.querySelector('.cart-count');

        if (badge) {
            badge.textContent = count > 0 ? count : '';
            badge.style.display = count > 0 ? 'inline-block' : 'none';
        }
    }

    // ===== MINI-CART =====
    const overlayEl = document.getElementById('overlay');
    const miniCartEl = document.getElementById('mini-cart');
    const miniCartItemsEl = document.getElementById('minicart-items-list');
    const miniCountEl = document.getElementById('minicart-item-count');
    const miniTotalEl = document.getElementById('minicart-total-price');
    const closeBtn = document.querySelector('#mini-cart .close-btn');

    function openMiniCart() {
        if (!overlayEl || !miniCartEl) return;
        overlayEl.classList.add('show');
        miniCartEl.style.display = 'flex';
        setTimeout(() => miniCartEl.classList.add('show'), 10);
        document.body.classList.add('no-scroll');
    }

    function closeMiniCart() {
        if (!overlayEl || !miniCartEl) return;
        miniCartEl.classList.remove('show');
        overlayEl.classList.remove('show');
        document.body.classList.remove('no-scroll');
        setTimeout(() => miniCartEl.style.display = 'none', 350);
    }

    if (overlayEl) overlayEl.addEventListener('click', closeMiniCart);
    if (closeBtn) closeBtn.addEventListener('click', closeMiniCart);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && miniCartEl && miniCartEl.classList.contains('show')) {
            closeMiniCart();
        }
    });

    function renderMiniCart() {
        if (!miniCartItemsEl || !miniCountEl || !miniTotalEl) return;

        const cart = readCart();
        miniCartItemsEl.innerHTML = cart.length === 0
            ? '<p style="text-align:center;color:#777;padding:20px">Giỏ hàng trống</p>'
            : '';

        let total = 0;
        cart.forEach(item => {
            total += item.price * item.quantity;
            const row = document.createElement('div');
            row.className = 'minicart-item';
            row.innerHTML = `
                <img src="${item.image}" alt="${item.name}" class="minicart-item-image">
                <div class="minicart-item-details">
                    <span class="minicart-item-name">${item.name}</span>
                    <span class="minicart-item-price">${(item.price * item.quantity).toLocaleString('vi-VN')} VNĐ x ${item.quantity}</span>
                </div>
                <button class="minicart-item-remove" data-id="${item.id}">
                    <i class="fa-solid fa-trash"></i>
                </button>
            `;
            miniCartItemsEl.appendChild(row);
        });

        miniCountEl.textContent = `${cart.length} sản phẩm`;
        miniTotalEl.textContent = `${total.toLocaleString('vi-VN')} VND`;
    }

    if (miniCartItemsEl) {
        miniCartItemsEl.addEventListener('click', (e) => {
            const btn = e.target.closest('.minicart-item-remove');
            if (!btn) return;

            const id = btn.getAttribute('data-id');
            const cart = readCart().filter(item => String(item.id) !== String(id));
            writeCart(cart);
            renderMiniCart();
        });
    }

    // ===== ADD TO CART EVENT =====
    document.body.addEventListener('click', (e) => {
        const addBtn = e.target.closest('.btn-add');
        if (!addBtn) return;

        e.preventDefault();

        const item = {
            id: addBtn.getAttribute('data-id'),
            name: addBtn.getAttribute('data-name'),
            price: parseFloat(addBtn.getAttribute('data-price')),
            image: addBtn.getAttribute('data-image'),
            quantity: 1
        };

        const cart = readCart();
        const existing = cart.find(i => i.id === item.id);

        if (existing) {
            existing.quantity += 1;
        } else {
            cart.push(item);
        }

        writeCart(cart);
        renderMiniCart();
        openMiniCart();
    });

    // ===== INIT =====
    updateCartBadge();
    renderMiniCart();
});