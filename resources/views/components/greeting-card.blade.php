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
                @if (! empty($d['calendar']['url']))<a class="dl-wcalc-btn" href="{{ $d['calendar']['url'] }}" aria-label="{{ \Devlib\Lang::text('Calendar') }}" title="{{ \Devlib\Lang::text('Calendar') }}"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i></a>
                @else<button type="button" class="dl-wcalc-btn" disabled aria-label="{{ \Devlib\Lang::text('Calendar') }}" title="{{ \Devlib\Lang::text('Calendar') }} · {{ \Devlib\Lang::text('Coming soon') }}"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i></button>@endif
            @endif
            @if (! empty($d['calculator']))
                <x-dl::calculator :quantities="$d['calculator']['quantities'] ?? []" :currencies="$d['calculator']['currencies'] ?? []" :featured="$d['calculator']['featured'] ?? []" :base="$d['calculator']['base'] ?? 'IDR'" :rate-url="$d['calculator']['rate_url'] ?? null" :id="$id.'-calc'" />
            @endif
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
