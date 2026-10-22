// ---------- Pilih bank----------
var semuaBank = document.querySelectorAll('.opsi-bank');

semuaBank.forEach(function (opsi) {
    var radio = opsi.querySelector('input');

    radio.addEventListener('change', function () {
        semuaBank.forEach(function (o) {
            o.classList.remove('terpilih');
        });
        opsi.classList.add('terpilih');
    });
});

// ---------- Unggah bukti transfer  ----------
var areaUnggah  = document.getElementById('areaUnggah');
var inputBukti  = document.getElementById('inputBukti');
var teksUnggah  = document.getElementById('teksUnggah');
var pesanError  = document.getElementById('pesanError');
var teksAwal    = teksUnggah ? teksUnggah.textContent : '';

function tampilkanError(teks) {
    pesanError.textContent = teks;
    pesanError.hidden = teks === '';
}

// Cek file
function cekFile(file) {
    if (!file) return 'Unggah bukti transfer terlebih dahulu.';

    var ekstensi = file.name.split('.').pop().toLowerCase();
    if (['jpg', 'jpeg', 'png', 'pdf'].indexOf(ekstensi) === -1) {
        return 'Bukti transfer harus berupa JPG, PNG, atau PDF.';
    }
    if (file.size > 2 * 1024 * 1024) {
        return 'Ukuran bukti transfer maksimal 2 MB.';
    }
    return '';
}

function perbaruiTampilanFile() {
    var file = inputBukti.files[0];
    var error = file ? cekFile(file) : '';

    if (file && error === '') {
        teksUnggah.textContent = '📎 ' + file.name;
        areaUnggah.classList.add('terpilih');
    } else {
        if (error !== '') inputBukti.value = '';
        teksUnggah.textContent = teksAwal;
        areaUnggah.classList.remove('terpilih');
    }
    tampilkanError(error);
}

if (inputBukti) {
    inputBukti.addEventListener('change', perbaruiTampilanFile);
}

// Upload Bukti Pembayaran
var tombolPilihBukti = document.getElementById('tombolPilihBukti');
if (tombolPilihBukti) {
    tombolPilihBukti.addEventListener('click', function () {
        inputBukti.click();
    });
}

if (areaUnggah) {
    ['dragenter', 'dragover'].forEach(function (namaEvent) {
        areaUnggah.addEventListener(namaEvent, function (event) {
            event.preventDefault();
            areaUnggah.classList.add('seret');
        });
    });

    ['dragleave', 'drop'].forEach(function (namaEvent) {
        areaUnggah.addEventListener(namaEvent, function (event) {
            event.preventDefault();
            areaUnggah.classList.remove('seret');
        });
    });

    areaUnggah.addEventListener('drop', function (event) {
        if (event.dataTransfer.files.length) {
            inputBukti.files = event.dataTransfer.files;
            perbaruiTampilanFile();
        }
    });
}

// ---------- Kirim Bukti Pembayaran ----------
var formBayar   = document.getElementById('formBayar');
var tombolKirim = document.getElementById('tombolKirim');

if (formBayar) {
    formBayar.addEventListener('submit', function (event) {
        var noRek   = document.getElementById('inputNoRek').value.trim();
        var namaRek = document.getElementById('inputNamaRek').value.trim();

        if (noRek === '' || namaRek === '') {
            event.preventDefault();
            tampilkanError('Nomor rekening dan nama pemilik rekening pengirim wajib diisi.');
            return;
        }

        var error = cekFile(inputBukti.files[0]);
        if (error !== '') {
            event.preventDefault();
            tampilkanError(error);
            return;
        }

        tampilkanError('');
        tombolKirim.disabled = true;
        tombolKirim.textContent = 'Mengirim bukti...';
    });
}

// ---------- berhasil ----------
if (document.getElementById('modalBerhasil')) {
    document.body.classList.add('ada-modal');
}