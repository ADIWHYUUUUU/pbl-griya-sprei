<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../app/config/database.php';

try {
    $statement = $pdo->query('SELECT public.auto_cancel_expired_orders()');
    $cancelledCount = (int) $statement->fetchColumn();

    $utc = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $wib = $utc->setTimezone(new DateTimeZone('Asia/Jakarta'));

    printf(
        "[%s WIB | %s UTC] Auto-cancelled %d expired order(s).%s",
        $wib->format('Y-m-d H:i:s'),
        $utc->format('Y-m-d H:i:s'),
        $cancelledCount,
        PHP_EOL
    );
} catch (Throwable $error) {
    error_log('[auto-cancel-orders] ' . $error->getMessage());
    fwrite(STDERR, "Auto-cancel job failed. Check the PHP error log." . PHP_EOL);
    exit(1);
}
