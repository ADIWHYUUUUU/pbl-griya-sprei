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
        document.getElementById('dProduk').textContent = data.produk || '-';
        document.getElementById('dTotal').textContent = data.total || '-';
        document.getElementById('dPembayaran').textContent =
            (data.pembayaran || 'Belum ada pembayaran') +
            (data.metode && data.metode !== 'Belum tersedia' ? ' · ' + data.metode : '');
        document.getElementById('dStatus').textContent = data.status || '-';
        document.getElementById('dCatatan').textContent = data.catatan || 'Tidak ada catatan.';
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
})();
