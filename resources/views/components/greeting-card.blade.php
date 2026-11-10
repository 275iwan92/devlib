@php
    $d = $board;
    $u = $d['user'] ?? [];
    $id = $key();
@endphp
<div {{ $attributes }} {!! $dl() !!}>
@php($fx = ! empty($d['animate']))
<section @class(['dl-dash__hero', 'dl-dash__hero--fx' => $fx]) @if ($fx) data-dl-hero-fx @endif>
    @if ($fx)
        <canvas class="dl-dash__fx" aria-hidden="true"></canvas>
    @endif
    <div class="dl-dash__hello">
        <div class="dl-dash__eyebrow">{{ $d['profile_label'] ?? 'Dashboard' }}</div>
        <x-dl::greeting :name="$u['first'] ?? ''" />
        <div class="dl-dash__pos">
            @foreach (array_filter([$u['position'] ?? null, $u['department'] ?? null, isset($u['superior']) ? \Devlib\Lang::text('Reports to').': '.$u['superior'] : null]) as $p)
                <span>{{ $p }}</span>
            @endforeach
        </div>
        @if (! empty($d['brief']))
            <div class="dl-dash__brief">
                @foreach ($d['brief'] as [$label, $value])
                    <span>{{ $label }} <b>{{ $value }}</b></span>
                @endforeach
            </div>
        @endif
        <div class="dl-dash__docs">
            @if (! empty($d['jobdesc']))
                <x-dl::button variant="secondary" icon="fa-briefcase" label="Job Description" data-dl-open="{{ $id }}-jd" />
            @endif
            @if (! empty($d['sops']))
                <x-dl::button variant="ghost" icon="fa-book-open" label="SOP" data-dl-open="{{ $id }}-sop"><span class="dl-dash__count">{{ count($d['sops']) }}</span></x-dl::button>
            @endif
            @if (isset($d['calendar']))
                @if (! empty($d['calendar']['url']))<a class="dl-wcalc-btn" href="{{ $d['calendar']['url'] }}" aria-label="{{ \Devlib\Lang::text('Calendar') }}" title="{{ \Devlib\Lang::text('Calendar') }}"><svg viewBox="0 0 24 24" data-ic="calendar" aria-hidden="true"><path d="M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><path d="M16 3v4M8 3v4M4 11h16M11 15h1M12 15v3"/></svg></a>
                @else<button type="button" class="dl-wcalc-btn" disabled aria-label="{{ \Devlib\Lang::text('Calendar') }}" title="{{ \Devlib\Lang::text('Calendar') }} · {{ \Devlib\Lang::text('Coming soon') }}"><svg viewBox="0 0 24 24" data-ic="calendar" aria-hidden="true"><path d="M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><path d="M16 3v4M8 3v4M4 11h16M11 15h1M12 15v3"/></svg></button>@endif
            @endif
            @if (! empty($d['calculator']))
                <x-dl::calculator :quantities="$d['calculator']['quantities'] ?? []" :currencies="$d['calculator']['currencies'] ?? []" :featured="$d['calculator']['featured'] ?? []" :base="$d['calculator']['base'] ?? 'IDR'" :rate-url="$d['calculator']['rate_url'] ?? null" :id="$id.'-calc'" />
            @endif
            {{-- Pintasan (Jo 2026-11-10): ikon garis putih di kiri tombol Play, bentuk sama dengan Calendar / Calculator; tiap isi ['label', 'url', 'code', 'paths' => isi <svg> 24x24 bergaya garis], klik membuka url sebagai tab kerja. --}}
            @foreach ($d['shortcuts'] ?? [] as $sc)
                <a class="dl-wcalc-btn" href="{{ $sc['url'] }}" data-dl-tab-url="{{ $sc['url'] }}" data-dl-tab-code="{{ $sc['code'] ?? $sc['url'] }}" data-dl-tab-label="{{ $sc['label'] }}" aria-label="{{ $sc['label'] }}" title="{{ $sc['label'] }}"><svg viewBox="0 0 24 24" aria-hidden="true">{!! $sc['paths'] !!}</svg></a>
            @endforeach
            @if (! empty($d['music']['tracks']))
                <x-dl::music-player :tracks="$d['music']['tracks']" :base="$d['music']['base'] ?? '/audio'" :storage-key="$d['music']['key'] ?? 'dl-music'" :volume="$d['music']['volume'] ?? 25" />
            @endif
        </div>
    </div>
    <div class="dl-dash__side">
        <x-dl::clock />
        @if (! empty($d['attendance']))
            @php($a = $d['attendance'])
            <div class="dl-dash__att">
                <x-dl::badge :tone="$a['tone'] ?? 'success'" icon="fa-fingerprint" :label="'Check-in '.$a['in']" />
                <small>{{ $a['status'] ?? '' }} · Shift {{ $a['shift'] ?? '-' }} ({{ $a['start'] ?? '' }}–{{ $a['end'] ?? '' }})</small>
            </div>
        @endif
        @if (! empty($d['weather']))
            <x-dl::weather :locate="$d['weather']['locate'] ?? true" :city="$d['weather']['city'] ?? null" :lat="$d['weather']['lat'] ?? null" :lon="$d['weather']['lon'] ?? null" />
        @endif
    </div>
</section>

    {{-- Job Description & SOP: HTML dari master jabatan (diisi HR Officer), tampil read-only --}}
    @if (! empty($d['jobdesc']))
        <x-dl::modal :id="$id.'-jd'" :title="'Job Description · '.\Devlib\Lang::raw($d['jobdesc']['title'] ?? '')" icon="fa-briefcase" size="lg">
            @if (isset($d['jobdesc']['text']))
                <x-dl::doc-sections :text="$d['jobdesc']['text']" :inline="$d['jobdesc']['inline'] ?? []" />
            @else
                <div class="dl-dash__doc">{!! $d['jobdesc']['html'] !!}</div>
            @endif
            <x-slot:footer><small class="dl-dash__upd">{{ \Devlib\Lang::text('Last updated') }} {{ $d['jobdesc']['updated'] ?? '-' }}</small><x-dl::button preset="close" data-dl-close-modal /></x-slot:footer>
        </x-dl::modal>
    @endif
    @if (! empty($d['sops']))
        <x-dl::modal :id="$id.'-sop'" :title="'SOP · '.\Devlib\Lang::raw($u['position'] ?? '')" icon="fa-book-open" size="lg">
            @foreach ($d['sops'] as $i => $s)
                <x-dl::accordion :title="\Devlib\Lang::raw($s['code'].' · '.$s['title'])" :badge="'Rev. '.$s['revision']" :open="$i === 0" icon="fa-file-lines">
                    <div class="dl-dash__doc"><p class="dl-dash__upd">{{ \Devlib\Lang::text('Effective from') }} {{ $s['effective'] }}</p>{!! $s['html'] !!}</div>
                </x-dl::accordion>
            @endforeach
            <x-slot:footer><x-dl::button preset="close" data-dl-close-modal /></x-slot:footer>
        </x-dl::modal>
    @endif
</div>
