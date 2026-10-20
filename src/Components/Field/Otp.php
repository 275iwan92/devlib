<?php

/*
 * REMARK
 * Tujuan       : Kode OTP per digit, fokus pindah otomatis.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : length, name, label, tooltip, hint, error, value, placeholder, required, disabled, … (15 prop).
 * Output       : Markup dari resources/views/components/field/otp.blade.php.
 * Dipakai oleh : field-otp (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components\Field;

// Kode OTP per digit, fokus pindah otomatis.
class Otp extends Field
{
    public function __construct(
        public int $length = 6,
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
        return 'otp';
    }
}
