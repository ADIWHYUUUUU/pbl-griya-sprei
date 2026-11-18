// ---------- metode pengiriman ----------
var semuaOpsi = document.querySelectorAll('.opsi');

semuaOpsi.forEach(function (opsi) {
    opsi.querySelector('input').addEventListener('change', function () {
        semuaOpsi.forEach(function (pilihan) {
            pilihan.classList.remove('terpilih');
        });
        opsi.classList.add('terpilih');
    });
});

// ---------- persetujuan checkout ----------
var cekSetuju = document.getElementById('cekSetuju');
var tombolKirim = document.getElementById('tombolKirim');
var tombolUbahAlamat = document.getElementById('tombolUbahAlamat');

if (tombolUbahAlamat) {
    tombolUbahAlamat.addEventListener('click', function () {
        document.getElementById('inputAlamat').focus();
    });
}

if (cekSetuju && tombolKirim) {
    cekSetuju.addEventListener('change', function () {
        tombolKirim.disabled = !cekSetuju.checked;
        cekSetuju.closest('.persetujuan').classList.remove('perlu');
    });
}

// ---------- konfirmasi pesanan ----------
var formCheckout = document.getElementById('formCheckout');

if (formCheckout && cekSetuju && tombolKirim) {
    formCheckout.addEventListener('submit', function (event) {
        if (!cekSetuju.checked) {
            event.preventDefault();
            cekSetuju.closest('.persetujuan').classList.add('perlu');
            return;
        }

        var alamat = document.getElementById('inputAlamat');
        if (!alamat.value.trim()) {
            event.preventDefault();
            alert('Alamat pengiriman wajib diisi.');
            alamat.focus();
            return;
        }

        if (!confirm('Kirim pesanan sekarang? Pesanan akan diverifikasi Admin maksimal 1×24 jam.')) {
            event.preventDefault();
            return;
        }

        tombolKirim.disabled = true;
        tombolKirim.textContent = 'Mengirim pesanan...';
    });
}

if (document.getElementById('modalSukses')) {
    document.body.classList.add('ada-modal');
}
