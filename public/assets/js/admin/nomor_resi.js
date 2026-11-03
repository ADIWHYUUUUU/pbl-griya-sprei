document.addEventListener('DOMContentLoaded', function () {
    const resiModal = document.getElementById('resiModal');
    const modalTitle = document.getElementById('resiModalTitle');
    const resiNumberValue = document.getElementById('resiNumberValue');
    const resiModalSubtitle = document.getElementById('resiModalSubtitle');
    const resiCourierValue = document.getElementById('resiCourierValue');
    const resiCourierService = document.getElementById('resiCourierService');
    const resiCustomerName = document.getElementById('resiCustomerName');
    const resiCustomerPhone = document.getElementById('resiCustomerPhone');
    const resiCustomerAddress = document.getElementById('resiCustomerAddress');
    const resiProductName = document.getElementById('resiProductName');
    const resiProductMeta = document.getElementById('resiProductMeta');
    const resiProductNote = document.getElementById('resiProductNote');
    const copyResiBtn = document.getElementById('copyResiBtn');
    const printLabelBtn = document.getElementById('printLabelBtn');

    let activeResi = '';

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

        modalTitle.textContent = 'Detail Nomor Resi & Pengiriman';
        resiNumberValue.textContent = detail.resi;
        resiModalSubtitle.textContent = 'No. Pesanan: ' + detail.invoice + ' • ' + detail.courier;
        resiCourierValue.textContent = detail.courier;
        resiCourierService.textContent = detail.courierKey === 'INSTANT_GOJEK' ? 'Pengiriman instan' : 'Layanan ekspedisi';
        resiCustomerName.textContent = detail.customer || 'Pelanggan';
        resiCustomerPhone.textContent = detail.phone || '-';
        resiCustomerAddress.textContent = detail.address || 'Alamat belum tersedia';

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
                    copyResiBtn.textContent = 'Tersalin';
                    window.setTimeout(function () {
                        copyResiBtn.textContent = 'Salin Resi';
                    }, 1200);
                });
            } else {
                const tempInput = document.createElement('input');
                tempInput.value = activeResi;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                copyResiBtn.textContent = 'Tersalin';
                window.setTimeout(function () {
                    copyResiBtn.textContent = 'Salin Resi';
                }, 1200);
            }
        });
    }

    if (printLabelBtn) {
        printLabelBtn.addEventListener('click', function () {
            window.print();
        });
    }
});
