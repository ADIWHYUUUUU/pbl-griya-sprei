// =====================================================
// SCRIPT HALAMAN CHECKOUT - GRIYA SPREI
// =====================================================


// ---------- "Ubah Alamat" tampilkan atau di sembunyikan----------
var tombolUbah    = document.getElementById('tombolUbahAlamat');
var alamatTampil  = document.getElementById('alamatTampil');
var alamatUbah    = document.getElementById('alamatUbah');

if (tombolUbah) {
    tombolUbah.addEventListener('click', function () {
        var sedangUbah = !alamatUbah.classList.contains('sembunyi');

        if (sedangUbah) {
            // Simpan
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


// =====================================================
// TAMBAHAN: MODAL UBAH ALAMAT, UBAH SPESIFIKASI & PESANAN BERHASIL
// =====================================================

// ---------- A. Buka & tutup modal ----------
// Saat modal dibuka, isiannya disimpan dulu.
// Kalau "Batal" / × ditekan, isian dikembalikan seperti semula.
var simpananIsian = {};

function bukaModal(idModal) {
    var modal = document.getElementById(idModal);
    if (!modal) return;

    simpananIsian = {};
    modal.querySelectorAll('input, textarea').forEach(function (el) {
        simpananIsian[el.id || el.name + el.value] =
            (el.type === 'radio') ? el.checked : el.value;
    });

    modal.classList.add('tampil');
    document.body.classList.add('ada-modal');

    var pertama = modal.querySelector('input:not([type=radio]):not([readonly]), textarea');
    if (pertama) pertama.focus();
}

function tutupModal(modal, batal) {
    if (batal) {
        modal.querySelectorAll('input, textarea').forEach(function (el) {
            var kunci = el.id || el.name + el.value;
            if (kunci in simpananIsian) {
                if (el.type === 'radio') el.checked = simpananIsian[kunci];
                else el.value = simpananIsian[kunci];
            }
        });
    }
    modal.classList.remove('tampil');
    document.body.classList.remove('ada-modal');
}

// Tombol "Ubah Alamat" & "Ubah Spesifikasi"
document.querySelectorAll('[data-buka-modal]').forEach(function (tombol) {
    tombol.addEventListener('click', function () {
        bukaModal(tombol.dataset.bukaModal);
    });
});

// Tombol × dan "Batal"
document.querySelectorAll('[data-tutup-modal]').forEach(function (tombol) {
    tombol.addEventListener('click', function () {
        tutupModal(tombol.closest('.modal-latar'), true);
    });
});

// Klik area gelap di luar kotak modal = batal (kecuali modal sukses)
document.querySelectorAll('.modal-latar').forEach(function (latar) {
    latar.addEventListener('click', function (event) {
        if (event.target === latar && latar.id !== 'modalSukses') {
            tutupModal(latar, true);
        }
    });
});

// Tombol Esc = batal
document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    var terbuka = document.querySelector('.modal-latar.tampil:not(#modalSukses)');
    if (terbuka) tutupModal(terbuka, true);
});


// ---------- B. Modal "Ubah Alamat" -> Simpan Perubahan ----------
function isi(id) {
    return document.getElementById(id).value.trim();
}

var tombolSimpanAlamat = document.getElementById('simpanAlamat');

if (tombolSimpanAlamat) {
    tombolSimpanAlamat.addEventListener('click', function () {
        if (isi('inputNama') === '' || isi('inputTelepon') === '' || isi('inputAlamat') === '') {
            alert('Nama penerima, nomor WhatsApp, dan alamat wajib diisi.');
            return;
        }

        document.getElementById('teksNama').textContent    = isi('inputNama');
        document.getElementById('teksTelepon').textContent = isi('inputTelepon');
        document.getElementById('teksAlamat').textContent  = isi('inputAlamat');
        document.getElementById('teksCatatanAlamat').textContent = isi('inputCatatanAlamat');

        // Sembunyikan kotak catatan kalau kosong
        document.getElementById('kotakCatatanAlamat').style.display =
            isi('inputCatatanAlamat') === '' ? 'none' : '';

        tutupModal(document.getElementById('modalAlamat'), false);
    });
}


// ---------- C. Modal "Ubah Spesifikasi" ----------

// Tombol − dan + untuk sarung bantal & guling
document.querySelectorAll('[data-tambah], [data-kurang]').forEach(function (tombol) {
    tombol.addEventListener('click', function () {
        var input = document.getElementById(tombol.dataset.tambah || tombol.dataset.kurang);
        var nilai = parseInt(input.value, 10) || 0;

        nilai = tombol.dataset.tambah ? nilai + 1 : nilai - 1;
        input.value = Math.max(0, nilai);   // tidak boleh minus
    });
});

var tombolSimpanSpek = document.getElementById('simpanSpesifikasi');

if (tombolSimpanSpek) {
    tombolSimpanSpek.addEventListener('click', function () {
        var p = parseInt(isi('inputPanjang'), 10);
        var l = parseInt(isi('inputLebar'), 10);
        var t = parseInt(isi('inputTinggi'), 10);

        if (!(p > 0 && l > 0 && t > 0)) {
            alert('Panjang, lebar, dan tinggi kasur wajib diisi dengan angka lebih dari 0.');
            return;
        }

        // Label STANDAR / CUSTOM
        var jenis = document.querySelector('input[name="jenis"]:checked').value;
        var label = document.getElementById('teksJenis');
        label.textContent = jenis.toUpperCase();
        label.className = 'label label-' + jenis;

        document.getElementById('teksUkuran').textContent = p + ' × ' + l + ' × ' + t + ' cm';

        // Teks tambahan: "2 sarung bantal + 1 sarung guling"
        var bantal = parseInt(isi('inputBantal'), 10) || 0;
        var guling = parseInt(isi('inputGuling'), 10) || 0;
        var tambahan = [];
        if (bantal > 0) tambahan.push(bantal + ' sarung bantal');
        if (guling > 0) tambahan.push(guling + ' sarung guling');
        document.getElementById('teksTambahan').textContent = tambahan.length ? tambahan.join(' + ') : '-';

        document.getElementById('teksCatatanProduk').textContent = isi('inputCatatanProduk') || '-';

        tutupModal(document.getElementById('modalSpesifikasi'), false);
    });
}


// ---------- D. Modal sukses: kunci scroll selama tampil ----------
if (document.getElementById('modalSukses')) {
    document.body.classList.add('ada-modal');
}
// ===== AKHIR TAMBAHAN =====