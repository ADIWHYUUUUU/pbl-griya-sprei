SELECT setval(
    pg_get_serial_sequence('pembayaran', 'id_pembayaran')::regclass,
    COALESCE(MAX(id_pembayaran), 1),
    COUNT(*) > 0
)
FROM pembayaran;
