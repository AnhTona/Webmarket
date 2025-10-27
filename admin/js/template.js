/**
 * template.js
 * Global template functionality
 */

(function() {
    'use strict';

    const NOTIFICATION_REFRESH_INTERVAL = 30000; // 30 seconds

    function refreshNotifications() {
        if (document.hidden) return;

        AdminUtils.ajax('../controller/get_notifications.php', { showLoading: false })
            .then(result => {
                if (result.success && result.data.ok) {
                    updateNotificationBadge(result.data.count);
                    updateNotificationList(result.data.notifications);
                }
            })
            .catch(err => console.error('Failed to refresh notifications:', err));
    }

    function updateNotificationBadge(count) {
        const badge = document.querySelector('.notification-badge, #notification-bell span');
        if (badge) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = count > 0 ? 'inline-block' : 'none';
        }
    }

    function updateNotificationList(notifications) {
        const body = document.querySelector('.notification-body');
        if (!body || !notifications) return;

        // TODO: Implement notification list update
    }

    // Start auto-refresh only on authenticated pages
    if (document.getElementById('notification-bell')) {
        setInterval(refreshNotifications, NOTIFICATION_REFRESH_INTERVAL);

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) refreshNotifications();
        });
    }
})();