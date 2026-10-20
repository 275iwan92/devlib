<?php

/*
 * REMARK
 * Tujuan       : Angka + terbilang langsung saat diketik (Devlib\Terbilang, ikut bahasa user).
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : currency, picker, decimals, name, label, tooltip, hint, error, value, placeholder, … (17 prop).
 * Output       : Markup dari resources/views/components/field/number-words.blade.php.
 * Dipakai oleh : field-number-words (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components\Field;

use Devlib\Terbilang;

// Angka + terbilang langsung saat diketik (Devlib\Terbilang, ikut bahasa user).
// currency = kode (IDR/USD/...) -> kata mata uang ikut; picker = mata uang bisa dipilih.
// Tanpa currency: angka biasa, desimal (koma) dibaca "koma ...". Terkirim: name = angka mentah
// (titik desimal), name_currency bila ada mata uang.
class NumberWords extends Field
{
    public function __construct(
        public ?string $currency = null,
        public bool $picker = false,
        public int $decimals = 0,
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
        if ($this->picker && ! $this->currency) {
            $this->currency = 'IDR';
        }
    }

    public function control(): string
    {
        return 'number-words';
    }

    public function currencies(): array
    {
        return Terbilang::CURRENCIES;
    }
}
