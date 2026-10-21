{{-- Nested Grid, tingkat 1: semua Customer Order. Sel Order No., Customer membuka grid lain (grid ke grid saja, tanpa form). Aturan: skills/devlib/grid.md --}}
@php
    $nest = \App\Support\NestedDemo::class;
    $tones = ['Draft' => 'neutral', 'Confirmed' => 'success', 'Cancelled' => 'danger', 'Closed' => 'info'];
@endphp
<p class="dl-note">Sel bergaris bawah dengan panah (↗) adalah <b>tautan ke grid lain</b>: <b>Order No.</b> membuka item order itu, <b>Customer</b> membuka semua order customer itu. Di grid tujuan ada tautan lagi (Product → semua order berisi produk itu → Customer → ...), tingkatnya tidak dibatasi dan tidak pernah membuka form. Breadcrumb dan <b>Back</b> naik tingkat demi tingkat.</p>
<x-dl::grid id="nested-orders" mode="report" restore :rows="$nest::orders()" search="Search order no., customer..."
    :columns="[
        ['key' => 'co_no', 'label' => 'Order No.', 'url' => $nest::tpl('order', 'id'), 'ref' => true],
        ['key' => 'co_date', 'label' => 'Date', 'type' => 'date'],
        ['key' => 'customer', 'label' => 'Customer', 'url' => $nest::tpl('customer', 'customer_slug'), 'ref' => true],
        ['key' => 'ship_date', 'label' => 'Ship date', 'type' => 'date'],
        ['key' => 'total', 'label' => 'Total', 'type' => 'money', 'sum' => true],
        ['key' => 'status', 'label' => 'Status', 'type' => 'badge', 'tones' => $tones],
    ]"
    :filters="['customer' => 'Customer', 'status' => 'Status']" />
