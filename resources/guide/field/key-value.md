## Overview

`<x-dl::field.key-value>` is a small editable table of name/value pairs with a "+ Add row" button and a delete button on every row. Use it for free-form attributes of a record (item specification, extra properties, header/value lists) where the names are not fixed in advance. For fixed attributes use ordinary fields; for lists of words use `x-dl::field.tags`; for real detail lines with typed columns, totals and validation use `x-dl::lines`.

It sits in the shared field frame (`Devlib\Components\Field\Field`): label row, the control, the error line and the optional hint. Extra HTML attributes (`id`, `class`, `data-*`) land on the root `<div class="dl-field dl-field--key-value ...">`. `can="module.action"` removes the whole field when the user lacks the ability.

### What it is not

- Not typed: both cells are plain text inputs; no number, date or select cells.
- Not unique-key aware: nothing stops two rows with the same name, and empty rows are allowed.
- Not a validator: no maximum rows, no format check, in the browser or on the server.

## Props

Constructor of `Devlib\Components\Field\KeyValue` (16 props). Blade names are kebab-case.

### Own props

| Prop | Type, default | Meaning |
|---|---|---|
| `key-label` | `string`, `'Name'` | Heading of the first column. Translated (`Lang::text`). |
| `value-label` | `string`, `'Value'` | Heading of the second column. Translated. |

The "+ Add row" text and the delete button title ("Delete row") are fixed dictionary texts; there are no props for them.

### Inherited props

Defined once in `Field`; the full reference is in `x-dl::field.text`. Differences for this control:

| Prop | Type, default | In this field |
|---|---|---|
| `name` | `string`, `''` | Base name. The cells are named `<name>[<n>][key]` and `<name>[<n>][value]`, so write the name **without** brackets. Also `data-dl-field` and the `data-name` of the control. |
| `label` | `string`, `''` | Label text (translated). Empty = no label row. The label has no `for` (the control is a table, not one input); the group `div` carries `role="group"` and `aria-labelledby` pointing at the label. |
| `tooltip` | `?string`, `null` | Text of the (i) bubble (translated). |
| `hint` | `?string`, `null` | Help line under the control (translated). |
| `error` | `?string`, `null` | Server error text; non-empty adds `is-invalid`. Not translated. |
| `value` | `mixed`, `null` | Initial rows as an **associative array** `name => value` (`['Weight' => '250 gsm']`); it is looped as `$k => $v`, so values must be scalars (an array value breaks the view with an "Array to string" error). A list of pairs (`[['key' => .., 'value' => ..]]`) is **not** accepted: it would render keys `0, 1, ...`. |
| `placeholder` | `?string`, `null` | **Not used** (the cells have no placeholder). |
| `required` | `bool`, `false` | Red `*` and the `x-dl::form` "Required" check: at least one cell (key or value) of any row has text. No HTML `required`, no server check. |
| `disabled` | `bool`, `false` | `disabled` on the "+ Add row" button, every cell input and every delete button; the cells are not posted. |
| `readonly` | `bool`, `false` | The add and delete buttons are `disabled` and the cells are `readonly` (still posted), plus the class `is-readonly`. Forced on while `Field::$readonlyAll` is true. |
| `icon` | `?string`, `null` | Not used by this control. |
| `size` | `string`, `'sm'` | `sm` or `lg`; only the CSS class `dl-field--sm\|lg`. |
| `i18n` | `?string`, `null` | Written to `data-i18n` on the label for auth screens. |
| `source` | `?string`, `null` | Free text for the developer catalog (default `input "<name>"`); no visual effect. |

## Behavior

### Markup

```
div.dl-field.dl-field--key-value[data-dl-field=name]
  label#<uid>-lbl ...                                      (no for=)
  div.dl-kvedit[data-dl-kv][data-name=name][role=group][aria-labelledby=<uid>-lbl]
    table.dl-kvedit__table
      thead > tr > th key-label | th value-label | th (empty)
      tbody[data-dl-kv-body] > tr > td input[type=text][name="name[0][key]"] | td input[name="name[0][value]"] | td button[data-dl-kv-x] ×
    button.dl-kvedit__add[data-dl-kv-add]  "+ Add row"
```

### Adding, deleting and numbering

The class `Kv` (`components/kv.js`) is wired to the add button and to the table body:

- **+ Add row** appends a row with two empty text inputs and a delete button, renumbers all rows and focuses the first input of the new row.
- The **x** button removes its row at once (no confirmation) and renumbers.
- `renumber()` rewrites the names of all rows to `name[0][key]`, `name[0][value]`, `name[1][key]`, ... in display order, so the indices in the request are always contiguous from 0 and follow the order on screen. The server-rendered rows use the same scheme.
- `Enter` inside a cell has no special handler: it is the browser's normal implicit form submission (it does not add a row).
- The table has no rows when `value` is empty; only the header and the add button show.

### Read-only and disabled

`disabled` switches off the add button (opacity 0.45), the delete buttons and every cell (`disabled`, so nothing is posted); `readonly` also switches off the buttons and makes the cells `readonly`, so the pairs are still posted. Inside a locked `x-dl::form` (disabled fieldset, or `mode="view"`) the browser disables every input and button, so the table cannot be edited and its cells are not submitted.

## Data and events

### What the server receives

A nested array under `name`, one entry per row in display order, including rows whose cells are empty:

```
spec[0][key]=Weight      spec[0][value]=250 gsm
spec[1][key]=Size        spec[1][value]=65 x 100 cm
```

```php
$request->input('spec');   // [['key' => 'Weight', 'value' => '250 gsm'], ['key' => 'Size', 'value' => '65 x 100 cm']]
```

With no rows the field is **absent** from the request (not an empty array). The server decides what to do with empty and duplicate names:

```php
$spec = collect($request->input('spec', []))
    ->filter(fn ($r) => trim($r['key'] ?? '') !== '')
    ->mapWithKeys(fn ($r) => [trim($r['key']) => trim($r['value'] ?? '')])   // a duplicate name: the last row wins
    ->all();
```

### DOM events and hooks

| Hook | Detail |
|---|---|
| events | Typing fires the native bubbling `input`/`change` of the named cell (`e.target.name` is `spec[0][key]`). Adding and deleting a row dispatch **no** event. No `dl:*` events. |
| `data-dl-kv`, `data-name` | The `.dl-kvedit` element of this control and the base name. |
| `data-dl-kv-body`, `data-dl-kv-add`, `data-dl-kv-x` | Table body, add button, delete button (one per row). |
| `data-dl-field="<name>"` | Root attribute (used by validation). |
| `el.dlComponent` | On the `[data-dl-kv]` element it is the `Kv` instance with `renumber()`; call it after you change rows by script. |

### Form integration

- The Save/Confirm check marks the field "Required" when every cell is empty; typing in a cell clears the error.
- Typing in a cell marks a guarded form dirty. Adding or deleting a row dispatches a bubbling `change` on the root `[data-dl-kv]` (it carries `data-dl-reports`, which the dirty guard accepts in place of a field name), so these changes mark the form dirty and enable Undo too.
- `Devlib.fill`, `Devlib.setField` and the form's Undo / Cancel-in-view-mode restore the rows: the key `<name>` takes an array of `{key, value}` (or `[key, value]`) rows, or the flat keys `<name>[0][key]` / `<name>[0][value]`; `KeyValue` has `rows()` and `set(rows)`. `clear=true` empties the rows when the key is absent.

## Rules and pitfalls

- Write the `name` without `[]`; the field builds `name[n][key]` itself.
- Pass `value` as `name => value` pairs with scalar values and build the pairs back from the posted rows on the server (example above). Two rows with the same name collapse into one in an associative array; decide which one wins or reject duplicates.
- Names that look like integers (`'1'`) become integer keys in PHP arrays; keep the order and the exact spelling you need by storing the rows as a list of pairs (JSON) if that matters.
- Empty rows are posted as empty strings. Always filter them out.
- An empty table posts nothing: update stored data with `$request->input('x', [])`, otherwise the last row can never be deleted.
- `disabled`/`readonly` lock the rows in the markup (cells, add and delete buttons). Rows added or deleted by script are not covered; freeze with a locked form if a script may change them.
- Deleting a row has no confirmation and no undo inside the field.
- Keyboard: the cells are normal inputs and the add/delete controls are `<button>` elements, so everything is reachable with `Tab`, `Enter` and `Space`. The cells have no `aria-label` (the column headings are `<th>` only), the delete button has only a `title`, and the label is linked to the group through `aria-labelledby`, not to a single control.
- Texts of the headings are translated; the values are data and not.

## Example

Specification list with custom column headings:

```blade
<x-dl::field.key-value name="spec" label="Item specification" key-label="Attribute" value-label="Value"
    :value="old('spec', $item->spec ?? ['Weight' => '250 gsm', 'Size' => '65 x 100 cm'])" :error="$errors->first('spec')" />
```

Empty list with default headings and a hint:

```blade
<x-dl::field.key-value name="extra" label="Extra properties" hint="Empty rows are ignored." tooltip="Free name/value pairs." />
```

Controller side:

```php
$item->spec = collect($request->input('spec', []))
    ->filter(fn ($r) => trim($r['key'] ?? '') !== '')
    ->mapWithKeys(fn ($r) => [trim($r['key']) => trim($r['value'] ?? '')])
    ->all();
```

## Related

- `x-dl::field.text`: the full reference of the 14 inherited props and the shared field frame.
- `x-dl::field.tags`: a list of free-text labels (posted as `name[]`).
- `x-dl::field.multi-select`: several values from a fixed list.
- `x-dl::lines`: real detail lines with typed columns and totals.
- `x-dl::form`: required check, dirty guard and Undo.
- `skills/devlib/form.md`, `skills/devlib/components.md`: project rules for forms and fields.
