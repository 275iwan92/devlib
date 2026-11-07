<?php

/*
 * REMARK
 * Tujuan       : Janji / agenda pribadi: x-dl::calendar (Day/Week/Month, buat, ubah, pindah, hapus) yang daftar acaranya disimpan otomatis ke server tiap perubahan.
 * Sumber hulu  : x-dl::calendar (Devlib); acara masuk lewat prop dan disimpan lewat saveUrl (JSON {items}) milik aplikasi.
 * Input        : items, display, saveUrl, csrf.
 * Output       : Markup x-dl::calendar di dalam pembungkus + js/components/appointments.js.
 * Dipakai oleh : layar proyek lewat x-dl::appointments (menu Tools > Calendar Appointment).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Janji pribadi. items = [['id', 'title', 'start' => 'YYYY-MM-DD HH:MM' | 'YYYY-MM-DD', 'end', 'allDay' => bool, 'tone', 'desc']] (bentuk acara x-dl::calendar).
// Mengikuti event dl:calendar (create | update | move | delete) untuk menjaga daftar sendiri, lalu mengirim seluruh daftar ke $saveUrl (POST JSON {items}) 300 ms
// setelah perubahan terakhir; kosong = pratinjau. Pengingat / bunyi belum ada (acara hanya tampil di kalender).
class Appointments extends DevlibComponent
{
    public function __construct(
        public array $items = [],
        public string $display = 'week',
        public string $saveUrl = '',
        public ?string $csrf = '',
    ) {}

    public function tag(): string
    {
        return 'x-dl::appointments';
    }

    public function source(): string
    {
        return $this->saveUrl ?: 'pratinjau (tanpa server)';
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class('dl-appt') }} {!! $dl() !!} data-dl-appt data-save="{{ $saveUrl }}" data-csrf="{{ $csrf }}" data-items="{{ json_encode(array_values($items), JSON_UNESCAPED_UNICODE) }}"
                data-l-failed="{{ \Devlib\Lang::text('Could not save. Try again.') }}">
                <x-dl::calendar :events="array_values($items)" :display="$display" />
            </div>
            BLADE;
    }
}
