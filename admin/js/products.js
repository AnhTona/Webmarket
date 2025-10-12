// admin/js/products.js
// Chỉ sửa đúng logic filter & CRUD gọi server. Layout/DOM giữ nguyên.

// ====== Query elements ======
const searchInput = document.getElementById('search-input');
const btnSearch   = document.getElementById('btn-search');
const filterCategory = document.getElementById('filter-category');
const filterType     = document.getElementById('filter-type');   // hiện chưa dùng server-side
const filterPromo    = document.getElementById('filter-promo');

const table = document.getElementById('product-list-table');
const btnAdd = document.getElementById('btn-add-product');

const modal = document.getElementById('product-modal');
const modalCloseBtns = modal ? modal.querySelectorAll('.close-button') : [];
const productForm = document.getElementById('product-form');

// ====== Sidebar toggle (nhẹ) ======
document.getElementById('menu-toggle')?.addEventListener('click', () => {
    const sb = document.getElementById('sidebar');
    if (!sb) return;
    sb.classList.toggle('-translate-x-full');
});

// ====== Modal helpers ======
function openModal(title = 'THÊM SẢN PHẨM MỚI') {
    modal?.classList.add('open');
    document.getElementById('modal-title').textContent = title;
}
function closeModal() { modal?.classList.remove('open'); }

modalCloseBtns.forEach(b => b.addEventListener('click', closeModal));
window.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

// ====== Add product (open modal & reset form) ======
btnAdd?.addEventListener('click', () => {
    productForm?.reset();
    document.getElementById('product-id').value = '';
    openModal('THÊM SẢN PHẨM MỚI');
});

// ====== Edit/Delete buttons on table ======
table?.addEventListener('click', (ev) => {
    const btn = ev.target.closest('.btn-action');
    if (!btn) return;
    const tr = btn.closest('tr');
    const id = tr?.dataset?.id;
    if (!id) return;

    if (btn.classList.contains('edit-product')) {
        // Lấy giá trị từ cells để fill form (đúng thứ tự cột hiện tại)
        const cells = tr.querySelectorAll('td');
        const name  = cells[1]?.textContent?.trim() || '';
        const priceText = cells[2]?.textContent || '';
        const price = priceText.replace(/[^\d]/g, ''); // bỏ ' VNĐ' và dấu chấm
        const category = cells[4]?.textContent?.trim() || '';
        const type  = cells[5]?.textContent?.trim() || 'Trà'; // hiện chưa lưu server-side
        const promoText = cells[6]?.textContent?.trim() || 'Không';
        const promo = (promoText === 'Có') ? '1' : '0';

        document.getElementById('product-id').value = id;
        document.getElementById('name').value = name;
        document.getElementById('price').value = price;
        document.getElementById('category').value = category === 'Bánh' ? 'Bánh' : 'Trà';
        document.getElementById('type').value = type;
        document.getElementById('promo').value = promo;
        // ảnh không thể set trước vào input[type=file]

        openModal('CẬP NHẬT SẢN PHẨM');
    }
    else if (btn.classList.contains('delete-product')) {
        if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) return;
        fetch(`products.php?action=delete&id=${encodeURIComponent(id)}&ajax=1`)
            .then(r => r.json())
            .then(j => {
                alert(j.message);
                if (j.ok) location.reload();
            })
            .catch(() => alert('Xóa thất bại'));
    }
});

// ====== Save (create/update) – server-side via AJAX ======
productForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(productForm);
    fd.append('action','save');
    try {
        const res = await fetch('products.php?ajax=1', { method:'POST', body: fd });
        const j = await res.json();
        alert(j.message);
        if (j.ok) location.reload();
    } catch (err) {
        alert('Lưu thất bại');
    }
});

// ====== Filter/Search – đẩy query lên URL để server lọc & phân trang ======
function applyFiltersToUrl(e) {
    if (e) e.preventDefault();
    const qs = new URLSearchParams(location.search);

    const s = (searchInput?.value || '').trim();
    if (s) qs.set('search', s); else qs.delete('search');

    if (filterCategory) qs.set('category', filterCategory.value || 'All');
    if (filterPromo)    qs.set('promo',    filterPromo.value    || 'All');

    // reset về trang 1 khi đổi bộ lọc/tìm kiếm
    qs.set('page', '1');

    location.search = qs.toString();
}

btnSearch?.addEventListener('click', applyFiltersToUrl);
filterCategory?.addEventListener('change', applyFiltersToUrl);
filterPromo?.addEventListener('change', applyFiltersToUrl);
searchInput?.addEventListener('keypress', (e) => { if (e.key === 'Enter') applyFiltersToUrl(e); });

// Hydrate UI từ URL (nếu reload có query sẵn)
(function hydrateCategories(){
    if (!filterCategory) return;

    // Bơm toàn bộ danh mục thực tế từ DB nếu thiếu
    if (Array.isArray(window.categoryOptions)) {
        const exist = new Set(Array.from(filterCategory.options).map(o => o.value));
        window.categoryOptions.forEach(v => {
            if (v && !exist.has(v)) {
                const opt = document.createElement('option');
                opt.value = v;
                opt.textContent = v;
                filterCategory.appendChild(opt);
            }
        });
    }

    // Nếu URL đang có ?category=Cà%20phê mà select chưa có -> thêm option tạm để chọn đúng
    const qs = new URLSearchParams(location.search);
    const c = qs.get('category');
    if (c && ![...filterCategory.options].some(o => o.value === c)) {
        const opt = document.createElement('option');
        opt.value = c;
        opt.textContent = c;
        filterCategory.appendChild(opt);
    }
})();

