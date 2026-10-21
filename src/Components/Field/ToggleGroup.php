<?php

/*
 * REMARK
 * Tujuan       : Pilihan tombol berderet (segmented), satu yang aktif. Mis. Harian / Mingguan / Bulanan.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : options, name, label, tooltip, hint, error, value, placeholder, required, disabled, … (15 prop).
 * Output       : Markup dari resources/views/components/field/toggle-group.blade.php.
 * Dipakai oleh : field-toggle-group (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components\Field;

// Pilihan tombol berderet (segmented), satu yang aktif. Mis. Harian / Mingguan / Bulanan.
class ToggleGroup extends Field
{
    public function __construct(
        public array $options = [],
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

    public function labelable(): bool
    {
        return false;
    }

    public function control(): string
    {
        return 'toggle-group';
    }
}
