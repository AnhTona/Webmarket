class CustomersPage {
    constructor() {
        // ====== DOM refs ======
        this.searchInput = document.getElementById('search-input');
        this.btnSearch   = document.getElementById('btn-search');
        this.filterStatus= document.getElementById('filter-status');
        this.filterRank  = document.getElementById('filter-rank');

        this.table = document.getElementById('customer-list-table');
        this.tableBody = document.querySelector('#customer-list-table tbody');

        // Init
        this.initFilterControls();
        this.hydrateFromUrl();
    }

    // ====== Filters ======
    initFilterControls() {
        const apply = (e) => this.applyFiltersToUrl(e);

        this.btnSearch?.addEventListener('click', apply);
        this.searchInput?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') apply(e);
        });
        this.filterStatus?.addEventListener('change', apply);
        this.filterRank?.addEventListener('change', apply);
    }

    applyFiltersToUrl(e) {
        if (e) e.preventDefault();
        const qs = new URLSearchParams(location.search);

        const s = (this.searchInput?.value || '').trim();
        if (s) qs.set('q', s);
        else qs.delete('q');

        const st = this.filterStatus?.value || 'All';
        const rk = this.filterRank?.value   || 'All';

        qs.set('status', st);
        qs.set('rank', rk);

        qs.set('page', '1'); // reset trang khi lọc/tìm kiếm
        location.search = qs.toString();
    }

    hydrateFromUrl() {
        const qs = new URLSearchParams(location.search);
        if (qs.has('q') && this.searchInput) {
            this.searchInput.value = qs.get('q');
        }
        if (qs.has('status') && this.filterStatus) {
            this.filterStatus.value = qs.get('status');
        }
        if (qs.has('rank') && this.filterRank) {
            this.filterRank.value = qs.get('rank');
        }
    }
}

document.addEventListener('DOMContentLoaded', () => new CustomersPage());