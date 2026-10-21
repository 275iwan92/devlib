{{-- Nested Grid, contoh kedua (data lain, aturan sama): semua Purchase Order. PO No., Supplier membuka grid lain; jejak mulai dari daftar ini (home:purchases). --}}
@php
    $nest = \App\Support\NestedDemo::class;
    $home = $nest::home('purchases');
    $tones = ['Draft' => 'neutral', 'Confirmed' => 'success', 'Cancelled' => 'danger', 'Closed' => 'info'];
@endphp
<p class="dl-note">Same rules, different data: <b>PO No.</b> opens that purchase order's items, <b>Supplier</b> opens all purchase orders of that supplier, and an item opens every purchase order that contains it. Nothing here is specific to Customer Orders.</p>
<x-dl::grid id="nested-purchases" mode="report" restore :rows="$nest::purchases()" search="Search PO no., supplier..."
    :columns="[
        ['key' => 'po_no', 'label' => 'PO No.', 'url' => $nest::tpl('po', 'id', $home), 'ref' => true],
        ['key' => 'po_date', 'label' => 'Date', 'type' => 'date'],
        ['key' => 'supplier', 'label' => 'Supplier', 'url' => $nest::tpl('supplier', 'supplier_slug', $home), 'ref' => true],
        ['key' => 'items', 'label' => 'Items', 'type' => 'number'],
        ['key' => 'total', 'label' => 'Total', 'type' => 'money', 'sum' => true],
        ['key' => 'status', 'label' => 'Status', 'type' => 'badge', 'tones' => $tones],
    ]"
    :filters="['supplier' => 'Supplier', 'status' => 'Status']" />
