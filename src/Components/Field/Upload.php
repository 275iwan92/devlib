<?php

/*
 * REMARK
 * Tujuan       : Unggah file (tarik-lepas / pilih) + pratinjau nama file.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : accept, multiple, preview, name, label, tooltip, hint, error, value, placeholder, … (17 prop).
 * Output       : Markup dari resources/views/components/field/upload.blade.php.
 * Dipakai oleh : field-upload, form-md-po, form-md (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components\Field;

// Unggah file (tarik-lepas / pilih) + pratinjau nama file.
class Upload extends Field
{
    public function __construct(
        public ?string $accept = null,
        public bool $multiple = false,
        public bool $preview = true,
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
        return 'upload';
    }
}
