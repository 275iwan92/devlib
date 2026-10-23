## Overview

`<x-dl::grid>` is the single list/table object of DevLib. It renders a searchable, sortable, filterable, paginated table of rows that the screen hands over as a plain PHP array. The screen only *declares* (columns, rows, a few options); every behaviour (search, sort, filters, stats/tabs bar, column chooser, grouping, density, selection and bulk actions, export, card view, nested detail rows, quick add, restore after a form) lives in `src/Components/Grid.php`, `resources/views/components/grid.blade.php` and `resources/assets/js/components/grid.js`. There is no database, model or HTTP source behind it: **all rows are serialised into the page (`data-rows`) and processed in the browser**. Server-side authority only appears where a URL is passed (`toggle-url`, `open-url`, `new-url`).

### Kinds and modes

A grid is chosen by two props, `kind` and `mode`:

| Grid | Declaration | Intent | Row actions | Look |
|---|---|---|---|---|
| Master (Grid Pre-Entry, master branch) | `kind="master"` | Small reference data (items, suppliers, employees). Door to a Master Form. | Edit + Active/Inactive toggle | First column is a coloured box (`identity` + `box`), density always `normal` |
| Transactional (Grid Pre-Entry, transactional branch) | `kind="transactional"` | Documents (PO, CO, vouchers), can be thousands of rows. Door to a document form. | Open only (Confirm/Approve/Cancel/Delete live in the form, never in the grid) | PLAIN for speed: bold-text first column (no box, no avatar), zebra rows, only the Status badge is coloured, density always `compact` |
| Report (Grid for Reporting) | `mode="report"` | Read-only analysis, no form. Also the base of the Nested Grid. | none | Compact, sticky header, zebra rows, no checkboxes/Actions/New Record, Filter pop-up with per-column criteria |
| Legacy entry | neither `kind` nor `mode` | Pre-`kind` behaviour (columns and `actions` taken literally) | by `actions` | Lint G101 (info) |

Other combinations:

- `actions="worklist"` or `actions="none"` without `kind` is the "other list" case (groups, modules, security users): Open only, or nothing.
- Anything other than `mode="report"` is treated as `entry` (the default). There is no other `mode` value with behaviour.
- `kind` values other than `master` / `transactional` are silently ignored (`applyKind()` returns early).

### Where it sits in the screen flow

```
Grid Report (no form)                     Grid Pre-Entry (grid -> form)
  mode="report"                             kind="transactional"  -> document form (Master-Detail, Single, Approval)
  Nested Grid (url + ref columns)           kind="master"         -> Master Form
                                            Lookup (x-dl::lookup-grid, fills in cells, no form)
```

Grid and form are two pages with their own addresses. The grid opens the form with `open-url` (double-click, Open/Edit icon) or `new-url` (+ New Record), the form returns with its own Back button to the grid address it was given (`from`). With `restore` the grid remembers search, page and stat box across that trip and highlights the saved row via `?hl=<id>`. The full screen order is in `skills/devlib/grid-form-flow.md`; document states in `skills/devlib/transaction-lifecycle.md`.

### What it is NOT

- Not a server-side data table: no pagination/sort/filter requests, no `source` URL (the `source` prop is only a label for the Technical Guide link, see below). For very large data sets the server (e.g. Livewire) version has to reuse the same markup and classes; this component does not do it.
- Not an editable spreadsheet: use `x-dl::sheet` / `x-dl::lookup-grid` for in-cell editing. The only inline edits here are the `toggle` column type and the quick-add row, and both only emit events.
- Not the form: it never contains Save/Confirm/Cancel. A master or transactional grid hands over to `x-dl::form`.
- Not a pivot, a printed report or a Kanban: see `x-dl::pivot`, `x-dl::report`, `x-dl::kanban`.
- Not a place for a custom `<table>`: the lint rule L001 (`ViewLinter`) forbids raw HTML where an object exists.

## Props

Constructor of `Devlib\Components\Grid` (29 props). Blade names are kebab-case; arrays and booleans use the `:` binding (`:rows="$rows"`, `restore` alone = `true`).

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `id` | string | `'grid'` | Grid identity. Used as the `localStorage` key (`dl-grid-<id>`), the `sessionStorage` return key (`dl-grid-ret-<id>`), the export file name (`<id>.csv` / `<id>.xlsx`), the radio-group name of the Group chooser and the lint location (`grid#<id>`). **Must be unique per grid and stable.** |
| `columns` | array | `[]` | Column definitions, see [columns](#columns). |
| `rows` | array | `[]` | List of associative arrays (one per row). Serialised to `data-rows`. |
| `row-key` | string | `'id'` | Row field that identifies a row (selection, open/toggle URLs, highlight, events). |
| `stats` | array | `[]` | Count boxes that act as filters, see [stats and tabs](#stats-and-tabs). |
| `tabs` | array | `[]` | Second filter group, same shape as `stats`. |
| `filters` | array | `[]` | `[rowField => 'Label']` select filters, see [filters](#filters). |
| `groups` | array | `[]` | `[rowField => 'Label']` fields offered in the Group chooser (report mode only). |
| `bulk` | array | `[]` | Bulk actions, see [bulk](#bulk). Cleared in report mode. |
| `expand` | array | `[]` | List of column keys shown in a small key/value box when a row is expanded. |
| `actions` | string | `'master'` | `master` (Edit + Active/Inactive), `worklist` (Open only) or `none`. Any other string behaves like `master`. Overridden by `kind`; forced to `none` in report mode. |
| `active-key` | ?string | `'active'` | Boolean row field that holds the active state (master toggle, `is-inactive` row style, quick-add default). `null` for grids without active/inactive. |
| `new-record` | ?string | `'+ New Record'` | Label of the New Record button. `null` (or `false`, which PHP coerces to `''`) hides it. Forced to `null` in report mode. |
| `per-page` | ?int | `null` | Rows per page. `null` becomes `25` (entry) or `100` (report). |
| `detail` | array | `[]` | Child table shown when a row is expanded, see [detail](#detail). |
| `cards` | array | `[]` | Enables the Table/Cards switch, see [cards](#cards). |
| `phone-cards` | array | `[]` | Phone view only (opt-in). Same shape as `cards` (`title`, `sub`, `badge`, `tones`, `meta`, `image`), but without the Table/Cards switch: in the phone context (page body has `dl-on-phone`, see `Devlib\Device::bodyClass`, or the grid is inside `x-dl::phone-shell`) the grid opens as a list of cards (name + code, status badge, the `meta` values), a tap on a card opens the row like a double click (`open-url`, event `dl:grid-open`), the toolbar (search, + New, Filters, Columns, Export) is hidden in the page and its actions appear in the bar of state B of `x-dl::phone-shell` when the user taps the floating button (Search, New, Filters, Columns; Export goes to "More actions"); **Filters / Columns / Export** open as sheets from the bottom, and the density and view controls are hidden. With `row-tone` the card gets a coloured left edge and a soft background. On a desktop screen nothing changes (the key is not even written to the page config when empty). |
| `quick-add` | array | `[]` | Column keys for the quick-add row on top of the table. Cleared in report mode. |
| `group-by` | ?string | `null` | Initial grouping field (works even without `groups` and in entry mode, where there is no Group button). |
| `search` | string | `'Search...'` | Placeholder of the search box; say which columns are searched. Translated. |
| `source` | ?string | `null` | Descriptive data-source label written to `data-dl-src` (default text `baris data grid`). Documentation only: **not a URL, nothing is fetched**. |
| `open-url` | ?string | `null` | Address opened by double-click / Open / Edit. `{id}` = URL-encoded row key, `{field}` = that row field (not encoded). |
| `new-url` | ?string | `null` | Address opened by New Record (a literal URL, no placeholders). |
| `new-modal` | ?string | `null` | `id` of an `x-dl::modal` opened by New Record (wizard). Wins over `new-url`. |
| `toggle-url` | ?string | `null` | POST address for Active/Inactive (`{id}` placeholder). Without it the toggle is preview-only. |
| `mode` | string | `'entry'` | `entry` or `report`. |
| `row-tone` | ?string | `null` | Name of the row field that carries a row colour, see [row tone](#row-tone). |
| `kind` | ?string | `null` | `master` or `transactional`, see Overview. |
| `restore` | bool | `false` | Opt-in grid -> form -> grid memory (search, page, stat box, `?hl` highlight). |

Common attributes: `can="module.action"` (any DevLib object, see Behavior), and any extra HTML attribute is merged onto the root `div.dl-grid`.

### Columns

`columns` is a list of arrays with **named** keys `key` and `label` (the header comment of `Grid.php` writes them positionally, but the code reads `$c['key']` / `$c['label']` and `c.key` / `c.label`). `config()` fills in `type => 'text'`, `visible => true`, `sortable => true` for keys not given.

Common column options:

| Option | Meaning |
|---|---|
| `key` | Row field shown. Sorting, search, export and filters use it. |
| `label` | Header text (English, translated by `Lang`). |
| `type` | One of the types below. Default `text`. |
| `visible` | `false` = hidden initially (still in the Columns chooser and in the row data). |
| `sortable` | `false` = header not clickable. |
| `width` | CSS width on the `th` (e.g. `'120px'`). |
| `src` | Source label for the Technical Guide: adds `data-dl` / `data-dl-class="kolom"` / `data-dl-src` to the header cell. |
| `sum` | `true` on a numeric column adds a total in the footer (all filtered rows) and a subtotal in each group header (rows of the current page in that group). Works on any column whose values are numeric (`+value`). |

Types (all handled in `cell()` of `grid.js`):

| `type` | Cell | Extra options |
|---|---|---|
| `text` (default) | Plain text. Also used for `time`/`datetime` (formatted through `fmtCell`). | `url`, `ref`, `list` (see below) |
| `identity` | Name in bold with a small code line; coloured initials square by default. | `code` (row field of the small line, also searched and exported as `name (code)`), `avatar => false` (no square), `box => true` (no initials; name + code inside one coloured rectangle), `hue` (row field with an angle 0-359 to colour the box/square per group; otherwise derived from the name), `lead => true` (adds `dl-avatar-id--lead`) |
| `doc` | **Legacy.** Bold document number + smaller `party` line, no coloured square. With `kind="transactional"` it is rewritten to `identity` + `avatar => false` + `code => party`. | `party` (row field of the second line) |
| `integer` | Right-aligned whole number. | |
| `number` | Right-aligned number. | `decimals => n` fixed digits (without it: 2 digits if the value contains `.`, otherwise 0) |
| `money` | Right-aligned `CUR 1.234`. | `currency` (default `IDR`), `decimals` (default 0) |
| `date` | Date as `dd-Mmm-yyyy` (`fmt.gridDate`). | `toneKey` = row field with a tone; the date then appears as a coloured pill |
| `time`, `datetime` | Formatted with `DlTime` / `DlDateTime` (rendered through the default text cell). | |
| `badge` | Pill. Tone from `tones[value]`, or from the row field `toneKey`; fallback `neutral`. | `tones => [value => tone]`, `toneKey` (use for varying text such as "Late 3 days") |
| `link` | Read-only value from another entity (e.g. "Valued transactions"), right-aligned, with an arrow icon. | `format => money|number` (default `number`, plus `currency`/`decimals`), `url` (only `{id}` is replaced; without `url` the link is `#` and click emits `dl:grid-link`) |
| `image` | Thumbnail button that opens a lightbox. | `fallback` = row field whose initials are shown when there is no image |
| `progress` | Bar 0-100, tone by value (>=100 success, >=60 accent, >=30 warn, else danger). | |
| `steps` | Dots for a stage list; the value is the current stage. | `steps => [stage..]` |
| `toggle` | Switch bound to the row field; change emits `dl:grid-cell`. | |
| `rating` | Five stars (value rounded). | |
| `spark` | Mini line chart from an array of numbers (needs 2+ values). | |
| `button` | Small button in the cell; click emits `dl:grid-action`. | `text` (default = `label`), `action` (default = `key`) |

Link options on text columns:

- `url` on a `text` (or list) column turns the cell into an `<a>`. `{id}` is the URL-encoded row key, `{field}` is the URL-encoded value of another field of that row (all occurrences). An empty cell shows `+` so it stays clickable.
- `ref => true` styles it as a reference link (accent colour, underline, arrow) pointing to another grid. This is the basis of the **Nested Grid** (grid to grid, no form, unlimited depth, see `skills/devlib/grid.md`).
- `list => true` makes the cell a multi-line list (`pre` white-space, never clamped).

The first column matters for `kind`: with `master`, an `identity` or `doc` first column gets `box => true`; with `transactional` it gets `avatar => false` and loses `box` / `hue`. A first column of any other type is not touched.

### Stats and tabs

Both props share the shape `[['label' => 'Active', 'where' => ['active' => true], 'tone' => 'success', 'default' => true], ...]`.

- `where`: `[field => value]` or `[field => [value, value..]]` (any of). Several fields are AND-ed. Comparison is by `String()` equality. **No `where` = "All"** (the total).
- `tone`: colour of the box/underline: `accent` (default), `success`, `warn`, `danger`, `neutral`, `tangerine`, `yellow`, `violet`, `orange`, `pink`, `lime`. Unknown names fall back to the accent colour. Tabs without a `tone` get a cycling colour (`orange, pink, lime, violet, success, warn`), except an All tab, which is `accent`.
- `default => true` (stats only): initial selected box when the grid opens (e.g. "To Do" on a worklist). All can still be chosen.
- Each button shows its row count. A stat count honours the other filters (tab, filters, search); a tab count ignores the stat selection too.
- A tab "All" (no `where`) is hidden when the stats already have an All.

### Filters

`filters` is `[rowField => 'Label']`. Each entry becomes a `<select>` whose options are the distinct values of that field over all rows (sorted as strings, plus "All"). The rowField does not have to be a column. Matching is exact string equality. Entry mode: a Filters pop-over with a counter badge; report mode: the same selects inside the Filter modal, together with per-column criteria.

### Groups

`groups` is `[rowField => 'Label']`: the options of the Group chooser (radio list plus "No group"). The chooser button exists only in **report** mode (`$groups && ! isEntry()`), and `group-by` is the initial group.

### Bulk

`bulk` is a list of `['key' => 'export', 'label' => 'Mark', 'icon' => 'check', 'variant' => 'secondary', 'confirm' => 'Mark these rows?', 'quiet' => true]`. `key` and `label` are required; `variant` defaults to `secondary`; `confirm` becomes `data-dl-confirm` (the core confirm dialog runs before the click is handled); `quiet => true` suppresses the preview toast so the screen can do the work itself on `dl:grid-bulk`. Checkboxes exist only when `bulk` is non-empty (and not report mode). `key` values `confirm`, `approve`, `cancel`, `delete`, `reject` are forbidden on transactional grids (G128).

### Detail

```php
:detail="['key' => 'lines', 'title' => 'Item: {po_no}', 'columns' => [
    ['key' => 'item_label', 'label' => 'Item Name', 'strong' => true],
    ['key' => 'qty', 'label' => 'Qty', 'type' => 'number'],
]]"
```

`key` = row field holding a list of child rows; `columns` = `[key, label, type?, strong?]` (child cells are formatted for `money`, `number`, `integer`, `date`, `datetime`, `time`; other types print as text; numeric types right-aligned); `title` may contain `{field}` of the parent row. The row count is deliberately not shown in the title. Empty child list shows "No details". Title and column labels are translated server-side (`Grid::translateTexts()`).

### Cards

`['image' => 'photo', 'title' => 'name', 'sub' => 'code', 'badge' => 'status', 'tones' => ['Active' => 'success'], 'meta' => ['dept', 'city']]`: all values are row field names except `tones` (value -> tone). `meta` entries use the matching column's label and format. Rendering the prop adds a Table/Cards segment to the toolbar.

### Row tone

`row-tone="priority"`: the row field holds `danger | tangerine | yellow | success | accent` (soft background plus a firm underline) or `muted` (grey text), empty = normal row. Always add a text column (badge + `toneKey`) that states the meaning, so colour is never the only signal. Not allowed on transactional grids (G122).

## Behavior

### Rendering pipeline

1. `Grid::__construct()` calls `applyKind()` (rewrites `actions` and the first column), then, in report mode, forces `actions = 'none'`, `new-record = null`, `bulk = []`, `quick-add = []`, then defaults `per-page`.
2. Before the view renders, `DevlibComponent::resolveView()` calls `translateTexts()` (labels, `search`, and in `Grid` also `detail` title and column labels).
3. `grid.blade.php` writes the root `div.dl-grid[data-dl-grid]` with `data-config` (`config()` as JSON, columns completed with defaults) and `data-rows` (`$rows` as JSON), plus the empty containers `data-g-stats`, `data-g-tabs`, `data-g-head`, `data-g-body`, `data-g-foot`, `data-g-pager`, `data-g-cards`, `data-g-chips`, the toolbar and the bulk bar. Both JSON attributes are HTML-escaped by Blade `{{ }}`.
4. `grid.js` (`Devlib.define("Grid", Grid)`, selector `[data-dl-grid]`) parses both, restores saved state, and `redraw()` = `drawStats` + `drawTabs` + `drawTools` + `drawHead` + `drawBody` + `drawBulk` + `persist`. All changes after that re-run the relevant draw functions; nothing talks to the server except the optional toggle POST.

CSS classes on the root: `dl-grid`, `dl-grid--report`, `dl-grid--entry` (not report), `dl-grid--tx` (`kind="transactional"`, zebra rows). `data-density` is set from state; `is-clamped` limits text lines per cell.

### Search

One box, debounced 250 ms. A row matches when the lower-cased term is contained in the **formatted text of any visible column** (money/date/etc. as displayed) or in the raw value of that column's `code` / `party` field. Hidden columns are not searched. Search always resets to page 1. It is shown as a chip ("Search: ...") and is not persisted (only restored by `restore`).

### Sort

Click a sortable header: ascending, then descending, then unsorted. Number-like types (`number`, `money`, `integer`, `link`) sort numerically; everything else with `localeCompare('id', {numeric: true})`. Sorting works on the full filtered set before pagination. The sort state is persisted.

### Filters, stats, tabs, chips

Rows must satisfy, in order: selected stat, selected tab, select filters, per-column criteria (report), and search. Every active condition is a removable chip with a "Clear all" link. Clicking the active stat again, or any All box, clears the stat; clicking a different tab switches (clicking the active tab returns to the All tab if one exists). The filter bar (`dl-grid__fbar`) is rendered only when `stats` or `tabs` is non-empty; a separator is shown when both exist. The moving underline (`data-g-find`) follows the active box and takes its colour.

Report mode: the Filter button opens a modal with the select filters ("Selection") and one criterion input per column (not for `image`, `toggle`, `spark`, `button`, `steps`, `progress`, `rating`). Text criteria mean "contains" (case-insensitive); on `number`, `money`, `integer` columns `>`, `<`, `>=`, `<=`, `=` followed by a number are understood (e.g. `>1000`). Values apply on **Apply**; Reset clears everything; Enter applies and Esc closes. A counter badge shows the number of active filters. A "N rows match out of M" counter appears next to it.

### Column chooser

The Columns pop-over lists every column with a checkbox. The last visible column cannot be hidden. Hidden state is persisted and also determines which columns are exported and searched.

### Grouping

With a group field the body is split into group header rows (collapsible, with row count and per-column `sum` subtotals). **Grouping is applied to the current page only** (buckets are built from the page slice), so a group can continue on the next page and subtotals do not cover other pages. Empty values are grouped as "(empty)". Collapsed groups are not persisted; the group field is.

### Density and lines per cell

Report mode only (the Density pop-over is `@unless isEntry`): compact / normal / comfortable plus "lines per cell" automatic or fixed (1-5). Report default density is `compact`. In entry mode state is forced on every load: density `normal` when `actions === 'master'`, otherwise `compact`; lines `fixed`, 1.

### Pagination and per page

Client-side. The pager shows: go-to-page input, page-size buttons (`10,25,50,100` entry; `50,100,250,500` report), first/previous, up to 5 numbered pages, next/last, and "[from - to of total]". The size choice is persisted. A `per-page` not in the size list shows no active size button but still works.

### Selection and bulk actions

Checkboxes only if `bulk` is non-empty. Click selects; Shift+click selects the range between the last clicked row and this one (within the current page); the header box selects/clears the page. The bulk bar shows the count, "Select all N results" (every filtered row across pages), the bulk buttons and "Clear selection". Selection survives paging, filtering and sorting (a `Set` of string keys) and is cleared by `setRows()`. A bulk click emits `dl:grid-bulk` with `{action, ids}`; unless `quiet`, a preview toast "N rows (preview)" is shown first.

### Opening a row

- Double-click on a row or card (not on a button, input or label) emits `dl:grid-open` with `{id, action: 'open'}`. The Open icon (worklist) and the Edit icon (master) emit it with `action: 'open'` / `'edit'`.
- If the event is not cancelled and `open-url` is set and the action is `open` or `edit`, the grid calls `keep()` (for `restore`) and assigns `location.href` with `{id}` replaced by `encodeURIComponent(id)` and every other `{field}` replaced by the raw row field value.
- Without `open-url` the default is a preview toast ("Open: name (double-click)"). Cancel the event (`preventDefault`) to take over completely. Note that the toast text uses the row field `name`, falling back to the key.
- Rows carry a `title` of "Double-click to open" regardless of `open-url`.

### + New Record

`new-record` renders a primary button (`data-g-new`). Click emits `dl:grid-new`; if not cancelled: `new-modal` set -> opens `[data-dl-modal-id="<new-modal>"]` (adds `is-on`); else `new-url` set -> `keep()` and navigate; else a preview toast. Hide it for users without the right with `:new-record="null"`.

### Active / Inactive (master)

`actions="master"` (or `kind="master"`) renders two icon buttons per row: Edit and a toggle (ban icon when active, check icon when inactive). The toggle button carries `data-dl-confirm`, so the core confirm dialog ("Deactivate this record?") runs first. Then:

- With `toggle-url` and `active-key`: `POST <toggle-url with {id}>` (`credentials: same-origin`, `X-CSRF-TOKEN` from `config.csrf`, `Accept: application/json`). The status shown afterwards is the server's answer `{"active": true|false}`; failure or non-2xx shows a red toast "Could not save the active status." and leaves the row unchanged.
- With `active-key` but without `toggle-url`: the row is flipped locally with a toast (pure preview, lost on reload).
- A row with `active === false` (strictly) gets the `is-inactive` style. Rows should carry a real boolean.

Entry-mode action buttons appear in a title-less first column (icons without box); worklist grids have a single Open icon.

### Export

The Export button opens a two-item menu, CSV and XLSX. The export takes the **visible columns in their current order, all rows matching the current filters, in the current sort order** (not only the current page). Values are raw row values (not formatted); `identity` with `code` is written `name (code)`, `doc` with `party` `name (party)`. CSV: `;` separator, every cell quoted, UTF-8 BOM, file `<id>.csv`. XLSX is written in the browser without a library (`downloadXlsx`), numbers stay numbers for `number`, `money`, `integer` (and `decimal`) columns; file `<id>.xlsx`. A toast reports rows x columns.

### Card view

When `cards` is set, the Table/Cards switch toggles between the table and a card grid of the **current page** of the filtered, sorted rows. Cards support double-click open but have no checkbox, actions or detail. The choice is not persisted.

### Nested detail rows

If `detail` is set (or `expand` is non-empty), a chevron column appears. `detail` wins when both are given. Open rows are kept in memory only. `expand` shows a small label/value box for the listed column keys.

### Quick add

`quick-add` lists column keys; one input per key plus an Add button forms a row at the top of the table body (re-drawn on every body render). Enter in an input, or Add, validates that the **first** field is non-empty (warn toast "X is required."), builds a row `{<row-key>: 'n' + Date.now(), <active-key>: true, ...values}` (numeric columns parsed with `parseNum`), `unshift`s it into the client rows, redraws, emits `dl:grid-add` with `{row}` and shows "Row added.". **Nothing is saved**: the screen must listen to `dl:grid-add` and persist.

### Row tone, summary, footer

Row tone and `sum` are described under Props. The footer (`tfoot`) shows "Total (all results)" in the first visible column (unless that column is itself a `sum` column) and the total over **all filtered rows** of each `sum` column, only when at least one `sum` column is visible and the page is not empty.

### Empty state

No matching rows (or no rows at all) shows an `dl-empty` block "No matching data" with a hint to change keyword, clear filters or pick another tab.

### Keyboard

Enter in a quick-add input adds the row; in the report Filter modal Enter applies and Esc closes. Pop-overs (Filters, Columns, Group, Density) close on any click outside; the Export menu closes on outside click. The page input accepts a number and applies on change. Rows are not keyboard-focusable and there is no arrow-key navigation: the main keyboard route to open a row is the action icon button (focusable `button`).

### Translation

Screens write English; `Lang` translates at render time:

- Translated server-side: `columns[].label`, `stats[].label`, `tabs[].label`, `filters` and `groups` values, `search`, `detail.title` and `detail.columns[].label`, plus the fixed labels in the Blade view (`Search`, `Filters`, `Columns`, `Filter`, ...).
- Not translated: row data (cell values, badge texts), `bulk` labels and `new-record` (rendered by `x-dl::button`, whose labels stay English by design), `cards`, `quick-add` keys.
- All client-side texts (`g_*` keys: pager, chips, toasts, empty state, action titles, density) come from `window.DL_LANG` via `T()` / `Tf()` and follow the current language.

### Access (`can`)

`<x-dl::grid can="po.view" ...>`: the `can` attribute is intercepted by `DevlibComponent::withAttributes()`; when `Devlib\Access::can('po.view')` is false the component renders nothing (not hidden by CSS). `Access` uses the resolver set with `Access::resolveUsing()` or `config('devlib.abilities')`. There is no per-row or per-button access; hide `new-record` or choose `actions` yourself.

### Persistence summary

| Stored | Where | Key | Content |
|---|---|---|---|
| Per-grid user settings | `localStorage` | `dl-grid-<id>` | `sort, dir, hidden, group, perPage, density, lines, linesN, filters, tab` |
| Return state (only `restore`) | `sessionStorage` | `dl-grid-ret-<id>` | `search, page, stat`; written by `keep()` before navigating, read and removed once on load |

Not stored: search, page, stat box, view (table/cards), collapsed groups, open details, selection, per-column criteria.

## Data and events

### data-config keys (JSON on `data-config`)

`id`, `columns`, `rowKey`, `stats`, `tabs`, `filters`, `groups`, `bulk`, `expand`, `actions`, `activeKey`, `perPage`, `openUrl`, `newUrl`, `newModal`, `toggleUrl`, `csrf` (only when `toggle-url` is set), `detail` (or `null`), `cards` (or `null`), `quickAdd`, `groupBy`, `mode`, `rowTone`, `restore`, `pageSizes`. `kind`, `search` and `new-record` are not in the config (they only change PHP-side output: the `dl-grid--tx` class, the placeholder and the button). `data-rows` is the `rows` array.

### Root and hook attributes

| Attribute | Where | Use |
|---|---|---|
| `data-dl="x-dl::grid id=<id>"`, `data-dl-class`, `data-dl-src` | root | Technical Guide link (KTP); `data-dl-src` = `source` |
| `data-dl-grid` | root | mount selector for the JS class |
| `data-config`, `data-rows` | root | configuration and data |
| `data-density` | root | current density |
| `data-g-search` | search input | |
| `data-g-fbar`, `data-g-stats`, `data-g-tabs`, `data-g-fsep`, `data-g-find="stats\|tabs"` | filter bar | |
| `data-i` | stat/tab button | index in `stats` / `tabs` |
| `data-g-toggle="filters\|columns\|group\|density"`, `data-g-panel`, `data-g-pop` | pop-overs | |
| `data-g-filter`, `data-g-filter-fields`, `data-g-filter-reset`, `data-g-filter-count` | Filters | |
| `data-g-col`, `data-g-col-list` | Columns chooser | |
| `data-g-group`, `data-g-group-list`, `data-g-grp` | Group chooser / group header | |
| `data-g-density`, `data-g-lines`, `data-g-lines-n`, `data-g-density-reset` | Density panel | |
| `data-g-view` (`table`/`cards`), `data-g-cards` | view switch / card container | |
| `data-g-export`, `data-g-expmenu`, `data-g-fmt="csv\|xlsx"` | Export | |
| `data-g-new` | New Record button | |
| `data-g-chips`, `data-g-chip="all\|stat\|search\|f:<k>\|c:<k>"` | chips | |
| `data-g-bulk`, `data-g-bulk-count`, `data-g-bulk-action`, `data-g-bulk-quiet`, `data-g-select-all`, `data-g-select-none`, `data-g-all` | bulk bar / page checkbox | |
| `data-g-head`, `data-g-body`, `data-g-foot`, `data-g-pager`, `data-g-count` | table parts | |
| `data-p` (page), `data-pp` (page size), `data-g-goto` | pager | |
| `data-id`, `data-idx` | `tr.dl-grid__row` / card | row key, index in the page |
| `data-g-act="edit\|open\|toggle"` | row action icon | |
| `data-g-exp` | chevron | expand/collapse row |
| `data-g-link="<key>"` | link cell | |
| `data-g-img` | thumbnail | lightbox |
| `data-g-cell="<key>"` | toggle column switch | |
| `data-g-action="<action>"` | button column | |
| `data-g-qa="<key>"`, `data-g-qa-add` | quick add | |
| `data-g-fmodal`, `data-g-fopen`, `data-g-fclose`, `data-g-fapply`, `data-g-freset`, `data-g-fform`, `data-gf-sel`, `data-gf-col` | report Filter modal | |

### Events (dispatched on the grid root, bubbling, cancelable except `dl:grid-bulk`)

| Event | `detail` | Fired when | Default if not cancelled |
|---|---|---|---|
| `dl:grid-open` | `{id, action}` (`open`, `edit`, or `toggle` when the toggle has no `active-key`) | double-click, Open/Edit icon | navigate to `open-url` (open/edit) else preview toast |
| `dl:grid-new` | `{}` | New Record click | open `new-modal`, else navigate to `new-url`, else preview toast |
| `dl:grid-bulk` | `{action, ids[]}` (string ids) | bulk button | preview toast (unless `quiet`); not cancelable |
| `dl:grid-link` | `{id, key}` | click on a `link` cell without `url` (`href="#"`) | preview toast |
| `dl:grid-action` | `{id, action}` | `button` column | preview toast |
| `dl:grid-cell` | `{id, key, value}` | `toggle` column switch | none (the row field is updated in memory) |
| `dl:grid-add` | `{row}` | quick-add row created | none (screen must persist) |

Preview toasts exist so a grid with no screen logic still reacts in the catalog; production screens listen and `preventDefault()` or persist.

### JS access

`el.dlGrid = { rows, cfg, redraw, setRows(list) }` on the root element. `rows` is the live array (mutating it requires a `redraw()`), `setRows(list)` replaces the rows, returns to page 1, clears the selection and redraws. `x-dl::drilldown` (`drill.js`) uses `dlGrid.rows`, `cfg.rowKey`, `setRows` and listens to `dl:grid-open` on the summary grid. `Devlib.Component` autobinds methods, so `redraw` can be passed as a callback. The element also has `el.dlComponent` (the `Grid` instance, with `st` = state, `filtered()`, `sorted()`, `exportFile(fmt)`).

Helpers taken from `Devlib.h`: `srcAttr`, `$`, `$$`, `esc`, `toast`, `fmt` (`number`, `money`, `gridDate`), `parseNum`, `DlDateTime`, `DlTime`, `T`, `Tf`, `downloadXlsx`. The global confirm handler (`data-dl-confirm`) and the modal opener (`data-dl-modal-id`) live in `core.js` / `x-dl::modal`.

### URL contracts

| Prop | Request | Placeholders | Expected |
|---|---|---|---|
| `open-url` | browser navigation (GET) | `{id}` URL-encoded; `{field}` raw row value | a page (usually the form) |
| `new-url` | browser navigation (GET) | none | the "new record" page |
| `toggle-url` | `POST`, `Accept: application/json`, header `X-CSRF-TOKEN`, no body | `{id}` URL-encoded | JSON `{"active": bool}` with HTTP 2xx; anything else is a failure toast |
| column `url` (text) | link `href` | `{id}`, `{field}` (URL-encoded) | any page, typically another grid |
| column `url` (`link`) | link `href` | `{id}` only | transactions of the row |
| `?hl=<id>` on the grid address | read on load (`restore` only) | | highlights the row for 4 s and jumps to its page |

## Rules and pitfalls

### Mandatory project rules (enforced by `Devlib\Lint\GridRules`, opt-in via `php artisan devlib:lint [--render] [--strict] [--level=info|warn|error]`)

| Code | Level | Applies to | Rule |
|---|---|---|---|
| G101 | info | entry grid without `kind` | Use `kind="master\|transactional"`; legacy behaviour otherwise |
| G110 | error | `kind="master"` | `toggle-url` is mandatory, otherwise Active/Inactive is preview only |
| G111 | warn | `kind="master"` | First column must be `identity` with `box` (normally guaranteed by `applyKind()`) |
| G112 | warn | transactional | First column plain bold text: no `box`, `hue`, avatar |
| G113 | warn | transactional | No `progress`, `rating`, `spark`, `steps` or `hue` columns: only Status may be coloured |
| G120 | error | transactional | A Status column `type=badge` with `tones` or `toneKey` is required (the source-level linter reports it as warn) |
| G121 | warn | transactional | 5-8 visible columns (hide the rest with `visible => false`) |
| G122 | error | transactional | No `row-tone` |
| G123 | warn | transactional | No item-count column (keys like `items`, `lines`, `rows`, `item_count`, `total_lines`, ...) of type `integer`/`number` |
| G124 | error | transactional with `detail` | At most 7 detail columns |
| G125 | error | transactional with `detail` | Detail must include an item name column (key or label matching name/item/product/material/description/barang) |
| G126 | warn | transactional with `detail` | No row count in the detail title |
| G127 | warn | transactional with `detail` | No prices in detail (no `money`, no key matching price/harga) |
| G128 | error | transactional | No bulk `confirm`, `approve`, `cancel`, `delete`, `reject`: those live in the document form only |
| G129 | warn | transactional | No `toggle-url` and no `toggle` column (transactions are never deactivated) |

Report grids return no findings (zebra, no checkboxes/Actions/New Record are forced by the constructor). `ViewLinter` also scans Blade source for `x-dl::grid` tags (G101, G110, G120, G122) and the general rules L001 (raw HTML), L002 (inline script) and L003 (hex colours). Findings never change the output.

Other project rules (from `skills/devlib/grid.md` and `grid-form-flow.md`):

- Transactional and report grids always have zebra rows; do not add them manually. Selected and toned rows are not overwritten.
- Master: first column box, Active/Inactive requires `toggle-url`. Transactional: Open only, no Confirm/Approve/Cancel/Delete in the grid.
- A grid that opens a form uses `restore`, `:open-url` and `:new-url` that carry `from=<grid address with query>` (form `:back` is that address; never `history.back()`).
- Every entry grid: `+ New Record` through `new-url`, `search` says which columns are searched, `stats` are filter boxes with counts, the toolbar (search, filters, columns, export) comes for free, bulk only when requested.
- Grid Approval: confirmed documents only, two badge columns (Status, Approval), tabs All (without Rejected), Awaiting, Hold, Approved, Rejected.
- Nested Grid: grid-to-grid only, never a form, any depth, links through `url` + `ref => true`.

### Common mistakes

- **Duplicate or changing `id`**: two grids with the same `id` share `localStorage` state; renaming resets everyone's settings. Nested levels need an id per level.
- **Saved column state overrides code**: `hidden` is written to `localStorage` on the first draw, so later edits to `visible` are ignored in browsers that already opened the grid. Same for saved `perPage`, `group`, `sort`, `tab`, `filters`.
- **Using positional columns** (`['name', 'Name']`): the code needs the named `key` and `label`.
- **Expecting `source` to load data**: it does not; pass `rows`.
- **Grouping/subtotals expected across pages**: they apply to the current page only. Footer totals cover all filtered rows.
- **Bulk without `bulk`**: there are no checkboxes without a `bulk` list, and no Select-all.
- **`kind` plus manual `actions`/`box`/`avatar`**: `kind` overrides them; do not write them.
- **`tones` missing**: a badge without a matching tone renders `neutral`.
- **Quick add / toggle column / New Record in report mode**: quick add and New Record are removed; the screen must persist quick-add and toggle changes itself.
- **`{field}` in `open-url`** is not URL-encoded; prefer `{id}` or build the address on the server.
- **`:new-record="false"`** works only because PHP coerces `false` to `''` for a `?string`; prefer `null`.
- **Selector clash in rows**: the selection handler matches `.dl-grid__row input[type=checkbox]`, which also matches the switch of a `toggle` column (see the note at the end of this section).

### Performance

- Everything is client-side: rows are in the HTML as JSON and each search/filter/sort walks the full array. This is intended for lists up to thousands of rows (the reason transactional grids are plain: no boxes, no avatars, 5-8 columns). Counts on stats and tabs are recomputed on every redraw. Use pagination sizes and fewer visible columns before anything else; for tens of thousands of rows move to a server-side variant that keeps the markup.
- Send only the fields the grid needs; every hidden column is still in the page source.
- Search is debounced (250 ms); pager and selection redraw only the body.
- `image` thumbnails use `loading="lazy"`.

### Security

- All cell text, labels, titles and attribute values go through `esc()` (escapes `& < > " '`); JSON attributes are escaped by Blade. Rows are *data*: never pre-render HTML into them.
- Hidden columns and filtered-out rows are still delivered to the browser: do not put secrets or rows the user may not see into `rows`. Filter by authorisation on the server before building the array.
- Links and images use the values as given: `image` columns use the row value as `src`; the `open-url` with `{field}` (for example `'{open_url}'`) assigns the row value to `location.href`. Build these values on the server and never from user-controlled free text.
- Server-side authority: `toggle-url` must authorise, validate and return the real state (the grid trusts the answer). Quick add, `toggle` cells and bulk actions are client events only: re-check permissions and validate on the server. The POST uses `X-CSRF-TOKEN` (`config.csrf` only exists when `toggle-url` is set).
- CSV export does not neutralise cells that start with `=`, `+`, `-` or `@`; treat exported files from untrusted data as untrusted in spreadsheets.
- Use `can="module.action"` for object visibility, but still enforce the same rule on the routes the grid points to.

### Notes on inconsistencies found while writing this entry (not fixed)

- `columns` are documented positionally in the header comment but read by name in code.
- Link columns: type `link` replaces only `{id}` in `url`, text columns replace `{id}` and `{field}`.
- Export treats the type `decimal` as numeric, but `decimal` is not a column type of the grid (no special formatting).
- G112 can hardly fire: `applyKind()` already strips `box`/`hue` and sets `avatar => false` on an identity first column.
- `.dl-grid__row input[type=checkbox]` also matches `toggle` column switches, so clicking one can run the selection logic (may show the bulk bar and re-render the row before `change` fires). Not verified in a browser.
- A saved `tab` index larger than the current `tabs` count (tabs edited after the user saved settings) is read as `cfg.tabs[st.tab].where` without a guard.
- Preview toasts use the row field `name` even when the grid has no such field.

## Example

Master grid (Grid Pre-Entry -> Master Form), from the flow demo:

```blade
<x-dl::grid id="flow-master" kind="master" restore :rows="$employees" search="Search name, code, position..."
    :open-url="url('/hr/employees/{id}').'?from='.urlencode(request()->getRequestUri())"
    :new-url="url('/hr/employees/new').'?from='.urlencode(request()->getRequestUri())"
    :toggle-url="url('/hr/employees/{id}/toggle')"
    :columns="[
        ['key' => 'name', 'label' => 'Employee', 'type' => 'identity', 'code' => 'code'],
        ['key' => 'dept', 'label' => 'Department'],
        ['key' => 'position', 'label' => 'Position'],
        ['key' => 'status', 'label' => 'Status', 'type' => 'badge', 'tones' => ['PERMANENT' => 'success', 'CONTRACT' => 'info', 'DAILY' => 'warn']],
        ['key' => 'join_date', 'label' => 'Joined', 'type' => 'date'],
    ]"
    :stats="[['label' => 'Total'], ['label' => 'Active', 'where' => ['active' => true], 'tone' => 'success'], ['label' => 'Inactive', 'where' => ['active' => false], 'tone' => 'danger']]"
    :filters="['dept' => 'Department', 'status' => 'Status']" />
```

The server route for `toggle-url` answers `{"active": true|false}` after changing the record.

Transactional grid (document list) with tabs, a total and an expandable child table:

```blade
@php $tones = ['Draft' => 'neutral', 'Confirmed' => 'success', 'Cancelled' => 'danger', 'Closed' => 'info']; @endphp
<x-dl::grid id="flow-po" kind="transactional" restore :rows="$purchaseOrders" search="Search PO no., supplier..."
    :open-url="route('po.form', ['id' => '{id}', 'from' => request()->getRequestUri()])"
    :new-url="route('po.form', ['id' => 'new', 'from' => request()->getRequestUri()])"
    :columns="[
        ['key' => 'po_no', 'label' => 'PO No. / Supplier', 'type' => 'identity', 'code' => 'supplier', 'avatar' => false],
        ['key' => 'po_date', 'label' => 'Date', 'type' => 'date'],
        ['key' => 'requester', 'label' => 'Requester'],
        ['key' => 'total', 'label' => 'Total', 'type' => 'money', 'sum' => true],
        ['key' => 'status', 'label' => 'Status', 'type' => 'badge', 'tones' => $tones],
    ]"
    :tabs="[['label' => 'All'], ['label' => 'Draft', 'where' => ['status' => 'Draft']], ['label' => 'Confirmed', 'where' => ['status' => 'Confirmed']], ['label' => 'Closed / Cancelled', 'where' => ['status' => ['Closed', 'Cancelled']]]]"
    :filters="['supplier' => 'Supplier']"
    :detail="['key' => 'lines', 'title' => 'Items', 'columns' => [
        ['key' => 'item_label', 'label' => 'Item Name', 'strong' => true],
        ['key' => 'qty', 'label' => 'Qty', 'type' => 'number'],
        ['key' => 'uom', 'label' => 'Unit'],
    ]]" />
```

Report grid and a Nested Grid link column (no form):

```blade
<x-dl::grid id="grid-report" mode="report" :rows="$salesLines" search="Search invoice, customer, product..."
    :filters="['category' => 'Category', 'sales' => 'Sales', 'status' => 'Status']"
    :columns="[
        ['key' => 'inv_date', 'label' => 'Date', 'type' => 'date'],
        ['key' => 'inv_no', 'label' => 'Invoice No.', 'url' => '/reports/invoice/{id}', 'ref' => true],
        ['key' => 'customer', 'label' => 'Customer', 'url' => '/reports/customer/{customer_slug}', 'ref' => true],
        ['key' => 'qty', 'label' => 'Qty', 'type' => 'number', 'sum' => true],
        ['key' => 'value', 'label' => 'Value', 'type' => 'money', 'sum' => true],
        ['key' => 'status', 'label' => 'Status', 'type' => 'badge', 'tones' => ['PAID' => 'success', 'DUE' => 'warn', 'UNPAID' => 'danger']],
    ]" />
```

Handling events (in the project's DevLib JS component, not inline on a screen; lint rule L002 forbids inline scripts):

```js
grid.addEventListener('dl:grid-bulk', e => save(e.detail.action, e.detail.ids));
grid.addEventListener('dl:grid-add', e => persist(e.detail.row));
```

## Related

- `x-dl::form` (the page a master/transactional grid opens; `:back` returns to the grid, `:strict` cycle)
- `x-dl::lines` (detail rows inside a document form)
- `x-dl::lookup-grid` (Grid Lookup: small helper lists edited in cells, no form)
- `x-dl::sheet` (in-cell editing, base of the lookup grid)
- `x-dl::pivot` (cross-table of flat data)
- `x-dl::drilldown` (summary grid -> detail grid on `dl:grid-open`)
- `x-dl::report` (printable report; Period + Show)
- `x-dl::modal` (target of `new-modal`)
- `x-dl::button` (toolbar, bulk and row buttons; confirm via `data-dl-confirm`)
- `x-dl::pagination` (standalone pager; the grid draws its own)
- `x-dl::empty-state` (empty states elsewhere)
