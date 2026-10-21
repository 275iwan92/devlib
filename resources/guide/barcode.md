## Overview

`<x-dl::barcode>` draws a 1D barcode (an SVG) from a text value. The drawing is done in the browser by the JsBarcode library (`JsBarcode.all.min.js` 3.11.6, loaded once from cdnjs when the first barcode needs it). The default format is `CODE128`, which carries any letters, digits and symbols and is the right choice for item codes, bin codes, lot numbers and pallet numbers. Other formats are for numbers that an outside party defines: `EAN13` and `EAN8` for retail items, `UPC`, `ITF14` for outer cartons, `CODE39` for old scanners.

Use it to show one code on screen or on a printout (item label, lot number, delivery note). Do NOT use it for:

- A QR code (phone camera, URLs, long text): use `x-dl::qr`.
- A whole warehouse label with band, name and lines: use `x-dl::label`, which draws its own CODE128 barcode from its `barcode` prop (fixed format, no text under the bars).
- Letting the user choose the type and download PNG or SVG: use `x-dl::code-generator` (it uses the same JsBarcode library and lists the formats with their content rules).
- Reading a code: use `x-dl::scan`.

### Markup produced

```html
<span class="dl-barcode" data-dl="x-dl::barcode format=CODE128" data-dl-class="Barcode ← DevlibComponent" data-dl-src="—">
  <svg data-dl-barcode data-value="RM-KRT-0012" data-format="CODE128" data-height="50" data-width="1.6" data-text="1" role="img" aria-label="Barcode RM-KRT-0012"></svg>
</span>
```

The `Barcode` script (`resources/assets/js/components/barcode.js`, selector `[data-dl-barcode]`) reads these `data-*` attributes and fills the SVG. Extra HTML attributes (`id`, `class`, `style`, `data-*`) and `can="module.action"` go on the outer `span`.

## Props

Constructor of `Devlib\Components\Barcode` (5 props). Blade names are kebab-case; use `:prop="expr"` for numbers and booleans (`:height="36"`, `:width="2"`, `:text="false"`).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `value` | `string`, `''` | The content to encode. Written (escaped) to `data-value` and to the `aria-label` (`Barcode <value>`). It is data: never translated. What is allowed depends on `format` (see below); an invalid value draws an error text instead of a barcode. An empty value is not valid for the common formats, so always pass a value. |
| `format` | `string`, `'CODE128'` | JsBarcode format name, written to `data-format` and shown in the developer tag (`x-dl::barcode format=...`). Not validated by PHP; the name is case-sensitive and goes to the library as is. Names in use in this package (from `CodeGenerator::TYPES`): `CODE128`, `CODE128A`, `CODE128B`, `CODE128C`, `CODE39`, `EAN13`, `EAN8`, `UPC`, `UPCE`, `ITF14`, `ITF`, `MSI`, `codabar`, `pharmacode` (the last two are lower case). The class header lists `CODE128`, `EAN13`, `EAN8`, `CODE39`, `ITF14`, `UPC` as the supported set. An unknown name makes the library fail, which shows the error text. |
| `height` | `int`, `50` | Height of the bars in CSS pixels (`data-height`, passed to JsBarcode `height`). The text under the bars is added on top of it. |
| `width` | `float`, `1.6` | Width of the thinnest bar in CSS pixels (`data-width`, JsBarcode `width`). The total width = modules x `width`, so a longer `value` or a bigger `width` makes a wider drawing. `x-dl::label` uses 1 for `sm` and 1.3 for the other sizes; test any smaller value with the real scanner. Bind numbers with `:width="2"`. |
| `text` | `bool`, `true` | `true` prints the human-readable value under the bars (monospace 13 px); `false` hides it (`data-text="0"`). Hide it only when the value is shown nearby, as `x-dl::label` does. |

Interaction between props: `format` decides which `value` is valid; `height`, `width` and `text` only change the drawing. There is no prop for margin, colour, caption or alignment: the script uses margin 0, a transparent SVG background, and the wrapper gives a white background with padding.

### Formats and what `value` must be (verified in `CodeGenerator::TYPES`)

| Format | Content rule |
|---|---|
| `CODE128` | Any letters, digits and symbols; the densest 1D code (default). |
| `CODE128A` | Capital letters, digits and control characters. |
| `CODE128B` | Letters (upper and lower case), digits and symbols. |
| `CODE128C` | Digits only, in pairs (even count). |
| `CODE39` | Capital letters, digits and `- . $ / + %` and space. |
| `EAN13` | 12 or 13 digits (the check digit is added when 12). |
| `EAN8` | 7 or 8 digits (the check digit is added when 7). |
| `UPC` | 11 or 12 digits (the check digit is added when 11). |
| `UPCE` | 6, 7 or 8 digits (compressed UPC). |
| `ITF14` | 13 or 14 digits, for outer cartons. |
| `ITF` | Digits only, even count. |
| `MSI` | Digits only. |
| `codabar` | Digits and `- $ : / . +`, usually wrapped in A to D. |
| `pharmacode` | A number from 3 to 131070. |

## Behavior

- On mount, the script loads the library (`loadLib`, one script tag for the whole page) and then calls `JsBarcode(svg, value, {format, height, width, displayValue, margin: 0, fontSize: 13, font: 'monospace', background: 'transparent'})`. Several barcodes on one page share the one download.
- The element is mounted once (`dlMounted`). To draw a barcode added later (Livewire, AJAX), call `Devlib.mount(container)` after inserting the markup.
- If the library throws (invalid value or format), the `svg` is replaced by `<span class="dl-barcode__err">` with the text `Invalid <format> barcode: <value>` (Indonesian: `Barcode <format> tidak sah: <value>`), in the danger colour. Screens that know the value rules should validate on the server and not rely on this.
- If the library cannot be loaded (offline, CDN blocked), the `svg` is replaced by `<code>value</code>`: the value is still readable but cannot be scanned.
- Look: `.dl-barcode` is an inline-block with a white background, padding 6 px 8 px and radius 4 px (a quiet zone; white in dark theme on purpose, because scanners need dark bars on light). The SVG is `display:block` and has no maximum width, so a very long `value` overflows its container; put it in a wide enough place or lower `width`.
- Print: the SVG prints as vector. Background colours may need "Background graphics" on in the print dialog if the page is not white.
- Language: only the error text is translated (client dictionary key `bc_invalid`, delivered by `x-dl::theme-boot`; without it the raw key would show). The `aria-label` word `Barcode` is fixed English.
- Access: `can="module.action"` hides the object (nothing rendered) when the ability is missing.
- Accessibility as implemented: the SVG has `role="img"` and `aria-label="Barcode <value>"`. The text under the bars is drawn inside the SVG. After an error or fallback there is no role or label.

## Data and events

- No data layer and no network call except the library download. Nothing is posted.
- No `dl:*` event is emitted. `el.dlComponent` on the `svg` is the `Barcode` script instance (it only has `el`).
- Markup hooks: `data-dl-barcode` (script selector, on the `svg`), `data-value`, `data-format`, `data-height`, `data-width`, `data-text` (`1` or `0`), `.dl-barcode` (wrapper), `.dl-barcode__err` (error text). Root `data-dl`, `data-dl-class`, `data-dl-src` on the wrapper.
- Other objects reuse the same script by writing `<svg data-dl-barcode ...>` themselves: `x-dl::label` (`CODE128`, height 18/28/44 px for `sm`/`md`/`lg`, width 1 for `sm` and 1.3 otherwise, no text) and `x-dl::code-generator`.
- To change the behaviour in a project: `Devlib.register(class MyBarcode extends Devlib.classes.Barcode {...})` and `data-dl-js="MyBarcode"` (or `jsClass()` on a PHP subclass).

## Rules and pitfalls

- Choose the format by who reads the code: `CODE128` for internal codes; `EAN13` or `EAN8` only for numbers that really are EAN items. Do not draw an internal code as `EAN13`: the check digit rule rejects or changes it.
- An `EAN13` with 13 digits must have the correct check digit, otherwise the library rejects it and the error text is shown (the check digit is added automatically only when you pass 12 digits).
- Test `width` and `height` with the real scanner and printer (the label object uses width 1 to 1.3). Print at 100% scale; scaling the printout changes the bar width.
- Scanners need a quiet zone: do not remove the wrapper padding or put the barcode on a coloured or dark background.
- `value` is data: wrap nothing in `raw()` for this prop, it is not in the translated text props. Do not put secrets in a barcode; anyone can read it.
- Always show the value as text next to a barcode that has `:text="false"`, so a user can type it when the scan fails.
- The CDN library means the screen needs internet access the first time (the browser may cache it). For fully offline sites, ship the library yourself and load it before DevLib (the script uses `window.JsBarcode` if it already exists).
- Code oddity (not fixed): the catalog demo `site/resources/views/demos/codes.blade.php` writes `caption="Kode item"` on `x-dl::barcode`, but the class has no `caption` prop; the text is not shown and the attribute is only rendered on the wrapper `span`. Use a separate caption element, or `x-dl::qr` which has `caption`.
- Code oddity (not fixed): the header comment of `Barcode.php` lists only six formats, while the same script is used with `CODE128A/B/C`, `UPCE`, `ITF`, `MSI`, `codabar` and `pharmacode` in `CodeGenerator`; `format` is not validated in PHP either way.
- Code oddity (not fixed): after a failed draw the script replaces the `svg` with `outerHTML`, so `el.dlComponent`, the `role` and the `aria-label` are gone; there is no retry.

## Example

Shapes taken from the catalog demo `codes` (fictional values):

```blade
<x-dl::barcode value="RM-KRT-0012" />
<x-dl::barcode value="8991234567895" format="EAN13" />
<x-dl::barcode value="LOT-IVR-2608-A" :height="36" />
```

Thin code without the printed value, and a wider one for a screen scanner:

```blade
<x-dl::barcode value="PL2600032" :height="28" :width="1.3" :text="false" />
<x-dl::barcode value="W1-RCK-L02-03" :height="70" :width="2.2" />
```

Beside its own caption and inside a document line:

```blade
<div class="dl-row" style="gap:12px;align-items:center">
    <x-dl::barcode :value="$item->code" :height="40" />
    <span>{{ $item->name }}</span>
</div>
```

## Related

- `x-dl::qr`: the 2D code (QR) for phones, URLs and long text.
- `x-dl::label`, `x-dl::label-sheet`, `x-dl::label-guide`: warehouse labels that print a barcode, and the sheet and guide around them.
- `x-dl::scan`: the field that reads a barcode (keyboard scanner or phone camera); the camera reads `code_128`, `code_39`, `ean_13` and `qr_code` only.
- `x-dl::code-generator`: choose a type, type the content, download PNG or SVG, print.
