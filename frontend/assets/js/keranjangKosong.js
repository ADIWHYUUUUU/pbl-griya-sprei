// =====================================================
// SCRIPT HALAMAN KERANJANG KOSONG - GRIYA SPREI
// Letak file: pbl-griya-sprei/frontend/assets/js/keranjangKosong.js
// =====================================================


// ---------- 1. Animasi muncul (kode asli) ----------
// Setelah halaman selesai dimuat,
// tambahkan class "muncul" supaya kotak tampil pelan-pelan.
window.addEventListener('load', function () {
    var kotak = document.getElementById('kotakKosong');
    if (kotak) {
        kotak.classList.add('muncul');
    }
});


// ---------- 2. Ikon keranjang bergoyang kecil saat diarahkan mouse ----------
var ikon = document.querySelector('.ikon-kosong');

if (ikon) {
    ikon.style.transition = 'transform 0.3s ease';

    ikon.addEventListener('mouseenter', function () {
        ikon.style.transform = 'rotate(-8deg) scale(1.05)';
    });

    ikon.addEventListener('mouseleave', function () {
        ikon.style.transform = '';
    });
}


// ---------- 3. Tombol "Jelajahi Katalog" memberi tanda sedang memuat ----------
var tombolKatalog = document.querySelector('.kosong-besar .tombol');
var teksAsli = tombolKatalog ? tombolKatalog.innerHTML : '';

if (tombolKatalog) {
    tombolKatalog.addEventListener('click', function () {
        tombolKatalog.textContent = 'Membuka katalog...';
        tombolKatalog.style.opacity = '0.7';
        tombolKatalog.style.pointerEvents = 'none';   // cegah klik dua kali
    });
}


// ---------- 4. Kembali lewat tombol Back browser -> tombol normal lagi ----------
window.addEventListener('pageshow', function () {
    if (tombolKatalog) {
        tombolKatalog.innerHTML = teksAsli;
        tombolKatalog.style.opacity = '';
        tombolKatalog.style.pointerEvents = '';
    }
});