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

    filterOrders();
});
