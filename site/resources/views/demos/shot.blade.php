@php
    $img = 'data:image/svg+xml;utf8,'.rawurlencode("<svg xmlns='http://www.w3.org/2000/svg' width='960' height='540'><rect width='960' height='540' fill='aliceblue'/><rect x='40' y='30' width='500' height='36' rx='6' fill='white' stroke='gray'/><rect x='780' y='30' width='140' height='36' rx='6' fill='royalblue'/><rect x='40' y='100' width='880' height='34' fill='gainsboro'/><rect x='40' y='150' width='880' height='34' fill='white'/></svg>");
@endphp
<div class="dl-demo" style="display:grid;grid-template-columns:repeat(2,minmax(0,320px));gap:16px">
    <x-dl::shot :src="$img" :x="89" :y="11" :zoom="2.4" />
    <x-dl::shot :src="$img" :x="30" :y="11" :zoom="2" />
</div>
