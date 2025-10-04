// contact.js

document.addEventListener('DOMContentLoaded', () => {
    // Cập nhật số lượng giỏ hàng
    if (typeof updateCartBadge === 'function') {
        updateCartBadge();
    }

    const mapIframe = document.getElementById('google-map-iframe');
    const mapContainer = mapIframe ? mapIframe.parentElement : null;

    if (mapContainer && mapIframe) {
        
        // 1. Xử lý sự kiện Ctrl + Scroll (cho phép zoom)
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Control' || e.ctrlKey) {
                // Khi Ctrl được nhấn, bật tương tác và đổi cursor
                mapContainer.classList.add('interactive');
            }
        });

        document.addEventListener('keyup', (e) => {
            if (e.key === 'Control') {
                // Khi Ctrl được nhả, tắt tương tác
                mapContainer.classList.remove('interactive');
            }
        });

        // 2. Xử lý Kéo Chuột (Nhấn giữ chuột trái)
        let isDragging = false;

        mapContainer.addEventListener('mousedown', (e) => {
            // Chỉ bật tương tác khi chuột trái được nhấn và không có Ctrl
            if (e.button === 0 && !e.ctrlKey) { 
                mapContainer.classList.add('interactive');
                isDragging = true;
                // Đổi cursor thành dragging (nếu CSS đã định nghĩa :active)
                mapContainer.style.cursor = 'grabbing';
                mapContainer.style.cursor = '-webkit-grabbing';
            }
        });

        document.addEventListener('mouseup', () => {
            if (isDragging) {
                // Tắt tương tác khi nhả chuột (chỉ tắt khi không có Ctrl)
                if (!event.ctrlKey) { 
                    mapContainer.classList.remove('interactive');
                }
                isDragging = false;
                mapContainer.style.cursor = 'default';
            }
        });
        
        // Thiết lập cursor mặc định cho container để gợi ý hành động
        mapContainer.style.cursor = 'pointer'; 
    }
});