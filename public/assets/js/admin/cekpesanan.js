document.addEventListener('DOMContentLoaded', function () {
    const tabs = Array.from(document.querySelectorAll('[data-order-filter]'));
    const searchInput = document.getElementById('orderSearch');
    const rows = Array.from(document.querySelectorAll('[data-order-row]'));
    const emptyState = document.getElementById('ordersEmpty');
    const resultCount = document.getElementById('ordersResultCount');
    const statusInput = document.querySelector('.order-search input[name="status"]');

    if (!rows.length && !emptyState) return;

    const activeTab = tabs.find(function (tab) {
        return tab.classList.contains('is-active');
    });
    let activeFilter = activeTab ? activeTab.dataset.orderFilter : 'semua';

    function filterOrders() {
        const keyword = searchInput ? searchInput.value.trim().toLocaleLowerCase('id') : '';
        let visibleCount = 0;

        rows.forEach(function (row) {
            const matchesCategory =
                activeFilter === 'semua' || row.dataset.category === activeFilter;
            const matchesSearch = row.textContent
                .toLocaleLowerCase('id')
                .includes(keyword);
            const isVisible = matchesCategory && matchesSearch;

            row.hidden = !isVisible;
            const detailRow = row.nextElementSibling;
            if (!isVisible && detailRow && detailRow.matches('[data-order-details]')) {
                detailRow.hidden = true;
                const toggle = row.querySelector('[data-toggle-details]');
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.textContent = toggle.classList.contains('order-action--verify') ? 'Verifikasi' : 'Detail';
                }
            }
            if (isVisible) visibleCount++;
        });

        if (emptyState) {
            emptyState.hidden = visibleCount > 0;
        }

        if (resultCount) {
            resultCount.textContent =
                'Menampilkan ' + visibleCount + ' dari ' + rows.length + ' pesanan terbaru';
        }

        if (statusInput) {
            statusInput.value = activeFilter;
        }
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function (event) {
            event.preventDefault();
            activeFilter = tab.dataset.orderFilter;

            tabs.forEach(function (item) {
                const isActive = item === tab;
                item.classList.toggle('is-active', isActive);
                if (isActive) {
                    item.setAttribute('aria-current', 'page');
                } else {
                    item.removeAttribute('aria-current');
                }
            });

            filterOrders();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', filterOrders);
    }

    rows.forEach(function (row) {
        const toggle = row.querySelector('[data-toggle-details]');
        if (!toggle) return;

        toggle.addEventListener('click', function () {
            const detailRow = row.nextElementSibling;
            if (!detailRow || !detailRow.matches('[data-order-details]')) return;

            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            detailRow.hidden = isExpanded;
            toggle.setAttribute('aria-expanded', String(!isExpanded));
            toggle.textContent = isExpanded
                ? (toggle.classList.contains('order-action--verify') ? 'Verifikasi' : 'Detail')
                : 'Tutup';
        });
    });

    filterOrders();
});
