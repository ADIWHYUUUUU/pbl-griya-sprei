(function () {
    'use strict';

    var pemicuModal = null;
    var modalTerbuka = null;

    function bukaModal(modal, pemicu) {
        if (!modal) {
            return;
        }

        if (modalTerbuka && modalTerbuka !== modal) {
            tutupModal(modalTerbuka, false);
        }

        pemicuModal = pemicu || document.activeElement;
        modalTerbuka = modal;
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');

        var fokus = modal.querySelector('.close-btn, button, a, select');
        if (fokus) {
            fokus.focus();
        }
    }

    function tutupModal(modal, pulihkanFokus) {
        if (!modal) {
            return;
        }

        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
        if (modalTerbuka === modal) {
            modalTerbuka = null;
        }
        if (!document.querySelector('.modal-overlay.active')) {
            document.body.classList.remove('modal-open');
        }
        if (pulihkanFokus !== false && pemicuModal && pemicuModal.isConnected) {
            pemicuModal.focus();
        }
    }

    function isiDetail(button) {
        var data = button.dataset;
        document.getElementById('dKodeTanggal').textContent =
            'Invoice: ' + data.kode + ' · ' + data.tanggal;
        document.getElementById('dNama').textContent = data.nama || '-';
        document.getElementById('dTelepon').textContent = data.telepon || '-';
        document.getElementById('dAlamat').textContent = data.alamat || '-';
        document.getElementById('dItems').textContent = data.produk || '-';
        document.getElementById('dTotal').textContent = data.total || '-';
        document.getElementById('dMetode').textContent =
            (data.pembayaran || 'Belum ada pembayaran') +
            (data.metode && data.metode !== 'Belum tersedia' ? ' · ' + data.metode : '');
        document.getElementById('dStatus').value = data.status || '-';
        document.getElementById('dCatatanAdmin').value = data.catatan || 'Tidak ada catatan.';
    }

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        var trigger = target.closest('[data-modal]');
        if (trigger) {
            event.preventDefault();
            bukaModal(document.getElementById(trigger.dataset.modal), trigger);
            return;
        }

        var orderTrigger = target.closest('[data-order-detail]');
        if (orderTrigger) {
            isiDetail(orderTrigger);
            bukaModal(document.getElementById('modalDetail'), orderTrigger);
            return;
        }

        var closeTrigger = target.closest('[data-close]');
        if (closeTrigger) {
            tutupModal(closeTrigger.closest('.modal-overlay'));
            return;
        }

        if (target.classList.contains('modal-overlay')) {
            tutupModal(target);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modalTerbuka) {
            tutupModal(modalTerbuka);
            return;
        }

        if (event.key !== 'Tab' || !modalTerbuka) {
            return;
        }

        var controls = modalTerbuka.querySelectorAll(
            'a[href], button:not([disabled]), select:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );
        if (!controls.length) {
            event.preventDefault();
            return;
        }

        var first = controls[0];
        var last = controls[controls.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });

    var hashModal = location.hash ? document.getElementById(location.hash.slice(1)) : null;
    if (hashModal && hashModal.classList.contains('modal-overlay')) {
        bukaModal(hashModal);
    }

    var filterPeriode = document.getElementById('filterPeriode');
    if (filterPeriode) {
        function sesuaikanFilterPeriode() {
            document.querySelectorAll('[data-periode-group]').forEach(function (field) {
                var periodeTersedia = field.dataset.periodeGroup.split(' ');
                var tampil = periodeTersedia.indexOf(filterPeriode.value) !== -1;
                field.hidden = !tampil;
                field.querySelectorAll('select, input').forEach(function (input) {
                    input.disabled = !tampil;
                });
            });
        }

        filterPeriode.addEventListener('change', sesuaikanFilterPeriode);
        sesuaikanFilterPeriode();
    }

    var reportTable = document.getElementById('lapTabel');
    var reportPagination = document.getElementById('reportPagination');
    if (reportTable && reportPagination) {
        var reportRows = Array.prototype.slice.call(
            reportTable.querySelectorAll('[data-report-order]')
        );
        var reportSearch = document.getElementById('reportSearch');
        var reportStatus = document.getElementById('reportStatusFilter');
        var reportPageSummary = document.getElementById('reportPageSummary');
        var reportEmptyState = document.getElementById('reportEmptyState');
        var reportPageSize = 7;
        var reportCurrentPage = parseInt(reportTable.dataset.initialPage || '1', 10);

        function renderReportTable() {
            var searchValue = reportSearch ? reportSearch.value.trim().toLocaleLowerCase() : '';
            var statusValue = reportStatus ? reportStatus.value : '';
            var filteredRows = reportRows.filter(function (row) {
                var matchesSearch = row.textContent.toLocaleLowerCase().indexOf(searchValue) !== -1;
                var matchesStatus = !statusValue || row.dataset.status === statusValue;
                return matchesSearch && matchesStatus;
            });
            var pageCount = Math.max(1, Math.ceil(filteredRows.length / reportPageSize));
            reportCurrentPage = Math.min(Math.max(1, reportCurrentPage), pageCount);

            reportRows.forEach(function (row) {
                row.hidden = true;
            });
            filteredRows.slice(
                (reportCurrentPage - 1) * reportPageSize,
                reportCurrentPage * reportPageSize
            ).forEach(function (row) {
                row.hidden = false;
            });

            if (reportEmptyState) {
                reportEmptyState.hidden = filteredRows.length !== 0;
            }
            if (reportPageSummary) {
                reportPageSummary.textContent = filteredRows.length
                    ? 'Halaman ' + reportCurrentPage + ' dari ' + pageCount
                    : 'Tidak ada transaksi';
            }

            reportPagination.replaceChildren();
            if (pageCount <= 1) {
                return;
            }

            function addPageButton(page, label, ariaLabel, disabled) {
                var button = document.createElement('button');
                button.type = 'button';
                button.dataset.reportPage = String(page);
                button.textContent = label;
                button.disabled = Boolean(disabled);
                if (ariaLabel) {
                    button.setAttribute('aria-label', ariaLabel);
                }
                if (page === reportCurrentPage) {
                    button.className = 'current';
                    button.setAttribute('aria-current', 'page');
                }
                reportPagination.appendChild(button);
            }

            function addEllipsis() {
                var ellipsis = document.createElement('span');
                ellipsis.textContent = '…';
                ellipsis.setAttribute('aria-hidden', 'true');
                reportPagination.appendChild(ellipsis);
            }

            addPageButton(reportCurrentPage - 1, '‹', 'Halaman sebelumnya', reportCurrentPage === 1);
            if (pageCount <= 5) {
                for (var page = 1; page <= pageCount; page += 1) {
                    addPageButton(page, String(page));
                }
            } else if (reportCurrentPage <= 3) {
                for (var firstPage = 1; firstPage <= 3; firstPage += 1) {
                    addPageButton(firstPage, String(firstPage));
                }
                addEllipsis();
                addPageButton(pageCount, String(pageCount));
            } else if (reportCurrentPage >= pageCount - 2) {
                addPageButton(1, '1');
                addEllipsis();
                for (var lastPage = pageCount - 2; lastPage <= pageCount; lastPage += 1) {
                    addPageButton(lastPage, String(lastPage));
                }
            } else {
                addPageButton(1, '1');
                addEllipsis();
                for (var middlePage = reportCurrentPage - 1; middlePage <= reportCurrentPage + 1; middlePage += 1) {
                    addPageButton(middlePage, String(middlePage));
                }
                addEllipsis();
                addPageButton(pageCount, String(pageCount));
            }
            addPageButton(reportCurrentPage + 1, '›', 'Halaman berikutnya', reportCurrentPage === pageCount);
        }

        if (reportSearch) {
            reportSearch.addEventListener('input', function () {
                reportCurrentPage = 1;
                renderReportTable();
            });
        }
        if (reportStatus) {
            reportStatus.addEventListener('change', function () {
                reportCurrentPage = 1;
                renderReportTable();
            });
        }
        reportPagination.addEventListener('click', function (event) {
            var target = event.target;
            if (!(target instanceof Element)) {
                return;
            }
            var pageButton = target.closest('[data-report-page]');
            if (!pageButton) {
                return;
            }
            reportCurrentPage = parseInt(pageButton.dataset.reportPage, 10);
            renderReportTable();
        });

        renderReportTable();
    }
})();