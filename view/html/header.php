<header class="header">
    <div class="logo">
        <img src="../../image/logo.webp" alt="Logo Trà & Bánh Trung Thu">
    </div>
    <nav>
        <ul>
            <li><a href="home.php">Trang Chủ</a></li>
            <li><a href="products.php">Sản Phẩm</a></li>
            <li><a href="contact.php">Liên Hệ</a></li>
        </ul>
    </nav>
    <div class="search-container">
        <form class="search-form" action="search_results.php" method="GET">
            <input type="text" id="search-input" name="keyword" placeholder="Tìm kiếm sản phẩm...">
            <button type="submit" class="search-submit-btn"> <i class="fa-solid fa-magnifying-glass"></i> </button>
        </form>
        <div class="autocomplete-results" id="autocomplete-results"></div>
    </div>
    <div class="icons">
        <a href="cart.php" class="cart-icon">🛒 <span class="cart-count"></span></a>
        <a href="login.php">👤</a>
    </div>
</header>
<script src="../js/search.js"></script> <!-- Thêm file search.js -->