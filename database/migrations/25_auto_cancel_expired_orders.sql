-- Auto-cancel pesanan kedaluwarsa menggunakan Supabase PostgreSQL.
-- Timestamp pada tabel pesanan/pembayaran disimpan sebagai UTC tanpa time zone.

CREATE INDEX IF NOT EXISTS idx_pesanan_auto_cancel_status_date
    ON public.pesanan (tanggal_pesan, id_pesanan)
    WHERE status_pesanan IN (
        'MENUNGGU_VERIFIKASI'::public.status_pesanan_enum,
        'MENUNGGU_PEMBAYARAN'::public.status_pesanan_enum
    );

-- Membantu pengecekan pembayaran aktif dan pencarian pembayaran terakhir.
CREATE INDEX IF NOT EXISTS idx_pembayaran_active_order
    ON public.pembayaran (id_pesanan)
    WHERE status_pembayaran IN (
        'MENUNGGU_VERIFIKASI'::public.status_pembayaran_enum,
        'TERVERIFIKASI'::public.status_pembayaran_enum
    );

CREATE INDEX IF NOT EXISTS idx_pembayaran_latest_by_order
    ON public.pembayaran (id_pesanan, tanggal_upload DESC, id_pembayaran DESC)
    INCLUDE (status_pembayaran, tanggal_verifikasi);

CREATE OR REPLACE FUNCTION public.auto_cancel_expired_orders()
RETURNS integer
LANGUAGE plpgsql
VOLATILE
PARALLEL UNSAFE
SECURITY INVOKER
SET search_path = pg_catalog, public
AS $auto_cancel$
DECLARE
    v_cancelled_count integer := 0;
BEGIN
    WITH expired_orders AS MATERIALIZED (
        SELECT p.id_pesanan
        FROM public.pesanan AS p
        WHERE p.status_pesanan IN (
            'MENUNGGU_VERIFIKASI'::public.status_pesanan_enum,
            'MENUNGGU_PEMBAYARAN'::public.status_pesanan_enum
        )
          AND CASE
                WHEN p.status_pesanan = 'MENUNGGU_PEMBAYARAN'::public.status_pesanan_enum
                THEN COALESCE(
                    (
                        SELECT CASE
                            WHEN b.status_pembayaran = 'DITOLAK'::public.status_pembayaran_enum
                            THEN b.tanggal_verifikasi
                        END
                        FROM public.pembayaran AS b
                        WHERE b.id_pesanan = p.id_pesanan
                        ORDER BY b.tanggal_upload DESC, b.id_pembayaran DESC
                        LIMIT 1
                    ),
                    p.tanggal_verifikasi_admin,
                    p.tanggal_pesan
                ) + INTERVAL '48 hours'
                ELSE p.tanggal_pesan + INTERVAL '48 hours'
              END <= (clock_timestamp() AT TIME ZONE 'UTC')
          AND NOT EXISTS (
                SELECT 1
                FROM public.pembayaran AS b
                WHERE b.id_pesanan = p.id_pesanan
                  AND b.status_pembayaran IN (
                      'MENUNGGU_VERIFIKASI'::public.status_pembayaran_enum,
                      'TERVERIFIKASI'::public.status_pembayaran_enum
                  )
          )
        ORDER BY p.tanggal_pesan, p.id_pesanan
        FOR UPDATE OF p SKIP LOCKED
    ), cancelled_orders AS (
        UPDATE public.pesanan AS p
        SET status_pesanan = 'DIBATALKAN'::public.status_pesanan_enum
        FROM expired_orders AS expired
        WHERE p.id_pesanan = expired.id_pesanan
          AND p.status_pesanan IN (
              'MENUNGGU_VERIFIKASI'::public.status_pesanan_enum,
              'MENUNGGU_PEMBAYARAN'::public.status_pesanan_enum
          )
        RETURNING p.id_pesanan
    )
    SELECT COUNT(*)::integer
    INTO v_cancelled_count
    FROM cancelled_orders;

    RAISE NOTICE 'Auto-cancel expired orders finished: % order(s) cancelled.', v_cancelled_count;
    RETURN v_cancelled_count;
END;
$auto_cancel$;

-- Jangan izinkan role API publik menjalankan fungsi pembatalan ini lewat RPC.
REVOKE ALL ON FUNCTION public.auto_cancel_expired_orders() FROM PUBLIC;
REVOKE ALL ON FUNCTION public.auto_cancel_expired_orders() FROM anon, authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.auto_cancel_expired_orders() TO postgres;
