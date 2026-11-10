@php
    // Tangkapan layar contoh: SVG sederhana (warna bernama, tanpa hex) supaya demo tidak butuh berkas gambar.
    $shot = 'data:image/svg+xml;utf8,'.rawurlencode("<svg xmlns='http://www.w3.org/2000/svg' width='960' height='540'><rect width='960' height='540' fill='aliceblue'/><rect x='40' y='30' width='500' height='36' rx='6' fill='white' stroke='gray'/><rect x='780' y='30' width='140' height='36' rx='6' fill='royalblue'/><rect x='40' y='100' width='880' height='34' fill='gainsboro'/><rect x='40' y='150' width='880' height='34' fill='white'/><rect x='40' y='200' width='880' height='34' fill='white'/></svg>");
@endphp
<div class="dl-demo">
    <x-dl::tour key="demo" title="Sample missions" :missions="[
        ['id' => 'demo-list', 'group' => 'Sales', 'title' => 'Read the list', 'summary' => 'Search, create and open a record.', 'image' => $shot, 'width' => 960, 'height' => 540, 'steps' => [
            [30, 11, 'Search box', 'Type a number or a name; the list narrows as you type.'],
            [89, 11, 'New record', 'Press to create a record.'],
            [50, 28, 'Column headings', 'Click a heading to sort.'],
            [50, 40, 'A row', 'Click a row to open the record.'],
        ]],
        ['id' => 'demo-two', 'group' => 'Sales', 'title' => 'Second mission', 'summary' => 'Shows the next-mission button.', 'image' => $shot, 'width' => 960, 'height' => 540, 'steps' => [
            [50, 40, 'A row', 'Click a row to open the record.'],
        ]],
    ]" />
</div>
