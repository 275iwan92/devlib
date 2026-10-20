{{-- Lembar lampiran x-dl::print-doc (Document::appendices): blok facts / table / list / text, atau view milik aplikasi. --}}
<section class="dl-pdoc__sheet is-a4 dl-pdoc__appx" data-pd-sheet data-pd-type="{{ $doc->type }}" style="--pd-info-rows:{{ $doc->infoRows }};--pd-rh:{{ $doc->rowHeight }}mm;--pd-tail:{{ $doc->tailHeight }}mm">
    @if ($wm = $doc->watermark())<div class="dl-pdoc__wm {{ mb_strlen($wm) > 9 ? 'is-long' : '' }}" aria-hidden="true">{{ $wm }}</div>@endif
    @include('devlib::partials.print-doc-kop', ['title' => $t($ap['title']), 'subtitle' => $doc->data['number'] ?? null, 'compact' => true])
    <div class="dl-pdoc__appx-body">
        @foreach ($ap['blocks'] ?? [] as $b)
            <div class="dl-pdoc__blk">
                @if (! empty($b['title']))<h5>{{ $t($b['title']) }}</h5>@endif
                @switch($b['kind'])
                    @case('facts')
                        @foreach ($b['rows'] as $r)<div class="dl-pdoc__kv"><span>{{ $t($r[0]) }}</span><b>{{ $r[1] }}</b></div>@endforeach
                        @break
                    @case('table')
                        <table class="dl-pdoc__tbl">
                            <thead><tr>@foreach ($b['head'] as $i => $h)<th @class(['is-num' => in_array($i, $b['num'] ?? [], true)])>{{ $t($h) }}</th>@endforeach</tr></thead>
                            <tbody>@foreach ($b['rows'] as $r)<tr>@foreach ($r as $i => $v)<td @class(['is-num' => in_array($i, $b['num'] ?? [], true)])>{{ $v }}</td>@endforeach</tr>@endforeach</tbody>
                        </table>
                        @break
                    @case('list')
                        <ol class="dl-pdoc__ol">@foreach ($b['items'] as $it)<li>{{ $t($it) }}</li>@endforeach</ol>
                        @break
                    @case('text')
                        <p class="dl-pdoc__txt">{{ $b['text'] }}</p>
                        @break
                @endswitch
                @if (! empty($b['note']))<p class="dl-pdoc__cap">{{ $t($b['note']) }}</p>@endif
            </div>
        @endforeach
        @if (! empty($ap['view']))@include($ap['view'], $ap['data'] ?? [])@endif
    </div>
    <footer class="dl-pdoc__foot"><span>{{ $doc->printedLine() }}</span><span>{{ $t('Appendix') }} {{ $n }}/{{ $total }}</span></footer>
</section>
