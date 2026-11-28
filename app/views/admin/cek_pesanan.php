<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    ($_SESSION['login'] ?? false) !== true ||
    ($_SESSION['role'] ?? '') !== 'ADMIN'
) {
    header('Location: /?page=admin-login');
    exit;
}

$ordersError = false;
$orders = [];
$statusCounts = [
    'MENUNGGU_VERIFIKASI' => 0,
    'MENUNGGU_PEMBAYARAN' => 0,
    'DIPROSES' => 0,
    'DIKIRIM' => 0,
    'SELESAI' => 0,
    'DIBATALKAN' => 0,
];
try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../models/DashboardModel.php';
    $dashboard = new DashboardModel($pdo);
    $orders = $dashboard->getRecentOrders(100);
    $statusCounts = $dashboard->getOrderStatusCounts();
} catch (PDOException $error) {
    error_log('[admin-orders] Failed to load orders: ' . $error->getMessage());
    $ordersError = true;
}
require_once __DIR__ . '/../../helpers/storage.php';
$flash = $_SESSION['admin_order_message'] ?? null;
unset($_SESSION['admin_order_message']);
$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$orderStatuses = [
    'MENUNGGU_VERIFIKASI' => ['Menunggu Verifikasi', 'verification'],
    'MENUNGGU_PEMBAYARAN' => ['Menunggu Pembayaran', 'payment'],
    'DIPROSES' => ['Diproses (Sedang Dijahit)', 'processing'],
    'DIKIRIM' => ['Dikirim', 'shipped'],
    'SELESAI' => ['Selesai', 'complete'],
    'DIBATALKAN' => ['Dibatalkan', 'cancelled'],
];
$transitions = [
    'MENUNGGU_VERIFIKASI' => ['MENUNGGU_PEMBAYARAN', 'DIBATALKAN'],
    'MENUNGGU_PEMBAYARAN' => ['DIPROSES', 'DIBATALKAN'],
    'DIPROSES' => ['DIKIRIM', 'DIBATALKAN'],
    'DIKIRIM' => ['SELESAI'],
    'SELESAI' => [],
    'DIBATALKAN' => [],
];
$directTransitions = [
    'MENUNGGU_VERIFIKASI' => ['DIBATALKAN'],
    'MENUNGGU_PEMBAYARAN' => ['DIBATALKAN'],
    'DIPROSES' => ['DIBATALKAN'],
    'DIKIRIM' => ['SELESAI'],
    'SELESAI' => [],
    'DIBATALKAN' => [],
];
$filters = [
    'semua' => 'Semua',
    'MENUNGGU_VERIFIKASI' => 'Menunggu Verifikasi',
    'MENUNGGU_PEMBAYARAN' => 'Menunggu Pembayaran',
    'DIPROSES' => 'Diproses',
    'DIKIRIM' => 'Dikirim',
    'SELESAI' => 'Selesai',
];
$requestedFilter = $_GET['status'] ?? 'semua';
$activeFilter = is_string($requestedFilter) && array_key_exists($requestedFilter, $filters)
    ? $requestedFilter
    : 'semua';
$requestedSearch = $_GET['cari'] ?? '';
$searchTerm = is_string($requestedSearch) ? trim($requestedSearch) : '';
$visibleOrders = array_values(array_filter($orders, static function (array $order) use ($activeFilter, $searchTerm): bool {
    if ($activeFilter !== 'semua' && ($order['status_pesanan'] ?? '') !== $activeFilter) {
        return false;
    }

    return $searchTerm === '' || stripos(
        (string) ($order['kode'] ?? '') . ' ' . (string) ($order['nama_pelanggan'] ?? ''),
        $searchTerm
    ) !== false;
}));
$visibleOrderIds = array_fill_keys(array_column($visibleOrders, 'id_pesanan'), true);
$statusCounts['semua'] = array_sum($statusCounts);
$oldestWaitingOrder = null;
foreach ($orders as $order) {
    if (($order['status_pesanan'] ?? '') === 'MENUNGGU_VERIFIKASI') {
        $oldestWaitingOrder = $order;
    }
}
$formatOrderDateValue = static function ($value): string {
    try {
        $date = new DateTimeImmutable((string) $value, new DateTimeZone('UTC'));
        $date = $date->setTimezone(new DateTimeZone('Asia/Jakarta'));
        $months = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        return $date->format('d') . ' ' . $months[(int) $date->format('n')] . $date->format(' Y, H:i') . ' WIB';
    } catch (Exception $error) {
        return (string) $value;
    }
};
$formatOrderDate = static fn ($value): string => $escape($formatOrderDateValue($value));
$shippingMethodLabels = [
    'INSTANT_GOJEK' => 'GoJek Instant',
    'EKSPEDISI_JNE' => 'JNE Express',
    'EKSPEDISI_JNT' => 'J&T Express',
];
$instantShippingMethods = ['INSTANT_GOJEK'];
$paymentMethodLabels = [
    'TRANSFER_BCA' => 'Transfer BCA',
    'TRANSFER_BRI' => 'Transfer BRI',
    'TRANSFER_BSI' => 'Transfer BSI',
    'TRANSFER_MANDIRI' => 'Transfer Mandiri',
];
$formatRupiah = static fn ($value): string => 'Rp ' . number_format((float) $value, 0, ',', '.');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Pesanan - Griya Sprei</title>
    <script>document.documentElement.classList.add('orders-js');</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/admin/sidebar.css">
    <link rel="stylesheet" href="/assets/css/admin/dashboard-new.css">
    <link rel="stylesheet" href="/assets/css/admin/cekpesanan.css">
</head>
<body class="admin-dashboard admin-orders admin-orders-list">
    <div class="admin-layout">
        <?php require __DIR__ . '/../layouts/sidebar.php'; ?>

        <main class="dashboard-main orders-main">
            <button type="button" class="burger-btn" id="burgerBtn" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">&#9776;</button>

            <nav class="orders-breadcrumb" aria-label="Breadcrumb">
                <a href="/?page=admin-dashboard">Admin</a>
                <span aria-hidden="true">›</span>
                <strong aria-current="page">Cek Pesanan</strong>
            </nav>

            <header class="dashboard-header orders-header">
                <div>
                    <h1>Cek Pesanan</h1>
                    <p>Kelola dan pantau status pesanan pelanggan.</p>
                </div>
            </header>

            <?php if (is_array($flash) && isset($flash['text'])): ?>
                <div class="verification-flash verification-flash--<?= $escape($flash['type'] ?? 'error') ?>" role="status">
                    <?= $escape($flash['text']) ?>
                </div>
            <?php endif; ?>

            <?php if (!$ordersError && (int) ($statusCounts['MENUNGGU_VERIFIKASI'] ?? 0) > 0): ?>
                <aside class="orders-warning" aria-label="Pesanan menunggu verifikasi">
                    <div>
                        <strong><?= (int) $statusCounts['MENUNGGU_VERIFIKASI'] ?> pesanan menunggu verifikasi</strong>
                        <?php if ($oldestWaitingOrder !== null): ?>
                            <span>Yang terlama di daftar terbaru sejak <?= $escape($formatOrderDate($oldestWaitingOrder['tanggal_pesan'])) ?>.</span>
                        <?php else: ?>
                            <span>Pesanan menunggu verifikasi belum tersedia pada daftar terbaru.</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($oldestWaitingOrder !== null): ?>
                        <a class="orders-warning__action" href="#order-<?= (int) $oldestWaitingOrder['id_pesanan'] ?>" data-oldest-order="<?= (int) $oldestWaitingOrder['id_pesanan'] ?>">
                            Verifikasi yang terlama <span aria-hidden="true">→</span>
                        </a>
                    <?php endif; ?>
                </aside>
            <?php endif; ?>

            <form class="order-search" action="/" method="get" role="search">
                <input type="hidden" name="page" value="admin-orders">
                <input type="hidden" name="status" value="<?= $escape($activeFilter) ?>">
                <label class="sr-only" for="orderSearch">Cari nomor invoice atau nama pelanggan</label>
                <span class="order-search__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><circle cx="10.8" cy="10.8" r="6.8"></circle><path d="m16 16 4.5 4.5"></path></svg>
                </span>
                <input
                    id="orderSearch"
                    type="search"
                    name="cari"
                    value="<?= $escape($searchTerm) ?>"
                    placeholder="Cari invoice / nama..."
                    autocomplete="off"
                >
            </form>

            <section class="orders-content" aria-label="Daftar pesanan" data-orders-error="<?= $ordersError ? 'true' : 'false' ?>">
                <div class="orders-panel">
                    <div class="orders-toolbar">
                    <nav class="order-tabs" aria-label="Filter status pesanan">
                        <?php foreach ($filters as $filter => $label): ?>
                            <?php
                            $isActive = $activeFilter === $filter;
                            $count = $filter === 'semua' ? $statusCounts['semua'] : ($statusCounts[$filter] ?? 0);
                            ?>
                            <a
                                class="order-tab<?= $isActive ? ' is-active' : '' ?>"
                                href="/?page=admin-orders&amp;status=<?= rawurlencode($filter) ?><?= $searchTerm !== '' ? '&amp;cari=' . rawurlencode($searchTerm) : '' ?>"
                                data-order-filter="<?= $escape($filter) ?>"
                                <?= $isActive ? 'aria-current="page"' : '' ?>
                            >
                                <?= $escape($label) ?>
                                <span class="order-tab__count"><?= (int) $count ?></span>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                    </div>
                    <div class="orders-table-scroll">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th scope="col">Invoice &amp; Tanggal</th>
                                <th scope="col">Pelanggan</th>
                                <th scope="col">Spesifikasi Sprei</th>
                                <th scope="col">Tagihan</th>
                                <th scope="col">Status Pesanan</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <?php
                                $statusCode = (string) ($order['status_pesanan'] ?? '');
                                $status = $orderStatuses[$statusCode] ?? [$statusCode, 'other'];
                                $tanggalLabel = $formatOrderDate($order['tanggal_pesan']);
                                $itemsJson = $order['produk_detail'] ?? null;
                                $detailItems = json_decode(
                                    is_string($itemsJson) && $itemsJson !== '' ? $itemsJson : '[]',
                                    true,
                                    512,
                                    JSON_THROW_ON_ERROR
                                );
                                $detailItems = is_array($detailItems) ? array_values($detailItems) : [];
                                $modalItems = [];
                                foreach ($detailItems as $detailItem) {
                                    if (!is_array($detailItem)) {
                                        continue;
                                    }
                                    $imagePath = trim((string) ($detailItem['gambar_produk'] ?? ''));
                                    $modalItems[] = [
                                        'name' => (string) ($detailItem['nama_produk'] ?? ''),
                                        'category' => (string) ($detailItem['kategori_produk'] ?? ''),
                                        'image' => $imagePath !== '' ? product_image_url($imagePath) : '',
                                        'type' => (string) ($detailItem['tipe_ukuran'] ?? ''),
                                        'quantity' => (int) ($detailItem['jumlah'] ?? 0),
                                        'length' => $detailItem['panjang'] ?? null,
                                        'width' => $detailItem['lebar'] ?? null,
                                        'height' => $detailItem['tinggi'] ?? null,
                                        'pillow' => (int) ($detailItem['tambah_sarban'] ?? 0),
                                        'bolster' => (int) ($detailItem['tambah_sargul'] ?? 0),
                                        'note' => (string) ($detailItem['catatan_pesanan'] ?? ''),
                                    ];
                                }
                                $shippingMethod = (string) ($order['metode_pengiriman'] ?? '');
                                $instantCourier = in_array($shippingMethod, $instantShippingMethods, true);
                                $shippingReference = trim((string) ($instantCourier
                                    ? ($order['nomor_telepon_kurir'] ?? '')
                                    : ($order['nomor_resi'] ?? '')));
                                $paymentCode = (string) ($order['status_pembayaran'] ?? '');
                                $paymentTone = match ($paymentCode) {
                                    'MENUNGGU_VERIFIKASI' => 'waiting',
                                    'TERVERIFIKASI' => 'complete',
                                    'DITOLAK' => 'cancelled',
                                    default => 'other',
                                };
                                $paymentLabels = [
                                    'MENUNGGU_VERIFIKASI' => 'Menunggu Verifikasi',
                                    'TERVERIFIKASI' => 'Terverifikasi',
                                    'DITOLAK' => 'Ditolak',
                                ];
                                $hasPayment = $paymentCode !== '';
                                $principal = $order['tagihan_pokok'] === null ? null : (float) $order['tagihan_pokok'];
                                $shippingCost = (float) ($order['ongkir'] ?? 0);
                                $discount = $order['diskon'] === null ? null : (float) $order['diskon'];
                                $hasCompleteBilling = $principal !== null && $shippingCost > 0;
                                $paymentProofName = trim((string) ($order['bukti_nama_asli'] ?? ''));
                                if ($paymentProofName === '') {
                                    $paymentProofName = basename((string) ($order['bukti_transfer'] ?? ''));
                                }
                                $address = trim((string) ($order['alamat_pengiriman'] ?? ''));
                                $addressNote = trim((string) ($order['catatan_alamat'] ?? ''));
                                if ($addressNote !== '') {
                                    $address .= ($address !== '' ? "\n" : '') . $addressNote;
                                }
                                $modalData = [
                                    'invoice' => (string) ($order['kode'] ?? ''),
                                    'date' => $formatOrderDateValue($order['tanggal_pesan'] ?? ''),
                                    'status' => ['label' => $status[0], 'tone' => $status[1]],
                                    'items' => $modalItems,
                                    'customer' => [
                                        'name' => (string) ($order['nama_pelanggan'] ?? ''),
                                        'phone' => (string) ($order['telepon'] ?? ''),
                                        'address' => $address,
                                        'shippingMethod' => $shippingMethodLabels[$shippingMethod] ?? $shippingMethod,
                                        'shippingReferenceLabel' => $instantCourier ? 'Nomor Telepon Kurir' : 'Nomor Resi',
                                        'shippingReference' => $shippingReference,
                                        'shippingNote' => (string) ($order['catatan_pengiriman'] ?? ''),
                                    ],
                                    'billing' => [
                                        'principal' => $principal,
                                        'discount' => $discount,
                                        'shipping' => $shippingCost > 0 ? $shippingCost : null,
                                        'total' => $hasCompleteBilling ? (float) $order['total'] : null,
                                    ],
                                    'payment' => [
                                        'exists' => $hasPayment,
                                        'status' => $paymentLabels[$paymentCode] ?? 'Belum ada pembayaran',
                                        'tone' => $hasPayment ? $paymentTone : 'other',
                                        'method' => $paymentMethodLabels[$order['metode_pembayaran'] ?? ''] ?? (string) ($order['metode_pembayaran'] ?? ''),
                                        'amount' => $order['nominal_transfer'] === null ? null : (float) $order['nominal_transfer'],
                                        'date' => !empty($order['tanggal_upload']) ? $formatOrderDateValue($order['tanggal_upload']) : '',
                                        'sender' => trim((string) ($order['nama_pemilik_pengirim'] ?? '')),
                                        'account' => trim((string) ($order['nomor_rekening_pengirim'] ?? '')),
                                        'proof' => $paymentProofName,
                                        'adminNote' => trim((string) ($order['catatan_admin'] ?? '')),
                                    ],
                                ];
                                $modalJson = json_encode(
                                    $modalData,
                                    JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_THROW_ON_ERROR
                                );
                                $specificationText = trim((string) ($order['daftar_spesifikasi'] ?? ''));
                                $specifications = $specificationText !== ''
                                    ? preg_split('/\n\s*\n/', $specificationText)
                                    : [(string) ($order['daftar_produk'] ?? 'Produk tidak tersedia')];
                                $specifications = array_values(array_filter($specifications, static fn ($value) => trim((string) $value) !== ''));
                                $shownSpecifications = array_slice($specifications, 0, 2);
                                $isCustomSize = stripos($specificationText, 'Custom:') !== false;
                                $sizeType = $isCustomSize ? 'Custom Size' : 'Standar';
                                $sizeTypeClass = $isCustomSize ? 'custom' : 'standard';
                                ?>
                                <tr
                                    id="order-<?= (int) $order['id_pesanan'] ?>"
                                    class="<?= $statusCode === 'MENUNGGU_VERIFIKASI' ? 'order-row--verification' : '' ?>"
                                    tabindex="-1"
                                    data-order-row
                                    data-category="<?= $escape($statusCode) ?>"
                                    data-search="<?= $escape(strtolower((string) ($order['kode'] ?? '') . ' ' . (string) ($order['nama_pelanggan'] ?? ''))) ?>"
                                    <?= isset($visibleOrderIds[$order['id_pesanan']]) ? '' : 'hidden' ?>
                                >
                                    <td>
                                        <strong class="order-invoice"><?= $escape($order['kode']) ?></strong>
                                        <small><?= $escape($tanggalLabel) ?></small>
                                        <span class="order-size-label order-size-label--<?= $sizeTypeClass ?>"><?= $escape($sizeType) ?></span>
                                    </td>
                                    <td>
                                        <strong class="order-customer-name"><?= $escape($order['nama_pelanggan']) ?></strong>
                                        <small class="order-phone">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h3l2 5-2 1.5a15 15 0 0 0 4.5 4.5L16 12l2-2 5 2v3c0 1.1-.9 2-2 2C10 19 5 14 5 5c0-1.1.9-2 2-2Z"></path></svg>
                                            <?= $escape($order['telepon']) ?>
                                        </small>
                                    </td>
                                    <td class="order-products">
                                        <?php foreach ($shownSpecifications as $specification): ?>
                                            <div class="order-product-spec"><?= nl2br($escape(trim((string) $specification))) ?></div>
                                        <?php endforeach; ?>
                                        <?php if (count($specifications) > count($shownSpecifications)): ?>
                                            <small class="order-more-items">+<?= count($specifications) - count($shownSpecifications) ?> item lainnya</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="order-total">
                                        <strong>
                                            <?= $escape($formatRupiah($order['total'])) ?>
                                        </strong>
                                        <?php if ((float) ($order['ongkir'] ?? 0) <= 0): ?><small>Ongkir belum ditetapkan</small><?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="order-status order-status--<?= $escape($status[1]) ?>">
                                            <span class="order-status-dot" aria-hidden="true"></span>
                                            <?= $escape($status[0]) ?>
                                        </span>
                                        <?php if (!empty($directTransitions[$statusCode])): ?>
                                            <form class="status-change-form" method="post" action="/?action=admin-order-status-save">
                                                <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token'] ?? '') ?>">
                                                <input type="hidden" name="id_pesanan" value="<?= (int) $order['id_pesanan'] ?>">
                                                <label class="sr-only" for="status-<?= (int) $order['id_pesanan'] ?>">Ubah status pesanan <?= $escape($order['kode']) ?></label>
                                                <select id="status-<?= (int) $order['id_pesanan'] ?>" name="status_pesanan" required>
                                                    <option value="">Ubah status…</option>
                                                    <?php foreach ($directTransitions[$statusCode] as $targetStatus): ?>
                                                        <option value="<?= $escape($targetStatus) ?>"><?= $escape($orderStatuses[$targetStatus][0]) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" disabled>Simpan</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="order-actions">
                                            <button class="order-action" type="button" data-order-detail-trigger data-order-detail="<?= $escape($modalJson) ?>" aria-haspopup="dialog" aria-label="Lihat detail pesanan <?= $escape($order['kode']) ?>">Detail</button>
                                            <?php if ($statusCode === 'MENUNGGU_VERIFIKASI'): ?>
                                                <a class="order-action order-action--verify" href="/?page=admin-order-verification&amp;id=<?= (int) $order['id_pesanan'] ?>">Verifikasi</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="orders-loading" data-orders-loading aria-hidden="true">
                                <td colspan="6"><span></span><span></span><span></span></td>
                            </tr>
                            <tr class="orders-empty" id="ordersEmpty"<?= $visibleOrders || $ordersError ? ' hidden' : '' ?>>
                                <td colspan="6">
                                    <?= $orders ? 'Tidak ada pesanan yang cocok dengan pencarian.' : 'Tidak ada pesanan.' ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
                <?php if ($ordersError): ?>
                    <div class="orders-state orders-state--error" role="alert">
                        Pesanan tidak dapat dimuat. Silakan muat ulang halaman atau coba lagi nanti.
                    </div>
                <?php endif; ?>
                <p class="orders-list-note" id="ordersResultCount" aria-live="polite"></p>
                <nav class="orders-pagination" aria-label="Navigasi halaman pesanan" hidden>
                    <button type="button" data-page-previous disabled>‹ Sebelumnya</button>
                    <div class="orders-pagination__pages" data-page-numbers></div>
                    <button type="button" data-page-next>Berikutnya ›</button>
                </nav>
            </section>

            <dialog class="order-detail-modal" id="orderDetailModal" aria-labelledby="orderDetailTitle" aria-modal="true" tabindex="-1">
                <div class="order-detail-modal__panel">
                    <header class="order-detail-modal__header">
                        <div class="order-detail-modal__heading">
                            <div class="order-detail-modal__title-line">
                                <h2 id="orderDetailTitle">Detail Pesanan</h2>
                                <span class="order-status" data-modal-order-status hidden></span>
                            </div>
                            <p><span data-modal-invoice></span><span aria-hidden="true"> · </span><span data-modal-date></span></p>
                        </div>
                        <button class="order-detail-modal__icon-close" type="button" data-modal-close aria-label="Tutup detail pesanan">&times;</button>
                    </header>
                    <div class="order-detail-modal__content">
                        <section class="order-detail-card">
                            <h3>Spesifikasi Pesanan</h3>
                            <div class="order-detail-items" data-modal-items></div>
                        </section>
                        <section class="order-detail-card">
                            <h3>Data Pelanggan &amp; Pengiriman</h3>
                            <div class="order-detail-fields order-detail-fields--customer" data-modal-customer></div>
                        </section>
                        <div class="order-detail-financials">
                            <section class="order-detail-card">
                                <h3>Tagihan</h3>
                                <div class="order-detail-billing" data-modal-billing></div>
                                <div class="order-detail-total" data-modal-total></div>
                            </section>
                            <section class="order-detail-card">
                                <h3>Pembayaran</h3>
                                <div data-modal-payment></div>
                            </section>
                        </div>
                    </div>
                    <footer class="order-detail-modal__footer">
                        <button type="button" data-modal-close>Tutup</button>
                    </footer>
                </div>
            </dialog>

            <section class="orders-info-card" aria-labelledby="statusFlowTitle">
                <h2 id="statusFlowTitle">Alur Status yang Diizinkan</h2>
                <div class="status-flow" aria-label="Menunggu Verifikasi menuju Menunggu Pembayaran, Diproses, Dikirim, lalu Selesai">
                    <?php foreach (['MENUNGGU_VERIFIKASI', 'MENUNGGU_PEMBAYARAN', 'DIPROSES', 'DIKIRIM', 'SELESAI'] as $index => $flowStatus): ?>
                        <?php if ($index > 0): ?><span class="status-flow-arrow" aria-hidden="true">→</span><?php endif; ?>
                        <span class="order-status order-status--<?= $escape($orderStatuses[$flowStatus][1]) ?>">
                            <span class="order-status-dot" aria-hidden="true"></span>
                            <?= $escape($orderStatuses[$flowStatus][0]) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
                <div class="status-flow-footer">
                    <p class="status-flow-note">
                        Pesanan dapat dibatalkan saat Menunggu Verifikasi, Menunggu Pembayaran, atau Diproses.
                        Status Selesai dan Dibatalkan bersifat final.
                    </p>
                    <span class="order-status order-status--cancelled"><span class="order-status-dot" aria-hidden="true"></span>Dibatalkan</span>
                </div>
            </section>

            <details class="orders-info-card orders-actions-card">
                <summary>
                    <h2 id="statusActionsTitle">Aksi per Status</h2>
                    <span class="orders-actions-card__toggle" data-open-label="Tampilkan" data-close-label="Sembunyikan">Tampilkan
                        <svg viewBox="0 0 16 16" aria-hidden="true"><path d="m4 6 4 4 4-4"></path></svg>
                    </span>
                </summary>
                <div class="status-actions-scroll">
                    <table class="status-actions-table">
                        <thead>
                            <tr>
                                <th scope="col">Status</th>
                                <th scope="col">Tombol Aksi</th>
                                <th scope="col">Bisa Dipindah ke</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderStatuses as $statusCode => [$label, $tone]): ?>
                                <?php
                                $destinations = array_map(
                                    static fn ($target) => $orderStatuses[$target][0],
                                    $transitions[$statusCode]
                                );
                                $actions = match ($statusCode) {
                                    'MENUNGGU_VERIFIKASI' => 'Detail + Verifikasi',
                                    'MENUNGGU_PEMBAYARAN' => 'Detail + Rincian + Cek Pembayaran',
                                    'DIPROSES' => 'Detail + Rincian + Input Resi',
                                    default => 'Detail + Rincian',
                                };
                                ?>
                                <tr>
                                    <td><span class="order-status order-status--<?= $escape($tone) ?>"><span class="order-status-dot" aria-hidden="true"></span><?= $escape($label) ?></span></td>
                                    <td><?= $escape($actions) ?></td>
                                    <td><?= $destinations === [] ? 'Final, tidak bisa dipindah' : $escape(implode(' · ', $destinations)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="status-actions-note">Perpindahan ke Menunggu Pembayaran, Diproses, dan Dikirim memakai alur Verifikasi, Cek Pembayaran, dan Nomor Resi agar data terkait tercatat dengan benar. Dropdown hanya menawarkan perubahan yang didukung oleh aksi ini.</p>
            </details>
        </main>
    </div>

    <script src="/assets/js/admin/sidebar.js"></script>
    <script src="/assets/js/admin/cek_pesanan.js"></script>
</body>
</html>
