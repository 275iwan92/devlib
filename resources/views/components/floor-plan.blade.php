@php
    $rows = $legendRows();
    $has = fn ($slot) => isset($slot) && $slot->isNotEmpty();
@endphp
<div {{ $attributes->class(['dl-floorplan', 'is-readonly' => $readonly]) }} {!! $dl() !!} data-dl-floorplan data-selected="{{ $selected }}">
    @if ($has($summary ?? null))<div class="dl-floorplan__summary">{{ $summary }}</div>@endif
    <div class="dl-floorplan__plan" style="aspect-ratio: {{ $ratio() }}" role="group" @if ($label) aria-label="{{ $label }}" @endif>
        @if ($image)
            <img class="dl-floorplan__img" src="{{ $image }}" alt="{{ $label }}">
        @else
            @foreach ($rooms as $r)
                <div class="dl-floorplan__room" style="left: {{ $r['x'] ?? 0 }}%; top: {{ $r['y'] ?? 0 }}%; width: {{ $r['w'] ?? 10 }}%; height: {{ $r['h'] ?? 10 }}%"><b>{{ $r['code'] ?? '' }}</b> {{ $r['label'] ?? '' }}</div>
            @endforeach
        @endif
        @foreach ($pins as $p)
            @php
                $pid = (string) ($p['id'] ?? $loop->index);
                $on = $selected !== null && $selected === $pid;
                $st = $status($p);
                $text = trim(($p['label'] ?? '').($st !== '' ? ', '.$st : ''), ', ');
                $tag = ! empty($p['href']) ? 'a' : ($readonly ? 'span' : 'button');
            @endphp
            <{{ $tag }} class="dl-floorplan__pin is-{{ $tone($p) }}{{ $on ? ' is-selected' : '' }}" style="left: {{ $p['x'] ?? 0 }}%; top: {{ $p['y'] ?? 0 }}%"
                title="{{ $p['title'] ?? str_replace(', ', ' · ', $text) }}" aria-label="{{ $text }}" data-pin="{{ $pid }}"
                @if ($tag === 'a') href="{{ $p['href'] }}" @if ($on) aria-current="true" @endif
                @elseif ($tag === 'button') type="button" aria-pressed="{{ $on ? 'true' : 'false' }}"
                @else role="img" @endif>{{ $p['number'] ?? $loop->iteration }}</{{ $tag }}>
        @endforeach
    </div>
    @if ($rows)
        <ul class="dl-floorplan__legend">
            @foreach ($rows as $l)
                <li><i class="dl-floorplan__key is-{{ $tone($l) }}"></i>{{ $l['label'] ?? '' }}</li>
            @endforeach
        </ul>
    @endif
    @if ($name)<input type="hidden" name="{{ $name }}" value="{{ $selected }}" data-floorplan-input>@endif
    @if ($has($detail ?? null))<div class="dl-floorplan__detail">{{ $detail }}</div>@endif
</div>
