// =====================================================
// SCRIPT HALAMAN CHECKOUT - GRIYA SPREI
// Letak file: pbl-griya-sprei/frontend/assets/js/checkout.js
// =====================================================


// ---------- 1. Tombol "Ubah Alamat" -> tampilkan / sembunyikan form ----------
var tombolUbah    = document.getElementById('tombolUbahAlamat');
var alamatTampil  = document.getElementById('alamatTampil');
var alamatUbah    = document.getElementById('alamatUbah');

if (tombolUbah) {
    tombolUbah.addEventListener('click', function () {
        var sedangUbah = !alamatUbah.classList.contains('sembunyi');

        if (sedangUbah) {
            // Simpan: salin isi input ke tampilan biasa
            document.getElementById('teksAlamat').textContent =
                document.getElementById('inputAlamat').value;
            document.getElementById('teksCatatanAlamat').textContent =
                document.getElementById('inputCatatanAlamat').value;

            alamatUbah.classList.add('sembunyi');
            alamatTampil.classList.remove('sembunyi');
            tombolUbah.querySelector('span').textContent = 'Ubah Alamat';
        } else {
            alamatTampil.classList.add('sembunyi');
            alamatUbah.classList.remove('sembunyi');
            tombolUbah.querySelector('span').textContent = 'Simpan Alamat';
            document.getElementById('inputAlamat').focus();
        }
    });
}


// ---------- 2. Kotak metode pengiriman: tandai yang dipilih ----------
var semuaOpsi = document.querySelectorAll('.opsi');

semuaOpsi.forEach(function (opsi) {
    opsi.querySelector('input').addEventListener('change', function () {
        semuaOpsi.forEach(function (o) {
            o.classList.remove('terpilih');
        });
        opsi.classList.add('terpilih');
    });
});


// ---------- 3. Tombol "Kirim Pesanan" aktif hanya kalau persetujuan dicentang ----------
var cekSetuju   = document.getElementById('cekSetuju');
var tombolKirim = document.getElementById('tombolKirim');

if (cekSetuju) {
    cekSetuju.addEventListener('change', function () {
        tombolKirim.disabled = !cekSetuju.checked;
        cekSetuju.closest('.persetujuan').classList.remove('perlu');
    });
}


// ---------- 4. Konfirmasi terakhir sebelum pesanan dikirim ----------
var formCheckout = document.getElementById('formCheckout');

if (formCheckout) {
    formCheckout.addEventListener('submit', function (event) {
        if (!cekSetuju.checked) {
            event.preventDefault();
            cekSetuju.closest('.persetujuan').classList.add('perlu');
            return;
        }

        var yakin = confirm('Kirim pesanan sekarang? Pesanan akan diverifikasi Admin maksimal 1×24 jam.');

        if (!yakin) {
            event.preventDefault();   // batal kirim form
        } else {
            tombolKirim.disabled = true;
            tombolKirim.textContent = 'Mengirim pesanan...';
        }
    });
}