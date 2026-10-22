## Overview

`<x-dl::print-sheet>` is a **sectioned A4 printout** for reports and field forms: company header (left), title + form code (right), a few info lines, then any number of numbered sections, each holding tables, party boxes and free text; then a note, a signature row and a footer line. The sheet is one A4 page (portrait or landscape) that grows downward as sections are added and is cut into pages when printed, with the table header repeated on each page.

The screen hands over plain arrays. The company header comes from the same source as every other printout (`Devlib\PrintDoc\Document::companyOf()`, registered once with `Document::companyUsing()`), so it never has to be passed in.

### Use it, or use another object

| Need | Object |
|---|---|
| Form or report made of several small blocks (checklist table, party boxes, text), with signatures | `x-dl::print-sheet` |
| Transactional document with one item table, fixed layout, status stamp, watermark, QR, PDF / e-mail | `x-dl::print-doc` + `x-dl::doc-share` |
| Nota or report with a preview bar (paper, price, VAT, group), automatic page filling and a Print button | `x-dl::print-out` / `x-dl::report` |
| A small on-screen table | `x-dl::table` |

### Markup

```html
<div class="dl-psheet [is-landscape]" data-dl="x-dl::print-sheet" ... data-dl-pdoc data-paper="a4" data-orientation="portrait|landscape">
  <section class="dl-psheet__page">
    <header class="dl-psheet__kop"> <div class="dl-pdoc__co"> logo or initials, name, lines </div> <div class="dl-psheet__title"><h1>Title</h1><b>CODE</b></div> </header>
    <div class="dl-psheet__info"><div>info line</div>...</div>
    <section class="dl-psheet__sec"> <h2><span>1</span>Section title</h2>
        <div class="dl-psheet__parties"><div><small>Label</small><div>line</div></div>...</div>      <!-- type parties -->
        <h3>Heading</h3> <table class="dl-psheet__tbl"> ... </table> <p class="dl-psheet__note">...</p>   <!-- type table -->
        <p class="dl-psheet__text">...</p>                                                              <!-- type text -->
    </section>
    <p class="dl-psheet__note">note</p>
    <div class="dl-pdoc__sign dl-psheet__sign"> <div><span>Role</span><i></i><b>(Name)</b></div>... </div>
    <footer class="dl-pdoc__foot dl-psheet__foot"><span>Printed on dd-Mmm-yyyy HH:mm</span></footer>
  </section>
</div>
```

Extra HTML attributes (`id`, `class`, `data-*`) and `can="module.action"` go on the root `div`; without the ability nothing is rendered.

## Props

Constructor of `Devlib\Components\PrintSheet`: **8 props**. Use `:prop="expr"` for arrays and booleans (`:sections="$sections"`, `landscape` or `:landscape="true"`).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `title` | `string`, `''` | Heading (upper case by CSS) at the right of the header. Translated. |
| `code` | `?string`, `null` | Form / report code printed in bold under the title (for example a controlled form number). Not translated. Not rendered when null. |
| `info` | `array`, `[]` | List of plain text lines under the header, right aligned (period, location, ...). Printed as given, not translated. Empty = no info block. |
| `sections` | `array`, `[]` | The body. List of sections, each `['title' => string, 'no' => int or string, 'blocks' => [...]]`. `title` empty or missing = no heading line for that section (the blocks are still printed); `no` = text in the little number badge (default: position, starting at 1). Block shapes below. |
| `signatures` | `array`, `[]` | Signature boxes, left to right, equal width: `['role' => string, 'name' => string]`. `role` is translated; `name` is printed in brackets under the signing space (an empty name prints `()` with nothing inside). Empty list = no signature row. |
| `note` | `?string`, `null` | Small paragraph after the sections and before the signatures. Printed as given. |
| `landscape` | `bool`, `false` | `true` = A4 landscape (297 x 210 mm page, root class `is-landscape`, `data-orientation="landscape"`). The component has no `paper` prop: the sheet is always A4. |
| `footer` | `?string`, `null` | Footer line text. Default (null) = `Printed on dd-Mmm-yyyy HH:mm` (translated label, server time at render). Setting it replaces the whole line; there is no page number. |

### Block shapes (`sections[].blocks[]`)

`type` is `table` (default when missing), `parties` or `text`.

| Type | Keys |
|---|---|
| `table` | `columns` (required list of `['key', 'label', 'align' => left or center or right, 'width' => mm, 'mono' => bool]`), `rows` (list of arrays keyed by column `key`), `heading` (small heading above the table), `note` (caption under it), `empty` (text when there are no rows, default `No data.`, translated). |
| `parties` | `items` (required list of `['label' => string, 'lines' => [text, ...]]`). One equal-width column per item; the label is translated, the lines are data. |
| `text` | `text` (one paragraph). |

Cell value in a table row: a plain string or number, or an array `['text', 'sub', 'rowspan', 'bold', 'class']` (`sub` = small second line, `bold` = strong text, `rowspan` = merge downward, `class` = extra CSS class on the `td`), or `false` = this cell does not exist because the cell above spans over it. A missing key prints an empty cell.

## Behavior

### Page and layout

- The page is `210mm` wide with `min-height: 297mm` (landscape: `297mm` x `210mm`), padding 10 / 9 / 6 mm, on a grey surface on screen. It grows downward with the content; it is not paginated on screen.
- Sections are stacked; a section heading is `h2` with a dark number badge; a table `heading` is `h3`. Table header cells have a light background, cells have thin borders, `mono` columns use a monospace font. `thead` is `table-header-group` and rows are `break-inside: avoid`, so a browser that paginates the sheet repeats the header and does not cut a row.
- The footer is pushed to the bottom of the page (`margin-top: auto`). The signature block does not break across pages.
- There is no `@page` rule: the paper size and orientation of a browser print are chosen by the print dialog (landscape sheets must be printed landscape).

### Company header

`company()` returns `Document::companyOf()`: `name`, `initials`, `lines`, `logo`, `npwp`. With `logo` the image replaces the initials box and the name; otherwise an initials box + the name. Each entry of `lines` is a small line. The non-VAT variant is always used; `npwp` is not printed by this object.

The header is built by `Devlib\PrintDoc\Kop::of()`, the same helper as `x-dl::print-doc`. Optional keys `style` (`logo_name_address` | `logo_name` | `name`) and `legal_name` (small line under the name, only when it differs from `name`) are read from the same array: `logo_name_address` = logo + name + legal name + `lines`; `logo_name` = logo + name + legal name; `name` = name + legal name; a logo style with no `logo` = name only. Without `style` the header is unchanged. `npwp` is still not printed here. Full key list and registration example: `x-dl::print-doc`.

### Translation

`title`, section `title`, party `label`, column `label`, `empty` and signature `role` go through `Document::t()` (print dictionary in Indonesian, application dictionary otherwise; follows the print language). `info`, `heading`, `note`, `text`, cell values, `code`, `footer` and `lines` are data and are never translated.

### Fit script

The root carries `data-dl-pdoc`, so `print-doc.js` binds to it. Only the selector for signature boxes (`.dl-pdoc__sign > div`) matches here: a role or name wider than its box is shrunk down to 6.5 pt. Nothing else is measured.

### Together with `x-dl::doc-share`

The sheet has no `data-pd-sheet`, so `x-dl::doc-share` treats it as a generic area: it rasterises the whole area as one tall image and, for PDF, slices that image into A4 pages (8 mm margins covered by white bands). Rows can therefore be cut at a page boundary and the table header is not repeated in the PDF. Give the share button `orientation="landscape"` when the sheet is landscape. For exact page breaks with a repeated header use the browser print of the page (see the stylesheet oddity below).

### Accessibility

Real `h1` / `h2` / `h3`, `table` / `thead` / `tbody`, no ARIA, no interactive element. The section number is a `span` inside the `h2`, so a screen reader reads the number before the title.

## Data and events

- Nothing is posted and no event is emitted. All data are props rendered on the server; `el.dlComponent` is the `PrintDoc` JS instance created by the shared `data-dl-pdoc` selector (it only runs the fit-to-size step).
- Markup hooks: `data-dl-pdoc`, `data-paper="a4"`, `data-orientation`, classes `dl-psheet`, `dl-psheet__page`, `__kop`, `__title`, `__info`, `__sec`, `__tbl`, `__parties`, `__text`, `__note`, `__sign`, `__foot`, `is-landscape`, `is-left` / `is-center` / `is-right` on cells, `is-mono`.
- The page content should come from the stored record; the sheet itself does not read anything from the request.
- Lint: no rule is specific to this object. General screen rules apply to the Blade file: `L001` (no raw `table` / `button` / `input` / `form`), `L002` (no inline `<script>`), `L003` (no hex colours).

## Rules and pitfalls

- `style` and `legal_name` come from `Document::companyUsing()` (see `x-dl::print-doc`), not from a prop of this object; in the `style` modes a long `lines` entry is cut with an ellipsis in the header, not wrapped.
- Pass display-ready text: the sheet does not format numbers or dates. Format them with `Devlib\Locale` (print language) before building the arrays.
- A `table` block without `columns` (and a `parties` block without `items`) raises an "Undefined array key" error: the view indexes them directly. The block type defaults to `table`.
- Cells covered by a `rowspan` must be `false` in the following rows; a missing key or `''` still prints an empty `td` and shifts the row.
- The sheet has no page numbers and no status stamp or watermark. If the form is a transactional document that must carry its status, use `x-dl::print-doc` or put the status into `info` / `footer` yourself (project rule: a printout always carries its status).
- The brackets around a signature `name` are always printed; a box with an empty `name` shows `()` under the signing space (there is no dotted-line variant as in `x-dl::print-doc`).
- `landscape` changes only the page width; remember the print dialog (or `orientation="landscape"` on `x-dl::doc-share`) as well.
- Long `info` lines or table cells are not shrunk (only signature boxes are); keep columns `width` in mm to control wrapping.
- Catalog: this object has no page in the catalog and no test yet; the example below is written from the view contract and was read-checked, not rendered. Add a catalog page (and ask for approval) before inheritors rely on it.
- Code oddity (not fixed): `src/Components/DevlibComponent.php:95` lists `PrintDoc`, `PrintOut`, `DocFooter`, `DocShare` in the `print` group but not `PrintSheet`, so its dictionary entries fall into the general group and the Technical Guide lists it under "Other objects" instead of "Print & documents".
- Code oddity (not fixed): the class REMARK (`src/Components/PrintSheet.php:5-12`) does not list `footer` among the inputs and names a demo page ("demo devlib.test > Cetak") and application screens that do not exist in this repository; no catalog page or test uses `x-dl::print-sheet`.
- Code oddity (not fixed): `Document::companyOf()` returns `npwp`, but `resources/views/components/print-sheet.blade.php` never prints it, and the object cannot select the VAT (legal entity) header that `x-dl::print-doc` selects with `data['ppn']`.
- Code oddity (not fixed, from reading the stylesheet, not verified in a browser): `resources/assets/devlib.css:2939` gives `.dl-psheet` an `@media print` rule for direct browser printing, but the unscoped `@media print{ body *{visibility:hidden} }` at `devlib.css:1729` (written for `x-dl::print-out`) hides every element that is not inside `.dl-print__pages`, which would blank a direct browser print of this sheet.

## Example

A checklist form with a party box, a table that merges the first column, a text block and two signatures (fictional data).

```blade
<x-dl::print-sheet title="Daily Line Inspection" code="FRM-QA-01" :landscape="false"
    :info="['Period: 01-Oct-2026 - 07-Oct-2026', 'Line: Packing 2']"
    :sections="[
        ['title' => 'Responsible parties', 'blocks' => [
            ['type' => 'parties', 'items' => [
                ['label' => 'Inspector', 'lines' => ['A. Tester', 'Quality']],
                ['label' => 'Line leader', 'lines' => ['B. Sample', 'Packing']],
            ]],
        ]],
        ['title' => 'Checks', 'blocks' => [
            ['type' => 'table', 'heading' => 'Machine checks',
                'columns' => [
                    ['key' => 'area', 'label' => 'Area', 'width' => 40],
                    ['key' => 'check', 'label' => 'Check'],
                    ['key' => 'result', 'label' => 'Result', 'align' => 'center', 'width' => 25],
                    ['key' => 'remark', 'label' => 'Remarks', 'width' => 45],
                ],
                'rows' => [
                    ['area' => ['text' => 'Sealer', 'rowspan' => 2, 'bold' => true], 'check' => 'Temperature in range', 'result' => 'OK', 'remark' => ''],
                    ['area' => false, 'check' => 'Seal strength', 'result' => 'OK', 'remark' => 'Re-test tomorrow'],
                    ['area' => 'Labeller', 'check' => ['text' => 'Label alignment', 'sub' => 'Sample of 10'], 'result' => '', 'remark' => ''],
                ],
                'note' => 'OK = within tolerance.'],
            ['type' => 'text', 'text' => 'Stop the line and call the supervisor when any result is outside tolerance.'],
        ]],
    ]"
    note="Keep this form for one year."
    :signatures="[['role' => 'Prepared By', 'name' => 'A. Tester'], ['role' => 'Checked By', 'name' => '']]" />
```

For a landscape sheet that is shared as PDF:

```blade
<x-dl::action-bar>
    <x-dl::doc-share target="#line-sheet" type="line-inspection" number="FRM-QA-01" title="Daily Line Inspection" orientation="landscape" />
</x-dl::action-bar>
<x-dl::print-sheet id="line-sheet" title="Daily Line Inspection" code="FRM-QA-01" landscape :sections="$sections" />
```

## Related

- `x-dl::print-doc`: fixed-layout transactional document (status stamp, watermark, QR, A5 / A4); the other half of the print family.
- `x-dl::print-out`: nota / report with a preview bar, automatic page filling and a Print button.
- `x-dl::report`: report variant of `x-dl::print-out`.
- `x-dl::doc-share`: PDF / JPEG / link / Email for any area; points at the sheet with `target`.
- `x-dl::action-bar`: usual place for the share button above the sheet.
- `skills/devlib/transaction-lifecycle.md`: the status that a transactional printout must carry.
