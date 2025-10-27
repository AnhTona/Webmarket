class AdminTemplate {
    constructor() {
        this.sidebar = document.getElementById('sidebar');
        this.menuToggle = document.getElementById('menu-toggle');
        this.mainLayout = document.getElementById('main-layout');
        this.notificationBell = document.getElementById('notification-bell');
        this.notificationDropdown = document.getElementById('notification-dropdown');

        this._onDocClick = this._onDocClick.bind(this);
        this._onBellClick = this._onBellClick.bind(this);
        this._onKeydown = this._onKeydown.bind(this);

        this.init();
    }

    init() {
        this.setupSidebar();
        this.setupNotifications();
    }

    setupSidebar() {
        if (!this.menuToggle || !this.sidebar) return;

        this.menuToggle.addEventListener('click', () => this.toggleSidebar());

        // Đóng sidebar khi click ngoài (mobile)
        this.mainLayout?.addEventListener('click', (event) => {
            if (
                window.innerWidth < 768 &&
                !this.sidebar.contains(event.target) &&
                !this.menuToggle.contains(event.target) &&
                !this.sidebar.classList.contains('-translate-x-full')
            ) {
                this.toggleSidebar();
            }
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                this.sidebar.classList.remove('-translate-x-full');
            }
        });

        if (window.innerWidth >= 768) {
            this.sidebar.classList.remove('fixed', 'shadow-xl');
            this.sidebar.classList.add('relative');
        }
    }

    toggleSidebar() {
        this.sidebar?.classList.toggle('-translate-x-full');
    }

    setupNotifications() {
        if (!this.notificationBell || !this.notificationDropdown) return;

        this.notificationBell.addEventListener('click', this._onBellClick);
        document.addEventListener('click', this._onDocClick);
        document.addEventListener('keydown', this._onKeydown);
    }

    _onBellClick(e) {
        e.stopPropagation();
        const isOpen = this.notificationDropdown.classList.toggle('active');
        if (isOpen) this.animateBell();
    }

    _onDocClick(e) {
        if (
            !this.notificationBell?.contains(e.target) &&
            !this.notificationDropdown?.contains(e.target)
        ) {
            this.closeNotificationDropdown();
        }
    }

    _onKeydown(e) {
        if (e.key === 'Escape') this.closeNotificationDropdown();
    }

    closeNotificationDropdown() {
        this.notificationDropdown?.classList.remove('active');
    }

    animateBell() {
        const bellIcon = this.notificationBell?.querySelector('i');
        if (!bellIcon) return;
        bellIcon.classList.add('bell-animate');
        setTimeout(() => bellIcon.classList.remove('bell-animate'), 500);
    }
}

// ===== Notification Manager =====
class NotificationManager {
    constructor(config = {}) {
        this.refreshInterval = config.refreshInterval || 30000; // 30s
        this.apiEndpoint = config.apiEndpoint || '../controller/get_notifications.php';
        this.maxItems = config.maxItems || 10;

        this.lastCount = 0;
        this.isDropdownOpen = false;
        this.refreshTimer = null;
        this.inflight = null;          // AbortController
        this.etag = null;              // server ETag nếu có
        this.lastDataHash = '';        // để tránh re-render
        this.backoffMs = 0;            // exponential backoff khi lỗi

        this._boundVisibility = this._onVisibilityChange.bind(this);

        this.init();
    }

    init() {
        // Lấy count ban đầu từ badge (nếu có)
        const badge = document.querySelector('.notification-badge');
        if (badge) this.lastCount = parseInt(badge.textContent) || 0;

        // Theo dõi mở/đóng dropdown
        const bell = document.getElementById('notification-bell');
        const dropdown = document.getElementById('notification-dropdown');
        bell?.addEventListener('click', () => {
            this.isDropdownOpen = dropdown?.classList.contains('active');
        });
        document.addEventListener('click', (e) => {
            if (!bell?.contains(e.target) && !dropdown?.contains(e.target)) {
                this.isDropdownOpen = false;
            }
        });

        // Auto-refresh + Visibility API
        document.addEventListener('visibilitychange', this._boundVisibility);
        this.startAutoRefresh();
    }

    destroy() {
        this.stopAutoRefresh();
        document.removeEventListener('visibilitychange', this._boundVisibility);
        this._abortInflight();
    }

    _onVisibilityChange() {
        if (document.hidden) {
            this.stopAutoRefresh();
        } else {
            this.startAutoRefresh(true); // resume ngay
        }
    }

    startAutoRefresh(runImmediately = true) {
        this.stopAutoRefresh();
        if (runImmediately) this.refreshNotifications();
        this.refreshTimer = setInterval(() => {
            if (!document.hidden && !this.isDropdownOpen) this.refreshNotifications();
        }, this.refreshInterval);
    }

    stopAutoRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
        }
    }

    _abortInflight() {
        if (this.inflight) {
            this.inflight.abort();
            this.inflight = null;
        }
    }

    _stableHash(obj) {
        try {
            return JSON.stringify(obj);
        } catch {
            return String(Math.random());
        }
    }

    async refreshNotifications() {
        // Nếu dropdown đang mở, tránh nhảy UI
        if (this.isDropdownOpen) return;

        this._abortInflight();
        this.inflight = new AbortController();

        // Backoff nếu trước đó lỗi
        if (this.backoffMs > 0) {
            await new Promise((r) => setTimeout(r, this.backoffMs));
        }

        try {
            const url = new URL(this.apiEndpoint, location.href);
            url.searchParams.set('limit', String(this.maxItems));

            const headers = {};
            if (this.etag) headers['If-None-Match'] = this.etag;

            const resp = await fetch(url.toString(), {
                method: 'GET',
                headers,
                signal: this.inflight.signal,
                cache: 'no-store'
            });

            // Nếu server hỗ trợ ETag và không đổi
            if (resp.status === 304) {
                this._resetBackoff();
                return;
            }

            if (!resp.ok) throw new Error(`HTTP ${resp.status}`);

            const data = await resp.json();

            // Lưu ETag (nếu có)
            const et = resp.headers.get('ETag');
            if (et) this.etag = et;

            if (!data || data.success !== true) {
                // API không thành công: không crash, chỉ log
                console.warn('Notification API returned non-success payload');
                this._resetBackoff();
                return;
            }

            // Chỉ cập nhật UI khi dữ liệu đổi
            const nextHash = this._stableHash({ c: data.count, n: data.notifications });
            if (nextHash !== this.lastDataHash) {
                this.updateNotificationUI(data);

                // Toast khi có thông báo mới
                if (typeof data.count === 'number' && data.count > this.lastCount) {
                    this.showNewNotificationToast(data.count - this.lastCount);
                }

                this.lastDataHash = nextHash;
                this.lastCount = data.count || 0;
            }

            this._resetBackoff();
        } catch (err) {
            if (err.name === 'AbortError') return; // bị huỷ là bình thường
            console.error('Error refreshing notifications:', err);
            this._increaseBackoff();
        } finally {
            this.inflight = null;
        }
    }

    _resetBackoff() {
        this.backoffMs = 0;
    }

    _increaseBackoff() {
        // 0 -> 2s -> 4s -> 8s (tối đa 30s)
        this.backoffMs = Math.min(this.backoffMs ? this.backoffMs * 2 : 2000, 30000);
    }

    updateNotificationUI(data) {
        this.updateBadge(data.count);
        this.updateHeader(data.count);
        // Khi dropdown đang mở đã return sớm phía trên
        this.updateNotificationList(Array.isArray(data.notifications) ? data.notifications : []);
    }

    updateBadge(count) {
        const badge = document.querySelector('.notification-badge');
        const bell = document.getElementById('notification-bell');

        if (count > 0) {
            const text = count > 99 ? '99+' : String(count);
            if (badge) {
                if (badge.textContent !== text) badge.textContent = text;
            } else if (bell) {
                const newBadge = document.createElement('span');
                newBadge.className = 'notification-badge';
                newBadge.textContent = text;
                bell.appendChild(newBadge);
            }
        } else {
            badge?.remove();
        }
    }

    updateHeader(count) {
        const header = document.querySelector('.notification-header span:last-child');
        if (header) {
            const newText = `${count || 0} mới`;
            if (header.textContent !== newText) header.textContent = newText;
        }
    }

    updateNotificationList(notifications) {
        const body = document.querySelector('.notification-body');
        if (!body) return;

        if (!notifications.length) {
            body.innerHTML = this.getEmptyTemplate();
            return;
        }

        // render thuần HTML (giữ nguyên kiểu cũ)
        const html = notifications.map((n) => this.getNotificationTemplate(n)).join('');
        // tránh thrash: chỉ set nếu thay đổi
        if (body._lastHTML !== html) {
            body.innerHTML = html;
            body._lastHTML = html;
        }
    }

    getNotificationTemplate(notif) {
        // Giữ nguyên lớp & cấu trúc cũ để không cần đổi CSS
        const icon = notif?.icon || 'fa-bell';
        const color = notif?.color || 'bg-blue-500';
        const msg = notif?.message || 'Thông báo';
        const time = notif?.time || '';
        const order = notif?.order_id;

        return `
      <div class="notification-item">
        <div class="notif-icon ${color}">
          <i class="fas ${icon}"></i>
        </div>
        <div class="notif-content">
          <div class="notif-message">${msg}</div>
          <div class="notif-time">
            <i class="far fa-clock"></i>
            ${time}
          </div>
          ${order ? `
            <div class="notif-action">
              <a href="orders.php?order_id=${encodeURIComponent(order)}">
                Xem chi tiết <i class="fas fa-arrow-right text-xs"></i>
              </a>
            </div>` : ''}
        </div>
      </div>
    `;
    }

    getEmptyTemplate() {
        return `
      <div class="notification-empty">
        <i class="fa-solid fa-bell-slash"></i>
        <p class="text-sm font-medium">Không có thông báo mới</p>
        <p class="text-xs mt-1">Bạn đã xem hết các thông báo</p>
      </div>
    `;
    }

    showNewNotificationToast(count) {
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
      <div class="toast-content">
        <div class="toast-icon"><i class="fas fa-bell"></i></div>
        <div class="toast-text">
          <div class="toast-title">Thông báo mới!</div>
          <div class="toast-message">Bạn có ${count} thông báo mới</div>
        </div>
        <button class="toast-close" aria-label="Đóng thông báo">
          <i class="fas fa-times"></i>
        </button>
      </div>
    `;

        document.body.appendChild(toast);

        toast.querySelector('.toast-close')?.addEventListener('click', () => this.removeToast(toast));

        // Animate bell
        const bell = document.querySelector('#notification-bell i');
        if (bell) {
            bell.classList.add('bell-animate');
            setTimeout(() => bell.classList.remove('bell-animate'), 500);
        }

        setTimeout(() => this.removeToast(toast), 5000);
    }

    removeToast(toast) {
        if (!toast) return;
        toast.classList.add('hiding');
        setTimeout(() => toast.remove(), 300);
    }
}

// ===== Khởi tạo khi DOM sẵn sàng =====
document.addEventListener('DOMContentLoaded', () => {
    window.adminTemplate = new AdminTemplate(); // giữ nguyên hành vi giao diện
    window.notificationManager = new NotificationManager({
        refreshInterval: 30000,
        apiEndpoint: '../html/get_notifications.php',
        maxItems: 10
    });
});
