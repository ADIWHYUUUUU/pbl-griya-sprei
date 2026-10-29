// Konfirmasi sebelum menghapus produk
document.querySelectorAll('.btn-hapus').forEach(function (tombol) {
    tombol.addEventListener('click', function (e) {
        var nama = tombol.dataset.nama || 'produk ini';
        if (!confirm('Hapus "' + nama + '"?')) {
            e.preventDefault();
        }
    });
});
