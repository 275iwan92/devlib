<?php

/*
 * REMARK
 * Tujuan       : Angka bulat + tombol naik/turun.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : min, max, step, name, label, tooltip, hint, error, value, placeholder, … (17 prop).
 * Output       : Markup dari resources/views/components/field/number.blade.php.
 * Dipakai oleh : field-number (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components\Field;

// Angka bulat + tombol naik/turun.
class Number extends Field
{
    public function __construct(
        public int $min = 0,
        public ?int $max = null,
        public int $step = 1,
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
    }

    public function control(): string
    {
        return 'number';
    }
}
