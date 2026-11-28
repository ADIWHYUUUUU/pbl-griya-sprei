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
    const detailModal = document.getElementById('orderDetailModal');
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
    let activeModalTrigger = null;
    let previousDocumentOverflow = '';

    function displayValue(value, fallback) {
        const normalized = value === null || value === undefined ? '' : String(value).trim();
        return normalized || (fallback !== undefined ? fallback : '-');
    }

    function createElement(tag, className, text) {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (text !== undefined) element.textContent = text;
        return element;
    }

    function createField(label, value, options) {
        const settings = options || {};
        const field = createElement('div', 'order-detail-field' + (settings.strong ? ' order-detail-field--strong' : ''));
        field.appendChild(createElement('span', 'order-detail-field__label', label));
        if (value instanceof Node) {
            field.appendChild(value);
        } else {
            const valueElement = createElement('p', 'order-detail-field__value', displayValue(value));
            field.appendChild(valueElement);
        }
        return field;
    }

    function createBadge(label, tone, payment) {
        const className = payment
            ? 'order-payment order-payment--' + tone
            : 'order-status order-status--' + tone;
        const badge = createElement('span', className);
        badge.appendChild(createElement('span', payment ? 'order-detail-status-dot' : 'order-status-dot'));
        badge.appendChild(document.createTextNode(label));
        return badge;
    }

    function createRow(label, value, className) {
        const row = createElement('div', 'order-detail-row' + (className ? ' ' + className : ''));
        row.appendChild(createElement('span', 'order-detail-row__label', label));
        const valueElement = value instanceof Node
            ? value
            : createElement('span', 'order-detail-row__value', displayValue(value));
        row.appendChild(valueElement);
        return row;
    }

    function renderSpecificationItems(items) {
        const itemsContainer = document.querySelector('[data-modal-items]');
        itemsContainer.replaceChildren();
        if (items.length === 0) {
            itemsContainer.appendChild(createElement('p', 'order-detail-empty', 'Spesifikasi pesanan tidak tersedia.'));
        }
        const productLabels = {
            BED_COVER: 'Bedcover',
            BEDCOVER: 'Bedcover',
            SARUNG_BANTAL: 'Sarung Bantal',
            SARUNG_GULING: 'Sarung Guling',
            SARUNG_BANTAL_GULING: 'Sarung Bantal & Guling'
        };
        items.forEach(function (item) {
            const itemElement = createElement('article', 'order-detail-item');
            const productHeader = createElement('div', 'order-detail-product');
            const thumbnail = createElement('div', 'order-detail-product__thumbnail');
            const imagePlaceholder = createElement('span', 'order-detail-product__placeholder');
            imagePlaceholder.setAttribute('aria-hidden', 'true');
            imagePlaceholder.innerHTML = '<svg viewBox="0 0 24 24" focusable="false"><rect x="4" y="4" width="16" height="16" rx="2"></rect><circle cx="9" cy="9" r="1.5"></circle><path d="m20 15-5-5L5 20"></path></svg>';
            thumbnail.appendChild(imagePlaceholder);
            if (item.image) {
                const image = createElement('img', 'order-detail-product__image');
                image.src = item.image;
                image.alt = '';
                image.addEventListener('error', function () {
                    image.remove();
                }, { once: true });
                thumbnail.appendChild(image);
            }
            productHeader.appendChild(thumbnail);
            const productInfo = createElement('div', 'order-detail-product__info');
            productInfo.appendChild(createElement('strong', '', displayValue(item.name, 'Produk tidak tersedia')));
            productInfo.appendChild(createElement('span', 'order-detail-outline-tag', item.type === 'CUSTOM' ? 'Custom Size' : 'Standar'));
            productHeader.appendChild(productInfo);
            itemElement.appendChild(productHeader);

            const extras = [];
            if (Number(item.pillow) > 0) extras.push(Number(item.pillow) + ' Sarung Bantal');
            if (Number(item.bolster) > 0) extras.push(Number(item.bolster) + ' Sarung Guling');
            const completenessLabel = productLabels[item.category] || 'Sprei';
            const sizeValues = [item.length, item.width, item.height]
                .filter(function (value) {
                    return value !== null && value !== undefined && String(value) !== '';
                });
            const size = sizeValues.length >= 2 ? sizeValues.join(' × ') + ' cm' : '-';
            const completeness = displayValue(item.quantity, '0') + ' ' + completenessLabel
                + (extras.length ? ' + ' + extras.join(' + ') : '');
            const additions = extras.length ? extras.map(function (extra) {
                return '+' + extra;
            }).join(', ') : '-';
            const specificationFields = createElement('div', 'order-detail-fields order-detail-fields--specification');
            specificationFields.append(
                createField('Ukuran', size),
                createField('Kelengkapan', completeness),
                createField('Tambahan', additions),
                createField('Catatan Pelanggan', item.note)
            );
            itemElement.appendChild(specificationFields);
            itemsContainer.appendChild(itemElement);
        });
    }

    function renderCustomer(customer) {
        const customerFields = document.querySelector('[data-modal-customer]');
        customerFields.replaceChildren();
        const courierValue = displayValue(customer.shippingReference, '');
        const courierBadge = courierValue
            ? createElement('p', 'order-detail-field__value order-detail-field__value--bold', courierValue)
            : createElement('span', 'order-detail-pending', 'Belum diisi');
        if (!courierValue) courierBadge.prepend(createElement('span', 'order-detail-status-dot'));
        const methodBadge = createElement('span', 'order-detail-outline-tag', displayValue(customer.shippingMethod));
        customerFields.append(
            createField('Nama', customer.name, { strong: true }),
            createField('Metode Pengiriman', methodBadge),
            createField('WhatsApp', customer.phone),
            createField(displayValue(customer.shippingReferenceLabel, 'Nomor Resi'), courierBadge),
            createField('Alamat', customer.address),
            createField('Catatan Pengiriman', customer.shippingNote)
        );
    }

    function renderBilling(billing) {
        const billingContainer = document.querySelector('[data-modal-billing]');
        billingContainer.replaceChildren();
        function billingValue(value, discount) {
            if (value === null || value === undefined) {
                return createElement('strong', 'order-detail-pending-text', 'Belum ditetapkan');
            }
            const amount = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(value));
            return createElement('span', 'order-detail-row__value', (discount && Number(value) > 0 ? '– ' : '') + 'Rp ' + amount);
        }
        billingContainer.append(
            createRow('Tagihan pokok', billingValue(billing.principal)),
            createRow('Diskon', billingValue(billing.discount, true)),
            createRow('Ongkir', billingValue(billing.shipping))
        );
        const totalContainer = document.querySelector('[data-modal-total]');
        totalContainer.replaceChildren(
            createElement('strong', '', 'Total tagihan'),
            billing.total === null || billing.total === undefined
                ? createElement('strong', 'order-detail-pending-text', 'Belum ditagih')
                : createElement('strong', '', 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(billing.total)))
        );
    }

    function renderPayment(payment) {
        const paymentContainer = document.querySelector('[data-modal-payment]');
        paymentContainer.replaceChildren();
        paymentContainer.appendChild(createRow(
            'Status',
            createBadge(displayValue(payment.status, 'Belum ada pembayaran'), displayValue(payment.tone, 'other'), true),
            'order-detail-row--payment-status'
        ));
        if (payment.exists) {
            const sender = [displayValue(payment.sender, ''), displayValue(payment.account, '')]
                .filter(Boolean)
                .join(' · ');
            const amount = payment.amount === null || payment.amount === undefined
                ? '-'
                : 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(payment.amount));
            paymentContainer.append(
                createRow('Metode', payment.method),
                createRow('Nominal transfer', amount),
                createRow('Tanggal upload', payment.date),
                createRow('Pengirim', sender),
                createRow('Bukti transfer', payment.proof)
            );
            if (payment.status === 'Ditolak' && payment.adminNote) {
                paymentContainer.appendChild(createRow('Catatan admin', payment.adminNote, 'order-detail-row--admin-note'));
            }
        }
    }

    function renderOrderDetail(data) {
        const status = data.status || {};
        const statusBadge = document.querySelector('[data-modal-order-status]');
        statusBadge.className = 'order-status order-status--' + displayValue(status.tone, 'other');
        statusBadge.replaceChildren(
            createElement('span', 'order-status-dot'),
            document.createTextNode(displayValue(status.label))
        );
        statusBadge.hidden = false;
        document.querySelector('[data-modal-invoice]').textContent = displayValue(data.invoice);
        document.querySelector('[data-modal-date]').textContent = displayValue(data.date);
        renderSpecificationItems(Array.isArray(data.items) ? data.items : []);
        renderCustomer(data.customer || {});
        renderBilling(data.billing || {});
        renderPayment(data.payment || {});
    }

    function closeDetailModal(restoreFocus) {
        if (!detailModal || !detailModal.open) return;
        detailModal.close();
        document.documentElement.style.overflow = previousDocumentOverflow;
        const trigger = activeModalTrigger;
        activeModalTrigger = null;
        if (restoreFocus !== false && trigger && trigger.isConnected) trigger.focus();
    }

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
            if (!pageRows.has(row) && activeModalTrigger && activeModalTrigger.closest('[data-order-row]') === row) {
                closeDetailModal(false);
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

    document.querySelectorAll('[data-order-detail-trigger]').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            const data = JSON.parse(trigger.dataset.orderDetail || '{}');
            renderOrderDetail(data);
            activeModalTrigger = trigger;
            previousDocumentOverflow = document.documentElement.style.overflow;
            document.documentElement.style.overflow = 'hidden';
            detailModal.showModal();
            detailModal.querySelector('[data-modal-close]').focus();
        });
    });

    if (detailModal) {
        detailModal.querySelectorAll('[data-modal-close]').forEach(function (button) {
            button.addEventListener('click', function () {
                closeDetailModal();
            });
        });
        detailModal.addEventListener('click', function (event) {
            if (event.target === detailModal) closeDetailModal();
        });
        detailModal.addEventListener('cancel', function (event) {
            event.preventDefault();
            closeDetailModal();
        });
        detailModal.addEventListener('keydown', function (event) {
            if (event.key !== 'Tab') return;
            const focusable = Array.from(detailModal.querySelectorAll(
                'button:not([disabled]), a[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            )).filter(function (element) {
                return !element.hidden;
            });
            if (focusable.length === 0) {
                event.preventDefault();
                detailModal.focus();
                return;
            }
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });
    }

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
