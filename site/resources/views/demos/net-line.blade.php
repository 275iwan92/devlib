{{-- x-dl::net-line: baris kecil di bawah nama pengguna (footer Layer Menu). Data fiktif; tombol Simulasi menggantikan jaringan putus sungguhan. --}}
@php
    app()->bind('devlib.net', fn () => ['client_ip' => '10.238.189.21', 'client_host' => 'PC-GUDANG', 'server_ip' => '10.238.189.159', 'server_host' => 'SRV-UTAMA']);
    $items = [
        ['label' => 'Sales', 'icon' => 'fa-cart-shopping', 'items' => [['key' => 'co', 'label' => 'Customer Order', 'icon' => 'fa-clipboard-list']]],
        ['label' => 'Inventory', 'icon' => 'fa-boxes-stacked', 'items' => [['key' => 'card', 'label' => 'Stock Card', 'icon' => 'fa-rectangle-list']]],
    ];
@endphp
<div class="dl-lm-demo">
    <x-dl::layer-menu id="demo-netline" :items="$items" user="Johannes Iwan" role="General Manager" login="jo@example.test" />
    <div class="dl-sm-demo__page"><div><b>Network line</b><br>Under the user name at the bottom of the left menu: client IP → server IP, small and faint. Touch or hover it to see the host names. Use the buttons below to see each state without cutting the network.</div></div>
</div>
@php
    unset(app()['devlib.net']);
@endphp
<x-dl::card title="Simulate" description="The buttons only change what this page shows; no request is blocked">
    <x-dl::action-bar align="start">
    <x-dl::button type="button" variant="secondary" label="Normal" data-nl-sim="ok" />
    <x-dl::button type="button" variant="secondary" label="Server unreachable" data-nl-sim="server" />
    <x-dl::button type="button" variant="secondary" label="Device offline" data-nl-sim="offline" />
    </x-dl::action-bar>
    <p class="dl-note">Normal: IP client → IP server. Server unreachable: the server IP blinks pink and "Server unreachable" appears; it clears by itself when the server answers again. Device offline: the client IP blinks yellow and "Device offline" appears. With reduced motion the blinking is off and only the colour and the text remain.</p>
</x-dl::card>
<x-dl::card title="Usage" description="The application binds the network data once; the Layer Menu uses the line when it is bound">
    <div class="dl-code__file">app/Providers/AppServiceProvider.php</div>
    <pre class="dl-code__pre"><code>app()->bind('devlib.net', fn () => \Devlib\NetworkInfo::of(request()));</code></pre>
    <div class="dl-code__file">anywhere else</div>
    <pre class="dl-code__pre"><code>&lt;x-dl::net-line client-ip="10.0.0.21" server-ip="10.0.0.5" ping-url="/session/ping" /&gt;</code></pre>
</x-dl::card>
