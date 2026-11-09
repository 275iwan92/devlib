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

// Deck: $slides = [['title', 'lead' (satu kalimat, opsional), 'items' => [butir, ...] (muncul berurutan), 'image' => url (opsional)], ...].
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
            <section {{ $attributes->class('dl-deck') }} {!! $dl() !!} data-dl-deck data-interval="{{ max(2, $interval) }}" data-autoplay="{{ $autoplay ? 1 : 0 }}" tabindex="0" aria-roledescription="carousel" aria-label="{{ $title ?? \Devlib\Lang::text('Presentation') }}">
                <div class="dl-deck__stage">
                    @foreach ($slides as $s)
                        <article class="dl-deck__slide {{ $loop->first ? 'is-on' : '' }}">
                            <h2>{{ $s['title'] ?? '' }}</h2>
                            @if (! empty($s['lead']))<p class="dl-deck__lead">{{ $s['lead'] }}</p>@endif
                            @if (! empty($s['items']))<ol class="dl-deck__items">@foreach ($s['items'] as $k => $t)<li style="--i: {{ $k }}">{{ $t }}</li>@endforeach</ol>@endif
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
                </div>
            </section>
            BLADE;
    }
}
