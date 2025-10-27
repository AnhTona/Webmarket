class AdminBase {
    constructor() {
        this.sidebar = document.getElementById('sidebar');
        this.menuToggle = document.getElementById('menu-toggle');
        this.notificationBell = document.getElementById('notification-bell');
        this.notificationDropdown = document.getElementById('notification-dropdown');

        this.initSidebar();
        this.initNotifications();
        this.initFlashMessages();
    }

    /**
     * Initialize sidebar toggle
     */
    initSidebar() {
        this.menuToggle?.addEventListener('click', () => {
            this.sidebar?.classList.toggle('-translate-x-full');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 768) {
                if (!this.sidebar?.contains(e.target) &&
                    !this.menuToggle?.contains(e.target) &&
                    !this.sidebar?.classList.contains('-translate-x-full')) {
                    this.sidebar?.classList.add('-translate-x-full');
                }
            }
        });
    }

    /**
     * Initialize notification dropdown
     */
    initNotifications() {
        this.notificationBell?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.notificationDropdown?.classList.toggle('active');
        });

        // Close notification when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.notificationBell?.contains(e.target) &&
                !this.notificationDropdown?.contains(e.target)) {
                this.notificationDropdown?.classList.remove('active');
            }
        });

        // Mark as read when clicking on notification
        this.notificationDropdown?.addEventListener('click', (e) => {
            const notifItem = e.target.closest('.notification-item');
            if (notifItem) {
                notifItem.style.opacity = '0.6';
                // TODO: Send AJAX to mark as read
            }
        });
    }

    /**
     * Initialize flash messages auto-hide
     */
    initFlashMessages() {
        const flashMessages = document.querySelectorAll('[id^="flash-"]');
        flashMessages.forEach(msg => {
            setTimeout(() => {
                msg.style.transition = 'opacity 0.5s, transform 0.5s';
                msg.style.opacity = '0';
                msg.style.transform = 'translateX(100%)';
                setTimeout(() => msg.remove(), 500);
            }, 5000);
        });
    }

    /**
     * Modal helper class
     */
    createModal(id, title) {
        return new AdminModal(id, title);
    }
}

/**
 * Modal helper class
 */
class AdminModal {
    constructor(id, title = '') {
        this.modal = document.getElementById(id);
        this.title = title;
        this.initCloseButtons();
    }

    initCloseButtons() {
        if (!this.modal) return;

        // Close buttons
        this.modal.querySelectorAll('.close-button').forEach(btn => {
            btn.addEventListener('click', () => this.close());
        });

        // Click outside to close
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.close();
            }
        });

        // ESC key to close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen()) {
                this.close();
            }
        });
    }

    open() {
        if (!this.modal) return;

        const titleEl = this.modal.querySelector('#modal-title, .modal-title, h2');
        if (titleEl && this.title) {
            titleEl.textContent = this.title;
        }

        this.modal.style.display = 'flex';
        this.modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevent body scroll
    }

    close() {
        if (!this.modal) return;

        this.modal.style.display = 'none';
        this.modal.classList.remove('active');
        document.body.style.overflow = ''; // Restore body scroll
    }

    isOpen() {
        return this.modal?.classList.contains('active');
    }

    setContent(html) {
        const content = this.modal?.querySelector('.modal-body, .modal-content > div');
        if (content) {
            AdminUtils.setInnerHTML(content, html);
        }
    }
}

// Initialize base functionality on all pages
document.addEventListener('DOMContentLoaded', () => {
    window.adminBase = new AdminBase();
});