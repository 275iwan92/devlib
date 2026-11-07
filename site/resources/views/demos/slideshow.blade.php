@php
    // Gambar contoh: SVG sederhana (warna bernama, tanpa hex) supaya demo tidak butuh berkas gambar.
    $slide = fn (string $label, string $fill) => 'data:image/svg+xml;utf8,'.rawurlencode("<svg xmlns='http://www.w3.org/2000/svg' width='640' height='320'><rect width='640' height='320' fill='{$fill}'/><text x='320' y='170' font-size='36' text-anchor='middle' fill='black'>{$label}</text></svg>");
@endphp
<div class="dl-demo dl-demo--row">
    <x-dl::button label="Guide" icon="fa-circle-play" data-dl-open="sl-demo" />
</div>
<x-dl::slideshow id="sl-demo" title="Sample guide" :items="[
    ['src' => $slide('Step 1', 'aliceblue'), 'caption' => 'Step 1: open the screen and press the button.'],
    ['src' => $slide('Step 2', 'gainsboro'), 'caption' => 'Step 2: choose the reason and confirm.'],
    ['src' => $slide('Step 3', 'honeydew'), 'caption' => 'Step 3: the result is shown.'],
]" />
