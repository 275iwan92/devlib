<form {{ $attributes->class('dl-pq') }} {!! $dl() !!} data-dl-pq data-pq-filter="{{ $filter }}" method="post" @if ($action) action="{{ $action }}" @endif
    data-pq-button="{{ $button }}" data-pq-button-one="{{ $buttonOne }}" data-pq-button-partial="{{ $buttonPartial }}"
    data-pq-selected="{{ $selected }}" data-pq-together="{{ $together }}" data-pq-mismatch="{{ $mismatch }}">
    @if ($action)@csrf @endif
    @if ($title)<h2 class="dl-pq__title">{{ $title }}</h2>@endif
    <div class="dl-pq__chips" role="group">
        @foreach ($filters as $f)
            <button type="button" class="dl-pq__chip{{ $f['key'] === $filter ? ' is-active' : '' }}" data-pq-chip="{{ $f['key'] }}" data-pq-states="{{ ($f['states'] ?? null) === null ? '*' : implode(',', $f['states']) }}" aria-pressed="{{ $f['key'] === $filter ? 'true' : 'false' }}">{{ $f['label'] }} <b>{{ $count($f) }}</b></button>
        @endforeach
    </div>
    <div class="dl-pq__bar" data-pq-bar hidden role="status">
        <span class="dl-pq__sum" data-pq-sum></span>
        <span class="dl-pq__note" data-pq-note></span>
        <button type="submit" class="dl-btn dl-btn--primary dl-pq__go" data-pq-go></button>
    </div>
    <div class="dl-pq__head" aria-hidden="true"><span></span><span>{{ $rowHead }}</span><span>{{ $qtyHead }}</span><span>{{ $dueHead }}</span></div>
    <ul class="dl-pq__list">
        @foreach ($rows as $r)
            @php
                $st = $states[$r['state'] ?? ''] ?? ['label' => (string) ($r['state'] ?? ''), 'tone' => 'neutral'];
                $have = (float) ($r['have'] ?? 0);
                $want = (float) ($r['want'] ?? 0);
                $pct = $want > 0 ? min(100, $have / $want * 100) : 0;
                $pick = $pickable($r);
                $due = $dueText($r);
            @endphp
            <li class="dl-pq__row{{ $pick ? '' : ' is-off' }}" data-pq-row data-pq-state="{{ $r['state'] ?? '' }}" data-pq-group="{{ $r['group'] ?? '' }}" data-pq-party="{{ $r['party'] ?? '' }}"
                data-pq-full="{{ $have >= $want ? '1' : '0' }}" data-pq-have="{{ \Devlib\Locale::decimal($have, 2) }}" data-pq-want="{{ \Devlib\Locale::decimal($want, 2) }}" data-pq-unit="{{ $r['unit'] ?? '' }}" @if (! $shown($r)) hidden @endif>
                <label class="dl-pq__pick"><input type="checkbox" name="{{ $name }}[]" value="{{ $r['id'] }}" @disabled(! $pick) aria-label="{{ $r['title'] ?? '' }}"><i></i></label>
                <div class="dl-pq__main"><b>{{ $r['title'] ?? '' }}</b><span>{{ $r['party'] ?? '' }}@if (! empty($r['place'])) · {{ $r['place'] }}@endif</span></div>
                <div class="dl-pq__qty">
                    <x-dl::badge :tone="$st['tone']" :label="\Devlib\Lang::text($st['label'])" />
                    <x-dl::progress :value="$pct" :tone="$st['tone']" />
                    <span class="dl-pq__nums">{{ \Devlib\Locale::decimal($have, 2) }} / {{ \Devlib\Locale::decimal($want, 2) }} {{ $r['unit'] ?? '' }}@if (! empty($r['note'])) <small>· {{ $r['note'] }}</small>@endif</span>
                </div>
                <div class="dl-pq__due{{ isset($r['dueIn']) && $r['dueIn'] < 0 ? ' is-late' : '' }}"><span>{{ $r['due'] ?? '' }}</span>@if ($due)<small>{{ $due }}</small>@endif</div>
            </li>
        @endforeach
    </ul>
    <p class="dl-pq__empty" data-pq-empty hidden>{{ $empty }}</p>
</form>
