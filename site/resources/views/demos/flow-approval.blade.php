{{-- Alur Approval: grid dokumen Confirmed yang menunggu keputusan -> Form Approval baku (hanya-baca; Reject / Hold / Setujui) -> Back kembali ke grid. Aturan: skills/devlib/grid-form-flow.md --}}
@php
    $slug = 'p5-flow-approval';
    $from = \App\Support\FlowDemo::gridUrl($slug);
    $label = ['waiting' => 'Awaiting', 'hold' => 'Hold', 'approved' => 'Approved', 'rejected' => 'Rejected'];
    $rows = array_map(fn ($r) => $r + ['approval_label' => $label[$r['approval']]], \App\Support\DemoData::approvalOrders());
@endphp
<p class="dl-note">Status dokumen dan status approval adalah <b>dua field terpisah</b>. Daftar ini memuat dokumen yang sudah <b>Confirmed</b>. Dokumen <b>Rejected</b> tidak tampil di tab biasa, hanya di tab <b>Rejected</b>. Klik 2x baris membuka Form Approval baku (detail hanya-baca; Reject / Hold / Setujui).</p>
<x-dl::grid id="flow-approval" kind="transactional" restore :rows="$rows" :new-record="null" search="Search order no., customer..."
    :open-url="\App\Support\FlowDemo::formUrl($slug, '{id}', '', $from)"
    :columns="[
        ['key' => 'co_no', 'label' => 'Order No. / Customer', 'type' => 'identity', 'code' => 'customer', 'avatar' => false],
        ['key' => 'co_date', 'label' => 'Date', 'type' => 'date'],
        ['key' => 'total', 'label' => 'Total', 'type' => 'money', 'sum' => true],
        ['key' => 'status', 'label' => 'Status', 'type' => 'badge', 'tones' => ['Confirmed' => 'success']],
        ['key' => 'approval_label', 'label' => 'Approval', 'type' => 'badge', 'tones' => ['Awaiting' => 'neutral', 'Hold' => 'warn', 'Approved' => 'success', 'Rejected' => 'danger']],
    ]"
    :tabs="[
        ['label' => 'All', 'where' => ['approval_label' => ['Awaiting', 'Hold', 'Approved']]],
        ['label' => 'Awaiting', 'where' => ['approval_label' => 'Awaiting']],
        ['label' => 'Hold', 'where' => ['approval_label' => 'Hold']],
        ['label' => 'Approved', 'where' => ['approval_label' => 'Approved']],
        ['label' => 'Rejected', 'where' => ['approval_label' => 'Rejected']],
    ]" />
