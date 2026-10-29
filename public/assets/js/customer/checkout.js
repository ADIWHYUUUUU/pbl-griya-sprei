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

// ---------- kunci scroll selama tampil ----------
if (document.getElementById('modalSukses')) {
    document.body.classList.add('ada-modal');
}