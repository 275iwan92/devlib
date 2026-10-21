<?php

/*
 * REMARK
 * Tujuan       : Tampilan nilai read-only (bukan kotak isian): label + nilai. format: text|money|number|date|mono.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : format, name, label, tooltip, hint, error, value, placeholder, required, disabled, … (15 prop).
 * Output       : Markup dari resources/views/components/field/display.blade.php.
 * Dipakai oleh : field-display, layer-menu, metadata, modal-drawer (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components\Field;

// Tampilan nilai read-only (bukan kotak isian): label + nilai. format: text|money|number|date|mono.
class Display extends Field
{
    public function __construct(
        public string $format = 'text',
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
        // Tampilan tanpa input bernama: required tidak berarti (form.js menganggapnya selalu kosong).
        $this->required = false;
    }

    public function labelable(): bool
    {
        return false;
    }

    public function control(): string
    {
        return 'display';
    }
}
