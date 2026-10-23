<?php

/*
 * REMARK
 * Tujuan       : Galeri gambar acuan: kisi gambar kecil dengan keterangan dan catatan ukuran; ketuk gambar = buka ukuran penuh di tab baru. Untuk tangkapan layar acuan visual sebuah object.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop (alamat gambar di public/ proyek), tanpa tabel/model proyek.
 * Input        : items, title, note, columns.
 * Output       : Markup Blade inline (render() kelas ini).
 * Dipakai oleh : halaman katalog p7-phone (galeri Acuan visual).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Galeri gambar acuan. items: [['src' => '/ref/x.png', 'caption' => 'Keadaan A', 'alt' => '...'], ...]; alt kosong = caption. Tanpa JS.
class ImageGallery extends DevlibComponent
{
    public function __construct(
        public array $items = [],
        public string $title = '',
        // Catatan di bawah judul (mis. ukuran uji gambar).
        public string $note = '',
        // Lebar kolom minimum kartu gambar dalam px (kisi menyesuaikan jumlah kolom).
        public int $columns = 150,
    ) {}

    public function tag(): string
    {
        return 'x-dl::image-gallery';
    }

    public function source(): string
    {
        return '—';
    }

    public function render()
    {
        return <<<'BLADE'
            <figure {{ $attributes->class('dl-gallery') }} {!! $dl() !!} style="--dl-gal-min:{{ max(80, $columns) }}px">
                @if ($title !== '' || $note !== '')
                    <figcaption class="dl-gallery__head">@if ($title !== '')<b>{{ \Devlib\Lang::text($title) }}</b>@endif @if ($note !== '')<span>{{ \Devlib\Lang::text($note) }}</span>@endif</figcaption>
                @endif
                <div class="dl-gallery__grid">
                    @foreach ($items as $it)
                        <a class="dl-gallery__item" href="{{ $it['src'] }}" target="_blank" rel="noopener">
                            <img src="{{ $it['src'] }}" alt="{{ \Devlib\Lang::text($it['alt'] ?? $it['caption'] ?? '') }}" loading="lazy">
                            @if (! empty($it['caption']))<span>{{ \Devlib\Lang::text($it['caption']) }}</span>@endif
                        </a>
                    @endforeach
                </div>
            </figure>
            BLADE;
    }
}
