## Overview

`<x-dl::lookup-grid>` is the Grid Lookup (Grid Pre-Entry, lookup branch) of DevLib: a small, dense, spreadsheet-like list that the user fills **directly in the cells**, with no form and no separate record page. It is meant for helper lists that rarely change and that other screens pick from: Religion, Blood Type, Supplier Category, Work Shifts, Public Holidays, unit conversion factors. Toolbar: Search, a row counter, `Row` (add), optional `Delete row` and `Reset`, `Undo` and `Save` (or none of the last two with `auto-save`).

It is the class `Devlib\Components\LookupGrid`, a **thin subclass of `x-dl::sheet`** that forces `lookup = true`. All cell behaviour (typing, keyboard, paste from Excel, date/time/number pickers, validation, server save) lives in `Sheet.php` and `sheet.js`; this entry documents what the lookup grid exposes and how it differs from the plain sheet.

Use it when:

- the list is small (tens to a few hundred rows) and every row is a flat set of simple cells (text, number, money, select, date, time, check);
- "a row" is not a document: no status flow, no detail lines, no approval.

Pick another object when:

- the row is a master record with many fields, a form page and Active/Inactive: `x-dl::grid kind="master"` + `x-dl::form` (Master Form).
- the data is a document (Draft/Confirmed/Cancelled): `x-dl::grid kind="transactional"` + `x-dl::form`.
- you need a free spreadsheet with Undo/Redo, Delete Row, a help line and no lookup restrictions: `x-dl::sheet` (not lookup mode).
- the user only picks one value in a form field from a list: `x-dl::field.lookup` or `x-dl::field.select` (this object is for maintaining the list itself; do not confuse it with the field).
- the data is read-only: `x-dl::grid` or `x-dl::table`.

### What differs from `x-dl::sheet`

| Aspect | `x-dl::sheet` | `x-dl::lookup-grid` |
|---|---|---|
| Root class | `dl-sheet` | `dl-sheet dl-sheet--lookup` (denser rows: 26px cells) |
| Toolbar | title, `Row`, `Delete Row` (client only), Undo, Redo, row info, `Save All (n)` | Search box, row info (`N rows` / `N unsaved changes`), `Row`, optional `Delete row`, optional `Reset`, `Undo` (discard all changes), `Save` |
| Title | printed (`title`) | **not printed** (the `title` prop is accepted but the lookup toolbar has no title) |
| Help line under the table | yes | no |
| Delete | client-side `Delete Row` | none unless `delete-url`: rows already used are deactivated with a check column, not deleted |
| Check cell | a tick | a switch (`dl-sheet__sw`) |
| Row filter | none | Search box and `stats` boxes hide non-matching rows |

## Props

Constructor of `Devlib\Components\LookupGrid` (19 props; the same as `x-dl::sheet` without `lookup`, which is always `true`). Use `:prop="expr"` for arrays and booleans.

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `columns` | `array` | `[]` | List of column definitions (see "Column definition"). Labels are translated. |
| `rows` | `array` | `[]` | List of associative arrays, one per row, keyed by the column `key`s plus the row key. Rows are JSON-encoded in `data-rows`. A row whose key is in `rows` at load is a **saved** row; rows added by the user have a temporary key `new1`, `new2`, ... |
| `row-key` | `string` | `'id'` | Name of the unique key of a row. Used for "saved or new", `lockSaved`, reorder, delete and as the identity in the posted rows. |
| `title` | `string` | `''` | Accepted and translated, but **not shown** in lookup mode (the heading belongs to the screen/card around the grid). |
| `source` | `?string` | `null` | Free text naming where the data comes from (`data-dl-src`); default `tabel lookup (daftar bantu)`. No runtime effect. |
| `save-url` | `?string` | `null` | URL that receives Save. `POST` JSON `{changed: [rows], reason}` with header `X-CSRF-TOKEN`. Answer `{rows: [...]}` (the full current list from the database; new rows get their real id) for success, or HTTP error / `{error: 'text'}` for failure (the changes stay on screen). Without `save-url` the grid is a **preview**: Save only fires `dl:sheet-save` and marks the rows as saved in the browser. |
| `stats` | `array` | `[]` | Filter boxes above the toolbar: `[['label' => 'Active', 'where' => ['active' => true], 'tone' => 'success']]`. `where` is `column => value` or `column => [values]` (all conditions must match); `tone` defaults to `accent`. The number is computed from the rows and updated after edits; a click shows only the matching rows (click again to release). A box without `where` counts and shows all rows. |
| `add-rows` | `bool` | `true` | `false` removes the `Row` button (rows come from elsewhere). Pasting beyond the last row can still add rows. |
| `reorder-url` | `?string` | `null` | Rows can be dragged onto another row to change the order. Asks for a required reason, then `POST {order: [ids], reason}`; answer `{rows}` (new order), optionally `{reload: true}`; `{error}` on failure. |
| `reason-on-save` | `?string` | `null` | Text of a prompt: Save first asks one required reason with this message, and posts it as `reason`. Used only with `save-url`; cancel = nothing is saved. Not translated by the dictionary (write it in the screen language or translate before passing). |
| `links` | `array` | `[]` | Computed cells. Each entry `['when' => [keys], 'set' => key, 'a' => key, 'op' => '-' or '+', 'b' => key, 'min' => 0]`: when one of the `when` cells is edited, `set` = `a op b` (4 decimals), not lower than `min`; the server must recompute it. Entries with another `op` are treated as `-`. |
| `auto-save` | `bool` | `false` | With `save-url`: each edited cell is saved at once; `Undo` and `Save` are not drawn and the counter does not show unsaved changes. A new row is posted only when all required cells are filled. The server may answer `{reason_required: true, error: 'question'}` to ask a reason; refusal or a cancelled reason puts the cell back. Ignored without `save-url`. |
| `add-label` | `?string` | `null` | Text of the add button (default `Row`). Translated. |
| `delete-url` | `?string` | `null` | Draws a `Delete row` button. The selected saved rows are posted `{ids, reason}`; answer `{rows}`; `{reason_required: true, error}` asks for a reason and resends; other errors are shown. Unsaved new rows just disappear. Prefer an `active` check column for lookup data; use this only when a row may really be removed. |
| `searchable` | `bool` | `true` | `false` hides the Search box (small lists always read whole). |
| `show-count` | `bool` | `true` | `false` hides the `N rows` / `N unsaved changes` text. |
| `delete-label` | `?string` | `null` | Text of the delete button (default `Delete row`). When set the button is the `danger` variant and **disabled until a row number is clicked** (a whole row chosen); when empty it is a secondary button that is always enabled. Only with `delete-url`. |
| `reset-url` | `?string` | `null` | Draws a `Reset` button. `POST {dry: true}` must return `{message}` shown in a confirm dialog; on OK `POST {}` returns `{message}` and the page is reloaded (the server keeps the message as flash). Meant for engine-made rows (restore the generated list). |
| `reset-label` | `?string` | `null` | Text of the reset button (default `Reset`). Translated. |

The class passes every prop to `Sheet::__construct` and fixes `lookup: true`; `lookup` cannot be set from the tag.

### Column definition

```php
:columns="[
    ['key' => 'code',   'label' => 'Code',   'required' => true, 'unique' => true, 'case' => 'uppercase', 'width' => '110px'],
    ['key' => 'name',   'label' => 'Name',   'required' => true, 'unique' => true, 'case' => 'capitalize'],
    ['key' => 'rate',   'label' => 'Rate',   'type' => 'number', 'decimals' => 2, 'width' => '140px'],
    ['key' => 'kind',   'label' => 'Kind',   'type' => 'select', 'options' => ['A' => 'Type A', 'B' => 'Type B']],
    ['key' => 'active', 'label' => 'Active', 'type' => 'check', 'width' => '70px'],
]"
```

`Sheet::config()` adds the defaults `type = 'text'`, `required = false`, `readonly = false` and turns a list `options` (`['A', 'B']`) into `['A' => 'A', 'B' => 'B']`.

| Key | Values | Effect |
|---|---|---|
| `key` | string | Row key of the cell. |
| `label` | string | Header text; translated. A red `*` follows it when `required`. |
| `type` | `text` (default), `number`, `integer`, `money`, `select`, `date`, `time`, `datetime`, `check` | See below. |
| `width` | CSS size | Header width, for example `'110px'`. |
| `required` | bool | Empty cell is invalid (red) and blocks Save; also blocks an auto-save of a new row. |
| `unique` | bool | The value (trimmed, case-insensitive) may not repeat in another row; the second one is red and blocks Save. The server must still check. |
| `readonly` | bool | The cell is grey (`is-ro`) and cannot be edited, pasted over, cleared or filled. |
| `lockSaved` | bool | Locks the cell on rows that already exist (saved key): the code is referenced elsewhere. New rows can still fill it. Disabling the row (check column) is the way to retire a code. |
| `lockFilled` | bool | With `lockSaved`: a saved cell is locked only if it already has a value; an empty one can still be chosen. |
| `emptyText` | string | For `select`: text shown when the value is empty (for example `Process not set`). |
| `emptyWarn` | bool | Empty cells get a warning background (`is-empty`), for example translations not filled yet. |
| `case` | `uppercase`, `capitalize` | Initial case mode of a **text** column (`AA` / `Aa`). The user cycles `aa / Aa / AA` with the badge shown in the edited cell; the choice is remembered per column in the open page only. |
| `options` | map `value => label` or list | For `select`: choices. A value not in `options` is invalid. |
| `optionsBy` | `['key' => column, 'map' => [value => [choices]]]` | For `select`: the choices depend on another cell of the row (for example machines of a process). With `auto-save`, such a cell must be chosen once the other cell offers choices. |
| `decimals` | int | For `number`/`money`: always show n digits (`18.450,00`); the cell has the `,00` button (0 / 2 / 4 / calculator). `decimal => true` = free decimals. Without either, `number` is an integer with a spinner. |
| `min`, `max`, `step` | numbers | Used by the integer spinner. |

Cell types: `text`; `number` / `integer` (integer = spinner; with `decimals` or `decimal => true` = decimal with the ,00 button); `money` (shown `IDR 1.250.000`); `select` (drop-down; typing a label also matches); `date` (shown `dd-Mmm-yyyy`, typed as `10-10-26`, `10 okt`, `2026-10-10`, stored `YYYY-MM-DD`, calendar button); `time` (typed `8`, `830`, `8.30`, stored `HH:MM`, picker); `datetime` (stored `YYYY-MM-DD HH:MM`); `check` (switch; stored `true`/`false`; Space/Enter/F2 toggles).

## Behavior

### Markup produced

```html
<div class="dl-sheet dl-sheet--lookup" data-dl="x-dl::lookup-grid" data-dl-class="LookupGrid ← Sheet ← DevlibComponent"
     data-dl-src="tabel lookup (daftar bantu)" data-dl-sheet data-config='{...}' data-rows='[...]'>
  <div class="dl-grid__stats">...stat boxes...</div>                    <!-- only with stats -->
  <div class="dl-sheet__bar">
    <div class="dl-grid__search"><input type="search" data-s-search></div> <!-- searchable -->
    <span class="dl-sheet__info" data-s-info></span>                       <!-- show-count -->
    buttons: data-s-add | data-s-del-srv | data-s-reset | data-s-revert (Undo) | data-s-save (Save)
  </div>
  <div class="dl-sheet__wrap" tabindex="0" data-s-wrap aria-label="Worksheet"><table class="dl-sheet__table" data-s-table></table></div>
</div>
```

The table is drawn by script. A first column `#` holds the row number (`data-s-rowsel`); cells are `td[data-r][data-c]` with state classes: `is-cur` (active), `is-sel` (range), `is-dirty` (changed: amber corner, amber background), `is-bad` (invalid, red), `is-ro` (locked/read-only), `is-empty` (`emptyWarn`), `is-pick` / `is-dec` (cells with a button). `data-config` keys: `columns`, `rowKey`, `lookup` (true), `saveUrl`, `reorderUrl`, `reasonOnSave`, `autoSave` (only true when `save-url` is set), `deleteUrl`, `resetUrl`, `links`, `csrf` (the token, present only when `save-url` or `reorder-url` is set).

### Editing and keyboard

Click a cell to select it; arrow keys / `Tab` / `Shift+Tab` move; just typing starts an edit that replaces the content; `Enter` / `F2` / double-click edit with the cursor at the end; `Esc` cancels; `Enter` commits and moves down; `Tab` commits and moves right. `Shift`+click or drag selects a range; `Delete` / `Backspace` clears the range (not read-only cells); `Ctrl+C` / `Ctrl+V` copy and paste tab-separated text with Excel (paste adds rows when needed and parses each cell by its type); the small handle at the corner of the selection fills down (copies, or continues a number sequence); `Ctrl+Z` / `Ctrl+Y` undo and redo cell edits; `Alt+Down` opens the date/time/number picker of the cell. Clicking the row number selects the whole row (needed before `Delete row` with `delete-label`).

The toolbar `Undo` is different: it asks "discard all unsaved changes?" and restores the saved rows, dropping new rows (the button is disabled when nothing changed).

### Search, stats and the counter

- The Search box filters rows live: a row stays if any column's **displayed text** (select label, formatted date/number) contains the text (case-insensitive). Hidden rows keep their position and still count in `N rows`.
- Stat boxes are `x-dl::stat-box` with `filter`; a click sets the `where` filter, another click clears it. They combine with Search.
- The counter shows `N rows`, or `N unsaved changes` (amber) while there are changes and `auto-save` is off.

### Validation and Save

Before saving, every cell is checked: required, unique, number really a number, date/time/datetime format, select value in `options`. Any invalid cell: toast `N cells are not valid yet (...)`, the first bad cell is selected, nothing is sent. Save is disabled while nothing changed. With `save-url` the server is the authority: it validates again, writes, and returns the rows; `rows` replace the grid and clear the undo stacks. Without `save-url` the grid is a preview.

### Language

Translated through the dictionary: column `label`s, `stats` labels, `add-label`, the fixed words (`Search...`, `Row`, `Delete row`, `Reset`) and the run-time messages (`N rows`, `N unsaved changes`, `N rows saved.`, `Could not save.`, `No changes.`, `N cells are not valid yet`, `Worksheet`). `Undo`, `Save` (buttons) are always English. Not translated: `options` labels, cell values, `emptyText`, and the prompts of `reason-on-save` (passed as written) and of delete/reset (fixed English dialog titles; the button texts `delete-label` and `reset-label` are translated in the view). Dates and numbers follow the application language and locale (`dd-Mmm-yyyy`, `1.234,56` / `1,234.56`). The dictionary group is `grid`.

### Access (`can`)

`can="module.action"` on the tag: without the ability nothing is rendered. It does not make cells read-only; for a read-only list use `x-dl::grid`. The `save-url`, `delete-url`, `reset-url` and `reorder-url` routes must authorise the same ability.

## Data and events

### What the server receives

| Action | Request | Expected answer |
|---|---|---|
| Save / auto-save | `POST save-url`, JSON `{changed: [row objects with ALL the row's keys, new rows have key "newN"], reason: string\|null}` | `{rows: [...]}` on success; `{error}` or a non-2xx status on failure; `{reason_required: true, error}` (auto-save only) to ask for a reason |
| Reorder | `POST reorder-url` `{order: [ids in the new order], reason}` | `{rows}` (+ optional `reload`) or `{error}` |
| Delete row | `POST delete-url` `{ids, reason}` | `{rows}` or `{reason_required: true, error}` or `{error}` |
| Reset | `POST reset-url` `{dry: true}` then `{}` | `{message}` for the dry run (confirmation text); `{message}` after reset; `{error}` |

All requests use `credentials: same-origin`, `Accept: application/json`, `Content-Type: application/json` and the header `X-CSRF-TOKEN` taken from `config.csrf`.

### Events (bubbling, not cancelable; fired on the grid root, `dl:stat` by the stat box inside it)

| Event | `detail` | When |
|---|---|---|
| `dl:sheet-change` | `{changes: [{r, k, v}]}` (row index, column key, new value) | after every cell edit, paste, fill, clear, undo and redo |
| `dl:sheet-save` | `{rows: [all rows], changed: [saved rows]}` | after a successful server save/auto-save, or immediately in preview mode (no `save-url`) |
| `dl:stat` | `{active}` (from the stat box) | a stat box was clicked |

```js
grid.addEventListener('dl:sheet-save', e => refreshDropdowns(e.detail.rows));
```

(Use a DevLib JS component or the project script, not an inline script: lint L002.)

### JS access

`el.dlSheet = { data, render, apply, cur }` on the root: `data` is the live array of row objects, `render()` redraws, `apply([{r, k, v}], noUndo)` sets cells (fires `dl:sheet-change`, handles `links` and auto-save), `cur` gets/sets the active cell `{r, c}`. The instance is `el.dlComponent` (class `Sheet`).

## Rules and pitfalls

- Conventions from the source comments: write labels in English (the dictionary translates them); mark code columns `required`, `unique` and `lockSaved` when other tables reference them; retire a used row with an `active` check column instead of deleting it (so normally no `delete-url`).
- Always give a real `save-url` in an application. Without it Save only simulates (preview) and nothing is stored.
- The server must repeat all validation (required, unique, types, `links` formulas) and authorise per row. The browser checks are for convenience.
- The posted `changed` contains the whole row (all keys), including new rows with a temporary key `newN`; ignore that key and insert. Return the full list in `rows`.
- `row-key` must exist in every row and be unique; it decides which rows are "saved" (locked by `lockSaved`) and which are new.
- `title` is not displayed; put the heading in the surrounding card or page.
- `delete-url` and `reset-url` use the CSRF token from the config, which is only emitted when `save-url` or `reorder-url` is set; always combine them with `save-url`.
- `stats` counts: PHP counts with strict comparison, the script recounts after load with loose string comparison; use the same type in `rows` and `where` (for example `true`, not `'1'`).
- Not for large data or paging: the whole list is in the page and every redraw renders all rows.
- Lint: no rule of its own; L001 (no raw HTML tags), L002, L003 apply to the screen. `x-dl::pagination` is not used with this object (it has no pager).
- Catalog page `p5-grid-lookup` (menu Grid and Form > Grid Pre-Entry > Grid Lookup) is in `Catalog::APPROVED`; do not change its look/behavior without Jo's approval. `GridFormFlowTest` checks the menu entry only; there is no unit test of the sheet script.

### Notes on inconsistencies found while writing this entry (not fixed)

- The row template in `sheet.js` has a stray quote: `<tr class=""${...}">`, so the `is-new` class of new rows is never applied (the green gradient CSS `tr.is-new` does not match).
- The `title` prop of the lookup grid is translated and passed along but never drawn.
- `config().csrf` is emitted only for `save-url` / `reorder-url`; `delete-url` and `reset-url` alone would send an empty `X-CSRF-TOKEN`.
- The skill doc `pagination.md` says `x-dl::grid` / `x-dl::lookup-grid` "drive their own pager"; the lookup grid (sheet) has no pager.
- `reason-on-save`, `delete-label` and `reset-label` are not in `TEXT_PROPS`; the buttons are translated in the view but the dialog titles/messages (`Save changes`, `Reason`, `RESET`, `Delete`) are fixed English strings in the script.
- The catalog demos for this object use Indonesian labels (`Kode`, `Nama`, `Aktif`, `Tahun Baru Masehi`) although screens are meant to be written in English.
- `Sheet.php` comments say a lookup grid has no `Delete`; the `delete-url` / `delete-label` props do add a server-side `Delete row` button in lookup mode.

## Example

Adapted from the catalog demo `site/resources/views/demos/grid-lookup.blade.php`, with English labels and a server save:

```blade
<x-dl::lookup-grid
    :rows="[
        ['id' => 1, 'code' => 'ISL', 'name' => 'Option One',   'sort' => 1, 'active' => true],
        ['id' => 2, 'code' => 'KRS', 'name' => 'Option Two',   'sort' => 2, 'active' => true],
        ['id' => 3, 'code' => 'KAT', 'name' => 'Option Three', 'sort' => 3, 'active' => false],
    ]"
    :columns="[
        ['key' => 'code',   'label' => 'Code',   'required' => true, 'unique' => true, 'lockSaved' => true, 'case' => 'uppercase', 'width' => '110px'],
        ['key' => 'name',   'label' => 'Name',   'required' => true, 'unique' => true, 'case' => 'capitalize'],
        ['key' => 'sort',   'label' => 'Order',  'type' => 'number', 'width' => '90px'],
        ['key' => 'active', 'label' => 'Active', 'type' => 'check', 'width' => '70px'],
    ]"
    :stats="[['label' => 'Active', 'where' => ['active' => true], 'tone' => 'success'], ['label' => 'Inactive', 'where' => ['active' => false], 'tone' => 'danger']]"
    :save-url="route('lookups.religion.save')"
    add-label="Row" />
```

The route answers the saved list:

```php
// POST {changed: [...], reason}
return response()->json(['rows' => $rowsFromDatabase]);   // or response()->json(['error' => 'Code OPT already exists.'], 422)
```

## Related

- `x-dl::sheet` (the parent: free spreadsheet, not in lookup mode; also used by the Bill of Process/Material tabs)
- `x-dl::grid` (read-only lists, master and transactional grids that open forms)
- `x-dl::form` (Master Form for records that need a page)
- `x-dl::field.lookup` (form field that picks from a list like this)
- `x-dl::field.select` (simple drop-down)
- `x-dl::stat-box` (the filter boxes of `stats`)
- `x-dl::button` (toolbar buttons)
- `x-dl::table` (read-only static table)
