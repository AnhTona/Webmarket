// view/js/search.js
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search-input');
    const autocompleteResults = document.getElementById('autocomplete-results');
    const searchForm = document.querySelector('.search-form');

    let timeout;

    // Autocomplete: Gọi AJAX khi gõ input (debounce 300ms)
    searchInput.addEventListener('input', () => {
        clearTimeout(timeout);
        const keyword = searchInput.value.trim();
        
        // Ẩn kết quả nếu keyword rỗng
        if (keyword.length === 0) {
            autocompleteResults.innerHTML = '';
            autocompleteResults.style.display = 'none';
            return;
        }

        if (keyword.length > 0) {
            timeout = setTimeout(() => {
                // Tên file PHP xử lý gợi ý
                fetch(`search_suggestions.php?keyword=${encodeURIComponent(keyword)}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        autocompleteResults.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(product => {
                                const link = document.createElement('a');
                                // Gán keyword của gợi ý vào link
                                const searchUrl = `search_results.php?keyword=${encodeURIComponent(product.name)}`; 
                                
                                link.href = searchUrl;
                                link.textContent = product.name;
                                link.dataset.id = product.id;
                                
                                // Nếu click vào gợi ý -> chuyển hướng ngay
                                link.addEventListener('click', (e) => {
                                    // Không cần e.preventDefault() vì đã có link.href
                                });
                                
                                autocompleteResults.appendChild(link);
                            });
                            autocompleteResults.style.display = 'block';
                        } else {
                            const noResult = document.createElement('div');
                            noResult.classList.add('no-results');
                            noResult.textContent = 'Không có kết quả';
                            autocompleteResults.appendChild(noResult);
                            autocompleteResults.style.display = 'block';
                        }
                    })
                    .catch(error => {
                        console.error('Lỗi AJAX:', error);
                        autocompleteResults.style.display = 'none';
                    });
            }, 300);
        }
    });

    // Chuyển hướng đến search_results.php khi nhấn Enter hoặc nút submit
    searchForm.addEventListener('submit', (e) => {
        const keyword = searchInput.value.trim();
        if (keyword) {
            // Để form submit tự nhiên đến search_results.php?keyword=...
            autocompleteResults.style.display = 'none';
        } else {
            e.preventDefault(); // Ngăn submit rỗng
        }
    });

    // Đóng autocomplete khi click ra ngoài
    document.addEventListener('click', (e) => {
        // Kiểm tra xem click có phải là trong thanh search container không
        const searchContainer = searchInput.closest('.search-container');
        if (searchContainer && !searchContainer.contains(e.target)) {
            autocompleteResults.style.display = 'none';
        }
    });
});