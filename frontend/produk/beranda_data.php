<?php
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
$data = load_beranda();
$data['hero_foto_url'] = $data['hero_foto'] && foto_url($data['hero_foto'])
    ? '../produk/' . foto_url($data['hero_foto'])
    : null;
foreach ($data['kategori'] as &$category) {
    $category['foto_url'] = $category['foto'] && foto_url($category['foto'])
        ? '../produk/' . foto_url($category['foto'])
        : null;
}
unset($category);
echo json_encode($data, JSON_UNESCAPED_UNICODE);
