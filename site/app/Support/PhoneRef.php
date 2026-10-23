<?php

namespace App\Support;

/* REMARK
 * Tujuan       : Data fiktif netral untuk layar contoh "Phone shell" (halaman katalog p7-phone): menu, modul, barang, laporan. Tanpa data atau nama proyek nyata.
 * Sumber hulu  : Tidak ada (nilai tetap di kode).
 * Input        : -
 * Output       : array untuk view site/resources/views/phone-ref/*.
 * Dipakai oleh : site/routes/phone-ref.php, demo phone-shell.
 * Status       : Aktif (situs katalog).
 */
final class PhoneRef
{
    // Layar contoh yang bisa dibuka: kode => [judul, menu utama yang aktif].
    public const SCREENS = [
        'home' => ['Home', 'home'],
        'form' => ['Item', 'stock'],
        'grid' => ['Items', 'stock'],
        'report' => ['Stock Summary', 'report'],
        'login' => ['Sign in', ''],
        'offer' => ['Sign in (desktop, narrow)', ''],
    ];

    public static function url(string $screen): string
    {
        return route('phone.ref', $screen);
    }

    // Empat menu utama (+ "Menu" yang ditambahkan shell).
    public static function tabs(): array
    {
        return [
            ['key' => 'home', 'label' => 'Home', 'icon' => 'fa-solid fa-house', 'url' => self::url('home')],
            ['key' => 'stock', 'label' => 'Stock', 'icon' => 'fa-solid fa-boxes-stacked', 'url' => self::url('grid')],
            ['key' => 'trx', 'label' => 'Transactions', 'icon' => 'fa-solid fa-right-left', 'url' => self::url('form')],
            ['key' => 'report', 'label' => 'Reports', 'icon' => 'fa-solid fa-chart-column', 'url' => self::url('report')],
        ];
    }

    public static function modules(): array
    {
        return [
            ['code' => 'MST', 'label' => 'Master Data', 'icon' => 'fa-solid fa-boxes-stacked', 'children' => [
                ['code' => 'ITM', 'label' => 'Items', 'url' => self::url('grid'), 'icon' => 'fa-solid fa-box'],
                ['code' => 'ITF', 'label' => 'Item form', 'url' => self::url('form'), 'icon' => 'fa-solid fa-pen-to-square'],
            ]],
            ['code' => 'RPT', 'label' => 'Reports', 'icon' => 'fa-solid fa-chart-column', 'children' => [
                ['code' => 'SUM', 'label' => 'Stock Summary', 'url' => self::url('report'), 'icon' => 'fa-solid fa-table-list'],
            ]],
        ];
    }

    public static function user(): array
    {
        return ['name' => 'Sample User', 'role' => 'Data Entry'];
    }

    public static function items(): array
    {
        $rows = [
            ['A-001', 'Notebook A5', 'Stationery', 'pcs', 42, 10, 'Shelf 1'],
            ['A-002', 'Ballpoint Black', 'Stationery', 'box', 19, 20, 'Shelf 1'],
            ['A-003', 'Whiteboard Marker', 'Stationery', 'box', 12, 8, 'Shelf 2'],
            ['A-004', 'Plastic Folder', 'Stationery', 'pack', 50, 15, 'Shelf 2'],
            ['B-001', 'Cleaning Wipes', 'Cleaning', 'pack', 6, 10, 'Shelf 3'],
            ['B-002', 'Hand Soap 500 ml', 'Cleaning', 'bottle', 24, 12, 'Shelf 3'],
            ['C-001', 'Extension Cable 3 m', 'Tools', 'pcs', 9, 5, 'Shelf 4'],
            ['C-002', 'Tape Measure', 'Tools', 'pcs', 4, 5, 'Shelf 4'],
        ];

        return array_map(fn ($r) => [
            'id' => $r[0], 'code' => $r[0], 'name' => $r[1], 'category' => $r[2], 'unit' => $r[3], 'stock' => $r[4], 'rop' => $r[5], 'location' => $r[6],
            'level' => $r[4] <= $r[5] ? 'REORDER' : 'OK', 'tone' => $r[4] <= $r[5] ? 'danger' : '', 'active' => true,
        ], $rows);
    }
}
