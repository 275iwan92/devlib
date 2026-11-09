<?php

/*
 * REMARK
 * Tujuan       : Pembunyi alarm tugas yang tak terlihat: tiap 5 detik mencocokkan jam browser dengan due date tugas Eisenhower yang beralarm dan belum selesai; due tiba (paling lama 12 jam lalu) = bunyi 'notif' (Devlib.sound, mengikuti Setup > Sound) + toast kecil, sekali per tugas dan due.
 * Sumber hulu  : Komponen generik DevLib; daftar awal lewat prop, perubahan dari x-dl::eisenhower lewat localStorage[storageKey] (event storage); catatan sudah berbunyi di localStorage[storageKey-rung].
 * Input        : items, storageKey, label.
 * Output       : <span hidden> + js/components/eisenhower.js.
 * Dipakai oleh : halaman induk aplikasi (shell Layer Menu / Home); hanya berjalan di jendela paling atas (bukan di iframe layar).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Pembunyi alarm tugas. Taruh SATU di halaman induk yang selalu terbuka; bunyi hanya selama halaman itu terbuka (batas browser).
class TaskAlarmRunner extends DevlibComponent
{
    public function __construct(
        public array $items = [], // tugas x-dl::eisenhower: ['id', 'text', 'done', 'due' => 'Y-m-d H:i', 'alarm' => bool]
        public string $storageKey = 'dl-tasks',
        public string $label = 'Task due', // judul toast saat bunyi (mis. 'Reminder' untuk pengingat acara)
    ) {}

    public function tag(): string
    {
        return 'x-dl::task-alarm-runner';
    }

    public function source(): string
    {
        return count($this->items).' task';
    }

    public function render()
    {
        return <<<'BLADE'
            <span hidden {!! $dl() !!} data-dl-task-alarm data-key="{{ $storageKey }}" data-items="{{ json_encode(array_values($items), JSON_UNESCAPED_UNICODE) }}" data-l-ring="{{ \Devlib\Lang::text($label) }}"></span>
            BLADE;
    }
}
