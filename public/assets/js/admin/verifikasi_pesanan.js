document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.verification-form');
    const fields = ['tagihanPokok', 'ongkir', 'diskon'].map(function (id) {
        return document.getElementById(id);
    });
    const total = document.getElementById('totalTagihan');

    if (!form || fields.some(function (field) { return !field; }) || !total) return;

    const formatRupiah = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    });

    function updateTotal() {
        const principal = Number(fields[0].value) || 0;
        const shipping = Number(fields[1].value) || 0;
        const discount = Number(fields[2].value) || 0;
        total.textContent = formatRupiah.format(Math.max(0, principal + shipping - discount));
    }

    fields.forEach(function (field) {
        field.addEventListener('input', updateTotal);
    });
});
