<?php

/*
 * REMARK
 * Tujuan       : Pembunyi alarm yang tak terlihat: tiap beberapa detik memeriksa daftar alarm aktif terhadap jam browser; saat jam dan hari cocok memainkan bunyi 'alarm' (Devlib.sound, bawaan mati) dan menampilkan toast. Alarm sekali jalan dimatikan setelah berbunyi.
 * Sumber hulu  : Komponen generik DevLib; daftar awal lewat prop, perubahan dari x-dl::alarm-list lewat localStorage[storageKey] (event storage), pematian alarm sekali jalan dikirim ke saveUrl.
 * Input        : items, saveUrl, csrf, storageKey.
 * Output       : <span hidden> + js/components/alarm.js.
 * Dipakai oleh : halaman induk aplikasi (shell Layer Menu) lewat x-dl::alarm-runner; hanya berjalan di jendela paling atas (bukan di iframe layar).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Pembunyi alarm. Taruh SATU di halaman induk yang selalu terbuka; alarm hanya berbunyi selama halaman itu terbuka (batas browser).
class AlarmRunner extends DevlibComponent
{
    public function __construct(
        public array $items = [],
        public string $saveUrl = '',
        public ?string $csrf = '',
        public string $storageKey = 'dl-alarms',
    ) {}

    public function tag(): string
    {
        return 'x-dl::alarm-runner';
    }

    public function source(): string
    {
        return count($this->items).' alarm';
    }

    public function render()
    {
        return <<<'BLADE'
            <span hidden {!! $dl() !!} data-dl-alarm-runner data-key="{{ $storageKey }}" data-save="{{ $saveUrl }}" data-csrf="{{ $csrf }}"
                data-items="{{ json_encode(array_values($items), JSON_UNESCAPED_UNICODE) }}" data-l-ring="{{ \Devlib\Lang::text('Alarm') }}"></span>
            BLADE;
    }
}
