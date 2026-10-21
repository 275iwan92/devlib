## Overview

`<x-dl::code-generator>` is a small self-contained tool that makes a QR code or a 1D barcode on screen. The user picks the code type, types the content, sees the picture redraw at once, and then downloads it as PNG or SVG or prints it. Each type has its own content rules (digits only, fixed length, allowed characters); content that does not fit the type is refused with a message, never drawn as a broken picture. Nothing is posted or stored by the object: it is a utility, not a form field.

It supports 15 types (the constant `CodeGenerator::TYPES`): `QR`, `CODE128` (auto), `CODE128A`, `CODE128B`, `CODE128C`, `CODE39`, `EAN13`, `EAN8`, `UPC` (UPC-A), `UPCE`, `ITF14`, `ITF`, `MSI`, `CODABAR`, `PHARMA` (Pharmacode). QR uses the library qrcodejs, the 1D codes use JsBarcode; both are loaded once from cdnjs the first time a generator appears on a page (internet access is needed on first use).

Use it when:

- people need to make a code from free content (a document number, a lot number, a link) and download or print it, for example in a tools or setup area;
- a screen needs to let the user choose among several symbologies.

Do NOT use it when:

- the code belongs to a record and must appear on a printed label or document: use `x-dl::qr` (one QR) or `x-dl::barcode` (one 1D code) for display, `x-dl::label` and `x-dl::label-sheet` for warehouse labels, `x-dl::print-doc` for documents;
- you need to READ a code: use `x-dl::scan`;
- the code type is fixed and only the content changes in a form: use `x-dl::barcode` / `x-dl::qr` with the value of the record.

### Markup produced

```html
<div class="dl-codegen" data-dl="x-dl::code-generator type=QR" data-dl-class="CodeGenerator ← DevlibComponent" data-dl-src="—"
     data-dl-codegen data-types='{"QR":{"format":null,"hint":"...","example":"https://example.test/doc/CO2600011"},"CODE128":{"format":"CODE128",...},...}'>
  <h3 class="dl-codegen__title">Title</h3>                              <!-- only with title -->
  <div class="dl-codegen__grid">
    <div class="dl-codegen__form">
      <!-- x-dl::field.select name="type" (Code type) ; x-dl::field.text name="value" (Content, code font, no case badge) -->
      <div class="dl-field__hint" data-cg-hint></div>                     <!-- rule of the chosen type -->
      <div class="dl-codegen__opts" data-cg-qr-opts> ec (Error correction), size </div>
      <div class="dl-codegen__opts" data-cg-bar-opts> height, width, text (toggle) </div>
      <small class="dl-codegen__note">hint</small>                        <!-- only with hint -->
    </div>
    <div class="dl-codegen__result">
      <div class="dl-codegen__canvas" data-cg-out><span data-cg-qr></span><svg data-cg-bar role="img" aria-label="Barcode"></svg></div>
      <div class="dl-codegen__err" data-cg-err hidden></div>
      <div class="dl-codegen__actions"> PNG (primary), SVG (secondary), Print buttons </div>
    </div>
  </div>
</div>
```

Two columns (form left, result right); under 720px wide they stack. The picture area is always white (`background:#fff`) so the code stays readable in dark themes.

## Props

Constructor of `Devlib\Components\CodeGenerator` (4 props). Blade names are kebab-case; all four are plain strings.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `type` | `string`, `'QR'` | The code type chosen when the page loads. Must be one of the 15 keys listed above (exact case). An unknown value is silently replaced by `QR`. The user can change it later in the `Code type` dropdown; the prop is only the starting choice. |
| `value` | `?string`, `null` | The starting content. `null` (also when the attribute is omitted) becomes the example content of the type (`https://example.test/doc/CO2600011` for `QR`, `8991234567895` for `EAN13`, ...). An empty string `''` is kept as empty, so the picture area shows the message `Type the content first.` until the user types. Written into the `Content` input. Not translated. |
| `title` | `?string`, `null` | Heading `h3.dl-codegen__title` above the tool. Translated through the dictionary. No title = no heading (and the title-link to the Technical Guide has nothing to attach to). |
| `hint` | `?string`, `null` | A small grey note under the options (`small.dl-codegen__note`), for instructions to the user. Translated. This is separate from the live rule line (`data-cg-hint`) that always shows the rule of the chosen type. |

Common attribute: `can="module.action"`; without the ability nothing is rendered. Extra HTML attributes (`id`, `class`, `style`, `data-*`) are merged onto the root `div.dl-codegen`.

Not props, but fixed inside the object (the user changes them on screen): QR error correction `L / M / Q / H` (start `M`), QR size `200` px, bar height `70` px, bar width `2`, and the toggle `Number below the bars` (start on).

The types and their rules (hint text is shown live and translated):

| Key | Label | Content rule |
|---|---|---|
| `QR` | QR Code | Any text, link or ID |
| `CODE128` | Code 128 (auto) | Any letters, digits and symbols; the densest 1D code |
| `CODE128A` | Code 128 A | Capital letters, digits and control characters |
| `CODE128B` | Code 128 B | Letters (upper and lower case), digits and symbols |
| `CODE128C` | Code 128 C | Digits only, in pairs (even count) |
| `CODE39` | Code 39 | Capital letters, digits and `- . $ / + %` and space |
| `EAN13` | EAN-13 | 12 or 13 digits (check digit is added when 12) |
| `EAN8` | EAN-8 | 7 or 8 digits (check digit is added when 7) |
| `UPC` | UPC-A | 11 or 12 digits (check digit is added when 11) |
| `UPCE` | UPC-E | 6, 7 or 8 digits (compressed UPC) |
| `ITF14` | ITF-14 (carton) | 13 or 14 digits, for outer cartons |
| `ITF` | Interleaved 2 of 5 | Digits only, even count |
| `MSI` | MSI | Digits only |
| `CODABAR` | Codabar | Digits and `- $ : / . +`, usually wrapped in A-D |
| `PHARMA` | Pharmacode | A number from 3 to 131070 |

## Behavior

### Drawing

- The picture is redrawn on every `input` and `change` inside the root (each keystroke, each option). There is no Generate button.
- QR: `Size` is clamped to 80..1000 px (a non-number falls back to 200); `Error correction` L 7%, M 15%, Q 25%, H 30%. Output is a `<canvas>` made by qrcodejs, dark `#111827` on white.
- 1D: `Bar height` minimum 20 (default 70), `Bar width` minimum 1 (default 2), margin 10, font 14 monospace, the number under the bars is shown when the toggle is on. The picture is an inline `<svg>` made by JsBarcode.
- QR options (`data-cg-qr-opts`) are shown only for QR, bar options (`data-cg-bar-opts`) only for 1D; the `SVG` button is hidden for QR.

### Changing the type

Changing `Code type` REPLACES the content with the example of the new type (so the picture is valid at once). Anything the user typed is lost. The rule line below `Content` is updated to the rule of the new type.

### Errors

- Empty content: the picture is cleared and the red line `Type the content first.` shows.
- Content refused by the type (wrong length, forbidden characters, QR too long for its capacity): the picture is cleared and the red line shows `This content is not valid for the chosen code type.` followed by the rule of the type.
- PNG / SVG / Print are refused while an error is showing: the same message appears as a warning toast.

### Output actions

| Button | Result |
|---|---|
| `PNG` | Downloads `<type>-<content>.png` (letters, digits, `_` and `-`, other characters become `_`, at most 60 characters before the extension). QR: the canvas as is. 1D: the SVG painted at 2x on white so it stays sharp when printed. |
| `SVG` | 1D only. Downloads the serialised SVG as `<type>-<content>.svg`. |
| `Print` (preset print) | Opens a blank window with the PNG image centred and calls `window.print()`. If the browser blocks the window, the warning toast `The print window was blocked.` shows. |

### Screen text, language and rights

- Texts are written in English and translated through the dictionary: `title`, `hint`, labels (`Code type`, `Content`, `Error correction`, `Size (px)`, `Bar height (px)`, `Bar width`, `Number below the bars`, `Show` / `Hide`), the type hints (translated in PHP into `data-types`) and the button captions. The type NAMES (for example `QR Code`, `EAN-13`) are options and are not translated. The three browser messages are looked up in the page dictionary (`Devlib.h.t`); DevLib's own built-in dictionary has no Indonesian text for them, so they stay English unless the application dictionary provides them.
- Language/locale has no effect on the code itself.
- Access: `can="module.action"` on the tag.

### Accessibility as implemented

- The barcode `<svg>` has `role="img"` and `aria-label="Barcode"` (the label is fixed and not translated, and it does not carry the content). The QR canvas has no label.
- The error line has no `role="alert"` or `aria-live`; the user is told only visually (and by toast for the buttons).
- Fields come from `x-dl::field.*` (real labels). Buttons are native `<button>`s in the Tab order.

## Data and events

### What the server receives

Nothing is sent to the server by the object itself, and no `dl:*` custom event is emitted. All work (drawing, download, print) is in the browser. Be careful, though: the inputs inside the object have real names (`type`, `value`, `ec`, `size`, `height`, `width`, `text`). If the generator is put inside a `<form>` or an `x-dl::form`, those names ARE posted and may clash with the fields of the form; place it outside a form.

### Markup hooks and JS

| Hook | Where |
|---|---|
| `data-dl-codegen` | root; the class `CodeGenerator` in `resources/assets/js/components/code-generator.js` mounts on it. |
| `data-types` | JSON `{key: {format, hint, example}}` for the 15 types (`format` is the JsBarcode format, `null` for QR). |
| `data-cg-hint` | the live rule line of the chosen type. |
| `data-cg-qr-opts`, `data-cg-bar-opts` | option groups shown per kind. |
| `data-cg-out`, `data-cg-qr`, `data-cg-bar`, `data-cg-err` | picture area, QR holder, barcode svg, error line. |
| `data-cg-png`, `data-cg-svg`, `data-cg-print` | the three buttons. |

`el.dlComponent` is the `CodeGenerator` instance (methods `draw()`, `png()`, `svg()`, `print()`, `pickType()`, property `kind`, `isQr`). The component re-reads its fields by `name` on every draw, so a project can change values by script (`input.value = ...` then dispatch `input`). Override with `data-dl-js` + `Devlib.register(class MyGen extends Devlib.classes.CodeGenerator {...})` (the PHP class returns no `jsClass()`).

## Rules and pitfalls

- This is a utility screen. For a code that belongs to a document or an item, show it with `x-dl::qr`, `x-dl::barcode` or the label objects, using the stored value, so every print is consistent.
- The type list is fixed in the class constant; to add a type, a JsBarcode format must exist for it and `TYPES` must be extended (a framework change, not a prop).
- First use needs internet: the libraries come from `cdnjs.cloudflare.com` (`qrcodejs 1.0.0`, `jsbarcode 3.11.6`). Offline installations must serve them locally.
- EAN, UPC and ITF-14 add the check digit when the digit before it is missing; if the full length is typed, JsBarcode checks it and refuses a wrong check digit.
- A GS1 / retail barcode generated here is only a picture; the numbers must be allocated by the company that owns the prefix.
- Do not put the object inside a form (field names are real, see Data and events).
- `value=''` shows an error on load; omit the prop to get the example content.
- Code oddity (not fixed): `draw()` in `resources/assets/js/components/code-generator.js` catches EVERY error (including a failed library download) and shows `This content is not valid for the chosen code type.` plus the rule, so offline or a blocked cdnjs looks like bad content. The failed load is also cached by `Devlib.h.loadLib` (`libOnce`), so it is not retried until the page is reloaded.
- Code oddity (not fixed): `print()` calls `window.print()` twice on the new window (`w.onload` and a 400 ms timer); depending on the browser the print dialog can open twice. The window is written with `document.write` and the title strips only `<` and `&`.
- Code oddity (not fixed): the view puts `data-cg-value` on `x-dl::field.text`, which writes extra attributes on the field root, and no script uses it; the script finds the input by `[name="value"]`.
- Code oddity (not fixed): the colours `#111827` and `#ffffff` are written in the script (not theme tokens); they are on purpose (a code must stay black on white), but they are the one place where the rule against raw hex colours is not followed.

## Example

From the catalog demo (`site/resources/views/demos/code-generator.blade.php`):

```blade
<x-dl::code-generator title="QR & Barcode Generator" hint="Pick the type, type the content, then download PNG / SVG or print. Each type checks its own content rules." />
```

Start as a Code 128 for a lot number, with fixed content:

```blade
<x-dl::code-generator type="CODE128" value="LOT-2608-A" title="Lot label code" />
```

Start empty (the user must type):

```blade
<x-dl::code-generator type="EAN13" value="" />
```

## Related

- `x-dl::qr`, `x-dl::barcode`: display ONE code for a stored value (same libraries) inside a screen or a label.
- `x-dl::label`, `x-dl::label-sheet`, `x-dl::label-guide`: printable warehouse labels that carry a QR or barcode.
- `x-dl::scan`: the opposite direction, reading a code into a box.
- `x-dl::field.select`, `x-dl::field.text`, `x-dl::field.toggle`, `x-dl::button`: the objects the generator is built from.
- `x-dl::print-doc`: documents that print a code on paper.
