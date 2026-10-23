<?php

/*
 * REMARK
 * Tujuan       : Pilihan tampilan perangkat (Phone view / Desktop view): dua pilihan radio besar bernama `ui`, terpilih awal menurut cookie atau tebakan perangkat.
 * Sumber hulu  : Devlib\Device (cookie ui, Sec-CH-UA-Mobile, User-Agent).
 * Input        : name, value, label.
 * Output       : fieldset berisi dua radio yang ikut terkirim bersama form tempatnya berada (login atau profil).
 * Dipakai oleh : x-dl::login-box (bagian device), x-dl::phone-shell (lembar Menu), halaman profil proyek.
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

use Devlib\Device;

// Pilihan tampilan perangkat: dua radio `ui` = hp | desktop, terpilih awal menurut Device::fromRequest (cookie menang atas tebakan).
// Komponen ini hanya MENAMPILKAN pilihan di dalam form milik pemakai; yang menyimpan cookie adalah proyek (satu rute yang membaca `ui`).
class DevicePick extends DevlibComponent
{
    public function __construct(
        public string $name = 'ui',
        // null = menurut cookie / tebakan permintaan ini; 'hp' | 'desktop' memaksa pilihan awal.
        public ?string $value = null,
        public string $label = 'Display',
    ) {
        // Urutan: nilai yang diberikan, isian lama (form ditolak lalu ditampilkan ulang), lalu cookie / tebakan.
        $old = old($name);
        $this->value = in_array($value, Device::CHOICES, true) ? $value : (in_array($old, Device::CHOICES, true) ? $old : Device::fromRequest(request()));
    }

    public function tag(): string
    {
        return 'x-dl::device-pick';
    }

    public function source(): string
    {
        return 'cookie ui, Sec-CH-UA-Mobile, User-Agent';
    }

    public function render()
    {
        return <<<'BLADE'
            <fieldset {{ $attributes->class('dl-devpick') }} {!! $dl() !!}>
                <legend class="dl-devpick__l">{{ \Devlib\Lang::text($label) }}</legend>
                @foreach ([\Devlib\Device::HP => ['fa-solid fa-mobile-screen', 'Phone view'], \Devlib\Device::DESKTOP => ['fa-solid fa-desktop', 'Desktop view']] as $v => [$icon, $text])
                    <label class="dl-devpick__o"><input type="radio" name="{{ $name }}" value="{{ $v }}" @checked($value === $v)><span class="dl-devpick__t"><i class="{{ $icon }}" aria-hidden="true"></i> {{ \Devlib\Lang::text($text) }}</span></label>
                @endforeach
            </fieldset>
            BLADE;
    }
}
