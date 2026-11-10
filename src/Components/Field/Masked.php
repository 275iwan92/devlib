<?php

/*
 * REMARK
 * Tujuan       : Input bermask angka, mis. NIK 6.6.4 digit.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : mask, invalidText, nikBirth / nikGender / nikMale / nikFemale (mode NIK, lihat di bawah), name, label, tooltip, hint, error, value, placeholder, required, … (16 prop).
 * Output       : Markup dari resources/views/components/field/masked.blade.php.
 * Dipakai oleh : field-masked, form-md-po, form-md, form-single dan 1 lainnya (layar katalog/proyek).
 * Status       : Aktif (DevLib). 2026-11-10 (Jo): mode NIK. nikBirth / nikGender = nama field tanggal lahir / jenis kelamin pada form yang sama; bila diisi, mengetik 16 digit NIK (6 wilayah . 6 tanggal lahir DDMMYY, +40 untuk perempuan . 4 urut) mengisi keduanya otomatis, dan tepat di bawah kolom langsung muncul pesan bila belum benar (kurang digit, tanggal tidak sah, atau beda dengan isian tanggal lahir / jenis kelamin). nikMale / nikFemale = nilai option jenis kelamin (bawaan M / F).
 */

namespace Devlib\Components\Field;

// Input bermask angka, mis. NIK 6.6.4 digit.
class Masked extends Field
{
    public function __construct(
        public string $mask = '999999.999999.9999',
        public ?string $invalidText = null,
        public ?string $nikBirth = null,
        public ?string $nikGender = null,
        public string $nikMale = 'M',
        public string $nikFemale = 'F',
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

    // Teks pesan mode NIK (diterjemahkan lewat Lang): :n = digit terisi, :t = total, :v = nilai menurut NIK.
    public function nikMessages(): array
    {
        return [
            'count' => \Devlib\Lang::text('NIK has :n of :t digits.'),
            'date' => \Devlib\Lang::text('Digits 7-12 are not a valid birth date (DDMMYY; women add 40 to the day).'),
            'differs' => \Devlib\Lang::text('The NIK says :v.'),
            'male' => \Devlib\Lang::text('Male'),
            'female' => \Devlib\Lang::text('Female'),
        ];
    }

    public function control(): string
    {
        return 'masked';
    }
}
