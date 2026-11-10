@php
    // Tangkapan layar contoh: SVG sederhana (warna bernama, tanpa hex) supaya demo tidak butuh berkas gambar.
    $shot = 'data:image/svg+xml;utf8,'.rawurlencode("<svg xmlns='http://www.w3.org/2000/svg' width='960' height='540'><rect width='960' height='540' fill='aliceblue'/><rect x='40' y='30' width='500' height='36' rx='6' fill='white' stroke='gray'/><rect x='780' y='30' width='140' height='36' rx='6' fill='royalblue'/><rect x='40' y='100' width='880' height='34' fill='gainsboro'/><rect x='40' y='150' width='880' height='34' fill='white'/></svg>");
@endphp
<div class="dl-demo">
    <x-dl::chapters title="Sample guide" present-url="#present-{id}" :groups="['sales' => ['title' => 'Sales', 'tag' => 'Orders', 'hue' => 150], 'hr' => ['title' => 'People', 'tag' => 'Staff', 'hue' => 330]]" :tasks="[
        ['id' => 'list', 'group' => 'sales', 'title' => 'Read the order list', 'summary' => 'Search, create and open an order.', 'image' => $shot, 'pin' => true,
         'flow' => ['Search', 'Sort', 'Open'], 'tips' => ['Double-click a row to open it.'], 'related' => ['staff'],
         'steps' => [[30, 11, 'Search box', 'Type a number or a name.'], [89, 11, 'New record', 'Press to create an order.'], [50, 28, 'A row', 'Double-click to open.']]],
        ['id' => 'staff', 'group' => 'hr', 'title' => 'Find a person', 'summary' => 'Look a person up by name.', 'image' => $shot,
         'flow' => ['Type a name', 'Open the record'], 'steps' => [[30, 11, 'Search box', 'Type part of the name.']]],
    ]" />
</div>
