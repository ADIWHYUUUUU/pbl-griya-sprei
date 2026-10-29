<?php

function simpan_bukti_pembayaran(?array $file): array
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['error' => 'Unggah bukti transfer terlebih dahulu.'];
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['error' => 'Upload bukti pembayaran gagal. Coba pilih file kembali.'];
    }

    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        return ['error' => 'Ukuran bukti pembayaran maksimal 2 MB.'];
    }

    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
    ];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($extensions[$mime])) {
        return ['error' => 'Bukti transfer harus berupa JPG, PNG, atau PDF.'];
    }

    $directory = __DIR__ . '/uploads/payments/';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        return ['error' => 'Folder penyimpanan bukti pembayaran tidak dapat dibuat.'];
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . $filename)) {
        return ['error' => 'Bukti pembayaran tidak dapat disimpan di server.'];
    }

    return [
        'file' => basename($file['name']),
        'path' => 'uploads/payments/' . $filename,
        'mime' => $mime,
        'size' => (int)$file['size'],
    ];
}