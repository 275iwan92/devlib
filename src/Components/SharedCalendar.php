<?php

/*
 * REMARK
 * Tujuan       : Kalender bersama: x-dl::calendar yang acaranya bisa mengundang peserta lewat @nama, dijawab Accept / Tentative / Decline, dilihat rekan sesuai izin, dengan ruang rapat dan pengingat; tiap perubahan dikirim ke server per acara.
 * Sumber hulu  : x-dl::calendar (Devlib); acara, daftar orang, daftar ruang, dan alamat server masuk lewat prop (milik aplikasi).
 * Input        : events, people, rooms, display, saveUrl, deleteUrl, rsvpUrl, teamUrl, openUrl, csrf.
 * Output       : Markup x-dl::calendar (dengan people / rooms) di dalam pembungkus + js/components/shared-calendar.js (bilah "Show calendar of" dibuat JS bila teamUrl ada).
 * Dipakai oleh : layar proyek yang membagikan kalender (menu Tools > Calendar & Task di tmerp).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Kalender bersama. Server memutuskan siapa melihat apa; objek hanya menampilkan dan mengirim.
// events: bentuk x-dl::calendar + people [['id', 'name', 'rsvp']], room (id), roomName, remind (menit), vis (PRIVATE|BUSY|DETAIL);
//   ro => true (baca-saja, mis. task atau waktu sibuk rekan; kind 'task' + openUrl = klik 2x membuka sumbernya),
//   invite => ['rsvp' => INVITED|ACCEPTED|TENTATIVE, 'by' => nama pengundang] (acara orang lain yang saya diundang).
// Alamat: saveUrl (POST {event} -> {event, warnings[]}; id 'n..' = baru), deleteUrl (DELETE, ':id' diganti id), rsvpUrl (POST {rsvp}, ':id'), teamUrl (GET ?users=1,2 -> {events[]}).
class SharedCalendar extends DevlibComponent
{
    public function __construct(
        public array $events = [],
        public array $people = [],
        public array $rooms = [],
        public string $display = 'week',
        public string $saveUrl = '',
        public string $deleteUrl = '',
        public string $rsvpUrl = '',
        public string $teamUrl = '',
        public string $openUrl = '',
        public ?string $csrf = '',
    ) {}

    public function tag(): string
    {
        return 'x-dl::shared-calendar';
    }

    public function source(): string
    {
        return $this->saveUrl ?: 'pratinjau (tanpa server)';
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class('dl-shared') }} {!! $dl() !!} data-dl-shared data-save="{{ $saveUrl }}" data-delete="{{ $deleteUrl }}" data-rsvp="{{ $rsvpUrl }}" data-team="{{ $teamUrl }}" data-open="{{ $openUrl }}" data-csrf="{{ $csrf }}"
                data-l-failed="{{ \Devlib\Lang::text('Could not save. Try again.') }}">
                <x-dl::calendar :events="array_values($events)" :display="$display" :people="array_values($people)" :rooms="$rooms ?: null" />
            </div>
            BLADE;
    }
}
