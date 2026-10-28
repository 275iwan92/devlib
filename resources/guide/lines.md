## Overview

`<x-dl::lines>` is the table of item rows inside a document form: the detail part of a Master-Detail screen (Purchase Order, Customer Order, Stock In and similar). It draws editable rows with typed cells (text, number, money, select, date, lookup, Yes/No, Primary), computes row formulas, adds a sum row, and, when `totals` is given, a document footer with Discount / Tax / Other Costs tabs, a Summary (Details, Discount, Tax Base, Tax, Grand Total) and the amount in words. Everything is posted together with the form: the rows become ordinary form inputs (`name[...]`), there is no separate save request.

The class `Devlib\Components\Lines` has no Blade view file (inline `render()`); the behaviour is the JS class `Lines` in `resources/assets/js/components/lines.js` (selector `[data-dl-lines]`). It is normally placed in the `detail` slot of `x-dl::form`, which validates it on Save and keeps a snapshot of the rows for Undo.

### When to use it, and when NOT

- Use `lines` for the item rows of a transactional document that is saved with its header in one form POST, especially when there are quantities x prices, a sum, discount, tax or costs.
- Use `x-dl::sheet` (or `x-dl::lookup-grid`) for a worksheet or helper list that saves itself and is not part of a document form.
- Use `x-dl::grid` with `detail` for a read-only display of the lines of many documents (a grid row expands to the item list).
- Use `x-dl::table` for a plain read-only table.
- Use `x-dl::pick-matrix` or another object when the rows are chosen by ticking from a catalog, not typed.

## Props

Constructor of `Devlib\Components\Lines` (17 props). Blade names are kebab-case; non-string values use `:prop="expr"` (`:min-rows="0"`, `:readonly="true"`).

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `name` | `string` | `'items'` | Base name of the posted fields (`name[0][qty]`, `name_disc_normal_pct`, ...) and of the summary hooks (`data-l-val="name:grand"`). Unique per page. Also the key in `Devlib.fill(form, data)` (`data[name]` = list of rows). |
| `columns` | `array` | `[]` | Column definitions, see "Column definition". Labels are translated. |
| `rows` | `array` | `[]` | Initial rows (list of associative arrays; extra keys such as `item_label` or a database id travel with the row). Written as JSON to `data-rows`. |
| `title` | `string` | `'Item'` | Bold title of the header bar (translated). Forms use `Details`. |
| `add-label` | `string` | `'+ Add Row'` | Label of the add button (translated); also quoted in the empty-state text ("Click + Add Row."). |
| `min-rows` | `int` | `1` | Minimum number of rows. `0` = may be empty (no starting blank row). When the number of rows is `<= min-rows`, the delete button empties the row (the row stays) instead of removing it. Validation fails when fewer than `min-rows` rows are filled. |
| `totals` | `array` | `[]` | Footer definition, see "Totals". Empty (and no `note` slot) = no footer. |
| `total-key` | `?string` | `null` | Written to `data-total-key`; the JS does not read it (see the notes). |
| `count-key` | `?string` | `null` | Written to `data-count-key`; the JS does not read it (see the notes). |
| `readonly` | `bool` | `false` | No add/duplicate/delete buttons, no action column, cells shown as text, footer inputs read-only. Also automatic inside a `form.is-view` that is not a strict-cycle form (`form[data-f-strict]`): the strict cycle keeps the row buttons during Draft and uses `:readonly` only for Confirmed / Cancelled. |
| `source` | `?string` | `null` | Source text for the developer catalog (`data-dl-src`); default `'baris detail dokumen'`. |
| `key-field` | `?string` | `null` | Server-driven screens: name of the row field that holds the database id (for example `id_item`). Rows are posted as `name[<id>][col]` instead of `name[<index>][col]`; rows without an id as `name[new<i>][col]`. |
| `remove-field` | `?string` | `null` | With `key-field`: a deleted saved row is posted as hidden `remove-field[<id>]=1`. |
| `addable` | `bool` | `true` | `false` = no add button and no add-by-Enter on the last row; the screen adds rows itself (slot `tools`, then `el.dlLines.set(list)`). |
| `duplicable` | `bool` | `true` | `false` = no "duplicate row" button. Ignored with `row-state` (that mode uses other buttons). |
| `row-lock` | `?string` | `null` | Name of a boolean row field. Rows where it is falsy get no delete button (for example a snapshot row that must stay: `row-lock="editable"`). |
| `row-state` | `bool` | `false` | Opt-in two-state rows (owner rule 2026-10-20): a new or incomplete row is in state `edit` (buttons Save row + Undo), a saved row is locked (buttons Edit + Delete). Details under Behavior. |

Slots:

| Slot | Where | Use |
|---|---|---|
| `tools` | in the header bar, before the add button | the screen's own add-row controls (for example choose a master item + button) |
| `note` | footer, a tab "Notes" in the left tabs | free notes (for example a field for the supplier note); shown even when `totals` is empty |

### Column definition

`config()` adds `type => 'text'`, `required => false`, `sum => false` to every column; for `lookup` columns it merges the lookup definition (`Devlib\Lookup::get(name)` plus `url` = route `devlib.lookup`); a plain list `options` becomes key = label.

| Key | Values and default | Meaning |
|---|---|---|
| `key` | string | Field name in the row (also the posted key). A formula may use only these names. |
| `label` | string | Heading (translated); red `*` when `required`. |
| `type` | `text` (default), `number`, `money`, `select`, `lookup`, `date`, `display`, `primary`, `bool` | See the cell types below. |
| `required` | `false` | Must be filled in every filled row; Save (or row Save with `row-state`) marks empty cells (`is-invalid`). |
| `width` | CSS width | Width and min-width of the heading cell. |
| `placeholder` | string | Placeholder of text/number inputs. |
| `options` | list or map | `select` choices (key = stored value, label = shown; read-only shows the label). |
| `lookup` | name of a `Devlib\Lookup` definition | For `type lookup`. Required for that type. The row stores the key in `<key>` and the text in `<key>_label` (your rows must supply `<key>_label` when loading). The lookup's `fill` map (`['uom' => 'uom', 'price' => 'price']`: target row field => source field) fills other columns of the same row when a choice is made. |
| `readonlyUnless` | a `bool` column key | Text/number/money cell is read-only (not focusable) until that Yes/No column of the row is true. |
| `labels` | `[on, off]` | Texts of the toggle button of `primary` / `bool` columns. Defaults: primary `✓ Primary` / `Choose`; bool `Yes` / `No`. |
| `default` | any | Starting value of a new row (a `bool` column uses its truthiness). |
| `formula` | `'qty * price'` | Computed column, read-only. Only column keys, numbers, `+ - * / ( )` and spaces are allowed; anything else, or a name that is not a column, throws an `Error` when the component starts. Missing values count as 0; the result is rounded to 2 decimals; it is shown in money format unless `type` is `number`. |
| `sum` | `false` | Adds the column total to a sum row at the bottom (label "Total" in the first column). |
| `more` | `false` | The column is not in the table; it is edited in a panel opened with the `›` button at the row number (the button gets `has-val` when something is filled). Required cells hidden there are opened automatically by validation. Keeps wide details compact. |
| `landed` | `'amount'` or `'unit'` | Computed landed-cost column (needs `totals.landed`): `amount` = the row's share of the costs ticked "COGS"; `unit` = (row net + share) / `qty`. Uses a column named `qty`. Read-only, not counted as "filled". |

Cell types:

| `type` | Cell | Stored value |
|---|---|---|
| `text` | text input + `aa` case button (cycles none / Title Case / UPPER) | string |
| `number` | decimal input, right-aligned; focus shows the raw value, blur formats (up to 3 decimals) | number (canonical, `1234.5`) |
| `money` | same as number; shown with up to 3 decimals | number |
| `select` | `<select>` with an empty first option | option key |
| `lookup` | text box + search icon; a drop-down table opens on focus/typing | key; label in `<key>_label` |
| `date` | native `<input type="date">`; read-only shows `dd-Mmm-yyyy` | `YYYY-MM-DD` |
| `display` | read-only text (value supplied by the server) | whatever the row has |
| `primary` | button; exactly one row is true (the first row becomes Primary automatically; deleting the Primary row moves it to the first remaining row) | boolean |
| `bool` | Yes/No toggle button | boolean |

### Totals

`totals` is an array:

| Key | Meaning |
|---|---|
| `of` | Column key whose sum is the **Details** subtotal (required for any footer figure). |
| `discount` | `true` = three rows at 0 %: Normal / Qty / Amount Discount; or an array `code => [label, startPercent, 1]`. A "Discount" tab appears. Discount is capped at the subtotal. |
| `ppn` | Number (for example `11`) = tax rows VAT (given %), Income Tax PPh 23 (0 %, deducted), Customs Duty (0 %, added). |
| `tax` | Own tax rows `code => [label, startPercent, +1 added or -1 deducted]`; wins over `ppn`. |
| `costs` | Other costs `code => label` or `code => [label, startPercent, 1, inTaxBase, inCogs]`. By default every cost is in the tax base (checkbox VAT ticked), except codes `admin`, `materai`, `stamp`; the COGS checkbox (only with `landed`) has the same default. |
| `landed` | `true` = Purchasing landed cost: adds the COGS checkbox per cost and the "Allocation to item COGS" select (By Value / By Quantity / Equal) and fills the `landed` columns. |
| `currencyField` | Name of the form field with the currency code (default `currency`). Symbol and amount in words follow it (IDR = Indonesian words, others English). |

Calculation (in `total()`): `sub` = sum of `of`; `disc`; `dpp` (Tax Base) = `sub - disc + costs in the tax base`; `tax` = tax rows on `dpp` (PPh counts negative); `grand = dpp + tax + costs outside the tax base`. In every discount, tax and cost row the user types either the percentage or the amount; the last typed one is the source and the other is recalculated and filled in.

## Behavior

### Adding, editing, removing rows

- At start the component adds one blank row when `rows` is empty, `min-rows > 0` and the lines are not read-only.
- The add button (and Enter on the last row of a column) appends a blank row and focuses it. Enter on any other row moves to the same column of the next row.
- A row counts as filled when at least one non-formula, non-landed cell of type other than `display`, `primary`, `bool` has text. Only filled rows are counted ("N rows"), validated, summed, posted and returned by `el.dlLines.rows`; blank rows are ignored.
- Delete asks "Delete row N?" when the row is filled. At `min-rows` the row is emptied instead of removed. Removing a saved row records its id for `remove-field`.
- Duplicate copies the row (including its id field; see the notes) right below it.
- Typing is parsed with the display-language number format; stored values are canonical (`1234.5`).

### `row-state` rows

| Row state | Buttons | Cells |
|---|---|---|
| `edit` (blank, new, or incomplete when loaded: some required cell empty) | Save row, Undo row | open |
| `saved` (complete when loaded, or after Save row) | Edit, Delete | locked as text |

Save row refuses (and marks the cells) while a required cell is empty. Edit remembers the content; Undo returns to it and locks again; for a never-saved row Undo removes it (or blanks it at `min-rows`). Nothing is sent to the server: all filled rows, in either state, are still posted with the form Save. In a locked form (`readonly`) the buttons disappear.

### Totals footer

Left side: tabs Discount / Tax / Other Costs / Notes (only those that exist), each table with % and Amount columns that fill each other and a total line; the Amount in words box. Right side: Summary (Details, Discount, Other Costs subject to VAT, Tax Base, Tax, Other Costs non-VAT, Grand Total). The currency symbol comes from `[name=<currencyField>]` of the form and the footer redraws when that field changes. Grand total words: IDR in Indonesian, other currencies in English with the currency's sub-unit.

### Freeze panes and the `more` panel

The first table column is frozen on the left (`dl-lines__fzl`), the last formula column on the right (`dl-lines__fzr`), so wide tables scroll sideways with the key columns visible.

### Phone view

In the phone context (page body has `dl-on-phone`, see `Devlib\Device::bodyClass`, or the lines are inside `x-dl::phone-shell`) each row becomes a card instead of a table row, so nothing scrolls sideways: the row number at the top left, the row buttons (save, undo, edit, delete, copy) at the top right, and every column as a labelled field below (`lines.js` puts the column label on each cell as `data-label`). Short cells (number, money, read-only, date, select) sit two or three to a line; text and lookup cells take the whole line. Fields are 16 px and at least 44 px tall. The sums row of the table footer is hidden on a phone; the `totals` Summary stays. No prop is needed and the desktop table is not changed (every rule is scoped to the phone context).

### Lookup columns

Local lookups (rows embedded in the page) show the first 50 matches of the typed text over all lookup columns. Remote lookups (a definition with `search`) wait for at least 2 letters, then `GET <devlib.lookup route>?q=...` (250 ms debounce, stale answers dropped). Choosing a row sets `<key>`, `<key>_label` and the `fill` fields, closes the list, redraws and moves focus to the next column. Leaving the box restores its text to the chosen label (free text is never kept).

### Translation (Lang) and access (`can`)

`title`, `add-label`, column labels, footer labels, hints and tooltips go through `Lang` (dictionary group `grid`). Item names and other row values are data and are never translated. Cost labels you pass in `totals.costs` are looked up in the dictionary and shown as given when there is no entry. `can="module.action"` on the tag hides the whole object when the user lacks the ability.

## Data and events

### Root and hook attributes

```html
<div class="dl-lines [is-readonly]" data-dl="x-dl::lines name=po_lines" data-dl-lines data-name="po_lines"
     data-total-key data-count-key data-config='{...}' data-rows='[...]'>
  <div class="dl-lines__head"><b>Title</b><span class="dl-lines__count" data-l-count></span> ... <button data-l-add> </div>
  <div class="dl-lines__wrap"><table class="dl-lines__table"><thead data-l-head/><tbody data-l-body/><tfoot data-l-foot/></table></div>
  <div data-l-inputs hidden></div>                         <!-- the posted hidden inputs, rewritten on every refresh -->
  <div class="dl-lines__footer"> tabs (data-l-grp, data-l-adj rows, data-l-pct, data-l-amt, data-l-tx, data-l-hpp, data-l-alloc)
       + words (data-l-words) + Summary (data-l-t="sub|disc|cost_tx|dpp|tax|cost_nt|grand") </div>
</div>
```

Row buttons use `data-l-dup`, `data-l-del`, `data-l-save`, `data-l-edit`, `data-l-undo`, `data-l-exp`, `data-l-primary`, `data-l-bool`; cells `td[data-k]` or inputs `.dl-lines__in[data-k]`; error line `data-l-err`. Row tr has `data-i` (index). Extra attributes go on the root.

`data-config` keys: `name`, `columns`, `minRows`, `totals`, `readonly`, `keyField`, `removeField`, `addable`, `duplicable`, `rowLock`, `rowState`.

### What is posted

The hidden inputs (inside `[data-l-inputs]`) hold every key of every filled row, including `<key>_label` and `fill` fields:

| Field name | Value |
|---|---|
| `name[<i>][<key>]` | row value. `<i>` = position among filled rows (0, 1, ...); with `key-field` it is the row id, or `new<i>` for a row without id. Booleans arrive as the strings `true` / `false`. |
| `remove-field[<id>]` | `1` for deleted saved rows (with `key-field` + `remove-field`) |
| `name_disc_<code>_pct` / `_amt` | discount row percentage / amount (the amount as formatted in the display language) |
| `name_tax_<code>_pct` / `_amt` | tax row |
| `name_cost_<code>_pct` / `_amt` | cost row |
| `name_cost_<code>_tax` / `_hpp` | `1` when the VAT / COGS box is ticked (checkbox, absent otherwise) |
| `name_alloc` | `value`, `qty` or `equal` (landed allocation) |

The server should recompute subtotal, discount, tax and grand total from the rows; the footer figures are for display only.

### Summary hooks outside the object

Any element in the same `<form>` (or the document) with `data-l-val="<name>:<key>"` is kept up to date: `key` = `count` (filled rows), `sum.<column>` (sum of a column), or a total key (`grand`, `sub`, `dpp`, `tax`, `disc`, `cost`, `cost_tx`, `cost_nt`, `hpp`, `disc_pct`, ...), shown with the currency symbol. The demos use it for the Grand Total in the key summary strip.

### Events

There are no `dl:*` events. After any structural or committed change the root dispatches a bubbling native `change` event (typing inside cells already bubbles native `input` / `change`). `x-dl::form` listens to `change` to mark the form as modified.

### JS access: `el.dlLines`

| Member | Meaning |
|---|---|
| `rows` | getter: copies of the filled rows |
| `set(list)` | replace all rows (copies), recompute `row-state` states, add blank rows up to `max(1, min-rows)`, clear the error line. Used by `x-dl::form` Undo and by `Devlib.fill()`. |
| `validate()` | clear and mark `is-invalid` on empty required cells of filled rows, open `more` panels that hide such cells, write the message in `[data-l-err]` ("At least N filled rows." or "N required cells are empty."); returns the number of problems (0 = OK). Called by the form Save. |
| `total()` | the totals object (`sub`, `disc`, `dpp`, `tax`, `cost`, `grand`, ...) |

## Rules and pitfalls

- **Lines are posted with the form, not saved by themselves.** For a server-driven edit screen use `key-field` / `remove-field` so the server can tell insert, update and delete. Do not trust the posted totals.
- **Booleans are posted as the text `true` / `false`**, not `1` / `0`.
- **Amounts in the footer inputs (`_amt`) are formatted in the display language**; parse them as a locale number, or ignore them and recompute from the rows.
- **Give every `lookup` column rows with `<key>_label`** when you load saved data, otherwise the box shows empty text. The lookup name must exist (`Devlib\Lookup::define(...)` or `config('devlib.lookups')`); `Lookup::get` returns an empty definition otherwise.
- **Formulas fail loudly**: an invalid character or an unknown column name throws while the component starts and the whole object stays empty. Keep formulas to column keys and `+ - * / ( )`.
- **`landed` columns assume** a column named `qty` and the `totals.of` column; they work only with `totals.landed`.
- **One lines object per form field name**; two objects with the same `name` post into each other.
- In the strict form cycle (`x-dl::form strict`) the row buttons stay during Draft even when the form is in view mode; pass `:readonly` only for Confirmed / Cancelled.
- No Devlib lint rule inspects `x-dl::lines`. General view rules apply to the screen (no inline `<script>`, L002; no raw HTML for objects that exist, L001; no hex colours, L003). Owner rules for the row buttons are in `skills/devlib/transaction-lifecycle.md` and `skills/devlib/grid-form-flow.md`.
- The only feature test that mentions it checks that a single-flow form (cash voucher) renders **without** `data-dl-lines`.

### Notes on inconsistencies found while writing this entry (not fixed)

- The class comment says the post fields are `name[i][column]`, `name_discount`, `name_ppn`. The real names are `name_disc_<code>_pct|_amt`, `name_tax_<code>_*`, `name_cost_<code>_*`, `name_alloc` (see "What is posted"); there is no `name_discount` or `name_ppn`.
- `total-key` and `count-key` are written to `data-total-key` / `data-count-key` but nothing in the JS or elsewhere reads them; they have no effect. The working replacement is `data-l-val="name:grand"` / `name:count` on another element.
- The JS comment lists the summary keys `grand|sub|dpp|ppn`; there is no `ppn` key (it shows 0). The tax total key is `tax`.
- `el.dlLines.set([])` always leaves at least one blank row (`max(1, min-rows)`), while at start `min-rows=0` leaves the table empty.
- Duplicate row copies the id field (`key-field`). With `key-field` both rows are posted as `name[<same id>]` and one overwrites the other; use `:duplicable="false"` on server-driven screens.
- The class comment for cost rows says `[label, % start, 1]`; the code also reads the 4th and 5th items (in tax base, in COGS) and ignores the 3rd (the sign is always 1).
- `readonlyUnless` locks only text / number / money cells; select, date and lookup cells ignore it.
- `lines.js` has no REMARK header (other component scripts have one).

## Example

Adapted from the Purchase Order demo (`form-md-po`), fictional data, inside a document form:

```blade
<x-dl::form kind="document" status="draft" title="Purchase Order" :back="route('po.index')">
    <x-slot:detail>
        <x-dl::lines name="po_lines" title="Details" add-label="+ Add Line"
            :rows="$po['lines']" :min-rows="1" :readonly="in_array($status, ['confirmed', 'cancelled', 'closed'], true)"
            :totals="['of' => 'subtotal', 'discount' => true, 'ppn' => 11,
                      'costs' => ['trucking' => 'Trucking / Freight', 'handling' => 'Handling', 'admin' => 'Administration / Stamp'],
                      'landed' => true]"
            :columns="[
                ['key' => 'item', 'label' => 'Item', 'type' => 'lookup', 'lookup' => 'item', 'required' => true, 'width' => '240px'],
                ['key' => 'spec', 'label' => 'Specification', 'width' => '170px'],
                ['key' => 'qty', 'label' => 'Qty', 'type' => 'number', 'required' => true, 'sum' => true, 'width' => '90px'],
                ['key' => 'uom', 'label' => 'Unit', 'type' => 'select', 'options' => ['PCS', 'LBR', 'RIM', 'KG'], 'required' => true, 'width' => '85px'],
                ['key' => 'price', 'label' => 'Unit price', 'type' => 'money', 'required' => true, 'width' => '120px'],
                ['key' => 'subtotal', 'label' => 'Subtotal', 'type' => 'money', 'formula' => 'qty * price', 'sum' => true, 'width' => '130px'],
                ['key' => 'eta', 'label' => 'Delivery date', 'type' => 'date', 'more' => true],
                ['key' => 'rem', 'label' => 'Line note', 'more' => true],
                ['key' => 'landed_amt', 'label' => 'Landed cost share', 'type' => 'money', 'landed' => 'amount', 'more' => true],
                ['key' => 'landed_unit', 'label' => 'COGS / unit', 'type' => 'money', 'landed' => 'unit', 'more' => true],
            ]">
            <x-slot:note>
                <x-dl::field.text name="note" label="Note for the supplier" multiline />
            </x-slot:note>
        </x-dl::lines>
    </x-slot:detail>
</x-dl::form>
```

A row of `$po['lines']` for the lookup column carries the label next to the key:

```php
['item' => 'RM-IVR-0310', 'item_label' => 'Ivory 310 gsm', 'spec' => 'Plano 65x100', 'qty' => 70, 'uom' => 'LBR', 'price' => 4200, 'eta' => '2026-11-09']
```

Server-driven screen with ids and the two-state row buttons (rows are posted as `po_lines[<id>][...]`, removed rows as `remove[<id>]=1`):

```blade
<x-dl::lines name="po_lines" :row-state="true" key-field="id_line" remove-field="remove" :duplicable="false"
    :rows="$lines" :columns="$columns" />
```

Summary figure outside the object (anywhere inside the same form):

```blade
<b data-l-val="po_lines:grand">IDR 0</b> <span data-l-val="po_lines:count">0</span> rows
```

## Related

- `x-dl::form` (hosts the object in its `detail` slot; validates it and restores it on Undo; `strict` cycle keeps the row buttons in Draft)
- `x-dl::field.lookup` (the same `Devlib\Lookup` definitions, as a single field)
- `x-dl::sheet` (cell grid that saves by itself; not for document lines)
- `x-dl::lookup-grid` (small helper lists)
- `x-dl::grid` (a document list; `detail` shows the lines read-only)
- `x-dl::table` (read-only table)
- `x-dl::doc-footer` (document footer pattern that the Lines footer follows)
- `x-dl::button` and `x-dl::icon-button` (add and row buttons)
