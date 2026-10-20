{{-- Alur Dokumen Master-Detail: grid transaksional CO / PO -> Form Master-Detail (header + x-dl::lines) -> Back kembali ke grid yang sama (?doc=). Aturan: skills/devlib/grid-form-flow.md --}}
@php
    $slug = 'p5-flow-document';
    $doc = \App\Support\FlowDemo::doc($slug, request('doc'));
    $from = \App\Support\FlowDemo::gridUrl($slug, $doc);
    $co = $doc === 'co';
    $tones = ['Draft' => 'neutral', 'Confirmed' => 'success', 'Cancelled' => 'danger', 'Closed' => 'info'];
@endphp
<div class="dl-row" style="margin-bottom:8px">
    <span class="dl-note" style="margin:0">Document:</span>
    @foreach (['co' => 'Customer Order', 'po' => 'Purchase Order'] as $dk => $dn)
        <x-dl::button size="sm" :variant="$dk === $doc ? 'primary' : 'secondary'" :label="$dn" :href="\App\Support\FlowDemo::gridUrl($slug, $dk)" />
    @endforeach
</div>
<p class="dl-note">Contoh #1 <b>Customer Order</b> (Rupiah, pajak, biaya lain), contoh #2 <b>Purchase Order</b>. Klik 2x baris (atau ikon Buka) membuka <b>form dokumen</b> di halaman sendiri; <b>Save</b> dan <b>Confirm</b> tetap di form, hanya <b>Back</b> kembali ke daftar ini pada dokumen yang sama. Confirm / Cancel tidak ada di grid, hanya di form.</p>
@if ($co)
    <x-dl::grid id="flow-co" kind="transactional" restore :rows="\App\Support\DemoData::customerOrders()" search="Cari no. CO, customer..."
        :open-url="\App\Support\FlowDemo::formUrl($slug, '{id}', $doc, $from)" :new-url="\App\Support\FlowDemo::formUrl($slug, 'new', $doc, $from)"
        :columns="[
            ['key' => 'co_no', 'label' => 'No. CO / Customer', 'type' => 'identity', 'code' => 'customer', 'avatar' => false],
            ['key' => 'co_date', 'label' => 'Tanggal', 'type' => 'date'],
            ['key' => 'ship_date', 'label' => 'Rencana Kirim', 'type' => 'date'],
            ['key' => 'total', 'label' => 'Total', 'type' => 'money', 'sum' => true],
            ['key' => 'status', 'label' => 'Status', 'type' => 'badge', 'tones' => $tones],
        ]"
        :tabs="[['label' => 'Semua'], ['label' => 'Draft', 'where' => ['status' => 'Draft']], ['label' => 'Confirmed', 'where' => ['status' => 'Confirmed']], ['label' => 'Closed / Cancelled', 'where' => ['status' => ['Closed', 'Cancelled']]]]"
        :filters="['customer' => 'Customer']" />
@else
    <x-dl::grid id="flow-po" kind="transactional" restore :rows="\App\Support\DemoData::purchaseOrders()" search="Cari no. PO, supplier..."
        :open-url="\App\Support\FlowDemo::formUrl($slug, '{id}', $doc, $from)" :new-url="\App\Support\FlowDemo::formUrl($slug, 'new', $doc, $from)"
        :columns="[
            ['key' => 'po_no', 'label' => 'No. PO / Supplier', 'type' => 'identity', 'code' => 'supplier', 'avatar' => false],
            ['key' => 'po_date', 'label' => 'Tanggal', 'type' => 'date'],
            ['key' => 'requester', 'label' => 'Pemohon'],
            ['key' => 'total', 'label' => 'Total', 'type' => 'money', 'sum' => true],
            ['key' => 'status', 'label' => 'Status', 'type' => 'badge', 'tones' => $tones],
        ]"
        :tabs="[['label' => 'Semua'], ['label' => 'Draft', 'where' => ['status' => 'Draft']], ['label' => 'Confirmed', 'where' => ['status' => 'Confirmed']], ['label' => 'Closed / Cancelled', 'where' => ['status' => ['Closed', 'Cancelled']]]]"
        :filters="['supplier' => 'Supplier']" />
@endif
