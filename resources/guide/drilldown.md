## Overview

`<x-dl::drilldown>` is the Grid Summary -> Details (drill-down) object of the Grid Report family. It is a thin wrapper around **two ordinary `x-dl::grid` objects**: a summary grid (one line per supplier, customer, month, ...) and a detail grid (the lines behind them). The user double-clicks a summary row; the summary is replaced by the detail grid showing only the lines that belong to that row, with a breadcrumb to go back. Both grids keep every feature of `x-dl::grid` (search, sort, filter, export, ...). The wrapper adds no data and no server call: it only filters the detail rows in the browser (`drill.js`, class `Drill`).

Use it when a report has exactly one level of drill-down and both data sets are small enough to be in the page.

Pick another object when:

- the user should see both levels at once, or an expandable child table inside the same row: the `expand` / `detail` props of `x-dl::grid`, or the Nested Grid (`url` + `ref` columns).
- the user wants to rearrange the dimensions freely: `x-dl::pivot` (its cells open the source rows).
- the detail is a record the user edits: `x-dl::grid` with `open-url` to a form (Grid -> Form flow).
- the data is large or must be paged by the server: this object loads all detail rows into the page.

### Markup produced

```html
<div class="dl-drill" data-dl="x-dl::drilldown" data-dl-class="Drilldown ← DevlibComponent" data-dl-src="—"
     data-dl-drill data-link="supplier" data-label="name" data-title="Summary by supplier">
  <nav class="dl-drill__crumb" data-dd-crumb></nav>          <!-- filled by script -->
  <div data-dd-summary> ...summary grid... </div>
  <div data-dd-detail hidden> ...detail grid... </div>
</div>
```

Extra HTML attributes (`id`, `class`, `style`, `data-*`) and `can="module.action"` go on the root `div`. The object has no `source()` override, so `data-dl-src` is the dash `—`.

## Props

Constructor of `Devlib\Components\Drilldown` (3 props) and two slots.

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `link` | `string` | `''` | The column of the **detail rows** that holds the key of the summary row. Detail rows shown after a drill = those where `String(detailRow[link]) === String(summaryRow[rowKey])`, where `rowKey` is the `row-key` of the summary grid (default `id`). Required: with `''` no detail row matches and every drill shows 0 rows. |
| `label-key` | `string` | `'name'` | The column of the **summary row** printed in bold in the breadcrumb (`Summary > Acme Paper`). When the row has no such value the key of the row is printed. Not translated. |
| `title` | `string` | `'Summary'` | Text of the root of the breadcrumb: the title shown in the summary state and the label of the "back" button in the detail state. Translated through the dictionary. |

### Slots

| Slot | Content |
|---|---|
| `summary` (named: `<x-slot:summary>`) | The summary grid. |
| default slot | The detail grid. |

The script takes the **first** `[data-dl-grid]` inside the object as the summary grid and the **second** as the detail grid, so the summary grid must come first in the DOM (it does, because the `summary` slot is printed before the default slot) and there must be exactly these two grids. With fewer than two grids the script stops silently and nothing is wired. Give each grid its own `id`.

## Behavior

1. **Start (home).** The summary is shown, the detail is hidden (`hidden` attribute), and the breadcrumb shows `[layers icon] <title>` plus the hint "Double-click a row to see its details" (`dr_hint`).
2. **Drill.** A double-click on a summary row (or any row action the summary grid draws, which fires the same `dl:grid-open`) hands control to the drill-down: the event is cancelled and stopped (`preventDefault` + `stopPropagation`), so the summary grid does **not** navigate to its `open-url` and shows no preview toast, and listeners above the object do not hear the summary's `dl:grid-open`. The summary row is found by `String(row[rowKey]) === String(detail.id)`.
3. The detail grid gets only the matching rows through `dlGrid.setRows(list)` (the detail grid returns to page 1 and clears its selection; its search text, sort and filters stay as the user left them). The summary box is hidden, the detail box shown.
4. The breadcrumb becomes: `[<- title]` (a `dl-btn dl-btn--link dl-btn--sm` button, `data-dd-back`) `>` **row label** `N detail rows` (`dr_n_rows`).
5. **Back.** Clicking the back button shows the summary again (the summary grid keeps its own search/sort/page). The detail grid keeps showing the last filtered list while hidden; it is replaced at the next drill.

The full detail set is captured once at start (`dlGrid.rows` of the detail grid at init); changing the detail grid's rows from outside afterwards is not seen by the filter.

### Which rows can be drilled

Any row of the summary grid with a key. In a grid with `mode="report"` (the recommended summary grid) the grid forces `actions` to `none`, so there is no Open button and the **double-click is the only gesture**; a summary grid in another mode with `actions="worklist"` also drills from the Open icon. The Active/Inactive `toggle` action of a master grid that has an `active-key` is handled inside the grid and does not drill.

### Language

The wrapper translates `title` (attribute prop) and the two run-time texts `dr_hint` and `dr_n_rows`. The grids translate their own texts. The dictionary group is `grid`.

### Access (`can`)

`can="module.action"` on the tag: without the ability the whole object (both grids) is not rendered. The data of the detail grid is nevertheless in the page for whoever may see the object, so authorise the rows on the server first.

## Data and events

- The wrapper keeps no data of its own: `data-link`, `data-label` and `data-title` are the only attributes it writes.
- Events: it listens to `dl:grid-open` of the **summary** grid and consumes it. The detail grid dispatches its normal `dl:grid-open`, `dl:grid-bulk`, ... which bubble out of the object as usual. The wrapper dispatches no event of its own.
- JS access: the instance is `el.dlComponent` (class `Drill`) with the method `home()` (back to the summary, bound). The two grids are reachable as `el.querySelectorAll('[data-dl-grid]')[i].dlGrid` (`rows`, `cfg`, `redraw()`, `setRows(list)`).
- Server: none. No URL, no POST. If a row of the detail grid has an `open-url`, that works as in any grid (it opens the form; with `restore` the grid comes back with its state).

```js
// e.g. react when the user drills (summary row opened); the wrapper cancels the event first, so listen on the capture phase
drill.addEventListener('dl:grid-open', e => track(e.detail.id), true);
```

## Rules and pitfalls

- The summary row key and the detail `link` column must hold the **same kind of value** (supplier code, customer id). The comparison is by string; `7` and `"7"` match, `"A-01"` and `"a-01"` do not. Give the summary grid a real `row-key` (`id` by default) whose values are those keys.
- Both grids must be mounted by `Grid` before `Drill` starts (`dlGrid` must exist on the detail grid); this is the case with the normal DevLib script, but a custom load order that mounts `Drill` first would fail in `init`.
- All detail rows are in the page. Fine for hundreds or a few thousand lines; use a server-driven screen for more. Authorise (filter) the rows on the server: hidden rows are still delivered.
- One level only. For a third level use the detail grid's own `expand` / nested rows, or a form (`open-url`).
- Do not nest a drilldown inside a grid, and do not place more than two grids inside it (the third would be ignored but still be rendered).
- The grids stay plain `x-dl::grid`: lint rules for grids (for example `G101` kind) apply to them as usual; `mode="report"` grids need no `kind`. Rules L001-L003 (no raw HTML, inline script, hex colours) apply to the screen. The object itself has no lint rule.
- The page `p5-grid-drilldown` is in `Catalog::APPROVED` (locked): do not change its look or behavior without Jo's explicit approval.
- No automated test targets this object; `GridFormFlowTest` only checks the menu label `Grid Summary → Details`.

### Notes on inconsistencies found while writing this entry (not fixed)

- The header comment says "Klik 2x / Buka baris ringkasan" (double-click / Open); a `mode="report"` summary grid, which is what the catalog demo uses, has no Open button because the grid forces `actions="none"` in report mode. The demo's `actions="worklist"`, `:active-key="null"` and `:new-record="false"` have no effect in that mode.
- The `dl-btn--link` back button and the breadcrumb markup are written by the script with inline strings; the root `data-dl-src` shows a dash because the class has no `source()` override.
- The demo still uses Indonesian labels (`Ringkasan per Supplier`, `Cari supplier...`) while screens are meant to be English and translated by the dictionary.
- After going back, the detail grid still holds the last filtered rows (hidden); only the next drill replaces them.

## Example

Adapted from `site/resources/views/demos/grid-drilldown.blade.php` (English, fictional suppliers):

```blade
<x-dl::drilldown link="supplier" label-key="name" title="Summary by supplier">
    <x-slot:summary>
        <x-dl::grid id="po-by-supplier" mode="report" :rows="$supplierSummary" search="Search supplier..."
            :columns="[
                ['key' => 'name',  'label' => 'Supplier',  'type' => 'identity'],
                ['key' => 'po',    'label' => 'PO count',  'type' => 'number', 'sum' => true],
                ['key' => 'open',  'label' => 'Open POs',  'type' => 'number', 'sum' => true],
                ['key' => 'total', 'label' => 'PO value',  'type' => 'money',  'sum' => true],
            ]" />
    </x-slot:summary>

    <x-dl::grid id="po-lines" mode="report" :rows="$purchaseOrders" search="Search PO no..."
        :columns="[
            ['key' => 'po_no',    'label' => 'PO no.'],
            ['key' => 'po_date',  'label' => 'Date', 'type' => 'date'],
            ['key' => 'supplier', 'label' => 'Supplier'],
            ['key' => 'total',    'label' => 'Total', 'type' => 'money', 'sum' => true],
            ['key' => 'status',   'label' => 'Status', 'type' => 'badge', 'tones' => ['Draft' => 'neutral', 'Confirmed' => 'success', 'Cancelled' => 'danger', 'Closed' => 'info']],
        ]" />
</x-dl::drilldown>
```

Server side, the summary rows need `id` = the key that the detail rows carry in `supplier`:

```php
$supplierSummary = [['id' => 'SUP-01', 'name' => 'Example Paper Co', 'po' => 8, 'open' => 2, 'total' => 84500000]];
$purchaseOrders  = [['id' => 1, 'po_no' => 'PO-0001', 'po_date' => '2026-10-02', 'supplier' => 'SUP-01', 'total' => 9250000, 'status' => 'Confirmed']];
```

(In the catalog demo `id` and `supplier` both hold the supplier name; a code works the same.)

## Related

- `x-dl::grid` (the two grids inside; `mode="report"`)
- `x-dl::pivot` (cross-table whose cells list the source rows)
- `x-dl::table` (small static table)
- `x-dl::report` (printable report)
- `x-dl::form` (the page a detail row can open with `open-url`)
