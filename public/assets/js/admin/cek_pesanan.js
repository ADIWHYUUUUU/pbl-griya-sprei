document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.querySelector('.orders-table tbody');
    const rows = Array.from(document.querySelectorAll('[data-order-row]'));
    const emptyState = document.getElementById('ordersEmpty');
    const resultCount = document.getElementById('ordersResultCount');
    const pagination = document.getElementById('ordersPagination');
    const invoiceSearch = document.getElementById('orderSearchInvoice');
    const customerSearch = document.getElementById('orderSearchCustomer');
    const phoneSearch = document.getElementById('orderSearchPhone');
    const statusFilter = document.getElementById('orderStatusFilter');
    const dateFilter = document.getElementById('orderDateFilter');
    const methodFilter = document.getElementById('orderMethodFilter');
    const sortSelect = document.getElementById('orderSort');
    const statusTabs = Array.from(document.querySelectorAll('[data-order-status-filter]'));
    const pageSize = 10;
    let currentPage = 1;

    if (!tableBody || !resultCount || !pagination) return;

    const orderPairs = rows.map(function (row) {
        return {
            row: row,
            details: row.nextElementSibling && row.nextElementSibling.matches('[data-order-details]')
                ? row.nextElementSibling
                : null
        };
    });

    function syncStatusTabs() {
        const selectedStatus = statusFilter ? statusFilter.value : 'semua';
        statusTabs.forEach(function (tab) {
            const isActive = tab.dataset.orderStatusFilter === selectedStatus;
            tab.classList.toggle('is-active', isActive);
            tab.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    function renderPagination(pageCount) {
        pagination.replaceChildren();
        if (pageCount <= 1) return;

        const createButton = function (label, page, disabled, current) {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = label;
            button.disabled = disabled;
            if (current) {
                button.className = 'is-current';
                button.setAttribute('aria-current', 'page');
            }
            button.addEventListener('click', function () {
                currentPage = page;
                filterOrders();
            });
            pagination.appendChild(button);
        };

        createButton('‹ Sebelumnya', Math.max(1, currentPage - 1), currentPage === 1, false);
        const firstPage = Math.max(1, Math.min(currentPage - 2, pageCount - 4));
        const lastPage = Math.min(pageCount, firstPage + 4);
        for (let page = firstPage; page <= lastPage; page++) {
            createButton(String(page), page, false, page === currentPage);
        }
        createButton('Berikutnya ›', Math.min(pageCount, currentPage + 1), currentPage === pageCount, false);
    }

    function filterOrders() {
        const invoice = invoiceSearch ? invoiceSearch.value.trim().toLocaleLowerCase('id') : '';
        const customer = customerSearch ? customerSearch.value.trim().toLocaleLowerCase('id') : '';
        const phone = phoneSearch ? phoneSearch.value.replace(/\D+/g, '') : '';
        const selectedStatus = statusFilter ? statusFilter.value : 'semua';
        const selectedDate = dateFilter ? dateFilter.value : '';
        const selectedMethod = methodFilter ? methodFilter.value : '';
        const now = new Date();
        const matches = orderPairs.filter(function (pair) {
            const row = pair.row;
            const timestamp = Number(row.dataset.date) * 1000;
            const rowDate = new Date(timestamp);
            const invoiceMatch = invoice === '' || (row.dataset.invoice || '').includes(invoice)
                || (row.dataset.customer || '').includes(invoice);
            const customerMatch = customer === '' || (row.dataset.customer || '').includes(customer);
            const phoneMatch = phone === '' || (row.dataset.phone || '').includes(phone);
            const statusMatch = selectedStatus === 'semua' || row.dataset.category === selectedStatus;
            const methodMatch = selectedMethod === '' || row.dataset.method === selectedMethod;
            let dateMatch = true;

            if (selectedDate === 'today') {
                dateMatch = rowDate.toDateString() === now.toDateString();
            } else if (selectedDate !== '') {
                const dayCount = Number(selectedDate);
                dateMatch = timestamp >= now.getTime() - dayCount * 24 * 60 * 60 * 1000;
            }

            return invoiceMatch && customerMatch && phoneMatch && statusMatch && methodMatch && dateMatch;
        });

        const sortMode = sortSelect ? sortSelect.value : 'newest';
        matches.sort(function (first, second) {
            const firstDate = Number(first.row.dataset.date);
            const secondDate = Number(second.row.dataset.date);
            const firstAmount = Number(first.row.dataset.amount);
            const secondAmount = Number(second.row.dataset.amount);

            if (sortMode === 'oldest') return firstDate - secondDate;
            if (sortMode === 'amount-high') return secondAmount - firstAmount;
            if (sortMode === 'amount-low') return firstAmount - secondAmount;
            return secondDate - firstDate;
        });

        const pageCount = Math.max(1, Math.ceil(matches.length / pageSize));
        currentPage = Math.min(currentPage, pageCount);
        const start = (currentPage - 1) * pageSize;
        const visiblePairs = matches.slice(start, start + pageSize);
        const visibleSet = new Set(visiblePairs);

        orderPairs.forEach(function (pair) {
            const isVisible = visibleSet.has(pair);
            pair.row.hidden = !isVisible;
            if (pair.details) pair.details.hidden = true;
            const toggle = pair.row.querySelector('[data-toggle-details]');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
                toggle.textContent = 'Ringkasan';
            }
        });

        matches.forEach(function (pair) {
            tableBody.appendChild(pair.row);
            if (pair.details) tableBody.appendChild(pair.details);
        });
        if (emptyState) {
            emptyState.hidden = matches.length > 0;
            tableBody.appendChild(emptyState);
        }

        const firstItem = matches.length === 0 ? 0 : start + 1;
        const lastItem = Math.min(start + pageSize, matches.length);
        resultCount.textContent = 'Menampilkan ' + firstItem + '–' + lastItem + ' dari ' + matches.length
            + ' pesanan pada ' + Number(resultCount.dataset.loadedCount || 0) + ' pesanan terbaru yang dimuat.';
        renderPagination(pageCount);
    }

    [invoiceSearch, customerSearch, phoneSearch].forEach(function (input) {
        if (input) {
            input.addEventListener('input', function () {
                currentPage = 1;
                filterOrders();
            });
        }
    });

    [statusFilter, dateFilter, methodFilter, sortSelect].forEach(function (control) {
        if (control) {
            control.addEventListener('change', function () {
                if (control === statusFilter) syncStatusTabs();
                currentPage = 1;
                filterOrders();
            });
        }
    });

    statusTabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            if (!statusFilter) return;
            statusFilter.value = tab.dataset.orderStatusFilter || 'semua';
            syncStatusTabs();
            currentPage = 1;
            filterOrders();
        });
    });

    rows.forEach(function (row) {
        const toggle = row.querySelector('[data-toggle-details]');
        if (!toggle) return;
        toggle.addEventListener('click', function () {
            const detailRow = row.nextElementSibling;
            if (!detailRow || !detailRow.matches('[data-order-details]')) return;
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            detailRow.hidden = isExpanded;
            toggle.setAttribute('aria-expanded', String(!isExpanded));
            toggle.textContent = isExpanded ? 'Ringkasan' : 'Tutup';
        });
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

    syncStatusTabs();
    filterOrders();
});
