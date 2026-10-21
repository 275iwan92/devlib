{{-- Form Approval BAKU dalam alur Grid and Form (Jo 2026-10-21): bentuk Approval Center PO (x-dl::approval) untuk SATU dokumen yang dibuka dari grid; Back kembali ke grid. Demo tanpa penyimpanan: keputusan final (Setujui / Reject) disimulasikan lalu kembali ke grid; Hold tetap di form. --}}
@php
    $flow ??= null;
    $back = $flow['back'] ?? '/lib/p5-flow-approval';
    $docs = ($flow['row'] ?? null) ? [\App\Support\FlowDemo::approvalDoc($flow['row'])] : [];
@endphp
<div class="dl-row" style="margin-bottom:8px"><x-dl::back-button :fallback="$back" /></div>
<x-dl::approval single :back="$back" me="Operational Manager" approve-label="Setujui" :docs="$docs"
    :line-columns="['product_label' => 'Item', 'qty' => 'Qty', 'uom' => 'Satuan', 'price:money' => 'Harga/Unit', 'subtotal:money' => 'Subtotal']" />
