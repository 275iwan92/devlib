@php
    // Nilai yang ditolak browser (#fff, kosong, nama warna) jatuh ke bawaan; #rgb diperluas jadi #rrggbb.
    $hex = (string) $value;
    if (preg_match('/^#([0-9a-f])([0-9a-f])([0-9a-f])$/i', $hex, $m)) {
        $hex = '#'.$m[1].$m[1].$m[2].$m[2].$m[3].$m[3];
    }
    if (! preg_match('/^#[0-9a-f]{6}$/i', $hex)) {
        $hex = '#2563eb';
    }
@endphp
<div class="dl-color">
    <input id="{{ $uid }}" name="{{ $name }}" type="color" class="dl-color__btn" value="{{ $hex }}" data-dl-color @disabled($disabled || $readonly)>
    @if ($readonly && ! $disabled)<input type="hidden" name="{{ $name }}" value="{{ $hex }}">@endif
    <span class="dl-color__code" data-dl-color-code>{{ strtoupper($hex) }}</span>
</div>
