<?php

/**
 * Notifikasi pembaruan pesanan untuk customer.
 *
 * Sebuah pesanan dianggap "ada update baru" bila kondisi yang diubah Admin
 * (status pesanan, jumlah pembayaran yang sudah diputuskan Admin, nomor resi)
 * berbeda dari kondisi terakhir yang sudah dilihat customer di halaman Pesanan Saya.
 * Kondisi yang sudah dilihat disimpan di storage/pesanan_dilihat.json, jadi tidak
 * perlu mengubah tabel database.
 */

const ORDER_UPDATE_SEEN_FILE = __DIR__ . '/../../storage/pesanan_dilihat.json';
// Tanda pesanan yang baru dibuat customer dan belum disentuh Admin.
const ORDER_UPDATE_INITIAL_SIGNATURE = 'MENUNGGU_VERIFIKASI|0|';

function orderUpdatePdo(): ?PDO
{
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
        return $GLOBALS['pdo'];
    }
    require_once __DIR__ . '/../config/database.php';
    if (isset($pdo) && $pdo instanceof PDO) {
        $GLOBALS['pdo'] = $pdo;
        return $pdo;
    }
    return null;
}

/** Daftar pesanan customer beserta tanda kondisinya saat ini. */
function orderUpdateCurrentOrders(int $customerId): array
{
    static $cache = [];
    if (isset($cache[$customerId])) {
        return $cache[$customerId];
    }

    $pdo = orderUpdatePdo();
    if ($pdo === null) {
        return [];
    }

    $statement = $pdo->prepare(
        "SELECT
            p.id_pesanan,
            COALESCE(p.no_invoice, 'ORD-' || p.id_pesanan::text) AS kode,
            p.status_pesanan::text AS status_pesanan,
            COALESCE(p.nomor_resi, '') AS nomor_resi,
            (
                SELECT COUNT(*)
                FROM pembayaran b
                WHERE b.id_pesanan = p.id_pesanan
                  AND b.status_pembayaran::text <> 'MENUNGGU_VERIFIKASI'
            ) AS pembayaran_diputuskan,
            (
                SELECT b.status_pembayaran::text
                FROM pembayaran b
                WHERE b.id_pesanan = p.id_pesanan
                ORDER BY b.tanggal_upload DESC, b.id_pembayaran DESC
                LIMIT 1
            ) AS status_pembayaran
         FROM pesanan p
         WHERE p.id_user = :customer_id
         ORDER BY p.tanggal_pesan DESC, p.id_pesanan DESC"
    );
    $statement->execute([':customer_id' => $customerId]);

    $orders = [];
    foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $orders[(string) $row['id_pesanan']] = [
            'id_pesanan' => (int) $row['id_pesanan'],
            'kode' => (string) $row['kode'],
            'status_pesanan' => (string) $row['status_pesanan'],
            'status_pembayaran' => (string) ($row['status_pembayaran'] ?? ''),
            'nomor_resi' => (string) $row['nomor_resi'],
            'signature' => $row['status_pesanan'] . '|' . (int) $row['pembayaran_diputuskan'] . '|' . $row['nomor_resi'],
        ];
    }

    return $cache[$customerId] = $orders;
}

function orderUpdateReadSeen(): array
{
    if (!is_file(ORDER_UPDATE_SEEN_FILE)) {
        return [];
    }
    $data = json_decode((string) file_get_contents(ORDER_UPDATE_SEEN_FILE), true);
    return is_array($data) ? $data : [];
}

function orderUpdateSaveSeen(int $customerId, array $orders): void
{
    $handle = fopen(ORDER_UPDATE_SEEN_FILE, 'c+');
    if ($handle === false) {
        return;
    }
    try {
        flock($handle, LOCK_EX);
        $data = json_decode((string) stream_get_contents($handle), true);
        $data = is_array($data) ? $data : [];
        $data[(string) $customerId] = array_map(
            static fn(array $order): string => $order['signature'],
            $orders
        );
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, (string) json_encode($data, JSON_PRETTY_PRINT));
        fflush($handle);
        flock($handle, LOCK_UN);
    } finally {
        fclose($handle);
    }
}

/** Pesanan yang punya update baru dari Admin dan belum dilihat customer. */
function orderUpdatesUnseen(int $customerId): array
{
    try {
        $orders = orderUpdateCurrentOrders($customerId);
        $seenAll = orderUpdateReadSeen();
        $key = (string) $customerId;

        // Customer yang belum pernah tercatat: anggap semua pesanan lamanya sudah dilihat.
        if (!isset($seenAll[$key]) || !is_array($seenAll[$key])) {
            orderUpdateSaveSeen($customerId, $orders);
            return [];
        }

        $seen = $seenAll[$key];
        return array_values(array_filter($orders, static function (array $order) use ($seen): bool {
            $last = $seen[(string) $order['id_pesanan']] ?? ORDER_UPDATE_INITIAL_SIGNATURE;
            return $order['signature'] !== $last;
        }));
    } catch (Throwable $error) {
        error_log('Gagal memeriksa update pesanan: ' . $error->getMessage());
        return [];
    }
}

/** Dipanggil saat customer membuka Pesanan Saya: semua update dianggap sudah dibaca. */
function orderUpdatesMarkSeen(int $customerId): void
{
    try {
        orderUpdateSaveSeen($customerId, orderUpdateCurrentOrders($customerId));
    } catch (Throwable $error) {
        error_log('Gagal menyimpan status pesanan dilihat: ' . $error->getMessage());
    }
}

/**
 * Isi popup untuk satu pesanan yang di-update Admin.
 * Mengembalikan: label status, warna (ok|warn|danger|info), pesan, dan tombol aksi (boleh kosong).
 */
function orderUpdateInfo(array $order): array
{
    $paymentUrl = '/?page=customer-payment&id=' . $order['id_pesanan'];
    $ordersUrl = '/?page=customer-orders';

    if ($order['status_pesanan'] === 'MENUNGGU_PEMBAYARAN' && $order['status_pembayaran'] === 'DITOLAK') {
        return [
            'label' => 'Bukti Transfer Ditolak',
            'tone' => 'danger',
            'message' => 'Bukti transfer Anda ditolak Admin. Cek catatan Admin lalu unggah ulang bukti pembayaran.',
            'action_label' => 'Unggah Ulang',
            'action_url' => $paymentUrl,
        ];
    }

    return match ($order['status_pesanan']) {
        'MENUNGGU_PEMBAYARAN' => [
            'label' => 'Pesanan Diverifikasi',
            'tone' => 'info',
            'message' => 'Pesanan sudah diverifikasi Admin. Tagihan resmi siap dibayar.',
            'action_label' => 'Bayar Sekarang',
            'action_url' => $paymentUrl,
        ],
        'DIPROSES' => [
            'label' => 'Pembayaran Terverifikasi',
            'tone' => 'ok',
            'message' => 'Pembayaran sudah terverifikasi. Pesanan sedang disiapkan dan siap dikirim.',
            'action_label' => '',
            'action_url' => $ordersUrl,
        ],
        'DIKIRIM' => [
            'label' => 'Pesanan Dikirim',
            'tone' => 'ok',
            'message' => $order['nomor_resi'] !== ''
                ? 'Pesanan sudah dikirim. Nomor resi: ' . $order['nomor_resi'] . '.'
                : 'Pesanan sudah dikirim.',
            'action_label' => '',
            'action_url' => $ordersUrl,
        ],
        'SELESAI' => [
            'label' => 'Pesanan Selesai',
            'tone' => 'ok',
            'message' => 'Pesanan sudah selesai. Terima kasih telah berbelanja di Griya Sprei.',
            'action_label' => '',
            'action_url' => $ordersUrl,
        ],
        'DIBATALKAN' => [
            'label' => 'Pesanan Dibatalkan',
            'tone' => 'danger',
            'message' => 'Pesanan dibatalkan oleh Admin.',
            'action_label' => '',
            'action_url' => $ordersUrl,
        ],
        default => [
            'label' => 'Ada Pembaruan',
            'tone' => 'info',
            'message' => 'Ada pembaruan pada pesanan Anda.',
            'action_label' => '',
            'action_url' => $ordersUrl,
        ],
    };
}
