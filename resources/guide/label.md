## Overview

`<x-dl::label>` is one warehouse sticker at real print size: a coloured top band (kind icon, kind name, a mark such as the arrival month), a big code, an optional QR code (phone camera) and a 1D barcode (laser scanner), a name, a highlighted quantity, key/value lines, tags such as `Expired`, and a small footer. Three kinds look deliberately different so a picker can tell them apart at a glance, even on a black-and-white printer (the band text is printed too): `location` (bin / rack), `item` (the content of one bin), `pallet` (one pallet).

Use it inside `x-dl::label-sheet` (the print sheet: label roll or A4, with a Print button that prints only the labels). Do NOT use it:

- for a document print-out: `x-dl::print-doc` / `x-dl::print-out`.
- for a UI status tag on screen: `x-dl::badge`.
- for a bare QR or barcode: `x-dl::qr` / `x-dl::barcode`.
- for explaining sizes and colours to the operator: `x-dl::label-guide`.

Generated markup (parts are optional, see Props):

```html
<div class="dl-label dl-label--md dl-label--danger dl-label--item" data-color="accent" data-dl="x-dl::label size=md kind=item" data-dl-class="Label ← DevlibComponent" data-dl-src="—">
  <div class="dl-label__band"><i class="fa-solid fa-tag"></i><b>Item</b><span>Raw Material</span></div>
  <div class="dl-label__main">
    <span class="dl-label__qr" data-dl-qrcode data-text="ITM:RM:KR-140" data-size="112"></span>
    <div class="dl-label__body">
      <b class="dl-label__code">KR-140</b>
      <span class="dl-label__name">White Kraft 140 gsm 79 x 97</span>
      <b class="dl-label__hi">15,000 LBR</b>
      <dl class="dl-label__lines"><div><dt>Bin</dt><dd>A10-PPR-L03-01</dd></div></dl>
      <span class="dl-label__tags"><em class="dl-label__tag dl-label__tag--danger">Dead stock</em></span>
      <span class="dl-label__bar"><svg data-dl-barcode data-value="KR-140" data-format="CODE128" data-height="28" data-width="1.3" data-text="0" role="img" aria-label="Barcode KR-140"></svg></span>
      <small class="dl-label__foot">footer</small>
    </div>
  </div>
</div>
```

Extra HTML attributes (`class`, `id`, `style`, `data-*`) and `can="module.action"` go on the root `div`; without the ability nothing is rendered.

## Props

Constructor of `Devlib\Components\Label`: 14 props, none inherited. Use `:` for arrays (`:lines`, `:tags`).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `code` | `string`, `''` | The big bold code (bin code, item code, pallet number). Printed even when empty (an empty bold line). Data, not translated; long codes break anywhere (`word-break: break-all`). |
| `title` | `?string`, `null` | Text in the band (the kind name). `null` with a valid `kind` becomes the kind name (`Location`, `Item`, `Pallet`); without `kind` it stays `null`. The only translated prop (dictionary). The band is drawn when `title` or `mark` is truthy. |
| `name` | `?string`, `null` | Description under the code, clamped to two lines (one line on pallet labels that are not `lg`). Not translated. |
| `lines` | `array`, `[]` | Key/value lines: `[['label' => 'Max weight', 'value' => '3,000 kg'], ...]`. `label` goes through the dictionary at render, `value` is data as given. Missing keys print an empty string. Rendered as a `<dl>` of `<div><dt><dd>`; wraps in several columns. |
| `qr` | `?string`, `null` | Text encoded in the QR code (suggest a typed prefix: `LOC:...`, `ITM:...`, `PLT:...`). `null` = no QR. Size is fixed by `size` (62 / 112 / 150 px). Drawn in the browser by the library `qrcodejs` loaded from cdnjs. |
| `barcode` | `?string`, `null` | Value of the 1D barcode, always `CODE128` (no other format). `null` = none. Bar height 18 / 28 / 44 px and module width 1 / 1.3 / 1.3 for `sm` / `md` / `lg`. The readable text under the bars is turned off (`data-text="0"`); the big code above is the readable text. Drawn by `JsBarcode` (cdnjs). |
| `size` | `string`, `'md'` | `sm` 60 x 40 mm, `md` 100 x 50 mm (default), `lg` 100 x 150 mm: the real sticker size. An unknown value silently becomes `md`. Adds `dl-label--<size>`; also in `data-dl`. `lg` stacks the content vertically and centres it. |
| `tone` | `?string`, `null` | Adds `dl-label--<tone>`. Only `danger` is styled: a thick (5 mm) red left edge for dead stock or expired. Other values are written as a class with no style. |
| `footer` | `?string`, `null` | Small line at the bottom of the body (instructions such as "After every use: print a new label"). Not translated. |
| `kind` | `?string`, `null` | `location` (icon `fa-location-dot`), `item` (`fa-tag`), `pallet` (`fa-pallet`, thicker 2 px border). Adds `dl-label--<kind>` and puts the icon in the band. Unknown values become `null`. |
| `color` | `?string`, `null` | Colour of the band, via `data-color` (default `neutral` when empty). Styled: `accent`, `green`, `tangerine`, `violet`, `lime`, `pink`, `navy`, `yellow` (dark text), `teal`, `orange`, `amber`, `danger`; `neutral` and unknown values use the default dark tab colour. Red is reserved for dead stock / expiry, so month colours skip it. |
| `mark` | `?string`, `null` | Short text at the right end of the band, e.g. the arrival month (`OCT 2026`) or the category (`Raw Material`). Not translated. Also makes the band appear without a `title`. |
| `highlight` | `?string`, `null` | The second biggest line, e.g. the quantity with unit (`15,000 LBR`). Not translated. |
| `tags` | `array`, `[]` | Small framed words: `[['label' => 'Expired', 'tone' => 'danger'], ...]`. `tone`: `danger` (red fill, white text), `warn` (yellow fill), `info` (default, outline only). `label` is translated at render. |

Constants: `Label::SIZES`, `Label::SPEC` (width, height in mm and number of labels per A4 sheet: 21 / 10 / 2), `Label::KINDS`, `Label::MONTH_COLORS`, and the helper `Label::monthColor('2026-10-02')` which returns the colour name of the arrival month (Jan accent, Feb green, Mar tangerine, Apr violet, May lime, Jun pink, Jul navy, Aug yellow, Sep teal, Oct orange, Nov amber, Dec neutral; empty or invalid date gives `neutral`).

## Behavior

- Real size: the label uses millimetres and points, not pixels, so it prints at the stated size. `-webkit-print-color-adjust: exact` keeps the band colours; `break-inside: avoid` keeps one label on one page.
- Look: white background, near-black ink (`--dl-f-ink`) and border, 2 mm radius; these are print colours and do not follow the screen theme or the font scale.
- Layout: `sm` and `md` put the QR on the left and the body on the right; `lg` stacks everything centred with a very large code (25 pt) and quantity (30 pt).
- Danger: `tone="danger"` plus a `danger` tag is the convention for dead stock / expired. In a roll sheet the red edge is kept when printing (`.dl-labelsheet--roll .dl-label--danger`).
- QR and barcode need the browser to download the libraries from cdnjs once. If it fails, the QR area shows the plain text (class `is-fallback`) and the barcode is replaced by `<code>` with the value; a value CODE128 cannot encode shows an error text instead of bars.
- Language: only `title` (and the default kind name), line `label`s and tag `label`s are translated. Everything else is printed as given.
- Accessibility as implemented: the barcode `svg` has `role="img"` and `aria-label="Barcode <value>"` (English, not translated); the QR image gets `alt="QR <text>"` from the script. There is no `role` on the label itself.
- Keyboard and states: none (a static print object). Permissions: `can="module.action"`.

## Data and events

- Data in: scalars, plus `lines` and `tags` as lists of small arrays (shapes above). No slot. Nothing is posted. No `dl:*` events.
- JS: two small scripts are attached by markup hooks: `[data-dl-qrcode]` (`Qrcode` class, reads `data-text`, `data-size`) and `[data-dl-barcode]` (`Barcode` class, reads `data-value`, `data-format`, `data-height`, `data-width`, `data-text`). `el.dlComponent` on those two elements is the script instance; the label root has none.
- Markup hooks: `dl-label`, `dl-label--<size|tone|kind>`, `data-color`, `dl-label__band`, `__main`, `__qr`, `__body`, `__code`, `__name`, `__hi`, `__lines`, `__tags`, `__tag--<tone>`, `__bar`, `__foot`.
- Typical parent: `x-dl::label-sheet` (print bar and `@page` rule, `paper="roll"` or `paper="a4"`); it takes the labels as its slot and has its own `size` prop that must match the labels (it only sets the page rule and the count text).

## Rules and pitfalls

- Set the same `size` on `x-dl::label-sheet` and on every label in it, otherwise the page rule (roll = exactly one label per page) and the labels disagree.
- Use the month colour (`color="..."`) for arrival month only and keep red for expired / dead stock (`tone="danger"` and a `danger` tag).
- Always give `code`: it is the human-readable fallback when the QR or the barcode fails to load.
- `kind` and `size` are normalised silently (unknown becomes `null` / `md`); `color`, `tone` and tag tones are not validated and an unknown value only loses its style.
- `barcode` must be encodable as CODE128 (ASCII); long values make wide bars that shrink to fit (`max-width: 100%`) and may become hard to scan on `sm`.
- Wrap data values with `raw()` if you put them into `title` or into `label` of lines / tags; the values of `lines` and the other props are never translated.
- Code oddity (not fixed): the REMARK sentence and the generated description are cut off (`src/Components/Label.php:5`, copied into `skills/devlib/components.md:492`: "item (isi satu bin),."). The full description is the class comment at `src/Components/Label.php:17-30`.
- Code oddity (not fixed): `src/Components/Label.php:102` hard-codes the English word `Barcode` in the `aria-label` (and `resources/assets/js/components/qrcode.js:11` builds `QR <text>` in English), unlike the translated tag and line labels.
- Code oddity (not fixed): `tone` is a free string in `src/Components/Label.php:86` but only `danger` has a style (`resources/assets/devlib.css:3267`); the class comment mentions only `danger`.

## Example

Shapes copied from the catalog demo `wh-labels` (fictional data):

```blade
<x-dl::label-sheet title="Labels" :count="3" size="md" paper="a4">
    <x-dl::label kind="location" color="accent" mark="Storage" code="A10-PPR-L03-05" name="Plano Paper · Floor 1 · Warehouse A10" qr="LOC:A10-PPR-L03-05" barcode="A10-PPR-L03-05"
        :lines="[['label' => 'Max weight', 'value' => '3,000 kg'], ['label' => 'Volume', 'value' => '7.5 m³'], ['label' => 'Pallets', 'value' => '4']]" />
    <x-dl::label kind="item" color="accent" mark="Raw Material" code="KR-140" name="White Kraft 140 gsm 79 x 97" highlight="15,000 LBR" qr="ITM:RM:KR-140" barcode="KR-140" tone="danger"
        :lines="[['label' => 'Bin', 'value' => 'A10-PPR-L03-01'], ['label' => 'Arrived', 'value' => '31-Dec-2025'], ['label' => 'Expiry', 'value' => '27-Oct-2026']]"
        :tags="[['label' => 'Dead stock', 'tone' => 'danger'], ['label' => 'Expires soon', 'tone' => 'warn']]" />
    <x-dl::label size="sm" kind="item" color="green" mark="Finished Goods" code="FB-DUPLEX-001" name="Folding Box Duplex 350" highlight="12,000 PCS" qr="ITM:FG:FB-DUPLEX-001" barcode="FB-DUPLEX-001"
        :lines="[['label' => 'Bin', 'value' => 'A10-FG-L01-01'], ['label' => 'Arrived', 'value' => '20-Sep-2026']]" />
</x-dl::label-sheet>
```

Large pallet label, month colour from the arrival date, and a roll sheet:

```blade
<x-dl::label-sheet title="Pallet labels" size="lg" paper="roll" :count="1">
    <x-dl::label size="lg" kind="pallet" :color="\Devlib\Components\Label::monthColor('2026-10-02')" mark="OCT 2026" code="PL2600032"
        name="PYJ31 · Ivory 300 79 x 109" highlight="6,000 LBR" qr="PLT:PL2600032" barcode="PL2600032"
        footer="After every use: print a new label with the remaining quantity."
        :lines="[['label' => 'Arrived', 'value' => '02-Oct-2026'], ['label' => 'Supplier pallet', 'value' => 'BOHUI-PLT-00921'], ['label' => 'Expiry', 'value' => '02-Oct-2027']]" />
</x-dl::label-sheet>
```

## Related

- `x-dl::label-sheet`: print sheet (roll or A4) that holds the labels and prints only them.
- `x-dl::label-guide`: table of the three sizes, how to print, and the meaning of the band colours (`:colors`).
- `x-dl::scan`: scan box used with the labels in the catalog demo.
- `x-dl::qr`, `x-dl::barcode`: stand-alone QR / barcode objects.
- `x-dl::badge`: on-screen status tag (the label's `tags` are print-only).
- `skills/devlib/components.md` (generated prop list).
