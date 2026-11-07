<?php

/*
 * REMARK
 * Tujuan       : Toleransi (selisih boleh) dalam SATU kontrol ringkas: dropdown arah (± + −) di depan, angka (maks 4 digit bulat) di tengah, dropdown satuan (% / pcs) di belakang.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : dirName, unitName, dir, unit, dirs, units, decimals, digits, name, label, tooltip, hint, error, value, … (22 prop).
 * Output       : Markup dari resources/views/components/field/tolerance.blade.php; tiga nilai terkirim: name (angka kanonik), dirName, unitName.
 * Dipakai oleh : field-tolerance (katalog); proyek pewaris (mis. toleransi kirim Customer Order).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components\Field;

// Toleransi satu kontrol: [arah ▾][ angka ][satuan ▾]. Angka memakai perilaku field.decimal (format bahasa tampilan,
// kanonik saat kirim) dengan bagian bulat dibatasi `digits` digit. Arah dan satuan = daftar bernilai tetap (kode => simbol).
class Tolerance extends Field
{
    public function __construct(
        public string $dirName = '',
        public string $unitName = '',
        public string $dir = 'BOTH',
        public string $unit = 'PCT',
        public array $dirs = ['BOTH' => '±', 'PLUS' => '+', 'MINUS' => '−'],
        public array $units = ['PCT' => '%', 'QTY' => 'pcs'],
        public int $decimals = 2,
        public int $digits = 4,
        string $name = '',
        string $label = '',
        ?string $tooltip = null,
        ?string $hint = null,
        ?string $error = null,
        mixed $value = null,
        ?string $placeholder = null,
        bool $required = false,
        bool $disabled = false,
        bool $readonly = false,
        ?string $icon = null,
        string $size = 'sm',
        ?string $i18n = null,
        ?string $source = null,
    ) {
        parent::__construct($name, $label, $tooltip, $hint, $error, $value, $placeholder, $required, $disabled, $readonly, $icon, $size, $i18n, $source);
        $this->dirName = $dirName !== '' ? $dirName : ($name !== '' ? $name.'_dir' : '');
        $this->unitName = $unitName !== '' ? $unitName : ($name !== '' ? $name.'_unit' : '');
    }

    public function control(): string
    {
        return 'tolerance';
    }
}
