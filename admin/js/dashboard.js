
class DashboardApp {
    constructor() {
        // ===== DOM refs =====
        this.numberEls = document.querySelectorAll('.text-3xl.font-extrabold');
        this.barEls    = document.querySelectorAll('.chart-bar');
        this.clockEl   = document.getElementById('live-clock');

        // ===== Init =====
        console.log('Dashboard loaded');
        this.animateNumbers();
        this.animateChartBars();
        this.startClock(); // realtime clock
    }

    // ===== Helpers =====
    _extractTargetNumber(text) {
        const match = String(text || '').trim().match(/[\d,.]+/);
        if (!match) return null;
        // bỏ mọi dấu . , để parse sang số
        const raw = match[0].replace(/[,.]/g, '');
        const n = parseFloat(raw);
        return Number.isFinite(n) ? { n, matched: match[0] } : null;
    }

    _formatVN(n) {
        return Math.floor(n).toLocaleString('vi-VN');
    }

    // ===== KPI number count-up =====
    animateNumbers(duration = 1000) {
        this.numberEls.forEach((el) => {
            const info = this._extractTargetNumber(el.textContent);
            if (!info) return;

            const { n: target, matched } = info;
            let start = null;

            const tick = (t) => {
                if (start === null) start = t;
                const p = Math.min(1, (t - start) / duration); // 0..1
                const current = target * p;
                const formatted = this._formatVN(current);
                el.textContent = el.textContent.replace(/[\d,.]+/, formatted);

                if (p < 1) requestAnimationFrame(tick);
                else el.textContent = el.textContent.replace(/[\d,.]+/, this._formatVN(target)); // chốt giá trị
            };

            // reset hiển thị về 0 trước khi chạy
            el.textContent = el.textContent.replace(/[\d,.]+/, '0');
            requestAnimationFrame(tick);
        });
    }

    // ===== Column chart stagger animation =====
    animateChartBars(staggerMs = 100) {
        this.barEls.forEach((bar, index) => {
            setTimeout(() => {
                // chiều cao đích lấy từ biến CSS --final-height (ví dụ "70%")
                const finalH = bar.style.getPropertyValue('--final-height');
                // đặt transition trước khi set height để có animation
                bar.style.transition = 'height 0.6s ease-out';
                // kích hoạt animation
                bar.style.height = finalH;
            }, index * staggerMs);
        });
    }

    // ===== Realtime clock =====
    startClock() {
        const update = () => {
            if (!this.clockEl) return;
            const now = new Date();
            const timeStr = now.toLocaleTimeString('vi-VN');
            const dateStr = now.toLocaleDateString('vi-VN');
            this.clockEl.textContent = `${timeStr} - ${dateStr}`;
        };
        update(); // cập nhật ngay lần đầu
        this._clockTimer = setInterval(update, 1000);
    }

    // (tùy chọn) gọi khi rời trang để dọn interval
    destroy() {
        if (this._clockTimer) clearInterval(this._clockTimer);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.__dashboardApp = new DashboardApp();
});

