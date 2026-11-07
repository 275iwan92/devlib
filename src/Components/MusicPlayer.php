<?php

/*
 * REMARK
 * Tujuan       : Pemutar musik latar kecil (pil): tombol Play/Pause, bisukan, volume, dan kredit judul/pencipta/lisensi trek yang main. Bawaan DIAM; suara baru mulai setelah pengguna menekan Play (browser memblokir autoplay bersuara).
 * Sumber hulu  : Komponen generik DevLib; daftar trek masuk lewat prop, tanpa tabel/model proyek.
 * Input        : tracks, base, storageKey, volume.
 * Output       : Markup Blade inline (render() kelas ini) + js/components/music-player.js + CSS .dl-music.
 * Dipakai oleh : x-dl::greeting-card (board['music']) dan layar proyek lewat x-dl::music-player.
 * Status       : Aktif (DevLib). Pola dari proyek admin Jo: trek berurutan, fade 1 detik, jeda saat tab tersembunyi, posisi dilanjutkan.
 */

namespace Devlib\Components;

// Pemutar musik latar. $tracks = daftar ['file' => 'a.mp3', 'title' => ..., 'author' => ..., 'url' => ?, 'license' => ?, 'license_url' => ?]; $base = alamat folder berkas audio.
// Pengaturan (on, mute, vol, i, pos) disimpan per perangkat di localStorage[$storageKey]. Kredit selalu tampil selama trek main (syarat lisensi CC BY).
class MusicPlayer extends DevlibComponent
{
    public function __construct(
        public array $tracks = [],
        public string $base = '/audio',
        public string $storageKey = 'dl-music',
        public int $volume = 25,
    ) {}

    public function tag(): string
    {
        return 'x-dl::music-player tracks='.count($this->tracks);
    }

    public function source(): string
    {
        return $this->base.' ('.count($this->tracks).' trek)';
    }

    public function render()
    {
        return <<<'BLADE'
            @if ($tracks)
            <div {{ $attributes->class(['dl-music']) }} {!! $dl() !!} data-dl-music data-key="{{ $storageKey }}" data-base="{{ rtrim($base, '/') }}/" data-volume="{{ $volume }}"
                data-tracks="{{ json_encode(array_values($tracks), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}"
                data-l-play="{{ \Devlib\Lang::text('Play music') }}" data-l-pause="{{ \Devlib\Lang::text('Pause music') }}" data-l-mute="{{ \Devlib\Lang::text('Mute music') }}" data-l-unmute="{{ \Devlib\Lang::text('Unmute music') }}">
                <button type="button" class="dl-music__btn" data-dl-music-play aria-pressed="false" aria-label="{{ \Devlib\Lang::text('Play music') }}" title="{{ \Devlib\Lang::text('Play music') }}"><i class="fa-solid fa-play" aria-hidden="true"></i></button>
                <button type="button" class="dl-music__btn" data-dl-music-mute aria-pressed="false" aria-label="{{ \Devlib\Lang::text('Mute music') }}" title="{{ \Devlib\Lang::text('Mute music') }}" hidden><i class="fa-solid fa-volume-high" aria-hidden="true"></i></button>
                <input type="range" class="dl-music__vol" min="0" max="100" step="5" value="{{ $volume }}" data-dl-music-vol aria-label="{{ \Devlib\Lang::text('Music volume') }}" hidden>
                <span class="dl-music__credit" data-dl-music-credit hidden></span>
            </div>
            @endif
            BLADE;
    }
}
