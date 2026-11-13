<?php

/*
 * REMARK
 * Tujuan       : Papan slot satu layar (seperti kartu pemain ke petak): kiri kolam orang yang BELUM ditempatkan, kanan matriks baris x kolom (mis. shift x unit kerja/mesin); orang dipindah dengan seret, centang banyak lalu klik petak, atau klik dua kali untuk mengembalikan.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : name, rows [[key, label, sub, need]], cols [[key, label, sub]], people [[id, name, short, code, group, flag]], placed [id => "rowKey|colKey"], locked, poolTitle, rowTitle.
 * Output       : Input hidden name[<id>] = "rowKey|colKey" untuk setiap orang yang sudah di petak; perubahan menandai form belum disimpan dan Undo form mengembalikan isi awal (core.js fillForm -> el.dlSlots.reset()); event dl:slot-change. Gaya .dl-slots di devlib.css.
 * Dipakai oleh : (proyek) layar Shift Roster PPIC; demo katalog slot-board.
 * Status       : Aktif (DevLib). Pasangan JS: components/slot-board.js.
 */

namespace Devlib\Components;

use Devlib\Lang;

// Papan slot. rows = baris matriks (mis. shift) ['key', 'label', 'sub', 'need' => orang yang dibutuhkan per petak (0 = tanpa hitungan)];
// cols = kolom (mis. unit kerja / mesin) ['key', 'label', 'sub' => baris kecil, mis. target pcs/jam];
// people = semua orang yang bisa ditempatkan ['id', 'name', 'short' => nama pendek di petak, 'code', 'group' => untuk chip saring, 'flag' => bool tanda khusus (mis. borongan)];
// placed = yang sudah di petak [id => "rowKey|colKey"]. Yang tidak ada di placed = di kolam. locked = papan baca-saja (terkunci).
// Seret satu orang ke petak (atau ke kolam = batal). Centang beberapa orang (klik bulatan), lalu klik petak = semua masuk sekaligus.
// Server WAJIB memvalidasi ulang (orang, petak, batas, kunci): objek ini hanya membantu memilih. Layar sentuh memakai centang + klik petak.
class SlotBoard extends DevlibComponent
{
    public function __construct(
        public string $name = 'slots',
        public array $rows = [],
        public array $cols = [],
        public array $people = [],
        public array $placed = [],
        public bool $locked = false,
        public string $poolTitle = 'Not scheduled',
        public string $rowTitle = 'Shift',
    ) {}

    public function tag(): string
    {
        return 'x-dl::slot-board name='.$this->name;
    }

    public function config(): array
    {
        return [
            'name' => $this->name, 'locked' => $this->locked, 'rowTitle' => Lang::text($this->rowTitle),
            'rows' => array_values($this->rows), 'cols' => array_values($this->cols),
            'people' => array_values($this->people), 'placed' => (object) $this->placed,
            'text' => [
                'search' => Lang::text('Search name...'), 'all' => Lang::text('All'), 'enough' => Lang::text('enough'), 'short' => Lang::text('short :n'),
                'people' => Lang::text(':n people'), 'drop' => Lang::text('drop here'), 'pool_empty' => Lang::text('Everyone is placed'),
                'selected' => Lang::text(':n selected'), 'hint' => Lang::text('Drag a person to a slot. Tick several, then click a slot to place them all. Drag back here to unplace.'),
                'locked' => Lang::text('Locked: unlock to change'),
            ],
        ];
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class('dl-slots')->merge(['data-locked' => $locked ? '1' : '0']) }} {!! $dl() !!} data-dl-slots data-config="{{ json_encode($config()) }}">
                <aside class="dl-slots__pool" data-s-pool>
                    <header class="dl-slots__head"><b>{{ \Devlib\Lang::text($poolTitle) }}</b><span class="dl-slots__pill" data-s-count="pool"></span></header>
                    <label class="dl-slots__search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" data-s-search placeholder="{{ \Devlib\Lang::text('Search name...') }}" aria-label="{{ \Devlib\Lang::text('Search name...') }}"></label>
                    <div class="dl-slots__chips" data-s-chips></div>
                    <div class="dl-slots__tokens" data-s-tokens></div>
                    <p class="dl-slots__hint" data-s-hint></p>
                </aside>
                <div class="dl-slots__matrix" data-s-matrix></div>
            </div>
            BLADE;
    }
}
