<?php

/*
 * REMARK
 * Tujuan       : Input bermask angka, mis. NIK 6.6.4 digit.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : mask, invalidText, name, label, tooltip, hint, error, value, placeholder, required, … (16 prop).
 * Output       : Markup dari resources/views/components/field/masked.blade.php.
 * Dipakai oleh : field-masked, form-md-po, form-md, form-single dan 1 lainnya (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components\Field;

// Input bermask angka, mis. NIK 6.6.4 digit.
class Masked extends Field
{
    public function __construct(
        public string $mask = '999999.999999.9999',
        public ?string $invalidText = null,
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
        return 'masked';
    }
}
