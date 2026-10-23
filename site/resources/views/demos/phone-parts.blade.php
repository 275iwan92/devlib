<div class="dl-demo">
    <x-dl::device-frame mode="mobile">
        <x-dl::phone-shell contained title="Home" active="home" :tabs="[['key' => 'home', 'label' => 'Home', 'icon' => 'fa-solid fa-house', 'url' => '#'], ['key' => 'stock', 'label' => 'Stock', 'icon' => 'fa-solid fa-boxes-stacked', 'url' => '#']]">
            <x-dl::phone-link label="Stock count in progress" icon="fa-solid fa-clipboard-check" tone="info" badge="3" />
            <x-dl::phone-tiles>
                <x-dl::phone-tile label="Active items" :value="27" icon="fa-solid fa-boxes-stacked" />
                <x-dl::phone-tile label="Low stock" :value="4" icon="fa-solid fa-triangle-exclamation" tone="danger" />
                <x-dl::phone-tile label="Near expiry" :value="2" icon="fa-solid fa-hourglass-end" tone="warn" />
            </x-dl::phone-tiles>
            <x-dl::phone-link label="Receiving" icon="fa-solid fa-truck-ramp-box" />
            <x-dl::phone-bar columns="1fr 1.5fr">
                <x-dl::button variant="secondary" label="Back" />
                <x-dl::button label="Next" />
            </x-dl::phone-bar>
        </x-dl::phone-shell>
    </x-dl::device-frame>
</div>
