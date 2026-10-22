<?php
session_start();

function e($teks) {
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

function rupiah($angka) {
    return 'Rp ' . number_format((int) $angka, 0, ',', '.');
}

function tanggal($teks) {
    return str_replace(
        ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
        (string) $teks
    );
}

$toko = [
    'nama'   => 'Griya Sprei',
    'alamat' => 'Jl. Kanjuruhan Asri No. 8, Tlogomas, Kec. Lowokwaru, Kota Malang',
    'wa'     => '+62 812-3456-7890',
    'email'  => 'griyasprei@gmail.com',
];

$daftarBank = [
    'bca'     => ['kode' => 'BCA',     'nama' => 'Bank Central Asia (BCA Transfer)', 'ket' => 'Otomatis'],
    'mandiri' => ['kode' => 'Mandiri', 'nama' => 'Bank Mandiri (Livin\')',           'ket' => 'Manual Cek'],
    'bri'     => ['kode' => 'BRI',     'nama' => 'Bank Rakyat Indonesia (BRImo)',    'ket' => 'Manual Cek'],
    'bsi'     => ['kode' => 'BSI',     'nama' => 'Bank Syariah Indonesia',           'ket' => 'Syariah'],
];

// ---------- Cari pesanan yang mau dibayar ----------
$kode = $_POST['kode'] ?? ($_GET['kode'] ?? '');
$indeks = null;

foreach ($_SESSION['pesanan'] ?? [] as $i => $p) {
    if ($p['kode'] === $kode) {
        $indeks = $i;
        break;
    }
}

if ($indeks === null) {
    header('Location: pesananSaya.php');
    exit;
}

$pesanan = $_SESSION['pesanan'][$indeks];
$berhasil = isset($_GET['berhasil']);

if (!$berhasil && $pesanan['status'] !== 'Menunggu Pembayaran') {
    header('Location: pesananSaya.php');
    exit;
}

// ----------Kirim Bukti Pembayaran----------
$pesanError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bank    = $_POST['bank'] ?? '';
    $noRek   = trim($_POST['no_rekening'] ?? '');
    $namaRek = trim($_POST['nama_rekening'] ?? '');
    $file    = $_FILES['bukti'] ?? null;
    $ekstensi = $file ? strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) : '';

    if (!isset($daftarBank[$bank])) {
        $pesanError = 'Pilih bank tujuan transfer.';
    } elseif ($noRek === '' || $namaRek === '') {
        $pesanError = 'Nomor rekening dan nama pemilik rekening pengirim wajib diisi.';
    } elseif (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $pesanError = 'Unggah bukti transfer terlebih dahulu.';
    } elseif (!in_array($ekstensi, ['jpg', 'jpeg', 'png', 'pdf']) || $file['size'] > 2 * 1024 * 1024) {
        $pesanError = 'Bukti transfer harus JPG, PNG, atau PDF dengan ukuran maksimal 2 MB.';
    } else {
        $_SESSION['pesanan'][$indeks]['riwayat_bayar'][] = [
            'waktu'   => date('d M Y'),
            'jumlah'  => $pesanan['tagihan'],
            'file'    => basename($file['name']),
            'status'  => 'Dicek Admin',
            'bank'    => $daftarBank[$bank]['kode'],
            'rekening_pengirim' => $noRek,
            'nama_pengirim'     => $namaRek,
        ];
        $_SESSION['pesanan'][$indeks]['status'] = 'Menunggu Verifikasi';

        header('Location: pembayaran.php?kode=' . urlencode($kode) . '&berhasil=1');
        exit;
    }
}

// ---------- 4. Hitung rincian biaya ----------
$total  = (int) $pesanan['tagihan'];
$ongkir = $pesanan['ongkir'] ?? null;
$diskon = (int) ($pesanan['diskon'] ?? 0);

// Ambil ongkir dari teks rincian, misal "... + Ongkir Ekspedisi Rp 25.000"
if ($ongkir === null) {
    $ongkir = preg_match('/Ongkir[^R]*Rp\s*([\d.]+)/i', $pesanan['rincian'] ?? '', $m) ? (int) str_replace('.', '', $m[1]) : 0;
}
$subtotal = $total - $ongkir + $diskon;

$produk = $pesanan['produk'];
$jumlah = (int) $produk['jumlah'];

// Baris tabel produk
$daftarItem = [[
    'nama'   => $produk['nama'] . ($produk['jenis'] === 'custom' ? ' (Custom)' : ''),
    'spek'   => 'Ukuran: ' . $produk['ukuran']
              . ($produk['tambahan'] !== '-' ? ' · Kelengkapan: ' . $produk['tambahan'] : ''),
    'detail' => ($produk['bahan'] ?? '') . ($produk['catatan'] !== '-' ? ' · ' . $produk['catatan'] : ''),
    'jumlah' => $jumlah . ' set',
    'harga'  => $jumlah > 0 ? intdiv($subtotal, $jumlah) : $subtotal,
    'total'  => $subtotal,
]];

$penerima = $pesanan['penerima'] ?? 'Vico Dwi Wijaya';
$telepon  = $pesanan['telepon'] ?? '+62 812-4491-1980';
$alamat   = $pesanan['alamat'] ?? 'Jl. Ngawi Barat No. 12, RT 03 / RW 07, Kel. Ngawi Barat, Kec. Lowokwaru, Kota Malang, Jawa Timur (Kode Pos 65141)';
$tanggalInvoice = tanggal(explode(',', $pesanan['waktu_verifikasi'] ?? $pesanan['waktu'])[0]);   // tanggal saja, tanpa jam
$bankDipilih = $_POST['bank'] ?? 'bca';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Griya Sprei</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/pembayaran.css">
</head>
<body>

<main class="container">
<div class="isi">

    <form method="post" enctype="multipart/form-data" id="formBayar" class="kartu invoice" novalidate>
        <input type="hidden" name="kode" value="<?= e($pesanan['kode']) ?>">

        <!-- ================= INVOICE ================= -->
        <div class="invoice-kepala">
            <div class="toko">
                <img src="../assets/images/image1.png" alt="Logo Griya Sprei" class="logo-toko">
                <div>
                    <h1><?= e($toko['nama']) ?> <span class="badge-kecil"><?= $produk['jenis'] === 'custom' ? 'Custom' : 'Standar' ?></span></h1>
                    <p><?= e($toko['alamat']) ?></p>
                    <p>WA: <?= e($toko['wa']) ?> | Email: <?= e($toko['email']) ?></p>
                </div>
            </div>
            <div class="nomor-invoice">
                <small>NOMOR TAGIHAN / INVOICE</small>
                <b>#<?= e($pesanan['kode']) ?></b>
                <small>Tanggal: <?= e($tanggalInvoice) ?></small>
            </div>
        </div>

        <!-- ================= DITAGIHKAN KEPADA ================= -->
        <section class="kotak-abu">
            <small class="judul-bagian">DITAGIHKAN &amp; DIKIRIM KEPADA</small>
            <b><?= e($penerima) ?></b>
            <p>Nomor WA: <?= e($telepon) ?></p>
            <p>Alamat: <?= e($alamat) ?></p>
        </section>

        <!-- ================= TABEL PRODUK ================= -->
        <table class="tabel-item">
            <thead>
                <tr>
                    <th>PRODUK/ITEM &amp; SPESIFIKASI DETAIL</th>
                    <th>JUMLAH</th>
                    <th>HARGA SATUAN</th>
                    <th>TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daftarItem as $item) { ?>
                <tr>
                    <td>
                        <b><?= e($item['nama']) ?></b>
                        <small><?= e($item['spek']) ?></small>
                        <?php if (trim($item['detail'], ' ·') !== '') { ?><small><?= e(trim($item['detail'], ' ·')) ?></small><?php } ?>
                    </td>
                    <td><?= e($item['jumlah']) ?></td>
                    <td><?= rupiah($item['harga']) ?></td>
                    <td><b><?= rupiah($item['total']) ?></b></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>

        <div class="dua-kolom">
            <!-- pembayaran -->
            <section class="kotak-abu">
                <small class="judul-bagian">REKONSILIASI RIWAYAT PEMBAYARAN (PAYMENT BREAKDOWN)</small>
                <div class="baris-bayar">
                    <div>
                        <b>Pembayaran Penuh (Lunas)</b>
                        <small>Transfer sekali bayar sesuai total tagihan resmi Admin</small>
                    </div>
                    <div class="kanan">
                        <b><?= rupiah($total) ?></b>
                        <small class="menunggu">Menunggu</small>
                    </div>
                </div>
                <div class="baris-total-kecil">
                    <span>Total Harus Dibayarkan</span>
                    <b><?= rupiah($total) ?></b>
                </div>

                <div class="judul-metode">
                    <small class="judul-bagian">METODE PEMBAYARAN / PILIHAN BANK</small>
                    <span class="badge-kecil hijau">Transfer Bank</span>
                </div>

                <div class="pilihan-bank">
                    <?php foreach ($daftarBank as $kodeBank => $b) { ?>
                    <label class="opsi-bank <?= $kodeBank === $bankDipilih ? 'terpilih' : '' ?>">
                        <input type="radio" name="bank" value="<?= $kodeBank ?>" <?= $kodeBank === $bankDipilih ? 'checked' : '' ?>>
                        <span class="kode-bank"><?= e($b['kode']) ?></span>
                        <span class="nama-bank"><?= e($b['nama']) ?></span>
                        <small><?= e($b['ket']) ?></small>
                    </label>
                    <?php } ?>
                </div>

                <label class="isian">Nomor Rekening Pengirim (Untuk Verifikasi)
                    <input type="text" name="no_rekening" id="inputNoRek" placeholder="Contoh: 123-456789" value="<?= e($_POST['no_rekening'] ?? '') ?>">
                </label>
                <label class="isian">Nama Pemilik Rekening (Sesuai Buku Tabungan / M-Banking)
                    <input type="text" name="nama_rekening" id="inputNamaRek" placeholder="Contoh: <?= e($penerima) ?>" value="<?= e($_POST['nama_rekening'] ?? '') ?>">
                </label>
            </section>

            <!-- ringkasan biaya -->
            <section class="kotak-abu ringkasan">
                <small class="judul-bagian">RINGKASAN BIAYA PESANAN</small>
                <div class="baris-biaya"><span>Subtotal Produk (<?= $produk['jenis'] === 'custom' ? 'Custom' : 'Standar' ?>)</span><span><?= rupiah($subtotal) ?></span></div>
                <div class="baris-biaya"><span>Diskon / Potongan Biaya</span><span><?= $diskon > 0 ? '- ' . rupiah($diskon) : 'Rp 0' ?></span></div>
                <div class="baris-biaya"><span>Ongkos Kirim (<?= e($pesanan['metode'] ?? 'Ekspedisi') ?>)</span><span><?= rupiah($ongkir) ?></span></div>
                <div class="baris-total">
                    <b>TOTAL TAGIHAN</b>
                    <b class="nominal"><?= rupiah($total) ?></b>
                </div>
                <small>Total sudah ditetapkan Admin dan termasuk ongkos kirim.</small>
            </section>
        </div>

        <!-- ================= UNGGAH BUKTI ================= -->
        <label class="area-unggah" id="areaUnggah">
            <input type="file" name="bukti" id="inputBukti" accept=".jpg,.jpeg,.png,.pdf">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M7 18a4.5 4.5 0 0 1-.5-8.97A6 6 0 0 1 18 9a4 4 0 0 1 0 8"/><path d="M12 12v9M8.5 15.5 12 12l3.5 3.5"/></svg>
            <b id="teksUnggah">Tarik &amp; lepas atau klik untuk unggah bukti transfer</b>
            <small>Format: JPG, PNG, PDF (Maks. 2MB)</small>
        </label>

        <?php if ($pesanError !== '') { ?>
            <p class="pesan-error" id="pesanError"><?= e($pesanError) ?></p>
        <?php } else { ?>
            <p class="pesan-error" id="pesanError" hidden></p>
        <?php } ?>

        <button type="button" class="tombol tombol-abu tombol-penuh" id="tombolPilihBukti">Upload Bukti Pembayaran</button>
        <button type="submit" class="tombol tombol-penuh" id="tombolKirim">Kirim Bukti Pembayaran</button>
    </form>

</div>
</main>


<?php if ($berhasil) { ?>
<!-- =====================================================
     BUKTI PEMBAYARAN BERHASIL DIKIRIM
     ===================================================== -->
<div class="modal-latar tampil" id="modalBerhasil">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="judulBerhasil">
        <div class="ikon-berhasil">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
        </div>
        <h2 id="judulBerhasil">Bukti Pembayaran Berhasil Dikirim!</h2>
        <p>Bukti transfer Anda telah kami terima dan akan segera diverifikasi. Pesanan akan diproses
           setelah pembayaran dikonfirmasi oleh Admin.</p>

        <div class="kotak-ringkas">
            <div><span>No. Invoice</span><b>#<?= e($pesanan['kode']) ?></b></div>
            <div><span>Nominal Transfer</span><b><?= rupiah($pesanan['tagihan']) ?></b></div>
        </div>

        <div class="kotak-ringkas">
            <div><span>Status Verifikasi</span><b class="status-tunggu">● Menunggu Verifikasi Admin</b></div>
            <small>Verifikasi dilakukan manual oleh Admin. Status pembayaran akan diperbarui maksimal 1×24 jam.</small>
        </div>

        <a href="pesananSaya.php" class="tombol tombol-penuh">Kembali ke Pesanan Saya</a>
    </div>
</div>
<?php } ?>

<script src="../assets/js/pembayaran.js"></script>
</body>
</html>