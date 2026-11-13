<?php

/*
 * REMARK
 * Tujuan       : Dua kotak kiri-kanan (transfer / dual list): kiri = yang BELUM dijadwalkan, kanan = yang SUDAH; filter di kedua kotak, pilih banyak, tombol tengah atau drag and drop; baris kanan membawa pilihan tambahan (mis. mesin dan job).
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : name, items [id => label], rows [[id, <key> => nilai]], fields [[key, label, options, sticky, default, required]], idKey, leftTitle, rightTitle, max.
 * Output       : Input hidden name[i][<idKey>] dan name[i][<key>] per baris kanan, ikut terkirim bersama form; perubahan menandai form belum disimpan dan Undo form mengembalikan isi awal (core.js fillForm -> el.dlTransfer.reset()). Gaya .dl-transfer di devlib.css.
 * Dipakai oleh : (proyek) layar Shift Crew; demo katalog transfer.
 * Status       : Aktif (DevLib). Pasangan JS: components/transfer.js.
 */

namespace Devlib\Components;

use Devlib\Lang;

// Dua kotak kiri-kanan. items: semua yang bisa dipilih [id => label]; rows: yang sudah di kanan, tiap baris ['id' => id, '<key>' => nilai, ...].
// fields: pilihan per baris kanan, tiap field ['key', 'label', 'options' => [nilai => label], 'required' => bool, 'sticky' => bool (orang yang
// dipindah berikutnya memakai nilai baris terakhir, mis. mesin yang sama), 'default' => nilai awal]. Yang di kiri = items yang id-nya tidak di rows.
// Dual list: filter di kedua kotak, klik = pilih (Shift+klik = rentang, dua klik = pindah satu), tombol tengah (terpilih / semua yang tampil, kanan / kiri) = jalan non-drag yang selalu ada;
// drag memakai HTML5 drag and drop (mouse; baris terpilih ikut semua); layar sentuh memakai tombol tengah.
// Server WAJIB memvalidasi ulang (batas, bentrok, nilai wajib): objek ini hanya membantu memilih.
class Transfer extends DevlibComponent
{
    public function __construct(
        public string $name = 'rows',
        public array $items = [],
        public array $rows = [],
        public array $fields = [],
        public string $idKey = 'id',
        public string $leftTitle = 'Not scheduled',
        public string $rightTitle = 'Scheduled',
        public int $max = 0,
    ) {}

    public function tag(): string
    {
        return 'x-dl::transfer name='.$this->name;
    }

    public function config(): array
    {
        return [
            'name' => $this->name, 'idKey' => $this->idKey, 'max' => $this->max,
            'items' => (object) $this->items, 'rows' => array_values($this->rows),
            'fields' => array_map(fn ($f) => ['key' => $f['key'], 'label' => Lang::text($f['label'] ?? $f['key']), 'options' => (object) ($f['options'] ?? []),
                'required' => (bool) ($f['required'] ?? false), 'sticky' => (bool) ($f['sticky'] ?? false), 'default' => $f['default'] ?? ''], $this->fields),
            'text' => [
                'empty_left' => Lang::text('Everyone is scheduled'), 'empty_right' => Lang::text('Nobody yet: select people on the left and move them here'),
                'selected' => Lang::text(':n selected'),
            ],
        ];
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class('dl-transfer') }} {!! $dl() !!} data-dl-transfer data-config="{{ json_encode($config()) }}">
                <section class="dl-transfer__box" data-t-box="left">
                    <header class="dl-transfer__head"><b class="dl-transfer__title">{{ \Devlib\Lang::text($leftTitle) }}</b><span class="dl-transfer__pill" data-t-count="left"></span><span class="dl-transfer__sel" data-t-sel="left" hidden></span></header>
                    <label class="dl-transfer__filter"><i class="fa-solid fa-magnifying-glass"></i><input type="search" data-t-filter="left" placeholder="{{ \Devlib\Lang::text('Filter...') }}" aria-label="{{ \Devlib\Lang::text('Filter...') }}"></label>
                    <div class="dl-transfer__list" data-t-list="left" role="listbox" aria-multiselectable="true"></div>
                    <div class="dl-transfer__drop" aria-hidden="true"><i class="fa-solid fa-arrow-down-long"></i>{{ \Devlib\Lang::text('Drop here') }}</div>
                </section>
                <div class="dl-transfer__mid" role="group" aria-label="{{ \Devlib\Lang::text('Move') }}">
                    <button type="button" class="dl-transfer__act" data-t-act="all-right" title="{{ \Devlib\Lang::text('Move all shown to the right') }}" aria-label="{{ \Devlib\Lang::text('Move all shown to the right') }}"><i class="fa-solid fa-angles-right"></i></button>
                    <button type="button" class="dl-transfer__act" data-t-act="sel-right" title="{{ \Devlib\Lang::text('Move selected to the right') }}" aria-label="{{ \Devlib\Lang::text('Move selected to the right') }}"><i class="fa-solid fa-angle-right"></i></button>
                    <button type="button" class="dl-transfer__act" data-t-act="sel-left" title="{{ \Devlib\Lang::text('Move selected to the left') }}" aria-label="{{ \Devlib\Lang::text('Move selected to the left') }}"><i class="fa-solid fa-angle-left"></i></button>
                    <button type="button" class="dl-transfer__act" data-t-act="all-left" title="{{ \Devlib\Lang::text('Move all shown to the left') }}" aria-label="{{ \Devlib\Lang::text('Move all shown to the left') }}"><i class="fa-solid fa-angles-left"></i></button>
                </div>
                <section class="dl-transfer__box dl-transfer__box--right" data-t-box="right">
                    <header class="dl-transfer__head"><b class="dl-transfer__title">{{ \Devlib\Lang::text($rightTitle) }}</b><span class="dl-transfer__pill dl-transfer__pill--on" data-t-count="right"></span><span class="dl-transfer__sel" data-t-sel="right" hidden></span></header>
                    <label class="dl-transfer__filter"><i class="fa-solid fa-magnifying-glass"></i><input type="search" data-t-filter="right" placeholder="{{ \Devlib\Lang::text('Filter...') }}" aria-label="{{ \Devlib\Lang::text('Filter...') }}"></label>
                    <div class="dl-transfer__list" data-t-list="right" role="listbox" aria-multiselectable="true"></div>
                    <div class="dl-transfer__drop" aria-hidden="true"><i class="fa-solid fa-arrow-down-long"></i>{{ \Devlib\Lang::text('Drop here') }}</div>
                </section>
            </div>
            BLADE;
    }
}
