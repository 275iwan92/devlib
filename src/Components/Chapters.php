<?php

/*
 * REMARK
 * Tujuan       : Panduan gaya blog / Pinterest: papan kartu (tangkapan layar yang diperbesar di titik langkah pertama, pencarian, saringan kelompok, kartu alur) dan satu bab bergambar per tugas (judul, alur beranimasi, langkah demi langkah dengan potongan layar bercincin, tips, bacaan berikutnya, tombol Present / Try / Detail).
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : tasks, groups, title, presentUrl, tryUrl, source.
 * Output       : Markup Blade inline (render() kelas ini); perilaku di resources/assets/js/components/chapters.js; potongan layar = x-dl::shot.
 * Dipakai oleh : layar proyek lewat x-dl::chapters (Help & Support > User's Guide > Blog); demo devlib.test > Feedback > Chapters.
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Chapters: $tasks = [['id', 'group' (kunci di $groups), 'title', 'summary', 'image' (url tangkapan layar), 'flow' => [label, ...], 'steps' => [[x%, y%, judul, teks], ...],
// 'tips' => [teks, ...], 'related' => [id, ...], 'pin' => bool (kartu alur tambahan), 'href' => url panduan lengkap (opsional)], ...];
// $groups = [kunci => ['title', 'tag', 'hue' (0-360)]]. presentUrl / tryUrl = url dengan {id} (kosong = tombolnya tidak tampil). Semua teks adalah DATA (tidak diterjemahkan);
// label tombol diterjemahkan. Beda dengan x-dl::tour (pembaca mengklik titik) dan x-dl::deck (presentasi yang berjalan sendiri): ini bacaan bergambar yang bisa dicari.
class Chapters extends DevlibComponent
{
    public function __construct(
        public array $tasks = [],
        public array $groups = [],
        public ?string $title = null,
        public ?string $presentUrl = null,
        public ?string $tryUrl = null,
        public ?string $source = null,
    ) {}

    public function tag(): string
    {
        return 'x-dl::chapters';
    }

    public function source(): string
    {
        return $this->source ?? 'daftar tugas panduan';
    }

    public function render()
    {
        return <<<'BLADE'
            @php
                $byId = collect($tasks)->keyBy('id');
                $mins = fn ($t) => max(1, (int) round(count($t['steps'] ?? []) * 0.5));
                $url = fn (?string $tpl, $id) => $tpl ? str_replace('{id}', $id, $tpl) : null;
                $first = fn ($t) => ($t['steps'][0] ?? [50, 30]);
            @endphp
            <section {{ $attributes->class('dl-ch') }} {!! $dl() !!} data-dl-chapters aria-label="{{ $title ?? \Devlib\Lang::text('Illustrated guide') }}">
                <div class="dl-ch__home" data-ch-home>
                    <header class="dl-ch__head">
                        <h3>{{ $title ?? \Devlib\Lang::text('Illustrated guide') }}</h3>
                        <input type="search" class="dl-ch__search" data-ch-search placeholder="{{ \Devlib\Lang::text('Search a task') }}" aria-label="{{ \Devlib\Lang::text('Search a task') }}">
                        <div class="dl-ch__chips" role="group">
                            <button type="button" class="dl-ch__chip is-on" data-ch-group="">{{ \Devlib\Lang::text('All') }}</button>
                            @foreach ($groups as $k => $g)<button type="button" class="dl-ch__chip" data-ch-group="{{ $k }}" style="--h: {{ $g['hue'] ?? 215 }}">{{ $g['title'] }}</button>@endforeach
                        </div>
                    </header>
                    <div class="dl-ch__masonry" data-ch-cards>
                        @foreach ($tasks as $t)
                            @php $g = $groups[$t['group']] ?? ['title' => '', 'hue' => 215]; $s0 = $first($t); @endphp
                            @if (! empty($t['pin']) && ! empty($t['flow']))
                                <article class="dl-ch__card dl-ch__card--pin" data-ch-card data-g="{{ $t['group'] }}" data-t="{{ mb_strtolower($t['title'].' '.implode(' ', $t['flow'])) }}" style="--h: {{ $g['hue'] ?? 215 }}">
                                    <span class="dl-ch__tag">{{ \Devlib\Lang::text('Flow') }}</span>
                                    <h4><button type="button" data-ch-open="{{ $t['id'] }}">{{ $t['title'] }}</button></h4>
                                    <ol class="dl-ch__pinflow">@foreach ($t['flow'] as $i => $f)<li style="--i: {{ $i }}"><span>{{ $f }}</span></li>@endforeach</ol>
                                    <div class="dl-ch__acts"><button type="button" data-ch-open="{{ $t['id'] }}">{{ \Devlib\Lang::text('Read') }}</button></div>
                                </article>
                            @endif
                            <article class="dl-ch__card" data-ch-card data-g="{{ $t['group'] }}" data-t="{{ mb_strtolower($t['title'].' '.($t['summary'] ?? '').' '.$g['title'].' '.implode(' ', $t['flow'] ?? [])) }}" style="--h: {{ $g['hue'] ?? 215 }}">
                                <x-dl::shot :src="$t['image']" :anim="$t['anim'] ?? ''" :x="$s0[0]" :y="$s0[1]" :zoom="1.9" />
                                <div class="dl-ch__cbody">
                                    <span class="dl-ch__tag">{{ $g['title'] }}</span>
                                    <h4><button type="button" data-ch-open="{{ $t['id'] }}">{{ $t['title'] }}</button></h4>
                                    <p>{{ $t['summary'] ?? '' }}</p>
                                    <small>{{ count($t['steps'] ?? []) }} {{ \Devlib\Lang::text('steps') }} · {{ $mins($t) }} min</small>
                                    <div class="dl-ch__acts">
                                        <button type="button" data-ch-open="{{ $t['id'] }}">{{ \Devlib\Lang::text('Read') }}</button>
                                        @if ($u = $url($presentUrl, $t['id']))<a href="{{ $u }}">{{ \Devlib\Lang::text('Present') }}</a>@endif
                                        @if ($u = $url($tryUrl, $t['id']))<a href="{{ $u }}">{{ \Devlib\Lang::text('Try') }}</a>@endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <p class="dl-ch__empty" data-ch-empty hidden>{{ \Devlib\Lang::text('No task matches.') }}</p>
                </div>

                @foreach ($tasks as $t)
                    @php
                        $g = $groups[$t['group']] ?? ['title' => '', 'hue' => 215];
                        $sibs = collect($tasks)->where('group', $t['group'])->values();
                        $pos = $sibs->search(fn ($x) => $x['id'] === $t['id']);
                        $prev = $pos > 0 ? $sibs[$pos - 1] : null;
                        $next = $pos < $sibs->count() - 1 ? $sibs[$pos + 1] : null;
                        $s0 = $first($t);
                    @endphp
                    <article class="dl-ch__art" data-ch-art="{{ $t['id'] }}" hidden style="--h: {{ $g['hue'] ?? 215 }}">
                        <nav class="dl-ch__crumb"><button type="button" data-ch-back><i class="fa-solid fa-chevron-left"></i> {{ \Devlib\Lang::text('All tasks') }}</button><span>{{ $g['title'] }}</span></nav>
                        <div class="dl-ch__hero">
                            <x-dl::shot :src="$t['image']" :anim="$t['anim'] ?? ''" :x="50" :y="28" :zoom="1.15" :ring="false" />
                            <div>
                                <span class="dl-ch__tag">{{ $g['title'] }}</span>
                                <h2>{{ $t['title'] }}</h2>
                                <p class="dl-ch__lead">{{ $t['summary'] ?? '' }}</p>
                                <small>{{ count($t['steps'] ?? []) }} {{ \Devlib\Lang::text('steps') }} · {{ $mins($t) }} min</small>
                                <div class="dl-ch__acts dl-ch__acts--big">
                                    @if ($u = $url($presentUrl, $t['id']))<a class="is-primary" href="{{ $u }}">{{ \Devlib\Lang::text('Present') }}</a>@endif
                                    @if ($u = $url($tryUrl, $t['id']))<a href="{{ $u }}">{{ \Devlib\Lang::text('Try') }}</a>@endif
                                    @if (! empty($t['href']))<a href="{{ $t['href'] }}">{{ \Devlib\Lang::text('Full guide') }}</a>@endif
                                </div>
                            </div>
                        </div>
                        <div class="dl-ch__body">
                            <nav class="dl-ch__toc" aria-label="{{ \Devlib\Lang::text('In this chapter') }}">
                                <b>{{ \Devlib\Lang::text('In this chapter') }}</b>
                                @if (! empty($t['flow']))<button type="button" data-ch-jump="ch-{{ $t['id'] }}-flow">{{ \Devlib\Lang::text('The flow') }}</button>@endif
                                @foreach ($t['steps'] ?? [] as $i => $s)<button type="button" data-ch-jump="ch-{{ $t['id'] }}-s{{ $i + 1 }}">{{ $i + 1 }}. {{ $s[2] }}</button>@endforeach
                                @if (! empty($t['tips']))<button type="button" data-ch-jump="ch-{{ $t['id'] }}-tips">{{ \Devlib\Lang::text('Tips') }}</button>@endif
                                <button type="button" data-ch-jump="ch-{{ $t['id'] }}-next">{{ \Devlib\Lang::text('Read next') }}</button>
                            </nav>
                            <div class="dl-ch__main">
                                @if (! empty($t['flow']))
                                    <section id="ch-{{ $t['id'] }}-flow">
                                        <h3>{{ \Devlib\Lang::text('The flow') }}</h3>
                                        <ol class="dl-ch__flowline">@foreach ($t['flow'] as $i => $f)<li style="--i: {{ $i }}"><b>{{ $i + 1 }}</b><span>{{ $f }}</span></li>@endforeach</ol>
                                    </section>
                                @endif
                                <section>
                                    <h3>{{ \Devlib\Lang::text('Step by step') }}</h3>
                                    @foreach ($t['steps'] ?? [] as $i => [$x, $y, $st, $tx])
                                        <div class="dl-ch__step {{ $i % 2 ? 'is-rev' : '' }}" id="ch-{{ $t['id'] }}-s{{ $i + 1 }}">
                                            <x-dl::shot :src="$t['image']" :x="$x" :y="$y" :zoom="2.4" />
                                            <div><span class="dl-ch__no">{{ $i + 1 }}</span><h4>{{ $st }}</h4><p>{{ $tx }}</p></div>
                                        </div>
                                    @endforeach
                                </section>
                                @if (! empty($t['tips']))
                                    <section id="ch-{{ $t['id'] }}-tips"><h3>{{ \Devlib\Lang::text('Tips') }}</h3><div class="dl-ch__tips">@foreach ($t['tips'] as $tip)<p>{{ $tip }}</p>@endforeach</div></section>
                                @endif
                                <section id="ch-{{ $t['id'] }}-next">
                                    <h3>{{ \Devlib\Lang::text('Read next') }}</h3>
                                    <div class="dl-ch__next">
                                        @if ($prev)<button type="button" data-ch-open="{{ $prev['id'] }}"><small>‹ {{ \Devlib\Lang::text('Previous') }}</small><b>{{ $prev['title'] }}</b></button>@endif
                                        @if ($next)<button type="button" data-ch-open="{{ $next['id'] }}"><small>{{ \Devlib\Lang::text('Next') }} ›</small><b>{{ $next['title'] }}</b></button>@endif
                                        @foreach ($t['related'] ?? [] as $r)
                                            @if ($byId->has($r) && ($prev['id'] ?? null) !== $r && ($next['id'] ?? null) !== $r)<button type="button" data-ch-open="{{ $r }}"><small>{{ \Devlib\Lang::text('Related') }}</small><b>{{ $byId[$r]['title'] }}</b></button>@endif
                                        @endforeach
                                    </div>
                                </section>
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>
            BLADE;
    }
}
