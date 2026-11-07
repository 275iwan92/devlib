<?php

/*
 * REMARK
 * Tujuan       : Pelacak kebiasaan pribadi (Change Habit): kartu per kebiasaan dengan tombol centang hari ini, deret hari beruntun (streak), kisi 14 hari terakhir yang bisa diklik untuk mengoreksi, hapus, dan form Tambah kebiasaan.
 * Sumber hulu  : Komponen generik DevLib; daftar masuk lewat prop dan disimpan lewat saveUrl (JSON {items}) milik aplikasi.
 * Input        : items, saveUrl, csrf.
 * Output       : Markup Blade inline (render() kelas ini) + js/components/habit-tracker.js + CSS .dl-habit.
 * Dipakai oleh : layar proyek lewat x-dl::habit-tracker (menu Tools > Change Habit).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Pelacak kebiasaan. items = [['id', 'name', 'log' => ['YYYY-MM-DD', ...] (hari yang dilakukan, tanggal perangkat pengguna)]]. Streak = hari berurutan sampai hari ini
// (bila hari ini belum dicentang, dihitung sampai kemarin). Tiap perubahan mengirim seluruh daftar ke $saveUrl (POST JSON {items}); kosong = pratinjau.
// Catatan lebih dari 400 hari dibuang di browser supaya daftar tetap kecil.
class HabitTracker extends DevlibComponent
{
    public function __construct(
        public array $items = [],
        public string $saveUrl = '',
        public ?string $csrf = '',
    ) {}

    public function tag(): string
    {
        return 'x-dl::habit-tracker';
    }

    public function source(): string
    {
        return $this->saveUrl ?: 'pratinjau (tanpa server)';
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class('dl-habit') }} {!! $dl() !!} data-dl-habit data-save="{{ $saveUrl }}" data-csrf="{{ $csrf }}" data-items="{{ json_encode(array_values($items), JSON_UNESCAPED_UNICODE) }}"
                data-l-done="{{ \Devlib\Lang::text('Done today') }}" data-l-undo="{{ \Devlib\Lang::text('Mark as done') }}" data-l-streak="{{ \Devlib\Lang::text('days in a row') }}" data-l-delete="{{ \Devlib\Lang::text('Delete') }}"
                data-l-failed="{{ \Devlib\Lang::text('Could not save. Try again.') }}">
                <ul class="dl-habit__list" data-dl-habit-list></ul>
                <p class="dl-habit__empty" data-dl-habit-empty>{{ \Devlib\Lang::text('No habits yet.') }}</p>
                <form class="dl-habit__add" data-dl-habit-add>
                    <input type="text" maxlength="60" required data-dl-habit-name placeholder="{{ \Devlib\Lang::text('New habit, for example: walk 20 minutes') }}" aria-label="{{ \Devlib\Lang::text('Habit') }}">
                    <button type="submit" class="dl-btn dl-btn--primary"><i class="fa-solid fa-plus" aria-hidden="true"></i> {{ \Devlib\Lang::text('Add habit') }}</button>
                </form>
            </div>
            BLADE;
    }
}
