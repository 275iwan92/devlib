{{-- Kop lembar cetak (x-dl::print-doc): perusahaan kiri, judul tengah, QR kanan. Dipakai lembar dokumen dan lembar lampiran. --}}
<header class="dl-pdoc__kop">
    <div class="dl-pdoc__co">
        @if (! $co['logo'])<span class="dl-pdoc__logo">{{ $co['initials'] }}</span>@endif
        <div>@if ($co['logo'])<img class="dl-pdoc__brand" src="{{ $co['logo'] }}" alt="{{ $co['name'] }}">@else<b>{{ $co['name'] }}</b>@endif @foreach ($co['lines'] as $line)<small>{{ $line }}</small>@endforeach @if ($co['npwp'])<small>NPWP {{ $co['npwp'] }}</small>@endif</div>
    </div>
    <h1 @class(['is-compact' => $compact ?? false])>{{ $title }}@if (! empty($subtitle))<small>{{ $subtitle }}</small>@endif</h1>
    <div class="dl-pdoc__kopr"><small>{{ $t('Scan for') }}<br>{{ $t($doc->qrHint) }}</small><span class="dl-pdoc__qr" data-pd-qr></span></div>
</header>
