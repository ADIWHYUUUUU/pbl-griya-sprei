// =====================================================
// SCRIPT HALAMAN KERANJANG KOSONG - GRIYA SPREI
// =====================================================


// Animasi muncul: setelah halaman selesai dimuat,
// tambahkan class "muncul" supaya kotak tampil pelan-pelan.
window.addEventListener('load', function () {
    var kotak = document.getElementById('kotakKosong');
    kotak.classList.add('muncul');
});