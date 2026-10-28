<?php
session_start();

function e($teks) {
    return htmlspecialchars($teks, ENT_QUOTES, 'UTF-8');
}


$pemesan = ['nama' => 'Vico Dwi Wijaya', 'telepon' => '+62 812-4491-1980', 'email' => 'vicodwiwijaya@gmail.com'];
$alamatAwal  = 'Jl. Ngawi Barat No. 12, RT 03 / RW 07, Kel. Ngawi Barat, Kec. Lowokwaru, Kota Malang, Jawa Timur (Kode Pos 65141)';
$catatanAwal = 'Rumah pagar hitam samping warung Bu Sri, pagar selalu dibuka saja tidak dikunci.';

$infoReguler = 'Pengiriman reguler dengan jaringan kemitraan ke seluruh area di Indonesia.';
$metodeKirim = [
    'instan' => ['kelompok' => 'Kurir Instan', 'jenis' => 'instan', 'nama' => 'Gojek / Grab Express',
                 'info' => 'Khusus area Kota Malang', 'waktu' => 'Pesanan dikirim di hari yang sama setelah produksi selesai'],
    'jnt'    => ['kelompok' => 'Ekspedisi Reguler', 'jenis' => 'reguler', 'nama' => 'J&T Express', 'info' => $infoReguler, 'waktu' => 'Perkiraan: 1 – 3 hari'],
    'jne'    => ['kelompok' => 'Ekspedisi Reguler', 'jenis' => 'reguler', 'nama' => 'JNE Express', 'info' => $infoReguler, 'waktu' => 'Perkiraan: 1 – 3 hari'],
];

if (isset($_GET['berhasil'], $_SESSION['pesanan_terakhir'])) {
    $pesananBaru = $_SESSION['pesanan_terakhir'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int) ($_POST['id'] ?? 0);
    $metode = $_POST['metode'] ?? '';

    if (!isset($_SESSION['keranjang'][$id], $metodeKirim[$metode], $_POST['setuju'])) {
        header('Location: checkout.php?id=' . $id);
        exit;
    }
    
    $produk = $_SESSION['keranjang'][$id];
    $p = max(1, (int) ($_POST['panjang'] ?? 0));
    $l = max(1, (int) ($_POST['lebar'] ?? 0));
    $t = max(1, (int) ($_POST['tinggi'] ?? 0));
    $bantal = max(0, (int) ($_POST['bantal'] ?? 0));
    $guling = max(0, (int) ($_POST['guling'] ?? 0));

    $tambahan = [];
    if ($bantal > 0) $tambahan[] = $bantal . ' sarung bantal';
    if ($guling > 0) $tambahan[] = $guling . ' sarung guling';

    $produk['jenis']    = ($_POST['jenis'] ?? '') === 'custom' ? 'custom' : 'standar';
    $produk['ukuran']   = "$p × $l × $t cm";
    $produk['tambahan'] = $tambahan ? implode(' + ', $tambahan) : '-';
    $produk['catatan']  = trim($_POST['catatan_produk'] ?? '') ?: '-';

    $pesanan = [
        'kode'     => 'GS-' . date('ymd') . '-' . str_pad($id, 3, '0', STR_PAD_LEFT),
        'produk'   => $produk,
        'penerima' => trim($_POST['nama_penerima'] ?? '') ?: $pemesan['nama'],
        'telepon'  => trim($_POST['telepon'] ?? '') ?: $pemesan['telepon'],
        'alamat'   => trim($_POST['alamat']) ?: $alamatAwal,
        'catatan_alamat' => trim($_POST['catatan_alamat']),
        'metode'   => $metodeKirim[$metode]['nama'],
        'catatan_kirim' => trim($_POST['catatan_kirim']),
        'status'   => 'Menunggu Verifikasi',
        'waktu'    => date('d M Y, H:i'),
    ];

    $_SESSION['pesanan'][] = $_SESSION['pesanan_terakhir'] = $pesanan;
    unset($_SESSION['keranjang'][$id]);

    header('Location: checkout.php?berhasil=1');
    exit;
}

if (!isset($pesananBaru)) {
    $id = (int) ($_GET['id'] ?? 0);

    if (!isset($_SESSION['keranjang'][$id])) {
        header('Location: keranjang.php');
        exit;
    }

    $produk = $_SESSION['keranjang'][$id];

    preg_match_all('/\d+/', $produk['ukuran'], $angka);
    $dimensi = array_combine(['panjang', 'lebar', 'tinggi'], array_pad(array_slice($angka[0], 0, 3), 3, ''));
    $jumlahSarung = [];
    foreach (['bantal', 'guling'] as $s) {
        $jumlahSarung[$s] = preg_match('/(\d+)\s*sarung ' . $s . '/i', $produk['tambahan'], $m) ? (int) $m[1] : 0;
    }
}

$ikonCentang = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Griya Sprei</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/checkout.css">
</head>
<body>

<main class="container">
<div class="isi">

    <nav class="breadcrumb">
        <a href="beranda.html">Beranda</a> /
        <a href="keranjang.php">Keranjang</a> /
        <b>Checkout</b>
    </nav>

<?php if (isset($pesananBaru)) { ?>

    <!-- ================= PESANAN BERHASIL DIKIRIM ================= -->
    <div class="modal-latar tampil" id="modalSukses">
        <div class="modal modal-sukses" role="dialog" aria-modal="true" aria-labelledby="judulSukses">
            <div class="ikon-sukses">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5 4.5-5"/></svg>
            </div>
            <h2 id="judulSukses">Pesanan Berhasil Dikirim!</h2>
            <p>Pesanan Anda telah diterima oleh sistem kami. Admin sedang memverifikasi pesanan dan
               menghitung total biaya + ongkos kirim.</p>
            <p class="kode-pesanan">Kode pesanan: <b><?= e($pesananBaru['kode']) ?></b> · <?= e($pesananBaru['produk']['nama']) ?></p>
            <div class="aksi-sukses">
                <a href="pesananSaya.php" class="tombol tombol-lebar">Lihat Pesanan Saya</a>
                <a href="keranjang.php" class="tombol tombol-abu">Tutup</a>
            </div>
        </div>
    </div>

<?php } else { ?>

    <form method="post" id="formCheckout">
        <input type="hidden" name="id" value="<?= $id ?>">

        <!-- ================= DATA & ALAMAT ================= -->
        <section class="kartu">
            <div class="kepala">
                <span class="ikon-bulat">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
                </span>
                <div>
                    <h2>Data &amp; Alamat Pengiriman</h2>
                    <small>Informasi pemesan dan tujuan pengiriman</small>
                </div>
                <button type="button" class="tombol-kecil" data-buka-modal="modalAlamat">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m14 5 5 5-10 10H4v-5z"/></svg>
                    Ubah Alamat
                </button>
            </div>

            <div class="dua-kolom">
                <div>
                    <small class="judul-kecil">IDENTITAS PEMESAN</small>
                    <p class="baris-ikon"><b id="teksNama"><?= e($pemesan['nama']) ?></b></p>
                    <p class="baris-ikon">📞 <span id="teksTelepon"><?= e($pemesan['telepon']) ?></span></p>
                    <p class="baris-ikon">✉️ <?= e($pemesan['email']) ?></p>
                </div>
                <div class="kotak-alamat">
                    <small class="judul-kecil">ALAMAT PENGIRIMAN LENGKAP</small>
                    <p id="teksAlamat"><?= e($alamatAwal) ?></p>
                    <p class="catatan-alamat" id="kotakCatatanAlamat">ⓘ <b>Catatan alamat:</b> <span id="teksCatatanAlamat"><?= e($catatanAwal) ?></span></p>
                </div>
            </div>
        </section>

        <!-- ================= DETAIL PESANAN ================= -->
        <section class="kartu">
            <div class="kepala">
                <span class="ikon-bulat">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
                </span>
                <div>
                    <h2>Detail Pesanan</h2>
                    <small>Konfirmasi detail spesifikasi pesanan</small>
                </div>
                <button type="button" class="tombol-kecil" data-buka-modal="modalSpesifikasi">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 20 20 4M7 4h-3v3M20 17v3h-3"/></svg>
                    Ubah Spesifikasi
                </button>
            </div>

            <div class="produk">
                <img src="<?= e($produk['gambar']) ?>" alt="<?= e($produk['nama']) ?>" class="foto-produk">
                <div class="detail">
                    <h3>
                        <?= e($produk['nama']) ?>
                        <span class="label label-<?= e($produk['jenis']) ?>" id="teksJenis"><?= strtoupper(e($produk['jenis'])) ?></span>
                    </h3>
                    <p><small>Ukuran:</small> <span id="teksUkuran"><?= e($produk['ukuran']) ?></span> &nbsp;&nbsp; <small>Jumlah:</small> <?= (int) $produk['jumlah'] ?> pcs</p>
                    <p><small>Tambahan:</small> <span id="teksTambahan"><?= e($produk['tambahan']) ?></span></p>
                    <p class="catatan-produk"><b>Catatan:</b> <span id="teksCatatanProduk"><?= e($produk['catatan']) ?></span></p>
                </div>
            </div>
        </section>

        <!-- ================= METODE PENGIRIMAN ================= -->
        <section class="kartu">
            <div class="kepala">
                <span class="ikon-bulat">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/></svg>
                </span>
                <div>
                    <h2>Metode Pengiriman</h2>
                    <small>Pilih preferensi pengiriman produk</small>
                </div>
            </div>

            <div class="pilihan-kirim">
                <?php $pertama = true; foreach ($metodeKirim as $kode => $m) { ?>
                <label class="opsi <?= $pertama ? 'terpilih' : '' ?>">
                    <input type="radio" name="metode" value="<?= $kode ?>" <?= $pertama ? 'checked' : '' ?>>
                    <span class="kelompok kelompok-<?= $m['jenis'] ?>"><?= e($m['kelompok']) ?></span>
                    <b><?= e($m['nama']) ?></b>
                    <small><?= e($m['info']) ?></small>
                    <small class="waktu"><?= e($m['waktu']) ?></small>
                </label>
                <?php $pertama = false; } ?>
            </div>

            <label class="label-catatan">Catatan pengiriman <small>(Opsional)</small>
                <textarea name="catatan_kirim" rows="2" placeholder="Contoh: Titipkan di satpam jika rumah sedang kosong, hubungi nomor ini sebelum datang"></textarea>
            </label>
        </section>

        <!-- ================= ALUR & PERSETUJUAN ================= -->
        <section class="kartu kartu-info">
            <div class="kepala">
                <span class="ikon-bulat">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                </span>
                <div>
                    <h2>Alur Penetapan Total &amp; Validasi Admin</h2>
                    <p class="teks-alur">
                        Setelah Anda menekan tombol <b>"Kirim Pesanan"</b> di bawah, pesanan akan diteruskan ke sistem
                        produksi dengan status <span class="status">MENUNGGU VERIFIKASI</span>. Admin akan segera
                        memverifikasi pesanan dan menghitung total biaya serta ongkos kirim sesuai alamat Anda,
                        kemudian menerbitkan invoice resmi.
                    </p>
                </div>
            </div>

            <label class="persetujuan">
                <input type="checkbox" name="setuju" id="cekSetuju">
                <span>Saya mengonfirmasi bahwa data ukuran kasur (P × L × T) sudah diukur secara akurat. Saya mengerti
                      bahwa pesanan yang telah masuk tahap pemotongan kain tidak dapat dibatalkan atau diubah.</span>
            </label>
        </section>

        <!-- ================= TOMBOL KIRIM ================= -->
        <div class="bagian-kirim">
            <button type="submit" class="tombol tombol-besar" id="tombolKirim" disabled>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4z"/></svg>
                Kirim Pesanan
            </button>
            <small>🕓 Verifikasi Admin: Maks 1×24 Jam</small>
        </div>


        <!-- ================= MODAL: UBAH ALAMAT PENGIRIMAN ================= -->
        <div class="modal-latar" id="modalAlamat">
            <div class="modal" role="dialog" aria-modal="true" aria-labelledby="judulModalAlamat">
                <div class="modal-kepala">
                    <h2 id="judulModalAlamat">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4 6v6c0 4.5 3.4 8.3 8 9 4.6-.7 8-4.5 8-9V6z"/><path d="m9 12 2 2 4-4"/></svg>
                        Ubah Alamat Pengiriman
                    </h2>
                    <button type="button" class="tutup-modal" data-tutup-modal aria-label="Tutup">×</button>
                </div>

                <div class="modal-isi">
                    <label class="isian">Nama Penerima
                        <input type="text" name="nama_penerima" id="inputNama" value="<?= e($pemesan['nama']) ?>">
                    </label>
                    <label class="isian">Nomor WhatsApp
                        <input type="tel" name="telepon" id="inputTelepon" value="<?= e($pemesan['telepon']) ?>">
                    </label>
                    <label class="isian">Alamat Lengkap &amp; Kota
                        <textarea name="alamat" id="inputAlamat" rows="3"><?= e($alamatAwal) ?></textarea>
                    </label>
                    <label class="isian">Catatan Khusus Pengiriman <small>(Opsional)</small>
                        <input type="text" name="catatan_alamat" id="inputCatatanAlamat" value="<?= e($catatanAwal) ?>">
                    </label>
                </div>

                <div class="modal-kaki">
                    <button type="button" class="tombol tombol-abu" data-tutup-modal>Batal</button>
                    <button type="button" class="tombol" id="simpanAlamat"><?= $ikonCentang ?> Simpan Perubahan</button>
                </div>
            </div>
        </div>


        <!-- ================= MODAL: UBAH SPESIFIKASI PESANAN ================= -->
        <div class="modal-latar" id="modalSpesifikasi">
            <div class="modal modal-lebar" role="dialog" aria-modal="true" aria-labelledby="judulModalSpek">
                <div class="modal-kepala">
                    <div>
                        <h2 id="judulModalSpek">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17 17 3l4 4L7 21H3z"/><path d="m8 12 2 2M11 9l2 2M14 6l2 2"/></svg>
                            Ubah Spesifikasi Pesanan
                        </h2>
                        <small><?= e($produk['nama']) ?></small>
                    </div>
                    <button type="button" class="tutup-modal" data-tutup-modal aria-label="Tutup">×</button>
                </div>

                <div class="modal-isi">
                    <small class="judul-bagian">TIPE UKURAN</small>
                    <div class="pilih-tipe">
                        <?php foreach (['standar' => 'Standar', 'custom' => 'Custom'] as $nilai => $teks) { ?>
                        <label>
                            <input type="radio" name="jenis" value="<?= $nilai ?>" <?= $produk['jenis'] === $nilai ? 'checked' : '' ?>>
                            <span><?= $teks ?></span>
                        </label>
                        <?php } ?>
                    </div>

                    <small class="judul-bagian">DIMENSI KASUR</small>
                    <div class="tiga-kolom">
                        <?php foreach (['panjang' => 'Panjang', 'lebar' => 'Lebar', 'tinggi' => 'Tinggi Kasur'] as $nama => $teks) { ?>
                        <label class="isian"><?= $teks ?>
                            <span class="input-satuan"><input type="number" min="1" name="<?= $nama ?>" id="input<?= ucfirst($nama) ?>" value="<?= e($dimensi[$nama]) ?>"><i>cm</i></span>
                        </label>
                        <?php } ?>
                    </div>
                    <p class="info-tinggi">ⓘ Tinggi standar kasur: 25 cm. Tambahan penyesuaian tinggi per 5 cm: <b>+Rp 15.000</b>.</p>

                    <small class="judul-bagian">TAMBAHKAN SARUNG BANTAL DAN GULING</small>
                    <div class="dua-penghitung">
                        <?php foreach ($jumlahSarung as $nama => $jumlah) { $idInput = 'input' . ucfirst($nama); ?>
                        <div class="penghitung-kotak">
                            <div>
                                <b>Sarung <?= ucfirst($nama) ?></b>
                                <small>+Rp 25.000 / pcs</small>
                            </div>
                            <div class="penghitung">
                                <button type="button" data-kurang="<?= $idInput ?>">−</button>
                                <input type="number" min="0" name="<?= $nama ?>" id="<?= $idInput ?>" value="<?= $jumlah ?>" readonly>
                                <button type="button" data-tambah="<?= $idInput ?>">+</button>
                            </div>
                        </div>
                        <?php } ?>
                    </div>

                    <small class="judul-bagian">CATATAN JAHITAN KHUSUS WORKSHOP</small>
                    <textarea class="isian-lepas" name="catatan_produk" id="inputCatatanProduk" rows="3"><?= e($produk['catatan']) ?></textarea>
                </div>

                <div class="modal-kaki">
                    <button type="button" class="tombol tombol-garis" data-tutup-modal>Batal</button>
                    <button type="button" class="tombol" id="simpanSpesifikasi"><?= $ikonCentang ?> Simpan Spesifikasi</button>
                </div>
            </div>
        </div>

    </form>

<?php } ?>

</div>
</main>
<script src="../assets/js/checkout.js"></script>
</body>
</html>