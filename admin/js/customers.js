document.addEventListener('DOMContentLoaded', () => {
    // ----------------------------------------------------------------------
    // 1. Khai báo các biến DOM và Dữ liệu
    // ----------------------------------------------------------------------
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menu-toggle');
    const notificationBell = document.getElementById('notification-bell');
    const notificationDropdown = document.getElementById('notification-dropdown');
    
    // Elements cho Filter/Search
    const searchInput = document.getElementById('search-input');
    const btnSearch = document.getElementById('btn-search');
    const filterStatus = document.getElementById('filter-status');
    const filterRank = document.getElementById('filter-rank');
    const btnToggleAdvanced = document.getElementById('btn-toggle-advanced-filter');
    const advancedFilters = document.getElementById('advanced-filters');
    const filterCity = document.getElementById('filter-city');
    const filterDateFrom = document.getElementById('filter-date-from');
    const filterDateTo = document.getElementById('filter-date-to');
    const customerTableBody = document.querySelector('#customer-list-table tbody');
    const noResultsMessage = document.getElementById('no-results-message');

    // Elements cho Modal
    const btnAddCustomer = document.getElementById('btn-add-customer');
    const customerModal = document.getElementById('customer-modal');
    const closeButtons = document.querySelectorAll('.close-button'); // Lấy tất cả các nút đóng
    const modalTitle = document.getElementById('modal-title');
    const customerForm = document.getElementById('customer-form');
    const customerId = document.getElementById('customer-id');
    const displayRank = document.getElementById('display-rank');
    const historyNotesSection = document.getElementById('history-notes-section');

    // Lấy dữ liệu khách hàng từ PHP (dữ liệu giả định)
    let customersData = window.customersData;

    // ----------------------------------------------------------------------
    // 2. Hàm chung
    // ----------------------------------------------------------------------

    // Hàm đóng/mở sidebar (cho mobile/tablet)
    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');
    });

    // Hàm đóng/mở thông báo
    notificationBell.addEventListener('click', (e) => {
        notificationDropdown.classList.toggle('active');
        e.stopPropagation(); // Ngăn chặn sự kiện click lan ra body
    });

    document.body.addEventListener('click', (e) => {
        if (notificationDropdown.classList.contains('active') && !notificationDropdown.contains(e.target) && !notificationBell.contains(e.target)) {
            notificationDropdown.classList.remove('active');
        }
    });

    // Hàm Hiển thị/Ẩn Bộ lọc nâng cao
    btnToggleAdvanced.addEventListener('click', () => {
        advancedFilters.classList.toggle('hidden');
        const icon = btnToggleAdvanced.querySelector('i');
        if (advancedFilters.classList.contains('hidden')) {
            icon.className = 'fas fa-sliders-h';
            btnToggleAdvanced.innerHTML = '<i class="fas fa-sliders-h"></i> Bộ lọc nâng cao';
        } else {
            icon.className = 'fas fa-times';
            btnToggleAdvanced.innerHTML = '<i class="fas fa-times"></i> Đóng bộ lọc';
        }
    });

    // ----------------------------------------------------------------------
    // 3. Xử lý Lọc và Hiển thị Bảng
    // ----------------------------------------------------------------------

    // Hàm render (vẽ) bảng khách hàng
    const renderCustomerTable = (filteredCustomers) => {
        customerTableBody.innerHTML = ''; // Xóa nội dung cũ

        if (filteredCustomers.length === 0) {
            noResultsMessage.style.display = 'block';
            return;
        }

        noResultsMessage.style.display = 'none';

        filteredCustomers.forEach(customer => {
            const row = document.createElement('tr');
            const statusClass = customer.status === 'Hoạt động' ? 'status-active' : 'status-inactive';
            const rankClass = 'rank-' + customer.rank.toLowerCase();
            const toggleIcon = customer.status === 'Hoạt động' ? 'lock' : 'unlock-alt';
            const toggleTitle = customer.status === 'Hoạt động' ? 'Khóa' : 'Mở khóa';

            row.setAttribute('data-id', customer.id);
            row.innerHTML = `
                <td>${customer.id}</td>
                <td>${customer.name}</td>
                <td>${customer.email}</td>
                <td>${customer.phone}</td>
                <td>${customer.address}</td>
                <td class="${rankClass}">${customer.rank}</td>
                <td><span class="status-badge ${statusClass}">${customer.status}</span></td>
                <td>${customer.created_at}</td>
                <td>
                    <button class="btn-action view-detail" title="Xem chi tiết" data-id="${customer.id}"><i class="fas fa-eye"></i></button>
                    <button class="btn-action edit-customer" title="Sửa thông tin" data-id="${customer.id}"><i class="fas fa-edit"></i></button>
                    <button class="btn-action toggle-status" title="${toggleTitle}" data-id="${customer.id}"><i class="fas fa-${toggleIcon}"></i></button>
                    <button class="btn-action delete-customer" title="Xóa khách hàng" data-id="${customer.id}"><i class="fas fa-trash"></i></button>
                </td>
            `;
            customerTableBody.appendChild(row);
        });

        // Gắn lại sự kiện cho các nút hành động (View/Edit)
        attachActionListeners();
    };

    // Hàm xử lý logic LỌC và TÌM KIẾM
    const applyFilters = () => {
        const searchText = searchInput.value.toLowerCase().trim();
        const statusValue = filterStatus.value;
        const rankValue = filterRank.value;
        const cityValue = filterCity.value.toLowerCase().trim();
        const dateFromValue = filterDateFrom.value;
        const dateToValue = filterDateTo.value;

        const filtered = customersData.filter(customer => {
            let matchSearch = true;
            let matchStatus = true;
            let matchRank = true;
            let matchCity = true;
            let matchDate = true;

            // Lọc theo từ khóa
            if (searchText) {
                matchSearch = customer.name.toLowerCase().includes(searchText) ||
                              customer.phone.toLowerCase().includes(searchText) ||
                              customer.email.toLowerCase().includes(searchText) ||
                              customer.id.toString().includes(searchText);
            }

            // Lọc theo Trạng thái
            if (statusValue !== 'All') {
                matchStatus = customer.status === statusValue;
            }

            // Lọc theo Hạng
            if (rankValue !== 'All') {
                // Giả định dữ liệu rank là chữ cái đầu viết hoa
                matchRank = customer.rank === rankValue;
            }
            
            // Lọc theo Tỉnh/Thành
            if (cityValue) {
                matchCity = customer.address.toLowerCase().includes(cityValue);
            }
            
            // Lọc theo Ngày tạo (From)
            if (dateFromValue) {
                matchDate = matchDate && (new Date(customer.created_at) >= new Date(dateFromValue));
            }

            // Lọc theo Ngày tạo (To)
            if (dateToValue) {
                matchDate = matchDate && (new Date(customer.created_at) <= new Date(dateToValue));
            }


            return matchSearch && matchStatus && matchRank && matchCity && matchDate;
        });

        renderCustomerTable(filtered);
    };

    // Gắn sự kiện cho các bộ lọc và tìm kiếm
    btnSearch.addEventListener('click', applyFilters);
    searchInput.addEventListener('input', applyFilters); // Tìm kiếm trực tiếp khi gõ
    filterStatus.addEventListener('change', applyFilters);
    filterRank.addEventListener('change', applyFilters);
    filterCity.addEventListener('input', applyFilters);
    filterDateFrom.addEventListener('change', applyFilters);
    filterDateTo.addEventListener('change', applyFilters);
    
    // Lần đầu tải trang
    renderCustomerTable(customersData); 

    // ----------------------------------------------------------------------
    // 4. Xử lý Modal (Xem/Sửa/Thêm)
    // ----------------------------------------------------------------------

    // Hàm mở modal
    const openModal = (customer = null) => {
        customerForm.reset(); // Reset form trước
        historyNotesSection.style.display = 'grid'; // Luôn hiện lịch sử/ghi chú khi edit/view
        
        if (customer) {
            modalTitle.textContent = 'CHỈNH SỬA KHÁCH HÀNG: ' + customer.name;
            customerId.value = customer.id;
            // Điền dữ liệu vào form
            document.getElementById('full-name').value = customer.name || '';
            document.getElementById('email').value = customer.email || '';
            document.getElementById('phone').value = customer.phone || '';
            // Giả định thêm trường dob, gender, city, notes vào dữ liệu mẫu nếu cần
            document.getElementById('address').value = customer.address || '';
            document.getElementById('status').value = customer.status || 'Hoạt động';
            displayRank.textContent = customer.rank || 'Mới';
            displayRank.className = `rank-badge rank-${(customer.rank || 'Mới').toLowerCase()}`;

            // Điền dữ liệu giả lập cho Lịch sử mua hàng (chỉ để minh họa)
            document.getElementById('purchase-history').value = `
                HD00125 - 2025-05-20 - 250,000đ
                HD00100 - 2025-04-15 - 180,000đ
                HD00050 - 2025-03-01 - 320,000đ
            `.trim();
        } else {
            modalTitle.textContent = 'THÊM KHÁCH HÀNG MỚI';
            customerId.value = '';
            displayRank.textContent = 'Mới';
            displayRank.className = 'rank-badge rank-moi';
            // Ẩn phần lịch sử khi thêm mới
            historyNotesSection.style.display = 'none'; 
        }

        customerModal.style.display = 'flex';
    };

    // Hàm đóng modal
    const closeModal = () => {
        customerModal.style.display = 'none';
    };

    // Gắn sự kiện cho các nút Đóng modal
    closeButtons.forEach(button => {
        button.addEventListener('click', closeModal);
    });

    // Đóng modal khi click ra ngoài
    window.addEventListener('click', (event) => {
        if (event.target === customerModal) {
            closeModal();
        }
    });

    // Mở modal Thêm mới
    btnAddCustomer.addEventListener('click', () => openModal(null));

    // Hàm gắn sự kiện cho các nút hành động (View/Edit)
    function attachActionListeners() {
        // Nút Sửa
        document.querySelectorAll('.edit-customer').forEach(button => {
            button.removeEventListener('click', handleEditCustomer); // Xóa sự kiện cũ
            button.addEventListener('click', handleEditCustomer); // Gắn sự kiện mới
        });
        
        // Nút Xem chi tiết (có thể dùng chung hàm openModal)
        document.querySelectorAll('.view-detail').forEach(button => {
            button.removeEventListener('click', handleEditCustomer); 
            button.addEventListener('click', handleEditCustomer);
        });

        // Nút Khóa/Mở khóa trạng thái (Demo)
        document.querySelectorAll('.toggle-status').forEach(button => {
            button.removeEventListener('click', handleToggleStatus);
            button.addEventListener('click', handleToggleStatus);
        });

        // Nút Xóa (Demo)
        document.querySelectorAll('.delete-customer').forEach(button => {
            button.removeEventListener('click', handleDeleteCustomer);
            button.addEventListener('click', handleDeleteCustomer);
        });
    }

    // Xử lý sự kiện Sửa/Xem
    function handleEditCustomer(e) {
        const id = e.currentTarget.getAttribute('data-id');
        const customer = customersData.find(c => c.id == id);
        if (customer) {
            openModal(customer);
        } else {
            alert('Không tìm thấy khách hàng!');
        }
    }

    // Xử lý sự kiện Khóa/Mở khóa (DEMO)
    function handleToggleStatus(e) {
        const id = e.currentTarget.getAttribute('data-id');
        const customerIndex = customersData.findIndex(c => c.id == id);
        if (customerIndex !== -1) {
            const currentStatus = customersData[customerIndex].status;
            customersData[customerIndex].status = currentStatus === 'Hoạt động' ? 'Ngưng' : 'Hoạt động';
            alert(`Đã cập nhật trạng thái cho KH ID ${id} thành: ${customersData[customerIndex].status}`);
            applyFilters(); // Render lại bảng
        }
    }

    // Xử lý sự kiện Xóa (DEMO)
    function handleDeleteCustomer(e) {
        const id = e.currentTarget.getAttribute('data-id');
        if (confirm(`Bạn có chắc chắn muốn xóa Khách hàng ID ${id} này?`)) {
            customersData = customersData.filter(c => c.id != id);
            alert(`Đã xóa khách hàng ID ${id}.`);
            applyFilters(); // Render lại bảng
        }
    }

    // Xử lý Submit Form (Thêm/Sửa) - (DEMO)
    customerForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const id = customerId.value;
        const name = document.getElementById('full-name').value;
        const email = document.getElementById('email').value;
        const phone = document.getElementById('phone').value;
        const statusValue = document.getElementById('status').value;
        const addressValue = document.getElementById('address').value;
        
        if (id) {
            // Logic SỬA
            const customerIndex = customersData.findIndex(c => c.id == id);
            if (customerIndex !== -1) {
                customersData[customerIndex].name = name;
                customersData[customerIndex].email = email;
                customersData[customerIndex].phone = phone;
                customersData[customerIndex].status = statusValue;
                customersData[customerIndex].address = addressValue;
                // Cần thêm logic gọi AJAX/fetch API để lưu vào DB thực tế
                alert('Cập nhật thông tin khách hàng thành công! (Chức năng DB cần được code)');
            }
        } else {
            // Logic THÊM MỚI
            const newId = customersData.length > 0 ? Math.max(...customersData.map(c => c.id)) + 1 : 1;
            const newCustomer = {
                id: newId,
                name: name,
                email: email,
                phone: phone,
                address: addressValue,
                role: 'USER',
                rank: 'Mới',
                status: statusValue,
                created_at: new Date().toISOString().slice(0, 10)
            };
            customersData.push(newCustomer);
            // Cần thêm logic gọi AJAX/fetch API để lưu vào DB thực tế
            alert('Thêm khách hàng mới thành công! (Chức năng DB cần được code)');
        }

        applyFilters(); // Render lại bảng để thấy thay đổi
        closeModal();
    });
});