// products.js
document.addEventListener('DOMContentLoaded', () => {
    // ====== Cấu hình ======
    // Fix: hardcode đường dẫn đúng cho localhost
    const PRODUCTS_API = '/Webmarket/controller/Products_Filler_Controller.php';

    console.log('API URL:', PRODUCTS_API); // DEBUG để kiểm tra

    // ====== DOM refs (giữ nguyên HTML cũ) ======
    const productGrid = document.getElementById('product-grid');
    const sortBySelect = document.getElementById('sort-by');
    const categoryLinks = document.querySelectorAll('.category-link, .dropdown-menu-item a');
    const noProductsMessage = document.getElementById('no-products-message');
    const breadcrumbCategory = document.getElementById('breadcrumb-category');

    // Nếu HTML đã có .main-content thì giữ behavior cũ, không sửa file HTML
    const paginationDiv = document.createElement('div');
    paginationDiv.className = 'pagination';
    const mainContent = document.querySelector('.main-content');
    if (mainContent) mainContent.appendChild(paginationDiv);

    // ====== State ======
    const urlParams = new URLSearchParams(window.location.search);
    const initialCategory = urlParams.get('category') || 'All';
    let currentFilter = initialCategory;
    let currentSort = 'default';
    let currentPage = 1;
    const itemsPerPage = 12; // ĐỔI TỪ 50 THÀNH 12
    let productList = [];

    // ====== Utils (chuẩn hoá tiếng Việt / so sánh không dấu) ======
    const toAscii = (s = '') =>
        s.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

    const isAllFilter = (label = '') => {
        const t = toAscii(label).trim();
        return !t || t === 'all' || t.includes('tat ca');
    };

    const isPromoFilter = (label = '') => {
        const t = toAscii(label).trim();
        return t.includes('khuyen mai');
    };

    const eqLabel = (a, b) => toAscii(a) === toAscii(b);

    // ====== Chuẩn hoá item từ API ======
    function normalizeImage(src) {
        if (!src) return '/Webmarket/image/sp1.webp';
        return src.startsWith('/') ? src : '/' + src.replace(/^\/+/, '');
    }
    function inferMainCategory(sub, name) {
        const s = `${sub || ''} ${name || ''}`.toLowerCase();
        if (s.includes('combo')) return 'Combo';
        if (s.includes('bánh') || s.includes('banh')) return 'Bánh';
        if (s.includes('trà') || s.includes('tra') || s.includes('oolong') || s.includes('phổ nh') || s.includes('pho nhi')) return 'Trà';
        return 'All';
    }
    function inferSubCategory(name) {
        const x = (name || '').toLowerCase();
        if (x.includes('lục trà') || x.includes('luc tra') || x.includes('trà xanh')) return 'Lục Trà';
        if (x.includes('hồng trà') || x.includes('hong tra')) return 'Hồng Trà';
        if (x.includes('bạch trà') || x.includes('bach tra')) return 'Bạch Trà';
        if (x.includes('oolong') || x.includes('ô long'))     return 'Oolong Trà';
        if (x.includes('phổ nh') || x.includes('pho nhi'))    return 'Phổ Nhĩ';
        if (x.includes('bánh') || x.includes('banh'))         return 'Bánh';
        if (x.includes('combo'))                              return 'Combo';
        return '';
    }
    function normalizeProduct(it) {
        const sub = it.subCategory || it.categoryName || inferSubCategory(it.name);
        const cat = it.category || inferMainCategory(sub, it.name);
        return {
            id: it.id,
            name: it.name,
            price: Number(it.price || 0),
            oldPrice: it.oldPrice != null ? Number(it.oldPrice) : null,
            image: normalizeImage(it.image),
            subCategory: sub,
            category: cat,
            isPromo: !!(it.isPromo || (it.oldPrice && it.oldPrice > 0)),
            newProduct: Number(it.newProduct || it.createdDate || 0), // support both numeric or datetime->timestamp (BE đã convert)
            popularity: Number(it.popularity || 0),
        };
    }

    // ====== Fetch dữ liệu ======
    fetch(PRODUCTS_API)
        .then(res => {
            if (!res.ok) throw new Error('Lỗi mạng: ' + res.status);
            return res.json();
        })
        .then(data => {
            // Chịu được cả mảng thuần & { ok, items: [...] }
            const items = Array.isArray(data) ? data : (data && Array.isArray(data.items) ? data.items : []);
            productList = items.map(normalizeProduct);

            updatePromoBadge();
            // Mặc định hiển thị TẤT CẢ khi chưa chọn
            filterAndRenderProducts(currentFilter);
        })
        .catch(err => {
            console.error('Lỗi fetch sản phẩm:', err);
            if (noProductsMessage) noProductsMessage.style.display = 'block';
        });

    // ====== Event: click danh mục (giữ nguyên HTML) ======
    categoryLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const filter = (link.getAttribute('data-filter') || 'All').normalize('NFC');
            currentPage = 1;
            filterAndRenderProducts(filter);
        });
    });

    // ====== Event: sắp xếp ======
    if (sortBySelect) {
        sortBySelect.addEventListener('change', (e) => {
            currentSort = e.target.value;
            currentPage = 1;
            filterAndRenderProducts(currentFilter);
        });
    }

    // ====== Helpers UI ======
    function calculateDiscount(oldPrice, newPrice) {
        if (!oldPrice || oldPrice <= newPrice) return null;
        const discount = ((oldPrice - newPrice) / oldPrice * 100).toFixed(0);
        return `-${discount}%`;
    }

    // Badge "Khuyến mãi/Khuyến Mãi" - BỎ badge giảm %
    function updatePromoBadge() {
        const promoLink =
            document.querySelector('.category-link[data-filter="Khuyến Mãi"]') ||
            document.querySelector('.category-link[data-filter="Khuyến mãi"]');
        if (!promoLink) return;

        // Xóa badge cũ nếu có
        const existingBadge = promoLink.querySelector('.category-discount-badge');
        if (existingBadge) existingBadge.remove();

        // KHÔNG TẠO BADGE MỚI - chỉ để text "Khuyến Mãi"
    }

    function renderProductCard(product) {
        const formattedPrice = product.price.toLocaleString('vi-VN') + ' VNĐ';
        const formattedOldPrice = product.oldPrice ? product.oldPrice.toLocaleString('vi-VN') + ' VNĐ' : '';
        // BỎ dòng này nếu không muốn badge % trên card
        // const discount = calculateDiscount(product.oldPrice, product.price);

        return `
      <div class="product-card" data-category="${product.category}" data-sub-category="${product.subCategory}">
        ${/* Bỏ badge giảm % */ ''}
        <img src="${product.image}" alt="${product.name}" onerror="this.src='/Webmarket/image/sp1.webp';">
        <h3>${product.name}</h3>
        <div class="product-info">
          ${product.oldPrice ? `<p class="old-price">${formattedOldPrice}</p>` : ''}
          <p class="price ${product.oldPrice ? 'promo-price' : ''}">${formattedPrice}</p>
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
    }

    function sortProducts(products, sortKey) {
        const arr = [...products];
        switch (sortKey) {
            case 'price_asc':      return arr.sort((a, b) => a.price - b.price);
            case 'price_desc':     return arr.sort((a, b) => b.price - a.price);
            case 'newest':         return arr.sort((a, b) => (b.newProduct - a.newProduct) || (b.id - a.id));
            case 'popularity_desc':return arr.sort((a, b) => b.popularity - a.popularity);
            default:               return arr; // 'default' giữ nguyên
        }
    }

    function paginateProducts(products, page) {
        const start = (page - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        return products.slice(start, end);
    }

    function renderPagination(totalItems) {
        if (!paginationDiv) return;
        const totalPages = Math.ceil(totalItems / itemsPerPage);
        paginationDiv.innerHTML = '';
        for (let i = 1; i <= totalPages; i++) {
            const button = document.createElement('button');
            button.textContent = i;
            button.className = i === currentPage ? 'active' : '';
            button.addEventListener('click', () => {
                currentPage = i;
                filterAndRenderProducts(currentFilter);
            });
            paginationDiv.appendChild(button);
        }
        paginationDiv.style.display = totalPages > 1 ? 'flex' : 'none';
    }

    function filterAndRenderProducts(filterLabel) {
        currentFilter = (filterLabel || 'All').normalize('NFC');

        // 1) Mặc định: chưa chọn gì => All => hiện tất cả
        let filteredProducts = productList;

        // 2) Khuyến mãi (chấp nhận cả "Khuyến Mãi" & "Khuyến mãi")
        if (isPromoFilter(currentFilter)) {
            filteredProducts = productList.filter(p => p.isPromo && p.oldPrice);
        }
        // 3) Lọc theo danh mục/sub danh mục
        else if (!isAllFilter(currentFilter)) {
            filteredProducts = productList.filter(p =>
                eqLabel(p.subCategory, currentFilter) ||
                eqLabel(p.category, currentFilter)
            );
        }

        const sorted = sortProducts(filteredProducts, currentSort);
        const totalItems = sorted.length;
        const paginated = paginateProducts(sorted, currentPage);

        productGrid.innerHTML = '';
        if (totalItems === 0) {
            if (noProductsMessage) noProductsMessage.style.display = 'block';
            if (paginationDiv) paginationDiv.style.display = 'none';
        } else {
            if (noProductsMessage) noProductsMessage.style.display = 'none';
            paginated.forEach(p => productGrid.insertAdjacentHTML('beforeend', renderProductCard(p)));
            renderPagination(totalItems);
        }

        breadcrumbCategory.innerHTML = isAllFilter(currentFilter) ? '' : ` / ${currentFilter}`;
        updateCategoryActiveState(currentFilter);
    }

    function updateCategoryActiveState(filter) {
        document.querySelectorAll('.category-link').forEach(link => link.classList.remove('active'));
        // cố gắng tìm link khớp không dấu
        let activeLink = Array.from(document.querySelectorAll('.category-link'))
            .find(a => eqLabel(a.getAttribute('data-filter') || '', filter));

        if (!activeLink && isAllFilter(filter)) {
            activeLink = document.querySelector('.category-link[data-filter="All"]') ||
                document.querySelector('.category-link[data-filter="Tất Cả Sản Phẩm"]');
        }
        if (activeLink) {
            activeLink.classList.add('active');
            const parentDropdown = activeLink.closest('.dropdown-menu');
            if (parentDropdown) {
                const parentLink = parentDropdown.previousElementSibling;
                if (parentLink) parentLink.classList.add('active');
            }
        }
    }

    // ====== Mini-cart (giữ nguyên hành vi) ======
    function readCart() { return JSON.parse(localStorage.getItem('cart')) || []; }
    function writeCart(cart) { localStorage.setItem('cart', JSON.stringify(cart)); updateCartBadge(); }
    function updateCartBadge() {
        const cart = readCart();
        const count = cart.reduce((s, it) => s + (it.quantity || 1), 0);
        let badge = document.querySelector('.cart-count');
        if (!badge && document.querySelector('.icons .cart-icon')) {
            badge = document.createElement('span');
            badge.className = 'cart-count';
            document.querySelector('.icons .cart-icon').appendChild(badge);
        }
        if (badge) {
            badge.textContent = count > 0 ? count : '';
            badge.style.display = count > 0 ? 'inline-block' : 'none';
        }
    }

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
        if (e.key === 'Escape' && miniCartEl && miniCartEl.classList.contains('show')) closeMiniCart();
    });

    function renderMiniCart() {
        if (!miniCartItemsEl || !miniCountEl || !miniTotalEl) return;
        const cart = readCart();
        miniCartItemsEl.innerHTML = cart.length === 0
            ? `<p style="text-align:center;color:#777;padding:20px">Giỏ hàng trống</p>` : '';
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
        </button>`;
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

    document.body.addEventListener('click', (e) => {
        const addBtn = e.target.closest('.btn-add');
        if (!addBtn) return;
        e.preventDefault();
        const item = {
            id: addBtn.getAttribute('data-id'),
            name: addBtn.getAttribute('data-name'),
            price: parseInt(addBtn.getAttribute('data-price')),
            image: addBtn.getAttribute('data-image'),
            quantity: 1
        };
        const cart = readCart();
        const existing = cart.find(i => i.id === item.id);
        if (existing) existing.quantity += 1; else cart.push(item);
        writeCart(cart);
        renderMiniCart();
        openMiniCart();
    });

    // Dropdown (giữ như cũ)
    document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            const dropdownMenu = toggle.nextElementSibling;
            const arrow = toggle.querySelector('.dropdown-arrow');
            if (dropdownMenu) dropdownMenu.classList.toggle('open');
            if (arrow) arrow.classList.toggle('rotated');
        });
    });

    // Init
    updateCartBadge();
    renderMiniCart();
});