<?php

function tanggal($value): string
{
    $timestamp = new DateTimeImmutable((string) $value, new DateTimeZone('UTC'));
    return str_replace(
        ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
        $timestamp->setTimezone(new DateTimeZone('Asia/Jakarta'))->format('d M Y, H:i')
    );
}

function sisaWaktuPembayaran(int $seconds): string
{
    $minutes = (int) ceil($seconds / 60);
    $days = intdiv($minutes, 1440);
    $hours = intdiv($minutes % 1440, 60);

    if ($days > 0) {
        return $days . ' hari' . ($hours > 0 ? ' ' . $hours . ' jam' : '');
    }

    return $hours > 0
        ? $hours . ' jam' . ($minutes % 60 > 0 ? ' ' . ($minutes % 60) . ' menit' : '')
        : $minutes . ' menit';
}
