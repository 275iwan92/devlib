<?php

/*
 * REMARK
 * Tujuan       : Bilah "isi sekali" untuk pola jam mingguan: pilih hari lewat chip (Mon..Sun, preset Weekdays / All / Clear), isi jam masuk, mulai dan selesai istirahat, jam pulang SEKALI di empat isian yang disediakan layar (slot), lalu Apply menyalin ke isian hari yang dipilih di form yang sama.
 * Sumber hulu  : Komponen generik DevLib; tanpa tabel/model proyek. Isian jam sumber diberikan layar lewat slot (mis. x-dl::field.time).
 * Input        : map [start|bout|bin|end => ['from' => nama isian sumber, 'to' => pola nama isian target dengan {day}]], days [Nama hari => label], selected [hari yang awalnya terpilih], slot isian sumber.
 * Output       : Nilai disalin ke isian target di form yang sama (event input dan change dikirim supaya widget jam ikut), form ditandai belum disimpan; ringkasan "jam bersih per hari dan per minggu" dihitung dari isian sumber. Gaya .dl-weekfill di devlib.css.
 * Dipakai oleh : (proyek) form Master Shift tab Working hours; demo katalog week-fill.
 * Status       : Aktif (DevLib). Pasangan JS: components/week-fill.js.
 */

namespace Devlib\Components;

use Devlib\Lang;

// Bilah isi sekali pola mingguan. map: peran => ['from' => name isian sumber (di slot), 'to' => pola name isian target, mis. 'start_{day}'].
// Peran: start (jam masuk), bout (mulai istirahat), bin (selesai istirahat), end (jam pulang). Jam bersih = (end - start) - (bin - bout), lewat tengah malam bila selesai <= mulai.
// Server tetap yang memvalidasi isian hari (kosong masuk+pulang = hari tidak beroperasi); objek ini hanya mengisi.
class WeekFill extends DevlibComponent
{
    public const DAYS = ['Monday' => 'Mon', 'Tuesday' => 'Tue', 'Wednesday' => 'Wed', 'Thursday' => 'Thu', 'Friday' => 'Fri', 'Saturday' => 'Sat', 'Sunday' => 'Sun'];

    public function __construct(
        public array $map = [],
        public array $days = self::DAYS,
        public array $selected = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
        public string $applyLabel = 'Apply to selected days',
    ) {}

    public function tag(): string
    {
        return 'x-dl::week-fill';
    }

    public function config(): array
    {
        return [
            'map' => (object) $this->map, 'days' => (object) $this->days, 'selected' => array_values($this->selected),
            'text' => [
                'weekdays' => Lang::text('Weekdays'), 'all' => Lang::text('All'), 'clear' => Lang::text('Clear'),
                'net' => Lang::text('Net per day: :h h · :n days selected = :w h per week'), 'none' => Lang::text('Choose at least one day.'),
                'applied' => Lang::text('Applied to :n days. Save to keep it.'),
            ],
        ];
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class('dl-weekfill') }} {!! $dl() !!} data-dl-weekfill data-config="{{ json_encode($config()) }}">
                <div class="dl-weekfill__days" role="group" aria-label="{{ \Devlib\Lang::text('Days') }}">
                    @foreach ($days as $key => $label)
                        <button type="button" class="dl-weekfill__chip{{ in_array($key, $selected, true) ? ' is-on' : '' }}" data-wf-day="{{ $key }}" aria-pressed="{{ in_array($key, $selected, true) ? 'true' : 'false' }}">{{ $label }}</button>
                    @endforeach
                    <span class="dl-weekfill__presets"><button type="button" data-wf-preset="weekdays">{{ \Devlib\Lang::text('Weekdays') }}</button> · <button type="button" data-wf-preset="all">{{ \Devlib\Lang::text('All') }}</button> · <button type="button" data-wf-preset="clear">{{ \Devlib\Lang::text('Clear') }}</button></span>
                </div>
                <div class="dl-weekfill__fields">
                    {{ $slot }}
                    <button type="button" class="dl-btn dl-btn--primary dl-btn--md" data-wf-apply><i class="fa-solid fa-copy"></i>&nbsp;{{ \Devlib\Lang::text($applyLabel) }}</button>
                </div>
                <p class="dl-weekfill__net" data-wf-net aria-live="polite"></p>
            </div>
            BLADE;
    }
}
