<?php

/*
 * REMARK
 * Tujuan       : Tur interaktif ala misi (Panduan Pengguna): daftar misi per kelompok; satu misi = tangkapan layar nyata dengan titik berdenyut, klik titik memunculkan popup penjelas, daftar langkah dan batang kemajuan ikut terisi, selesai = misi tercentang dan skor naik.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek. Kemajuan disimpan di peramban (localStorage) per kunci.
 * Input        : missions, title, key, source.
 * Output       : Markup Blade inline (render() kelas ini); perilaku di resources/assets/js/components/tour.js.
 * Dipakai oleh : layar proyek lewat x-dl::tour (mis. Help & Support > User's Guide > Interactive); demo devlib.test > Feedback > Tour.
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Tour: $missions = [['id', 'group' (pengelompokan di daftar), 'title', 'summary', 'image' (url tangkapan layar), 'width', 'height' (piksel gambar),
// 'steps' => [[x%, y%, judul, teks], ...]], ...]. Posisi titik = persen dari lebar/tinggi gambar. Judul, ringkasan, dan teks adalah DATA (tidak
// diterjemahkan); label tombol diterjemahkan. key = pembeda penyimpanan kemajuan (mis. id pengguna) supaya satu perangkat dengan banyak akun tidak bercampur.
// Beda dengan x-dl::deck (slide yang berjalan sendiri) dan x-dl::slideshow (gambar bergantian): di sini pembaca yang mengklik titiknya.
class Tour extends DevlibComponent
{
    public function __construct(
        public array $missions = [],
        public ?string $title = null,
        public string $key = 'default',
        public ?string $source = null,
    ) {}

    public function tag(): string
    {
        return 'x-dl::tour';
    }

    public function source(): string
    {
        return $this->source ?? 'daftar misi tur';
    }

    public function render()
    {
        return <<<'BLADE'
            <section {{ $attributes->class('dl-tour') }} {!! $dl() !!} data-dl-tour data-key="{{ $key }}" data-total="{{ count($missions) }}" aria-label="{{ $title ?? \Devlib\Lang::text('Interactive missions') }}"
                data-l-done="{{ \Devlib\Lang::text('missions done') }}" data-l-next="{{ \Devlib\Lang::text('Next mission') }}">
                <div class="dl-tour__list" data-tr-list>
                    <header class="dl-tour__head">
                        <h3>{{ $title ?? \Devlib\Lang::text('Interactive missions') }}</h3>
                        <div class="dl-tour__score" aria-live="polite"><b data-tr-score>0</b> / {{ count($missions) }} <span>{{ \Devlib\Lang::text('missions done') }}</span><span class="dl-tour__bar"><i data-tr-scorebar></i></span>
                            <button type="button" class="dl-tour__link" data-tr-reset hidden>{{ \Devlib\Lang::text('Start over') }}</button></div>
                    </header>
                    @foreach (collect($missions)->groupBy(fn ($m) => $m['group'] ?? '') as $group => $items)
                        <section class="dl-tour__group">
                            @if ($group !== '')<h4>{{ $group }}</h4>@endif
                            <div class="dl-tour__cards">
                                @foreach ($items as $m)
                                    <button type="button" class="dl-tour__card" data-tr-open="{{ $m['id'] }}"><span class="dl-tour__state" data-tr-state aria-hidden="true"><i class="fa-solid fa-play"></i></span><b>{{ $m['title'] }}</b><small>{{ $m['summary'] ?? '' }}</small><em>{{ count($m['steps'] ?? []) }} {{ \Devlib\Lang::text('steps') }}</em></button>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                    @if (! $missions)<p class="dl-tour__empty">{{ \Devlib\Lang::text('No missions.') }}</p>@endif
                </div>
                @foreach ($missions as $m)
                    <div class="dl-tour__stage" data-tr-stage="{{ $m['id'] }}" hidden>
                        <div class="dl-tour__bar-row">
                            <button type="button" class="dl-tour__link" data-tr-back><i class="fa-solid fa-chevron-left"></i> {{ \Devlib\Lang::text('All missions') }}</button>
                            <h3>{{ $m['title'] }}</h3>
                            <span class="dl-tour__prog" aria-live="polite"><b data-tr-n>0</b> / {{ count($m['steps'] ?? []) }}</span>
                        </div>
                        <div class="dl-tour__play">
                            <div class="dl-tour__cam" data-tr-cam>
                                <img src="{{ $m['image'] }}" alt="{{ $m['title'] }}" @if (! empty($m['width']))width="{{ $m['width'] }}" height="{{ $m['height'] ?? '' }}"@endif loading="lazy" decoding="async">
                                @foreach ($m['steps'] ?? [] as $i => [$x, $y, $t, $d])
                                    <button type="button" class="dl-tour__hot" data-tr-step="{{ $i }}" data-t="{{ $t }}" data-d="{{ $d }}" style="left: {{ $x }}%; top: {{ $y }}%" aria-label="{{ $t }}"></button>
                                @endforeach
                                <div class="dl-tour__pop" data-tr-pop hidden role="dialog"><button type="button" class="dl-tour__x" data-tr-popx aria-label="{{ \Devlib\Lang::text('Close') }}">&times;</button><b data-tr-pt></b><p data-tr-pd></p></div>
                            </div>
                            <aside class="dl-tour__side">
                                <p class="dl-tour__sum">{{ $m['summary'] ?? '' }}</p>
                                <ol class="dl-tour__steps">@foreach ($m['steps'] ?? [] as $i => $s)<li data-tr-li="{{ $i }}"><button type="button" data-tr-go="{{ $i }}">{{ $s[2] }}</button></li>@endforeach</ol>
                                <div class="dl-tour__win" data-tr-win hidden><b>{{ \Devlib\Lang::text('Mission complete!') }}</b><span>{{ \Devlib\Lang::text('You have seen every point on this screen.') }}</span><span data-tr-nextbox></span></div>
                            </aside>
                        </div>
                    </div>
                @endforeach
            </section>
            BLADE;
    }
}
