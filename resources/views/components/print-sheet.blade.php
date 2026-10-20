@php
    $co = $company();
    $cell = fn ($v) => is_array($v) ? $v : ['text' => $v];
@endphp
<div {{ $attributes->class(['dl-psheet', 'is-landscape' => $landscape]) }} {!! $dl() !!} data-dl-pdoc data-paper="a4" data-orientation="{{ $landscape ? 'landscape' : 'portrait' }}">
    <section class="dl-psheet__page">
        <header class="dl-psheet__kop">
            <div class="dl-pdoc__co">
                @if (! $co['logo'])<span class="dl-pdoc__logo">{{ $co['initials'] }}</span>@endif
                <div>@if ($co['logo'])<img class="dl-pdoc__brand" src="{{ $co['logo'] }}" alt="{{ $co['name'] }}">@else<b>{{ $co['name'] }}</b>@endif @foreach ($co['lines'] as $line)<small>{{ $line }}</small>@endforeach</div>
            </div>
            <div class="dl-psheet__title"><h1>{{ $t($title) }}</h1>@if ($code)<b>{{ $code }}</b>@endif</div>
        </header>
        @if ($info)
            <div class="dl-psheet__info">@foreach ($info as $line)<div>{{ $line }}</div>@endforeach</div>
        @endif

        @foreach ($sections as $i => $s)
            <section class="dl-psheet__sec">
                @if (! empty($s['title']))<h2><span>{{ $s['no'] ?? $i + 1 }}</span>{{ $t($s['title']) }}</h2>@endif
                @foreach ($s['blocks'] ?? [] as $b)
                    @if (($b['type'] ?? 'table') === 'parties')
                        <div class="dl-psheet__parties" style="grid-template-columns:repeat({{ max(1, count($b['items'])) }},1fr)">
                            @foreach ($b['items'] as $p)
                                <div><small>{{ $t($p['label'] ?? '') }}</small>@foreach ($p['lines'] ?? [] as $line)<div>{{ $line }}</div>@endforeach</div>
                            @endforeach
                        </div>
                    @elseif (($b['type'] ?? 'table') === 'text')
                        <p class="dl-psheet__text">{{ $b['text'] ?? '' }}</p>
                    @else
                        @if (! empty($b['heading']))<h3>{{ $b['heading'] }}</h3>@endif
                        <table class="dl-psheet__tbl">
                            <colgroup>@foreach ($b['columns'] as $c)<col @if (! empty($c['width'])) style="width:{{ $c['width'] }}mm" @endif>@endforeach</colgroup>
                            <thead><tr>@foreach ($b['columns'] as $c)<th class="is-{{ $c['align'] ?? 'left' }}">{{ $t($c['label'] ?? '') }}</th>@endforeach</tr></thead>
                            <tbody>
                                @forelse ($b['rows'] ?? [] as $r)
                                    <tr>
                                        @foreach ($b['columns'] as $c)
                                            @php $v = $r[$c['key']] ?? ''; @endphp
                                            @continue($v === false)
                                            @php $v = $cell($v); @endphp
                                            <td class="is-{{ $c['align'] ?? 'left' }} {{ ! empty($c['mono']) ? 'is-mono' : '' }} {{ $v['class'] ?? '' }}" @if (! empty($v['rowspan'])) rowspan="{{ $v['rowspan'] }}" @endif>
                                                @if (! empty($v['bold']))<strong>{{ $v['text'] ?? '' }}</strong>@else{{ $v['text'] ?? '' }}@endif
                                                @if (! empty($v['sub']))<small>{{ $v['sub'] }}</small>@endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ count($b['columns']) }}" class="dl-psheet__empty">{{ $t($b['empty'] ?? 'No data.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @if (! empty($b['note']))<p class="dl-psheet__note">{{ $b['note'] }}</p>@endif
                    @endif
                @endforeach
            </section>
        @endforeach

        @if ($note)<p class="dl-psheet__note">{{ $note }}</p>@endif
        @if ($signatures)
            <div class="dl-pdoc__sign dl-psheet__sign" style="grid-template-columns:repeat({{ count($signatures) }},1fr)">
                @foreach ($signatures as $s)<div><span>{{ $t($s['role']) }}</span><i></i><b>({{ $s['name'] ?? '' }})</b></div>@endforeach
            </div>
        @endif
        <footer class="dl-pdoc__foot dl-psheet__foot"><span>{{ $footer ?? trim($t('Printed on').' '.\Devlib\Locale::stamp(now())) }}</span></footer>
    </section>
</div>
