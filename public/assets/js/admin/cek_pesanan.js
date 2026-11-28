document.addEventListener('DOMContentLoaded', function () {
    const root = document.querySelector('.admin-orders-list');
    const orderSection = document.querySelector('.orders-content');
    const tabs = Array.from(document.querySelectorAll('[data-order-filter]'));
    const searchInput = document.getElementById('orderSearch');
    const searchStatus = document.querySelector('.order-search input[name="status"]');
    const rows = Array.from(document.querySelectorAll('[data-order-row]'));
    const emptyState = document.getElementById('ordersEmpty');
    const loadingState = document.querySelector('[data-orders-loading]');
    const resultCount = document.getElementById('ordersResultCount');
    const pagination = document.querySelector('.orders-pagination');
    const pageNumbers = document.querySelector('[data-page-numbers]');
    const previousButton = document.querySelector('[data-page-previous]');
    const nextButton = document.querySelector('[data-page-next]');
    const pageSize = 10;

    if (!root || !orderSection) {
        document.documentElement.classList.add('orders-ready');
        return;
    }

    const activeTab = tabs.find(function (tab) {
        return tab.getAttribute('aria-current') === 'page';
    });
    let activeFilter = activeTab ? activeTab.dataset.orderFilter : 'semua';
    let currentPage = 1;

    function renderPage() {
        if (orderSection.dataset.ordersError === 'true') {
            if (loadingState) loadingState.hidden = true;
            document.documentElement.classList.add('orders-ready');
            return;
        }

        const keyword = searchInput
            ? searchInput.value.trim().toLocaleLowerCase('id')
            : '';
        const matchingRows = rows.filter(function (row) {
            const matchesStatus =
                activeFilter === 'semua' || row.dataset.category === activeFilter;
            const matchesSearch = (row.dataset.search || '')
                .toLocaleLowerCase('id')
                .includes(keyword);
            return matchesStatus && matchesSearch;
        });
        const pageCount = Math.max(1, Math.ceil(matchingRows.length / pageSize));
        currentPage = Math.min(currentPage, pageCount);
        const firstIndex = (currentPage - 1) * pageSize;
        const pageRows = new Set(matchingRows.slice(firstIndex, firstIndex + pageSize));

        rows.forEach(function (row) {
            row.hidden = !pageRows.has(row);
            const detailRow = row.nextElementSibling;
            if (!pageRows.has(row) && detailRow && detailRow.matches('[data-order-details]')) {
                detailRow.hidden = true;
                const toggle = row.querySelector('[data-toggle-details]');
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.textContent = 'Detail';
                }
            }
        });

        if (emptyState) {
            emptyState.hidden = matchingRows.length > 0;
            if (matchingRows.length === 0) {
                emptyState.querySelector('td').textContent = rows.length === 0
                    ? 'Tidak ada pesanan.'
                    : 'Tidak ada pesanan yang cocok dengan filter atau pencarian.';
            }
        }
        if (resultCount) {
            const shownCount = Math.min(pageSize, matchingRows.length - firstIndex);
            resultCount.textContent = 'Menampilkan ' + shownCount + ' dari '
                + matchingRows.length + ' pesanan di halaman ini.';
        }
        if (searchStatus) {
            searchStatus.value = activeFilter;
        }
        if (pagination && pageNumbers && previousButton && nextButton) {
            pagination.hidden = matchingRows.length <= pageSize;
            previousButton.disabled = currentPage === 1;
            nextButton.disabled = currentPage === pageCount;
            pageNumbers.replaceChildren();

            for (let page = 1; page <= pageCount; page += 1) {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = String(page);
                button.setAttribute('aria-label', 'Halaman ' + page);
                if (page === currentPage) {
                    button.classList.add('is-current');
                    button.setAttribute('aria-current', 'page');
                }
                button.addEventListener('click', function () {
                    currentPage = page;
                    renderPage();
                });
                pageNumbers.appendChild(button);
            }
        }

        if (loadingState) loadingState.hidden = true;
        document.documentElement.classList.add('orders-ready');
    }

    function selectFilter(tab) {
        activeFilter = tab.dataset.orderFilter || 'semua';
        currentPage = 1;
        tabs.forEach(function (item) {
            const isActive = item === tab;
            item.classList.toggle('is-active', isActive);
            if (isActive) {
                item.setAttribute('aria-current', 'page');
            } else {
                item.removeAttribute('aria-current');
            }
        });
        renderPage();
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function (event) {
            event.preventDefault();
            selectFilter(tab);
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            currentPage = 1;
            renderPage();
        });
        searchInput.addEventListener('search', function () {
            currentPage = 1;
            renderPage();
        });
    }

    if (previousButton) {
        previousButton.addEventListener('click', function () {
            if (currentPage > 1) {
                currentPage -= 1;
                renderPage();
            }
        });
    }
    if (nextButton) {
        nextButton.addEventListener('click', function () {
            currentPage += 1;
            renderPage();
        });
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
            toggle.textContent = isExpanded ? 'Detail' : 'Tutup';
        });
    });

    document.querySelectorAll('.status-change-form').forEach(function (form) {
        const select = form.querySelector('select[name="status_pesanan"]');
        const saveButton = form.querySelector('button[type="submit"]');
        if (select && saveButton) {
            select.addEventListener('change', function () {
                saveButton.disabled = select.value === '';
            });
        }

        form.addEventListener('submit', function (event) {
            const selectedOption = select && select.options[select.selectedIndex];
            const target = selectedOption ? selectedOption.text : '';
            const isCancellation = select && select.value === 'DIBATALKAN';
            const message = isCancellation
                ? 'Batalkan pesanan ini? Data dan riwayat pembayaran tidak dihapus.'
                : 'Ubah status pesanan menjadi "' + target + '"?';
            if (!target || !select.value || !window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('.orders-actions-card').forEach(function (card) {
        const summaryLabel = card.querySelector('.orders-actions-card__toggle');
        card.addEventListener('toggle', function () {
            if (!summaryLabel) return;
            const label = card.open
                ? summaryLabel.dataset.closeLabel
                : summaryLabel.dataset.openLabel;
            summaryLabel.firstChild.textContent = label + ' ';
        });
    });

    const oldestOrderLink = document.querySelector('[data-oldest-order]');
    if (oldestOrderLink) {
        oldestOrderLink.addEventListener('click', function (event) {
            const targetRow = document.getElementById('order-' + oldestOrderLink.dataset.oldestOrder);
            if (!targetRow) return;

            event.preventDefault();
            const allTab = tabs.find(function (tab) {
                return tab.dataset.orderFilter === 'semua';
            });
            if (allTab) selectFilter(allTab);
            if (searchInput) searchInput.value = '';
            currentPage = Math.floor(rows.indexOf(targetRow) / pageSize) + 1;
            renderPage();
            targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            targetRow.focus({ preventScroll: true });
            targetRow.classList.add('order-row--highlight');
            window.setTimeout(function () {
                targetRow.classList.remove('order-row--highlight');
            }, 1800);
        });
    }

    renderPage();
});
