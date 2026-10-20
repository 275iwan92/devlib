<?php

/*
 * REMARK
 * Tujuan       : Telepon per negara (Devlib\PhoneFormat): mask otomatis, buang 0 / +kode di depan, cek pola & panjang.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : countries, country, kind, name, label, tooltip, hint, error, value, placeholder, … (17 prop).
 * Output       : Markup dari resources/views/components/field/phone.blade.php.
 * Dipakai oleh : field-phone, form-md-po, form-md, form-single dan 3 lainnya (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components\Field;

// Telepon per negara (Devlib\PhoneFormat): mask otomatis, buang 0 / +kode di depan, cek pola & panjang.
// kind: mobile (HP) | landline (rumah/kantor, kode area) | any. Terkirim: name = E.164 (+62812...), name_country.
class Phone extends Field
{
    public function __construct(
        public array $countries = [],
        public string $country = 'ID',
        public string $kind = 'mobile',
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
        return 'phone';
    }
}
