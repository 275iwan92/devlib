<?php

/*
 * REMARK
 * Tujuan       : Dinding catatan tempel pribadi: kartu berwarna yang diedit langsung, pilih warna, hapus, tombol Tambah catatan; tersimpan otomatis.
 * Sumber hulu  : Komponen generik DevLib; daftar masuk lewat prop dan disimpan lewat saveUrl (JSON {items}) milik aplikasi.
 * Input        : items, saveUrl, csrf.
 * Output       : Markup Blade inline (render() kelas ini) + js/components/sticky-notes.js + CSS .dl-notes.
 * Dipakai oleh : layar proyek lewat x-dl::sticky-notes (menu Tools > Sticky Notes).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Catatan tempel. items = [['id', 'text' (max 1000), 'color' => yellow|green|blue|pink|orange]]. Teks diketik di <textarea> (tidak pernah HTML); simpan 600 ms setelah
// berhenti mengetik atau saat fokus pergi, seluruh daftar ke $saveUrl (POST JSON {items}); kosong = pratinjau.
class StickyNotes extends DevlibComponent
{
    public const COLORS = ['yellow', 'green', 'blue', 'pink', 'orange'];

    public function __construct(
        public array $items = [],
        public string $saveUrl = '',
        public ?string $csrf = '',
    ) {}

    public function tag(): string
    {
        return 'x-dl::sticky-notes';
    }

    public function source(): string
    {
        return $this->saveUrl ?: 'pratinjau (tanpa server)';
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class('dl-notes') }} {!! $dl() !!} data-dl-notes data-save="{{ $saveUrl }}" data-csrf="{{ $csrf }}" data-items="{{ json_encode(array_values($items), JSON_UNESCAPED_UNICODE) }}"
                data-colors="{{ json_encode(\Devlib\Components\StickyNotes::COLORS) }}" data-l-color="{{ \Devlib\Lang::text('Color') }}" data-l-delete="{{ \Devlib\Lang::text('Delete') }}" data-l-note="{{ \Devlib\Lang::text('Note') }}"
                data-l-failed="{{ \Devlib\Lang::text('Could not save. Try again.') }}">
                <div class="dl-notes__bar"><button type="button" class="dl-btn dl-btn--primary" data-dl-notes-add><i class="fa-solid fa-plus" aria-hidden="true"></i> {{ \Devlib\Lang::text('Add note') }}</button></div>
                <div class="dl-notes__wall" data-dl-notes-wall></div>
                <p class="dl-notes__empty" data-dl-notes-empty>{{ \Devlib\Lang::text('No notes yet.') }}</p>
            </div>
            BLADE;
    }
}
