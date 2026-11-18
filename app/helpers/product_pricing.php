<?php

function product_pricing_options(string $category): array
{
    $pricesByFabric = match (strtoupper(str_replace('_', '', $category))) {
        'SPREI' => [
            'MICROTEX' => [105000, 110000, 115000, 120000, 150000, 155000, 165000],
            'MICROTENCEL' => [130000, 140000, 150000, 160000, 200000, 205000, 210000],
            'KATUN_LOKAL' => [150000, 160000, 175000, 190000, 235000, 245000, 250000],
            'TENCEL' => [324000, 336000, 370000, 406000, 518000, 542000, 566000],
            'KATUN_JEPANG' => [220000, 230000, 260000, 280000, 355000, 370000, 385000],
        ],
        'BEDCOVER' => [
            'MICROTEX' => [225000, 225000, 225000, 225000, 370000, 300000, 300000],
            'MICROTENCEL' => [250000, 250000, 250000, 250000, 300000, 335000, 335000],
            'KATUN_LOKAL' => [295000, 295000, 295000, 295000, 350000, 350000, 395000],
            'TENCEL' => [532000, 532000, 532000, 532000, 485000, 667000, 752000],
            'KATUN_JEPANG' => [395000, 395000, 395000, 395000, 485000, 485000, 545000],
        ],
        default => [],
    };

    $widths = [90, 100, 120, 140, 160, 180, 200];
    $labels = [
        'MICROTEX' => 'Microtex / Microsilk / Embos',
        'MICROTENCEL' => 'Premium Microtencel',
        'KATUN_LOKAL' => 'Katun lokal / 3D',
        'TENCEL' => 'Tencel',
        'KATUN_JEPANG' => 'Katun Jepang',
    ];

    $options = [];
    foreach ($pricesByFabric as $fabric => $prices) {
        $options[$fabric] = [
            'label' => $labels[$fabric],
            'height' => match ($fabric) {
                'MICROTEX' => 20,
                'MICROTENCEL' => 30,
                default => 25,
            },
            'prices' => array_combine($widths, $prices),
        ];
    }

    return $options;
}

function product_price_for(string $category, string $fabric, int $width): ?int
{
    $option = product_pricing_options($category)[$fabric] ?? null;
    return $option['prices'][$width] ?? null;
}

function product_minimum_price(string $category): ?int
{
    $prices = [];
    foreach (product_pricing_options($category) as $option) {
        array_push($prices, ...array_values($option['prices']));
    }

    return $prices === [] ? null : min($prices);
}

function product_fabric_label(?string $fabric): string
{
    $labels = [
        'TENCEL' => 'Tencel',
        'KATUN_JEPANG' => 'Katun Jepang',
        'KATUN_LOKAL' => 'Katun lokal / 3D',
        'MICROTENCEL' => 'Premium Microtencel',
        'MICROTEX' => 'Microtex / Microsilk / Embos',
    ];

    return $labels[$fabric ?? ''] ?? 'Belum diatur';
}
