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
// Preview ảnh khi chọn file mới
document.getElementById('image')?.addEventListener('change', (e) => {
    const imgPreview = document.getElementById('image-preview');
    const file = e.target.files?.[0];
    if (!imgPreview) return;
    if (file) {
        imgPreview.src = URL.createObjectURL(file);
        imgPreview.style.display = 'block';
    } else {
        imgPreview.style.display = 'none';
    }
});

// ====== Modal helpers (đồng bộ hoá open/close) ======
function openModal(title = 'THÊM SẢN PHẨM MỚI') {
    if (!modal) return;
    modal.classList.add('open');
    modal.style.display = 'flex';                  // luôn dùng inline cho chắc
    const t = document.getElementById('modal-title');
    if (t) t.textContent = title;
}

function closeModal() {
    if (!modal) return;
    modal.classList.remove('open');
    modal.style.display = 'none';                  // xoá inline để thật sự ẩn
    productForm?.reset();
    const imgPrev = document.getElementById('image-preview');
    if (imgPrev) imgPrev.style.display = 'none';
}
btnAdd?.addEventListener('click', () => {
    productForm?.reset();
    const idEl = document.getElementById('product-id');
    if (idEl) idEl.value = '';
    const imgPrev = document.getElementById('image-preview');
    if (imgPrev) imgPrev.style.display = 'none';
    const imgOld = document.getElementById('image-old'); // để server giữ ảnh cũ khi không chọn ảnh mới
    if (imgOld) imgOld.value = '';
    openModal('THÊM SẢN PHẨM MỚI');
});
modalCloseBtns.forEach(b => b.addEventListener('click', closeModal));
document.getElementById('btn-cancel')?.addEventListener('click', closeModal);
// Bắt thêm các khả năng tên class khác nhau cho nút hủy:
document.querySelectorAll('.btn-cancel,.cancel-button,[data-role="cancel"],[data-dismiss="modal"]')
    .forEach(b => b.addEventListener('click', closeModal));

window.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

// ====== Edit/Delete buttons on table ======
// === EDIT: mở modal và đổ dữ liệu ===
// admin/js/products.js
document
    .querySelectorAll('#product-modal .btn-cancel, #product-modal .close-button')
    .forEach(b => b.addEventListener('click', (e) => { e.preventDefault(); closeModal(); }));

// Click ra nền để đóng
modal?.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.edit-product');
    if (!btn) return;

    const modal = document.getElementById('product-modal');
    const form  = document.getElementById('product-form');
    if (!modal || !form) return;

    // helper set value
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

    // lấy từ data-*
    const data = {
        id: btn.dataset.id,
        name: btn.dataset.name,
        price: btn.dataset.price,
        oldPrice: btn.dataset.oldPrice,
        promo: btn.dataset.promo,                    // "0" | "1"
        categoryId: btn.dataset.categoryId || btn.dataset.category, // ưu tiên id
        categoryName: btn.dataset.categoryName || btn.dataset.category,
        qty: btn.dataset.qty,
        description: btn.dataset.description,
        image: btn.dataset.image
    };

    // (tuỳ chọn) nếu thiếu dữ liệu, gọi API lấy chi tiết
    if ((!data.name || !data.price) && data.id) {
        try {
            const res = await fetch(`products.php?action=get&id=${encodeURIComponent(data.id)}&ajax=1`);
            const json = await res.json();
            if (json?.ok && json.product) {
                const p = json.product;
                data.name         = p.TenSanPham ?? data.name;
                data.price        = p.Gia ?? data.price;
                data.oldPrice     = p.GiaCu ?? data.oldPrice;
                data.promo        = ((p.IsPromo ?? 0) + '');
                data.categoryId   = p.MaDanhMuc ?? data.categoryId;
                data.categoryName = p.TenDanhMuc ?? data.categoryName;
                data.qty = (p.SoLuongTon ?? p.SoLuong ?? data.qty);
                data.description  = p.MoTa ?? data.description;
                data.image        = p.HinhAnh ?? data.image;
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

    // chọn danh mục — ưu tiên theo value = categoryId
    const catEl = form.querySelector('#category');
    if (catEl) {
        let selected = false;
        const idVal = (data.categoryId ?? '').toString();

        if (idVal) {
            catEl.value = idVal;
            selected = (catEl.value === idVal);
        }
        if (!selected && data.categoryName) {
            const optByText = Array.from(catEl.options).find(o => o.textContent.trim() === (data.categoryName || '').trim());
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
            catEl.value = opt.value; // option tạm
        }
    }

    // ảnh
    const imgPreview = document.getElementById('image-preview');
    const imgOld     = document.getElementById('image-old');
    if (imgOld) imgOld.value = data.image || '';
    if (imgPreview) {
        if (data.image) {
            imgPreview.src = data.image; // nếu cần, prepend đường dẫn upload
            imgPreview.style.display = 'block';
        } else {
            imgPreview.style.display = 'none';
        }
    }

    // mở modal
    const title = document.getElementById('modal-title');
    if (title) title.textContent = 'SỬA SẢN PHẨM';
    // modal.classList.add('open');
    // modal.style.display = 'flex';
    openModal('SỬA SẢN PHẨM');
});

// ====== DELETE: confirm rồi gọi server xóa ======
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.delete-product');
    if (!btn) return;

    const id   = btn.dataset.id;
    const name = btn.dataset.name
        || btn.closest('tr')?.querySelector('td:nth-child(2)')?.textContent?.trim()
        || 'sản phẩm';
    if (!id) { alert('Thiếu mã sản phẩm!'); return; }

    const ok = confirm(`Bạn có chắc chắn muốn xóa "${name}" không?`);
    if (!ok) return;

    // UI: khóa nút + spinner
    const oldHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

    try {
        const res = await fetch(`products.php?action=delete&id=${encodeURIComponent(id)}&ajax=1`, {
            method: 'GET',
            credentials: 'same-origin'
        });
        const j = await res.json();

        if (!j.ok) {
            alert(j.message || 'Xóa thất bại');
            btn.disabled = false;
            btn.innerHTML = oldHtml;
            return;
        }

        // Xóa hàng khỏi bảng
        const row = btn.closest('tr');
        row?.parentNode?.removeChild(row);

    } catch (err) {
        console.error(err);
        alert('Lỗi kết nối máy chủ.');
        btn.disabled = false;
        btn.innerHTML = oldHtml;
    }
});

// ====== Save (create/update) – server-side via AJAX ======
// products.js
// ====== Save (create/update) – server-side via AJAX ======
document.getElementById('product-form').addEventListener('submit', async (e) => {
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
            body: fd, // KHÔNG set Content-Type
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        // cố gắng parse JSON; nếu server trả HTML thì báo lỗi dễ hiểu
        const text = await res.text();
        let j;
        try { j = JSON.parse(text); }
        catch (e2) {
            console.error('Non-JSON response:', text);
            alert('Server không trả JSON (có thể bị redirect hoặc lỗi PHP). Kiểm tra tab Network.');
            return;
        }

        if (!j.ok) {
            alert(j.message || 'Lưu thất bại');
            return;
        }

        // đồng bộ lại data-* và thumbnail (nếu bạn dùng)
        const pid = j.product?.id ?? fd.get('id');
        const btn = document.querySelector(`.edit-product[data-id="${pid}"]`);
        if (btn && j.product?.HinhAnh) {
            btn.dataset.image = j.product.HinhAnh;
            const imgCell = btn.closest('tr')?.querySelector('td img');
            if (imgCell) imgCell.src = j.product.HinhAnh;
        }

        // đóng modal + reload để thấy dữ liệu mới
        closeModal();
        window.location.reload();
    } catch (err) {
        console.error(err);
        alert('Có lỗi khi gửi yêu cầu. Kiểm tra kết nối/Network.');
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
// Gắn sự kiện đổi filter
document.getElementById('filter-category')?.addEventListener('change', () => {
    const url = new URL(window.location.href);
    const qs  = url.searchParams;
    const cat = (document.getElementById('filter-category').value || '').trim();

    // cat rỗng = Tất cả → xóa param để backend không lọc
    if (cat) qs.set('category', cat); else qs.delete('category');

    window.location.href = url.toString();
});

// Khởi tạo select từ URL (nếu không có param -> chọn Tất cả)
(function initCategoryFromUrl(){
    const qs  = new URLSearchParams(window.location.search);
    const sel = document.getElementById('filter-category');
    if (sel) sel.value = qs.get('category') || '';
})();

// products.js
document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('filter-category');
    if (!sel) return;

    // Lần nào vào trang cũng mặc định "Tất cả" nếu URL không có category
    const qs = new URLSearchParams(location.search);
    sel.value = qs.has('category') ? qs.get('category') : 'All';

    function navigate(q) {
        const s = q.toString();
        // nếu không còn query -> quay về pathname (đảm bảo reload)
        location.href = s ? (location.pathname + '?' + s) : location.pathname;
    }

    sel.addEventListener('change', () => {
        const v = sel.value;
        const q = new URLSearchParams(location.search);
        if (v === 'All' || v === '') {
            q.delete('category');          // chọn "Tất cả" -> bỏ param
        } else {
            q.set('category', v);          // chọn danh mục khác -> set param
        }
        navigate(q);
    });
});





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
    if (c && !Array.from(filterCategory.options).some(o => o.value === c)) {
        const opt = document.createElement('option');
        opt.value = c;
        opt.textContent = c;
        filterCategory.appendChild(opt);
    }
})();

