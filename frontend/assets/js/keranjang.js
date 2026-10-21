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

// ---------- Animasi muncul ----------
window.addEventListener('load', function () {
    var kotak = document.getElementById('kotakKosong');
    if (kotak) {
        kotak.classList.add('muncul');
    }
});


// ---------- Ikon keranjang bergoyang----------
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


// ----------Jelajahi Katalog ----------
var tombolKatalog = document.querySelector('.kosong-besar .tombol');
var teksAsli = tombolKatalog ? tombolKatalog.innerHTML : '';

if (tombolKatalog) {
    tombolKatalog.addEventListener('click', function () {
        tombolKatalog.textContent = 'Membuka katalog...';
        tombolKatalog.style.opacity = '0.7';
        tombolKatalog.style.pointerEvents = 'none'; 
    });
}


// ---------- tombol kembali  ----------
window.addEventListener('pageshow', function () {
    if (tombolKatalog) {
        tombolKatalog.innerHTML = teksAsli;
        tombolKatalog.style.opacity = '';
        tombolKatalog.style.pointerEvents = '';
    }
});