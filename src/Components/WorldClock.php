<?php

/*
 * REMARK
 * Tujuan       : Jam dunia pribadi: kartu per kota (jam berjalan, tanggal, hari/malam, selisih jam dari waktu perangkat), pilih kota dari daftar zona waktu IANA, hapus. Waktu dihitung browser (Intl), bukan server.
 * Sumber hulu  : Komponen generik DevLib; daftar kota masuk lewat prop dan disimpan lewat saveUrl (JSON {items}) milik aplikasi.
 * Input        : items, zones, saveUrl, csrf.
 * Output       : Markup Blade inline (render() kelas ini) + js/components/world-clock.js + CSS .dl-wclock.
 * Dipakai oleh : layar proyek lewat x-dl::world-clock (menu Tools > World Clock).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Jam dunia. items = [['id', 'tz' => nama zona IANA, 'label']]; zones = [tz => nama kota] untuk pilihan (bawaan: kota besar dunia). Waktu = Intl.DateTimeFormat(timeZone), jadi mengikuti aturan
// musim panas browser. Seluruh daftar dikirim ke $saveUrl (POST JSON {items}); kosong = pratinjau.
class WorldClock extends DevlibComponent
{
    public const ZONES = [
        'Asia/Jakarta' => 'Jakarta', 'Asia/Makassar' => 'Makassar', 'Asia/Jayapura' => 'Jayapura', 'Asia/Singapore' => 'Singapore', 'Asia/Kuala_Lumpur' => 'Kuala Lumpur', 'Asia/Bangkok' => 'Bangkok',
        'Asia/Manila' => 'Manila', 'Asia/Hong_Kong' => 'Hong Kong', 'Asia/Shanghai' => 'Shanghai', 'Asia/Taipei' => 'Taipei', 'Asia/Seoul' => 'Seoul', 'Asia/Tokyo' => 'Tokyo', 'Asia/Kolkata' => 'Mumbai',
        'Asia/Dubai' => 'Dubai', 'Asia/Riyadh' => 'Riyadh', 'Europe/Istanbul' => 'Istanbul', 'Europe/Moscow' => 'Moscow', 'Europe/London' => 'London', 'Europe/Paris' => 'Paris', 'Europe/Berlin' => 'Berlin',
        'Europe/Amsterdam' => 'Amsterdam', 'Europe/Madrid' => 'Madrid', 'Europe/Rome' => 'Rome', 'Africa/Cairo' => 'Cairo', 'Africa/Johannesburg' => 'Johannesburg', 'Africa/Lagos' => 'Lagos',
        'America/New_York' => 'New York', 'America/Chicago' => 'Chicago', 'America/Denver' => 'Denver', 'America/Los_Angeles' => 'Los Angeles', 'America/Mexico_City' => 'Mexico City',
        'America/Sao_Paulo' => 'Sao Paulo', 'America/Argentina/Buenos_Aires' => 'Buenos Aires', 'Australia/Perth' => 'Perth', 'Australia/Sydney' => 'Sydney', 'Pacific/Auckland' => 'Auckland', 'UTC' => 'UTC',
    ];

    public function __construct(
        public array $items = [],
        public array $zones = [],
        public string $saveUrl = '',
        public ?string $csrf = '',
    ) {
        $this->zones = $zones ?: self::ZONES;
    }

    public function tag(): string
    {
        return 'x-dl::world-clock';
    }

    public function source(): string
    {
        return $this->saveUrl ?: 'pratinjau (tanpa server)';
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class('dl-wclock') }} {!! $dl() !!} data-dl-wclock data-save="{{ $saveUrl }}" data-csrf="{{ $csrf }}" data-items="{{ json_encode(array_values($items), JSON_UNESCAPED_UNICODE) }}"
                data-l-delete="{{ \Devlib\Lang::text('Delete') }}" data-l-same="{{ \Devlib\Lang::text('Same time as your device') }}" data-l-ahead="{{ \Devlib\Lang::text('ahead') }}" data-l-behind="{{ \Devlib\Lang::text('behind') }}"
                data-l-failed="{{ \Devlib\Lang::text('Could not save. Try again.') }}">
                <div class="dl-wclock__cards" data-dl-wclock-cards></div>
                <p class="dl-wclock__empty" data-dl-wclock-empty>{{ \Devlib\Lang::text('No cities yet.') }}</p>
                <form class="dl-wclock__add" data-dl-wclock-add>
                    <select data-dl-wclock-zone aria-label="{{ \Devlib\Lang::text('City') }}" required>
                        <option value="">{{ \Devlib\Lang::text('Choose a city') }}</option>
                        @foreach ($zones as $tz => $city)<option value="{{ $tz }}">{{ $city }}</option>@endforeach
                    </select>
                    <button type="submit" class="dl-btn dl-btn--primary"><i class="fa-solid fa-plus" aria-hidden="true"></i> {{ \Devlib\Lang::text('Add city') }}</button>
                </form>
            </div>
            BLADE;
    }
}
