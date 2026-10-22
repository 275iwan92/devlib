## Overview

`<x-dl::print-out>` is the nota / report printout with its own **preview bar**. The screen only declares the content (header texts, parties, columns, rows, totals, signatures); the script lays it out onto real paper sheets (A4 portrait, A4 landscape or A5 landscape), measuring each row so a sheet is filled exactly, repeating the table header on every sheet and keeping totals + notes + signatures together on the last sheet. The user can switch paper, with or without prices, VAT or non-VAT header, grouping, ink-saver (grey) mode and press **Print** (browser print dialog, page size set automatically).

Use it for: customer-order nota with prices, VAT and amount in words; goods-receipt notes without prices and with a two-level table header; stock card / master stock reports (through `x-dl::report`, which is this same object with another tag).

### Use it, or use another object

| Need | Object |
|---|---|
| Nota or report with a preview bar, paper / price / VAT / group switches, running balance, subtotal per group | `x-dl::print-out` (reports: `x-dl::report`) |
| Fixed-layout transactional document (A5 for 1-5 rows, A4 above) that must go to PDF / JPEG / e-mail with a QR | `x-dl::print-doc` + `x-dl::doc-share` |
| Sectioned field form or report made of several small tables and boxes | `x-dl::print-sheet` |
| An on-screen data table (not for paper) | `x-dl::table` or `x-dl::grid` |

### Markup

```html
<div class="dl-print" data-dl="x-dl::print-out" data-dl-print data-config='{"columns":[...],"rows":[...],"totals":{...},"currency":[...],"hasPpnKop":true,"summary":[...],"groups":{...},"opening":null}'
     data-paper="a4" data-prices="1" data-ppn="0" data-group="">
  <div class="dl-print__bar"> title | Paper [A4 portrait / A4 landscape / A5 landscape] | Group | Price | VAT | Ink Saver | Print </div>
  <div class="dl-print__pages" data-p-pages> ... generated <section class="dl-print__sheet"> per sheet ... </div>
  <template data-p-head> ... </template> <template data-p-criteria> ... </template> <template data-p-parties> ... </template>
  <template data-p-notes> ... </template> <template data-p-sign> ... </template> <template data-p-foot> ... </template>
</div>
```

The templates hold the server-rendered pieces; `print.js` copies them into each generated sheet. Extra HTML attributes (`id`, `class`, `data-*`) and `can="module.action"` go on the root `div`; without the ability nothing is rendered.

## Props

Constructor of `Devlib\Components\PrintOut`: **23 props**. Use `:prop="expr"` for anything that is not a plain string (`:rows="$rows"`, `:ppn="true"`, `:prices="false"`).

### Document header

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `title` | `string`, `''` | Document title in the header box and in the preview bar. Translated through the dictionary (standard text prop). |
| `subtitle` | `?string`, `null` | Small line under the title. Translated. Not rendered when null. |
| `number` | `?string`, `null` | Document number (bold, under the title) and `- <title> . <number>` in the preview bar. Data: not translated. |
| `date` | `?string`, `null` | ISO date, printed as `dd-Mmm-yyyy` in the display language, followed by `Page n/N`. An unparsable value is printed as given. Without `date` only the page number is shown. |
| `status` | `?string`, `null` | Status text in a bordered badge at the right of the header box (for example `CONFIRMED`). Printed as given, not translated or normalised; the project rule that every printout carries its status makes this prop effectively required for documents. |
| `company` | `array`, `[]` | Company header (Non VAT): `name`, `tagline`, `address`, `contact`, `initials`, `logo` (URL), `logoBw` (URL, black and white). `logo` set = the image replaces the initials box, the name and the tagline (address and contact stay). `logoBw` (or `logo` when missing) is shown in ink-saver mode. Optional `style` (`logo_name_address` | `logo_name` | `name`) and `legal_name` (small line under the name when it differs from `name`) choose the layout: with a logo style the logo sits left of name + legal name (+ address and contact for `logo_name_address`); `name` shows name + legal name only; a logo style with empty `logo` = name only; the `tagline` shows with the name. Without `style` nothing changes. Built by `Devlib\PrintDoc\Kop::of()`, shared with `x-dl::print-doc`. Usually `config('devlib.company')`. |
| `company-ppn` | `array`, `[]` | Header of the VAT (legal entity) variant, same keys plus `npwp` (printed as `NPWP <number>`, only in this header). When not empty a **VAT / Non VAT** switch appears in the preview bar and the header changes with it; when empty `company` is used for both and the switch is absent. Usually `config('devlib.company_ppn')`. |
| `ppn` | `bool`, `false` | Initial state of the VAT switch (`data-ppn`): `true` starts with the VAT header and VAT line. Only meaningful together with `company-ppn`; without it the switch does not exist and VAT is decided only by `totals['ppn']`. |
| `printed-by` | `?string`, `null` | Name added to the sheet footer (`Printed from DevLib . <name>`). |
| `source` | `?string`, `null` | Free text naming where the data comes from (catalog / Manifest). No visual or runtime effect. Default `dokumen cetak`. |

### Parties and criteria

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `parties` | `array`, `[]` | Two-column box under the header: `label => value`, where value is a string or a list of lines (first line normal, the others in small type). The keys are translated; values are data. Empty = no box. |
| `criteria` | `array`, `[]` | Report filter box: `label => value` (period, warehouse, ...). Keys translated. Empty = no box. |
| `summary` | `array`, `[]` | KPI tiles above the table (reports): list of `['label' => ..., 'sum' => column key]` (sum of all rows), `['label' => ..., 'last' => column key]` (value of the last row, for example the closing balance) or `['label' => ..., 'value' => literal]`; optional `'signed' => true` (green positive, red negative) and `'plus' => true` (show `+`). Labels are not translated by the component. |

### Table

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `columns` | `array`, `[]` | Column definitions, see the table below. The `label` is translated. |
| `rows` | `array`, `[]` | List of rows; each row an array keyed by the column `key`s (plus any key used by `sub`, `running` formulas, `groups`, `rowTones`). Values are data, never translated. Row numbers (`No`) are generated. |
| `groups` | `array`, `[]` | `column key => label` of the columns the user may group by. When not empty a **Group** switch appears (None + one button per entry); a group shows a heading row `<value> . n rows` and, when some column has `sum`, a subtotal row. Labels translated. |
| `group-by` | `?string`, `null` | Column key the table is grouped by from the start (`data-group`). Works even when `groups` is empty (then the user cannot change it); a value not in `groups` leaves no switch button active. |
| `opening` | `?float`, `null` | Opening balance for `running` columns (null counts as 0). |
| `totals` | `array`, `[]` | Totals block, see below. Printed on the last sheet only and only while prices are shown. |
| `currency` | `string`, `'IDR'` | Key of `Devlib\Terbilang::CURRENCIES` (`IDR`, `USD`, `EUR`, `SGD`, `MYR`, `AUD`, `JPY`, `CNY`, `GBP`). Decides the rounding of the VAT amount (IDR 0 decimals, most others 2) and the currency word of the amount in words. An unknown code is passed as null: VAT rounds to 2 decimals and the words have no currency. |

### Paper, prices, signatures

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `paper` | `string`, `'a4'` | Initial paper: `a4` (A4 portrait), `a4l` (A4 landscape), `a5` (A5 landscape). Any other value gives A4 portrait sizing and `@page`, with no paper button highlighted. |
| `prices` | `bool`, `true` | Initial state of the **Price** switch (`data-prices`). `false` hides columns with `price => true` and the whole totals block + amount in words. The switch exists only when at least one column has `price => true`; with `:prices="false"` and no price column the totals can never be shown. |
| `signatures` | `array`, `[]` | Signature boxes, left to right: `[role, note?, name?]`. `role` and `note` are translated; the name line is `( name )` or a dotted line when no name is given. Empty = no signature row. |

### Column definition (`columns[]`)

| Key | Meaning |
|---|---|
| `key`, `label` | Row key and header text (required). |
| `type` | `text` (default), `number` and `money` (right aligned, shown with up to 2 decimals in the display language), `badge` (small coloured tag, centered; `tones` maps value -> `success`, `danger`, `warn`, `info`, `violet`, other = neutral). Values of `number` / `money` are right aligned; header cells are always centered. |
| `price` | `true` = hidden by **Without price**. |
| `sub` | Key of another row field printed as a small second line in the cell (for example the item code). |
| `group` | Heading text above this and neighbouring columns with the same `group` (two-level header). Not translated. |
| `width` | CSS width string of the header cell (`'62px'`). Ignored when any visible column has `group` (the two-level header does not write widths). |
| `sum` | `true` = the column is summed in the grand-total row (and in group subtotals). Without any `sum` column there are no total / subtotal rows. |
| `signed`, `plus` | `signed` colours positive green and negative red and prints negatives with a true minus sign; `plus` adds `+` to positive values (needs `signed`). |
| `bold` | `true` = bold values. |
| `wrap` | `true` = the header text of this column may break over several lines (default: one line). Use it on long labels when the table is wider than the sheet; ignored when any visible column has `group`. |
| `rowTones` | `value => tone` map; a row whose value in this column matches is highlighted (`danger` or `warn` have styles). |
| `running`, `prev` | `running` = formula over the other column keys, for example `'in - out + adj'`, accumulated row by row from `opening` and stored in this column; `prev` = key that receives the balance before each row. Only identifiers, numbers, spaces and `+ - * / ( )` are accepted, otherwise an error is thrown. |

### Totals block (`totals`)

| Key | Meaning |
|---|---|
| `of` | Column key to sum (required for the block to appear). |
| `discount` | Discount as an **amount** (not a percentage), subtracted before VAT; shows lines Discount and Tax Base. |
| `ppn` | VAT percentage. Applied when the VAT header/switch is on, or when no `company-ppn` exists. |
| `extra` | `[[label, amount], ...]` lines added after the total (negative amount = deduction, shown in brackets). Shown only together with `net`. |
| `net` | Label of the final line (for example `Net Received`): `total + extra`. With `net` the main line is named `Total Due`, otherwise `Total`. |

The amount in words (`# ... #`) is printed under the block for the final amount.

## Behavior

### Preview bar

Sticky bar above the sheets: title and number; **Paper** (always); **Group** (only with `groups`); **Price** (only when a column has `price`); **VAT / Non VAT** (only with `company-ppn`); **Ink Saver** (toggle: sheets in grey, black-and-white logo, plain backgrounds) and **Print**. Each segment click rebuilds all sheets. The bar is hidden on paper.

### Layout and pagination

`print.js` (JS class `Print`, selector `[data-dl-print]`) builds sheets of the chosen paper: a4 = 210 x 297 mm, a4l = 297 x 210 mm, a5 = 210 x 148 mm. Order: header, parties, criteria, KPI tiles, table. Rows are added one by one and measured; a row that does not fit moves to a new sheet with the table header repeated. The closing part (totals, amount in words, notes, signatures) must stay on one sheet; if it does not fit, the last two rows move to a new sheet together with it. Sheets are rebuilt once more after web fonts finish loading (row height changes). Each sheet shows `Page n/N` in the header box and `sheet n/N` in the footer.

### Numbers, language, locale

Numbers use the display language (`1.234,56` / `1,234.56`), up to 2 decimals, trailing zeros trimmed. Dates use `Devlib\Locale`. The preview bar, footer words and total labels are dictionary texts (`pr_*`, written Indonesian/English). Column labels, party keys, criteria keys, signature roles and the title are translated through the dictionary; row values never. The footer prints the render time (server time) and the printed-by name.

### Printing

`Print` calls `window.print()` and writes `@page{size: A4 portrait / A4 landscape / A5 landscape; margin: 0}` into a `<style id="dl-print-page">` in the head. During print the bar and everything outside `.dl-print__pages` are hidden. This is browser (vector) printing: text stays selectable and the user can choose "Save as PDF". For PDF / JPEG / e-mail with a QR use `x-dl::print-doc` + `x-dl::doc-share`.

### Accessibility

Native `button`s in a sticky bar (Tab / Enter / Space work). The segment buttons mark the current choice only by the `is-active` class (no `aria-pressed`). The sheets are plain `section` / `table` markup without roles or labels.

## Data and events

### What the script reads

All data travels in `data-config` (JSON): `columns` (each with `type` defaulted to `text`), `rows`, `totals` (object), `currency` (the `Terbilang` row for the currency code or null), `hasPpnKop`, `summary`, `groups` (object), `opening`. The state of the switches lives in `data-paper`, `data-prices`, `data-ppn`, `data-group` on the root. Nothing is posted and no request is made.

### Events and JS

| Item | Meaning |
|---|---|
| `dl:print-built` | Bubbling `CustomEvent` after every (re)build; `detail.pages` = number of sheets. |
| `el.dlComponent` | The `Print` instance. |
| `el.dlPrint.build()` | Rebuild after changing a dataset value from script, for example `el.dataset.paper = 'a5'; el.dlPrint.build();` (this is what a segment click does). |

### Markup hooks

`data-p-paper`, `data-p-group`, `data-p-prices`, `data-p-ppn` (segments, each button `data-value`), `data-p-bw` (ink saver), `data-p-print`, `data-p-pages` (sheet container), `data-p-body` and `data-p-pg` (inside each sheet), `data-p-kop="0|1"` (Non VAT / VAT header), the six `template[data-p-head|criteria|parties|notes|sign|foot]`. Classes `dl-print`, `dl-print__sheet`, `dl-print__table`, `dl-print__tail`, `dl-print__totals`, `dl-print__grp` / `__sub` / `__grand` (group, subtotal and grand-total rows), `is-bw`.

### Slot

`<x-slot:notes>` (HTML) is the note box placed before the signatures on the last sheet. Not a prop.

### Lint

No rule is specific to this object. General screen rules apply to the Blade file: `L001` (no raw `table` / `button` / `input` / `form`), `L002` (no inline `<script>`), `L003` (no hex colours).

## Rules and pitfalls

- Declare content, do not draw: no raw `<table>`, no inline script; the script builds the sheets. Catalog pages `p5-print` (Customer Order Note, Goods Receipt Note) are APPROVED: do not change their look without the owner's approval.
- `prices="false"` also removes the totals and the amount in words. If the document has no priced column the switch is absent, so keep `prices` true for documents that must show totals.
- `totals['extra']` is ignored unless `totals['net']` is set. `totals['discount']` is an amount, not a percentage.
- `ppn` is the initial state of the VAT switch and does nothing without `company-ppn`; the VAT amount is controlled by `totals['ppn']`.
- A grouped header (`group` on columns) only looks right when the columns of one group are adjacent.
- The `running` formula is evaluated with `new Function` after a character check; keep it to column keys and arithmetic. Order of `rows` is the order of accumulation: sort on the server first.
- `number` columns have no per-column decimals (up to 2); format other precisions into text on the server.
- Status text is not normalised here (unlike `x-dl::print-doc`); pass the wording you want to see.
- Values go in escaped; there is no HTML cell type. The only HTML input is the `notes` slot.
- Code oddity (not fixed): `resources/assets/devlib.css:1729-1735` is an unscoped `@media print{ body *{visibility:hidden} ... }`. Label sheet and login card scope the same trick with `body:has(...)`; this one affects every page that loads the stylesheet (browser print of a page without `.dl-print__pages` hides everything, including `x-dl::print-doc`).
- Company header `style` / `legal_name` apply to `company` and `company-ppn` independently, so the VAT switch can change the style as well as the name; keys, rules and a registration example: `x-dl::print-doc` (**Company header: keys, styles and legal name**). `x-dl::report` takes the same keys.
- Fixed 2026-10-22: the VAT header now prints its `NPWP <number>` line (the loop used to compare `$isPpn === '1'` while PHP makes the key an integer, so the line never appeared). Only the VAT header gains that line; with no `company-ppn` the VAT view reuses `company`, whose `npwp` is then printed too. The header sizes of `x-dl::print-out` / `x-dl::report` are the same on A4, A4 landscape and A5 (name 14 px, other lines 9.5 px, logo at most 44 x 120 px) and were not changed.
- Code oddity (not fixed): `resources/views/components/print-out.blade.php` (initials fallback) falls back to `mb_substr($c['name'] ?? 'TM', 0, 2)` for the logo initials, so a `company` array without `initials` and `name` shows the letters `TM` (a project abbreviation inside the library default).
- Code oddity (not fixed): the footer text is the dictionary key `pr_printed_from` = "Printed from DevLib" (`src/Lang.php:172`); the product name is printed on every sheet and the text cannot be changed by a prop.
- Code oddity (not fixed): in a two-level header `print.js:86` writes the hard-coded word `No` instead of `T('pr_no')`; and `print.js:120` writes `data-paper-now` that nothing reads.
- Code oddity (not fixed): the class comment (`src/Components/PrintOut.php:24`) lists the summary keys as `sum` or `value` (+ `signed`), but `print.js:80` also supports `last` and `plus`, and the column keys `bold` and `plus` are not in that comment either.

## Example

Customer-order nota with prices, VAT, group-less table and amount in words (shape of the catalog demo `print-co`; fictional data).

```blade
<x-dl::print-out title="Nota Customer Order" subtitle="Customer Order" number="CO2600011" date="2026-08-15" status="CONFIRMED"
    :company="config('devlib.company')" :company-ppn="config('devlib.company_ppn')" :ppn="true" printed-by="admin"
    :parties="['Customer' => ['PT Contoh Snackindo', 'Central Warehouse, Malang'], 'Ref. Quotation' => 'QT2600007', 'Payment Terms' => 'Net 30']"
    :columns="[
        ['key' => 'name', 'label' => 'Product Name', 'sub' => 'code'],
        ['key' => 'ship', 'label' => 'Ship Date', 'width' => '62px'],
        ['key' => 'qty', 'label' => 'Qty', 'type' => 'number'],
        ['key' => 'price', 'label' => 'Price / pc', 'type' => 'money', 'price' => true],
        ['key' => 'subtotal', 'label' => 'Subtotal', 'type' => 'money', 'price' => true],
    ]"
    :rows="[
        ['name' => 'Banana chips 250 g', 'code' => 'BC-250', 'ship' => '20-Aug-2026', 'qty' => 508, 'price' => 14500, 'subtotal' => 7366000],
        ['name' => 'Donut box 20 x 20 x 6', 'code' => 'DB-206', 'ship' => '22-Aug-2026', 'qty' => 3000, 'price' => 2150, 'subtotal' => 6450000],
    ]"
    :totals="['of' => 'subtotal', 'discount' => 100000, 'ppn' => 11, 'extra' => [['Income Tax 23', -250000]], 'net' => 'Net Received']"
    :signatures="[['Prepared By', 'Sales'], ['Acknowledged By', 'Ops Manager'], ['Approved By', 'Customer']]">
    <x-slot:notes><b>Note:</b> Prices include delivery inside the city. Deliveries follow the delivery plan.</x-slot:notes>
</x-dl::print-out>
```

Report-style options (`x-dl::report` takes the same props): grouping, running balance, KPI tiles and criteria.

```blade
<x-dl::print-out title="Stock Card" number="SC-0001" date="2026-08-31" :company="config('devlib.company')"
    :criteria="['Period' => '01-Aug-2026 - 31-Aug-2026', 'Warehouse' => 'Raw Material']"
    :summary="[['label' => 'Total in', 'sum' => 'in'], ['label' => 'Closing balance', 'last' => 'balance', 'signed' => true]]"
    :columns="[
        ['key' => 'date', 'label' => 'Date'],
        ['key' => 'ref', 'label' => 'Reference'],
        ['key' => 'in', 'label' => 'In', 'type' => 'number', 'sum' => true],
        ['key' => 'out', 'label' => 'Out', 'type' => 'number', 'sum' => true],
        ['key' => 'balance', 'label' => 'Balance', 'type' => 'number', 'running' => 'in - out', 'bold' => true],
    ]"
    :rows="[
        ['date' => '2026-08-01', 'ref' => 'GRN-001', 'in' => 100, 'out' => 0],
        ['date' => '2026-08-04', 'ref' => 'ISS-007', 'in' => 0, 'out' => 40],
    ]"
    :opening="25" />
```

## Related

- `x-dl::report`: subclass of this object (same props and view) used for report screens such as stock card and master stock.
- `x-dl::print-doc`: fixed-layout transactional document; pair with `x-dl::doc-share` for PDF / JPEG / link / e-mail.
- `x-dl::print-sheet`: A4 sheet of numbered sections for forms and multi-table reports.
- `x-dl::doc-share`: Print menu and Email dialog (not needed here; this object has its own Print button).
- `x-dl::button`: the preview bar uses `preset="print"` and the ink-saver button.
- `x-dl::table`, `x-dl::grid`: the on-screen list from which a report is usually opened.
- `skills/devlib/transaction-lifecycle.md`: the status that every printout must carry.
