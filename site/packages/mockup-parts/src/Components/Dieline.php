<?php

namespace MockupParts\Components;

use Devlib\Components\DevlibComponent;

// Dieline kemasan hidup (InqProd Engine): pilih tipe -> ukuran pasaran / isi dimensi -> SVG langsung berubah, garis ukuran seperti gambar
// teknik (rantai panel di atas/kiri, total di bawah/kanan, kode istilah internasional L, W, H, GF, TF, DF, BF, mm/cm), judul spesifikasi,
// panjang total Cutting / Creasing / Perforation (dasar meter lari pisau) dan jendela kecil 3D (kotak / silinder / lembaran).
// Versi lama (v1 tanpa ukuran, v2 tanpa judul / 3D) dikubur: tag history/dieline-v1, history/dieline-v2.
// Render = DevlibDieline (resources/assets/devlib-dieline.js), salinan UTUH dari tmerp (zona beku: esensi tidak diubah).
// types: [kode => ['label', 'category', 'spec' => DSL dieline_spec, 'params' => [key => ['label','unit','default','min','max']],
//          'sizes' => [['label', 'dims' => [key => nilai]]]]]  (di aplikasi pemakai: prod_type + prod_type_param + prod_type_size(_val))
// Terkirim: name[type], name[dims][key], name[flat_w], name[flat_h], name[flat_area], name[flat_manual]. Event dl:dieline.
class Dieline extends DevlibComponent
{
    // [label internasional, kode di garis ukuran] per param_key; label DB dipakai bila key tidak ada.
    public const TERMS = [
        'product_length' => ['Length', 'L'], 'product_width' => ['Width', 'W'], 'product_height' => ['Height', 'H'],
        'diameter' => ['Diameter', 'Ø'], 'lid_height' => ['Lid Height', 'LH'], 'glue_flap' => ['Glue Flap', 'GF'],
        'top_tuck_flap' => ['Top Tuck Flap', 'TF'], 'dust_flap' => ['Dust Flap', 'DF'], 'bottom_tuck_flap' => ['Bottom Tuck Flap', 'BF'],
        'overflow_margin' => ['Bleed', 'BL'],
    ];

    public function __construct(
        public array $types = [],
        public ?string $type = null,
        public array $dims = [],
        public string $name = 'dieline',
        public bool $picker = true,
        public string $height = '460px',
        public ?string $source = null,
        public bool $dimensions = true,
        public string $unit = 'mm',
    ) {}

    public function tag(): string
    {
        return 'x-mp::dieline';
    }

    public function source(): string
    {
        return $this->source ?? 'master.prod_type (dieline_spec) + prod_type_param + prod_type_size';
    }

    public function terms(): array
    {
        return static::TERMS;
    }

    public function render()
    {
        return <<<'BLADE'
            @once<script src="{{ \MockupParts\MockupPartsServiceProvider::asset('devlib-dieline.js') }}"></script>@endonce
            <div {{ $attributes->class('dl-dieline') }} {!! $dl() !!} data-dl-dieline-x data-types="{{ json_encode($types) }}" data-type="{{ $type ?? array_key_first($types) }}" data-dims="{{ json_encode((object) $dims) }}" data-name="{{ $name }}" data-terms="{{ json_encode($terms()) }}" data-unit="{{ $unit }}" style="--dl-dl-h:{{ $height }}">
                @if ($picker)
                    <x-dl::field.select :name="$name.'[type]'" label="Product Type" :options="collect($types)->map(fn ($t) => $t['label'])->all()" :value="$type ?? array_key_first($types)" />
                @else
                    <input type="hidden" name="{{ $name }}[type]" value="{{ $type }}">
                @endif
                <div class="dl-dieline__stage">
                    <div class="dl-dieline__title" data-d-title></div>
                    <div class="dl-dieline__tools">
                        <span class="dl-dieline__legend"><i class="is-cut"></i>{{ \Devlib\Lang::text('Cutting') }} <i class="is-crease"></i>{{ \Devlib\Lang::text('Creasing') }} <i class="is-bleed"></i>{{ \Devlib\Lang::text('Bleed') }} <i class="is-hole"></i>{{ \Devlib\Lang::text('Hole') }}</span>
                        <span class="dl-print__spacer"></span>
                        {{-- Cetak 2 sisi: tampil bila ada artwork sisi dalam (data-artwork-inner / setArtworkInner). --}}
                        <div class="dl-segment" data-d-side hidden><button type="button" class="dl-segment__opt is-active" data-value="out">{{ \Devlib\Lang::text('Outer Side') }}</button><button type="button" class="dl-segment__opt" data-value="in">{{ \Devlib\Lang::text('Inner Side') }}</button></div>
                        <div class="dl-segment dl-dieline__unit" data-d-unit><button type="button" class="dl-segment__opt" data-value="mm">mm</button><button type="button" class="dl-segment__opt" data-value="cm">cm</button></div>
                        <x-dl::icon-button icon="fa-magnifying-glass-minus" title="Zoom out" data-d-zoom="-1" /><b data-d-zoomval>100%</b><x-dl::icon-button icon="fa-magnifying-glass-plus" title="Zoom in" data-d-zoom="1" />
                        <x-dl::button variant="secondary" size="sm" icon="download" label="SVG" data-d-svg />
                    </div>
                    {{-- Opsi tampilan (hanya gambar; tidak mengubah geometri DevlibDieline maupun panjang pisau). --}}
                    <div class="dl-dieline__opts">
                        <label class="dl-choice"><input type="checkbox" data-d-opt="artwork" checked> {{ \Devlib\Lang::text('Artwork') }}</label>
                        <label class="dl-choice"><input type="checkbox" data-d-opt="overflow" checked> {{ \Devlib\Lang::text('Overflow') }}</label>
                        <label class="dl-choice"><input type="checkbox" data-d-opt="glue" checked> {{ \Devlib\Lang::text('Glue margin') }}</label>
                        <label class="dl-choice"><input type="checkbox" data-d-opt="direction" checked> {{ \Devlib\Lang::text('Direction') }} <x-dl::tooltip text="Paper grain direction — parallel to the sheet length."><i class="fa-regular fa-circle-question dl-dieline__q"></i></x-dl::tooltip></label>
                        <label class="dl-choice"><input type="checkbox" data-d-opt="cross" checked> {{ \Devlib\Lang::text('Cross') }} <x-dl::tooltip text="Register marks (crosshair) at the bleed corners to align CMYK printing."><i class="fa-regular fa-circle-question dl-dieline__q"></i></x-dl::tooltip></label>
                        <label class="dl-choice"><input type="checkbox" data-d-opt="lock"> {{ \Devlib\Lang::text('Lock tab') }} <x-dl::tooltip text="Friction lock notch at the end of the tuck flap fold — indicative."><i class="fa-regular fa-circle-question dl-dieline__q"></i></x-dl::tooltip></label>
                        <label class="dl-choice"><input type="checkbox" data-d-dims-on @checked($dimensions)> {{ \Devlib\Lang::text('Dimension lines') }}</label>
                        <label class="dl-choice" data-d-mirror-wrap hidden><input type="checkbox" data-d-opt="mirror"> {{ \Devlib\Lang::text('Mirror') }} <x-dl::tooltip text="Tick this when the inner side file was made on a mirrored dieline (seen from the inside) — the image is flipped so that it lands exactly on its panels."><i class="fa-regular fa-circle-question dl-dieline__q"></i></x-dl::tooltip></label>
                    </div>
                    {{-- Jendela 3D di samping kanvas, tinggi sama (user 2026-10-01); kotak bisa diputar dengan drag. --}}
                    <div @class(['dl-dieline__view', 'dl-dieline__view--3d'])>
                        <div class="dl-dieline__canvas"><div class="dl-dieline__preview" data-d-preview></div></div>
                        <div class="dl-dieline__3d" data-d-3d></div>
                    </div>
                    <div class="dl-dieline__lens" data-d-lens></div>
                    <div class="dl-dieline__info" data-d-info></div>
                    <div class="dl-dieline__terms" data-d-terms></div>
                </div>
                {{-- Dimensi di BAWAH gambar (user 2026-10-01), 2 baris berkelompok: ukuran produk | Flat Size, lalu flap & allowance. --}}
                <div class="dl-dieline__side">
                    <div class="dl-dieline__dimwrap">
                        <div class="dl-dieline__cap">{{ \Devlib\Lang::text('Product Size') }}</div>
                        <div class="dl-dieline__dims" data-d-dims></div>
                    </div>
                    <div class="dl-dieline__flat">
                        <div class="dl-dieline__cap">{{ \Devlib\Lang::text('Flat Size (Blank)') }} <label class="dl-choice"><input type="checkbox" name="{{ $name }}[flat_manual]" value="1" data-d-manual> {{ \Devlib\Lang::text('Manual') }}</label></div>
                        <div class="dl-dieline__flatrow">
                            <label>W <input type="text" inputmode="decimal" name="{{ $name }}[flat_w]" data-d-fw readonly></label>
                            <span>×</span>
                            <label>H <input type="text" inputmode="decimal" name="{{ $name }}[flat_h]" data-d-fh readonly></label>
                            <label>{{ \Devlib\Lang::text('Area') }} <input type="text" name="{{ $name }}[flat_area]" data-d-fa readonly></label>
                        </div>
                    </div>
                    <div class="dl-dieline__dimwrap" data-d-dims2-wrap>
                        <div class="dl-dieline__cap">{{ \Devlib\Lang::text('Flaps & Allowances') }}</div>
                        <div class="dl-dieline__dims" data-d-dims2></div>
                    </div>
                    {{-- Lubang / jendela di panel depan (user 2026-10-01). Ikut tersimpan di dims (hole_type, hole_w, hole_h) hanya bila dipakai:
                         field dims dimatikan saat None, pilihan tipe sendiri (hole_ui) bukan bagian dims. --}}
                    <div class="dl-dieline__dimwrap">
                        <div class="dl-dieline__cap">{{ \Devlib\Lang::text('Hole (front panel)') }}</div>
                        <div class="dl-dieline__dims dl-dieline__dims--3">
                            <x-dl::field.select :name="$name.'[hole_ui]'" label="Hole Type" :options="['NONE' => \Devlib\Lang::text('None'), 'ROUND' => \Devlib\Lang::text('Round'), 'RECT' => \Devlib\Lang::text('Rectangle')]" :value="$dims['hole_type'] ?? 'NONE'" />
                            <input type="hidden" name="{{ $name }}[dims][hole_type]" data-d-hole="type" disabled>
                            <label class="dl-dieline__dim"><span>{{ \Devlib\Lang::text('Hole Width / Ø') }}</span><span class="dl-field__box"><input class="dl-input" type="text" inputmode="decimal" name="{{ $name }}[dims][hole_w]" data-d-hole="w" disabled><em>mm</em></span></label>
                            <label class="dl-dieline__dim"><span>{{ \Devlib\Lang::text('Hole Height') }}</span><span class="dl-field__box"><input class="dl-input" type="text" inputmode="decimal" name="{{ $name }}[dims][hole_h]" data-d-hole="h" disabled><em>mm</em></span></label>
                        </div>
                    </div>
                </div>
            </div>
            BLADE;
    }
}
