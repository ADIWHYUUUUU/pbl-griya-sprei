<?php

// Queries using this expression alias the pesanan row as p.
const PAYMENT_DEADLINE_SQL = "COALESCE(
    (SELECT CASE WHEN b.status_pembayaran::text = 'DITOLAK' THEN b.tanggal_verifikasi END
     FROM pembayaran AS b
     WHERE b.id_pesanan = p.id_pesanan
     ORDER BY b.tanggal_upload DESC, b.id_pembayaran DESC
     LIMIT 1),
    p.tanggal_verifikasi_admin,
    p.tanggal_pesan
) + INTERVAL '48 hours'";
