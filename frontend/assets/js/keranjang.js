// =====================================================
// SCRIPT HALAMAN KERANJANG - GRIYA SPREI
// =====================================================


// Konfirmasi sebelum menghapus produk
var semuaFormHapus = document.querySelectorAll('.form-hapus');

semuaFormHapus.forEach(function (form) {
    form.addEventListener('submit', function (event) {
        var yakin = confirm('Yakin ingin menghapus produk ini dari keranjang?');

        if (!yakin) {
            event.preventDefault();   // batal kirim form
        }
    });
});