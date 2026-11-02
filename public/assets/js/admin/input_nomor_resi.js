document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form[action*="admin-order-resi-save"]');
    if (!form) {
        return;
    }

    const trackingInput = form.querySelector('#nomor_resi');
    const confirmBox = form.querySelector('#konfirmasi');

    form.addEventListener('submit', function (event) {
        if (trackingInput) {
            trackingInput.value = trackingInput.value.trim();
            if (!trackingInput.value) {
                event.preventDefault();
                trackingInput.focus();
                trackingInput.setCustomValidity('Nomor resi wajib diisi.');
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
