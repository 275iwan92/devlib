<?php

/*
 * REMARK
 * Tujuan       : Pemilih warna + kode HEX.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : name, label, tooltip, hint, error, value, placeholder, required, disabled, readonly, … (14 prop).
 * Output       : Markup dari resources/views/components/field/color.blade.php.
 * Dipakai oleh : field-color (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components\Field;

// Pemilih warna + kode HEX.
class Color extends Field
{
    public function __construct(
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
        return 'color';
    }
}
