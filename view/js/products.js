document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const initialCategory = urlParams.get('category') || 'All';
    let currentFilter = initialCategory;
    let currentPage = 1;
    let productList = [];

    // Gọi API PHP để lấy dữ liệu sản phẩm từ database
    fetch('../../view/html/get_products.php')
        .then(res => res.json())
        .then(data => {
            console.log("Products:", data);
            if (Array.isArray(data) && data.length > 0) {
                productList = data;
                updatePromoBadge(); // Cập nhật badge khuyến mãi khi load trang
                filterAndRenderProducts(currentFilter);
            }
        })
        .catch(err => {
            console.error("Lỗi fetch sản phẩm:", err);
            filterAndRenderProducts(currentFilter);
        });

    const productGrid = document.getElementById('product-grid');
    const sortBySelect = document.getElementById('sort-by');
    const categoryLinks = document.querySelectorAll('.category-link');
    const noProductsMessage = document.getElementById('no-products-message');
    const breadcrumbCategory = document.getElementById('breadcrumb-category');
    const paginationDiv = document.createElement('div');
    paginationDiv.className = 'pagination';
    document.querySelector('.main-content').appendChild(paginationDiv);

    let currentSort = 'default';
    const itemsPerPage = 12;

    // Xử lý click vào danh mục
    document.querySelectorAll('.category-link, .dropdown-menu-item a').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const filter = link.getAttribute('data-filter');
            if (filter) {
                currentPage = 1;
                filterAndRenderProducts(filter);
            }
        });
    });

    // Xử lý sắp xếp
    sortBySelect.addEventListener('change', (e) => {
        currentSort = e.target.value;
        currentPage = 1;
        filterAndRenderProducts(currentFilter);
    });

    // Hàm hỗ trợ
    function calculateDiscount(oldPrice, newPrice) {
        if (!oldPrice || oldPrice <= newPrice) return null;
        const discount = ((oldPrice - newPrice) / oldPrice * 100).toFixed(0);
        return `-${discount}%`;
    }

    // Cập nhật badge khuyến mãi cho category "Khuyến Mãi"
    function updatePromoBadge() {
        const promoLink = document.querySelector('.category-link[data-filter="Khuyến Mãi"]');
        if (!promoLink) return;

        // Lấy các sản phẩm có khuyến mãi
        const promoProducts = productList.filter(p => p.isPromo);
        if (promoProducts.length === 0) {
            const existingBadge = promoLink.querySelector('.category-discount-badge');
            if (existingBadge) existingBadge.remove();
            return;
        }

        // Tính mức giảm giá cao nhất
        const maxDiscount = promoProducts.reduce((max, p) => {
            const discount = calculateDiscount(p.oldPrice, p.price);
            if (discount) {
                const percent = parseInt(discount.replace('-', ''));
                return Math.max(max, percent);
            }
            return max;
        }, 0);

        // Xóa badge cũ nếu có
        const existingBadge = promoLink.querySelector('.category-discount-badge');
        if (existingBadge) existingBadge.remove();

        // Thêm badge mới
        if (maxDiscount > 0) {
            const badge = document.createElement('span');
            badge.className = 'category-discount-badge';
            badge.textContent = `-${maxDiscount}%`;
            promoLink.appendChild(badge);
        }
    }

    function renderProductCard(product) {
        const formattedPrice = product.price.toLocaleString('vi-VN') + ' VNĐ';
        const formattedOldPrice = product.oldPrice ? product.oldPrice.toLocaleString('vi-VN') + ' VNĐ' : '';
        const discount = calculateDiscount(product.oldPrice, product.price);
        return `
            <div class="product-card" data-category="${product.category}" data-sub-category="${product.subCategory}">
                ${discount ? `<div class="discount-badge">${discount}</div>` : ''}
                <img src="${product.image}" alt="${product.name}" onerror="this.src='image/sp1.jpg';">
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
        return [...products].sort((a, b) => {
            switch (sortKey) {
                case 'price_asc': return a.price - b.price;
                case 'price_desc': return b.price - a.price;
                case 'newest': return b.newProduct - a.newProduct || b.id - a.id;
                case 'popularity_desc': return b.popularity - a.popularity;
                default: return 0;
            }
        });
    }

    function paginateProducts(products, page) {
        const start = (page - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        return products.slice(start, end);
    }

    function renderPagination(totalItems) {
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

    function filterAndRenderProducts(filter) {
        currentFilter = filter;
        let filteredProducts = productList;
        if (filter === 'Khuyến Mãi') {
            filteredProducts = productList.filter(p => p.isPromo);
        } else if (filter !== 'All') {
            filteredProducts = productList.filter(p =>
                p.subCategory === filter ||
                (p.category === filter && !['Lục Trà', 'Hồng Trà', 'Bạch Trà', 'Oolong Trà', 'Phổ Nhĩ', 'Bánh Nướng', 'Bánh Dẻo', 'Bánh Ăn Kèm'].includes(filter))
            );
        }
        const sortedProducts = sortProducts(filteredProducts, currentSort);
        const totalItems = sortedProducts.length;
        const paginatedProducts = paginateProducts(sortedProducts, currentPage);
        productGrid.innerHTML = '';
        if (totalItems === 0) {
            noProductsMessage.style.display = 'block';
            paginationDiv.style.display = 'none';
        } else {
            noProductsMessage.style.display = 'none';
            paginatedProducts.forEach(product => {
                productGrid.innerHTML += renderProductCard(product);
            });
            renderPagination(totalItems);
        }
        breadcrumbCategory.innerHTML = filter !== 'All' ? ` / ${filter}` : '';
        updateCategoryActiveState(filter);
    }

    function updateCategoryActiveState(filter) {
        categoryLinks.forEach(link => link.classList.remove('active'));
        const activeLink = document.querySelector(`.category-link[data-filter="${filter}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
            const parentDropdown = activeLink.closest('.dropdown-menu');
            if (parentDropdown) {
                const parentLink = parentDropdown.previousElementSibling;
                if (parentLink) parentLink.classList.add('active');
            }
        } else {
            document.querySelector('.category-link[data-filter="All"]').classList.add('active');
        }
    }

    // Minicart logic (giữ nguyên, rút gọn)
    function readCart() { return JSON.parse(localStorage.getItem('cart')) || []; }
    function writeCart(cart) { localStorage.setItem('cart', JSON.stringify(cart)); updateCartBadge(); }
    function updateCartBadge() {
        const cart = readCart();
        const count = cart.reduce((s, it) => s + (it.quantity || 1), 0);
        let badge = document.querySelector('.cart-count');
        if (!badge && document.querySelector('.icons .cart-icon')) {
            badge = document.createElement('span'); badge.className = 'cart-count'; document.querySelector('.icons .cart-icon').appendChild(badge);
        }
        if (badge) { badge.textContent = count > 0 ? count : ''; badge.style.display = count > 0 ? 'inline-block' : 'none'; }
    }

    const overlayEl = document.getElementById('overlay');
    const miniCartEl = document.getElementById('mini-cart');
    const miniCartItemsEl = document.getElementById('minicart-items-list');
    const miniCountEl = document.getElementById('minicart-item-count');
    const miniTotalEl = document.getElementById('minicart-total-price');
    const closeBtn = document.querySelector('#mini-cart .close-btn');

    function openMiniCart() { overlayEl.classList.add('show'); miniCartEl.style.display = 'flex'; setTimeout(() => miniCartEl.classList.add('show'), 10); document.body.classList.add('no-scroll'); }
    function closeMiniCart() { miniCartEl.classList.remove('show'); overlayEl.classList.remove('show'); document.body.classList.remove('no-scroll'); setTimeout(() => miniCartEl.style.display = 'none', 350); }

    overlayEl.addEventListener('click', closeMiniCart);
    if (closeBtn) closeBtn.addEventListener('click', closeMiniCart);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && miniCartEl.classList.contains('show')) closeMiniCart(); });

    function renderMiniCart() {
        const cart = readCart();
        miniCartItemsEl.innerHTML = cart.length === 0 ? `<p style="text-align:center;color:#777;padding:20px">Giỏ hàng trống</p>` : '';
        let total = 0;
        cart.forEach(item => { total += item.price * item.quantity; const row = document.createElement('div'); row.className = 'minicart-item'; row.innerHTML = `<img src="${item.image}" alt="${item.name}" class="minicart-item-image"><div class="minicart-item-details"><span class="minicart-item-name">${item.name}</span><span class="minicart-item-price">${(item.price * item.quantity).toLocaleString('vi-VN')} VNĐ x ${item.quantity}</span></div><button class="minicart-item-remove" data-id="${item.id}"><i class="fa-solid fa-trash remove-icon"></i></button>`; miniCartItemsEl.appendChild(row); });
        miniCountEl.textContent = `${cart.length} Sản phẩm`; miniTotalEl.textContent = `${total.toLocaleString('vi-VN')} VND`;
    }

    miniCartItemsEl.addEventListener('click', (e) => { const btn = e.target.closest('.minicart-item-remove'); if (!btn) return; const id = btn.getAttribute('data-id'); let cart = readCart().filter(it => String(it.id) !== String(id)); writeCart(cart); renderMiniCart(); });

    document.body.addEventListener('click', (e) => { const addBtn = e.target.closest('.btn-add'); if (!addBtn) return; e.preventDefault(); const item = { id: addBtn.getAttribute('data-id'), name: addBtn.getAttribute('data-name'), price: parseInt(addBtn.getAttribute('data-price')), image: addBtn.getAttribute('data-image'), quantity: 1 }; let cart = readCart(); const existing = cart.find(i => i.id === item.id); if (existing) existing.quantity += 1; else cart.push(item); writeCart(cart); renderMiniCart(); openMiniCart(); });

    // Dropdown logic
    document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            const dropdownMenu = toggle.nextElementSibling;
            const arrow = toggle.querySelector('.dropdown-arrow');
            dropdownMenu.classList.toggle('open');
            arrow.classList.toggle('rotated');
        });
    });

    // Init
    updateCartBadge();
    renderMiniCart();
});

document.addEventListener('DOMContentLoaded', () => {
    const searchToggle = document.getElementById('search-toggle');
    const searchBar = document.getElementById('search-bar');
    const searchInput = document.getElementById('search-input');
    const autocompleteResults = document.getElementById('autocomplete-results');
    const searchClose = document.getElementById('search-close');
    const searchForm = document.querySelector('.search-form');

    let timeout;

    // Toggle thanh search khi click icon
    searchToggle.addEventListener('click', (e) => {
        e.preventDefault();
        searchBar.classList.toggle('active');
        if (searchBar.classList.contains('active')) {
            searchInput.focus();
        }
    });

    // Xóa input và ẩn gợi ý khi click nút close (×)
    searchClose.addEventListener('click', () => {
        searchInput.value = '';
        autocompleteResults.innerHTML = '';
        autocompleteResults.style.display = 'none';
        searchBar.classList.remove('active');
    });

    // Autocomplete: Gọi AJAX khi gõ input (debounce 300ms)
    searchInput.addEventListener('input', () => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            const keyword = searchInput.value.trim();
            if (keyword.length > 0) {
                fetch(`search_suggestions.php?keyword=${encodeURIComponent(keyword)}`)
                    .then(response => response.json())
                    .then(data => {
                        autocompleteResults.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(product => {
                                const link = document.createElement('a');
                                link.href = `javascript:void(0);`; // Không chuyển hướng ngay, chỉ gợi ý
                                link.textContent = product.name;
                                link.dataset.id = product.id; // Lưu ID để dùng sau nếu cần
                                autocompleteResults.appendChild(link);
                            });
                            autocompleteResults.style.display = 'block';
                        } else {
                            const noResult = document.createElement('div');
                            noResult.classList.add('no-results');
                            noResult.textContent = 'Không có kết quả';
                            autocompleteResults.appendChild(noResult);
                            autocompleteResults.style.display = 'block';
                        }
                    })
                    .catch(error => console.error('Lỗi AJAX:', error));
            } else {
                autocompleteResults.innerHTML = '';
                autocompleteResults.style.display = 'none';
            }
        }, 300);
    });

    // Chuyển hướng khi nhấn Enter hoặc nút submit
    searchForm.addEventListener('submit', (e) => {
        e.preventDefault(); // Ngăn submit ngay
        const keyword = searchInput.value.trim();
        if (keyword) {
            window.location.href = `search_results.php?keyword=${encodeURIComponent(keyword)}`;
        }
    });

    // Đóng search khi click ngoài
    document.addEventListener('click', (e) => {
        if (!searchBar.contains(e.target) && !searchToggle.contains(e.target)) {
            searchBar.classList.remove('active');
            autocompleteResults.style.display = 'none';
        }
    });
});