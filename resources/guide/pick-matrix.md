## Overview

`<x-dl::pick-matrix>` is a "pick the winner per line" matrix. Rows are requirements (for example the lines of a purchase request), columns are bidders (for example suppliers), and every cell holds one radio button with the offer of that bidder for that row (price, text, sub-line). The user chooses one cell per row, or "Skip" to pick none. A row can start with a default winner (for example the cheapest offer), which is pre-selected and tinted. Below the table a summary counts the choices per column and updates live: `2 PO: Supplier A (2 rows), Supplier B (1 rows)`, so the officer sees how many documents the choices will create.

The object is a set of plain radio inputs plus a summary script. It makes no request and fires no event: the surrounding `<form>` posts `name[rowKey] = value of the chosen cell`.

Use it to award each line of a comparison to exactly one bidder. Do NOT use it to compare offers without choosing (use `x-dl::grid` or `x-dl::table`), to choose several options per row (use checkboxes in a `x-dl::grid`), or to pick one value for a whole form (use `x-dl::field.radio`).

## Props

Constructor of `Devlib\Components\PickMatrix` (6 props). Blade names are kebab-case; arrays go through `:prop="expr"`.

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `name` | `string` | `'pick'` | Base name of the radios. Each row posts as `name[rowKey]` (for example `pick[1]`). The `name` is escaped, not translated. |
| `columns` | `array` | `[]` | The bidders: `[colKey => ['label' => string, 'sub' => ?string]]`. `colKey` (int or string) ties the column to the cells and to `default` in the rows. `label` is the column heading and the name used in the summary; `sub` is a small second line in the heading. Empty = the empty message instead of a table. |
| `rows` | `array` | `[]` | The requirements, as a list. Each item: `['key', 'label', 'sub', 'locked', 'default', 'cells']`. See "The rows array". Empty = the empty message. |
| `empty` | `string` | `'There are no quotations to compare yet.'` | Text shown (in a dashed box, class `dl-pick__empty`) when `rows` or `columns` is empty. Translated through the dictionary. |
| `unit` | `string` | `'PO'` | The word in the summary that names what the choices create: `N <unit>: ...`. Written to `data-unit`. Not translated. |
| `source` | `?string` | `null` | Free text naming where the data comes from, for the developer catalog (`data-dl-src`). `null` = `'baris x penawar'`. No visual or runtime effect. |

### The columns array

```php
[11 => ['label' => 'Supplier A', 'sub' => 'Net 30'], 12 => ['label' => 'Supplier B', 'sub' => 'Net 30']]
```

- The array key is the column key (`colKey`), used in `rows[].default`, `rows[].cells` and the summary. It is written as `data-col` on each radio.
- `label` is shown in the header and in the summary; if it is missing the column heading is empty. `sub` is optional.
- The column order is the order of the array.

### The rows array

```php
[
    'key'     => 2,                  // required: row key; posts as name[2]
    'label'   => '#2 Duplex 310',    // required: main text of the row (bold)
    'sub'     => '25 RIM',           // optional small line under the label
    'default' => 12,                 // optional colKey pre-selected and tinted
    'locked'  => 'Already on a PO',  // optional: any non-empty text locks the row
    'cells'   => [11 => [...], 12 => [...]],
]
```

| Key | Valid values | Notes |
|---|---|---|
| `key` | int or string | Required. Becomes the index in the posted name, so keep it unique. |
| `label` | string | Required (reading a row without `label` raises an "undefined array key" error). |
| `sub` | string or absent | Small line under the label. |
| `default` | a `colKey` | Compared with `===` to the column key, so the type must match (`12` and `'12'` are different). The default cell is tinted (`is-best`) and pre-selected when it exists, is not `disabled` and the row is not `locked`. Absent, unknown or not offered = "Skip" is pre-selected. |
| `locked` | non-empty string | The reason shown in green under the row label. The whole row is greyed (`is-locked`), every radio, including "Skip", is disabled, nothing is pre-selected except "Skip" (checked but disabled), and (because disabled inputs are not submitted) the row is not posted at all. |
| `cells` | `[colKey => cell]` | One entry per bidder that quoted. A column without a cell shows a dash. Cells whose key is not in `columns` are ignored. |

Cell: `['value' => scalar, 'text' => string, 'sub' => ?string, 'disabled' => ?string]`.

| Key | Meaning |
|---|---|
| `value` | Required. The posted value when this cell is chosen (for example the id of the quotation line). |
| `text` | Required. The visible text next to the radio (for example the price). |
| `sub` | Small line under the cell (for example lead time). |
| `disabled` | A non-empty reason makes this single radio disabled, adds the reason as a tooltip (`title`) and as an amber warning line under the cell. The row stays selectable through its other cells. |

## Behavior

### Markup produced

```html
<div class="dl-pick" data-dl="x-dl::pick-matrix" data-dl-class="PickMatrix" data-dl-src="baris x penawar"
     data-dl-pick data-unit="PO" data-cols='{"11":"Supplier A","12":"Supplier B"}'>
  <div class="dl-table-wrap"><table class="dl-table dl-pick__table">
    <thead><tr><th>Requirement</th><th class="num">Supplier A<small>Net 30</small></th> ... <th class="num">Skip</th></tr></thead>
    <tbody>
      <tr class="is-locked?"><td><b>label</b><small>sub</small><small class="dl-pick__lock">locked</small></td>
        <td class="num is-best?"><label class="dl-pick__cell" title="disabled reason?">
              <input type="radio" name="pick[1]" value="101" data-col="11" checked? disabled?"><span>text</span></label>
            <small>sub</small><small class="dl-pick__warn">disabled reason</small></td>
        ... <td class="num"><span class="dl-pick__none">—</span></td> (no offer)
        <td class="num"><input type="radio" name="pick[1]" value="" data-col=""></td></tr>
    </tbody></table></div>
  <div class="dl-pick__sum" data-dl-pick-sum>2 PO: Supplier A (2 rows), Supplier B (1 rows)</div>
</div>
```

Without rows or columns the root contains only `<div class="dl-pick__empty">`.

### Selection rules

- One radio group per row (`name[rowKey]`), so one choice per row. The last column is always "Skip" (value `''`, `data-col=""`).
- Initial state: a row is pre-selected on its `default` cell when the row is not locked, that cell exists and is not disabled; otherwise "Skip" is checked. A locked row has "Skip" checked but disabled.
- The tinted `is-best` cell follows `default` only (it ignores `disabled` and `locked`).
- Cells without an offer show an em dash and have no radio.

### Summary line

The script (`PickMatrix`, selector `[data-dl-pick]`) listens to `change` on the root and recomputes the summary from all checked radios that have a non-empty `data-col`: `<b>{number of columns chosen} {unit}</b>: {label} ({n} rows), ...`. The number is the count of distinct columns, i.e. documents to create; the label comes from `data-cols`, falling back to the column key. When nothing is chosen the text is "No line is picked yet.". Counting rules: Skip rows and locked rows are not counted.

### Language

Headings "Requirement" and "Skip" use `Lang::text`; `empty` is a text prop and is translated; the summary uses the dictionary keys `pm_none_picked` and `g_n_rows` ("{n} rows" / "{n} baris"). The object belongs to the `grid` dictionary group. `columns[].label` is part of the translated list `columns` (see pitfalls); row labels, cell texts, `sub`, `locked` and `disabled` reasons are shown as given, so write them already in the right language.

### Accessibility

Native radios inside `label`s, grouped by name; the row label is not a programmatic group label (no `fieldset`). Disabled reasons are also given as `title`. The "Skip" radio has no visible label other than the column heading.

### Access

Only the generic `can="module.action"` attribute (without the ability nothing is rendered).

## Data and events

- Output: put the object inside a `<form>`. A submitted request contains `pick[rowKey]` = the `value` of the chosen cell, or `''` for Skip. Locked rows and rows where everything is disabled are not posted (disabled radios are not submitted). In Laravel: `foreach ($request->input('pick', []) as $rowKey => $value) { if ($value === '' || $value === null) continue; ... }`.
- Validate on the server: the browser only prevents choosing disabled cells; never trust that the value is one of the offered ones.
- Events: none are dispatched; use the browser's native `change` event on the root (or a radio) if the host needs to react.
- JavaScript: instance `el.dlComponent` (class `PickMatrix`, file `resources/assets/js/components/pick-matrix.js`); `sum()` recomputes the summary. No `el.dlXxx` API.
- Style hooks: `.dl-pick`, `.dl-pick__table`, `.dl-pick__cell`, `.dl-pick__sum`, `.dl-pick__lock`, `.dl-pick__warn`, `.dl-pick__none`, `.dl-pick__empty`, `td.is-best`, `tr.is-locked`.

## Rules and pitfalls

- Use the same type for the column key in `columns`, `cells` and `default`. PHP turns numeric string keys into integers, but the `default` value is compared strictly (`===`): `'default' => '12'` does not match the column key `12`, so no cell is tinted or pre-selected and "Skip" is checked.
- Always provide `value` and `text` for every cell and `key` and `label` for every row; missing keys are read without a default and raise a PHP error.
- `columns` is a translated list: each `label` goes through `Lang::text`, and a new text is recorded in the translation dictionary. Supplier names are data. Wrap them with `Lang::raw()` (the `raw()` helper) when building the array so they are not translated or collected.
- The summary says "1 rows" for one row: the dictionary text has no plural form.
- A default winner is only a suggestion: the server must recompute or validate it (for example "cheapest" from the quotation data) and not rely on the pre-selected radio of an old page.
- Keep row `key`s stable between renders; the posted name uses them, not the row position.
- Locked rows are not posted; the server must keep their current state.
- There is no "select all cheapest" or column-wise action; add that in the host with the `default` values you compute.
- Do not rely on `is-best` alone for meaning; the checked radio is the state. (The tint is also shown on a disabled default cell.)

### Notes on inconsistencies found while writing this entry (not fixed)

- If a row's `default` cell exists but is `disabled`, the cell is tinted (`is-best`), the cell radio is not checked, and "Skip" is not checked either (its `@checked` test only looks at whether the default cell exists), so the row ends with no checked radio and posts nothing. Avoid giving a `default` that points to a disabled cell, or accept that such a row is undecided.
- The CSS prefix `dl-pick` is shared with the date/time picker panels in `devlib.css` (`.dl-pick .dl-dd__panel`, `.dl-pick__panel`, `.dl-pick__head`, ...). Nothing in the matrix uses those selectors today, but the root class `dl-pick` is not unique to this object.
- The catalog entry title for this object (`site/app/Support/Catalog.php`) and the default `source()` text (`baris x penawar`) are Indonesian, while the rest of the object texts are English source text.

## Example

Runnable with fictional data (adapted from the catalog demo `site/resources/views/demos/pick-matrix.blade.php`):

```blade
<form method="POST" action="{{ url('/purchasing/awards') }}">
    @csrf
    <x-dl::pick-matrix name="pick" unit="PO"
        :columns="[
            11 => ['label' => 'Supplier A', 'sub' => 'Net 30'],
            12 => ['label' => 'Supplier B', 'sub' => 'Net 30'],
            13 => ['label' => 'Supplier C', 'sub' => 'Cash'],
        ]"
        :rows="[
            ['key' => 1, 'label' => '#1 Ivory 250', 'sub' => '40 RIM', 'default' => 11,
                'cells' => [
                    11 => ['value' => 101, 'text' => '1,160,000', 'sub' => 'lead 10 days'],
                    12 => ['value' => 201, 'text' => '1,185,000', 'sub' => 'lead 7 days'],
                ]],
            ['key' => 2, 'label' => '#2 Duplex 310', 'sub' => '25 RIM', 'default' => 12,
                'cells' => [
                    11 => ['value' => 102, 'text' => '930,000'],
                    12 => ['value' => 202, 'text' => '905,000'],
                    13 => ['value' => 302, 'text' => '912,000'],
                ]],
            ['key' => 3, 'label' => '#3 Kraft 125', 'sub' => '1,200 KG', 'default' => 11,
                'cells' => [
                    11 => ['value' => 103, 'text' => '14,250'],
                    13 => ['value' => 303, 'text' => '14,400 / ROLL', 'disabled' => 'Unit differs from the request'],
                ]],
            ['key' => 4, 'label' => '#4 Cyan Ink', 'sub' => '12 KG', 'locked' => 'Already on a PO',
                'cells' => [12 => ['value' => 204, 'text' => '185,000']]],
        ]" />
    <x-dl::button type="submit" label="Create POs" />
</form>
```

## Related

- `x-dl::permission-matrix`: another matrix editor (a level per row for a chosen group).
- `x-dl::grid`: the table object for comparing and listing without a choice per row.
- `x-dl::field.radio`: a single radio group for one value.
- `x-dl::form`, `x-dl::action-form`: forms that can hold the matrix and post it.
- `x-dl::lines`: line-item entry for the documents the choices create.
