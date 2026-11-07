<?php

/*
 * REMARK
 * Tujuan       : Timer hitung mundur pribadi: menit dapat diatur (- / +, 1-600), cincin progres, Start/Pause/Reset, daftar preset (klik = pakai, simpan preset baru dari menit sekarang, hapus). Selesai: bunyi 'timer' (Devlib.sound, bawaan mati), toast, judul tab.
 * Sumber hulu  : Komponen generik DevLib; preset masuk lewat prop dan disimpan lewat saveUrl (JSON {items}) milik aplikasi; yang berjalan hanya di browser (localStorage).
 * Input        : presets, minutes, saveUrl, csrf, storageKey.
 * Output       : Markup Blade inline (render() kelas ini) + js/components/countdown.js + CSS .dl-cd.
 * Dipakai oleh : layar proyek lewat x-dl::countdown (menu Tools > Timer).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Timer hitung mundur. presets = [['id', 'label', 'minutes' => 1..600]]. Yang berjalan = localStorage[$storageKey] {running, endAt (ms), left (ms), total (ms)}:
// dihitung dari jam, benar setelah pindah layar / muat ulang. Selesai saat halaman ini terbuka = bunyi + toast; selesai saat halaman tertutup = ditutup diam-diam.
// $saveUrl = alamat POST JSON {items: presets}; kosong = pratinjau tanpa server. Pembeda dari x-dl::pomodoro: satu hitungan bebas, tanpa fase Focus/Break.
class Countdown extends DevlibComponent
{
    public function __construct(
        public array $presets = [],
        public int $minutes = 5,
        public string $saveUrl = '',
        public ?string $csrf = '',
        public string $storageKey = 'dl-countdown',
    ) {
        $this->minutes = min(600, max(1, $minutes));
    }

    public function tag(): string
    {
        return 'x-dl::countdown';
    }

    public function source(): string
    {
        return $this->saveUrl ?: 'pratinjau (tanpa server)';
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class('dl-cd') }} {!! $dl() !!} data-dl-cd data-key="{{ $storageKey }}" data-minutes="{{ $minutes }}" data-save="{{ $saveUrl }}" data-csrf="{{ $csrf }}"
                data-items="{{ json_encode(array_values($presets), JSON_UNESCAPED_UNICODE) }}" data-l-start="{{ \Devlib\Lang::text('Start') }}" data-l-pause="{{ \Devlib\Lang::text('Pause') }}"
                data-l-done="{{ \Devlib\Lang::text('Timer finished') }}" data-l-delete="{{ \Devlib\Lang::text('Delete') }}" data-l-min="{{ \Devlib\Lang::text('min') }}" data-l-failed="{{ \Devlib\Lang::text('Could not save. Try again.') }}">
                <div class="dl-cd__top">
                    <div class="dl-cd__ring">
                        <svg viewBox="0 0 100 100" aria-hidden="true"><circle cx="50" cy="50" r="44" class="dl-ring__track"/><circle cx="50" cy="50" r="44" class="dl-ring__bar dl-cd__bar" pathLength="100" stroke-dasharray="100" stroke-dashoffset="0" transform="rotate(-90 50 50)" data-dl-cd-bar/></svg>
                        <output class="dl-cd__time" data-dl-cd-time aria-live="off">00:00</output>
                    </div>
                    <div class="dl-cd__ctl">
                        <div class="dl-cd__set">
                            <button type="button" class="dl-cd__step" data-dl-cd-minus aria-label="{{ \Devlib\Lang::text('One minute shorter') }}"><i class="fa-solid fa-minus" aria-hidden="true"></i></button>
                            <input type="number" min="1" max="600" step="1" data-dl-cd-minutes aria-label="{{ \Devlib\Lang::text('Minutes') }}"> <span>{{ \Devlib\Lang::text('min') }}</span>
                            <button type="button" class="dl-cd__step" data-dl-cd-plus aria-label="{{ \Devlib\Lang::text('One minute longer') }}"><i class="fa-solid fa-plus" aria-hidden="true"></i></button>
                        </div>
                        <div class="dl-cd__btns">
                            <button type="button" class="dl-btn dl-btn--primary" data-dl-cd-play><i class="fa-solid fa-play" aria-hidden="true"></i> <span>{{ \Devlib\Lang::text('Start') }}</span></button>
                            <button type="button" class="dl-btn dl-btn--ghost" data-dl-cd-reset>{{ \Devlib\Lang::text('Reset') }}</button>
                        </div>
                    </div>
                </div>
                <ul class="dl-cd__presets" data-dl-cd-presets aria-label="{{ \Devlib\Lang::text('Presets') }}"></ul>
                <form class="dl-cd__add" data-dl-cd-add>
                    <input type="text" maxlength="40" data-dl-cd-label placeholder="{{ \Devlib\Lang::text('Preset name') }}" aria-label="{{ \Devlib\Lang::text('Preset name') }}" required>
                    <button type="submit" class="dl-btn dl-btn--ghost"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> {{ \Devlib\Lang::text('Save as preset') }}</button>
                </form>
            </div>
            BLADE;
    }
}
