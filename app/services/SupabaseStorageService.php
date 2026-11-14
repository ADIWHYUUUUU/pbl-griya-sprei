<?php

final class SupabaseStorageService
{
    private const REFERENCE_PREFIX = 'supabase://';

    private $url;
    private $secretKey;

    public function __construct()
    {
        $this->url = rtrim((string) (getenv('SUPABASE_URL') ?: ''), '/');
        $this->secretKey = (string) (getenv('SUPABASE_SECRET_KEY') ?: '');
    }

    public static function isStorageReference($value): bool
    {
        return is_string($value) && str_starts_with($value, self::REFERENCE_PREFIX);
    }

    public static function productBucket(): string
    {
        return (string) (getenv('SUPABASE_PRODUCT_BUCKET') ?: 'produk');
    }

    public static function paymentBucket(): string
    {
        return (string) (getenv('SUPABASE_PAYMENT_BUCKET') ?: 'bukti-pembayaran');
    }

    public function uploadFile(array $file, string $bucket, int $maxBytes, array $allowedMimes): array
    {
        if (!isset($file['error'], $file['tmp_name'], $file['name'], $file['size'])) {
            throw new InvalidArgumentException('Data upload tidak lengkap.');
        }
        if (preg_match('/^[a-zA-Z0-9._-]+$/D', $bucket) !== 1) {
            throw new InvalidArgumentException('Invalid Supabase Storage bucket name.');
        }
        if ((int) $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('File upload tidak valid.');
        }
        if ((int) $file['size'] <= 0 || (int) $file['size'] > $maxBytes) {
            throw new InvalidArgumentException('Ukuran file melebihi batas yang diizinkan.');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!is_string($mime) || !isset($allowedMimes[$mime])) {
            throw new InvalidArgumentException('Format file tidak didukung.');
        }

        $contents = file_get_contents($file['tmp_name']);
        if ($contents === false) {
            throw new RuntimeException('File upload tidak dapat dibaca.');
        }

        $objectPath = bin2hex(random_bytes(16)) . '.' . strtolower((string) $allowedMimes[$mime]);
        $this->request(
            'POST',
            '/storage/v1/object/' . $this->encodePath($bucket) . '/' . $this->encodePath($objectPath),
            $contents,
            [
                'Content-Type: ' . $mime,
                'Cache-Control: max-age=3600',
                'x-upsert: false',
            ]
        );

        return [
            'path' => self::REFERENCE_PREFIX . $bucket . '/' . $objectPath,
            'original_name' => basename((string) $file['name']),
            'size' => (int) $file['size'],
            'mime' => $mime,
        ];
    }

    public function publicUrl(string $reference): string
    {
        [$bucket, $objectPath] = $this->parseReference($reference);
        return $this->baseUrl()
            . '/storage/v1/object/public/'
            . $this->encodePath($bucket)
            . '/'
            . $this->encodePath($objectPath);
    }

    public function signedUrl(string $reference, int $expiresIn = 300): string
    {
        [$bucket, $objectPath] = $this->parseReference($reference);
        $response = $this->request(
            'POST',
            '/storage/v1/object/sign/' . $this->encodePath($bucket) . '/' . $this->encodePath($objectPath),
            json_encode(['expiresIn' => max(60, min(3600, $expiresIn))]),
            ['Content-Type: application/json']
        );
        $data = json_decode($response, true);
        if (!is_array($data) || !is_string($data['signedURL'] ?? null) || $data['signedURL'] === '') {
            throw new RuntimeException('Supabase Storage returned an invalid signed URL.');
        }

        $signedPath = $data['signedURL'];
        if (preg_match('#^https?://#i', $signedPath) === 1) {
            return $signedPath;
        }
        if (str_starts_with($signedPath, '/storage/v1/')) {
            return $this->baseUrl() . $signedPath;
        }
        return $this->baseUrl() . '/storage/v1/' . ltrim($signedPath, '/');
    }

    public function delete(string $reference): void
    {
        [$bucket, $objectPath] = $this->parseReference($reference);
        $body = json_encode(['prefixes' => [$objectPath]]);
        if ($body === false) {
            throw new RuntimeException('Failed to encode Supabase Storage delete request.');
        }
        $this->request(
            'DELETE',
            '/storage/v1/object/' . $this->encodePath($bucket),
            $body,
            ['Content-Type: application/json']
        );
    }

    private function request(string $method, string $path, string $body, array $headers): string
    {
        $url = $this->baseUrl();
        if ($this->secretKey === '') {
            throw new RuntimeException('SUPABASE_SECRET_KEY is not configured.');
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL extension is unavailable.');
        }

        $curl = curl_init($url . $path);
        if ($curl === false) {
            throw new RuntimeException('Unable to initialize Supabase Storage request.');
        }
        $options = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => array_merge([
                'apikey: ' . $this->secretKey,
                'Authorization: Bearer ' . $this->secretKey,
                'Accept: application/json',
            ], $headers),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => false,
        ];
        if (!curl_setopt_array($curl, $options)) {
            curl_close($curl);
            throw new RuntimeException('Unable to configure Supabase Storage request.');
        }

        $response = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);
        if ($response === false) {
            throw new RuntimeException('Supabase Storage request failed: ' . $curlError);
        }
        if ($status < 200 || $status >= 300) {
            $errorData = json_decode($response, true);
            $detailValue = is_array($errorData)
                ? ($errorData['message'] ?? $errorData['error'] ?? '')
                : '';
            $detail = is_string($detailValue) ? $detailValue : '';
            $detail = trim((string) preg_replace('/[\r\n\t]+/', ' ', $detail));
            error_log(
                '[supabase-storage] HTTP ' . $status
                . ($detail !== '' ? ': ' . substr($detail, 0, 300) : '')
            );
            throw new RuntimeException(
                'Supabase Storage HTTP ' . $status
                . ($detail !== '' ? ': ' . substr($detail, 0, 300) : '.')
            );
        }
        return $response;
    }

    private function baseUrl(): string
    {
        if ($this->url === '' || filter_var($this->url, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException('SUPABASE_URL is missing or invalid.');
        }
        $scheme = parse_url($this->url, PHP_URL_SCHEME);
        if ($scheme !== 'https') {
            throw new RuntimeException('SUPABASE_URL must use HTTPS.');
        }
        return $this->url;
    }

    private function parseReference(string $reference): array
    {
        if (!self::isStorageReference($reference)) {
            throw new InvalidArgumentException('Invalid Supabase Storage reference.');
        }
        $value = substr($reference, strlen(self::REFERENCE_PREFIX));
        $parts = explode('/', $value, 2);
        if (
            count($parts) !== 2
            || preg_match('/^[a-zA-Z0-9._-]+$/D', $parts[0]) !== 1
            || $parts[1] === ''
            || str_contains($parts[1], '..')
        ) {
            throw new InvalidArgumentException('Invalid Supabase Storage reference.');
        }
        return [$parts[0], $parts[1]];
    }

    private function encodePath(string $path): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $path)));
    }
}
