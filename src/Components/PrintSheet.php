<?php

/*
 * REMARK
 * Tujuan       : Cetakan lembar berbagian (laporan cetak / formulir lapangan): kop perusahaan, judul + kode formulir, baris info, bagian bernomor berisi tabel / kotak pihak / catatan, tanda tangan, kaki. Melengkapi x-dl::print-doc (dokumen transaksi satu tabel).
 * Sumber hulu  : Layar pemanggil menyerahkan data mentah; kop perusahaan dari Devlib\PrintDoc\Document::companyOf() (satu sumber dengan semua cetakan).
 * Input        : title, code, info [teks], sections [['title' (kosong = tanpa judul), 'no', 'blocks' => [...]]], signatures [['role','name']], note, landscape.
 *                blocks: ['type' => 'table', 'heading', 'columns' => [['key','label','align' => left|center|right,'width' (mm),'mono']], 'rows' => [[key => teks | ['text','sub','rowspan','bold','class'] | false (sel tertutup rowspan)]], 'note', 'empty']
 *                        ['type' => 'parties', 'items' => [['label','lines' => [teks]]]]   ['type' => 'text', 'text']
 * Output       : <div.dl-psheet> satu lembar A4 (portrait atau landscape) yang tumbuh ke bawah dan terpotong per halaman saat dicetak; header tabel diulang.
 * Dipakai oleh : cetak Roster Shift v2 (tmerp), cetak Work Order v2 (tmerp); demo devlib.test > Cetak.
 * Status       : Aktif.
 */

namespace Devlib\Components;

use Devlib\PrintDoc\Document;

class PrintSheet extends DevlibComponent
{
    public function __construct(
        public string $title = '',
        public ?string $code = null,
        public array $info = [],
        public array $sections = [],
        public array $signatures = [],
        public ?string $note = null,
        public bool $landscape = false,
        public ?string $footer = null,
    ) {}

    public function tag(): string
    {
        return 'x-dl::print-sheet';
    }

    public function source(): string
    {
        return 'lembar cetak berbagian';
    }

    /** Kop perusahaan (nama, inisial, baris alamat, logo). */
    public function company(): array
    {
        return Document::companyOf();
    }

    /** Teks cetak: terjemahan English -> bahasa cetak, sama dengan semua cetakan. */
    public function t(?string $text): string
    {
        return Document::t($text);
    }

    public function render()
    {
        return view('devlib::components.print-sheet');
    }
}
