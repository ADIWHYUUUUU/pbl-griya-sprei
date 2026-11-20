<?php

require_once __DIR__ . '/../services/SupabaseStorageService.php';

function product_image_url($storedPath): string
{
    $storedPath = trim((string) $storedPath);
    if ($storedPath === '') {
        return '';
    }
    if (SupabaseStorageService::isStorageReference($storedPath)) {
        return (new SupabaseStorageService())->publicUrl($storedPath);
    }

    return '/uploads/produk/' . rawurlencode(basename(str_replace('\\', '/', $storedPath)));
}

function payment_proof_url($storedPath): string
{
    $storedPath = trim((string) $storedPath);
    if ($storedPath === '') {
        return '';
    }
    if (SupabaseStorageService::isStorageReference($storedPath)) {
        return (new SupabaseStorageService())->signedUrl($storedPath);
    }

    return '/uploads/bukti-transfer/' . rawurlencode(basename(str_replace('\\', '/', $storedPath)));
}
