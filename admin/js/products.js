document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menu-toggle');
    const contentArea = document.getElementById('content-area');
    const mainLayout = document.getElementById('main-layout');
    const notificationBell = document.getElementById('notification-bell');
    const notificationDropdown = document.getElementById('notification-dropdown');
    const searchInput = document.getElementById('search-input');
    const btnSearch = document.getElementById('btn-search');
    const filterCategory = document.getElementById('filter-category');
    const btnToggleAdvanced = document.getElementById('btn-toggle-advanced-filter');
    const advancedFilters = document.getElementById('advanced-filters');
    const filterType = document.getElementById('filter-type');
    const filterPromo = document.getElementById('filter-promo');
    const btnAddProduct = document.getElementById('btn-add-product');
    const productModal = document.getElementById('product-modal');
    const closeButton = document.querySelector('.close-button');
    const productForm = document.getElementById('product-form');
    const productId = document.getElementById('product-id');
    const name = document.getElementById('name');
    const price = document.getElementById('price');
    const oldPrice = document.getElementById('old-price');
    const category = document.getElementById('category');
    const type = document.getElementById('type');
    const promo = document.getElementById('promo');
    const image = document.getElementById('image');
    const btnSaveProduct = document.getElementById('btn-save-product');
    const productListTable = document.getElementById('product-list-table');
    const noResultsMessage = document.getElementById('no-results-message');

    // Kiểm tra sự tồn tại của các phần tử
    if (!sidebar || !menuToggle || !contentArea || !mainLayout || !notificationBell || !notificationDropdown ||
        !searchInput || !btnSearch || !filterCategory || !btnToggleAdvanced || !advancedFilters ||
        !filterType || !filterPromo || !btnAddProduct || !productModal || !closeButton || !productForm ||
        !productId || !name || !price || !oldPrice || !category || !type || !promo || !image ||
        !btnSaveProduct || !productListTable || !noResultsMessage) {
        console.error('Một hoặc nhiều phần tử DOM không tồn tại!');
        return;
    }

    // Toggle Sidebar
    function toggleSidebar() {
        sidebar.classList.toggle('-translate-x-full');
    }

    menuToggle.addEventListener('click', toggleSidebar);
    mainLayout.addEventListener('click', function(event) {
        if (window.innerWidth < 768 && !sidebar.contains(event.target) && !menuToggle.contains(event.target) && !sidebar.classList.contains('-translate-x-full')) {
            toggleSidebar();
        }
    });
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            sidebar.classList.remove('-translate-x-full');
        }
    });
    if (window.innerWidth >= 768) {
        sidebar.classList.remove('fixed', 'shadow-xl');
        sidebar.classList.add('relative');
    }

    // Xử lý thông báo
    notificationBell.addEventListener('click', function(e) {
        e.stopPropagation();
        notificationDropdown.classList.toggle('active');
    });
    document.addEventListener('click', function(e) {
        if (!notificationBell.contains(e.target) && !notificationDropdown.contains(e.target)) {
            notificationDropdown.classList.remove('active');
        }
    });

    // Toggle Advanced Filters
    btnToggleAdvanced.addEventListener('click', () => {
        advancedFilters.classList.toggle('hidden');
    });

    // Filter and Search
    function filterProducts() {
        const searchTerm = searchInput.value.toLowerCase();
        const categoryFilter = filterCategory.value;
        const typeFilter = filterType.value;
        const promoFilter = filterPromo.value;

        const rows = productListTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        let hasResults = false;

        for (let row of rows) {
            const id = row.getAttribute('data-id');
            const product = window.productsData.find(p => p.MaSanPham == id);
            if (!product) continue;

            const matchesSearch = product.TenSanPham.toLowerCase().includes(searchTerm);
            const matchesCategory = categoryFilter === 'All' || product.DanhMuc === categoryFilter;
            const matchesType = typeFilter === 'All' || product.Loai === typeFilter;
            const matchesPromo = promoFilter === 'All' || (product.isPromo.toString() === promoFilter);

            if (matchesSearch && matchesCategory && matchesType && matchesPromo) {
                row.style.display = '';
                hasResults = true;
            } else {
                row.style.display = 'none';
            }
        }

        noResultsMessage.style.display = hasResults ? 'none' : 'block';
    }

    searchInput.addEventListener('input', filterProducts);
    btnSearch.addEventListener('click', filterProducts);
    filterCategory.addEventListener('change', filterProducts);
    filterType.addEventListener('change', filterProducts);
    filterPromo.addEventListener('change', filterProducts);

    // Add Product
    btnAddProduct.addEventListener('click', () => {
        productId.value = '';
        name.value = '';
        price.value = '';
        oldPrice.value = '';
        category.value = 'Trà';
        type.value = 'Trà';
        promo.value = '0';
        image.value = '';
        productModal.classList.remove('hidden');
        document.getElementById('modal-title').textContent = 'THÊM SẢN PHẨM MỚI';
    });

    // Edit/Delete Product
    productListTable.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-action');
        if (!btn) return;

        const id = btn.closest('tr').getAttribute('data-id');
        const product = window.productsData.find(p => p.MaSanPham == id);

        if (btn.classList.contains('edit-product')) {
            productId.value = product.MaSanPham;
            name.value = product.TenSanPham;
            price.value = product.Gia;
            oldPrice.value = product.GiaCu || '';
            category.value = product.DanhMuc;
            type.value = product.Loai;
            promo.value = product.isPromo.toString();
            image.value = '';
            productModal.classList.remove('hidden');
            document.getElementById('modal-title').textContent = 'CHỈNH SỬA SẢN PHẨM';
        } else if (btn.classList.contains('delete-product')) {
            if (confirm('Bạn có chắc muốn xóa sản phẩm này?')) {
                const index = window.productsData.findIndex(p => p.MaSanPham == id);
                window.productsData.splice(index, 1);
                btn.closest('tr').remove();
                filterProducts();
                alert('Sản phẩm đã được xóa (giả lập)');
            }
        }
    });

    // Close Modal
    closeButton.addEventListener('click', () => productModal.classList.add('hidden'));
    window.addEventListener('click', (e) => {
        if (e.target === productModal) productModal.classList.add('hidden');
    });

    // Save Product
    productForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const id = productId.value || Math.max(...window.productsData.map(p => p.MaSanPham), 0) + 1;
        const data = {
            MaSanPham: id,
            HinhAnh: image.files.length ? URL.createObjectURL(image.files[0]) : window.productsData.find(p => p.MaSanPham == id)?.HinhAnh || 'https://placehold.co/50x50/8f2c24/ffffff?text=P' + id,
            TenSanPham: name.value,
            Gia: parseInt(price.value),
            GiaCu: oldPrice.value ? parseInt(oldPrice.value) : null,
            DanhMuc: category.value,
            Loai: type.value,
            isPromo: parseInt(promo.value)
        };
        if (!productId.value) {
            window.productsData.push(data);
            const newRow = productListTable.tBodies[0].insertRow();
            newRow.setAttribute('data-id', id);
            newRow.innerHTML = `
                <td><img src="${data.HinhAnh}" alt="${data.TenSanPham}" class="w-12 h-12 object-cover rounded"></td>
                <td>${data.TenSanPham}</td>
                <td>${numberFormat(data.Gia)} VNĐ</td>
                <td>${data.GiaCu ? numberFormat(data.GiaCu) + ' VNĐ' : '-'}</td>
                <td>${data.DanhMuc}</td>
                <td>${data.Loai}</td>
                <td class="promo-${data.isPromo ? 'yes' : 'no'}">${data.isPromo ? 'Có' : 'Không'}</td>
                <td>
                    <button class="btn-action edit-product" title="Sửa"><i class="fas fa-edit"></i></button>
                    <button class="btn-action delete-product" title="Xóa"><i class="fas fa-trash"></i></button>
                </td>
            `;
        } else {
            const index = window.productsData.findIndex(p => p.MaSanPham == id);
            window.productsData[index] = data;
            const row = productListTable.querySelector(`tr[data-id="${id}"]`);
            row.cells[1].textContent = data.TenSanPham;
            row.cells[2].textContent = numberFormat(data.Gia) + ' VNĐ';
            row.cells[3].textContent = data.GiaCu ? numberFormat(data.GiaCu) + ' VNĐ' : '-';
            row.cells[4].textContent = data.DanhMuc;
            row.cells[5].textContent = data.Loai;
            row.cells[6].className = `promo-${data.isPromo ? 'yes' : 'no'}`;
            row.cells[6].textContent = data.isPromo ? 'Có' : 'Không';
            row.cells[7].innerHTML = `
                <button class="btn-action edit-product" title="Sửa"><i class="fas fa-edit"></i></button>
                <button class="btn-action delete-product" title="Xóa"><i class="fas fa-trash"></i></button>
            `;
        }
        productModal.classList.add('hidden');
        filterProducts();
        alert('Sản phẩm đã được lưu (giả lập)');
    });

    // Helper function to format number
    function numberFormat(number) {
        return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
});