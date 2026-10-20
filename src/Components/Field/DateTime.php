<?php

/*
 * REMARK
 * Tujuan       : Tanggal + jam dalam satu popover (mockup form: "Select Date & Time").
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : minuteStep, name, label, tooltip, hint, error, value, placeholder, required, disabled, … (15 prop).
 * Output       : Markup dari resources/views/components/field/date-time.blade.php.
 * Dipakai oleh : field-date-time (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components\Field;

// Tanggal + jam dalam satu popover (mockup form: "Select Date & Time").
// Nilai tersimpan "YYYY-MM-DD HH:MM" (zona waktu sistem, WIB); tampil "1 Okt 1969, 14:30 WIB".
class DateTime extends Field
{
    public function __construct(
        public int $minuteStep = 5,
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
        return 'date-time';
    }
}
