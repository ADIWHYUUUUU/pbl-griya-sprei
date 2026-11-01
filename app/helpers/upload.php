<?php

function save_upload(array $file, string $subdir, int $maxBytes, array $allowedMimes): array
{
    if (!isset($file['error'], $file['tmp_name'], $file['name'], $file['size'])) {
        throw new InvalidArgumentException('Data upload tidak lengkap.');
    }
    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload file gagal.');
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('File upload tidak valid.');
    }
    if ((int) $file['size'] <= 0 || (int) $file['size'] > $maxBytes) {
        throw new RuntimeException('Ukuran file melebihi batas yang diizinkan.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!is_string($mime) || !isset($allowedMimes[$mime])) {
        throw new RuntimeException('Format file tidak didukung.');
    }

    $extension = strtolower((string) $allowedMimes[$mime]);
    $directory = dirname(__DIR__, 2) . '/public/uploads/' . trim($subdir, '/\\');
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
        throw new RuntimeException('Folder upload gagal dibuat.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    $destination = $directory . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('File gagal disimpan.');
    }

    return [
        'path' => 'uploads/' . trim($subdir, '/\\') . '/' . $filename,
        'original_name' => basename((string) $file['name']),
        'size' => (int) $file['size'],
        'mime' => $mime,
    ];
}