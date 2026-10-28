## Overview

`<x-dl::report>` is the printable report of DevLib: a report with its own preview bar that the script lays out onto real paper sheets (A4 portrait, A4 landscape or A5 landscape). The screen only declares the content (header texts, criteria box, KPI tiles, columns, rows, groups, signatures); `print.js` measures the rows, fills each sheet, repeats the table header on every sheet and keeps the closing part on the last sheet. Typical uses: a stock card with a running balance, a master stock list with KPI tiles, grouping and subtotals, any list that must come out on paper or "Save as PDF".

### It is `x-dl::print-out` with another tag

`Devlib\Components\Report` is a subclass of `Devlib\Components\PrintOut` with exactly two overrides: `tag()` returns `x-dl::report` and `source()` defaults to `laporan` (instead of `dokumen cetak`). It has no constructor, no view and no JS of its own:

| Shared with `x-dl::print-out` | Detail |
|---|---|
| Props | The same 23 constructor props (inherited constructor). |
| View | `resources/views/components/print-out.blade.php` (`render()` of `PrintOut`). |
| JS | `resources/assets/js/components/print.js`, class `Print`, selector `[data-dl-print]`. |
| CSS | `.dl-print*` classes in `resources/assets/devlib.css`, including the "REPORT (Part 5j)" block (A4 landscape, criteria box, KPI tiles, signed numbers, badges, row tones, group / subtotal / grand-total rows). |
| Language keys | `pr_*` entries in `Devlib\Lang::DICT` (preview bar, footer, total labels). |

The difference is only intent and naming: use `x-dl::report` for report screens (lists with criteria, KPI, groups, running balance) and `x-dl::print-out` for nota-style documents (parties, prices, VAT, totals, amount in words). Every report prop also works on `print-out` and the other way round; `parties`, `totals`, `company-ppn`, `ppn` and `prices` are documented below even though the report demos do not use them. The fuller description of the nota side is in `resources/guide/print-out.md`.

Two consequences of the subclass, both verified in code:

- The root element carries `data-dl="x-dl::report"`, `data-dl-class="Report ← PrintOut ← DevlibComponent"` and `data-dl-src="laporan"` (or the `source` prop).
- `DevlibComponent::GROUP_OF` lists `Report` in the `grid` message group and `PrintOut` in `print`. Because the group is looked up from the most specific class first, the texts that a `x-dl::report` registers in the Translation Dictionary go to the `grid` group, those of `x-dl::print-out` to `print`.

### Use it, or use another object

| Need | Object |
|---|---|
| Paper report with preview bar, paper / group switches, criteria box, KPI tiles, subtotals, running balance | `x-dl::report` |
| Nota or voucher with parties, prices, VAT, amount in words | `x-dl::print-out` |
| Fixed-layout transactional document (A5 for 1-5 rows, A4 above) to be shared as PDF / JPEG / link / e-mail | `x-dl::print-doc` (+ `x-dl::doc-share`) |
| Form-like sheet made of numbered sections, small tables and boxes | `x-dl::print-sheet` |
| On-screen analysis list (search, sort, filter, export) | `x-dl::grid` with `mode="report"`; the screen a paper report is usually opened from (`skills/devlib/grid.md`: "cetakan lewat `x-dl::report`") |
| Financial statement lines (balance sheet, profit and loss) on screen | `x-dl::statement` |

### Markup produced

```html
<div class="dl-print" data-dl="x-dl::report" data-dl-class="Report ← PrintOut ← DevlibComponent" data-dl-src="laporan"
     data-dl-print data-config='{"columns":[...],"rows":[...],"totals":{},"currency":[...],"hasPpnKop":false,"summary":[...],"groups":{...},"opening":450}'
     data-paper="a4l" data-prices="1" data-ppn="0" data-group="month">
  <div class="dl-print__bar"> title | Paper | Group | Price | VAT | Ink Saver | Print </div>
  <div class="dl-print__pages" data-p-pages> generated <section class="dl-print__sheet"> per sheet </div>
  <template data-p-head> ... </template> <template data-p-criteria> ... </template> <template data-p-parties> ... </template>
  <template data-p-notes> ... </template> <template data-p-sign> ... </template> <template data-p-foot> ... </template>
</div>
```

The templates carry the server-rendered pieces (company header, criteria box, parties box, notes, signatures, footer); `print.js` copies them into every generated sheet. Extra HTML attributes (`id`, `class`, `style`, `data-*`) and `can="module.action"` go on the root `div`; without the ability nothing is rendered.

## Props

Constructor of `Devlib\Components\PrintOut` (inherited by `Report`): 24 props. Blade names are kebab-case; use `:prop="expr"` for anything that is not a plain string (`:rows="$rows"`, `:opening="450"`, `:prices="false"`).

### Phone reading list

`phone-list` (default `false`, opt-in, inherited from `x-dl::print-out`): on a phone the scaled A4 sheet is unreadable, so with `phone-list` the screen shows one card per row (grouped, with sub totals and the grand total) built from the same columns and rows; printing still uses the sheets. See the prop row `phone-list` in `x-dl::print-out`.

### Header and identity

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `title` | `string` | `''` | Report title in the header box (upper-case by CSS) and in the preview bar. Translated through the dictionary (standard text prop); wrap data values in `raw()`. |
| `subtitle` | `?string` | `null` | Small line under the title. Translated. Not rendered when `null`. |
| `number` | `?string` | `null` | Report number: bold under the title and `title · number` in the preview bar. Data, not translated. |
| `date` | `?string` | `null` | Printed through `Locale::date()` (short style `dd-Mmm-yyyy`, month in the display language), followed by `Page n/N`. A value that cannot be parsed is printed as given. Without `date` only the page number is shown. |
| `status` | `?string` | `null` | Text in the bordered badge at the right of the header box (for example `CONFIRMED`). Printed as given: not translated, not normalised. Reports usually leave it empty; a printout of a document must set it (project rule: every printout carries the record status, `skills/devlib/transaction-lifecycle.md`). |
| `company` | `array` | `[]` | Company header (non-VAT). Keys: `name`, `tagline`, `address`, `contact`, `initials`, `logo` (URL), `logoBw` (URL, black and white). With `logo` the image replaces the initials box, name and tagline (address and contact stay); `logoBw` (or `logo` when missing) shows in ink-saver mode. Optional `style` (`logo_name_address` | `logo_name` | `name`) and `legal_name` (small line under the name when different from `name`) are the same as in `x-dl::print-out`; a logo style with empty `logo` = name only; without `style` nothing changes. Without `initials` the first two letters of `name` are used. Usually `config('devlib.company')`. |
| `company-ppn` | `array` | `[]` | Header of the VAT variant, same keys plus `npwp` (printed as `NPWP <number>`, only in this header). When not empty a **Non VAT / VAT** switch appears and the header changes with it; when empty `company` serves both and there is no switch. Rarely needed for a report. |
| `ppn` | `bool` | `false` | Initial state of the VAT switch (`data-ppn`). Meaningful only together with `company-ppn`. |
| `printed-by` | `?string` | `null` | Name added to the footer of every sheet (`Printed from DevLib · <name>`). Data, not translated. |
| `source` | `?string` | `null` | Free text naming the data source for the catalog / Manifest (`data-dl-src`). Default for `Report`: `laporan`. No visual or runtime effect. |

### Criteria, summary and parties

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `criteria` | `array` | `[]` | Filter box under the header: `label => value` (period, warehouse, item class...). The keys are translated; values are data. First sheet only. Empty = no box. |
| `summary` | `array` | `[]` | KPI tiles above the table, first sheet only. See Summary tiles below. Empty = no tiles. |
| `parties` | `array` | `[]` | Two-column box (`label => value`, value a string or a list of lines; the first line is normal, the others small). Keys translated. First sheet only. A report normally uses `criteria` instead. |

#### Summary tiles

Each item is an array:

| Key | Meaning |
|---|---|
| `label` | Tile caption. NOT translated by the component (`summary` is not in the translated lists): write it in the wanted language. |
| `sum` | Column key. Tile value = sum of that column over ALL rows (not per group, and independent of the Price switch). |
| `last` | Column key. Tile value = that column in the LAST row (order of `rows`), for example the closing balance of a `running` column. |
| `value` | Literal value shown as given (a number is formatted like other numbers; a string is printed escaped). Evaluation order in the script: `sum`, then `last`, then `value`. |
| `signed` | `true` = positive green, negative red, negative printed with a true minus sign. |
| `plus` | `true` = `+` before positive values (only together with `signed`). |

### Table

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `columns` | `array` | `[]` | Column definitions, see Column definition below. `label` is translated. |
| `rows` | `array` | `[]` | List of associative arrays keyed by the column `key`s, plus any key used by `sub`, `running` formulas, `groups`, `rowTones`. Values are data: never translated, escaped (there is no HTML cell). The row number column (`No`) is generated. |
| `groups` | `array` | `[]` | `column key => label` of the columns the user may group by. When not empty a **Group** switch appears (None plus one button per entry). Labels are translated. |
| `group-by` | `?string` | `null` | Column key the table is grouped by from the start (`data-group`). Works without `groups` (then the user cannot change it). A key that is not in `groups` leaves no switch button highlighted. |
| `opening` | `?float` | `null` | Opening balance for `running` columns (`null` counts as 0). Not shown anywhere by itself; show it with a `summary` tile (`'value' => 450`). |
| `totals` | `array` | `[]` | Nota-style totals block: `of` (column key to sum), `discount` (amount), `ppn` (percentage), `extra` (`[[label, amount], ...]`), `net` (label of the final line). Printed on the last sheet only while prices are shown, with the amount in words. A report normally uses `sum` columns instead and leaves this empty. Details in `resources/guide/print-out.md`. |
| `currency` | `string` | `'IDR'` | Key of `Devlib\Terbilang::CURRENCIES` (`IDR`, `USD`, `EUR`, `SGD`, `MYR`, `AUD`, `JPY`, `CNY`, `GBP`). Used only by the `totals` block (VAT rounding and currency word of the amount in words); report numbers are plain numbers without a currency. Unknown code: treated as no currency. |

#### Column definition

`columns[]` items (the script adds `type => 'text'` when missing):

| Key | Meaning |
|---|---|
| `key`, `label` | Row key and header text (required). |
| `type` | `text` (default); `number` and `money` (right aligned, up to 2 decimals, trailing zeros trimmed, display-language separators; both behave the same, there is no currency symbol); `badge` (small tag, centered; `tones` maps value to `success`, `danger`, `warn`, `info`, `violet`, anything else = grey). |
| `tones` | `badge` only: `value => tone` map. |
| `rowTones` | `value => tone` map. A row whose value in this column matches gets a tinted background; only `danger` and `warn` have styles (`danger` also a left bar). |
| `price` | `true` = hidden by the **Without Price** switch. The Price switch exists only when at least one column has `price`. |
| `sub` | Key of another row field printed as a small second line in the cell. |
| `group` | Heading above this and the following columns with the same `group` (two-level header; not translated). Columns of one group must be adjacent. |
| `width` | CSS width of the header cell (`'62px'`); ignored when any visible column has `group`. |
| `sum` | `true` = the column is totalled: a Total row at the end of the table, and a subtotal row per group when grouped. No `sum` column = no total or subtotal rows. Only visible columns are summed. |
| `signed`, `plus` | `signed`: positive green / negative red with a true minus sign; `plus`: `+` before positives (needs `signed`). |
| `bold` | `true` = bold values. |
| `running`, `prev` | `running` is a formula over other column keys, for example `'in - out + adj'`; the value is accumulated row by row starting at `opening` and stored in this column (the column's own row values are overwritten). `prev` is the key of a row field that receives the balance BEFORE each row. Only identifiers, numbers, spaces and `+ - * / ( )` are accepted (anything else throws `invalid formula`). Each step is rounded to 2 decimals. |

### Paper, price and signatures

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `paper` | `string` | `'a4'` | Initial paper: `a4` (A4 portrait 210 x 297 mm), `a4l` (A4 landscape 297 x 210 mm), `a5` (A5 landscape 210 x 148 mm). Any other value sizes the sheet and `@page` as A4 portrait and highlights no paper button. The user can switch in the bar. |
| `prices` | `bool` | `true` | Initial state of the **Price** switch (`data-prices`). `false` hides columns with `price => true` and the whole `totals` block. Irrelevant to reports without `price` columns. |
| `signatures` | `array` | `[]` | Signature boxes, left to right: `[role, note?, name?]` (for example `['Prepared by', 'Warehouse staff']`). `role` and `note` are translated; the name line is `( name )` or a dotted line. Printed in the closing part on the last sheet. Empty = none. |

The default slot `notes` (`<x-slot:notes>`, HTML) is a note box above the signatures on the last sheet. It is a slot, not a prop.

## Behavior

### Preview bar

A sticky bar above the sheets: title and number, then **Paper** (always), **Group** (only with `groups`), **Price** (only with a `price` column), **Non VAT / VAT** (only with `company-ppn`), **Ink Saver** (toggle) and **Print**. Each segment click sets the matching `data-*` on the root and rebuilds all sheets. The bar is hidden on paper. The segment buttons mark the current choice only with the class `is-active` (no `aria-pressed`).

### Sheet layout and pagination

`Print.build()` produces sheets of the chosen paper. Order on the first sheet: header (company, title box), parties, criteria, KPI tiles, table. Later sheets get the header and a fresh table with the header row repeated. Rows are inserted one by one; after each insert the body area (`[data-p-body]`) is measured (`scrollHeight <= clientHeight + 1`); a row that does not fit moves to a new sheet. The closing part (grand totals block, notes, signatures) is kept in one `dl-print__tail`; if it does not fit, up to the last two table rows move with it to a new sheet. Each sheet shows `Page n/N` in the header and `sheet n/N` in the footer. The sheets are rebuilt once more after web fonts finish loading, because row heights change.

Page size is written by the script into `<style id="dl-print-page">`: `@page{size:A4 portrait | A4 landscape | A5 landscape; margin:0}`. There is one such style element per document.

### Rows, groups and totals

- Row numbers `No` run continuously through the whole table, also across groups.
- With a group column selected, rows are regrouped in the order of FIRST APPEARANCE of each group value (no sorting). Each group gets a heading row `<value> · n rows` (`pr_rows`), its rows and, when some visible column has `sum`, a `Subtotal <value>` row. A grand `Total` row closes the table when some visible column has `sum`.
- The label of subtotal / total rows is written into the first visible column; if that column itself has `sum`, its cell shows the number and the label is lost.
- `running` columns are computed once at start from the original order of `rows`, before grouping. Grouping by a column that is not aligned with the order of `rows` therefore shows balances that jump: sort `rows` on the server in the order the balance must accumulate.
- Summary tiles always cover all rows, ignoring grouping.

### Numbers, dates and language

Numbers follow the display language (`1.234,56` or `1,234.56`; separators from `window.DL_NUM`), up to 2 decimals. The date uses `Devlib\Locale::date()`. The footer prints the server render time (`Locale::dateTime(now())`). Fixed words (preview bar, footer, `Subtotal`, `Total`, `rows`, `No`) come from the `pr_*` dictionary keys: PHP side through `Lang::t()`, JS side through `Devlib.h.T`. Translated props: `title`, `subtitle`, `columns[].label`, `groups` labels, `signatures` role and note, and the KEYS of `parties` and `criteria`. Not translated: row values, `number`, `status`, `printed-by`, `summary[].label`, `columns[].group`, `totals.extra` labels and `totals.net`.

### Ink saver and printing

**Ink Saver** toggles `is-bw` on the root: sheets in grey scale, black-and-white logo (`logoBw`), plain table header backgrounds. **Print** calls `window.print()`; the browser prints the sheets as vector text (selectable, "Save as PDF" works). During print everything outside `.dl-print__pages` is hidden and the sheets lose their shadow and break after each page.

### Access and accessibility

`can="module.action"` on the tag hides the whole report without the ability. The bar uses native `button`s (Tab, Enter and Space work). The sheets are plain `section` / `table` markup without roles, labels or `aria-*`.

## Data and events

### What the script reads

Everything travels in `data-config` (JSON from `PrintOut::config()`): `columns` (each with `type`), `rows`, `totals` (object), `currency` (the `Terbilang` row of the currency code or `null`), `hasPpnKop`, `summary`, `groups` (object, translated labels), `opening`. The state of the switches lives in `data-paper`, `data-prices`, `data-ppn` and `data-group` on the root. Nothing is posted and no request is made; the data is in the page source, so a very large report makes a large HTML attribute and the row-by-row measuring gets slow.

### Events and JS API

| Item | Meaning |
|---|---|
| `dl:print-built` | Bubbling `CustomEvent` on the root after every build; `detail.pages` = number of sheets. |
| `el.dlComponent` | The `Print` instance (methods are auto-bound). |
| `el.dlPrint.build()` | Rebuild after changing a dataset value from script, for example `el.dataset.paper = 'a5'; el.dlPrint.build();` (what a segment click does). |

### Markup hooks

`data-dl-print` (root), `data-p-paper`, `data-p-group`, `data-p-prices`, `data-p-ppn` (segments; each button has `data-value`), `data-p-bw` (ink saver), `data-p-print`, `data-p-pages` (sheet container), `data-p-body` and `data-p-pg` (inside each sheet), `data-p-kop="0|1"` (non-VAT / VAT header), and the six `template[data-p-head|criteria|parties|notes|sign|foot]`. Classes: `dl-print`, `dl-print__sheet`, `dl-print__table`, `dl-print__criteria`, `dl-print__kpis`, `dl-print__tail`, `dl-print__grp` / `__sub` / `__grand` (group heading, subtotal, grand total rows), `dl-print__badge`, `is-row-danger` / `is-row-warn`, `is-pos` / `is-neg` / `is-zero`, `is-bw`. Colours are the `--dl-pr-*` tokens declared on `:root`.

## Rules and pitfalls

- Declare content, do not draw: no raw `<table>`, no inline script (lint `L001`, `L002`, `L003` from `Devlib\Lint\ViewLinter` apply to the Blade file; no rule is specific to this object).
- Catalog page `p5-report` (Stock Card, Master Stock) is in `Catalog::APPROVED` (2026-09-26): do not change its look or behavior without explicit approval of the owner.
- `x-dl::report` is the paper report. The on-screen list a user opens it from is `x-dl::grid mode="report"`; they are different objects with different props.
- Sort `rows` on the server. Grouping keeps first-appearance order and `running` accumulates in the order of `rows`.
- `summary[].label` and `columns[].group` are not translated: write them in the target language.
- A `sum` column placed first loses the label of the subtotal and total rows; put a text column first.
- `prices="false"` hides the whole `totals` block; for a report this matters only if you use `totals`.
- A `group-by` value that is not a column of the rows produces one group with an empty name (heading ` · n rows`).
- Badge `tones` only style `success`, `danger`, `warn`, `info`, `violet`; `rowTones` only `danger` and `warn`.
- The `running` formula goes through `new Function` after a character check. Keep it to column keys and arithmetic; never build it from user input.
- Measuring is done row by row with forced layout. Thousands of rows are slow and the JSON sits in the page: limit or paginate on the server.
- Two `.dl-print` objects on one page share one `<style id="dl-print-page">` (the last build wins) and overlap when printed (`.dl-print__pages` is absolutely positioned in print).

### Notes on inconsistencies found while writing this entry (not fixed)

- Fixed 2026-10-22: the `NPWP <number>` line of the VAT header (`company-ppn.npwp`) is now printed (the loop compared `$isPpn === '1'` while PHP makes the key an integer). See `x-dl::print-out`.
- `resources/assets/devlib.css` (the `@media print{ body *{visibility:hidden} ...}` block right after `.dl-print.is-bw`) is not scoped: browser printing of any page that loads the stylesheet hides everything outside `.dl-print__pages`. The label sheet and the login card scope the same trick with `body:has(...)`.
- The header comment of `src/Components/PrintOut.php` lists the summary keys as `sum` / `value` (+ `signed`), but `print.js` also supports `last` and `plus`; the column keys `bold` and `plus` are not listed there either.
- `Report` is placed in the `grid` message group in `DevlibComponent::GROUP_OF` although its texts are print texts (`PrintOut` is in `print`).
- In a two-level header (`columns[].group`) `print.js` writes the hard-coded word `No` instead of the dictionary key `pr_no`.
- `print.js` writes `data-paper-now` on the root; nothing reads it.
- `print-out.blade.php` falls back to `mb_substr($c['name'] ?? 'TM', 0, 2)` for the logo initials, so a company array without `initials` and `name` shows `TM` (a project abbreviation inside the library).
- The footer text `Printed from DevLib` is the dictionary key `pr_printed_from`; it cannot be changed by a prop.
- The header cell class is `num` for every type except `text`, so `badge` headers also get the numeric class.

## Example

Master stock report, adapted from the catalog demo `report-master-stock` (fictional data; the demo takes its rows from `App\Support\DemoData::masterStock()`).

```blade
<x-dl::report title="Master Stock Report" subtitle="Raw materials" number="RPT-MST/26/00042"
    :company="config('devlib.company')" printed-by="Warehouse staff"
    :criteria="['Start date' => '01-Aug-2026', 'End date' => '21-Aug-2026', 'Warehouse' => 'All warehouses', 'Active items' => 'Yes']"
    group-by="cat" :groups="['cat' => 'By category', 'wh' => 'By warehouse']"
    :summary="[
        ['label' => 'Total opening', 'sum' => 'open'],
        ['label' => 'Total in', 'sum' => 'in', 'signed' => true, 'plus' => true],
        ['label' => 'Total out', 'sum' => 'out'],
        ['label' => 'Total closing', 'sum' => 'end'],
    ]"
    :columns="[
        ['key' => 'code', 'label' => 'Item code'],
        ['key' => 'name', 'label' => 'Item name', 'sub' => 'min'],
        ['key' => 'wh', 'label' => 'Warehouse'],
        ['key' => 'open', 'label' => 'Opening', 'type' => 'number', 'sum' => true],
        ['key' => 'in', 'label' => 'In', 'type' => 'number', 'sum' => true, 'signed' => true],
        ['key' => 'out', 'label' => 'Out', 'type' => 'number', 'sum' => true],
        ['key' => 'end', 'label' => 'Closing', 'type' => 'number', 'sum' => true, 'bold' => true],
        ['key' => 'status', 'label' => 'Status', 'type' => 'badge', 'tones' => ['OK' => 'success', '< MIN' => 'danger'], 'rowTones' => ['< MIN' => 'danger']],
    ]"
    :rows="[
        ['code' => 'RM-0001', 'name' => 'Art paper 150 gr', 'min' => 'min 500', 'cat' => 'Paper', 'wh' => 'WH-01', 'open' => 1240, 'in' => 2500, 'out' => 2180, 'end' => 1560, 'status' => 'OK'],
        ['code' => 'RM-0002', 'name' => 'Duplex 350 gr', 'min' => 'min 800', 'cat' => 'Paper', 'wh' => 'WH-01', 'open' => 300, 'in' => 0, 'out' => 120, 'end' => 180, 'status' => '< MIN'],
        ['code' => 'RM-0021', 'name' => 'Offset ink cyan', 'min' => 'min 40', 'cat' => 'Ink', 'wh' => 'WH-03', 'open' => 98, 'in' => 48, 'out' => 28, 'end' => 118, 'status' => 'OK'],
    ]" />
```

Stock card with a running balance, A4 landscape and signatures (shape of the demo `report-stock-card`):

```blade
<x-dl::report title="Stock Card" subtitle="Stock Card Report" number="RPT-CARD/26/00318"
    :company="config('devlib.company')" paper="a4l" :opening="450" group-by="month" :groups="['month' => 'By month']"
    :criteria="['Item code' => 'RM-0004', 'Warehouse' => 'WH-01', 'Period' => '01-Jul-2026 - 21-Aug-2026']"
    :summary="[
        ['label' => 'Opening balance', 'value' => 450],
        ['label' => 'Total in', 'sum' => 'in', 'signed' => true, 'plus' => true],
        ['label' => 'Total out', 'sum' => 'out'],
        ['label' => 'Closing balance', 'last' => 'end'],
    ]"
    :columns="[
        ['key' => 'date', 'label' => 'Date'],
        ['key' => 'doc', 'label' => 'Document no.', 'sub' => 'src'],
        ['key' => 'type', 'label' => 'Type', 'type' => 'badge', 'tones' => ['IN' => 'info', 'OUT' => 'danger', 'ADJ' => 'warn']],
        ['key' => 'in', 'label' => 'In', 'type' => 'number', 'sum' => true, 'signed' => true],
        ['key' => 'out', 'label' => 'Out', 'type' => 'number', 'sum' => true],
        ['key' => 'adj', 'label' => 'Adjustment', 'type' => 'number', 'sum' => true, 'signed' => true],
        ['key' => 'end', 'label' => 'Balance', 'type' => 'number', 'running' => 'in - out + adj', 'prev' => 'open', 'bold' => true],
    ]"
    :rows="[
        ['date' => '03-Jul-2026', 'month' => 'July 2026', 'doc' => 'IN/26/00028', 'src' => 'Source: PV/26/00098', 'type' => 'IN', 'in' => 2000, 'out' => 0, 'adj' => 0],
        ['date' => '05-Jul-2026', 'month' => 'July 2026', 'doc' => 'OUT/26/00034', 'src' => 'Source: WO/26/00281', 'type' => 'OUT', 'in' => 0, 'out' => 1100, 'adj' => 0],
        ['date' => '12-Aug-2026', 'month' => 'August 2026', 'doc' => 'OPN/26/00019', 'src' => 'Stock take', 'type' => 'ADJ', 'in' => 0, 'out' => 0, 'adj' => -30],
    ]"
    :signatures="[['Prepared by', 'Warehouse staff'], ['Checked by', 'Warehouse head']]">
    <x-slot:notes><b>Note:</b> Balances are calculated in the order of the rows.</x-slot:notes>
</x-dl::report>
```

Header style per variant (Non VAT with logo, address and legal name; VAT with the name only):

```blade
<x-dl::report title="Stock Report" :columns="$columns" :rows="$rows"
    :company="['name' => 'Toko Maju', 'legal_name' => 'PT Maju Jaya Abadi', 'style' => 'logo_name_address', 'address' => 'Jl. Melati 5', 'contact' => 'Telp 021-777', 'logo' => asset('logo.svg'), 'logoBw' => asset('logo-bw.svg')]"
    :company-ppn="['name' => 'Toko Maju', 'legal_name' => 'PT Maju Jaya Abadi', 'style' => 'name', 'npwp' => '01.234.567.8-901.000']" />
```

Full key list and how an application fills the header from its settings table: `x-dl::print-doc`.

## Related

- `x-dl::print-out`: the parent class; the same props, view and script for nota-style documents (parties, prices, VAT, totals, amount in words). Its entry `resources/guide/print-out.md` describes the nota side in more depth.
- `x-dl::print-doc`: separate fixed-layout transactional document (takes a `Devlib\PrintDoc\Document` object, A5 for 1-5 rows, A4 above, own `PrintDoc` script); shares no PHP or JS code with the report.
- `x-dl::doc-share`: Print menu (PDF, JPEG, online link + QR) and Email dialog for a document area; the report has its own Print button and does not need it.
- `x-dl::print-sheet`: A4 sheet of numbered sections for forms and multi-table reports; takes the company header from `Devlib\PrintDoc\Document::companyOf()` (a different array shape than the `company` prop here).
- `x-dl::grid` (`mode="report"`): the on-screen report list from which a paper report is opened.
- `x-dl::statement`: financial statement lines on screen.
- `x-dl::chart`: charts for the same data on screen.
- `x-dl::button`: the preview bar uses `preset="print"` and the ink-saver button.
- `skills/devlib/transaction-lifecycle.md`: every printout of a document carries its status.
