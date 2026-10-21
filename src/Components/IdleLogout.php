<?php

/*
 * REMARK
 * Tujuan       : Logout otomatis setelah tidak ada aktivitas: hitung mundur di browser, peringatan dengan tombol Tetap masuk, lalu logout dan pindah ke halaman login.
 * Sumber hulu  : Komponen generik DevLib; alamat logout dan ping dari aplikasi pemakai lewat prop, tanpa tabel/model proyek.
 * Input        : minutes, warn, logoutUrl, pingUrl, pingEvery, loginUrl, preview.
 * Output       : Dialog peringatan tersembunyi (alertdialog) yang dikendalikan skrip IdleLogout.
 * Dipakai oleh : (belum ada; opt-in, dipasang satu kali di layout aplikasi pewaris).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Logout otomatis setelah `minutes` menit tanpa aktivitas (mouse, klik, ketikan, scroll, sentuhan; semua tab ikut sinkron).
// Pasang SATU kali di layout aplikasi: <x-dl::idle-logout logout-url="/logout" ping-url="/session/ping" />. `warn` menit
// sebelum habis muncul peringatan dengan hitung mundur; "Tetap masuk" menyegarkan sesi lewat ping-url (GET, jawaban 204) dan
// menutup peringatan. Saat habis: POST ke logout-url (token CSRF dari window.DL_CSRF, meta csrf-token, atau input _token),
// lalu pindah ke login-url. Sisi server (umur sesi) tetap diurus aplikasi. minutes <= 0 = tidak merender apa pun.
// preview = peraga katalog: tidak pernah mengirim permintaan atau pindah halaman, hanya menampilkan pesan "Anda akan keluar".
class IdleLogout extends DevlibComponent
{
    public function __construct(
        public float $minutes = 30,
        public float $warn = 2,
        public string $logoutUrl = '',
        public string $pingUrl = '',
        public float $pingEvery = 5,
        public string $loginUrl = '',
        public bool $preview = false,
    ) {
        $this->warn = max(0, min($this->warn, $this->minutes));
    }

    public function tag(): string
    {
        return 'x-dl::idle-logout';
    }

    public function source(): string
    {
        return 'activity in the browser; session refresh = ping-url of the application';
    }

    public function jsClass(): ?string
    {
        return 'IdleLogout';
    }

    public function render()
    {
        return <<<'BLADE'
            @if ($minutes > 0)
                <div {{ $attributes->class('dl-idle') }} {!! $dl() !!} data-dl-idle data-minutes="{{ $minutes }}" data-warn="{{ $warn }}" data-logout-url="{{ $logoutUrl }}" data-ping-url="{{ $pingUrl }}" data-ping-every="{{ $pingEvery }}" data-login-url="{{ $loginUrl }}" @if ($preview) data-preview @endif hidden role="alertdialog" aria-labelledby="dl-idle-title" aria-describedby="dl-idle-msg">
                    <i class="fa-solid fa-hourglass-half dl-idle__icon" aria-hidden="true"></i>
                    <div class="dl-idle__body">
                        <b id="dl-idle-title" data-dl-t="idle_title">{{ \Devlib\Lang::t('idle_title') }}</b>
                        <span id="dl-idle-msg" data-idle-msg></span>
                        <div class="dl-idle__act">
                            <button type="button" class="dl-btn dl-btn--md dl-btn--primary" data-idle-stay data-dl-t="idle_stay">{{ \Devlib\Lang::t('idle_stay') }}</button>
                            <button type="button" class="dl-btn dl-btn--link" data-idle-out data-dl-t="idle_out">{{ \Devlib\Lang::t('idle_out') }}</button>
                        </div>
                    </div>
                </div>
            @endif
            BLADE;
    }
}
