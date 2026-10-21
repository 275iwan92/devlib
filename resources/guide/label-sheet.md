## Overview

`<x-dl::label-sheet>` is the print sheet for warehouse labels. The slot holds a row of `x-dl::label` objects; the sheet adds a top bar (title, label count, chosen size and paper, your own tools, Back and Print) and a print stylesheet so that the **Print button prints only the labels**: the bar, the guide and the rest of the screen are hidden, and a label is never split across two pages. It is a pure view object: no data layer, no model, no JavaScript class of its own (the Print button calls `window.print()`; the QR codes and barcodes inside the labels are drawn by the `Qrcode` and `Barcode` scripts).

Two printing worlds are supported, chosen by `paper`:

| `paper` | For | Print result |
|---|---|---|
| `roll` | Label printer or thermal printer | One label = one page. Page size = the size of the chosen `size`, margin 0, label border made transparent. |
| `a4` (default) | Office printer | Labels packed on A4 portrait, page margin 5 mm, no gap between labels, label borders stay as cut lines. |

Use it for: a print screen for bin, item and pallet labels in a warehouse. Do NOT use it for:

- A document printout (PO, invoice, delivery order): use `x-dl::print-doc` / `x-dl::print-out`.
- Showing one code on screen: use `x-dl::qr` or `x-dl::barcode` directly.
- Making a code on demand (type, preview, download PNG or SVG): use `x-dl::code-generator`.
- Explaining label sizes to the operator: use `x-dl::label-guide` (it is usually placed inside this sheet, see the `guide` slot).

### Markup produced

```html
<div class="dl-labelsheet dl-labelsheet--a4" data-dl="x-dl::label-sheet paper=a4" data-dl-class="LabelSheet ← DevlibComponent" data-dl-src="—">
  <style>@media print{ @page{size:A4 portrait;margin:5mm} }</style>
  <div class="dl-labelsheet__bar">
    <b>Labels</b><span>5 labels · 100 x 50 mm · A4 sheet (office printer)</span>   <!-- span only when count is not null -->
    <span class="dl-labelsheet__spacer"></span>
    ...tools slot...
    <a class="dl-btn dl-btn--secondary dl-btn--md" href="...">Back</a>            <!-- only with back -->
    <button type="button" class="dl-btn dl-btn--primary dl-btn--md" onclick="window.print()">Print</button>
  </div>
  ...guide slot...
  <div class="dl-labelsheet__labels"> ...x-dl::label items... </div>
</div>
```

Extra HTML attributes (`id`, `class`, `data-*`) and `can="module.action"` go on the root `div`. The `@page` rule is written by the component itself (method `pageRule()`), so the screen never writes print CSS.

## Props

Constructor of `Devlib\Components\LabelSheet` (5 props). Blade names are kebab-case; use `:prop="expr"` for anything that is not a plain string (`:count="5"`).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `title` | `string`, `'Labels'` | Bold title at the left of the bar. A standard text prop: translated through the dictionary; wrap data values in `raw()`. Screen only (the bar is not printed). |
| `back` | `?string`, `null` | Address of the Back button (`<a href>`). `null` = no Back button. The button text `Back` is fixed English by project rule (button words are always English). Use a real address, not `history.back()`. |
| `count` | `?int`, `null` | Number of labels, shown in the bar as `N labels · <size> · <paper>`. It is only a number you pass: it is NOT counted from the slot and not validated. `null` removes that whole info span (also the size and paper text). `0` is shown as `0 labels`. |
| `size` | `string`, `'md'` | Label size of this sheet: `sm` (60 x 40 mm), `md` (100 x 50 mm), `lg` (100 x 150 mm); any other string is replaced by `md` in the constructor. It does two things only: prints the size text in the bar, and sets the page size when `paper="roll"`. It does NOT resize the labels: every `x-dl::label` has its own `size`. Keep them equal on a roll sheet. |
| `paper` | `string`, `'a4'` | `roll` or `a4`; any other string becomes `a4`. Chooses the `@page` rule (see Overview) and the CSS class `dl-labelsheet--roll` / `dl-labelsheet--a4` that changes how labels flow when printing. The label text in the bar is the English name (`Label roll (label printer)` / `A4 sheet (office printer)`, translated). |

Interaction between props: `paper="roll"` uses `size` for the page; `paper="a4"` ignores `size` for printing (A4 portrait, 5 mm margin) and uses it for the bar text only.

### Slots

| Slot | Where | Use |
|---|---|---|
| default | inside `div.dl-labelsheet__labels` | The labels (`x-dl::label`). Other markup is allowed but is printed too. |
| `tools` | in the bar, before Back and Print | Extra buttons or filters of the screen (`<x-slot:tools>`). Printed never (bar is hidden in print). |
| `guide` | between the bar and the labels | Normally `<x-dl::label-guide />` (`<x-slot:guide>`). Screen only. |

### What the sheet expects from the labels

The sheet does not read the label data, but its print rules depend on the label markup (`div.dl-label`, `.dl-label--danger`, `break-inside: avoid`). In short, `x-dl::label` takes: `code`, `title`, `name`, `lines` (`[['label' => .., 'value' => ..]]`), `qr`, `barcode`, `size`, `tone`, `footer`, `kind` (`location`, `item`, `pallet`), `color`, `mark`, `highlight`, `tags`. `qr` draws a QR through `data-dl-qrcode`, `barcode` draws a fixed CODE128 through `data-dl-barcode`. The full list belongs to the `x-dl::label` entry.

## Behavior

### On screen

- A bar with title and info, a spacer, the `tools` slot, Back, Print.
- The labels are in a flex-wrap area (gap 4 mm, padding 5 mm, muted background) so the screen shows a preview of how labels sit together. Label sizes shown are the real mm sizes of the CSS (`.dl-label--sm` 60 x 40, default 100 x 50, `--lg` 100 x 150).

### Print (browser print dialog)

All print rules are in `@media print` (`devlib.css` plus the inline `<style>` of the component):

1. `body:has(.dl-labelsheet) *` is made `visibility:hidden`, then the labels area and its children are made visible again. So the shell, menus, bar and guide do not appear on paper.
2. The labels area becomes `position:absolute; left:0; top:0` with no padding, no gap, no background; `body` gets `height:auto; overflow:visible; margin:0` so more than one page can print.
3. `paper="roll"`: the area is `display:block`; each `.dl-label` is `display:flex`, border transparent (the red left border of `tone="danger"` stays), and `break-after: page`. `@page{size:<w>mm <h>mm; margin:0}`, so a 100 x 50 mm label printer receives pages of exactly that size. Roll mode relies on the label having the same size as the page.
4. `paper="a4"`: labels keep their border (cut lines) and `border-radius:0`; `@page{size:A4 portrait; margin:5mm}`. Labels per A4 sheet (from `Label::SPEC`, 5 mm margin, no gap): `sm` 21, `md` 10, `lg` 2.
5. Every label has `break-inside: avoid` and `print-color-adjust: exact`, so a label does not split between pages and the colour band is meant to print as a colour.

The print dialog still needs the right operator settings (paper size for rolls, margins None or Minimum, scale 100%); `x-dl::label-guide` states them.

### Language, access and accessibility

- Translated at render: `title`, the word `labels` and the paper name in the bar. Not translated by design: the `Back` and `Print` button words. Label data (`code`, `name`, line values) is data.
- `can="module.action"` on the sheet: without the ability nothing is rendered (not hidden by CSS).
- Accessibility as implemented: Back is a link, Print a native `button`, both in the Tab order; the bar has no landmark role; the labels carry the `role="img"` and `aria-label="Barcode <value>"` of the barcode SVG and `alt="QR <text>"` on the QR image (set by the scripts after loading).
- The print needs a browser with `:has()` support (current Chrome, Edge, Safari and Firefox).

## Data and events

- Nothing is posted and nothing is fetched. The data is whatever the screen passes into the labels.
- No `dl:*` event is emitted by the sheet. The Print button is an inline `onclick="window.print()"`; hook `beforeprint` / `afterprint` on `window` from a project JS component if the screen must react (inline scripts in screens are forbidden by lint L002).
- Markup hooks: `.dl-labelsheet`, `.dl-labelsheet--roll` / `--a4`, `.dl-labelsheet__bar`, `.dl-labelsheet__labels`, `.dl-labelsheet__spacer`; root `data-dl="x-dl::label-sheet paper=<paper>"`, `data-dl-class`, `data-dl-src` (developer catalog / Technical Guide link).
- Public PHP methods used by the view: `tag()`, `pageRule()` (returns the `@page` rule), plus constants `LabelSheet::PAPERS` (`roll`, `a4` => English names).
- Related client scripts started by the labels inside: `Qrcode` (`[data-dl-qrcode]`, library qrcodejs from cdnjs) and `Barcode` (`[data-dl-barcode]`, library JsBarcode from cdnjs). They load the library on demand, so the first print right after opening the page can come before the codes are drawn if the network is slow: wait until the codes appear.

## Rules and pitfalls

- Use the sheet only for labels; give every label its own `size`, equal to the sheet `size` when `paper="roll"`. A bigger label than the roll page is clipped or flows onto the next page (follows from the fixed `@page` size).
- `count` is a display number only. Keep it equal to the number of labels you render (`:count="count($labels)"`).
- The codes are drawn in the browser from CDN libraries: the print screen needs internet access (or the libraries cached). If the library cannot load, a QR becomes plain text and a barcode becomes its value in `<code>`, which is still printed but cannot be scanned.
- Roll printers: choose the roll paper size in the print dialog, margins None, scale 100%. A4: paper A4, margins None or Minimum, scale 100%, background graphics on (the tip is in `x-dl::label-guide`).
- Not verified here: how each browser treats `@page` written inside `@media print`; the resulting page count when the page behind the sheet is very tall (the rest of the page is hidden with `visibility`, not removed from layout). Test the first print on the real printer.
- Do not put the sheet inside another positioned container that clips overflow: the print rule moves the labels with `position:absolute` (not verified in every shell layout).
- The colour-band tip and the selected-row styling belong to `x-dl::label-guide`; see its entry for the code oddities found there.

## Example

Office printer (A4), shape taken from the catalog demo with fictional data:

```blade
<x-dl::label-sheet title="Labels" :count="3" size="md" paper="a4" :back="url('/warehouse/labels')">
    <x-slot:guide>
        <x-dl::label-guide size="md" />
    </x-slot:guide>

    <x-dl::label kind="location" color="accent" mark="Storage" code="W1-RCK-L02-03" name="Rack 2, level 3, Warehouse 1" qr="LOC:W1-RCK-L02-03" barcode="W1-RCK-L02-03"
        :lines="[['label' => 'Max weight', 'value' => '2,000 kg'], ['label' => 'Pallets', 'value' => '2']]" />
    <x-dl::label kind="item" color="green" mark="Raw Material" code="RM-0140" name="Kraft paper 140 gsm" highlight="15,000 SHT" qr="ITM:RM-0140" barcode="RM-0140"
        :lines="[['label' => 'Bin', 'value' => 'W1-RCK-L02-03'], ['label' => 'Arrived', 'value' => '31-Dec-2025']]"
        :tags="[['label' => 'Expires soon', 'tone' => 'warn']]" />
    <x-dl::label kind="item" color="orange" mark="Raw Material" code="RM-0141" name="Kraft paper 170 gsm" highlight="8,000 SHT" qr="ITM:RM-0141" barcode="RM-0141" tone="danger"
        :tags="[['label' => 'Dead stock', 'tone' => 'danger']]" />
</x-dl::label-sheet>
```

Label roll (thermal printer): one page per label, so the sheet size and every label size match:

```blade
<x-dl::label-sheet title="Pallet labels" :count="2" size="lg" paper="roll" :back="url('/warehouse/receipts')">
    <x-dl::label size="lg" kind="pallet" color="violet" mark="OCT 2026" code="PL2600101" name="Coated board 300 gsm" highlight="6,000 SHT" qr="PLT:PL2600101" barcode="PL2600101"
        footer="After every use: print a new label with the remaining quantity."
        :lines="[['label' => 'Arrived', 'value' => '02-Oct-2026'], ['label' => 'Supplier pallet', 'value' => 'SUP-PLT-00921'], ['label' => 'Bin', 'value' => 'W1-RCV']]" />
    <x-dl::label size="lg" kind="pallet" color="violet" mark="OCT 2026" code="PL2600102" name="Coated board 300 gsm" highlight="6,000 SHT" qr="PLT:PL2600102" barcode="PL2600102" />
</x-dl::label-sheet>
```

With extra buttons in the bar:

```blade
<x-dl::label-sheet title="Labels" :count="$n" paper="a4">
    <x-slot:tools>
        <x-dl::button label="Refresh" variant="secondary" :href="url('/warehouse/labels')" />
    </x-slot:tools>
    @foreach ($labels as $l)
        <x-dl::label :code="$l->code" :name="$l->name" :qr="$l->qr" :barcode="$l->code" />
    @endforeach
</x-dl::label-sheet>
```

## Related

- `x-dl::label`: one warehouse label (location, item, pallet) that goes in the slot.
- `x-dl::label-guide`: sizes, labels per A4 sheet, printing tips and colour band meaning; place it in the `guide` slot or above the sheet.
- `x-dl::qr`, `x-dl::barcode`: the same code scripts used inside the labels, for a code shown on its own.
- `x-dl::scan`: the field that reads these labels back (scanner or phone camera).
- `x-dl::code-generator`: free-form QR and barcode generator with PNG, SVG download and print.
- `x-dl::print-doc`, `x-dl::print-out`: printouts of documents (not labels).
