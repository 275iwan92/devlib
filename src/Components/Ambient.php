<?php

/*
 * REMARK
 * Tujuan       : Musik latar ambient yang dibuat di peramban lewat Web Audio API (pad akor lembut berganti pelan), tanpa berkas audio, tanpa hak cipta. Dua tombol: Play/Pause dan Mute. Bawaan DIAM; suara mulai hanya setelah pengguna menekan Play.
 * Sumber hulu  : Komponen generik DevLib; tidak membaca data proyek.
 * Input        : label (teks tombol), volume (0-100, bawaan 18), storageKey.
 * Output       : Markup Blade (render() kelas ini) + js/components/ambient.js + CSS .dl-ambient.
 * Dipakai oleh : layar proyek (mis. User's Guide > Video di Help & Support); setara x-dl::music-player untuk bila tidak ada berkas musik.
 * Status       : Aktif (DevLib). Pilihan mute disimpan per perangkat di localStorage[$storageKey]; berhenti otomatis saat tab tersembunyi.
 */

namespace Devlib\Components;

// Ambient: <x-dl::ambient label="Background music" />. Tidak ada autoplay: browser memblokirnya dan pengguna harus memilih.
class Ambient extends DevlibComponent
{
    public function __construct(
        public string $label = 'Background music',
        public int $volume = 18,
        public string $storageKey = 'dl-ambient',
    ) {}

    public function tag(): string
    {
        return 'x-dl::ambient';
    }

    public function source(): string
    {
        return 'Web Audio API (tanpa berkas)';
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class('dl-ambient') }} {!! $dl() !!} data-dl-ambient data-volume="{{ $volume }}" data-key="{{ $storageKey }}" role="group" aria-label="{{ $label }}">
                <i class="fa-solid fa-music" aria-hidden="true"></i>
                <span class="dl-ambient__label">{{ \Devlib\Lang::text($label) }}</span>
                <button type="button" class="dl-ambient__play" data-am-play aria-pressed="false" title="{{ \Devlib\Lang::text('Play') }}"><i class="fa-solid fa-play"></i><b>{{ \Devlib\Lang::text('Play') }}</b></button>
                <button type="button" class="dl-ambient__mute" data-am-mute aria-pressed="false" title="{{ \Devlib\Lang::text('Mute') }}"><i class="fa-solid fa-volume-high"></i><b>{{ \Devlib\Lang::text('Mute') }}</b></button>
            </div>
            BLADE;
    }
}
