<?php

/*
 * REMARK
 * Tujuan       : Halaman kesalahan baku (403, 404, 419, 500, 503, offline): kode, judul, penjelasan, tombol Back & Beranda. full=false untuk pratinjau di dalam halaman.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : code, title, message, home, full, note, eta, etaLabel.
 * Output       : Markup Blade inline (render() kelas ini) + js/components/error-page.js (hitung mundur eta).
 * Dipakai oleh : error-pages, 403, 404, 419 dan 2 lainnya (layar katalog/proyek).
 * Status       : Aktif (DevLib). 2026-11-17 (Jo): kode construction (Under construction) + prop note (catatan disorot), eta (hitung mundur) dan etaLabel.
 */

namespace Devlib\Components;

// Halaman kesalahan baku (403, 404, 419, 500, 503, offline): kode, judul, penjelasan, tombol Back & Beranda. full=false untuk pratinjau di dalam halaman.
class ErrorPage extends DevlibComponent
{
    public function __construct(
        public string $code = '404',
        public ?string $title = null,
        public ?string $message = null,
        public string $home = '/',
        public bool $full = true,
        // Catatan singkat yang disorot (mis. alasan layar ditutup). null = tanpa catatan.
        public ?string $note = null,
        // Perkiraan selesai (ISO 8601 dengan zona, mis. 2026-11-17T16:30:00+07:00): dihitung mundur di halaman (error-page.js). null = tanpa hitung mundur.
        public ?string $eta = null,
        // Teks perkiraan selesai yang dibaca manusia (mis. 17-Nov-2026 16:30), tampil bersama hitung mundur.
        public ?string $etaLabel = null,
    ) {}

    public function tag(): string
    {
        return 'x-dl::error-page code='.$this->code;
    }

    // kode => [judul, penjelasan, ikon, warna]
    public const CODES = [
        '403' => ['Access denied', 'You are not allowed to open this page. Contact the admin if you think this is a mistake.', 'fa-lock', 'warn'],
        '404' => ['Page not found', 'The address you opened does not exist or has been moved.', 'fa-compass', 'accent'],
        '419' => ['Session expired', 'The page was left open too long. Reload it and repeat your action.', 'fa-clock-rotate-left', 'warn'],
        '500' => ['A system error occurred', 'Sorry, there is a problem on the server. The IT team has been notified; try again in a moment.', 'fa-triangle-exclamation', 'danger'],
        'construction' => ['Under construction', 'This page is being improved right now. Please come back later; your data is safe.', 'fa-helmet-safety', 'warn'],
        '503' => ['Under maintenance', 'The application is being updated. Please come back in a few minutes.', 'fa-screwdriver-wrench', 'accent'],
        'offline' => ['No connection', 'The device is offline. Check the network and reload.', 'fa-wifi', 'danger'],
    ];

    public function info(): array
    {
        return self::CODES[$this->code] ?? ['An error occurred', 'Please try again.', 'fa-circle-exclamation', 'danger'];
    }

    public function render()
    {
        return <<<'BLADE'
            @php [$t, $m, $ic, $tone] = $info(); @endphp
            <div {{ $attributes->class(['dl-error', 'dl-error--full' => $full]) }} {!! $dl() !!}>
                <div class="dl-error__card">
                    <span class="dl-error__icon dl-error__icon--{{ $tone }}"><i class="fa-solid {{ $ic }}"></i></span>
                    <div class="dl-error__code">{{ $code === 'offline' ? '' : $code }}</div>
                    <h1 class="dl-error__title">{{ $title ?? \Devlib\Lang::text($t) }}</h1>
                    <p class="dl-error__msg">{{ $message ?? \Devlib\Lang::text($m) }}</p>
                    @if ($note)<p class="dl-error__msg"><mark class="dl-error__note">{{ $note }}</mark></p>@endif
                    @if ($eta)<p class="dl-error__msg"><b>{{ \Devlib\Lang::text('Expected back') }}:</b> {{ $etaLabel ?? $eta }}<br><b class="dl-error__eta" data-dl-eta="{{ $eta }}" data-dl-eta-done="{{ \Devlib\Lang::text('Almost ready, this page will reload by itself.') }}">--:--:--</b></p>@endif
                    <div class="dl-error__actions"><x-dl::back-button :fallback="$home" /><x-dl::button :href="$home" icon="home" label="Home" variant="secondary" /></div>
                </div>
            </div>
            BLADE;
    }
}
