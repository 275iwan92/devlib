## Overview

`<x-dl::sheet>` is the spreadsheet-style cell grid of DevLib: a table in which every cell is edited in place, with the keyboard habits of Excel (arrow keys, Tab, F2, Delete, Ctrl+C / Ctrl+V with Excel, a fill handle that copies or continues a number series, Ctrl+Z / Ctrl+Y). Edited cells are marked, invalid cells turn red, and one Save sends only the changed rows to the server (`save-url`) or, without a URL, raises a JavaScript event (preview mode). It is DevLib's own implementation of the behaviour of DataTables Editor + AutoFill + KeyTable (the original Editor is a paid product).

The class `Devlib\Components\Sheet` has no Blade view file: the markup is the inline `render()` of the class. The behaviour is the JS class `Sheet` in `resources/assets/js/components/sheet.js` (selector `[data-dl-sheet]`). `x-dl::lookup-grid` is a thin PHP subclass that forces `lookup=true`; this entry documents the shared engine, including the lookup mode (`lookup-grid` has its own entry that lists only what it adds).

### Two modes

| | Full mode (`lookup=false`, default) | Lookup mode (`lookup=true`, what `x-dl::lookup-grid` always uses) |
|---|---|---|
| Toolbar | optional bold `title`, `+ Row` (unless `add-rows=false`), `Delete Row` (local), Undo icon, Redo icon, info text (`N rows · C changed`), `Save All (C)` | Search box (`searchable`), info text (`searchable`/`show-count`), `+ <add-label>`, optional server `Delete row` (`delete-url`), optional `Reset` (`reset-url`), `Undo` (discard all) and `Save` (both omitted when `auto-save` + `save-url`) |
| Help line under the grid | yes (`Lang` key `s_help`) | no |
| Stat boxes act as filters | no (they only show counts) | yes |
| Search filter | no search box | yes |
| `title` shown | yes | no |
| `add-label`, `delete-label`, `reset-label`, `delete-url`, `reset-url`, `searchable`, `show-count` | have no effect (the Blade branch does not render them) | effective |
| `auto-save` | the posting works, but the Save All button is still shown | the Undo and Save buttons are removed |
| Boolean (`check`) cells | show a check mark | show a small on/off switch |
| Density | normal | compact (`dl-sheet--lookup`) |

### When to use it, and when NOT

- Use `sheet` for a list that people fill like a worksheet: bulk entry of many rows, schedules, plan tables (BOP / BOM), prices, any "edit many cells, then Save once" screen, including server-backed lists with `save-url`.
- Use `x-dl::lookup-grid` for the small helper lists that rarely change (religion, blood type, holiday, shift, supplier category), where the standard is a compact grid with Search + Row + Undo + Save, no Delete, and `unique` codes.
- Use `x-dl::grid` for browsing and finding records (search, filter, tabs, paging, export, open a form per row). A `grid` shows data; a `sheet` edits it in cells. A master data screen with a form per record is `grid` + `x-dl::form`, not a sheet.
- Use `x-dl::lines` for the item rows INSIDE a document form (PO, CO, stock in): they post with the form, have formulas, totals, discount/tax footer and lookups. A `sheet` has none of those and saves by itself.
- Use `x-dl::table` for a read-only table.

## Props

Constructor of `Devlib\Components\Sheet` (19 props). Blade names are kebab-case; non-string values use `:prop="expr"`. `x-dl::lookup-grid` accepts the same props except `lookup` (it is always `true`).

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `columns` | `array` | `[]` | Column definitions, see "Column definition" below. Labels are translated (`Lang::text`). |
| `rows` | `array` | `[]` | Initial rows, a list of associative arrays; written as JSON to `data-rows`. Each row must contain the `row-key` field; keys that are not columns travel with the row and are posted back (hidden helper keys such as a process id used by `optionsBy` or a stat `where`). |
| `row-key` | `string` | `'id'` | Name of the field that identifies a row. New rows get a temporary key `new1`, `new2`, ... (counter per sheet) that the server replaces in its answer. |
| `title` | `string` | `''` | Bold title in the toolbar. Full mode only. Translated. |
| `source` | `?string` | `null` | Data source text for the developer catalog (`data-dl-src`). Default `'tabel master (edit massal)'`. Not shown to users. |
| `lookup` | `bool` | `false` | `true` = Lookup mode (compact bar with Search, Row, Undo, Save; no local Delete). Normally you do not set this: use `x-dl::lookup-grid`. |
| `save-url` | `?string` | `null` | Server endpoint for Save (contract below). `null` = preview mode: Save only raises `dl:sheet-save` and the screen treats the data as saved. |
| `stats` | `array` | `[]` | Stat boxes above the grid: `[['label' => 'Active', 'where' => ['active' => true], 'tone' => 'success']]`. `where` is `field => value` or `field => [values]` (a row matches when every field matches; a missing `where` matches every row); `tone` defaults to `accent`. The numbers are recounted from the live data in the browser. In lookup mode a box is also a filter (click on, click again off, one active at a time). Labels are translated. |
| `add-rows` | `bool` | `true` | `false` = no `+ Row` button, for rows that come from another process (for example an engine output). It does not block pasting past the last row, which still appends rows. |
| `reorder-url` | `?string` | `null` | Set = rows can be dragged onto another row to change the order; the sheet POSTs `{order, reason}` (contract below). |
| `reason-on-save` | `?string` | `null` | Set = Save first opens a dialog with this text and a required reason field; the reason is posted with the rows. Used only when saving by the Save button; it is ignored when `auto-save` is on. |
| `links` | `array` | `[]` | Computed cells, see "`links`" below. |
| `auto-save` | `bool` | `false` | `true` and `save-url` set = every edited cell is saved at once (the edited rows are POSTed); a refusal puts the cell back. Without `save-url` the prop is ignored (config sends `autoSave && saveUrl`). |
| `add-label` | `?string` | `null` | Text of the add-row button, default `Row` (rendered as `+ Row` by the icon). Lookup mode only. A new row is saved by `auto-save` once all its required cells are filled. |
| `delete-url` | `?string` | `null` | Set = a server `Delete row` button (contract below). Lookup mode only. |
| `searchable` | `bool` | `true` | `false` = no Search box. Lookup mode only. |
| `show-count` | `bool` | `true` | `false` = no row counter. Lookup mode only. |
| `delete-label` | `?string` | `null` | Text of the delete button, default `Delete row`. When set, the button is the red `danger` variant and is enabled only after a row number was clicked (the whole row is chosen); when null the button is the plain `secondary` variant and is always enabled and acts on the current cell range. Lookup mode, needs `delete-url`. |
| `reset-url` | `?string` | `null` | Set = a `Reset` button (contract below). Lookup mode only. |
| `reset-label` | `?string` | `null` | Text of the reset button, default `Reset`. |
| `row-tone` | `?string` | `null` | Name of a row field whose value (`danger`, `warn` or `success`) tints the whole row (read-only and editable cells alike; the selected / current / invalid cell keeps its own colour). Empty or missing value = no tint. The server sets the field, so a tinted row follows the data after every save. Both modes. Default: no row is tinted and the markup is unchanged. |

### Column definition

`columns` is a list of arrays. `config()` adds the defaults `type => 'text'`, `required => false`, `readonly => false`, and turns a plain list `options` (`['A', 'B']`) into a map `key => label` (`['A' => 'A', 'B' => 'B']`).

| Key | Values and default | Meaning |
|---|---|---|
| `key` | string, required | Field name in the row. Posted back unchanged. |
| `label` | string | Column heading (translated). A red `*` is added for `required`. |
| `type` | `text` (default), `number`, `integer`, `money`, `select`, `date`, `time`, `datetime`, `check` | The cell type, see below. An unknown type behaves as text. |
| `options` | list or map | For `select`: the choices. A list becomes key = label. The stored value is the key, the cell shows the label. Typing a key or a label (any case) in the cell finds the option. |
| `optionsBy` | `['key' => 'process', 'map' => ['P1' => ['M1' => 'Machine 1']]]` | Chained select: the choices of this cell are `map[value of the row field named key]`. Not described in the PHP comment but supported by the JS. The column still needs the complete `options` map (the cell text, validation and copy read it). With `auto-save`, a new row is posted only after these cells are chosen when the first field has choices. |
| `required` | `false` | The cell must not be empty. Marked `*`, red when empty; Save is refused while any cell is invalid. |
| `readonly` | `false` | Never editable (also not by paste, fill or Delete). |
| `lockSaved` | `false` | Locked (grey) on rows that already exist on the server; editable on new rows. For code columns that other data refers to. |
| `lockFilled` | `false` | With `lockSaved`: a saved cell is locked only when it already has a value; an empty saved cell can still be chosen. |
| `emptyText` | string | Select cells: text shown for an empty value (for example `Process not set`). |
| `emptyWarn` | `false` | An empty cell gets a warning colour (class `is-empty`), for example untranslated entries. |
| `unique` | `false` | The value may not repeat in another row (trimmed, not case-sensitive). Marked red; the server must still validate. |
| `width` | CSS width, e.g. `'110px'` | Style on the heading cell. |
| `case` | `uppercase` or `capitalize` | Text columns: initial state of the `aa / Aa / AA` badge shown in the cell being edited. The user can cycle the badge; it is remembered per column while the page is open. |
| `decimals` | integer | Number columns: always show this many decimals (`18.450,00`). Values are rounded to it when typed. |
| `decimal` | `true` | Number columns: free decimals (conversion factors like `0,001`) without a fixed digit count. |
| `min`, `max`, `step` | numbers | Integer spinner limits and step (spinner arrows). |

Cell types:

| `type` | Editing | Stored value |
|---|---|---|
| `text` | Type, with the case badge | string |
| `number` | Without `decimals`/`decimal`: whole number with ▲▼ spinner. With `decimals` or `decimal`: decimal with the `,00` button | number (`''` when empty); unparseable text is kept as the string and marked red |
| `integer` | Whole number with spinner | number |
| `money` | Decimal; display prefix `IDR ` (fixed), decimal button cycles `0 / ,00 / ,0000 / calculator` | number |
| `select` | Drop-down while editing | the option key |
| `date` | Shows `dd-Mmm-yyyy`; type `10-10-26`, `10 okt`, `2026-10-10`; calendar button or Alt+Down | `YYYY-MM-DD` |
| `time` | Type `8`, `830`, `8.30`; clock button picks hour and minute (5-minute steps) | `HH:MM` |
| `datetime` | Shows `dd-Mmm-yyyy hh:mm`; calendar + hour/minute pop-up like `x-dl::field.date-time` | `YYYY-MM-DD HH:MM[:SS]` |
| `check` | Space or Enter toggles | `true` / `false` (paste accepts `1`, `true`, `ya`, `y`, `x`, `✓`) |

### `links` (computed cells)

```php
:links="[['when' => ['required', 'released'], 'set' => 'purchase', 'a' => 'required', 'op' => '-', 'b' => 'released', 'min' => 0]]"
```

| Key | Meaning |
|---|---|
| `when` | List of column keys; editing any of them (typing, paste, fill, Delete, undo) recalculates the row. |
| `set` | Column that receives the result. It is not recalculated if the same edit already changes it. |
| `a`, `b` | Operand columns (read with `parseFloat`, empty = 0). |
| `op` | `'+'` = `a + b`; anything else = `a - b`. |
| `min` | Lower bound of the result (default no bound); `0` prevents negatives. The result is rounded to 4 decimals. |

The calculation is a convenience of the screen: the server must compute and validate the value again.

## Behavior

### Selecting and keyboard

| Action | Effect |
|---|---|
| Click a cell | Active cell. Shift+click or mouse drag selects a range. |
| Click a row number | Selects the whole row (needed to enable a `danger` server Delete). |
| Arrow keys, Tab / Shift+Tab | Move the active cell; Shift+arrow extends the range. |
| Type any character | Starts editing with that character. |
| Enter or F2 | Start editing. In edit: Enter commits and moves down, Tab commits and moves right, Esc cancels, leaving the grid commits. |
| Space on a `check` cell | Toggles the check. |
| Delete / Backspace | Empties the selected range (check = false); readonly and locked cells are skipped. |
| Alt+Down | Opens the picker of a date, time, datetime or number cell (calendar, hour/minute, or the mini calculator). |
| Ctrl+C / Ctrl+V | Copies the range as tab-separated text; pastes tab-separated text (Excel) from the active cell, adding blank rows when needed. Numbers are copied raw, select cells as their labels. |
| Drag the small square at the corner of the selection | Fill down: copies the source cells in a cycle; for number columns with at least two source values and a constant step it continues the series. Readonly columns are skipped. |
| Ctrl+Z / Ctrl+Y | Undo / redo of cell changes (cell-level history, cleared by a successful save or reload). |

Nothing outside the cell is announced: the table is inside a focusable `div` (`tabindex="0"`, `aria-label` "Worksheet"); cells are plain `td` elements without ARIA grid roles.

### Cell states

`is-sel` (in the range), `is-cur` (active), `is-dirty` (differs from the loaded value), `is-bad` (invalid), `is-ro` (readonly/locked), `is-empty` (empty with `emptyWarn`), `is-pick`, `is-dec`. New rows are intended to get `is-new` (see the notes).

Validation (`bad`) runs on every render and again when Save is pressed: `required`, `unique`, number that is not a number, date/time/datetime format, select value that is not an option. If any cell is bad, Save shows a toast with the count (`s_bad`), moves to the first bad cell and sends nothing.

### Save flow

1. Save (the bar button) validates all cells.
2. The changed rows are all new rows plus rows where at least one cell differs from the loaded value. The whole row object (all keys) is sent, not a per-cell diff.
3. With `save-url`: if `reason-on-save` is set a dialog asks for the reason first (cancel = nothing is sent); then `POST save-url` (contract below). Success replaces the data with `rows` from the answer, clears the undo history, raises `dl:sheet-save` and shows the toast `N rows saved`. An error keeps the data and the unsaved marks.
4. Without `save-url` (preview): `dl:sheet-save` is raised, the current data becomes the loaded state and the toast says saved. Nothing is persisted.

Save is disabled while there are no changes (a toast `No changes` if it is forced). The Undo button of lookup mode asks "Discard all unsaved changes?" and restores the loaded rows (new rows disappear).

### Auto save

With `auto-save` + `save-url`, each edit is posted at once for the rows it touched. A new row is posted only when all its required cells (and the cells whose `optionsBy` list has choices) are filled. If the server answers an error, the toast shows it and the cells go back to their previous values. If the server answers `reason_required`, the sheet asks for a reason and posts again; cancelling the dialog also restores the cells.

### Server delete (`delete-url`)

Choose rows (click row numbers) and press the delete button, then confirm "Delete N selected rows?". Rows that were never saved are only removed from the screen; saved rows are posted. The button enabled state depends on `delete-label` (see the props table). The full-mode `Delete Row` button (no `delete-url`) is different: it removes the selected rows from the screen only and never tells the server (see the pitfalls).

### Reset (`reset-url`)

The button first sends a dry run, shows the server's message in a confirmation dialog titled RESET, and on confirmation sends the real request and reloads the page (the server keeps its own result message, for example as flash). Use it for "rebuild from the engine" actions.

### Reorder (`reorder-url`)

All body rows become draggable. Dropping a row on another asks for a required reason (dialog "Change the sequence"), then posts the new order of all row keys. The server answers the rows in their final order; unsaved edits on the screen are replaced by that answer.

### Live link to a Gantt

The sheet listens on `document` for `dl:plan-sync` (raised by `x-dl::gantt`, detail `{id, cells}`). The row whose `row-key` equals `id` gets its `start`, `finish` and `machine` values from `cells` (or back to the loaded ones when `cells` is null). The loaded copy follows too, so these read-only cells never count as unsaved edits.

### Translation (Lang) and access (`can`)

Column and stat labels, `title`, `add-label` and the standard texts (Search..., Row, the help line, toasts, error counts, tooltips) go through `Lang` and follow the page language. The dictionary group is `grid`. Button words (Save, Undo, Delete Row, Save All) are fixed English by the project's button rule. Several dialog texts in the JS are English literals (see the notes). Field values and row data are never translated. `can="module.action"` on the tag hides the whole sheet when the user lacks the ability.

## Data and events

### Root and hook attributes

```html
<div class="dl-sheet [dl-sheet--lookup]" data-dl="x-dl::sheet" data-dl-class="Sheet ← DevlibComponent" data-dl-src="..."
     data-dl-sheet data-config='{...}' data-rows='[...]'>
  <div class="dl-grid__stats">... x-dl::stat-box ...</div>                <!-- only with stats -->
  <div class="dl-sheet__bar"> ... buttons with data-s-add / data-s-del / data-s-del-srv / data-s-reset /
        data-s-revert / data-s-save / data-s-undo / data-s-redo, search input data-s-search, text data-s-info </div>
  <div class="dl-sheet__wrap" tabindex="0" data-s-wrap><table class="dl-sheet__table" data-s-table>...</table></div>
  <p class="dl-sheet__help">...</p>                                         <!-- full mode only -->
</div>
```

The table body is drawn by JS: `tr` > `td.dl-sheet__no[data-s-rowsel=<index>]` then `td[data-r=<row index>][data-c=<column index>]` per column, with helper elements `data-s-spin`, `data-s-dec`, `data-s-cal`, `data-s-fill`, `data-s-case`. Extra attributes and classes (for example `class="dl-sheet--roomy"` for large text and 72vh height) go on the root.

`data-config` keys: `columns`, `rowKey`, `lookup`, `saveUrl`, `reorderUrl`, `reasonOnSave`, `autoSave`, `deleteUrl`, `resetUrl`, `links`, `csrf` (the CSRF token, only when `save-url` or `reorder-url` is set).

### Events (bubbling `CustomEvent` on the root)

| Event | `detail` | When |
|---|---|---|
| `dl:sheet-save` | `{rows, changed}`: copies of all rows after the save, and the rows that were sent | Successful save (server answer), auto save success, or Save in preview mode |
| `dl:sheet-change` | `{changes: [{r, k, v}]}`: row index, column key, new value | After each applied edit (typing, paste, fill, Delete, toggle, picker; also undo/redo) |

Other events the sheet listens to: `dl:stat` (from the stat boxes, lookup mode filter) and `dl:plan-sync` on `document` (from a Gantt).

### JS access

`el.dlSheet` (also `el.dlComponent`):

| Member | Meaning |
|---|---|
| `data` | live array of the row objects (read) |
| `render()` | redraw the table |
| `apply(list, noUndo)` | apply `[{r, k, v}]` cell changes with undo, `links` and events; returns the previous values |
| `cur` | active cell `{r, c}`; assigning moves the cursor |

### Server contracts

All requests are `POST` with `Content-Type: application/json`, `Accept: application/json`, `X-CSRF-TOKEN` (from `data-config.csrf`) and same-origin credentials. A response counts as failed when the HTTP status is not 2xx or the JSON has an `error` key; the toast shows `error`, else `message`, else "Could not save."

| Prop | Request body | Success answer | Failure answer |
|---|---|---|---|
| `save-url` | `{"changed": [ {full row object}, ... ], "reason": null \| "text"}` | `{"rows": [ ...the complete list as in the database, new rows with real ids... ]}` | `{"error": "text"}`; with `auto-save` also `{"reason_required": true, "error": "Why?"}` to ask for a reason and receive the same request again with `reason` |
| `delete-url` | `{"ids": [row-key values of saved rows], "reason": null \| "text"}` | `{"rows": [...]}` complete list after the delete | `{"error": "text"}`, or `{"reason_required": true, "error": "..."}` to ask for a reason |
| `reset-url` | first `{"dry": true}`, then `{}` | dry run: `{"message": "text for the confirmation"}`; real: any 2xx without `error` (the page reloads) | `{"error": "text"}` |
| `reorder-url` | `{"order": [row-key values in the new order], "reason": "text"}` | `{"rows": [...]}` in the new order, optional `"reload": true` to reload the page | `{"error": "text"}` |

Rows returned by the server replace the sheet data and become the new "loaded" state.

## Rules and pitfalls

- **Validate on the server.** `required`, `unique`, `links`, date and number checks in the browser are for comfort. The server decides, and answers `{error}` for what it refuses.
- **Return the complete row list.** After save, delete and reorder the answer replaces all data. A missing `rows` key in a 2xx answer breaks the sheet.
- **`row-key` must be in every row** and must be unique; new rows carry `new1`, `new2`...; treat any key starting with `new` as "insert".
- **Full-mode `Delete Row` is local.** It splices rows from the screen; `changes()` does not detect missing rows and `dl:sheet-save` only lists the remaining rows. For real deletion use `delete-url` in lookup mode or handle deletion yourself. A master row that other data refers to should be deactivated (a `check` column such as Active), not deleted: the project rule for lookup lists is "no Delete".
- **Use `lockSaved` on code columns** that other tables refer to; keep `unique` for codes.
- **Lookup-mode-only props** (`add-label`, `delete-url`, `delete-label`, `reset-url`, `reset-label`, `searchable`, `show-count`) are silently ignored in full mode. If you need them use `x-dl::lookup-grid` (or `lookup`).
- **A select with `optionsBy` still needs `options`** with every possible key => label.
- **Money cells show a fixed `IDR ` prefix**, whatever currency your data is in.
- **Pasting appends rows** even with `add-rows=false`.
- **Do not put the sheet in a form that submits it**: it has no `name` inputs; it saves through `save-url` or the JS event, not by form POST. Documents with item rows belong in `x-dl::lines`.
- No Devlib lint rule (`GridRules`, `FormRules`, `ViewLinter`) inspects the sheet. The general view rules apply to the screen that contains it: no inline `<script>` (L002), no raw HTML where a `x-dl::*` object exists (L001), no hex colours (L003). Put project JS behaviour in a DevLib JS component (`jsClass()` / `data-dl-js`), not inline.
- The tests do not cover the sheet directly (no feature test renders `x-dl::sheet`); its lookup variant is shown on the catalog pages Grid Lookup (`grid-lookup*` demos).

### Notes on inconsistencies found while writing this entry (not fixed)

- `sheet.js` render builds the row as `` `<tr class=""${isSaved ? '' : 'is-new'}"> `` (line 329). The result is `<tr class=""is-new">`, a malformed attribute, so `tr.is-new` (the CSS rule that tints new rows green) is never applied. Probably intended: `class="${... 'is-new'}"`.
- `Sheet.php` comment for `delete-url` says the button is danger and enabled only after a row number is clicked "with deleteUrl". In the Blade it is danger/disabled only when `delete-label` is also set; without it the button is `secondary` and enabled.
- The `reset-url` comment says the real request returns `{rows, message}`. The JS ignores `rows` and always reloads the page; `message` is used only for the dry run.
- The `save-url` comment says the body is `{changed}`; the real body is `{changed, reason}` (reason null when none).
- `csrf` is put in the config only when `save-url` or `reorder-url` is set. A sheet with only `delete-url` or `reset-url` sends an empty `X-CSRF-TOKEN` header (fails on a route with CSRF protection).
- `reason-on-save` is ignored by `auto-save`; and in manual save, a `reason_required` answer from the server is not handled (only auto save and delete handle it).
- The PHP column comment lists `text|number|money|select|date|time|datetime|check`; the JS also accepts `integer`, and the keys `optionsBy`, `min`, `max`, `step`, `emptyWarn`, `case`, `decimal` that are not in the type list.
- `Sheet::statCount()` (PHP, initial numbers) compares with strict `in_array`, the browser recount compares loosely as strings. A boolean `true` in `where` and `1` in the data give 0 at first render and the right number after the first edit.
- Hard-coded English in `sheet.js` that does not use `Lang`: dialog titles and texts "Change the sequence", "Move this row? ...", "Save changes", "Reason", "A reason is required.", "Delete", "RESET", the toast "Sequence changed", "Clear" / "Apply" in the pickers, the full-mode "Save All" and "Delete Row".
- `Sheet.php` header says "Dipakai oleh: x-dl::lookup-grid; tab Bill of Process dan Bill of Material". Nothing in the devlib repo renders `x-dl::sheet` directly (no demo page); the full mode is therefore not exercised by a demo here.

## Example

Full mode with preview save (no URL), a computed cell and a stat box. Adapted pattern of the Grid Lookup demos, with fictional data:

```blade
<x-dl::sheet title="Reorder Plan" row-key="id"
    :stats="[['label' => 'Rows', 'where' => []], ['label' => 'To buy', 'where' => ['status' => 'buy'], 'tone' => 'warn']]"
    :links="[['when' => ['required', 'released'], 'set' => 'purchase', 'a' => 'required', 'op' => '-', 'b' => 'released', 'min' => 0]]"
    :rows="[
        ['id' => 1, 'code' => 'RM-0100', 'name' => 'Art paper 150gr', 'required' => 1200, 'released' => 400, 'purchase' => 800, 'status' => 'buy', 'due' => '2026-11-03'],
        ['id' => 2, 'code' => 'RM-0200', 'name' => 'Offset ink black', 'required' => 90, 'released' => 90, 'purchase' => 0, 'status' => 'ok', 'due' => '2026-11-10'],
    ]"
    :columns="[
        ['key' => 'code', 'label' => 'Code', 'required' => true, 'unique' => true, 'lockSaved' => true, 'case' => 'uppercase', 'width' => '120px'],
        ['key' => 'name', 'label' => 'Item', 'required' => true],
        ['key' => 'required', 'label' => 'Required', 'type' => 'number', 'decimals' => 0, 'width' => '110px'],
        ['key' => 'released', 'label' => 'Released', 'type' => 'number', 'width' => '110px'],
        ['key' => 'purchase', 'label' => 'To purchase', 'type' => 'number', 'readonly' => true, 'width' => '110px'],
        ['key' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['buy' => 'Buy', 'ok' => 'OK'], 'width' => '110px'],
        ['key' => 'due', 'label' => 'Due', 'type' => 'date', 'width' => '130px'],
    ]" />
```

Server-backed lookup list with auto save and server delete (compact bar). The routes return the contracts above:

```blade
<x-dl::lookup-grid :rows="$shifts" row-key="id_shift"
    :save-url="route('master.shift.save')" :auto-save="true"
    :delete-url="route('master.shift.delete')" delete-label="Delete shift"
    :columns="[
        ['key' => 'code', 'label' => 'Code', 'required' => true, 'unique' => true, 'lockSaved' => true, 'case' => 'uppercase'],
        ['key' => 'starts', 'label' => 'Start', 'type' => 'time', 'required' => true],
        ['key' => 'active', 'label' => 'Active', 'type' => 'check'],
    ]" />
```

Controller sketch for `save-url`:

```php
public function save(Request $r)
{
    foreach ($r->input('changed', []) as $row) {
        // validate; insert when str_starts_with((string) $row['id_shift'], 'new'), else update
    }

    return response()->json(['rows' => Shift::orderBy('code')->get()->toArray()]);
}
```

## Related

- `x-dl::lookup-grid` (thin wrapper: `lookup=true`, the compact lookup mode)
- `x-dl::grid` (browse, search, filter and open records; not cell editing)
- `x-dl::lines` (item rows inside a document form, with totals and formulas)
- `x-dl::stat-box` (the boxes drawn from `stats`; filters in lookup mode)
- `x-dl::button` and `x-dl::icon-button` (toolbar buttons)
- `x-dl::gantt` (raises `dl:plan-sync` that moves `start`, `finish`, `machine` cells)
- `x-dl::field.text`, `x-dl::field.number`, `x-dl::field.decimal`, `x-dl::field.money`, `x-dl::field.date`, `x-dl::field.time`, `x-dl::field.date-time` (the cell editors copy their behaviour)
- `x-dl::table` (read-only table)
