<?php

/*
 * REMARK
 * Tujuan       : Pilihan bahasa (bendera). Daftar bahasa = Locale::languages() (aplikasi: sys_languages yang aktif), jadi bahasa baru.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : langs, active, reload.
 * Output       : Markup Blade inline (render() kelas ini).
 * Dipakai oleh : i18n, misc (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

use Devlib\Locale;

// Pilihan bahasa (kode: EN | ID). Daftar bahasa = Locale::languages() (aplikasi: sys_languages yang aktif), jadi bahasa baru
// muncul sendiri. Memicu event "dl:lang" di dokumen (cookie perangkat + teks [data-dl-t]).
// reload: halaman dimuat ulang setelah bahasa dipilih supaya teks yang dirender server ikut berganti, dan pilihannya
// disimpan ke akun user lewat config devlib.lang_url. Bawaan: aktif bila ada user yang login; halaman tamu (login /
// daftar) berganti di tempat tanpa muat ulang.
class LangSwitch extends DevlibComponent
{
    public function __construct(
        public array $langs = [],
        public ?string $active = null,
        public ?bool $reload = null,
    ) {
        if (! $this->langs) {
            foreach (Locale::languages() as $code => $spec) {
                $this->langs[$code] = [strtoupper($code), $spec['name'] ?? $code]; // kode (EN | ID), bukan emoji bendera: Windows menampilkannya sebagai "GB" / "ID"
            }
        }
        $this->active ??= Locale::lang();
        $this->reload ??= function_exists('auth') && auth()->check();
    }

    public function tag(): string
    {
        return 'x-dl::lang-switch';
    }

    public function source(): string
    {
        return 'sys_languages + sec_user.lang_code (aplikasi pemakai)';
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class('dl-langs') }} {!! $dl() !!} data-dl-langs @if ($reload) data-reload @endif>
                @foreach ($langs as $code => [$flag, $title])
                    <button type="button" class="dl-flag {{ $code === $active ? 'is-active' : '' }}" data-lang="{{ $code }}" title="{{ $title }}">{{ $flag }}</button>
                @endforeach
            </div>
            BLADE;
    }
}
