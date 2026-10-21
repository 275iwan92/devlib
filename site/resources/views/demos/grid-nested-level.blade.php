{{-- Nested Grid, tingkat berikutnya (customer | order | product): judul ringkas + grid isinya; sel bertautan membuka grid lain. Back = naik satu tingkat. --}}
@php
    $nest = \App\Support\NestedDemo::class;
    $n = $nested;
    $id = 'nested-'.$n['type'].'-'.preg_replace('/\W+/', '-', $n['key']);
    $tones = ['Draft' => 'neutral', 'Confirmed' => 'success', 'Cancelled' => 'danger', 'Closed' => 'info'];
@endphp
<div class="dl-row" style="margin-bottom:8px">
    <x-dl::button size="sm" variant="secondary" icon="back" label="Back" :href="$n['back']" />
</div>
<x-dl::card :title="$n['kind'].': '.$n['title']" :description="$n['sub']" />
@if ($n['type'] === 'customer')
    <x-dl::grid :id="$id" mode="report" restore :rows="$n['rows']" search="Search order no..."
        :columns="[
            ['key' => 'co_no', 'label' => 'Order No.', 'url' => $nest::tpl('order', 'id', $n['chain']), 'ref' => true],
            ['key' => 'co_date', 'label' => 'Date', 'type' => 'date'],
            ['key' => 'ship_date', 'label' => 'Ship date', 'type' => 'date'],
            ['key' => 'total', 'label' => 'Total', 'type' => 'money', 'sum' => true],
            ['key' => 'status', 'label' => 'Status', 'type' => 'badge', 'tones' => $tones],
        ]" />
@elseif ($n['type'] === 'order')
    <x-dl::grid :id="$id" mode="report" :rows="$n['rows']" search="Search product..."
        :columns="[
            ['key' => 'product_label', 'label' => 'Product', 'url' => $nest::tpl('product', 'product', $n['chain']), 'ref' => true],
            ['key' => 'qty', 'label' => 'Qty', 'type' => 'number', 'sum' => true],
            ['key' => 'uom', 'label' => 'Unit'],
            ['key' => 'price', 'label' => 'Unit price', 'type' => 'money'],
            ['key' => 'subtotal', 'label' => 'Subtotal', 'type' => 'money', 'sum' => true],
        ]" />
@else
    <x-dl::grid :id="$id" mode="report" restore :rows="$n['rows']" search="Search order no., customer..."
        :columns="[
            ['key' => 'co_no', 'label' => 'Order No.', 'url' => $nest::tpl('order', 'id', $n['chain']), 'ref' => true],
            ['key' => 'customer', 'label' => 'Customer', 'url' => $nest::tpl('customer', 'customer_slug', $n['chain']), 'ref' => true],
            ['key' => 'qty', 'label' => 'Qty', 'type' => 'number', 'sum' => true],
            ['key' => 'uom', 'label' => 'Unit'],
            ['key' => 'price', 'label' => 'Unit price', 'type' => 'money'],
        ]" />
@endif
