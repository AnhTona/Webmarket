<?php
include __DIR__ . '/../../db.php';
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';

if (!empty($keyword) && isset($conn)) {
    // 1. Lấy địa chỉ IP
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // 2. Chuẩn bị câu lệnh INSERT. Đã thay tên bảng thành `lichsutimkiem`
    $sql_insert = "INSERT INTO lichsutimkiem (keyword, search_time, user_ip) VALUES (?, NOW(), ?)";
    
    if ($stmt_insert = $conn->prepare($sql_insert)) {
        // 3. Bind tham số
        $stmt_insert->bind_param("ss", $keyword, $ip_address);
        
        // 4. Thực thi câu lệnh
        if ($stmt_insert->execute()) {
            // Lịch sử tìm kiếm đã được lưu
        } else {
            error_log("Lỗi khi lưu từ khóa tìm kiếm vào lichsutimkiem: " . $stmt_insert->error);
        }
        $stmt_insert->close();
    } else {
        error_log("Chuẩn bị câu lệnh INSERT thất bại: " . $conn->error);
    }
}

// --- KẾT THÚC PHẦN CODE BỔ SUNG ---
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết Quả Tìm Kiếm - Trà & Bánh Trung Thu</title>
    <link rel="stylesheet" href="../css/products.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/search.css">
             <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"> <!-- icon tìm kiếm-->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7ISGqrIDrxlwX+uYwg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <header class="header">
        <div class="logo">
            <img src="image/logo.png" alt="Logo Trà & Bánh Trung Thu">
        </div>
        <nav>
            <ul>
                <li><a href="home.php">Trang Chủ</a></li>
                <li><a href="products.php">Sản Phẩm</a></li>
                <li><a href="promo.php">Khuyến Mãi</a></li>
                <li><a href="contact.php">Liên Hệ</a></li>
            </ul>
        </nav>
        <div class="search-container">
            <form class="search-form" action="search_results.php" method="GET">
                <input type="text" id="search-input" name="keyword" placeholder="Tìm kiếm sản phẩm..." value="<?php echo htmlspecialchars($keyword); ?>">
                  <button type="submit" class="search-submit-btn"> <i class="fa-solid fa-magnifying-glass"></i> </button>
            </form>
            <div class="autocomplete-results" id="autocomplete-results"></div>
        </div>
        <div class="icons">
            <a href="cart.php" class="cart-icon">🛒 <span class="cart-count"></span></a>
            <a href="login.php">👤</a>
        </div>
    </header>
    <main class="products-container">
        <div class="breadcrumb">
            <a href="home.php">Trang Chủ</a> / <span>Kết Quả Tìm Kiếm: "<?php echo htmlspecialchars($keyword); ?>"</span>
        </div>
        <div class="content-wrapper">
            <div class="main-content">
                <div class="title-filter">
                    <h1>KẾT QUẢ TÌM KIẾM</h1>
                </div>
                <div class="product-grid" id="product-grid"></div>
                <div id="no-products-message">Xin lỗi, không tìm thấy sản phẩm nào với từ khóa "<?php echo htmlspecialchars($keyword); ?>"</div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script src="../js/search.js"></script>
    <!-- Overlay (dùng để dim nền & đóng minicart khi click) -->
    <div id="overlay" aria-hidden="true"></div>

    <!-- Minicart -->
    <div id="mini-cart" aria-hidden="true" role="dialog" aria-label="Giỏ hàng mini">
        <div class="minicart-header">
            <span class="title">Giỏ hàng</span>
            <button type="button" class="close-btn" aria-label="Đóng mini cart">&times;</button>
        </div>
        <div id="minicart-items-list" class="minicart-items" aria-live="polite">
            <!-- items sẽ được JS chèn vào đây -->
        </div>
        <div class="minicart-footer">
            <div class="minicart-total-row">
                <span id="minicart-item-count">0 sản phẩm</span>
                <span id="minicart-total-price">0 VND</span>
            </div>
            <a href="cart.php" class="btn-view-cart">Xem giỏ hàng</a>
        </div>
    </div>

    <script>
        // Lấy dữ liệu sản phẩm trực tiếp từ PHP với mysqli
        <?php
        $products = [];

        // Chuẩn bị truy vấn
        $sql = "SELECT s.*, GROUP_CONCAT(DISTINCT dm.TenDanhMuc SEPARATOR ', ') AS categories
                FROM sanpham s
                LEFT JOIN sanpham_khuyenmai sd ON s.MaSanPham = sd.MaSanPham
                LEFT JOIN danhmuc dm ON sd.MaDanhMuc = dm.MaDanhMuc
                WHERE s.TenSanPham LIKE ?
                GROUP BY s.MaSanPham";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $searchKeyword);
            $searchKeyword = "%$keyword%";
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $isPromo = (int)$row["isPromo"];
                    $oldPrice = $isPromo && $row["GiaCu"] ? (int)$row["GiaCu"] : null;
                    if (!$oldPrice && $isPromo) {
                        $oldPrice = (int)($row["Gia"] * (1 + (rand(10, 30) / 100)));
                    }
                    $products[] = [
                        "id" => (int)$row["MaSanPham"],
                        "name" => $row["TenSanPham"],
                        "price" => (int)$row["Gia"],
                        "oldPrice" => $oldPrice,
                        "image" => $row["HinhAnh"] ?: "image/sp1.jpg",
                        "category" => $row["categories"] ?: $row["danh_muc"],
                        "subCategory" => $row["loai"] ?: "",
                        "popularity" => rand(50, 500),
                        "newProduct" => (bool)rand(0, 1),
                        "isPromo" => $isPromo
                    ];
                }
                $stmt->close();
            } else {
                echo "console.error('Lỗi truy vấn: " . $conn->error . "');\n";
                $products = [];
            }
        } else {
            echo "console.error('Chuẩn bị truy vấn thất bại: " . $conn->error . "');\n";
            $products = [];
        }

        echo "const products = " . json_encode($products, JSON_UNESCAPED_UNICODE) . ";\n";
        echo "console.log('Dữ liệu sản phẩm:', products);\n";
        ?>

        // Hàm hiển thị sản phẩm
        function displayProducts(products) {
            const productGrid = document.getElementById('product-grid');
            const noProductsMessage = document.getElementById('no-products-message');
            productGrid.innerHTML = '';

            if (products.length === 0) {
                noProductsMessage.style.display = 'block';
                return;
            }
            noProductsMessage.style.display = 'none';

            products.forEach(product => {
                const productCard = document.createElement('div');
                productCard.classList.add('product-card');
                productCard.innerHTML = `
                    <img src="${product.image || 'image/sp1.jpg'}" alt="${product.name}" onerror="this.src='image/sp1.jpg';">
                    <h3>${product.name}</h3>
                    <div class="product-info">
                        ${product.oldPrice ? `<p class="old-price">${product.oldPrice.toLocaleString('vi-VN')} VNĐ</p>` : ''}
                        <p class="price ${product.oldPrice ? 'promo-price' : ''}">${product.price.toLocaleString('vi-VN')} VNĐ</p>
                        <a href="#" class="btn-add" 
                           data-id="${product.id}" 
                           data-name="${product.name}" 
                           data-price="${product.price}" 
                           data-image="${product.image || 'image/sp1.jpg'}">
                           Thêm vào giỏ hàng <i class="fa-solid fa-basket-shopping"></i>
                        </a>
                    </div>
                `;
                productGrid.appendChild(productCard);
            });
        }

        // Khởi tạo và quản lý giỏ hàng
        let cart = JSON.parse(localStorage.getItem('cart')) || [];

        function readCart() { return JSON.parse(localStorage.getItem('cart')) || []; }
        function writeCart(cart) { localStorage.setItem('cart', JSON.stringify(cart)); updateCartBadge(); }
        function updateCartBadge() {
            const cart = readCart();
            const count = cart.reduce((sum, item) => sum + (item.quantity || 1), 0);
            const badge = document.querySelector('.cart-count');
            if (badge) {
                badge.textContent = count > 0 ? count : '';
                badge.style.display = count > 0 ? 'inline-block' : 'none';
            }
        }

        // Thêm sản phẩm vào giỏ hàng (loại bỏ alert)
        function addToCart(productId, name, price, image) {
            const existingItem = cart.find(item => item.id === productId);
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({ id: productId, name, price, image, quantity: 1 });
            }
            writeCart(cart);
            renderMiniCart();
            openMiniCart();
        }

        // Xử lý sự kiện thêm vào giỏ hàng
        document.addEventListener('click', (e) => {
            const addBtn = e.target.closest('.btn-add');
            if (addBtn) {
                e.preventDefault();
                const productId = addBtn.getAttribute('data-id');
                const name = addBtn.getAttribute('data-name');
                const price = parseFloat(addBtn.getAttribute('data-price'));
                const image = addBtn.getAttribute('data-image');
                addToCart(productId, name, price, image);
            }
        });

        // Minicart logic
        const overlayEl = document.getElementById('overlay');
        const miniCartEl = document.getElementById('mini-cart');
        const miniCartItemsEl = document.getElementById('minicart-items-list');
        const miniCountEl = document.getElementById('minicart-item-count');
        const miniTotalEl = document.getElementById('minicart-total-price');
        const closeBtn = document.querySelector('#mini-cart .close-btn');

        function openMiniCart() {
            overlayEl.classList.add('show');
            miniCartEl.style.display = 'flex';
            setTimeout(() => miniCartEl.classList.add('show'), 10);
            document.body.classList.add('no-scroll');
        }

        function closeMiniCart() {
            miniCartEl.classList.remove('show');
            overlayEl.classList.remove('show');
            document.body.classList.remove('no-scroll');
            setTimeout(() => miniCartEl.style.display = 'none', 350);
        }

        overlayEl.addEventListener('click', closeMiniCart);
        if (closeBtn) closeBtn.addEventListener('click', closeMiniCart);
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && miniCartEl.classList.contains('show')) closeMiniCart();
        });

        function renderMiniCart() {
            const cart = readCart();
            miniCartItemsEl.innerHTML = cart.length === 0 ? '<p style="text-align:center;color:#777;padding:20px">Giỏ hàng trống</p>' : '';
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
                    <button class="minicart-item-remove" data-id="${item.id}"><i class="fa-solid fa-trash remove-icon"></i></button>
                `;
                miniCartItemsEl.appendChild(row);
            });
            miniCountEl.textContent = `${cart.length} sản phẩm`;
            miniTotalEl.textContent = `${total.toLocaleString('vi-VN')} VND`;
        }

        miniCartItemsEl.addEventListener('click', (e) => {
            const btn = e.target.closest('.minicart-item-remove');
            if (btn) {
                const id = btn.getAttribute('data-id');
                let cart = readCart().filter(item => String(item.id) !== String(id));
                writeCart(cart);
                renderMiniCart();
            }
        });

        // Hiển thị sản phẩm khi trang tải
        document.addEventListener('DOMContentLoaded', () => {
            displayProducts(products);
            updateCartBadge();
            renderMiniCart();
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>