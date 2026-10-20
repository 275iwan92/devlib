<?php

/*
 * REMARK
 * Tujuan       : Teks antarmuka dari kamus Devlib\Lang (ID/EN); ikut berganti saat bahasa diganti tanpa muat ulang.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : key.
 * Output       : Markup Blade inline (render() kelas ini).
 * Dipakai oleh : i18n (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Teks antarmuka dari kamus Devlib\\Lang (ID/EN); ikut berganti saat bahasa diganti tanpa muat ulang.
class LangText extends DevlibComponent
{
    public function __construct(
        public string $key = '',
    ) {}

    public function tag(): string
    {
        return 'x-dl::lang-text key='.$this->key;
    }

    public function render()
    {
        return <<<'BLADE'
            <span {{ $attributes }} {!! $dl() !!} data-dl-t="{{ $key }}">{{ \Devlib\Lang::t($key) }}</span>
            BLADE;
    }
}
