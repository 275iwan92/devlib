<?php

/*
 * REMARK
 * Tujuan       : Pewaktu Pomodoro: Focus lalu Break dengan cincin progres, Play/Pause/Reset, durasi dapat diubah (- dan +, 1-90 menit), segmen Focus | Break, penghitung sesi Focus hari ini, pindah otomatis ke Break (opsi), judul tab dan notifikasi browser (opt-in) saat selesai.
 * Sumber hulu  : Komponen generik DevLib; durasi dan hitungan sesi masuk lewat prop dan disimpan lewat saveUrl (JSON) milik aplikasi; bunyi lewat Devlib.sound (js sound.js).
 * Input        : focus, break, auto, sessions, saveUrl, csrf, storageKey, compact.
 * Output       : Markup Blade inline (render() kelas ini) + js/components/pomodoro.js + CSS .dl-pomo.
 * Dipakai oleh : x-dl::widget-board (widget type 'pomodoro') dan layar proyek lewat x-dl::pomodoro.
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Pewaktu Pomodoro. Yang sedang berjalan hidup di browser (localStorage[$storageKey]: fase, cap waktu akhir) supaya benar setelah pindah layar,
// muat ulang, atau tab di belakang; selesai Focus = bunyi 'pomodoro' dan satu sesi bertambah, selesai Break = bunyi 'pomodoro_break'.
// $saveUrl = alamat POST JSON {focus, break, auto, done} -> {sessions}: server (bukan objek ini) menyimpan durasi dan menghitung sesi hari ini; kosong = pratinjau tanpa server.
// Bunyi mengikuti Setup > Sound (bawaan mati). prefers-reduced-motion / Devlib.motion 'off' mematikan animasi cincin.
class Pomodoro extends DevlibComponent
{
    public function __construct(
        public int $focus = 25,
        public int $break = 5,
        public bool $auto = true,
        public int $sessions = 0,
        public string $saveUrl = '',
        public string $csrf = '',
        public string $storageKey = 'dl-pomodoro',
        public bool $compact = false,
    ) {
        $this->focus = min(90, max(1, $focus));
        $this->break = min(90, max(1, $break));
    }

    public function tag(): string
    {
        return 'x-dl::pomodoro';
    }

    public function source(): string
    {
        return $this->saveUrl ?: 'pratinjau (tanpa server)';
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class(['dl-pomo', 'dl-pomo--compact' => $compact]) }} {!! $dl() !!} data-dl-pomo data-key="{{ $storageKey }}" data-focus="{{ $focus }}" data-break="{{ $break }}"
                data-auto="{{ $auto ? 1 : 0 }}" data-sessions="{{ $sessions }}" data-save="{{ $saveUrl }}" data-csrf="{{ $csrf }}"
                data-l-focus="{{ \Devlib\Lang::text('Focus') }}" data-l-break="{{ \Devlib\Lang::text('Break') }}" data-l-start="{{ \Devlib\Lang::text('Start') }}" data-l-pause="{{ \Devlib\Lang::text('Pause') }}"
                data-l-done-focus="{{ \Devlib\Lang::text('Focus finished') }}" data-l-done-break="{{ \Devlib\Lang::text('Break finished') }}">
                <div class="dl-pomo__ring">
                    <svg viewBox="0 0 100 100" aria-hidden="true"><circle cx="50" cy="50" r="44" class="dl-ring__track"/><circle cx="50" cy="50" r="44" class="dl-ring__bar dl-pomo__bar" pathLength="100" stroke-dasharray="100" stroke-dashoffset="100" transform="rotate(-90 50 50)" data-dl-pomo-bar/></svg>
                    <button type="button" class="dl-pomo__play" data-dl-pomo-play aria-label="{{ \Devlib\Lang::text('Start') }}" title="{{ \Devlib\Lang::text('Start') }}"><i class="fa-solid fa-play" aria-hidden="true"></i></button>
                </div>
                <div class="dl-pomo__main">
                    <div class="dl-pomo__time">
                        <button type="button" class="dl-pomo__step" data-dl-pomo-minus aria-label="{{ \Devlib\Lang::text('One minute shorter') }}" title="{{ \Devlib\Lang::text('One minute shorter') }}"><i class="fa-solid fa-minus" aria-hidden="true"></i></button>
                        <output class="dl-pomo__clock" data-dl-pomo-time aria-live="off">{{ sprintf('%02d:00', $focus) }}</output>
                        <button type="button" class="dl-pomo__step" data-dl-pomo-plus aria-label="{{ \Devlib\Lang::text('One minute longer') }}" title="{{ \Devlib\Lang::text('One minute longer') }}"><i class="fa-solid fa-plus" aria-hidden="true"></i></button>
                    </div>
                    <div class="dl-pomo__row">
                        <div class="dl-pomo__seg" role="radiogroup" aria-label="{{ \Devlib\Lang::text('Phase') }}">
                            <label><input type="radio" name="{{ $storageKey }}-phase" value="focus" data-dl-pomo-phase><span>{{ \Devlib\Lang::text('Focus') }}</span></label>
                            <label><input type="radio" name="{{ $storageKey }}-phase" value="break" data-dl-pomo-phase><span>{{ \Devlib\Lang::text('Break') }}</span></label>
                        </div>
                        <span class="dl-pomo__count">{{ \Devlib\Lang::text('Sessions today') }}: <b data-dl-pomo-sessions>{{ $sessions }}</b></span>
                        <details class="dl-pomo__menu">
                            <summary aria-label="{{ \Devlib\Lang::text('More') }}" title="{{ \Devlib\Lang::text('More') }}"><i class="fa-solid fa-ellipsis" aria-hidden="true"></i></summary>
                            <div class="dl-pomo__pop">
                                <button type="button" data-dl-pomo-reset>{{ \Devlib\Lang::text('Reset') }}</button>
                                <label><input type="checkbox" data-dl-pomo-auto> <span>{{ \Devlib\Lang::text('Start Break automatically') }}</span></label>
                                <label><input type="checkbox" data-dl-pomo-notify> <span>{{ \Devlib\Lang::text('Browser notification') }}</span></label>
                            </div>
                        </details>
                    </div>
                </div>
            </div>
            BLADE;
    }
}
