<div class="dl-demo">
    <x-dl::device-frame mode="mobile">
        <x-dl::phone-shell contained title="Count Stock" back="#" active="count" :user="['name' => 'Sample User', 'role' => 'Data Entry']" logout-action="#" device-action="#"
            :tabs="[
                ['key' => 'home', 'label' => 'Home', 'icon' => 'fa-solid fa-house', 'url' => '#'],
                ['key' => 'stock', 'label' => 'Stock', 'icon' => 'fa-solid fa-boxes-stacked', 'url' => '#'],
                ['key' => 'trx', 'label' => 'Transactions', 'icon' => 'fa-solid fa-right-left', 'url' => '#'],
                ['key' => 'count', 'label' => 'Count', 'icon' => 'fa-solid fa-clipboard-check', 'url' => '#'],
            ]"
            :modules="[
                ['code' => 'MST', 'label' => 'Master Data', 'icon' => 'fa-solid fa-boxes-stacked', 'children' => [
                    ['code' => 'ITM', 'label' => 'Items', 'url' => '#', 'icon' => 'fa-solid fa-box'],
                    ['code' => 'SUP', 'label' => 'Suppliers', 'url' => '#', 'icon' => 'fa-solid fa-truck'],
                ]],
                ['code' => 'TRX', 'label' => 'Transactions', 'icon' => 'fa-solid fa-right-left', 'children' => [
                    ['code' => 'RCV', 'label' => 'Receiving', 'url' => '#', 'icon' => 'fa-solid fa-truck-ramp-box'],
                    ['code' => 'ISS', 'label' => 'Issuance', 'url' => '#', 'icon' => 'fa-solid fa-dolly'],
                ]],
            ]">
            <x-dl::alert tone="info" message="Content of the screen goes here, full width. Tap Menu to open the module list." />
        </x-dl::phone-shell>
    </x-dl::device-frame>
</div>
