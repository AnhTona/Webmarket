/**
 * utils.js
 * Utilities và helpers chung cho admin panel
 */

const AdminUtils = (() => {
    'use strict';

    // ===== Constants =====
    const DEBOUNCE_DELAY = 300;
    const TOAST_DURATION = 5000;

    // ===== Number Formatter =====
    const numberFormatter = new Intl.NumberFormat('vi-VN');

    /**
     * Format number to Vietnamese currency
     */
    function formatCurrency(amount) {
        return numberFormatter.format(amount) + ' đ';
    }

    /**
     * Debounce function
     */
    function debounce(func, wait = DEBOUNCE_DELAY) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Sanitize HTML to prevent XSS
     */
    function sanitizeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    /**
     * Safe innerHTML setter with sanitization
     */
    function setInnerHTML(element, html) {
        if (!element) return;
        const temp = document.createElement('div');
        temp.innerHTML = html;
        element.innerHTML = temp.innerHTML;
    }

    /**
     * Show toast notification
     */
    function showToast(message, type = 'info') {
        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-blue-500'
        };

        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg z-50 flex items-center gap-3 animate-slide-in`;
        toast.innerHTML = `
            <i class="fas ${icons[type]}"></i>
            <span>${sanitizeHTML(message)}</span>
            <button onclick="this.parentElement.remove()" class="ml-4 hover:opacity-80">
                <i class="fas fa-times"></i>
            </button>
        `;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.transition = 'opacity 0.3s, transform 0.3s';
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, TOAST_DURATION);

        return toast;
    }

    /**
     * Confirm dialog with custom styling
     */
    function confirm(message, title = 'Xác nhận') {
        return new Promise((resolve) => {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 animate-scale-in">
                    <div class="p-6 border-b">
                        <h3 class="text-xl font-bold text-gray-800">${sanitizeHTML(title)}</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600">${sanitizeHTML(message)}</p>
                    </div>
                    <div class="p-6 border-t flex justify-end gap-3">
                        <button class="btn-cancel px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                            Hủy
                        </button>
                        <button class="btn-confirm px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition">
                            Xác nhận
                        </button>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            modal.querySelector('.btn-cancel').onclick = () => {
                modal.remove();
                resolve(false);
            };

            modal.querySelector('.btn-confirm').onclick = () => {
                modal.remove();
                resolve(true);
            };

            modal.onclick = (e) => {
                if (e.target === modal) {
                    modal.remove();
                    resolve(false);
                }
            };
        });
    }

    /**
     * Loading spinner
     */
    function showLoading(message = 'Đang xử lý...') {
        const loading = document.createElement('div');
        loading.id = 'admin-loading';
        loading.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        loading.innerHTML = `
            <div class="bg-white rounded-lg p-6 flex flex-col items-center gap-4">
                <i class="fas fa-spinner fa-spin text-4xl text-red-600"></i>
                <p class="text-gray-700 font-medium">${sanitizeHTML(message)}</p>
            </div>
        `;
        document.body.appendChild(loading);
        return loading;
    }

    function hideLoading() {
        const loading = document.getElementById('admin-loading');
        if (loading) loading.remove();
    }

    /**
     * AJAX wrapper with error handling
     */
    async function ajax(url, options = {}) {
        const loading = options.showLoading !== false ? showLoading() : null;

        try {
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    ...options.headers
                },
                ...options
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const contentType = response.headers.get('content-type');
            let data;

            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                const text = await response.text();
                try {
                    data = JSON.parse(text);
                } catch {
                    throw new Error('Server không trả về JSON hợp lệ');
                }
            }

            return { success: true, data };

        } catch (error) {
            console.error('AJAX Error:', error);
            showToast(error.message || 'Lỗi kết nối máy chủ', 'error');
            return { success: false, error: error.message };

        } finally {
            if (loading) hideLoading();
        }
    }

    /**
     * Validate file upload
     */
    function validateImage(file, maxSize = 5 * 1024 * 1024) {
        if (!file) {
            return { valid: false, message: 'Chưa chọn file' };
        }

        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            return { valid: false, message: 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP)' };
        }

        if (file.size > maxSize) {
            const maxMB = (maxSize / 1024 / 1024).toFixed(1);
            return { valid: false, message: `File quá lớn (tối đa ${maxMB}MB)` };
        }

        return { valid: true };
    }

    /**
     * Update URL query params without reload
     */
    function updateURLParams(params) {
        const url = new URL(window.location);
        Object.entries(params).forEach(([key, value]) => {
            if (value === null || value === undefined || value === '') {
                url.searchParams.delete(key);
            } else {
                url.searchParams.set(key, value);
            }
        });
        window.history.pushState({}, '', url);
    }

    /**
     * Get URL param
     */
    function getURLParam(key, defaultValue = null) {
        const params = new URLSearchParams(window.location.search);
        return params.get(key) || defaultValue;
    }

    /**
     * Escape HTML entities
     */
    function escapeHTML(str) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#x27;',
            '/': '&#x2F;'
        };
        return String(str).replace(/[&<>"'/]/g, (s) => map[s]);
    }

    // ===== Export Public API =====
    return {
        formatCurrency,
        debounce,
        sanitizeHTML,
        setInnerHTML,
        showToast,
        confirm,
        showLoading,
        hideLoading,
        ajax,
        validateImage,
        updateURLParams,
        getURLParam,
        escapeHTML
    };
})();

// Make available globally
window.AdminUtils = AdminUtils;