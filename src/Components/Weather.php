<?php

/*
 * REMARK
 * Tujuan       : Cuaca terkini dari internet (Open-Meteo, gratis tanpa kunci API), diambil oleh browser. Bawaan: tempat = posisi perangkat (Geolocation, nama kota dari reverse geocoding); gagal/ditolak -> lokasi dari IP pengakses (ipapi.co); gagal lagi -> city/lat/lon tempat cadangan.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : locate, city, lat, lon, variant, ipFallback.
 * Output       : Markup Blade inline (render() kelas ini).
 * Dipakai oleh : layar proyek lewat x-dl::weather.
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Cuaca terkini dari internet (Open-Meteo, gratis tanpa kunci API), diambil oleh browser.
// Bawaan (keputusan Jo 2026-10-23, lapis IP ditambah 2026-11-03): tempat cuaca = POSISI PERANGKAT (locate=true). Bila ditolak/tidak tersedia/
// konteks bukan HTTPS / localhost / timeout, coba LAPIS IP (ipFallback=true: ipapi.co dari browser, tanpa izin, jalan di HTTP biasa). Bila itu
// juga gagal, city + lat + lon = tempat CADANGAN; tanpa cadangan dan semua lapis gagal = kartu tidak tampil. locate=false memaksa tempat tetap
// dari props (lapis IP tidak dicoba).
class Weather extends DevlibComponent
{
    public function __construct(
        public bool $locate = true,
        public ?string $city = null,
        public ?float $lat = null,
        public ?float $lon = null,
        // glass (bawaan: kaca gelap di atas foto / kartu gelap) | card (kartu biru tua kecil di halaman terang) | chip (pil kecil: ikon, suhu, kota).
        public string $variant = 'glass',
        // Lapis tengah sebelum tempat cadangan: lokasi dari IP pengakses bila lokasi perangkat gagal/ditolak. false = lompat langsung ke cadangan.
        public bool $ipFallback = true,
    ) {}

    // Tempat cadangan lengkap (lat dan lon terisi).
    public function hasFixed(): bool
    {
        return $this->lat !== null && $this->lon !== null;
    }

    public function tag(): string
    {
        return 'x-dl::weather'.($this->city !== null ? ' city='.$this->city : '');
    }

    public function source(): string
    {
        return 'internet: api.open-meteo.com ('.($this->locate ? 'device location, ip fallback '.($this->ipFallback ? 'on' : 'off') : $this->lat.', '.$this->lon).')';
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class(['dl-weather', 'dl-weather--'.$variant => $variant !== 'glass']) }} {!! $dl() !!} data-dl-weather data-locate="{{ $locate ? 1 : 0 }}" data-ip-fallback="{{ $ipFallback ? 1 : 0 }}" @if ($hasFixed()) data-lat="{{ $lat }}" data-lon="{{ $lon }}" data-city="{{ $city }}" @else hidden @endif><span class="dl-weather__icon" data-dl-weather-icon>⛅</span><div><div class="dl-weather__temp" data-dl-weather-temp>--°C</div><div class="dl-weather__desc"><span data-dl-weather-desc>{{ \Devlib\Lang::text('loading weather…') }}</span><span data-dl-weather-city>{{ ! $locate && $city ? ' · '.$city : '' }}</span></div></div></div>
            BLADE;
    }
}
