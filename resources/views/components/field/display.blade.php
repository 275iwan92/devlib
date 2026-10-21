@php
    $shown = match ($format) {
        'money' => ($value === null || $value === '') ? '—' : \Devlib\Locale::money($value),
        'number' => ($value === null || $value === '') ? '—' : \Devlib\Locale::number($value, str_contains((string) $value, '.') ? 2 : 0),
        'date' => $value ? \Devlib\Locale::date($value) : '—',
        default => ($value === null || $value === '') ? '—' : $value,
    };
@endphp
<div class="dl-display {{ in_array($format, ['money', 'number'], true) ? 'is-num' : '' }} {{ $format === 'mono' ? 'is-mono' : '' }}" id="{{ $uid }}" role="group" @if ($label !== '') aria-labelledby="{{ $uid }}-lbl" @endif>{{ $shown }}</div>
