/**
 * Griya Sprei - Interaksi Frontend
 * Menggunakan JS Murni (Vanilla JS) yang bersih & efisien.
 */

// 1. Fungsi Umum Membuka Modal Pop-up
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
    }
}

// 2. Fungsi Umum Menutup Modal Pop-up
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// 3. Menutup Modal secara otomatis jika user mengklik area luar modal (overlay)
window.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.style.display = 'none';
    }
});

// 4. Handler Aksi Tombol di Modal (Contoh Integrasi Sederhana)
function confirmOrder() {
    alert('Pesanan berhasil dikonfirmasi!');
    closeModal('modalDetail');
}

function saveStock() {
    alert('Stok sprei berhasil ditambahkan!');
    closeModal('modalStok');
}