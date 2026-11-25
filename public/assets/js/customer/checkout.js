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
var tombolBatalAlamat = document.getElementById('tombolBatalAlamat');
var inputAlamat = document.getElementById('inputAlamat');
var inputAlamatTersimpan = document.getElementById('alamatTersimpanInput');
var inputCatatanAlamat = document.getElementById('inputCatatanAlamat');
var checkoutLayout = document.querySelector('.checkout-layout');
var addressSaved = checkoutLayout && checkoutLayout.dataset.addressSaved === 'true';
var addressSavedPanel = document.getElementById('addressSaved');
var addressEditor = document.getElementById('addressEditor');
var checklistAddress = document.getElementById('checkAddress');
var checklistShipping = document.getElementById('checkShipping');
var checklistAgreement = document.getElementById('checkAgreement');
var summaryShipping = document.getElementById('summaryShipping');

var setAddressEditor = function (isEditing) {
    if (!addressEditor || !addressSavedPanel || !inputAlamat || !inputAlamatTersimpan) {
        return;
    }

    addressEditor.hidden = !isEditing;
    addressSavedPanel.hidden = isEditing;
    inputAlamat.disabled = !isEditing;
    inputAlamatTersimpan.disabled = isEditing;
    if (inputCatatanAlamat) {
        inputCatatanAlamat.disabled = !isEditing;
    }
    updateCheckoutState();
};

var setChecklistState = function (element, complete) {
    if (!element) {
        return;
    }

    element.classList.toggle('complete', complete);
};

var updateCheckoutState = function () {
    var method = document.querySelector('input[name="metode"]:checked');
    var addressValue = inputAlamat && !inputAlamat.disabled
        ? inputAlamat.value.trim()
        : (inputAlamatTersimpan ? inputAlamatTersimpan.value.trim() : '');
    var hasAddress = addressSaved && addressValue !== '';
    var hasShipping = Boolean(method);
    var hasAgreement = Boolean(cekSetuju && cekSetuju.checked);

    setChecklistState(checklistAddress, hasAddress);
    setChecklistState(checklistShipping, hasShipping);
    setChecklistState(checklistAgreement, hasAgreement);
    if (checklistAddress) {
        checklistAddress.textContent = hasAddress ? 'Alamat tersimpan' : 'Alamat belum disimpan';
    }
    if (summaryShipping && method) {
        var selectedOption = method.closest('.opsi');
        var courierName = selectedOption ? selectedOption.querySelector('.courier-copy strong') : null;
        if (courierName) {
            summaryShipping.textContent = courierName.textContent;
        }
    }
    if (tombolKirim) {
        tombolKirim.disabled = !(hasAddress && hasShipping && hasAgreement);
    }
};

if (tombolUbahAlamat) {
    tombolUbahAlamat.addEventListener('click', function () {
        setAddressEditor(true);
        inputAlamat.focus();
    });
}

if (tombolBatalAlamat) {
    tombolBatalAlamat.addEventListener('click', function () {
        inputAlamat.value = inputAlamatTersimpan.value;
        setAddressEditor(false);
    });
}

if (inputAlamat) {
    inputAlamat.addEventListener('input', updateCheckoutState);
}

semuaOpsi.forEach(function (opsi) {
    opsi.querySelector('input').addEventListener('change', updateCheckoutState);
});

if (cekSetuju && tombolKirim) {
    cekSetuju.addEventListener('change', function () {
        cekSetuju.closest('.persetujuan').classList.remove('perlu');
        updateCheckoutState();
    });
}

updateCheckoutState();

// ---------- konfirmasi pesanan ----------
var formCheckout = document.getElementById('formCheckout');

if (formCheckout && cekSetuju && tombolKirim) {
    formCheckout.addEventListener('submit', function (event) {
        var submitter = event.submitter || document.activeElement;
        if (submitter && submitter.hasAttribute('data-save-address')) {
            return;
        }

        if (!cekSetuju.checked) {
            event.preventDefault();
            cekSetuju.closest('.persetujuan').classList.add('perlu');
            return;
        }

        var alamat = inputAlamat && !inputAlamat.disabled ? inputAlamat : inputAlamatTersimpan;
        if (!alamat || !alamat.value.trim()) {
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
