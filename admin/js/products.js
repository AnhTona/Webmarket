// admin/js/products.js — OOP, giữ nguyên hành vi & selector cũ

class ProductsPage {
    constructor() {
        // ===== Query elements =====
        this.searchInput = document.getElementById('search-input');
        this.btnSearch   = document.getElementById('btn-search');
        this.filterCategory = document.getElementById('filter-category');
        this.filterType     = document.getElementById('filter-type');   // hiện chưa dùng server-side
        this.filterPromo    = document.getElementById('filter-promo');

        this.table = document.getElementById('product-list-table');
        this.btnAdd = document.getElementById('btn-add-product');

        this.modal = document.getElementById('product-modal');
        this.productForm = document.getElementById('product-form');
        this.modalCloseBtns = this.modal ? this.modal.querySelectorAll('.close-button') : [];

        this.imgInput = document.getElementById('image');
        this.imgPreview = document.getElementById('image-preview');
        this.imgOld = document.getElementById('image-old');

        // number formatter
        this.fmt = new Intl.NumberFormat('vi-VN');

        // init
        this.initUI();
        this.bindModal();
        this.bindEditDelete();
        this.bindFormSubmit();
        this.bindFiltersToUrl();
        this.hydrateFromUrl();
    }

    // ===== UI toggles =====
    initUI() {

        // Preview ảnh khi chọn file mới
        this.imgInput?.addEventListener('change', (e) => {
            const file = e.target.files?.[0];
            if (!this.imgPreview) return;
            if (file) {
                this.imgPreview.src = URL.createObjectURL(file);
                this.imgPreview.style.display = 'block';
            } else {
                this.imgPreview.style.display = 'none';
            }
        });

        document.getElementById('btn-cancel')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.closeModal();
        });
        document
            .querySelectorAll('.btn-cancel,[data-role="cancel"],[data-dismiss="modal"]')
            .forEach((b) => b.addEventListener('click', (e) => { e.preventDefault(); this.closeModal(); }));

        window.addEventListener('keydown', (e) => { if (e.key === 'Escape') this.closeModal(); });
    }

    // ===== Modal helpers =====
    openModal(title = 'THÊM SẢN PHẨM MỚI') {
        if (!this.modal) return;
        const t = document.getElementById('modal-title');
        if (t) t.textContent = title;
        this.modal.classList.add('open');
        this.modal.style.display = 'flex';
    }

    closeModal() {
        if (!this.modal) return;
        this.modal.classList.remove('open');
        this.modal.style.display = 'none';
        this.productForm?.reset();
        if (this.imgPreview) this.imgPreview.style.display = 'none';
    }

    bindModal() {
        this.btnAdd?.addEventListener('click', () => {
            this.productForm?.reset();
            const idEl = document.getElementById('product-id');
            if (idEl) idEl.value = '';
            if (this.imgPreview) this.imgPreview.style.display = 'none';
            if (this.imgOld) this.imgOld.value = '';
            this.openModal('THÊM SẢN PHẨM MỚI');
        });

        this.modalCloseBtns.forEach((b) =>
            b.addEventListener('click', (e) => {
                e.preventDefault();
                this.closeModal();
            })
        );

        // Click ra nền để đóng
        this.modal?.addEventListener('click', (e) => {
            if (e.target === this.modal) this.closeModal();
        });
    }

    // ===== Edit/Delete buttons (delegation) =====
    bindEditDelete() {
        document.addEventListener('click', async (e) => {
            // EDIT
            const editBtn = e.target.closest('.edit-product');
            if (editBtn) {
                await this.onEditClick(editBtn);
                return;
            }

            // DELETE
            const delBtn = e.target.closest('.delete-product');
            if (delBtn) {
                await this.onDeleteClick(delBtn);
                return;
            }
        });
    }

    async onEditClick(btn) {
        const form  = this.productForm;
        if (!this.modal || !form) return;

        const setVal = (sel, val = '') => {
            const el = form.querySelector(sel);
            if (!el) return;
            if (el.type === 'checkbox') {
                el.checked = val === true || val === '1' || val === 1 || val === 'true';
            } else {
                el.value = (val ?? '').toString();
            }
        };

        form.reset();

        const data = {
            id: btn.dataset.id,
            name: btn.dataset.name,
            price: btn.dataset.price,
            oldPrice: btn.dataset.oldPrice,
            promo: btn.dataset.promo, // "0" | "1"
            categoryId: btn.dataset.categoryId || btn.dataset.category,
            categoryName: btn.dataset.categoryName || btn.dataset.category,
            qty: btn.dataset.qty,
            description: btn.dataset.description,
            image: btn.dataset.image,
        };

        // nếu thiếu dữ liệu, gọi API lấy chi tiết
        if ((!data.name || !data.price) && data.id) {
            try {
                const res = await fetch(
                    `products.php?action=get&id=${encodeURIComponent(data.id)}&ajax=1`
                );
                const json = await res.json();
                if (json?.ok && json.product) {
                    const p = json.product;
                    data.name = p.TenSanPham ?? data.name;
                    data.price = p.Gia ?? data.price;
                    data.oldPrice = p.GiaCu ?? data.oldPrice;
                    data.promo = (p.IsPromo ?? 0) + '';
                    data.categoryId = p.MaDanhMuc ?? data.categoryId;
                    data.categoryName = p.TenDanhMuc ?? data.categoryName;
                    data.qty = p.SoLuongTon ?? p.SoLuong ?? data.qty;
                    data.description = p.MoTa ?? data.description;
                    data.image = p.HinhAnh ?? data.image;
                }
            } catch (err) {
                console.warn('Fetch product failed:', err);
            }
        }

        // gán vào form
        setVal('#product-id', data.id);
        setVal('#name', data.name);
        setVal('#price', data.price);
        setVal('#old-price', data.oldPrice);
        setVal('#promo', data.promo);
        setVal('#quantity', data.qty);
        setVal('#description', data.description);

        // danh mục: ưu tiên chọn theo value = categoryId
        const catEl = form.querySelector('#category');
        if (catEl) {
            let selected = false;
            const idVal = (data.categoryId ?? '').toString();

            if (idVal) {
                catEl.value = idVal;
                selected = catEl.value === idVal;
            }
            if (!selected && data.categoryName) {
                const optByText = Array.from(catEl.options).find(
                    (o) => o.textContent.trim() === (data.categoryName || '').trim()
                );
                if (optByText) {
                    catEl.value = optByText.value;
                    selected = true;
                }
            }
            if (!selected && (idVal || data.categoryName)) {
                const opt = document.createElement('option');
                opt.value = idVal || data.categoryName;
                opt.textContent = data.categoryName || idVal;
                catEl.appendChild(opt);
                catEl.value = opt.value;
            }
        }

        // ảnh: set image-old để server giữ ảnh cũ nếu không chọn ảnh mới
        if (this.imgOld) this.imgOld.value = data.image || '';
        if (this.imgPreview) {
            if (data.image) {
                this.imgPreview.src = data.image;
                this.imgPreview.style.display = 'block';
            } else {
                this.imgPreview.style.display = 'none';
            }
        }

        this.openModal('SỬA SẢN PHẨM');
    }

    async onDeleteClick(btn) {
        const id =
            btn.dataset.id ||
            btn.closest('tr')?.dataset?.id ||
            '';
        const name =
            btn.dataset.name ||
            btn.closest('tr')?.querySelector('td:nth-child(2)')?.textContent?.trim() ||
            'sản phẩm';

        if (!id) {
            alert('Thiếu mã sản phẩm!');
            return;
        }

        const ok = confirm(`Bạn có chắc chắn muốn xóa "${name}" không?`);
        if (!ok) return;

        const oldHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

        try {
            const res = await fetch(
                `products.php?action=delete&id=${encodeURIComponent(id)}&ajax=1`,
                { method: 'GET', credentials: 'same-origin' }
            );
            const j = await res.json();

            if (!j.ok) {
                alert(j.message || 'Xóa thất bại');
                btn.disabled = false;
                btn.innerHTML = oldHtml;
                return;
            }

            const row = btn.closest('tr');
            row?.parentNode?.removeChild(row);
        } catch (err) {
            console.error(err);
            alert('Lỗi kết nối máy chủ.');
            btn.disabled = false;
            btn.innerHTML = oldHtml;
        }
    }

    // ===== Save (create/update) via AJAX =====
    bindFormSubmit() {
        this.productForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.currentTarget;

            const fd = new FormData(form);
            fd.set('id', form.querySelector('#product-id')?.value || '');
            fd.set('promo', form.querySelector('#promo')?.value || '0');

            // DEBUG: xem có chọn file chưa
            const f = fd.get('image');
            console.log('file chosen:', f instanceof File ? `${f.name} (${f.size}B)` : 'NO FILE');

            try {
                const res = await fetch('products.php?action=save&ajax=1', {
                    method: 'POST',
                    body: fd, // KHÔNG set Content-Type (để browser tự set boundary)
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });

                const text = await res.text();
                let j;
                try {
                    j = JSON.parse(text);
                } catch {
                    console.error('Non-JSON response:', text);
                    alert('Server không trả JSON (có thể bị redirect hoặc lỗi PHP). Kiểm tra tab Network.');
                    return;
                }

                if (!j.ok) {
                    alert(j.message || 'Lưu thất bại');
                    return;
                }

                // đồng bộ lại data-* và thumbnail (nếu có)
                const pid = j.product?.id ?? fd.get('id');
                const btn = document.querySelector(`.edit-product[data-id="${pid}"]`);
                if (btn && j.product?.HinhAnh) {
                    btn.dataset.image = j.product.HinhAnh;
                    const imgCell = btn.closest('tr')?.querySelector('td img');
                    if (imgCell) imgCell.src = j.product.HinhAnh;
                }

                this.closeModal();
                window.location.reload();
            } catch (err) {
                console.error(err);
                alert('Có lỗi khi gửi yêu cầu. Kiểm tra kết nối/Network.');
            }
        });
    }

    // ===== Filter/Search – đẩy query lên URL =====
    applyFiltersToUrl(e) {
        if (e) e.preventDefault();
        const qs = new URLSearchParams(location.search);

        const s = (this.searchInput?.value || '').trim();
        if (s) qs.set('search', s);
        else qs.delete('search');

        if (this.filterCategory) qs.set('category', this.filterCategory.value || 'All');
        if (this.filterPromo) qs.set('promo', this.filterPromo.value || 'All');

        qs.set('page', '1'); // đổi filter -> về trang 1
        location.search = qs.toString();
    }

    bindFiltersToUrl() {
        this.btnSearch?.addEventListener('click', (e) => this.applyFiltersToUrl(e));
        this.filterCategory?.addEventListener('change', (e) => this.applyFiltersToUrl(e));
        this.filterPromo?.addEventListener('change', (e) => this.applyFiltersToUrl(e));
        this.searchInput?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.applyFiltersToUrl(e);
        });

        // Khởi tạo select từ URL & bơm option nếu thiếu
        (function hydrateCategories(page = this) {
            if (!page.filterCategory) return;

            if (Array.isArray(window.categoryOptions)) {
                const exist = new Set(Array.from(page.filterCategory.options).map((o) => o.value));
                window.categoryOptions.forEach((v) => {
                    if (v && !exist.has(v)) {
                        const opt = document.createElement('option');
                        opt.value = v;
                        opt.textContent = v;
                        page.filterCategory.appendChild(opt);
                    }
                });
            }

            const qs = new URLSearchParams(location.search);
            const c = qs.get('category');

            // Nếu URL có category mà select chưa có -> thêm option tạm để chọn đúng
            if (c && !Array.from(page.filterCategory.options).some((o) => o.value === c)) {
                const opt = document.createElement('option');
                opt.value = c;
                opt.textContent = c;
                page.filterCategory.appendChild(opt);
            }

            // Nếu không có category trên URL → mặc định "All"
            page.filterCategory.value = qs.has('category') ? c : 'All';
        }.call(this));
    }

    hydrateFromUrl() {
        const qs = new URLSearchParams(window.location.search);
        if (this.filterCategory) this.filterCategory.value = qs.get('category') || 'All';
        if (this.filterPromo) this.filterPromo.value = qs.get('promo') || 'All';
        if (this.searchInput) this.searchInput.value = qs.get('search') || '';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.__productsPage = new ProductsPage();
});
