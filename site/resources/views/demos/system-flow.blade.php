{{-- Contoh x-dl::flow: System Flow = bagaimana modul menyerahkan dokumen satu ke yang lain (data fiktif). Status blok = kemajuan, cabang = jalur lain. --}}
<div class="dl-demo">
    <x-dl::flow title="Customer Order to cash" source="fictional sample" :steps="[
        ['key' => 'co', 'label' => 'Customer Order', 'module' => 'Sales', 'icon' => 'fa-file-signature', 'status' => 'done', 'note' => 'Confirmed order starts the flow'],
        ['key' => 'rsv', 'label' => 'Stock Reservation', 'module' => 'Inventory', 'icon' => 'fa-boxes-stacked', 'status' => 'done', 'note' => 'Reserves stock per item and batch', 'branch' => [['label' => 'Short stock: Purchase Request', 'icon' => 'fa-cart-shopping', 'status' => 'review']]],
        ['key' => 'do', 'label' => 'Delivery Order', 'module' => 'Logistics', 'icon' => 'fa-truck-fast', 'status' => 'review', 'note' => 'Prepared from the reservation'],
        ['key' => 'inv', 'label' => 'Customer Invoice', 'module' => 'Finance', 'icon' => 'fa-file-invoice-dollar', 'status' => 'todo', 'note' => 'Created when the delivery is closed'],
        ['key' => 'pay', 'label' => 'Payment', 'module' => 'Finance', 'icon' => 'fa-money-bill-wave', 'status' => 'locked', 'note' => 'Locked until the invoice is posted'],
    ]" />
</div>
