{{-- Nested Grid, tingkat 1: semua Customer Order. Sel Order No., Customer membuka grid lain (grid ke grid saja, tanpa form). Aturan: skills/devlib/grid.md --}}
@php
    $nest = \App\Support\NestedDemo::class;
    $home = $nest::home('orders');
    $tones = ['Draft' => 'neutral', 'Confirmed' => 'success', 'Cancelled' => 'danger', 'Closed' => 'info'];
@endphp
<p class="dl-note">Underlined cells with an arrow (↗) are <b>links to another grid</b>: <b>Order No.</b> opens that order's items, <b>Customer</b> opens all orders of that customer. The next grid has more links (Product → every order containing it → Customer → ...); levels are unlimited and never open a form. The breadcrumb and <b>Back</b> (Alt+↑) go up level by level, and the grid you left comes back as you left it.</p>
<x-dl::grid id="nested-orders" mode="report" restore :rows="$nest::orders()" search="Search order no., customer..."
    :columns="[
        ['key' => 'co_no', 'label' => 'Order No.', 'url' => $nest::tpl('order', 'id', $home),'ref' => true],
        ['key' => 'co_date', 'label' => 'Date', 'type' => 'date'],
        ['key' => 'customer', 'label' => 'Customer', 'url' => $nest::tpl('customer', 'customer_slug', $home),'ref' => true],
        ['key' => 'ship_date', 'label' => 'Ship date', 'type' => 'date'],
        ['key' => 'total', 'label' => 'Total', 'type' => 'money', 'sum' => true],
        ['key' => 'status', 'label' => 'Status', 'type' => 'badge', 'tones' => $tones],
    ]"
    :filters="['customer' => 'Customer', 'status' => 'Status']" />
