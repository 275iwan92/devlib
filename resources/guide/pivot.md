## Overview

`<x-dl::pivot>` is the Grid Pivot of DevLib (Grid Report family): the screen hands over **flat transaction rows** and the object draws a cross-table (rows x columns x one value) that the **user re-arranges on screen** with five selectors, an aggregate switch, a "% total" switch, a heatmap switch and a CSV export. Clicking a cell shows the source rows behind that number. Everything is computed in the browser from the rows in the page (`pivot.js`, class `Pivot`); the server is not called.

Use it when the question is "how much, by what, over what" and the user decides the arrangement: sales by customer and month, quantity by category and sales person.

Pick another object when:

- the user only needs a fixed list (search, sort, page): `x-dl::grid` (`mode="report"`).
- the summary must lead to a detail list: `x-dl::drilldown` (two grids) or the Nested Grid.
- the arrangement is fixed and printed: `x-dl::report` or `x-dl::statement`.
- a chart is wanted: `x-dl::chart`.
- the data set is large (tens of thousands of rows): the rows travel inside one HTML attribute; aggregate on the server first.
- the figures must be edited: `x-dl::sheet`.

### Markup produced

```html
<div class="dl-pivot" data-dl="x-dl::pivot" data-dl-class="Pivot ← DevlibComponent" data-dl-src="data transaksi (datar)"
     data-dl-pivot data-config='{...}' data-rows='[...]'>
  <div class="dl-pivot__bar">
    <b class="dl-pivot__title">Sales</b>                      <!-- only when title is not empty -->
    <label><span>Row</span><select data-p-row="0"></select></label>
    <label><span>Sub-row</span><select data-p-row="1"><option value="">—</option></select></label>
    <label><span>Column</span><select data-p-col>...</select></label>
    <label><span>Value</span><select data-p-val></select></label>
    <div class="dl-segment" data-p-agg> buttons data-value = sum | count | avg | min | max </div>
    <label class="dl-choice"><input type="checkbox" data-p-pct> % total</label>
    <label class="dl-choice"><input type="checkbox" data-p-heat checked> Heatmap</label>
    <button ... data-p-export>Export</button>
  </div>
  <div class="dl-pivot__wrap"><table class="dl-pivot__table" data-p-table></table></div>
</div>
```

The selectors and the table body are filled by JavaScript at start and after every change. Extra HTML attributes (`id`, `class`, `style`, `data-*`) and `can="module.action"` go on the root `div`.

## Props

Constructor of `Devlib\Components\Pivot` (10 props). Use `:prop="expr"` for arrays.

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `rows` | `array` | `[]` | The flat data: a list of associative arrays, one per transaction line, all with the same keys (`['customer' => 'X', 'category' => 'Box', 'month' => '01 Jan', 'qty' => 3000, 'amount' => 360000]`). JSON-encoded into `data-rows`. Dimension values are compared as strings (`null` becomes `''`); measure values are read as numbers (`+value || 0`, so non-numeric text counts as 0). |
| `fields` | `array` | `[]` | Map `row key => label` of the **dimensions** the user may put on Row, Sub-row and Column. These labels fill the three dimension selectors, the table headers and the source-rows modal headers. Required: an empty map leaves the selectors empty and the table fails to build. Not translated by the dictionary (write the label in the screen language). |
| `measures` | `array` | `[]` | Map `row key => label` of the **numeric** columns the user may put on Value (selector "Value"). Required, same notes as `fields`. |
| `rows-by` | `array` | `[]` | Initial Row selection: one key (one level) or two keys (level 1 and level 2 = Sub-row). More than two are cut to two. Empty = the first key of `fields`. A second key equal to the first is ignored (treated as no sub-row). |
| `col-by` | `?string` | `null` | Initial Column dimension (a key of `fields`). `null` = no column dimension: the table has only the Total column. |
| `value` | `?string` | `null` | Initial measure (a key of `measures`). `null` = the first key of `measures`. |
| `agg` | `string` | `'sum'` | Initial aggregate: `sum`, `count`, `avg`, `min` or `max`. `count` counts source rows and ignores the measure. Any other string breaks the table (no such aggregate in the script). |
| `formats` | `array` | `[]` | Map `measure key => format`. The only value the script recognises is `'money'`: figures of that measure are shown as `IDR 1.250.000` (rounded to whole units; not used when the aggregate is `count`). Any other or missing format shows a plain number: 0 decimals, except `avg` which shows up to 2. |
| `title` | `string` | `''` | Heading at the left of the toolbar (`<b class="dl-pivot__title">`, also the anchor of the Technical Guide link). Translated through the dictionary. Empty = no heading. |
| `source` | `?string` | `null` | Free text naming where the data comes from (`data-dl-src`); default `data transaksi (datar)`. No runtime effect. |

All the keys used in `fields`, `measures`, `rows-by`, `col-by` and `value` must exist in the rows. The initial choices are only the starting point: the user can change every selector (the setting is not remembered after reload).

## Behavior

### The selectors

| Control | Effect |
|---|---|
| Row (`data-p-row="0"`) | Dimension of the first level; always set (no blank option). |
| Sub-row (`data-p-row="1"`) | Optional second level. With a second level each first-level group gets one line per sub-value (first-level cell merged with `rowspan`) and a **Subtotal** line (class `is-sub`). Same field as Row = ignored. |
| Column (`data-p-col`) | Optional dimension spread across columns; one column per distinct value. Blank = none. |
| Value (`data-p-val`) | Measure to aggregate. |
| Sum / Row count / Avg / Min / Max (`data-p-agg`) | Aggregate applied per cell. `Row count` counts the lines. The active button has `is-active`. |
| % total (`data-p-pct`) | Shows each cell as a percentage of its **row total** (one decimal, `–` when that total is 0), the Total column and the column totals as a percentage of the **grand total**. Also applies to avg/min/max where the percentage is not meaningful. |
| Heatmap (`data-p-heat`, on by default) | Colours body cells with the accent colour; intensity = |value| / largest body cell (CSS variable `--k`, max 0.85). Only when there is a Column dimension. Subtotal and total cells are not coloured. |
| Export (`data-p-export`) | Downloads `pivot.csv` of what is on screen (see below). |

### Table layout

- Header: first-level label, sub-level label (if any), one `<th class="num">` per column value, `Total`.
- Body lines sorted by value: first level with `localeCompare('id', {numeric: true})` (so `2` sorts before `10`); sub-rows with the plain `Array.sort()`. Column values use the same locale sort as the first level. Use sortable labels (`01 Jan`, `02 Feb`, ...) for months.
- Footer: `Grand Total` and the total per column; background is the navy token.
- A cell without matching rows (or whose aggregate is 0) shows `–` (`dl-pivot__zero`). Total cells always show the number.
- Numbers follow the application language through `Devlib.h.fmt` (`1.234,56` / `1,234.56`).

### Source rows (cell click)

Every body cell, subtotal cell and the right-hand Total cell is clickable (`data-p-cell`, JSON `[level1, level2|null, columnValue|null]`); the column-total cells in the footer are not. A click filters the original `rows` by the first-level value, the sub-level value (if the line has one) and the column value (if the cell has one), then:

1. fires `dl:pivot-cell` on the root (bubbles, **cancelable**);
2. when the listener did not call `preventDefault()`, opens a modal (`.dl-mov.is-on` with `.dl-modal`) titled `value · value · value - N source rows`, listing the filtered rows with the **first 7 keys of the first row** as columns (labels from `fields` / `measures`, otherwise the raw key). The modal closes by clicking the dark overlay or the x button; there is no Esc handler.

A cell showing `–` is still clickable (the modal may show 0 rows).

### Export

`Export` builds a CSV from the table as drawn: BOM + UTF-8, separator `;`, every value quoted, header with the field labels, one line per body line (Subtotal lines have the label `Subtotal <value>` in the first column), the Total column and the `Grand Total` line. Cells are exported as **raw numbers** (not formatted, and not as percentages even when `% total` is on). The file name is always `pivot.csv`. The export does not neutralise values that start with `=`, `+`, `-` or `@`.

### Language

Translated through the dictionary: `title`, and the toolbar words Row, Sub-row, Column, Value, Sum, Row count, Avg, Max (`Lang::text`, at render) and the modal text `N source rows` (`pv_source_rows`, at run time). Written in English only: `Min`, `% total`, `Heatmap`, `Export` (button, always English), `Total`, `Grand Total`, `Subtotal`. The labels in `fields` and `measures` and all row values are data and are not translated. The dictionary group is `grid`.

### Access (`can`)

`can="module.action"`: without the ability nothing is rendered. The rows are in the page source for anyone who can open the screen (see Security).

### Accessibility as implemented

Native `<select>`, checkboxes and buttons (keyboard operable, labels wrap the selects). The table has no `scope`/`aria-*`; the cells that open the source rows are plain `<td>` elements reachable by mouse only (no `tabindex`, no key handler).

## Data and events

### data-config (JSON on `data-config`)

Built by `Pivot::config()`:

| Key | Content |
|---|---|
| `fields` | object `key => label` (dimensions) |
| `measures` | object `key => label` |
| `rowsBy` | list of 1 or 2 keys; PHP falls back to `[first key of fields]` |
| `colBy` | key or `null` |
| `value` | key; falls back to the first key of `measures` |
| `agg` | `sum` / `count` / `avg` / `min` / `max` |
| `formats` | object `measure key => 'money'` |

`data-rows` holds the JSON of `rows`.

### Event

| Event | Fired on | `detail` | Notes |
|---|---|---|---|
| `dl:pivot-cell` | the pivot root, bubbles, cancelable | `{ rows: [...source rows of the clicked cell...], filter: { <row field>: v, <sub-row field>: v, <column field>: v } }` | `filter` contains only the dimensions that apply to the clicked cell (a Total cell has no column key; a subtotal line has no sub-row key). `preventDefault()` suppresses the built-in modal so the screen can show its own list or navigate. |

```js
pivot.addEventListener('dl:pivot-cell', e => {
  e.preventDefault();                      // replace the built-in modal
  openDetail(e.detail.filter, e.detail.rows);
});
```

(Put the listener in a DevLib JS component or the project script, never inline on the screen: lint L002.)

### JS access

`el.dlPivot = { st, draw }` on the root. `st` is the live state `{ r: [level1, level2], c: columnKey, v: measureKey, agg, pct, heat }`; `draw()` redraws (methods are auto-bound). Change `st` and call `draw()` to arrange the table from code. The component instance is also on `el.dlComponent`. The state is not persisted (no localStorage), a reload returns to the props.

### Server

None. No URL, no POST, no CSRF. The data is whatever the screen put in `rows`.

## Rules and pitfalls

- Give `fields` and `measures` (non-empty) and make sure every key exists in each row. With empty `fields`, `rows-by` and the Row selector are empty and the draw fails.
- Keep `agg` to the five valid names, `formats` to `'money'`.
- Pivot the data after authorisation: all rows, and every key of the first row (the source-rows modal shows the first 7), are delivered to the browser. Do not include columns the user must not see; do not rely on hiding.
- Size: rows are repeated in one HTML attribute and re-scanned on every redraw (each cell filters the whole array). Keep it to a few thousand rows; for more, aggregate on the server (for example by customer x month) and send the result.
- Prefix sortable dimension values (`01 Jan`) because order comes from the sort of the text, not from the dates.
- Choose measures that can be added: the `sum` of a unit price is not a price (use `avg`, or a ready amount column). `% total` of `avg`/`min`/`max` is not meaningful.
- The pivot is a standalone object of the Grid Report family; do not nest it inside an `x-dl::grid`.
- Lint: the object has no rule of its own; L001/L002/L003 apply to the screen that contains it (no raw HTML tags, no inline script, no hex colours).
- Tests: no automated test targets the pivot directly; the catalog page `p5-grid-pivot` (in `Catalog::APPROVED`, locked) is the visual reference and the menu test in `GridFormFlowTest` only checks the menu label `Grid Pivot`.

### Notes on inconsistencies found while writing this entry (not fixed)

- The header comment of `Pivot.php` says `format: number|money`; the script only tests for `money`, every other value (including `number`) falls through to the plain number format.
- The comment says "maks 2 tingkat, tingkat 1 dapat subtotal"; the Subtotal line is produced whenever a second level is chosen (the level-1 group gets the subtotal).
- Sub-rows are sorted with `Array.sort()` (code-unit order) while level 1 and the columns use the numeric `id` locale sort, so the same labels can sort differently on the two levels.
- A cell whose value is exactly 0 and a cell with no data both show `–`; sums that cancel out (for example +10 and -10) look empty but are still clickable.
- With `% total` on, the export still writes raw numbers, so the file does not match the screen.
- Hard-coded English labels (`Min`, `% total`, `Heatmap`, `Total`, `Grand Total`, `Subtotal`) are not in the dictionary, unlike the other toolbar words.
- The source-rows modal is built in script with inline `style` values for width and scrolling, and has no Esc / focus handling.
- `agg`, `formats` and the keys are not validated on the PHP side; a wrong value fails only in the browser.
- The demo and the catalog page still use Indonesian labels, while the rule is that screens are written in English and translated by the dictionary.

## Example

From the catalog demo (`site/resources/views/demos/grid-pivot.blade.php`), with English labels:

```blade
<x-dl::pivot title="Sales" :rows="$sales"
    :fields="['customer' => 'Customer', 'category' => 'Category', 'month' => 'Month', 'rep' => 'Sales person']"
    :measures="['amount' => 'Amount (IDR)', 'qty' => 'Qty (pcs)']"
    :formats="['amount' => 'money']"
    :rows-by="['customer', 'category']" col-by="month" value="amount" agg="sum" />
```

`$sales` is a flat list built by the controller, for example:

```php
$sales = [
    ['customer' => 'PT Example Snacks', 'category' => 'Box',   'month' => '01 Jan', 'rep' => 'Andi', 'qty' => 3000, 'amount' => 360000],
    ['customer' => 'PT Example Snacks', 'category' => 'Label', 'month' => '02 Feb', 'rep' => 'Rina', 'qty' => 5000, 'amount' => 410000],
    // ...
];
```

## Related

- `x-dl::grid` (searchable list; `mode="report"` is the Grid Report family)
- `x-dl::drilldown` (summary grid -> detail grid)
- `x-dl::table` (small static table)
- `x-dl::report` (printable report)
- `x-dl::statement` (financial statement rows)
- `x-dl::chart` (charts)
- `x-dl::button` (the Export button)
