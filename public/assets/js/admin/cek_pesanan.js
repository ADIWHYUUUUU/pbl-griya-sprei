document.addEventListener('DOMContentLoaded', function () {
    const tabs = Array.from(document.querySelectorAll('[data-order-filter]'));
    const searchInput = document.getElementById('orderSearch');
    const rows = Array.from(document.querySelectorAll('[data-order-row]'));
    const emptyState = document.getElementById('ordersEmpty');
    const statusInput = document.querySelector('.order-search input[name="status"]');

    const activeTab = tabs.find(function (tab) {
        return tab.classList.contains('is-active');
    });
    let activeFilter = activeTab ? activeTab.dataset.orderFilter : 'semua';

    function filterOrders() {
        const keyword = searchInput
            ? searchInput.value.trim().toLocaleLowerCase('id')
            : '';
        let visibleCount = 0;

        rows.forEach(function (row) {
            const matchesStatus =
                activeFilter === 'semua' || row.dataset.category === activeFilter;
            const matchesSearch = (row.dataset.search || '')
                .toLocaleLowerCase('id')
                .includes(keyword);
            const isVisible = matchesStatus && matchesSearch;

            row.hidden = !isVisible;
            const detailRow = row.nextElementSibling;
            if (!isVisible && detailRow && detailRow.matches('[data-order-details]')) {
                detailRow.hidden = true;
                const toggle = row.querySelector('[data-toggle-details]');
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.textContent = 'Detail';
                }
            }
            if (isVisible) visibleCount++;
        });

        if (emptyState) {
            emptyState.hidden = visibleCount > 0;
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
        if (toggle) {
            toggle.addEventListener('click', function () {
                const detailRow = row.nextElementSibling;
                if (!detailRow || !detailRow.matches('[data-order-details]')) return;

                const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
                detailRow.hidden = isExpanded;
                toggle.setAttribute('aria-expanded', String(!isExpanded));
                toggle.textContent = isExpanded ? 'Detail' : 'Tutup';
            });
        }
    });

    document.querySelectorAll('.status-change-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            const select = form.querySelector('select[name="status_pesanan"]');
            const targetOption = select && select.options[select.selectedIndex];
            const target = targetOption ? targetOption.text : '';
            const isCancellation = select && select.value === 'DIBATALKAN';
            const message = isCancellation
                ? 'Batalkan pesanan ini? Data dan riwayat pembayaran tidak dihapus.'
                : 'Ubah status pesanan menjadi "' + target + '"?';
            if (!target || !window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    filterOrders();
});
