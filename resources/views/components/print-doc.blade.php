@php
    $t = fn (?string $s) => \Devlib\PrintDoc\Document::t($s);
    $d = $doc->data;
    $co = $doc->company();
    $party = $d['party'] ?? [];
    $cols = $doc->columns();
    $paper = $doc->paper();
    $totals = $doc->totals();
    $sign = $doc->signatures();
    $styled = $doc->styled();
    $stamp = '<span class="dl-pdoc__stamp is-'.$doc->statusTone().'">'.e($doc->statusLabel()).'</span>';
    $field = fn (?array $def) => $def
        ? '<div class="dl-pdoc__f" data-pd-k="'.e($def['key']).'"><span>'.e($t($def['label'])).'</span><i>:</i><b>'.$doc->value($def).'</b></div>'
        : '<div class="dl-pdoc__f"></div>';
@endphp
<div {{ $attributes->class('dl-pdoc') }} {!! $dl() !!} data-dl-pdoc data-paper="{{ $paper }}">
    @foreach ($doc->sheets() as $sheet)
        @include('devlib::partials.print-doc-sheet', ['cont' => $styled && $sheet['page'] > 1])
    @endforeach
    @if ($styled)<template data-pd-cont>@include('devlib::partials.print-doc-sheet', ['sheet' => ['rows' => [], 'from' => 0, 'last' => false, 'page' => 2, 'pages' => 2], 'cont' => true])</template>@endif
    @php($aps = $doc->appendices())
    @foreach ($aps as $ap)
        @include('devlib::partials.print-doc-appendix', ['ap' => $ap, 'n' => $loop->iteration, 'total' => count($aps)])
    @endforeach
</div>
