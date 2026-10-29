<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Griya Sprei</title>
    <!-- Memanggil file CSS -->
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/dasbord.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main>
        <div class="header-title">Dashboard</div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Pesanan Baru</div>
                <div class="value">4</div>
                <div class="subtext">Pesanan baru</div>
            </div>

            <div class="stat-card">
                <div class="label">Verifikasi Transfer</div>
                <div class="value">2</div>
                <div class="subtext">Menunggu Konfirmasi</div>
            </div>

            <div class="stat-card stat-card-stock" data-open-modal="modalStok" role="button" tabindex="0">
                <div class="label">Stok Sprei</div>
                <div class="value">70</div>
                <div class="subtext">Total Stok Tersedia</div>
                <div class="badge-top-right">Lihat Stok</div>
            </div>
        </div>

        <div class="chart-section">
            <div class="section-header">
                <h3>Grafik Penjualan</h3>
                <button class="btn-text" data-open-modal="modalLaporan" type="button">Lihat laporan</button>
            </div>

            <div class="sales-metrics">
                <div class="metric-item">
                    <div class="m-label">Total Penjualan</div>
                    <div class="m-val">Rp 13.750.000</div>
                </div>
                <div class="metric-item">
                    <div class="m-label">Total Pesanan Selesai</div>
                    <div class="m-val">30 pesanan</div>
                </div>
            </div>

            <div class="chart-legend">
                <span class="legend-item"><span class="legend-dot dark"></span> Penjualan</span>
                <span class="legend-item"><span class="legend-dot light"></span> Pesanan</span>
            </div>

            <div class="chart-bars" aria-label="Grafik penjualan bulanan">
                <div class="bar-group">
                    <div class="bar-stack">
                        <div class="bar bar-dark" style="height: 55%"></div>
                        <div class="bar bar-light" style="height: 32%"></div>
                    </div>
                    <span>Minggu 1</span>
                </div>
                <div class="bar-group">
                    <div class="bar-stack">
                        <div class="bar bar-dark" style="height: 72%"></div>
                        <div class="bar bar-light" style="height: 42%"></div>
                    </div>
                    <span>Minggu 2</span>
                </div>
                <div class="bar-group">
                    <div class="bar-stack">
                        <div class="bar bar-dark" style="height: 48%"></div>
                        <div class="bar bar-light" style="height: 25%"></div>
                    </div>
                    <span>Minggu 3</span>
                </div>
                <div class="bar-group">
                    <div class="bar-stack">
                        <div class="bar bar-dark" style="height: 84%"></div>
                        <div class="bar bar-light" style="height: 46%"></div>
                    </div>
                    <span>Minggu 4</span>
                </div>
            </div>
        </div>

        <div class="verification-box">
            <div class="section-header">
                <h3>Membutuhkan Verifikasi</h3>
            </div>

            <div class="verif-item">
                <div>
                    <strong>Kurniawati / Toko Kurnia</strong><br>
                    <span class="subtext-item">Transfer pembayaran produk fisik. Belanja di pasar 1.</span>
                </div>
                <button class="btn-text" type="button">Cek Pesanan</button>
            </div>

            <div class="verif-item">
                <div>
                    <strong>Verifikasi Bukti Transfer</strong><br>
                    <span class="subtext-item">Pesanan menunggu konfirmasi pembayaran dari kasir.</span>
                </div>
                <button class="btn-text" type="button">Cek Pesanan</button>
            </div>
        </div>

        <div class="table-box">
            <div class="section-header">
                <h3>Daftar Pesanan</h3>
                <button class="btn-text" type="button">Lihat Pesanan</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Kode Pesanan</th>
                        <th>Pelanggan & Produk</th>
                        <th>Tanggal</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>ORD-1001</td>
                        <td>Radia Putri<br><small class="subtext-item">Sprei Katun Motif Bunga</small></td>
                        <td>23 Okt 2026</td>
                        <td>Rp 230.000</td>
                        <td><span class="badge warning">Menunggu</span></td>
                        <td><button class="btn-text" data-open-modal="modalDetail" type="button">Lihat detail</button></td>
                    </tr>
                    <tr>
                        <td>ORD-1002</td>
                        <td>Rizki Amelia<br><small class="subtext-item">Sprei Katun Motif Bunga</small></td>
                        <td>23 Okt 2026</td>
                        <td>Rp 195.000</td>
                        <td><span class="badge info">Siap Kirim</span></td>
                        <td><button class="btn-text" data-open-modal="modalDetail" type="button">Lihat detail</button></td>
                    </tr>
                    <tr>
                        <td>ORD-1003</td>
                        <td>Dina Safitri<br><small class="subtext-item">Sprei Katun Polos Custom Earth Tone</small></td>
                        <td>23 Okt 2026</td>
                        <td>Rp 315.000</td>
                        <td><span class="badge neutral">Dalam Proses</span></td>
                        <td><button class="btn-text" data-open-modal="modalDetail" type="button">Lihat detail</button></td>
                    </tr>
                    <tr>
                        <td>ORD-1004</td>
                        <td>Ayu Lestari<br><small class="subtext-item">Sprei Custom King Size + Sarung Bantal</small></td>
                        <td>21 Okt 2026</td>
                        <td>Rp 420.000</td>
                        <td><span class="badge warning">Pesanan Baru</span></td>
                        <td><button class="btn-text" data-open-modal="modalDetail" type="button">Lihat detail</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>

    <div class="modal-overlay" id="modalLaporan">
        <div class="modal-container modal-lg">
            <div class="modal-header">
                <div class="modal-title-row">
                    <div class="modal-icon-box">▣</div>
                    <div>
                        <h3>Laporan &amp; Ringkasan Penjualan Bulanan</h3>
                        <p>Ikhtisar grafik penjualan dan rekapitulasi transaksi bulanan</p>
                    </div>
                </div>
                <button class="close-btn" data-close-modal="modalLaporan" type="button">&times;</button>
            </div>

            <div class="modal-body">
                <div class="report-summary-box">
                    <div>
                        <small class="subtext-item">TOTAL PENJUALAN</small>
                        <div class="summary-val">Rp 13.750.000</div>
                    </div>
                    <div class="summary-right">
                        <small class="subtext-item">TRANSAKSI SELESAI</small>
                        <div class="summary-val">30 pesanan</div>
                    </div>
                </div>

                <div class="chart-mini-grid">
                    <div class="mini-card">
                        <div class="mini-card-title">Rp. 3 jt</div>
                        <div class="mini-bar" style="height: 52%"></div>
                        <span>Minggu 1<br><small>(1-7 Okt)</small></span>
                    </div>
                    <div class="mini-card">
                        <div class="mini-card-title">Rp. 3 jt</div>
                        <div class="mini-bar" style="height: 70%"></div>
                        <span>Minggu 2<br><small>(8-14 Okt)</small></span>
                    </div>
                    <div class="mini-card">
                        <div class="mini-card-title">Rp. 4 jt</div>
                        <div class="mini-bar" style="height: 58%"></div>
                        <span>Minggu 3<br><small>(15-21 Okt)</small></span>
                    </div>
                    <div class="mini-card">
                        <div class="mini-card-title">Rp. 3,75 jt</div>
                        <div class="mini-bar" style="height: 80%"></div>
                        <span>Minggu 4<br><small>(22-30 Okt)</small></span>
                    </div>
                </div>

                <div class="table-title">Daftar Pesanan &amp; Transaksi Verifikasi (30 Hari Terakhir)</div>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>NO. INVOICE / KODE</th>
                            <th>TANGGAL</th>
                            <th>PELANGGAN &amp; PRODUK</th>
                            <th>TOTAL</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>ORD-1001</td>
                            <td>23 Okt 2026</td>
                            <td>Radia Putri<br><small>Sprei Katun Motif Bunga</small></td>
                            <td>Rp 230.000</td>
                            <td><span class="badge warning">Menunggu</span></td>
                        </tr>
                        <tr>
                            <td>ORD-1002</td>
                            <td>22 Okt 2026</td>
                            <td>Rizki Amelia<br><small>Sprei Katun Motif Bunga</small></td>
                            <td>Rp 195.000</td>
                            <td><span class="badge info">Siap Kirim</span></td>
                        </tr>
                        <tr>
                            <td>ORD-1003</td>
                            <td>21 Okt 2026</td>
                            <td>Dina Safitri<br><small>Sprei Katun Polos</small></td>
                            <td>Rp 315.000</td>
                            <td><span class="badge neutral">Diproses</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-close-modal="modalLaporan" type="button">Tutup</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modalStok">
        <div class="modal-container modal-stock-list">
            <div class="modal-header compact-header">
                <div class="modal-title-wrap">
                    <span class="modal-list-icon">▣</span>
                    <h3>LIHAT STOK</h3>
                </div>
                <button class="close-btn" data-close-modal="modalStok" type="button">&times;</button>
            </div>

            <div class="modal-body stock-list-body">
                <div class="stock-row">
                    <div class="stock-name-wrap">
                        <span class="stock-name">Hilton - Light Grey (King 180)</span>
                        <div class="stock-progress"><span style="width: 60%"></span></div>
                    </div>
                    <span class="stock-qty">10 set</span>
                </div>

                <div class="stock-row">
                    <div class="stock-name-wrap">
                        <span class="stock-name">Asterina Burgundy (Queen 160)</span>
                        <div class="stock-progress"><span style="width: 45%"></span></div>
                    </div>
                    <span class="stock-qty">25 set</span>
                </div>

                <div class="stock-row">
                    <div class="stock-name-wrap">
                        <span class="stock-name">Beverly Roland (Single 120)</span>
                        <div class="stock-progress"><span style="width: 70%"></span></div>
                    </div>
                    <span class="stock-qty">35 set</span>
                </div>
            </div>

            <div class="modal-footer stock-footer">
                <button class="btn btn-primary btn-tambah-stok" data-open-modal="modalTambahStok" type="button">+ Tambah Stok Sprei</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modalTambahStok">
        <div class="modal-container modal-add-stock">
            <div class="modal-header compact-header">
                <div class="modal-title-wrap">
                    <span class="modal-plus-icon">＋</span>
                    <h3>Tambah / Restock Stok Sprei</h3>
                </div>
                <button class="close-btn" data-close-modal="modalTambahStok" type="button">&times;</button>
            </div>

            <div class="modal-body add-stock-body">
                <div class="form-group">
                    <label>Pilih varian sprei ready</label>
                    <select class="form-control">
                        <option>Hilton - Light Grey (King 180x200)</option>
                        <option>Asterina Burgundy (Queen 160)</option>
                        <option>Beverly Roland (Single 120)</option>
                    </select>
                </div>

                <div class="form-row two-cols">
                    <div class="form-group flex-1">
                        <label>Stok Saat Ini</label>
                        <div class="inline-readonly">Tersisa 2 set</div>
                    </div>
                    <div class="form-group flex-1">
                        <label>Batas Aman Stok</label>
                        <div class="inline-readonly">10 set</div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Jumlah Tambahan Stok (set)</label>
                    <div class="stepper-box">
                        <button class="stepper-btn" type="button">−</button>
                        <span>8</span>
                        <button class="stepper-btn" type="button">＋</button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Sumber Masuk / Keterangan (opsional)</label>
                    <select class="form-control">
                        <option>Selesai Jahit Workshop Sendiri</option>
                        <option>Supplier</option>
                        <option>Stok Produksi</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Catatan tambahan</label>
                    <textarea class="form-control textarea" rows="3" placeholder="Catatan tambahan (misal: selesai jahit mbak Siti)"></textarea>
                </div>
            </div>

            <div class="modal-footer add-stock-footer">
                <button class="btn btn-secondary" data-close-modal="modalTambahStok" type="button">Batal</button>
                <button class="btn btn-primary" type="button">✓ Simpan Tambah Stok</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modalDetail">
        <div class="modal-container modal-detail-panel">
            <div class="modal-header detail-header">
                <div class="modal-detail-title">
                    <span class="modal-detail-icon">▣</span>
                    <div>
                        <h3>Detail Pesanan Ringkas</h3>
                        <small>Invoice: INV/20261012/SP/001</small>
                    </div>
                </div>
                <button class="close-btn" data-close-modal="modalDetail" type="button">&times;</button>
            </div>

            <div class="modal-body detail-body">
                <div class="detail-grid">
                    <div class="detail-card">
                        <div class="detail-label">Pelanggan</div>
                        <div class="detail-name">Dimas Setiawan</div>
                        <div class="detail-meta">☎ 0822-9823-0982 (WhatsApp)</div>
                        <div class="detail-meta">📍 Lowokwaru, Malang</div>
                    </div>

                    <div class="detail-card detail-card--right">
                        <div class="detail-label">Total Tagihan</div>
                        <div class="detail-price">Rp 230.000</div>
                        <div class="detail-meta small">Status: Belum Bayar (DP Min Rp 107.500)</div>
                    </div>

                    <div class="detail-card wide-card">
                        <div class="detail-label">Produk Sprei</div>
                        <div class="detail-name product-name">Asterina Sage Green</div>
                        <div class="detail-meta">Ukuran: 200 x 180 x 30 cm</div>
                        <div class="detail-meta">Kelengkapan: 2 Sarung + 2 Sarung</div>
                        <div class="detail-meta">Catatan: Karet sudut ekstra kencang tinggi 30cm</div>
                    </div>

                    <div class="detail-card detail-card--status">
                        <div class="detail-label">Status Pesanan</div>
                        <div class="status-box">Menunggu Verifikasi Pembayaran</div>
                        <div class="status-box muted">Catatan untuk Admin</div>
                        <div class="status-note">Kain dipotong dari roll rak A2 Gudang Malang</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/sidebar.js"></script>
    <script>
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.style.display = 'flex';
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.style.display = 'none';
        }

        document.querySelectorAll('[data-open-modal]').forEach(function (button) {
            button.addEventListener('click', function () {
                openModal(this.dataset.openModal);
            });
        });

        document.querySelectorAll('[data-close-modal]').forEach(function (button) {
            button.addEventListener('click', function () {
                closeModal(this.dataset.closeModal);
            });
        });

        document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
            overlay.addEventListener('click', function (event) {
                if (event.target === overlay) {
                    closeModal(overlay.id);
                }
            });
        });
    </script>
</body>
</html>