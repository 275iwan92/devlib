<?php

/*
 * REMARK
 * Tujuan       : Slideshow gambar dalam jendela modal: satu gambar besar + keterangan, tombol Sebelumnya / Berikutnya, titik penunjuk, panah papan ketik. Untuk panduan langkah demi langkah berupa tangkapan layar.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop (alamat gambar di public/ proyek), tanpa tabel/model proyek.
 * Input        : id, title, items [['src','caption','alt']], size, open.
 * Output       : Markup Blade inline (render() kelas ini) + perilaku resources/assets/js/components/slideshow.js.
 * Dipakai oleh : katalog modal-drawer (demo slideshow); proyek pewaris (tombol Guide di form dan grid).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Slideshow modal. Dibuka tombol ber-atribut data-dl-open="<id>" (sama dengan x-dl::modal). Gambar dimuat saat slide ditampilkan (bukan saat halaman dimuat).
// items: [['src' => '/guide/x/01.png', 'caption' => 'Press Deactivate', 'alt' => '...'], ...]; alt kosong = caption.
class Slideshow extends DevlibComponent
{
    public function __construct(
        public string $id = 'slideshow',
        public ?string $title = null,
        public array $items = [],
        public string $size = 'xl',
        public bool $open = false,
    ) {}

    public function tag(): string
    {
        return 'x-dl::slideshow id='.$this->id;
    }

    public function source(): string
    {
        return '—';
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class(['dl-mov', 'dl-slides', 'is-on' => $open]) }} {!! $dl() !!} data-dl-modal-id="{{ $id }}" data-dl-slideshow data-items="{{ json_encode(array_values($items)) }}" role="dialog" aria-modal="true" aria-label="{{ \Devlib\Lang::text($title ?? 'Guide') }}">
                <div class="dl-modal dl-modal--{{ $size }}">
                    <div class="dl-modal__head"><h3><i class="fa-solid fa-images"></i> {{ \Devlib\Lang::text($title ?? 'Guide') }}</h3><span class="dl-slides__n" data-sl-n></span><button type="button" class="dl-modal__x" data-dl-close-modal aria-label="{{ \Devlib\Lang::text('Close') }}">&times;</button></div>
                    <div class="dl-modal__body">
                        <figure class="dl-slides__fig"><img data-sl-img alt=""><figcaption data-sl-cap></figcaption></figure>
                    </div>
                    <div class="dl-modal__foot dl-slides__foot">
                        <x-dl::button variant="secondary" size="sm" icon="fa-chevron-left" :label="\Devlib\Lang::t('g_prev')" data-sl-prev />
                        <span class="dl-slides__dots" data-sl-dots></span>
                        <x-dl::button variant="secondary" size="sm" icon="fa-chevron-right" :label="\Devlib\Lang::t('g_next')" data-sl-next />
                    </div>
                </div>
            </div>
            BLADE;
    }
}
