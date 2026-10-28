/* Griya Sprei - Katalog Produk */

(function () {
    'use strict';

    // Mengambil semua form hapus produk
    const forms = document.querySelectorAll(
        'form[data-confirm]'
    );

    if (forms.length === 0) return;

    // Membuat dialog konfirmasi
    const dialog = document.createElement('dialog');
    dialog.className = 'confirm';

    dialog.innerHTML = `
        <form method="dialog">
            <h3>Hapus produk?</h3>
            <p></p>

            <div class="row">
                <button value="no" class="btn btn-light">
                    Batal
                </button>

                <button value="yes" class="btn btn-danger-solid">
                    Ya, hapus
                </button>
            </div>
        </form>
    `;

    document.body.appendChild(dialog);

    // Menangani tombol hapus
    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.confirmed) return;

            event.preventDefault();

            dialog.querySelector('p').textContent =
                form.dataset.confirm;

            dialog.returnValue = '';
            dialog.showModal();

            dialog.onclose = function () {
                if (dialog.returnValue === 'yes') {
                    form.dataset.confirmed = '1';
                    form.requestSubmit();
                }
            };
        });
    });
})();




