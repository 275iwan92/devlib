<?php

/*
 * REMARK
 * Tujuan       : Struktur organisasi (Organization-Structure-Mockup + org chart aplikasi): pohon bergaris, kartu jabatan.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : nodes, editable, title, source, departments.
 * Output       : Markup Blade inline (render() kelas ini).
 * Dipakai oleh : org-chart (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Struktur organisasi (Organization-Structure-Mockup + org chart aplikasi): pohon bergaris, kartu jabatan
// berisi satu/lebih pemegang (kosong = "Lowong"). Drag pegangan kartu ke kartu lain = pindah atasan
// (beserta bawahannya; tidak boleh ke bawahannya sendiri), Undo, Simpan (dl:orgchart-save), Unduh HTML,
// cari pegawai (sorot + buka cabang + gulir), lipat/buka cabang, zoom, tombol Level 1/2/3/All, geser (pan) dengan drag, minimap, Print.
// Kartu berwarna per departemen (hue dari nama); lowong tanpa bawahan di satu induk digabung jadi "N Lowong" (hanya mode baca).
// nodes: [['id', 'parent' => id|null, 'title' => jabatan, 'dept', 'people' => [nama | ['name','photo','locked'=>bool]]]]; locked = kartu gembok (nama sudah disamarkan oleh server).
// departments: daftar nama departemen -> chip berwarna; klik chip menyorot kartu departemen itu (bersama pencarian, tanpa mengubah garis struktur).
class OrgChart extends DevlibComponent
{
    public function __construct(
        public array $nodes = [],
        public bool $editable = true,
        public string $title = 'Organization Structure',
        public ?string $source = null,
        public array $departments = [],
    ) {}

    public function tag(): string
    {
        return 'x-dl::org-chart';
    }

    public function source(): string
    {
        return $this->source ?? 'struktur jabatan';
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class('dl-org') }} {!! $dl() !!} data-dl-org data-nodes="{{ json_encode($nodes) }}" data-editable="{{ $editable ? 1 : 0 }}" data-title="{{ $title }}">
                <div class="dl-org__bar">
                    @if ($editable)
                        <x-dl::button variant="secondary" size="sm" icon="undo" label="Undo" data-o-undo />
                        <x-dl::button preset="save" size="sm" data-o-save />
                    @endif
                    <x-dl::button variant="secondary" size="sm" icon="download" label="Download HTML" data-o-html />
                    <x-dl::button variant="secondary" size="sm" icon="print" label="Print" data-o-print />
                    <span class="dl-org__zoom"><x-dl::icon-button icon="fa-magnifying-glass-minus" title="Zoom out" data-o-zoom="-1" /><b data-o-zoomval>100%</b><x-dl::icon-button icon="fa-magnifying-glass-plus" title="Zoom in" data-o-zoom="1" /><x-dl::button variant="ghost" size="sm" label="Fit" data-o-zoom="0" /></span>
                    <span class="dl-org__zoom"><x-dl::button variant="ghost" size="sm" label="Level 1" data-o-level="1" /><x-dl::button variant="ghost" size="sm" label="Level 2" data-o-level="2" /><x-dl::button variant="ghost" size="sm" label="Level 3" data-o-level="3" /><x-dl::button variant="ghost" size="sm" label="All" data-o-level="0" /></span>
                    @if ($editable)<span class="dl-org__tip"><i class="fa-solid fa-circle-info"></i> {{ \Devlib\Lang::text('Drag the dotted icon on the left of a card onto another superior card to move its subordinates.') }}</span>@endif
                    <span class="dl-org__spacer"></span>
                    <div class="dl-grid__search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" placeholder="{{ \Devlib\Lang::text('Search employee / position...') }}" data-o-search aria-label="{{ \Devlib\Lang::text('Search employee') }}"></div>
                </div>
                @if ($departments)<div class="dl-org__depts" role="group" aria-label="{{ \Devlib\Lang::text('Department') }}"><button type="button" class="dl-org__chip" data-o-dept="">{{ \Devlib\Lang::text('All departments') }}</button>@foreach ($departments as $d)<button type="button" class="dl-org__chip" data-o-dept="{{ $d }}">{{ $d }}</button>@endforeach</div>@endif
                <div class="dl-org__stage"><div class="dl-org__canvas" data-o-canvas><div class="dl-org__tree" data-o-tree></div></div><div class="dl-org__mini" data-o-mini hidden aria-hidden="true"></div></div>
            </div>
            BLADE;
    }
}
