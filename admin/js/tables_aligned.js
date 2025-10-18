// admin/js/tables.js
// Viết lại để 'tables' chạy giống 'products' (modal CRUD, confirm delete, filter, status).
// Không đổi UI của bạn: chỉ cần có các id/class sau (hầu hết đã có sẵn):
// - #search-input, #btn-search, #filter-status
// - #btn-add-table, #table-modal, #table-form, [name=id], [name=seats], [name=status]
// - #tables-tbody (tbody danh sách)
// - Nút sửa: .edit-table (data-id, data-seats, data-status, data-usage)
// - Nút xoá: .delete-table (data-id)
// - Nút trạng thái: .status-action (data-id, data-op=book|cancel|checkout|set, data-value?)
// API đã dùng: tables.php?action=list|save|delete|status&ajax=1

(() => {
  // ===== Helpers =====
  const $  = (sel, ctx=document) => ctx.querySelector(sel);
  const $$ = (sel, ctx=document) => Array.from(ctx.querySelectorAll(sel));

  const state = {
    items: [],
    page: 1, per_page: 10, total: 0, total_pages: 1,
    current: null,
  };

  const tbody        = $('#tables-tbody') || $('#table-body') || $('#table-list');
  const btnAdd       = $('#btn-add-table');
  const modal        = $('#table-modal');
  const form         = $('#table-form');
  const inputId      = form ? form.querySelector('[name=id]') : null;
  const inputSeats   = form ? form.querySelector('[name=seats], [name=quantity], [name=soghe], [name=soGhe], [name=soluongghe], [name=soLuongGhe]') : null;
  const inputStatus  = form ? form.querySelector('[name=status], [name=trangthai]') : null;

  const searchInput  = $('#search-input');
  const btnSearch    = $('#btn-search');
  const filterStatus = $('#filter-status');

  function endpoint(path){
    return `tables.php?${path}&ajax=1`;
  }

  function openModal() {
    if (!modal) return;
    modal.classList.add('open');
  }
  function closeModal() {
    if (!modal) return;
    modal.classList.remove('open');
    if (form) form.reset();
    if (inputId) inputId.value = '';
  }

  function fillForm(row){
    if (!form) return;
    inputId && (inputId.value = row.id ?? row.MaBan ?? '');
    inputSeats && (inputSeats.value = row.seats ?? row.SoGhe ?? '');
    inputStatus && (inputStatus.value = row.status ?? row.TrangThai ?? '');
  }

  function readForm(){
    const id     = inputId && inputId.value ? Number(inputId.value) : null;
    const seats  = inputSeats ? Number(inputSeats.value) : 0;
    let   status = inputStatus ? inputStatus.value : '';
    return { id, seats, status };
  }

  function render() {
    if (!tbody) return;
    tbody.innerHTML = state.items.map(t => {
      const id     = t.id ?? t.MaBan;
      const seats  = t.seats ?? t.SoGhe;
      const status = (t.status ?? t.TrangThai ?? '').toString();
      const usage  = t.usage_count ?? t.SoLanSuDung ?? 0;
      return `
        <tr data-id="${id}">
          <td class="px-2 py-2 text-center">${id}</td>
          <td class="px-2 py-2 text-center">${seats}</td>
          <td class="px-2 py-2 text-center"><span class="status-badge status-${status.toLowerCase().replace(/\s+/g,'-')}">${status}</span></td>
          <td class="px-2 py-2 text-center">${usage}</td>
          <td class="px-2 py-2 text-center flex gap-2 justify-center">
            <button class="edit-table px-2 py-1 rounded bg-yellow-500 text-white" 
              data-id="${id}" data-seats="${seats}" data-status="${status}" data-usage="${usage}" title="Sửa">
              <i class="fa-solid fa-pen"></i>
            </button>
            <button class="delete-table px-2 py-1 rounded bg-red-500 text-white" 
              data-id="${id}" title="Xoá">
              <i class="fa-solid fa-trash"></i>
            </button>
            <div class="inline-flex gap-1">
              <button class="status-action px-2 py-1 rounded bg-blue-500 text-white" data-id="${id}" data-op="book" title="Đặt bàn">Đặt</button>
              <button class="status-action px-2 py-1 rounded bg-gray-500 text-white" data-id="${id}" data-op="cancel" title="Huỷ đặt">Huỷ</button>
              <button class="status-action px-2 py-1 rounded bg-green-600 text-white" data-id="${id}" data-op="checkout" title="Thanh toán">Trả</button>
            </div>
          </td>
        </tr>`;
    }).join('');
  }

  function upsertLocal(row){
    const model = {
      id: row.id ?? row.MaBan,
      seats: row.seats ?? row.SoGhe,
      status: row.status ?? row.TrangThai,
      usage_count: row.usage_count ?? row.SoLanSuDung ?? 0,
    };
    const i = state.items.findIndex(x => (x.id ?? x.MaBan) == model.id);
    if (i === -1) state.items.unshift(model);
    else state.items[i] = model;
  }

  // ===== API calls =====
  async function listTables(){
    const qs = new URLSearchParams(location.search);
    // server chấp nhận search & status
    const url = new URL(endpoint('action=list'), location.origin + location.pathname);
    const s = searchInput && searchInput.value.trim();
    const st = filterStatus && filterStatus.value;
    if (s) url.searchParams.set('search', s);
    if (st && st !== 'all') url.searchParams.set('status', st);

    const res = await fetch(url.toString(), { credentials: 'same-origin' });
    const j = await res.json();
    const items = (j?.data?.items ?? j?.table_list_paginated ?? j?.items ?? []).map(x => ({
      id: x.id ?? x.MaBan,
      seats: x.seats ?? x.SoGhe,
      status: x.status ?? x.TrangThai,
      usage_count: x.usage_count ?? x.SoLanSuDung ?? 0,
    }));
    state.items = items;
    if (j?.data?.pagination){
      Object.assign(state, j.data.pagination);
    }else{
      state.total = items.length; state.page = 1; state.total_pages = 1;
    }
    render();
  }

  async function saveTable(payload){
    const fd = new FormData();
    if (payload.id != null) fd.append('id', payload.id);
    fd.append('seats', payload.seats);
    fd.append('status', payload.status);
    const res = await fetch(endpoint('action=save'), { method: 'POST', body: fd, credentials: 'same-origin' });
    const j = await res.json();
    const row = j?.data?.table ?? j?.table ?? j;
    if (row) {
      upsertLocal(row);
      render();
      closeModal();
    } else {
      alert(j?.message || 'Lưu thất bại!');
    }
  }

  async function deleteTable(id){
    if (!confirm('Bạn có chắc chắn muốn xoá bàn này không?')) return;
    const fd = new FormData();
    fd.append('id', id);
    const res = await fetch(endpoint('action=delete'), { method: 'POST', body: fd, credentials: 'same-origin' });
    const j = await res.json();
    if (j?.ok) {
      state.items = state.items.filter(x => (x.id ?? x.MaBan) != id);
      render();
    } else {
      alert(j?.message || 'Xoá thất bại!');
    }
  }

  async function updateStatus(id, op, value){
    const fd = new FormData();
    fd.append('id', id);
    fd.append('op', op);
    if (value != null) fd.append('value', value);
    const res = await fetch(endpoint('action=status'), { method: 'POST', body: fd, credentials: 'same-origin' });
    const j = await res.json();
    const row = j?.data?.table ?? j?.table;
    if (row) { upsertLocal(row); render(); }
    else { alert(j?.message || 'Cập nhật trạng thái thất bại!'); }
  }

  // ===== Events =====
  btnAdd && btnAdd.addEventListener('click', () => {
    state.current = null;
    form && form.reset();
    inputId && (inputId.value = '');
    openModal();
  });

  if (form){
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const model = readForm();
      if (!model.seats || model.seats <= 0) return alert('Số ghế phải > 0');
      saveTable(model);
    });
  }

  btnSearch && btnSearch.addEventListener('click', (e) => {
    e.preventDefault(); listTables();
  });
  searchInput && searchInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') { e.preventDefault(); listTables(); }
  });
  filterStatus && filterStatus.addEventListener('change', () => listTables());

  // event delegation cho các nút trong bảng
  if (tbody){
    tbody.addEventListener('click', (e) => {
      const btn = e.target.closest('button');
      if (!btn) return;
      if (btn.classList.contains('edit-table')){
        const row = {
          id: btn.dataset.id ? Number(btn.dataset.id) : null,
          seats: btn.dataset.seats ? Number(btn.dataset.seats) : '',
          status: btn.dataset.status ?? '',
          usage_count: btn.dataset.usage ? Number(btn.dataset.usage) : 0,
        };
        fillForm(row);
        openModal();
      } else if (btn.classList.contains('delete-table')){
        const id = Number(btn.dataset.id);
        if (id) deleteTable(id);
      } else if (btn.classList.contains('status-action')){
        const id = Number(btn.dataset.id);
        const op = btn.dataset.op;
        const value = btn.dataset.value;
        if (id && op) updateStatus(id, op, value);
      }
    });
  }

  // Khởi động
  listTables();
})();
