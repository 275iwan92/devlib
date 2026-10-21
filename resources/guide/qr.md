## Overview

`<x-dl::qr>` draws a QR code from a text (a URL, an ID or a short code) with an optional caption under it. The drawing is done in the browser by the qrcodejs library (version 1.0.0, loaded once from cdnjs when the first QR needs it). It is the code for phone cameras: link to a document, employee or asset ID, a bin or pallet code that a phone scans.

Use it to show one QR code on screen or on a printout. Do NOT use it for:

- A 1D barcode for laser scanners and retail numbers: use `x-dl::barcode`.
- A full warehouse label (QR, barcode, code, lines, colour band): use `x-dl::label`, which draws its own QR from its `qr` prop.
- Letting the user choose the type and download PNG or SVG: use `x-dl::code-generator`.
- The "show this form as a QR" button of `x-dl::form` (`extra-action`): that is a separate dialog in the core script.
- Reading a code: use `x-dl::scan`.

### Markup produced

```html
<figure class="dl-qrbox" data-dl="x-dl::qr" data-dl-class="Qr ← DevlibComponent" data-dl-src="—" style="--q:120px">
  <span data-dl-qrcode data-text="https://example.test/doc/CO2600011" data-size="120" title="https://example.test/doc/CO2600011"></span>
  <figcaption>Document link</figcaption>      <!-- only with caption -->
</figure>
```

The `Qrcode` script (`resources/assets/js/components/qrcode.js`, selector `[data-dl-qrcode]`) fills the `span` with the QR image. Extra HTML attributes (`id`, `class`, `data-*`) and `can="module.action"` go on the `figure`.

## Props

Constructor of `Devlib\Components\Qr` (3 props). Blade names are kebab-case; use `:size="90"` for the number.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `text` | `string`, `''` | The content encoded in the QR. Written (escaped) to `data-text` and to the `title` tooltip of the code, so hovering shows the full content. Empty text is replaced by a single space inside the script, which still draws a (meaningless) code, so always pass a value. Error correction is fixed at level M; colours are fixed (dark `#111827` on white). See the translation note in Rules and pitfalls: `text` is one of the base text props. |
| `size` | `int`, `120` | Side of the QR image in CSS pixels. Written to `data-size` and to the CSS variable `--q` on the figure. The white box adds 3 px padding on each side, so the outer size is `size` + 6 px. The image itself is `size` x `size` regardless of the content: a longer `text` makes denser, smaller modules. |
| `caption` | `?string`, `null` | Small text under the code (`<figcaption>`). Rendered only when not empty. A standard text prop: translated through the dictionary; wrap data values (for example an employee number) in `raw()`. |

Interaction between props: none besides `size` and the content length (density). There is no prop for colour, error correction or logo.

## Behavior

- On mount, the script loads qrcodejs (`loadLib`, one script tag for the whole page), clears the `span` and creates `new QRCode(span, {text, width: size, height: size, colorDark: '#111827', colorLight: '#ffffff', correctLevel: M})`. After drawing, the generated `img` gets `alt="QR <text>"`.
- The element is mounted once. To draw a QR added later (Livewire, AJAX), call `Devlib.mount(container)` after inserting the markup.
- If the library cannot be loaded, or throws (for example a text longer than the QR capacity, about 2,300 bytes at level M; not verified in the browser), the `span` shows the plain text in monospace (class `is-fallback`) instead of a code.
- Look: `.dl-qrbox` is an inline flex column, centred, gap 4 px, no margin. The code sits on a white box with 3 px padding and radius 4 px; the image or canvas fills the box. The caption is muted 11 px text. The 3 px white border is the quiet zone: do not place the code on a coloured background.
- Print: the code prints as an image; white background and dark modules do not depend on the theme, also in dark mode.
- Language: only `caption` is translated. The `alt` text (`QR <text>`) is fixed English.
- Access: `can="module.action"` hides the object (nothing rendered) when the ability is missing.
- Accessibility as implemented: the generated `img` has `alt="QR <text>"`; the `figure`/`figcaption` pair is native; the `title` shows the content on hover (not available on touch devices). While loading, and after a failure, the text fallback is plain text.

## Data and events

- No data layer and no network call except the library download. Nothing is posted.
- No `dl:*` event is emitted. `el.dlComponent` on the `span[data-dl-qrcode]` is the `Qrcode` script instance (it only has `el`).
- Markup hooks: `data-dl-qrcode` (script selector), `data-text`, `data-size`, `.dl-qrbox`, `figcaption`, `.is-fallback`; CSS variable `--q`; root `data-dl`, `data-dl-class`, `data-dl-src`.
- Other objects reuse the same script by writing `<span data-dl-qrcode data-text=".." data-size="..">` themselves: `x-dl::label` (size 62, 112 or 150 px for `sm`, `md`, `lg`) and the `id-card` component of the mockup parts package (`x-dl::qr` with size 58 or 64).
- To change the behaviour in a project: `Devlib.register(class MyQr extends Devlib.classes.Qrcode {...})` and `data-dl-js="MyQr"`.

## Rules and pitfalls

- Put in the QR a short, stable identifier or a URL (for example `https://example.test/doc/CO2600011`) rather than a copy of the data; the QR cannot be corrected after it is printed. A prefix such as `LOC:` or `PLT:` (as in the label demo) tells the scan screen what kind of code it read.
- Do not encode secrets, passwords or personal data: everyone with a phone can read it, and the content is also in the `title` tooltip and in the page source.
- Choose `size` for the way it is read: roughly 90 px or more on screen for short codes, bigger for long text. Test scanning with the real device; the 3 px quiet zone is tighter than the 4-module rule of the QR standard, so keep a free margin around the figure.
- The library comes from a CDN: the first draw needs internet access (the browser may cache it). For offline sites, ship the library and load it before DevLib (the script uses `window.QRCode` if it already exists).
- `text` and `caption` are translated text props (see the oddity below). Wrap data in `raw()` when it could match a dictionary phrase or when you do not want every value recorded in the application dictionary.
- Do not pass a `style` attribute to `x-dl::qr`: the component writes its own `style="--q:..px"` after the attribute bag, so a second `style` attribute would be duplicated and (in HTML the first one wins) the `--q` size would be lost. Wrap the figure in an element and style that instead (not verified in a browser).
- Code oddity (not fixed): `Qr` does not override `TEXT_PROPS`, so the base list in `DevlibComponent` applies and includes `text` and `caption`. The QR payload `text` is therefore passed through `Lang::text()` at render: with an application translator every new payload may be recorded in the translation dictionary, and a payload that equals a dictionary phrase would be translated into the page language and encoded that way. `x-dl::label` avoids it (its `TEXT_PROPS` is only `title`). Workaround: write the payload as `raw($value)`.
- Code oddity (not fixed): the Technical Guide looks for the script at `resources/assets/js/components/qr.js` (kebab-case of the class name `Qr`), but the file is `qrcode.js` (class `Qrcode`), so the entry shows no JS file.
- Code oddity (not fixed): `size` has no lower bound or validation; `:size="0"` produces a zero-size box (the script passes `+data-size` to the library as is).

## Example

Shapes taken from the catalog demo `codes` (fictional values):

```blade
<x-dl::qr text="https://example.test/doc/CO2600011" caption="Document link" />
<x-dl::qr text="EMP-000123" :size="90" caption="Employee ID" />
```

With data in the caption, not translated:

```blade
<x-dl::qr :text="raw('EMP-'.$employee->code)" :size="96" :caption="raw($employee->name)" />
```

On a printout, next to a document number:

```blade
<div class="dl-row" style="gap:16px;align-items:center">
    <x-dl::qr :text="route('po.show', $po)" :size="80" />
    <b>{{ $po->number }}</b>
</div>
```

## Related

- `x-dl::barcode`: the 1D barcode (laser scanners, retail codes).
- `x-dl::label`, `x-dl::label-sheet`, `x-dl::label-guide`: warehouse labels that print a QR, and the sheet and guide around them.
- `x-dl::scan`: the field that reads a QR (keyboard scanner or phone camera).
- `x-dl::code-generator`: choose a type, type the content, download PNG or SVG, print.
- `x-dl::form`: its `extra-action` button shows the form values as a QR in a dialog.
