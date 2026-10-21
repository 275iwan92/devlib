## Overview

`<x-dl::import>` is the standard "Import from CSV" object of DevLib, following the behaviour of the Filament `ImportAction`: three steps on one card. (1) Upload a CSV file (drag and drop or choose) and optionally download a template. (2) Match the file's columns to the fields the screen declares (matched automatically from the key, the label and the aliases). (3) Preview every row with a per-cell validation, then import the valid rows and download a CSV of the rows that failed.

The whole object runs in the browser. It reads the file, validates it against the column definitions and, on the final Import, raises the event `dl:import` with the valid, normalised rows. It does not post anything and does not know any database: the screen listens to the event and persists the rows (for example by a fetch to its own route, written in the project's DevLib JS component).

The class `Devlib\Components\Import` has no Blade view file (inline `render()`); the behaviour is the JS class `Import` in `resources/assets/js/components/import.js` (selector `[data-dl-import]`). Dictionary group: `form`.

### When to use it, and when NOT

- Use it for one-off or occasional bulk loading of master data or opening balances from Excel / CSV, where the user must see which rows are bad before anything is saved.
- Use `x-dl::sheet` / `x-dl::lookup-grid` when users should type or paste (Ctrl+V from Excel) into a grid; the sheet validates per cell and has no file step.
- Use the `Export` of `x-dl::grid` for the opposite direction (CSV / XLSX of the visible columns).
- Not for huge files: the file is read completely into the browser, rows are validated in memory and the preview shows only the first 200 rows. Not for Excel `.xlsx` files: save as CSV (UTF-8) first. Not for lookups with thousands of rows or server-side search (see the lookup column below).

## Props

Constructor of `Devlib\Components\Import` (4 props). Blade names are kebab-case; arrays use `:columns="[...]"`.

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `title` | `string` | `'Import Data'` | Heading of the card (translated). |
| `columns` | `array` | `[]` | The fields the import accepts, in the order of the preview table and the template. See "Column definition". Labels are translated. |
| `template` | `string` | `'template-import'` | Base file name of the downloads: the template is `<template>.csv` and the failed rows file is `<template>-failed.csv`. |
| `source` | `?string` | `null` | Source text for the developer catalog (`data-dl-src`); default `'file CSV'`. |

### Column definition

`config()` adds `type => 'text'`, `required => false`, `aliases => []` to every column.

| Key | Values and default | Meaning |
|---|---|---|
| `key` | string | Name of the field in the result row (`detail.rows[n][key]`). Also used for matching. |
| `label` | string | Shown in step 2 and 3 and as the template heading (translated, so the template heading follows the page language). Also used for matching. |
| `required` | `false` | An empty value is an error ("required") on that row, and step 2 refuses to continue while a required column is not matched to a file column. |
| `type` | `text` (default), `number`, `date`, `lookup` | Validation and normalisation, see below. Any other value is treated like `text`. |
| `lookup` | name of a `Devlib\Lookup` definition | For `type lookup`. The definition's rows are embedded in the page as `[key, label]` pairs. |
| `aliases` | list of strings | Other names the column may have in the file header (for example `['kode', 'item code']`). |
| `example` | string | Value written in the second line of the downloaded template. |

Types:

| `type` | A cell is valid when | Result value |
|---|---|---|
| `text` | always (trimmed) | the trimmed string |
| `number` | `numStr` can read it. It accepts `1.250,50`, `1,250.50`, `1250.5`, currency words/symbols (`Rp`, `IDR`, `$`...) and `%`. If only one kind of separator appears, the display language decides (a single `.` or `,` that is the language's decimal separator is a decimal; a pattern like `1.234.567` is thousands). | a JavaScript number |
| `date` | `DD/MM/YYYY` (also `-`, `.` or space as separator, 2-digit year = 20xx, month names like `10 okt 2026`), or `YYYY-MM-DD`; the date must exist. A missing year means the current year. | `YYYY-MM-DD` |
| `lookup` | the value equals (ignoring case, spaces and punctuation) the **key** or the **label** of a row of the lookup definition | the **key** of that row |

An empty cell is valid unless `required`; its result is `''` (also for an unmatched column).

## Behavior

### Steps

| Step | What the user sees | Rules |
|---|---|---|
| 1 Upload | A drop zone (accepts `.csv`, `.txt`, `.tsv`, `text/csv`; first row = headings; delimiter `,` `;` or tab is detected from the first line), the button "Download CSV template" and a hint to save Excel files as CSV (UTF-8). | The file is read as UTF-8 text. A file with only a heading row (fewer than 2 non-blank rows) shows the toast "The file is empty or has only column headings." |
| 2 Match columns | One row per declared column: label, `*` when required, the type name, a drop-down of the file headings (or "not imported") and a sample from the first data row. | Default selection: the first file heading whose normalised name equals the normalised `key`, `label` or an alias (normalised = lower case, only `a-z0-9`). Required columns left on "not imported" are marked and **Preview** shows a toast "Required columns are not matched yet: ..." and stays. |
| 3 Preview and import | Badges (total rows, N valid, N with problems), a checkbox "Show only rows with problems", a table with the original text of each mapped cell, the error text under bad cells and a status column. | Line numbers are the file line (heading = 1, first data row = 2). Only the first 200 rows (after the problem filter) are drawn; the counts and the import cover all rows. The Import button reads "Import N valid rows" and is disabled when there are none; "Download failed rows" is disabled when there are none. |

Buttons: "Change file" returns to step 1, "Match again" returns to step 2. Matching again after changing a drop-down redraws the sample.

### Import

Import asks "Import N valid rows?" (or "Import N valid rows (M rows with problems are skipped)?"). On Yes it raises `dl:import`, shows a success toast ("N rows imported." / "N rows imported, M skipped.") and returns to step 1 with the file input cleared. If the screen's handler fails, the object cannot know: report failures from your own code.

### Downloads

Both are CSV files with a UTF-8 BOM, delimiter `;` (Excel opens them in the local layout), CRLF lines and quoting of cells that contain `"`, `;`, `,` or tab.

- Template: line 1 = the column labels, line 2 = the `example` values.
- Failed rows: line 1 = the original file headings plus a final heading "Reason"; each failed row is written as in the file plus `Label: error; Label: error`.

### Translation (Lang) and access (`can`)

`title`, column labels, the step names (except the first), hints, errors, toasts and dialogs go through `Lang`. Some button texts and badge words are fixed English (see the notes). `can="module.action"` on the tag hides the whole object when the user lacks the ability.

## Data and events

### Root and hook attributes

```html
<div class="dl-import" data-dl="x-dl::import" data-dl-import data-config='{"columns":[...],"template":"template-item"}'>
  <div class="dl-import__head"><b>Title</b><ol class="dl-import__steps" data-i-steps><li class="is-active|is-done">..</li>x3</ol></div>
  <div data-i-step="1"> <label class="dl-import__drop [is-over]" data-i-drop> <input type="file" data-i-file hidden> </label> <button data-i-template> </div>
  <div data-i-step="2" hidden> <p data-i-fileinfo/> <div class="dl-import__map" data-i-map>  label.dl-import__mrow[.is-bad] select[data-i-col=<key>] </div>
       <button data-i-back="1"> <button data-i-preview> </div>
  <div data-i-step="3" hidden> <div data-i-stats/> <input type="checkbox" data-i-onlybad> <table class="dl-import__table" data-i-table/>
       <button data-i-back="2"> <button data-i-failed> <button data-i-go> </div>
</div>
```

In the preview table, bad rows get `is-bad` and bad cells `is-err` (with the message in `title` and a `<small>`). Extra attributes and classes go on the root.

`data-config`: `columns` (each with `type`, `required`, `aliases`, and for lookup columns `lookup: {key, label, rows: [[key, label], ...]}`) and `template`.

### Event

| Event | `detail` | When |
|---|---|---|
| `dl:import` | `{rows: [ {key: value, ...} ], failed: <number of skipped rows>}` | The user confirmed Import. `rows` are the valid rows, one object per row, keys = column `key`, values normalised as in the type table. Bubbles, not cancelable. |

### JS access

`el.dlImport.load(text)` loads CSV text as if a file named `data.csv` was chosen (for demos and tests). The component object is also `el.dlComponent`.

## Rules and pitfalls

- **Listen to `dl:import` in a DevLib JS component** (not an inline `<script>`: lint rule L002). Do the real insert on the server in one transaction and validate again: the browser check is for comfort, the file is user-controlled data.
- **Required columns** guard the mapping step only if you mark them `required`; give every column the aliases the real files use (Indonesian and English headings) because auto matching uses `key`, `label` and aliases.
- **Dates in files** are read day-first (`01/09/2026` = 1 September). Prefer `YYYY-MM-DD` in templates for unambiguous exchange.
- **Numbers** such as `1.250` depend on the display language (Indonesian: 1250; English: 1.25). Use an explicit decimal in the template example or tell users to format cells as plain numbers.
- **Lookup columns** embed every row of the lookup in the page. Use it for short masters (units, categories). A lookup defined with server-side `search` has no embedded rows, so every non-empty value would be rejected as "not in the master".
- **Large files**: all rows are validated in memory and only 200 rows are drawn; very large files still lock the tab while parsing.
- **No lint rule** (`GridRules`, `FormRules`, `ViewLinter`) inspects `x-dl::import`, and no feature test renders it. The catalog page `p5-import` shows it (demo `import-items`).
- Export for the reverse direction lives on the grid toolbar, not here.

### Notes on inconsistencies found while writing this entry (not fixed)

- The error text for an invalid date (`i_err_date`) says "DD/MM/YYYY or YYYY-MM-DD", but `DlDate.of` accepts more (month names, `-` `.` and spaces, 2-digit years, no year).
- Fixed English that does not use `Lang`: the step name "Upload", the buttons "Download CSV template", "Change file", "Preview", "Match again", "Import", the badge word "valid", the status words "OK" / "error" and the table heading "Status".
- A `lookup` column returns the lookup **key**; the label typed in the file is not returned, and the `fill` and `columns` parts of the lookup definition are not used. `import.js` also has no REMARK header, unlike other component scripts.

## Example

Adapted from the demo `import-items`, fictional master data:

```blade
<x-dl::import title="Import Raw Material Items" template="template-item"
    :columns="[
        ['key' => 'code', 'label' => 'Item Code', 'required' => true, 'aliases' => ['kode', 'item code'], 'example' => 'RM-KRT-0100'],
        ['key' => 'name', 'label' => 'Item Name', 'required' => true, 'aliases' => ['nama', 'item name'], 'example' => 'Art Paper 150gr 65x100'],
        ['key' => 'uom', 'label' => 'Unit', 'type' => 'lookup', 'lookup' => 'uom', 'required' => true, 'aliases' => ['satuan', 'unit'], 'example' => 'LBR'],
        ['key' => 'price', 'label' => 'Price', 'type' => 'number', 'aliases' => ['harga', 'price'], 'example' => '1250.50'],
        ['key' => 'min', 'label' => 'Minimum Stock', 'type' => 'number', 'aliases' => ['min', 'minimum'], 'example' => '500'],
        ['key' => 'since', 'label' => 'Active Since', 'type' => 'date', 'aliases' => ['tanggal'], 'example' => '2026-09-01'],
    ]" />
```

The lookup `uom` must exist (`Devlib\Lookup::define('uom', [...])` or `config('devlib.lookups.uom')`). Handling the result in the project's DevLib JS component (not inline):

```js
document.addEventListener('dl:import', e => {
  fetch('/items/import', {
    method: 'POST', credentials: 'same-origin',
    headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
    body: JSON.stringify({rows: e.detail.rows}),
  });
});
```

## Related

- `x-dl::grid` (its Export button is the opposite direction; reload the grid after an import)
- `x-dl::sheet` and `x-dl::lookup-grid` (type or paste rows in cells instead of uploading a file)
- `x-dl::field.upload` (file upload field in a form; the import reads its file in the browser and uploads nothing)
- `x-dl::field.lookup` (the same `Devlib\Lookup` definitions as a field)
- `x-dl::modal` (host the import card in a pop-up)
- `x-dl::button` (the step buttons)
