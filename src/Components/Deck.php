<?php

/*
 * REMARK
 * Tujuan       : Presentasi slide di halaman (gaya "video" Panduan Pengguna): satu slide terlihat, butir muncul berurutan; tombol Previous / Play-Pause / Next / Full screen, batang kemajuan, titik, tombol panah; Play = maju sendiri tiap interval detik.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : slides, title, autoplay, interval, source.
 * Output       : Markup Blade inline (render() kelas ini); perilaku di resources/assets/js/components/deck.js.
 * Dipakai oleh : layar proyek lewat x-dl::deck (mis. Help & Support > User's Guide > Slides / Video); demo devlib.test > Feedback > Deck.
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Deck: $slides = [['title', 'lead' (satu kalimat, opsional), 'items' => [butir, ...] (muncul berurutan), 'image' => url (opsional),
// 'tag' => teks kecil di atas judul, 'hero' => true (slide pembuka bergradasi dengan bola melayang), 'flow' => true (butir jadi alur bernomor mendatar),
// 'shot' => ['src', 'x', 'y'] (tangkapan layar di bingkai peramban, zoom pelan dari titik x%,y%; 'kb' => 'out' = zoom keluar, 'none' = diam; 'anim' = url animasi WebP/GIF terbingkai sebagai ganti gambar),
// 'walk' => ['src', 'steps' => [[x%, y%, judul, teks], ...]] (kamera menyusuri langkah di tangkapan layar: zoom ke titik, cincin, keterangan),
// 'say' => teks narasi (string; untuk slide walk: list satu kalimat per langkah) dibacakan suara peramban id-ID (perempuan diutamakan); ada 'say' di salah satu slide = tombol suara muncul; slide maju saat bacaan selesai], ...].
// Judul, lead, dan butir adalah DATA (tidak diterjemahkan); label tombol diterjemahkan. autoplay = mulai Play saat halaman dibuka;
// interval = detik per slide. Beda dengan x-dl::slideshow: itu jendela modal untuk tangkapan layar; ini slide teks yang bisa jalan sendiri.
class Deck extends DevlibComponent
{
    public function __construct(
        public array $slides = [],
        public ?string $title = null,
        public bool $autoplay = false,
        public int $interval = 7,
        public ?string $source = null,
    ) {}

    public function tag(): string
    {
        return 'x-dl::deck';
    }

    public function source(): string
    {
        return $this->source ?? 'daftar slide';
    }

    public function render()
    {
        return <<<'BLADE'
            @php $narr = collect($slides)->contains(fn ($s) => ! empty($s['say'])); @endphp
            <section {{ $attributes->class('dl-deck') }} {!! $dl() !!} data-dl-deck data-interval="{{ max(2, $interval) }}" data-autoplay="{{ $autoplay ? 1 : 0 }}" tabindex="0" aria-roledescription="carousel" aria-label="{{ $title ?? \Devlib\Lang::text('Presentation') }}">
                <div class="dl-deck__stage">
                    @foreach ($slides as $s)
                        @php $walk = $s['walk'] ?? null; $shot = $s['shot'] ?? null; @endphp
                        <article class="dl-deck__slide {{ $loop->first ? 'is-on' : '' }} {{ ! empty($s['hero']) ? 'dl-deck__slide--hero' : '' }} {{ ($walk || $shot) ? 'dl-deck__slide--shot '.(($s['kb'] ?? 'in') === 'out' ? 'is-out' : (($s['kb'] ?? 'in') === 'none' ? 'is-none' : 'is-in')) : '' }}"
                            @if ($walk) data-dk-walk data-ms="{{ count($walk['steps']) * 3400 + 1500 }}" data-steps="{{ json_encode(array_map(fn ($w) => ['x' => $w[0], 'y' => $w[1], 't' => $w[2], 'd' => $w[3]], $walk['steps']), JSON_UNESCAPED_UNICODE) }}" @endif @if (! empty($s['say'])) data-say="{{ json_encode($s['say'], JSON_UNESCAPED_UNICODE) }}" @endif>
                            @if (! empty($s['hero']))<i class="dl-deck__orb dl-deck__orb--a" aria-hidden="true"></i><i class="dl-deck__orb dl-deck__orb--b" aria-hidden="true"></i>@endif
                            @if (! empty($s['tag']))<p class="dl-deck__tag">{{ $s['tag'] }}</p>@endif
                            <h2>{{ $s['title'] ?? '' }}</h2>
                            @if (! empty($s['lead']))<p class="dl-deck__lead">{{ $s['lead'] }}</p>@endif
                            @if (! empty($s['items']))<ol class="dl-deck__items {{ ! empty($s['flow']) ? 'dl-deck__items--flow' : '' }}">@foreach ($s['items'] as $k => $t)<li style="--i: {{ $k }}">{{ $t }}</li>@endforeach</ol>@endif
                            @if ($shot)<div class="dl-deck__frame"><div class="dl-deck__bt"><i></i><i></i><i></i></div><div class="dl-deck__clip">@if (! empty($shot['anim']))<picture><source media="(prefers-reduced-motion: reduce)" srcset="{{ $shot['src'] }}"><img src="{{ $shot['anim'] }}" alt="" loading="lazy"></picture>@else<img src="{{ $shot['src'] }}" alt="" style="transform-origin: {{ $shot['x'] ?? 50 }}% {{ $shot['y'] ?? 30 }}%" loading="lazy">@endif</div></div>@endif
                            @if ($walk)
                                <div class="dl-deck__frame"><div class="dl-deck__bt"><i></i><i></i><i></i></div>
                                    <div class="dl-deck__clip"><div class="dl-deck__cam" data-dk-cam><img src="{{ $walk['src'] }}" alt="" loading="lazy"><i class="dl-deck__ring" data-dk-ring></i></div></div>
                                    <div class="dl-deck__cap" aria-live="polite"><b data-dk-ct></b><span data-dk-cd></span></div>
                                </div>
                            @endif
                            @if (! empty($s['image']))<img class="dl-deck__img" src="{{ $s['image'] }}" alt="" loading="lazy">@endif
                        </article>
                    @endforeach
                    @if (! $slides)<p class="dl-deck__empty">{{ \Devlib\Lang::text('No slides.') }}</p>@endif
                </div>
                <div class="dl-deck__bar">
                    <button type="button" class="dl-deck__btn" data-dk-prev aria-label="{{ \Devlib\Lang::text('Previous') }}"><i class="fa-solid fa-backward-step"></i></button>
                    <button type="button" class="dl-deck__btn dl-deck__btn--play" data-dk-play aria-label="{{ \Devlib\Lang::text('Play') }}" data-l-play="{{ \Devlib\Lang::text('Play') }}" data-l-pause="{{ \Devlib\Lang::text('Pause') }}"><i class="fa-solid fa-play"></i></button>
                    <button type="button" class="dl-deck__btn" data-dk-next aria-label="{{ \Devlib\Lang::text('Next') }}"><i class="fa-solid fa-forward-step"></i></button>
                    <span class="dl-deck__prog" aria-hidden="true"><i data-dk-prog></i></span>
                    <span class="dl-deck__n" data-dk-n></span>
                    <button type="button" class="dl-deck__btn" data-dk-full aria-label="{{ \Devlib\Lang::text('Full screen') }}"><i class="fa-solid fa-expand"></i></button>
                    @if ($narr)<button type="button" class="dl-deck__btn" data-dk-voice aria-pressed="true" aria-label="{{ \Devlib\Lang::text('Voice') }}"><i class="fa-solid fa-volume-high"></i></button><span class="dl-deck__n" data-dk-vs data-none="{{ \Devlib\Lang::text('No Indonesian voice in this browser: the text stays, the voice is silent.') }}"></span>@endif
                </div>
            </section>
            BLADE;
    }
}
