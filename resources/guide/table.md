## Overview

`<x-dl::table>` is the small read-only table of DevLib: a plain HTML `<table>` inside a bordered wrapper, for a handful of rows that sit inside a form, a card, a modal or a documentation page. The screen hands over `columns` and `rows` as PHP arrays; the object prints the header, the body (or one "empty" row), and an optional totals row. It is pure server-side Blade: there is no JavaScript file, no search, no sort, no paging and no selection.

Pick another object when the list needs behaviour:

- `x-dl::grid` for any list the user searches, sorts, filters, pages, selects, exports or opens a record from (master / transactional / report grids).
- `x-dl::sheet` / `x-dl::lookup-grid` when the user edits the cells.
- `x-dl::lines` for the item rows of a document form (inputs, formulas, totals, footer).
- `x-dl::pivot` for a cross-table that the user re-arranges; `x-dl::report` or `x-dl::statement` for printed documents.
- `x-dl::strip-kv` or a key/value block when the data is a single record rather than a list.

Raw `<table>` in a screen is forbidden (lint rule L001, `ViewLinter`); use this object for plain tables.

### Markup produced

```html
<div class="dl-table-wrap" data-dl="x-dl::table" data-dl-class="Table ← DevlibComponent" data-dl-src="row table">
  <table class="dl-table is-striped">
    <thead><tr><th class="">Item</th><th class="num">Qty</th></tr></thead>
    <tbody>
      <tr><td class="">Art Paper</td><td class="num">5.000</td></tr>
      <!-- no rows: --> <tr><td colspan="N" class="dl-table__empty">No data yet.</td></tr>
    </tbody>
    <tfoot><tr><td class="">Total</td><td class="num">...</td></tr></tfoot>   <!-- only when totals is not empty -->
  </table>
</div>
```

Extra HTML attributes (`id`, `class`, `style`, `data-*`) and `can="module.action"` go on the wrapper `div` (class is merged with `dl-table-wrap`). Without the ability, nothing is rendered. The wrapper also carries `data-dl`, `data-dl-class` and `data-dl-src` (developer catalog / Technical Guide link). Default source label: `baris tabel` (the literal text returned by `source()`).

## Props

Constructor of `Devlib\Components\Table` (7 props). Use `:prop="expr"` for arrays and booleans.

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `columns` | `array` | `[]` | Ordered map `row key => definition`. A definition is either a string (the header label) or an array whose element `[0]` is the label plus named options (see "Column definition"). The order of `columns` is the order of the cells; keys not listed in `columns` are ignored. |
| `rows` | `array` | `[]` | List of associative arrays. A cell is `$row[$key]`; a missing key prints an empty cell (a missing key with `money` or `num` prints the formatted zero). |
| `empty` | `string` | `'No data yet.'` | Text of the single row shown when `rows` is empty (`<td colspan="count(columns)" class="dl-table__empty">`). Translated through the dictionary. |
| `striped` | `bool` | `true` | Adds `is-striped` to the `<table>` (even rows get the alternate background). `false` removes the class. |
| `totals` | `array` | `[]` | Map `column key => value` for the footer row. Empty array = no `<tfoot>`. Only keys present in `totals` print a value; every other column in the footer is an empty cell. |
| `source` | `?string` | `null` | Free text naming where the data comes from, shown by the developer catalog (`data-dl-src`); default `baris tabel`. No visual or runtime effect. |
| `stack` | `bool` | `false` | Opt-in for the phone view: the wrapper gets class `dl-table--stack` and every body cell gets `data-label` (the column label). Only inside `body.dl-on-phone` / `.dl-phone` each row then becomes a card (label on the left, value on the right, no sideways scroll, header hidden); on desktop the table looks exactly as before. |

### Column definition

```php
:columns="[
    'item'  => 'Item',                                   // string: label only, left aligned, printed as is
    'uom'   => ['UoM'],                                  // array form without options
    'qty'   => ['Qty', 'num' => true],                   // right aligned, Locale::number (0 decimals)
    'price' => ['Price', 'num' => true, 'money' => true],// right aligned, "IDR 1.850"
    'note'  => ['Note', 'raw' => true],                  // value printed as given (still HTML-escaped)
]"
```

| Option | Effect on header and body cells | Effect on the totals row |
|---|---|---|
| (none, string) | Plain text. | Value printed as given. |
| `'num' => true` | Adds class `num` (right aligned, tabular figures) to `<th>` and `<td>`; value goes through `Devlib\Locale::number($value)` (language aware, **0 decimals**: `1.5` shows as `2`). | Class `num` only; the value is printed as given (NOT formatted). |
| `'money' => true` | Value goes through `Devlib\Locale::money($value)`: `IDR ` + number with 0 decimals. Does not add alignment by itself; combine with `num`. | Formatted with `Locale::money`. |
| `'raw' => true` | Value printed as given, no number formatting (use it when the caller already formatted it: 2 decimals, parentheses for deductions). Combined with `num` it stays right aligned. `raw` is checked before `money` and `num`, so it wins. | Printed as given. |

Order of evaluation for a body cell: link cell first, then `raw`, then `money`, then `num`, then plain value.

### Link cell

A cell value that is an array with an `href` key prints an anchor: `['href' => '/po/12', 'text' => 'PO-0012']` gives `<a href="/po/12">PO-0012</a>`. `text` falls back to the `href`. Both are HTML-escaped. The link ignores `raw`/`money`/`num`. See the security note below: the `href` scheme is not checked.

## Behavior

- Static rendering only; the table never changes after load. There is no sorting, no hover action and no row click. Rows get a hover background (`tr:hover td`) from CSS.
- Empty: with `rows = []` the body shows the `empty` text spanning all columns. The footer is still printed when `totals` is not empty.
- Alignment and density follow the page theme: density (`--dl-density`) and font scale (`--dl-font-scale`) tokens scale the padding and the font; the wrapper scrolls horizontally (`overflow:auto`) when the table is wider than its container.
- Styling classes (CSS `devlib.css`): `.dl-table-wrap`, `.dl-table`, `.dl-table.is-striped`, `.dl-table .num`, `.dl-table tfoot td` (bold, thicker top border), `.dl-table__empty`. Header text is upper case, small, muted.
- Accessibility as implemented: a native `<table>` with `<thead>`/`<tbody>`/`<tfoot>` and `<th>` header cells; there is no `scope`, `caption` or `aria-*` attribute. Links are normal anchors (keyboard focusable).

### Language

The object translates only text the screen writes in English: the header labels (`columns`: string definitions and the `[0]` element of array definitions), and `empty`. Cell values, `totals` values and link texts are data and are never translated (wrap data in `raw()` in props that are translated). Numbers and money follow the application language (`1.234,56` for `id`, `1,234.56` for `en`) through `Devlib\Locale`. The dictionary group of this object is `grid`.

### Access (`can`)

`can="module.action"` on the tag: without the ability the object renders nothing (checked when the view is resolved, `DevlibComponent::resolveView()`).

## Data and events

- Input only: no `name`, so nothing is posted by a form that contains the table. It is not a form control.
- No `dl:*` events, no `el.dl*` JS object, no `data-config` attribute and no URL contract. Anything that must react to a click (open a record, filter) belongs in `x-dl::grid`.
- Rows are plain data: build them on the server from the already authorised query. The whole content is in the HTML; do not put hidden/secret columns in `rows` thinking they will not be shown (only keys listed in `columns` are printed, but keep the array minimal anyway).

## Rules and pitfalls

- Use it for small, read-only tables. For more than a few dozen rows, or when users search/sort, use `x-dl::grid` (client-side paging) instead.
- Do not write raw `<table>` in a screen (lint L001). Other lint rules that apply to the page containing it: L002 (no inline `<script>`), L003 (no hex colours; use theme tokens). The object itself has no lint rule of its own (`lint()` returns nothing).
- `num` formats with **0 decimals**. For quantities or prices with decimals pre-format on the server and use `'raw' => true` (with `num` to keep right alignment).
- `totals` is not formatted for `num` columns (only `money` columns are). Pass a pre-formatted string in `totals` for such columns, or use `money`.
- A `columns` entry written as `['label' => 'Qty']` is wrong: the view reads element `[0]`. Write `['Qty', 'num' => true]`.
- A cell value that is an array without `href` (for example nested data) breaks the render (array to string conversion). Flatten it first.
- Link cells: build `href` on the server from trusted data. The value is only HTML-escaped; a `javascript:` URL coming from user data would still be an active link.
- `totals` labels such as `'item' => 'Total'` are not translated; write them in the application language through your own translation helper if needed.
- Do not put form inputs inside the cells; use `x-dl::lines` (document detail) or `x-dl::sheet` (editable cells).

### Notes on inconsistencies found while writing this entry (not fixed)

- The header comment of `Table.php` says the `raw` option "bersama num" (together with num); in code `raw` also works without `num` (it only prevents number formatting).
- `DevlibComponent::translateTexts()` also translates column definitions written as `['label' => ...]`, but the view only reads `$c[0]`, so that shape is accepted by the translator and then fails in the view (undefined array key).
- The totals row formats only `money` columns; the same column with `num` is formatted in the body but not in the footer, so a `num` total looks different from the cells above it.
- A missing key in a `money` or `num` column prints the formatted zero (`IDR 0`, `0`), while a missing key in a plain column prints an empty cell.
- `source()` returns an Indonesian default (`baris tabel`) although the catalog texts are English; it is only a developer label.

## Example

From the catalog demo (`site/resources/views/demos/table.blade.php`), with fictional data:

```blade
<div class="dl-demo" style="max-width:820px">
    <x-dl::table
        :columns="['item' => 'Item', 'uom' => 'UoM', 'qty' => ['Qty', 'num' => true], 'price' => ['Price', 'num' => true, 'money' => true], 'total' => ['Total', 'num' => true, 'money' => true]]"
        :rows="[
            ['item' => 'Duplex board 250 gsm', 'uom' => 'SHT', 'qty' => 5000, 'price' => 1850, 'total' => 9250000],
            ['item' => 'Cyan ink', 'uom' => 'KG', 'qty' => 12, 'price' => 185000, 'total' => 2220000],
            ['item' => 'Lamination glue', 'uom' => 'KG', 'qty' => 20, 'price' => 51500, 'total' => 1030000],
        ]"
        :totals="['item' => 'Total', 'total' => 12500000]"
        empty="No items yet." />
</div>
```

Link cell and pre-formatted value:

```blade
<x-dl::table
    :columns="['no' => 'Document', 'amount' => ['Amount', 'num' => true, 'raw' => true]]"
    :rows="[['no' => ['href' => '/po/12', 'text' => 'PO-0012'], 'amount' => '1.250.000,50']]" />
```

## Related

- `x-dl::grid` (searchable, sortable, paged list; use it instead when the user works with the rows)
- `x-dl::sheet` (editable cells)
- `x-dl::lookup-grid` (small editable helper lists)
- `x-dl::lines` (item rows of a document form)
- `x-dl::pivot` (cross-table of flat data)
- `x-dl::report` (printable report)
- `x-dl::statement` (financial statement rows)
- `x-dl::issue-modal` (renders its problem list with `x-dl::table`)
- `x-dl::empty-state` (larger empty message; `empty` prop is enough for a table)
- `x-dl::card` (usual container)
