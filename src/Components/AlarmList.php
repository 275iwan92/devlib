<?php

/*
 * REMARK
 * Tujuan       : Daftar alarm pribadi: jam, label, hari ulang (kosong = sekali), saklar on/off, hapus, dan form Tambah. Perubahan dikirim ke server sebagai seluruh daftar.
 * Sumber hulu  : Komponen generik DevLib; daftar masuk lewat prop dan disimpan lewat saveUrl (JSON {items}) milik aplikasi; yang membunyikan alarm adalah x-dl::alarm-runner.
 * Input        : items, saveUrl, csrf, storageKey.
 * Output       : Markup Blade inline (render() kelas ini) + js/components/alarm.js + CSS .dl-alarm.
 * Dipakai oleh : layar proyek lewat x-dl::alarm-list (menu Tools > Alarm).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Daftar alarm. items = [['id', 'time' => 'HH:MM', 'label', 'days' => [0=Min..6=Sab] (kosong = sekali), 'on' => bool]].
// Setiap perubahan menyalin daftar aktif ke localStorage[$storageKey] (dibaca x-dl::alarm-runner di halaman induk) dan mengirim seluruh daftar ke $saveUrl; kosong = pratinjau.
class AlarmList extends DevlibComponent
{
    public function __construct(
        public array $items = [],
        public string $saveUrl = '',
        public ?string $csrf = '',
        public string $storageKey = 'dl-alarms',
    ) {}

    public function tag(): string
    {
        return 'x-dl::alarm-list';
    }

    public function source(): string
    {
        return $this->saveUrl ?: 'pratinjau (tanpa server)';
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class('dl-alarm') }} {!! $dl() !!} data-dl-alarm data-key="{{ $storageKey }}" data-save="{{ $saveUrl }}" data-csrf="{{ $csrf }}"
                data-items="{{ json_encode(array_values($items), JSON_UNESCAPED_UNICODE) }}"
                data-l-days="{{ json_encode([\Devlib\Lang::text('Sun'), \Devlib\Lang::text('Mon'), \Devlib\Lang::text('Tue'), \Devlib\Lang::text('Wed'), \Devlib\Lang::text('Thu'), \Devlib\Lang::text('Fri'), \Devlib\Lang::text('Sat')], JSON_UNESCAPED_UNICODE) }}"
                data-l-once="{{ \Devlib\Lang::text('Once') }}" data-l-delete="{{ \Devlib\Lang::text('Delete') }}" data-l-failed="{{ \Devlib\Lang::text('Could not save. Try again.') }}">
                <ul class="dl-alarm__list" data-dl-alarm-list></ul>
                <p class="dl-alarm__empty" data-dl-alarm-empty>{{ \Devlib\Lang::text('No alarms yet.') }}</p>
                <form class="dl-alarm__add" data-dl-alarm-add>
                    <input type="time" required data-dl-alarm-time aria-label="{{ \Devlib\Lang::text('Time') }}">
                    <input type="text" maxlength="60" data-dl-alarm-label placeholder="{{ \Devlib\Lang::text('Label') }}" aria-label="{{ \Devlib\Lang::text('Label') }}">
                    <fieldset class="dl-alarm__days" aria-label="{{ \Devlib\Lang::text('Repeat on') }}">
                        @foreach ([1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 0 => 'Sun'] as $d => $name)
                            <label><input type="checkbox" value="{{ $d }}" data-dl-alarm-day><span>{{ \Devlib\Lang::text($name) }}</span></label>
                        @endforeach
                    </fieldset>
                    <button type="submit" class="dl-btn dl-btn--primary"><i class="fa-solid fa-plus" aria-hidden="true"></i> {{ \Devlib\Lang::text('Add alarm') }}</button>
                </form>
            </div>
            BLADE;
    }
}
