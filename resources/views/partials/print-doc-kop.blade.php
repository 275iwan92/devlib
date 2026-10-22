{{-- Kop lembar cetak (x-dl::print-doc): perusahaan kiri, judul tengah, QR kanan. Dipakai lembar dokumen dan lembar lampiran. --}}
<header class="dl-pdoc__kop">
    @include('devlib::partials.print-doc-co', ['co' => $co, 'npwp' => true])
    <h1 @class(['is-compact' => $compact ?? false])>{{ $title }}@if (! empty($subtitle))<small>{{ $subtitle }}</small>@endif</h1>
    <div class="dl-pdoc__kopr"><small>{{ $t('Scan for') }}<br>{{ $t($doc->qrHint) }}</small><span class="dl-pdoc__qr" data-pd-qr></span></div>
</header>
