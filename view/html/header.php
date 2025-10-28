<header class="header">
    <div class="logo">
        <a href="/Webmarket/home">
            <img src="/Webmarket/image/logo.webp" alt="Logo Trà & Bánh Trung Thu">
        </a>
    </div>
    <nav>
        <ul>
            <li><a href="/Webmarket/home">Trang Chủ</a></li>
            <li><a href="/Webmarket/products">Sản Phẩm</a></li>
            <li><a href="/Webmarket/contact">Liên Hệ</a></li>
        </ul>
    </nav>
    <div class="search-container">
        <form class="search-form" action="/Webmarket/search" method="GET">
            <input type="text" id="search-input" name="keyword" placeholder="Tìm kiếm sản phẩm...">
            <button type="submit" class="search-submit-btn"> <i class="fa-solid fa-magnifying-glass"></i> </button>
        </form>
        <div class="autocomplete-results" id="autocomplete-results"></div>
    </div>
    <div class="icons">
        <a href="/Webmarket/cart" class="cart-icon">🛒 <span class="cart-count"></span></a>
        <a href="/Webmarket/login">👤</a>
    </div>
</header>
<script src="/Webmarket/view/js/search.js"></script>