document.addEventListener('DOMContentLoaded', () => {
    // API endpoint
    const PRODUCTS_API = '/Webmarket/controller/Products_Filler_Controller.php';
    const MAX_FEATURED = 7;   // ĐỔI: Giới hạn 7 sản phẩm nổi bật
    const MAX_PROMO = 12;     // Tối đa 12 sản phẩm khuyến mãi

    // DOM Elements
    const featuredGrid = document.querySelector('.featured .product-grid');
    const promoGrid = document.querySelector('.promo-section .product-slider');
    const sliderWrapper = document.querySelector('.product-slider-wrapper');
    const prevBtnPromo = document.querySelector('.prev-promo');
    const nextBtnPromo = document.querySelector('.next-promo');

    // ===== WHY CHOOSE SLIDER SCRIPT =====
    const numberSpans = document.querySelectorAll('.why-numbers span');
    const contentBoxes = document.querySelectorAll('.text-box');

    numberSpans.forEach(span => {
        span.addEventListener('mouseover', () => {
            numberSpans.forEach(s => s.classList.remove('active'));
            contentBoxes.forEach(box => box.classList.remove('active'));

            span.classList.add('active');
            const targetId = span.getAttribute('data-target');
            document.getElementById(targetId).classList.add('active');
        });
    });

    // ===== HERO SLIDER SCRIPT =====
    const slides = document.querySelectorAll('.slide');
    const prev = document.querySelector('.prev');
    const next = document.querySelector('.next');
    let index = 0;

    function showSlide(i) {
        slides.forEach((slide, idx) => {
            slide.classList.remove('active');
            if (idx === i) slide.classList.add('active');
        });
    }

    if (prev && next) {
        prev.addEventListener('click', () => {
            index = (index - 1 + slides.length) % slides.length;
            showSlide(index);
        });

        next.addEventListener('click', () => {
            index = (index + 1) % slides.length;
            showSlide(index);
        });

        // Tự động chuyển slide mỗi 5 giây
        setInterval(() => {
            index = (index + 1) % slides.length;
            showSlide(index);
        }, 5000);
    }

    // ===== PROMO CAROUSEL SCRIPT =====
    if (sliderWrapper && prevBtnPromo && nextBtnPromo) {
        const scrollDistance = (280 + 30) * 3; // Cuộn 3 card (280px width + 30px gap)

        nextBtnPromo.addEventListener('click', () => {
            sliderWrapper.scrollBy({
                left: scrollDistance,
                behavior: 'smooth'
            });
        });

        prevBtnPromo.addEventListener('click', () => {
            sliderWrapper.scrollBy({
                left: -scrollDistance,
                behavior: 'smooth'
            });
        });

        // Ẩn/hiện nút nếu cuộn đến đầu hoặc cuối
        sliderWrapper.addEventListener('scroll', () => {
            if (sliderWrapper.scrollLeft <= 5) {
                prevBtnPromo.style.opacity = '0.5';
                prevBtnPromo.style.pointerEvents = 'none';
            } else {
                prevBtnPromo.style.opacity = '1';
                prevBtnPromo.style.pointerEvents = 'auto';
            }

            if (sliderWrapper.scrollLeft + sliderWrapper.clientWidth >= sliderWrapper.scrollWidth - 5) {
                nextBtnPromo.style.opacity = '0.5';
                nextBtnPromo.style.pointerEvents = 'none';
            } else {
                nextBtnPromo.style.opacity = '1';
                nextBtnPromo.style.pointerEvents = 'auto';
            }
        });

        prevBtnPromo.style.opacity = '0.5';
        prevBtnPromo.style.pointerEvents = 'none';
    }

    // ===== UTILS =====
    function normalizeImage(src) {
        if (!src) return 'image/sp1.webp';
        
        // GIỮ NGUYÊN LOGIC CỦA products.js - THÊM / ở đầu nếu chưa có
        if (!src.startsWith('/')) {
            src = '/' + src.replace(/^\/+/, '');
        }
        
        return src;
    }

    function calculateDiscount(oldPrice, newPrice) {
        if (!oldPrice || oldPrice <= newPrice) return null;
        const discount = ((oldPrice - newPrice) / oldPrice * 100).toFixed(0);
        return `-${discount}%`;
    }

    // ===== RENDER PRODUCT CARD =====
    function renderFeaturedCard(product) {
        const formattedPrice = product.price.toLocaleString('vi-VN') + ' VNĐ';
        const imgSrc = normalizeImage(product.image); // Lấy ảnh từ DB

        return `
            <div class="product-card">
                <img src="${imgSrc}" alt="${product.name}" onerror="this.src='image/sp1.webp';">
                <h3>${product.name}</h3>
                <p class="price">${formattedPrice}</p>
                <a href="#" class="btn-add" 
                   data-id="${product.id}"
                   data-name="${product.name}"
                   data-price="${product.price}"
                   data-image="${imgSrc}">
                   Thêm vào giỏ hàng <i class="fa-solid fa-basket-shopping"></i>
                </a>
            </div>
        `;
    }

    function renderPromoCard(product) {
        const formattedPrice = product.price.toLocaleString('vi-VN') + ' VNĐ';
        const formattedOldPrice = product.oldPrice ? product.oldPrice.toLocaleString('vi-VN') + ' VNĐ' : '';
        const discount = calculateDiscount(product.oldPrice, product.price);
        const imgSrc = normalizeImage(product.image); // Lấy ảnh từ DB

        return `
            <div class="product-card promo-card">
                ${discount ? `<div class="discount-badge">${discount}</div>` : ''}
                <img src="${imgSrc}" alt="${product.name}" onerror="this.src='image/sp1.webp';">
                <h3>${product.name}</h3>
                <div class="product-info">
                    ${product.oldPrice ? `<p class="old-price">${formattedOldPrice}</p>` : ''}
                    <p class="price promo-price">${formattedPrice}</p>
                    <a href="#" class="btn-add" 
                       data-id="${product.id}"
                       data-name="${product.name}"
                       data-price="${product.price}"
                       data-image="${imgSrc}">
                       Thêm vào giỏ hàng <i class="fa-solid fa-basket-shopping"></i>
                    </a>
                </div>
            </div>
        `;
    }

    // ===== FETCH & RENDER PRODUCTS =====
    function loadProducts() {
        fetch(PRODUCTS_API)
            .then(res => {
                if (!res.ok) throw new Error('Lỗi mạng: ' + res.status);
                return res.json();
            })
            .then(data => {
                const products = Array.isArray(data) ? data : (data.items || []);

                // Lọc sản phẩm nổi bật (7 sản phẩm mới nhất, không phải khuyến mãi)
                const featured = products
                    .filter(p => !p.isPromo)
                    .sort((a, b) => (b.newProduct || 0) - (a.newProduct || 0))
                    .slice(0, MAX_FEATURED); // Giới hạn 7 sản phẩm

                // Lọc sản phẩm khuyến mãi (12 sản phẩm)
                const promo = products
                    .filter(p => p.isPromo && p.oldPrice > 0)
                    .slice(0, MAX_PROMO);

                // Render
                if (featuredGrid && featured.length > 0) {
                    featuredGrid.innerHTML = featured.map(renderFeaturedCard).join('');
                }

                if (promoGrid && promo.length > 0) {
                    promoGrid.innerHTML = promo.map(renderPromoCard).join('');
                }

                // Setup cart buttons
                setupCartButtons();
            })
            .catch(err => {
                console.error('Lỗi fetch sản phẩm:', err);
            });
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

    // ===== MINI-CART (Giống products.php) =====
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
            ? `<p style="text-align:center;color:#777;padding:20px">Giỏ hàng trống</p>`
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
                    <i class="fa-solid fa-trash remove-icon"></i>
                </button>
            `;
            miniCartItemsEl.appendChild(row);
        });

        miniCountEl.textContent = `${cart.length} Sản phẩm`;
        miniTotalEl.textContent = `${total.toLocaleString('vi-VN')} VND`;
    }

    if (miniCartItemsEl) {
        miniCartItemsEl.addEventListener('click', (e) => {
            const btn = e.target.closest('.minicart-item-remove');
            if (!btn) return;

            const id = btn.getAttribute('data-id');
            const cart = readCart().filter(it => String(it.id) !== String(id));
            writeCart(cart);
            renderMiniCart();
        });
    }

    function setupCartButtons() {
        document.querySelectorAll('.btn-add').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();

                const item = {
                    id: this.getAttribute('data-id'),
                    name: this.getAttribute('data-name'),
                    price: parseFloat(this.getAttribute('data-price')),
                    image: this.getAttribute('data-image'),
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
                openMiniCart(); // Mở popup giỏ hàng mini
            });
        });
    }

    // ===== INITIALIZE =====
    loadProducts();
    updateCartBadge();
    renderMiniCart();
});