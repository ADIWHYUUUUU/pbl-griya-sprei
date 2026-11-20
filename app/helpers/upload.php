<?php

require_once __DIR__ . '/../services/SupabaseStorageService.php';

function save_upload(array $file, string $bucket, int $maxBytes, array $allowedMimes): array
{
    return (new SupabaseStorageService())->uploadFile($file, $bucket, $maxBytes, $allowedMimes);
}

function delete_uploaded_file(string $storedPath, string $legacySubdir): void
{
    if (SupabaseStorageService::isStorageReference($storedPath)) {
        (new SupabaseStorageService())->delete($storedPath);
        return;
    }

    $path = dirname(__DIR__, 2)
        . '/public/uploads/'
        . trim($legacySubdir, '/\\')
        . '/'
        . basename(str_replace('\\', '/', $storedPath));
    if (is_file($path) && !unlink($path)) {
        throw new RuntimeException('Unable to remove legacy uploaded file.');
    }
}