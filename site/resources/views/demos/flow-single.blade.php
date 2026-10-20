{{-- Alur Single transaksional: grid Cash Voucher -> Single Form (satu record, tanpa baris) -> Back kembali ke grid. Aturan: skills/devlib/grid-form-flow.md --}}
@php
    $slug = 'p5-flow-single';
    $from = \App\Support\FlowDemo::gridUrl($slug);
    $tones = ['Draft' => 'neutral', 'Confirmed' => 'success', 'Cancelled' => 'danger'];
@endphp
<p class="dl-note">Klik 2x baris (atau ikon Buka) membuka <b>Single Form</b> di halaman sendiri; <b>+ New Record</b> membuka form kosong berisi nilai bawaan. <b>Save</b> dan <b>Confirm</b> tetap di form; hanya <b>Back</b> kembali ke daftar ini (kata cari, halaman, dan filter sama seperti sebelumnya).</p>
<x-dl::grid id="flow-single" kind="transactional" restore :rows="\App\Support\DemoData::cashVouchers()" search="Search voucher no., payee..."
    :open-url="\App\Support\FlowDemo::formUrl($slug, '{id}', '', $from)" :new-url="\App\Support\FlowDemo::formUrl($slug, 'new', '', $from)"
    :columns="[
        ['key' => 'cv_no', 'label' => 'Voucher No. / Payee', 'type' => 'identity', 'code' => 'payee', 'avatar' => false],
        ['key' => 'cv_date', 'label' => 'Date', 'type' => 'date'],
        ['key' => 'category', 'label' => 'Category'],
        ['key' => 'paid_from', 'label' => 'Paid from'],
        ['key' => 'amount', 'label' => 'Amount', 'type' => 'money', 'sum' => true],
        ['key' => 'status', 'label' => 'Status', 'type' => 'badge', 'tones' => $tones],
    ]"
    :tabs="[['label' => 'All'], ['label' => 'Draft', 'where' => ['status' => 'Draft']], ['label' => 'Confirmed', 'where' => ['status' => 'Confirmed']], ['label' => 'Cancelled', 'where' => ['status' => 'Cancelled']]]"
    :filters="['category' => 'Category', 'paid_from' => 'Paid from']" />
