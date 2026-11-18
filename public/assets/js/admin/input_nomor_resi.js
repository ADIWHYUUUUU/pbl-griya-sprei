document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form[action*="admin-order-resi-save"]');
    if (!form) {
        return;
    }

    const trackingInput = form.querySelector('#nomor_resi');
    const confirmBox = form.querySelector('#konfirmasi');
    const isGojek = trackingInput && trackingInput.getAttribute('type') === 'tel';

    form.addEventListener('submit', function (event) {
        if (trackingInput) {
            trackingInput.value = trackingInput.value.trim();
            if (!trackingInput.value) {
                event.preventDefault();
                trackingInput.focus();
                trackingInput.setCustomValidity(isGojek ? 'Nomor telepon kurir Gojek wajib diisi.' : 'Nomor resi pengiriman wajib diisi.');
                trackingInput.reportValidity();
                return;
            }
            if (isGojek && !/^\+?[0-9][0-9\s\-]{7,49}$/.test(trackingInput.value)) {
                event.preventDefault();
                trackingInput.focus();
                trackingInput.setCustomValidity('Masukkan nomor telepon kurir Gojek yang valid.');
                trackingInput.reportValidity();
                return;
            }
            trackingInput.setCustomValidity('');
        }

        if (confirmBox && !confirmBox.checked) {
            event.preventDefault();
            confirmBox.focus();
            confirmBox.setCustomValidity('Harap konfirmasi SOP pengiriman.');
            confirmBox.reportValidity();
            return;
        }

        if (confirmBox) {
            confirmBox.setCustomValidity('');
        }
    });
});
