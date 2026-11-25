document.addEventListener('DOMContentLoaded', function () {
    const resiModal = document.getElementById('resiModal');
    const modalTitle = document.getElementById('resiModalTitle');
    const resiNumberValue = document.getElementById('resiNumberValue');
    const resiModalSubtitle = document.getElementById('resiModalSubtitle');
    const resiCourierValue = document.getElementById('resiCourierValue');
    const resiTrackingStatus = document.getElementById('resiTrackingStatus');
    const resiCustomerName = document.getElementById('resiCustomerName');
    const resiCustomerPhone = document.getElementById('resiCustomerPhone');
    const resiCustomerAddress = document.getElementById('resiCustomerAddress');
    const resiProductName = document.getElementById('resiProductName');
    const resiProductMeta = document.getElementById('resiProductMeta');
    const resiProductNote = document.getElementById('resiProductNote');
    const resiOrderDate = document.getElementById('resiOrderDate');
    const copyResiBtn = document.getElementById('copyResiBtn');
    const copyResiLabel = copyResiBtn ? copyResiBtn.querySelector('span') : null;

    let activeResi = '';
    const trackingFilters = document.querySelectorAll('[data-tracking-filter]');
    const orderRows = Array.from(document.querySelectorAll('tbody tr[data-tracking-status]'));
    const orderSearch = document.getElementById('orderSearch');
    const filterEmptyState = document.querySelector('.filter-empty-state');
    const orderCount = document.getElementById('orderCount');
    const orderPager = document.getElementById('orderPager');
    const pageSize = 10;
    let currentPage = 1;
    let activeTrackingFilter = 'all';

    const renderPager = function (pageCount) {
        if (!orderPager) {
            return;
        }

        orderPager.replaceChildren();
        if (pageCount <= 1) {
            return;
        }

        const addPageButton = function (label, page, options) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'page-number' + (options.active ? ' active' : '');
            button.textContent = label;
            button.disabled = options.disabled;
            if (options.active) {
                button.setAttribute('aria-current', 'page');
            }
            button.addEventListener('click', function () {
                currentPage = page;
                filterOrders();
            });
            orderPager.appendChild(button);
        };

        addPageButton('‹ Sebelumnya', Math.max(1, currentPage - 1), { disabled: currentPage === 1 });

        const firstPage = Math.max(1, Math.min(currentPage - 2, pageCount - 4));
        const lastPage = Math.min(pageCount, firstPage + 4);
        for (let page = firstPage; page <= lastPage; page += 1) {
            addPageButton(String(page), page, { active: page === currentPage, disabled: false });
        }

        addPageButton('Berikutnya ›', Math.min(pageCount, currentPage + 1), { disabled: currentPage === pageCount });
    };

    const filterOrders = function () {
        const searchTerm = orderSearch ? orderSearch.value.trim().toLocaleLowerCase() : '';
        const matchingRows = [];

        orderRows.forEach(function (row) {
            const matchesFilter = activeTrackingFilter === 'all'
                || row.dataset.trackingStatus === activeTrackingFilter;
            const matchesSearch = !searchTerm
                || (row.dataset.search || '').toLocaleLowerCase().includes(searchTerm);
            if (matchesFilter && matchesSearch) {
                matchingRows.push(row);
            }
        });

        const pageCount = Math.max(1, Math.ceil(matchingRows.length / pageSize));
        currentPage = Math.min(currentPage, pageCount);
        const firstVisibleIndex = (currentPage - 1) * pageSize;
        const lastVisibleIndex = Math.min(firstVisibleIndex + pageSize, matchingRows.length);
        const visibleRows = new Set(matchingRows.slice(firstVisibleIndex, lastVisibleIndex));
        orderRows.forEach(function (row) {
            row.hidden = !visibleRows.has(row);
        });

        if (filterEmptyState) {
            filterEmptyState.hidden = matchingRows.length > 0;
        }

        if (orderCount) {
            const rangeStart = matchingRows.length > 0 ? firstVisibleIndex + 1 : 0;
            orderCount.textContent = 'Menampilkan ' + rangeStart + '–' + lastVisibleIndex + ' dari '
                + matchingRows.length + ' pesanan yang siap dikirim atau telah dikirim.';
        }

        renderPager(pageCount);
    };

    trackingFilters.forEach(function (button) {
        button.addEventListener('click', function () {
            activeTrackingFilter = button.dataset.trackingFilter || 'all';
            trackingFilters.forEach(function (filterButton) {
                const isActive = filterButton === button;
                filterButton.classList.toggle('active', isActive);
                filterButton.setAttribute('aria-pressed', String(isActive));
            });
            currentPage = 1;
            filterOrders();
        });
    });

    if (orderSearch) {
        orderSearch.addEventListener('input', function () {
            currentPage = 1;
            filterOrders();
        });
    }

    filterOrders();

    const setModalState = function (isOpen) {
        if (!resiModal) {
            return;
        }

        resiModal.classList.toggle('show', isOpen);
        resiModal.setAttribute('aria-hidden', String(!isOpen));
        document.body.style.overflow = isOpen ? 'hidden' : '';
    };

    const closeModal = function () {
        setModalState(false);
    };

    const openModal = function (detail) {
        if (!detail || !detail.resi) {
            return;
        }

        activeResi = detail.resi;

        modalTitle.textContent = detail.courierKey === 'INSTANT_GOJEK'
            ? 'Nomor Telepon Kurir Gojek'
            : 'Nomor Resi Pengiriman';
        document.getElementById('resiModalIcon').classList.toggle('phone', detail.courierKey === 'INSTANT_GOJEK');
        resiNumberValue.textContent = detail.resi;
        document.getElementById('resiLabelValue').textContent = detail.resiLabel || 'NOMOR RESI PENGIRIMAN';
        resiModalSubtitle.textContent = detail.invoice;
        resiCourierValue.textContent = detail.courier;
        resiTrackingStatus.textContent = detail.trackingStatus || 'Sudah diisi';
        resiTrackingStatus.classList.toggle('filled', detail.trackingStatus !== 'Belum diisi');
        resiTrackingStatus.classList.toggle('empty', detail.trackingStatus === 'Belum diisi');
        if (copyResiBtn) {
            if (copyResiLabel) {
                copyResiLabel.textContent = 'Salin';
            }
        }
        resiCustomerName.textContent = detail.customer || 'Pelanggan';
        resiCustomerPhone.textContent = detail.phone || '-';
        resiCustomerAddress.textContent = detail.address || 'Alamat belum tersedia';
        resiOrderDate.textContent = detail.date || '-';

        const productText = detail.product || 'Rincian produk tidak tersedia';
        const lines = String(productText).split(/\r?\n/).map(function (line) { return line.trim(); }).filter(Boolean);
        const mainProduct = lines[0] || 'Produk tidak tersedia';
        const productMeta = lines.slice(1).join(' • ') || '-';

        resiProductName.textContent = mainProduct;
        resiProductMeta.textContent = productMeta;
        resiProductNote.textContent = detail.note || 'Tidak ada catatan pengiriman.';

        setModalState(true);
    };

    document.querySelectorAll('.resi-detail-trigger').forEach(function (btn) {
        btn.addEventListener('click', function () {
            let detail = {};
            try {
                detail = JSON.parse(btn.getAttribute('data-detail') || '{}');
            } catch (error) {
                detail = {};
            }

            openModal(detail);
        });
    });

    document.querySelectorAll('[data-close="resi-modal"]').forEach(function (button) {
        button.addEventListener('click', closeModal);
    });

    resiModal.addEventListener('click', function (event) {
        if (event.target === resiModal || event.target.classList.contains('resi-modal-backdrop')) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && resiModal && resiModal.classList.contains('show')) {
            closeModal();
        }
    });

    if (copyResiBtn) {
        copyResiBtn.addEventListener('click', function () {
            if (!activeResi) {
                return;
            }

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(activeResi).then(function () {
                    if (copyResiLabel) {
                        copyResiLabel.textContent = 'Tersalin';
                        window.setTimeout(function () {
                            copyResiLabel.textContent = 'Salin';
                        }, 1200);
                    }
                });
            } else {
                const tempInput = document.createElement('input');
                tempInput.value = activeResi;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                if (copyResiLabel) {
                    copyResiLabel.textContent = 'Tersalin';
                    window.setTimeout(function () {
                        copyResiLabel.textContent = 'Salin';
                    }, 1200);
                }
            }
        });
    }

});
