// ---------- Ambil data pesanan ----------
var dataPesanan = {};
var elemenData = document.getElementById('dataPesanan');

if (elemenData) {
    dataPesanan = JSON.parse(elemenData.textContent || '{}');
}


// ---------- Isi teks 
function ambilNilai(objek, jalur) {
    return jalur.split('.').reduce(function (isi, kunci) {
        return (isi && isi[kunci] !== undefined) ? isi[kunci] : '';
    }, objek);
}

function isiModal(modal, pesanan) {
    modal.querySelectorAll('[data-isi]').forEach(function (el) {
        var nilai = ambilNilai(pesanan, el.dataset.isi);
        el.textContent = (nilai === '' || nilai === null) ? '-' : nilai;
    });
}
// ---------- Buka & tutup ----------
function bukaModal(modal) {
    modal.classList.add('tampil');
    document.body.classList.add('ada-modal');
}

function tutupModal(modal) {
    modal.classList.remove('tampil');
    document.body.classList.remove('ada-modal');
}

document.querySelectorAll('[data-tutup-modal]').forEach(function (tombol) {
    tombol.addEventListener('click', function () {
        tutupModal(tombol.closest('.modal-latar'));
    });
});

document.querySelectorAll('.modal-latar').forEach(function (latar) {
    latar.addEventListener('click', function (event) {
        if (event.target === latar) tutupModal(latar);
    });
});

document.addEventListener('keydown', function (event) {
    var terbuka = document.querySelector('.modal-latar.tampil');
    if (event.key === 'Escape' && terbuka) tutupModal(terbuka);
});


// ---------- STATUS PESANAN ----------
var modalStatus  = document.getElementById('modalStatus');
var formUnggah   = document.getElementById('formUnggah');
var inputBukti   = document.getElementById('inputBukti');
var tombolUnggah = document.getElementById('tombolUnggah');

document.querySelectorAll('[data-buka-status]').forEach(function (tombol) {
    tombol.addEventListener('click', function () {
        var pesanan = dataPesanan[tombol.dataset.bukaStatus];
        if (!pesanan) return;

        isiModal(modalStatus, pesanan);
        modalStatus.querySelector('.kotak-info-status').className = 'kotak-info-status ' + pesanan.kelas;
        document.getElementById('unggahKode').value = pesanan.kode;
        inputBukti.value = '';
        tombolUnggah.disabled = false;

        bukaModal(modalStatus);
    });
});

if (tombolUnggah) {
    tombolUnggah.addEventListener('click', function () {
        inputBukti.click();
    });
}

if (inputBukti) {
    inputBukti.addEventListener('change', function () {
        var file = inputBukti.files[0];
        if (!file) return;

        var ekstensi = file.name.split('.').pop().toLowerCase();
        if (['jpg', 'jpeg', 'png', 'pdf'].indexOf(ekstensi) === -1) {
            alert('File harus berupa JPG, PNG, atau PDF.');
            inputBukti.value = '';
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            alert('Ukuran file maksimal 2 MB.');
            inputBukti.value = '';
            return;
        }

        tombolUnggah.disabled = true;
        tombolUnggah.querySelector('[data-isi="aksi"]').textContent = 'Mengunggah...';
        formUnggah.submit();
    });
}


// ----------  DETAIL PESANAN ----------
var modalDetail = document.getElementById('modalDetail');

document.querySelectorAll('[data-buka-detail]').forEach(function (tombol) {
    tombol.addEventListener('click', function () {
        var pesanan = dataPesanan[tombol.dataset.bukaDetail];
        if (!pesanan) return;

        isiModal(modalDetail, pesanan);
        modalDetail.querySelectorAll('[data-badge]').forEach(function (badge) {
            badge.className = 'badge badge-' + pesanan.kelas;
        });
        document.getElementById('detailCatatanAdmin').hidden = pesanan.catatan_admin === '';

        bukaModal(modalDetail);
    });
});