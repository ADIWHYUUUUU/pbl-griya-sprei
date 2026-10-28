// ---------- Ubah Alamat ----------
var tombolUbah    = document.getElementById('tombolUbahAlamat');
var alamatTampil  = document.getElementById('alamatTampil');
var alamatUbah    = document.getElementById('alamatUbah');

if (tombolUbah) {
    tombolUbah.addEventListener('click', function () {
        var sedangUbah = !alamatUbah.classList.contains('sembunyi');

        if (sedangUbah) {
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

// ---------- metode pengiriman ----------
var semuaOpsi = document.querySelectorAll('.opsi');

semuaOpsi.forEach(function (opsi) {
    opsi.querySelector('input').addEventListener('change', function () {
        semuaOpsi.forEach(function (o) {
            o.classList.remove('terpilih');
        });
        opsi.classList.add('terpilih');
    });
});

// ---------- tipe dan dimensi ukuran ----------
var pilihanJenis = document.querySelectorAll('input[name="jenis"]');
var inputDimensi = document.querySelectorAll('#inputPanjang, #inputLebar, #inputTinggi');

function terapkanJenisUkuran(jenis) {
    var standar = jenis === 'standar';
    inputDimensi.forEach(function (input) {
        input.disabled = standar;
        if (standar) input.value = input.dataset.standard;
    });
}

pilihanJenis.forEach(function (pilihan) {
    pilihan.addEventListener('change', function () {
        terapkanJenisUkuran(pilihan.value);
    });
});
var jenisTerpilih = document.querySelector('input[name="jenis"]:checked');
if (jenisTerpilih) terapkanJenisUkuran(jenisTerpilih.value);

// ---------- Kirim Pesanan  ----------
var cekSetuju   = document.getElementById('cekSetuju');
var tombolKirim = document.getElementById('tombolKirim');

if (cekSetuju) {
    cekSetuju.addEventListener('change', function () {
        tombolKirim.disabled = !cekSetuju.checked;
        cekSetuju.closest('.persetujuan').classList.remove('perlu');
    });
}

// ---------- Konfirmasi  ----------
var formCheckout = document.getElementById('formCheckout');

if (formCheckout) {
    formCheckout.addEventListener('submit', function (event) {
        if (!cekSetuju.checked) {
            event.preventDefault();
            cekSetuju.closest('.persetujuan').classList.add('perlu');
            return;
        }

        if (isi('inputAlamat') === '') {
            event.preventDefault();
            alert('Alamat pengiriman wajib diisi.');
            bukaModal('modalAlamat');
            document.getElementById('inputAlamat').focus();
            return;
        }

        var yakin = confirm('Kirim pesanan sekarang? Pesanan akan diverifikasi Admin maksimal 1×24 jam.');

        if (!yakin) {
            event.preventDefault();   
        } else {
            tombolKirim.disabled = true;
            tombolKirim.textContent = 'Mengirim pesanan...';
        }
    });
}


// =====================================================
// UBAH ALAMAT, UBAH SPESIFIKASI & PESANAN BERHASIL
// =====================================================

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

document.querySelectorAll('[data-buka-modal]').forEach(function (tombol) {
    tombol.addEventListener('click', function () {
        bukaModal(tombol.dataset.bukaModal);
    });
});

document.querySelectorAll('[data-tutup-modal]').forEach(function (tombol) {
    tombol.addEventListener('click', function () {
        tutupModal(tombol.closest('.modal-latar'), true);
    });
});

document.querySelectorAll('.modal-latar').forEach(function (latar) {
    latar.addEventListener('click', function (event) {
        if (event.target === latar && latar.id !== 'modalSukses') {
            tutupModal(latar, true);
        }
    });
});

document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    var terbuka = document.querySelector('.modal-latar.tampil:not(#modalSukses)');
    if (terbuka) tutupModal(terbuka, true);
});

// ---------- Ubah Alamat ----------
function isi(id) {
    return document.getElementById(id).value.trim();
}

var tombolSimpanAlamat = document.getElementById('simpanAlamat');

if (tombolSimpanAlamat) {
    tombolSimpanAlamat.addEventListener('click', function () {
        if (isi('inputAlamat') === '') {
            alert('Alamat pengiriman wajib diisi.');
            document.getElementById('inputAlamat').focus();
            return;
        }

        document.getElementById('teksAlamat').textContent  = isi('inputAlamat');
        document.getElementById('teksCatatanAlamat').textContent = isi('inputCatatanAlamat');

        document.getElementById('kotakCatatanAlamat').style.display =
            isi('inputCatatanAlamat') === '' ? 'none' : '';

        tutupModal(document.getElementById('modalAlamat'), false);
    });
}

// ---------- "Ubah Spesifikasi" ----------
document.querySelectorAll('[data-tambah], [data-kurang]').forEach(function (tombol) {
    tombol.addEventListener('click', function () {
        var input = document.getElementById(tombol.dataset.tambah || tombol.dataset.kurang);
        var maximum = parseInt(input.max, 10) || 20;
        var nilai = parseInt(input.value, 10) || 0;

        nilai = tombol.dataset.tambah ? nilai + 1 : nilai - 1;
        input.value = Math.max(0, Math.min(maximum, nilai));
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

        var jenis = document.querySelector('input[name="jenis"]:checked').value;
        var label = document.getElementById('teksJenis');
        label.textContent = jenis.toUpperCase();
        label.className = 'label label-' + jenis;

        document.getElementById('teksUkuran').textContent = p + ' × ' + l + ' × ' + t + ' cm';
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

// ---------- kunci scroll selama tampil ----------
if (document.getElementById('modalSukses')) {
    document.body.classList.add('ada-modal');
}