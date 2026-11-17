<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../app/config/database.php';

try {
    $pdo->beginTransaction();

    $statement = $pdo->query(
        "WITH expired_orders AS (
            SELECT p.id_pesanan
            FROM public.pesanan AS p
            WHERE p.status_pesanan::text IN ('MENUNGGU_VERIFIKASI', 'MENUNGGU_PEMBAYARAN')
              AND p.tanggal_pesan < CURRENT_TIMESTAMP - INTERVAL '48 hours'
              AND NOT EXISTS (
                  SELECT 1
                  FROM public.pembayaran AS b
                  WHERE b.id_pesanan = p.id_pesanan
                    AND b.status_pembayaran::text IN ('MENUNGGU_VERIFIKASI', 'TERVERIFIKASI')
              )
            ORDER BY p.tanggal_pesan, p.id_pesanan
            FOR UPDATE OF p SKIP LOCKED
        )
        UPDATE public.pesanan AS p
        SET status_pesanan = CAST('DIBATALKAN' AS status_pesanan_enum)
        FROM expired_orders AS expired
        WHERE p.id_pesanan = expired.id_pesanan
        RETURNING p.id_pesanan"
    );
    $cancelledCount = count($statement->fetchAll(PDO::FETCH_COLUMN));

    $pdo->commit();
    printf(
        "[%s] Auto-cancelled %d expired order(s).%s",
        date('Y-m-d H:i:s'),
        $cancelledCount,
        PHP_EOL
    );
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('[auto-cancel-orders] ' . $error->getMessage());
    fwrite(STDERR, "Auto-cancel job failed. Check the PHP error log." . PHP_EOL);
    exit(1);
}
