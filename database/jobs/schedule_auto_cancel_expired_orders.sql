-- Jalankan setelah pg_cron diaktifkan pada Supabase Dashboard.
-- Supabase mendokumentasikan grant ini untuk role postgres setelah pg_cron aktif.
GRANT USAGE ON SCHEMA cron TO postgres;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA cron TO postgres;

-- Nama job yang sama akan mengganti schedule/command job sebelumnya.
SELECT cron.schedule(
    'auto-cancel-expired-orders',
    '*/5 * * * *',
    'SELECT public.auto_cancel_expired_orders();'
);

