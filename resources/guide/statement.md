## Overview

`<x-dl::statement>` shows a financial statement line by line: balance sheet, income statement (profit and loss), cash flow. The object knows nothing about accounts or the database: the screen hands over finished lines (`rows`), each with a `kind` that decides how the line looks (group heading, normal line, subtotal, net total, note) and a list of amounts, one per amount column. The object only formats the amounts in the display language, colours negatives red and draws the table. One amount column is a plain statement; two or more columns give a period comparison (for example this year and last year).

Use it for statements read on screen. Do NOT use it for:

| Need | Better object |
|---|---|
| Printed paper report with preview bar, paper sizes, pagination | `x-dl::report` |
| Searchable, sortable list of records | `x-dl::grid` |
| Plain small table inside a form or card | `x-dl::table` |
| Cross-tab with user-chosen dimensions | `x-dl::pivot` |
| Totals, sub-totals or ratios computed for you | none: this object never calculates, the caller supplies every number |

### Markup produced

```html
<div class="dl-stmt" data-dl="x-dl::statement" data-dl-class="Statement ← DevlibComponent" data-dl-src="baris laporan keuangan">
  <div class="dl-stmt__head"><b>Title</b><span>Subtitle</span></div>           <!-- only when title or subtitle is set -->
  <table class="dl-stmt__table">
    <thead><tr><th></th><th class="num">2026</th><th class="num">2025</th></tr></thead>   <!-- only with 2 or more columns -->
    <tbody>
      <tr class="dl-stmt__row is-group"><td colspan="3">ASSETS</td></tr>
      <tr class="dl-stmt__row is-line"><td>Label</td><td class="num">IDR 480.000.000</td><td class="num is-neg">IDR -5.000</td></tr>
    </tbody>
  </table>
</div>
```

Extra HTML attributes (`id`, `class`, `style`, `data-*`) and `can="module.action"` go on the root `div`; without the ability nothing is rendered. The object has no JavaScript.

## Props

Constructor of `Devlib\Components\Statement` (6 props). Use `:prop="expr"` for arrays.

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `rows` | `array` | `[]` | The lines, in display order. See [Row shape](#row-shape). |
| `columns` | `array` | `['Amount']` | Headers of the amount columns, one string each (for example `['2026', '2025']`). The number of columns decides how many entries of every row's `values` are shown. The header row is drawn only when there are 2 or more columns; with exactly one column the default `Amount` header is never shown. The strings are translated through the dictionary (`columns` is a translated list). |
| `title` | `string` | `''` | Bold title above the table. Translated (standard text prop). The head block exists when `title` or `subtitle` is not empty. |
| `subtitle` | `string` | `''` | Small grey text next to the title (for example `Unaudited management accounts · IDR`). Translated. |
| `currency` | `string` | `'IDR'` | Currency code written before every amount by `Devlib\Locale::money()` (code, not symbol, for example `IDR 1.250.000.000`). It is only a text prefix: no conversion, and amounts are always shown with 0 decimals. |
| `source` | `?string` | `null` | Free text naming where the data comes from, shown in the catalog / Manifest (`data-dl-src`). Default `baris laporan keuangan`. No visual or runtime effect. |

### Row shape

Each entry of `rows` is an array:

| Key | Meaning |
|---|---|
| `kind` | One of `group`, `line`, `total`, `net`, `note` (the constant `Statement::KINDS`). Missing or unknown value = `line`. |
| `label` | Text of the line. Required. Printed escaped. NOT translated by the object (see Behavior). |
| `values` | List of numbers, one per column in `columns` (index `0` = first column). `null`, a missing index or a non-numeric value = empty cell. Ignored for `group` and `note`. A numeric string is accepted (`is_numeric`) and cast to float. |

How each kind looks (class `dl-stmt__row is-<kind>`):

| `kind` | Rendering |
|---|---|
| `group` | One cell across the whole width (`colspan` = columns + 1), tinted background, bold: a section heading such as `ASSETS`. |
| `line` | Label (indented 26 px) plus amounts. The default. |
| `total` | Bold, thin line above: a subtotal such as `Total revenue` or `Gross profit`. |
| `net` | Extra bold, accent background, thick line above and double line below: the final result such as `NET PROFIT FOR THE PERIOD` or `TOTAL ASSETS`. |
| `note` | One cell across the whole width, small italic grey text: a remark. |

## Behavior

### Amounts

Each amount cell is `Devlib\Locale::money($value, $currency)`: the currency code, a space and the number with the separators of the display language (`1.234.567` in Indonesian, `1,234,567` in English), 0 decimals, so cents are rounded away. Negative numbers (`< 0`) get class `is-neg` and are shown in red; the minus sign comes from the number formatting (for example `IDR -830.000.000`). There are no brackets and no thousands scaling. Cells have `white-space: nowrap`, tabular digits and right alignment.

### Comparison columns

Give `columns` two or more labels and put one value per column in each row; the header row appears with an empty first cell and right-aligned column labels. All rows must follow the same column order. The object does not compute differences or percentages: add a column such as `Change` yourself.

### Language and translation

- `title`, `subtitle` and the `columns` strings are translated from English through the dictionary (`Lang::text`). Wrap data (a period text, an entity name) in `raw()` so it is not translated or recorded in the dictionary.
- `rows[].label` is printed as given. The header comment of `Statement.php` says labels are English "translated by the object", but `rows` is not in the translated lists of `DevlibComponent` and the view echoes the label directly. Pass already translated labels (or call `Lang::text()` yourself), or wrap data values in `raw()` for clarity.
- Numbers follow the display language through `Locale`.

### Access, theme and accessibility

`can="module.action"` hides the whole object without the ability. Colours come from theme tokens (`--dl-ink`, `--dl-danger`, `--dl-accent-soft`, ...), so dark themes work. The table is plain `table` / `tr` / `td` markup: there is no `caption`, no `scope` on header cells and no `aria-*`. The table has no horizontal scroll wrapper and the block is limited to `max-width: 760px`.

## Data and events

- Server data in, markup out. Nothing is posted, no request is made, no `dl:*` event is emitted, no `el.dlXxx` API exists and there is no `data-dl-js`.
- Public PHP helpers used by the view: `kind(array $row): string` (validated kind) and `money(float|int|null $v): string` (formatted amount, empty string for `null`).
- Hooks for CSS or tests: `.dl-stmt`, `.dl-stmt__head`, `.dl-stmt__table`, `.dl-stmt__row` with `is-group|is-line|is-total|is-net|is-note`, `td.num`, `td.is-neg`.
- Building rows from a ledger is the screen's job: group the accounts, sum the subtotals and pass the result. A usual shape is `[['kind' => 'group', 'label' => 'ASSETS'], ['label' => ..., 'values' => [...]], ['kind' => 'total', ...], ['kind' => 'net', ...]]`.

## Rules and pitfalls

- The object never adds up anything: a `total` or `net` row shows exactly the numbers you give. Compute them on the server from the same source as the lines so the statement balances.
- `values` is positional. A row with fewer values than `columns` leaves blank cells; extra values are ignored.
- `group` and `note` rows ignore `values`.
- `currency` is only a prefix; amounts are rounded to whole units. For currencies with cents, do not use this object as is, or format the amounts into the label yourself.
- Because of the prefix, every cell repeats the currency code (`IDR 480.000.000`). If you want a bare number, there is no prop for it.
- A single column hides its header, so say what the amounts are in `subtitle` (for example `IDR`).
- Do not write HTML in `label`: it is escaped.
- Keep the table inside a normal page width. Many columns overflow because there is no scroll wrapper.
- Lint: no rule is specific to this object. The general screen rules `L001` (no raw `table`), `L002` (no inline script) and `L003` (no hex colours) apply to the Blade file.
- The catalog page `p6-statement` (Financial Statement) is not in `Catalog::APPROVED`.

### Notes on inconsistencies found while writing this entry (not fixed)

- The header comment of `src/Components/Statement.php` says the label is "English text (translated by the object)", but the labels in `rows` are not translated (only `title`, `subtitle` and `columns` are).
- `Statement` is not listed in `DevlibComponent::GROUP_OF`, so its texts are recorded in the general dictionary group, not in a statement or grid group.
- `group` / `note` rows ignore `values` in the view, but nothing validates or warns when they are given.

## Example

Adapted from the catalog demo `statement` (fictional figures).

```blade
<x-dl::statement title="Statement of Profit or Loss" subtitle="Unaudited management accounts · IDR" :rows="[
    ['kind' => 'group', 'label' => 'REVENUE'],
    ['label' => 'Sales revenue', 'values' => [1250000000]],
    ['kind' => 'total', 'label' => 'Total revenue', 'values' => [1250000000]],
    ['kind' => 'group', 'label' => 'COST OF REVENUE'],
    ['label' => 'Cost of goods sold', 'values' => [-830000000]],
    ['kind' => 'total', 'label' => 'Gross profit', 'values' => [420000000]],
    ['kind' => 'note', 'label' => 'Income tax is estimated monthly.'],
    ['kind' => 'net', 'label' => 'NET PROFIT FOR THE PERIOD', 'values' => [338000000]],
]" />
```

Period comparison with two columns:

```blade
<x-dl::statement title="Comparative (two columns)" :columns="['2026', '2025']" :rows="[
    ['kind' => 'group', 'label' => 'ASSETS'],
    ['label' => 'Cash and cash equivalents', 'values' => [480000000, 410000000]],
    ['label' => 'Trade accounts receivable, net', 'values' => [920000000, 1010000000]],
    ['kind' => 'net', 'label' => 'TOTAL ASSETS', 'values' => [1400000000, 1420000000]],
]" />
```

## Related

- `x-dl::report`: printable report on paper (preview bar, paper sizes, groups, subtotals, running balance).
- `x-dl::grid`: searchable list of records; `mode="report"` is the on-screen analysis list.
- `x-dl::table`: simple table with right-aligned number columns, for use inside forms and cards.
- `x-dl::pivot`: cross-tab with user-chosen row and column fields.
- `x-dl::chart`: charts of the same figures.
- `x-dl::card`: a box to put a statement in.
