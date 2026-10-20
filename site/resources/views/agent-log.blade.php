{{-- Agent Log devlib.test (user 2026-10-07): log pekerjaan agen Iwan dan Jo (satu baris per commit), teks polos per tanggal.
     Aturan = App\Support\AgentLog. --}}
@extends('layout', ['title' => 'Agent Log', 'dev' => true, 'bodyClass' => 'dl-page-body'])
@php
    use App\Support\AgentLog;
    use Devlib\Lang;

    $home = route('agent-log');
    $agent = request('agent');
    $all = AgentLog::rows();
    $months = array_values(array_unique(array_column($all, 'month')));
    $month = in_array(request('month'), $months, true) ? request('month') : null;
    $q = trim((string) request('q'));

    $agentName = fn ($key) => AgentLog::AGENTS[$key === 'none' ? '' : $key] ?? null;
    $rows = array_filter($all, fn ($r) => (! $agent || $r['agent'] === $agentName($agent))
        && (! $month || $r['month'] === $month)
        && ($q === '' || stripos($r['job'].' '.$r['commit'], $q) !== false));
    // Log selalu bertambah banyak: tampil per halaman, satu baris per commit.
    $perPage = 100;
    $total = count($rows);
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, (int) request('page', 1)), $pages);
    $byDate = [];
    foreach (array_slice(array_values($rows), ($page - 1) * $perPage, $perPage) as $r) { $byDate[$r['date']][] = $r; }
    $url = fn (int $p, array $over = []) => $home.'?'.http_build_query(array_filter(array_merge(['agent' => $agent, 'month' => $month, 'q' => $q, 'page' => $p > 1 ? $p : null], $over)));
    $agentTabs = ['' => 'All', 'Iwan' => 'Iwan', 'Jo' => 'Jo', 'none' => 'Before tracking'];
@endphp
@section('body')
<div class="dl-sum">
    <header class="dl-sum__head"><b>{{ Lang::text('Agent Log') }}</b><small>{{ Lang::text('Every job saved by Agent Iwan and Agent Jo, one row per commit. Filter by agent or month.') }}</small></header>

    {{-- Hanya dua agen dan beberapa bulan: tautan teks langsung, bukan dropdown. Pilihan aktif ditebalkan. --}}
    <div style="display:flex;gap:6px 18px;flex-wrap:wrap;align-items:baseline">
        <span style="display:flex;gap:12px;flex-wrap:wrap">
            @foreach ($agentTabs as $key => $label)
                <a href="{{ $url(1, ['agent' => $key ?: null]) }}" style="{{ (string) $agent === (string) $key ? 'font-weight:bold;text-decoration:underline' : 'text-decoration:none' }}">{{ Lang::text($label) }}</a>
            @endforeach
        </span>
        <span style="color:var(--dl-border)">|</span>
        <span style="display:flex;gap:12px;flex-wrap:wrap">
            <a href="{{ $url(1, ['month' => null]) }}" style="{{ ! $month ? 'font-weight:bold;text-decoration:underline' : 'text-decoration:none' }}">{{ Lang::text('All months') }}</a>
            @foreach ($months as $m)
                <a href="{{ $url(1, ['month' => $m]) }}" style="{{ $month === $m ? 'font-weight:bold;text-decoration:underline' : 'text-decoration:none' }}">{{ $m }}</a>
            @endforeach
        </span>
        <form method="get" action="{{ $home }}" style="display:flex;gap:6px;margin-left:auto">
            <input type="hidden" name="agent" value="{{ $agent }}"><input type="hidden" name="month" value="{{ $month }}">
            <input type="search" name="q" value="{{ $q }}" placeholder="{{ Lang::text('Search job, commit...') }}" style="min-width:220px">
        </form>
        <span style="color:var(--dl-muted)">{{ $total }} {{ Lang::text('jobs') }}</span>
    </div>

    @forelse ($byDate as $date => $items)
        <section style="margin-top:4px">
            <h3 style="margin:0;padding:2px 0;font-size:13px;color:var(--dl-ink-strong);border-bottom:1px solid var(--dl-border)">{{ \Devlib\Locale::date($date) }} <small style="font-weight:normal;color:var(--dl-muted)">· {{ count($items) }}</small></h3>
            @foreach ($items as $r)
                <div title="{{ $r['job'] }}" style="display:flex;gap:10px;padding:1px 0;font-size:13px;line-height:1.45;white-space:nowrap;overflow:hidden">
                    <span style="flex:0 0 36px;font-weight:bold">{{ str_replace('Agent ', '', $r['agent']) }}</span>
                    <span style="flex:0 0 64px;color:var(--dl-muted);font-family:monospace">{{ $r['commit'] }}</span>
                    <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis">{{ $r['job'] }}</span>
                </div>
            @endforeach
        </section>
    @empty
        <p style="color:var(--dl-muted)">{{ Lang::text('No jobs match.') }}</p>
    @endforelse

    @if ($pages > 1)
        <nav style="display:flex;gap:10px;align-items:center;margin-top:8px">
            @if ($page > 1)<a href="{{ $url($page - 1) }}">&laquo; {{ Lang::text('Newer') }}</a>@endif
            <span style="color:var(--dl-muted)">{{ $page }} / {{ $pages }}</span>
            @if ($page < $pages)<a href="{{ $url($page + 1) }}">{{ Lang::text('Older') }} &raquo;</a>@endif
        </nav>
    @endif
</div>
@endsection
