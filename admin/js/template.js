/**
 * products.js - Optimized version
 * Quản lý sản phẩm với better error handling và UX
 */

class ProductsPage {
    constructor() {
        // Elements
        this.searchInput = document.getElementById('search-input');
        this.btnSearch = document.getElementById('btn-search');
        this.filterCategory = document.getElementById('filter-category');
        this.table = document.getElementById('product-list-table');
        this.btnAdd = document.getElementById('btn-add-product');
        this.productForm = document.getElementById('product-form');
        this.imgInput = document.getElementById('image');
        this.imgPreview = document.getElementById('image-preview');

        // Modal
        this.modal = new AdminModal('product-modal', 'Quản lý sản phẩm');

        // State
        this.currentProduct = null;

        // Init
        this.init();
    }

    init() {
        this.bindEvents();
        this.hydrateFromURL();
    }

    bindEvents() {
        // Add product button
        this.btnAdd?.addEventListener('click', () => this.openAddModal());

        // Search with debounce
        this.searchInput?.addEventListener('input',
            AdminUtils.debounce(() => this.applyFilters(), 300)
        );

        this.btnSearch?.addEventListener('click', () => this.applyFilters());

        // Filters
        this.filterCategory?.addEventListener('change', () => this.applyFilters());

        // Enter key to search
        this.searchInput?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.applyFilters();
            }
        });

        // Image preview
        this.imgInput?.addEventListener('change', (e) => this.previewImage(e));

        // Form submit
        this.productForm?.addEventListener('submit', (e) => this.handleSubmit(e));

        // Table actions (delegation)
        this.table?.addEventListener('click', (e) => this.handleTableClick(e));
    }

    previewImage(e) {
        const file = e.target.files?.[0];
        if (!file || !this.imgPreview) return;

        const validation = AdminUtils.validateImage(file);
        if (!validation.valid) {
            AdminUtils.showToast(validation.message, 'error');
            e.target.value = '';
            return;
        }

        this.imgPreview.src = URL.createObjectURL(file);
        this.imgPreview.style.display = 'block';
    }

    openAddModal() {
        this.currentProduct = null;
        this.productForm?.reset();
        this.imgPreview.style.display = 'none';
        this.modal.open();
    }

    async openEditModal(productId) {
        const loading = AdminUtils.showLoading('Đang tải thông tin sản phẩm...');

        try {
            const result = await AdminUtils.ajax(
                `products.php?action=get&id=${encodeURIComponent(productId)}&ajax=1`
            );

            if (!result.success || !result.data.ok) {
                throw new Error(result.data?.message || 'Không thể tải thông tin sản phẩm');
            }

            this.currentProduct = result.data.product;
            this.fillForm(this.currentProduct);
            this.modal.open();

        } catch (error) {
            AdminUtils.showToast(error.message, 'error');
        } finally {
            loading.remove();
        }
    }

    fillForm(product) {
        if (!this.productForm || !product) return;

        const setField = (name, value) => {
            const field = this.productForm.querySelector(`[name="${name}"]`);
            if (field) field.value = value ?? '';
        };

        setField('id', product.MaSanPham);
        setField('name', product.TenSanPham);
        setField('price', product.Gia);
        setField('old-price', product.GiaCu);
        setField('quantity', product.SoLuongTon || product.SoLuong);
        setField('category', product.MaDanhMuc || product.TenDanhMuc);
        setField('promo', product.IsPromo || 0);

        // Image preview
        if (product.HinhAnh && this.imgPreview) {
            this.imgPreview.src = product.HinhAnh;
            this.imgPreview.style.display = 'block';
        }
    }

    async handleSubmit(e) {
        e.preventDefault();

        const formData = new FormData(this.productForm);
        const isEdit = !!formData.get('id');

        const confirmed = await AdminUtils.confirm(
            isEdit ? 'Xác nhận cập nhật sản phẩm?' : 'Xác nhận thêm sản phẩm mới?',
            'Xác nhận'
        );

        if (!confirmed) return;

        try {
            const result = await AdminUtils.ajax('products.php?action=save&ajax=1', {
                method: 'POST',
                body: formData
            });

            if (!result.success || !result.data.ok) {
                throw new Error(result.data?.message || 'Lưu thất bại');
            }

            AdminUtils.showToast(
                isEdit ? 'Cập nhật sản phẩm thành công!' : 'Thêm sản phẩm thành công!',
                'success'
            );

            this.modal.close();

            // Reload after 1s to show toast
            setTimeout(() => window.location.reload(), 1000);

        } catch (error) {
            AdminUtils.showToast(error.message, 'error');
        }
    }

    async handleTableClick(e) {
        const editBtn = e.target.closest('.edit-product');
        const deleteBtn = e.target.closest('.delete-product');

        if (editBtn) {
            const productId = editBtn.dataset.id;
            await this.openEditModal(productId);
            return;
        }

        if (deleteBtn) {
            await this.handleDelete(deleteBtn);
            return;
        }
    }

    async handleDelete(btn) {
        const productId = btn.dataset.id;
        const productName = btn.dataset.name || 'sản phẩm này';

        if (!productId) {
            AdminUtils.showToast('Thiếu mã sản phẩm', 'error');
            return;
        }

        const confirmed = await AdminUtils.confirm(
            `Bạn có chắc chắn muốn xóa "${productName}" không?\nHành động này không thể hoàn tác.`,
            'Xác nhận xóa'
        );

        if (!confirmed) return;

        try {
            const result = await AdminUtils.ajax(
                `products.php?action=delete&id=${encodeURIComponent(productId)}&ajax=1`
            );

            if (!result.success || !result.data.ok) {
                throw new Error(result.data?.message || 'Xóa thất bại');
            }

            AdminUtils.showToast('Xóa sản phẩm thành công!', 'success');

            // Remove row from table
            const row = btn.closest('tr');
            if (row) {
                row.style.transition = 'opacity 0.3s';
                row.style.opacity = '0';
                setTimeout(() => row.remove(), 300);
            }

        } catch (error) {
            AdminUtils.showToast(error.message, 'error');
        }
    }

    applyFilters() {
        const params = {
            search: this.searchInput?.value?.trim() || null,
            category: this.filterCategory?.value !== 'All' ? this.filterCategory?.value : null,
            page: 1
        };

        AdminUtils.updateURLParams(params);
        window.location.reload();
    }

    hydrateFromURL() {
        if (this.searchInput) {
            this.searchInput.value = AdminUtils.getURLParam('search', '');
        }
        if (this.filterCategory) {
            this.filterCategory.value = AdminUtils.getURLParam('category', 'All');
        }
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    window.productsPage = new ProductsPage();
});