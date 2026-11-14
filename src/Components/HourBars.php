<?php

/*
 * REMARK
 * Tujuan       : Batang jam per hari (Mon..Sun) pada sumbu 00-24: tarik di area kosong untuk membuat jam kerja, geser batang untuk memindah, tarik ujung untuk mengubah jam masuk / pulang; istirahat tampil sebagai ruas terang di dalam batang; klik nama hari untuk meliburkan.
 * Sumber hulu  : Komponen generik DevLib; nilai dibaca dari dan ditulis ke isian jam per hari di form yang sama (isian itu tetap sumber simpan), tanpa tabel/model proyek.
 * Input        : fields [peran => pola nama isian dengan {day}], days [kunci hari => label], from, to (jam sumbu), snap (menit), readonly.
 * Output       : Isian jam per hari ditulis (event input + change dikirim, form ditandai belum disimpan); batang digambar ulang bila isian berubah (isi sekali, ketik manual, Undo). Gaya .dl-hbars di devlib.css.
 * Dipakai oleh : (proyek) form Master Shift tab Working hours; demo katalog hour-bars.
 * Status       : Aktif (DevLib). Pasangan JS: components/hour-bars.js.
 */

namespace Devlib\Components;

use Devlib\Lang;

// Batang jam mingguan. fields: peran => pola name isian, peran start (jam masuk), bout (mulai istirahat), bin (selesai istirahat), end (jam pulang), mis. 'start_{day}'.
// Batang yang melewati tengah malam (pulang <= masuk) digambar dua ruas dan tidak bisa diseret (ubah lewat isian jamnya). Server tetap yang memvalidasi.
class HourBars extends DevlibComponent
{
    public function __construct(
        public array $fields = [],
        public array $days = WeekFill::DAYS,
        public int $from = 0,
        public int $to = 24,
        public int $snap = 15,
        public bool $readonly = false,
    ) {}

    public function tag(): string
    {
        return 'x-dl::hour-bars';
    }

    public function config(): array
    {
        return [
            'fields' => (object) $this->fields, 'days' => (object) $this->days, 'from' => $this->from, 'to' => $this->to, 'snap' => max(1, $this->snap), 'readonly' => $this->readonly,
            'text' => [
                'off' => Lang::text('off'), 'hours' => Lang::text(':h h'), 'night' => Lang::text('past midnight: edit the times'),
                'hint' => Lang::text('Drag in an empty area to create, drag the bar to move, drag an end to change. Click a day name to set it off.'),
            ],
        ];
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class('dl-hbars') }} {!! $dl() !!} data-dl-hbars data-config="{{ json_encode($config()) }}">
                <div class="dl-hbars__axis" data-hb-axis></div>
                @foreach ($days as $key => $label)
                    <div class="dl-hbars__row" data-hb-row="{{ $key }}">
                        <button type="button" class="dl-hbars__day" data-hb-off="{{ $key }}" title="{{ \Devlib\Lang::text('Set off') }}">{{ $label }}</button>
                        <div class="dl-hbars__track" data-hb-track="{{ $key }}"></div>
                        <span class="dl-hbars__net" data-hb-net="{{ $key }}"></span>
                    </div>
                @endforeach
                <p class="dl-hbars__hint">{{ \Devlib\Lang::text($readonly ? 'Read only.' : 'Drag in an empty area to create, drag the bar to move, drag an end to change. Click a day name to set it off.') }}</p>
            </div>
            BLADE;
    }
}
