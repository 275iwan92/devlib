## Overview

`<x-dl::field.lookup>` lets the user **choose one row of a master table** (supplier, customer, item, unit, area) instead of typing free text, and can fill other fields of the form from the chosen row. It renders a button that opens a panel with a search box and a small table; the posted value is the **key** of the chosen row, the button shows its **label**. The data behind it is declared once per master with `Devlib\Lookup::define()` (or `config('devlib.lookups')`) and every screen only names it: `lookup="supplier"`.

Two modes, decided by the definition:

| Mode | When | Rows | Search |
|---|---|---|---|
| **Client** | the definition has `rows` | all rows are serialised into the page (`data-rows`) | instant filter in the browser |
| **Remote** | the definition has `search` | no rows in the page | from 2 letters, a debounced `GET` to the route `devlib.lookup`, max. 50 rows |

Use it for master data with an id/code and descriptive columns. For a short fixed list use `x-dl::field.select`; to maintain a small helper list (the data of a lookup) directly in cells use `x-dl::lookup-grid`.

It sits in the shared field frame (`Devlib\Components\Field\Field`): label row, the control, the error line and the optional hint. Extra HTML attributes (`id`, `class`, `data-*`) land on the root `<div class="dl-field dl-field--lookup ...">`. `can="module.action"` removes the whole field when the user lacks the ability.

### What it is not

- Not free text: the value is a key from the definition (the only exception is a row added through `create`).
- Not a data layer: the definition decides where rows come from (config array, a query in a closure, a remote closure); DevLib stores nothing.
- Not an authorisation layer: the remote search route returns whatever the definition's `search` returns; filtering by rights is the definition's job.

## Props

Constructor of `Devlib\Components\Field\Lookup` (18 props). Blade names are kebab-case; arrays and booleans use the `:` binding (`create` alone = `true`).

### Own props

| Prop | Type, default | Meaning |
|---|---|---|
| `lookup` | `string`, `''` | Name of the definition, see the table below. An unknown or empty name does not fail: the field renders with no rows (client mode, key `id`, label `name`). |
| `fill` | `array`, `[]` | `fieldName => column` pairs: after a row is chosen, each target field is set to that column of the row. Empty array = use the `fill` of the definition; a non-empty array **replaces** it (no merge). |
| `create` | `bool`, `false` | Adds an "+ Add new: <typed text>" button in the panel for text that matches no existing label (see Behavior). |
| `params` | `array`, `[]` | Remote mode only. Extra query parameters added to the search URL and passed to the definition's `search` closure as its second argument (for example `['customer_id' => 12]` to list that customer's items first). Fixed at render time. A key `name` or `q` is ignored/overwritten. |

#### Definition (`Devlib\Lookup::define($name, $def)` or `config('devlib.lookups.<name>')`)

`define()` wins over the config entry of the same name.

| Key | Default | Meaning |
|---|---|---|
| `rows` | `[]` | Client mode. A **plain array** of associative arrays, or a `Closure` returning one (called at every render). Each row may contain more columns than shown (they are all serialised into the page, so do not put secrets in them). A Collection is not converted correctly: call `->all()`. |
| `key` | `'id'` | Column whose value is posted and stored. |
| `label` | `'name'` | Column shown on the button after a choice and used for the `create` duplicate check. |
| `columns` | `[label => 'Nama']` | `column => heading`: the columns of the panel table (headings translated). Only these are shown and searched. |
| `fill` | `[]` | Default `fieldName => column` map. |
| `source` | the lookup name | Text for the developer catalog; the field's `source` becomes `lookup "<name>" = <source>`. |
| `search` | none | Switches on remote mode: `fn (string $q, array $ctx): iterable` returning rows. `$ctx` is all query parameters except `q` (the `params`). |
| `find` | none | Remote mode: `fn ($value): ?array` returning the row of an already stored key, to show its label when the form opens. |

### Inherited props

Defined once in `Field`; the full reference is in `x-dl::field.text`. Differences for this control:

| Prop | Type, default | In this field |
|---|---|---|
| `name` | `string`, `''` | Name of the hidden input that is posted (the key of the chosen row, or `new:<text>` for a created one). Also `data-dl-field` and the slug of the generated id placed on the button (the `<label for>` target). |
| `label` | `string`, `''` | Label text (translated). Its lower-case form is also inserted in the search placeholder ("Search supplier..."). |
| `tooltip` | `?string`, `null` | Text of the (i) bubble (translated). |
| `hint` | `?string`, `null` | Help line under the control (translated). |
| `error` | `?string`, `null` | Server error text; non-empty adds `is-invalid`. Not translated. |
| `value` | `mixed`, `null` | The stored **key**. Client mode: compared as strings with the key column to find the label for the button. Remote mode: passed to `find`. A value without a matching row still goes into the hidden input and is posted, while the button shows the placeholder. |
| `placeholder` | `?string`, `null` | Button text while nothing is shown (translated); default is the dictionary word `f_choose` ("Choose..."). It is not the placeholder of the search box. |
| `required` | `bool`, `false` | Red `*` and the `x-dl::form` "Required" check (hidden input empty). No HTML `required`, no server check. |
| `disabled` | `bool`, `false` | Disables the **button** (cannot open); the hidden input stays enabled and is posted. |
| `readonly` | `bool`, `false` | Same as `disabled` on the button, plus the class `is-readonly`; value is posted. Forced on while `Field::$readonlyAll` is true. |
| `icon` | `?string`, `null` | Not used: the button always shows a magnifier icon. |
| `size` | `string`, `'sm'` | `sm` or `lg`; only the CSS class `dl-field--sm\|lg`. |
| `i18n` | `?string`, `null` | Written to `data-i18n` on the label for auth screens. |
| `source` | `?string`, `null` | Free text for the developer catalog; default `lookup "<lookup>" = <definition source>`. No visual effect. |

## Behavior

### Markup

```
div.dl-field.dl-field--lookup[data-dl-field=name]
  div.dl-dd.dl-lookup[data-dl-lookup][data-fill][data-rows][data-key][data-label]([data-create])([data-url][data-columns])
    input[type=hidden][name=name][data-dl-value]
    button#uid.dl-dd__btn[data-dl-dd-toggle] > span[data-dl-dd-text] (label of the chosen row) + magnifier
    div.dl-dd__panel.dl-lookup__panel[data-dl-dd-panel]
      div.dl-dd__search > input[data-dl-dd-search]
      table.dl-lookup__table > thead (headings) + tbody[data-dl-dd-list] > tr.dl-dd__item[data-i]
      button[data-dl-lookup-create]                    (only with create)
```

### Choosing a row (both modes)

1. The button opens the panel (shared `bindPanel`: placed `position: fixed` under or above the button, other dropdowns close, outside click closes, `Esc` does not). On every open the search box is cleared and focused.
2. A click on a `tr.dl-dd__item` takes the row object by its index: hidden input = `row[key]`, button text = `row[label]`, panel closes.
3. A bubbling `change` event is dispatched on the hidden input - **before** the fill step.
4. Fill: for each `field => column` in `fill`, the target is looked up inside the enclosing `<form>` (or the document): first an element `[data-dl-field="<field>"]` (its `[data-dl-value]`, else its first `input`/`textarea`/`select`), else an element with `name="<field>"`. The target's value is set to `row[column]` (`''` if the column is missing), `input` and `change` events are dispatched on it, and if the target box has a `[data-dl-dd-text]` (a select or lookup) that text is set too, so for a select the button shows the raw column value. The box gets the class `is-filled` (a short highlight animation). A target that is not found is skipped silently.

### Search

- **Client mode**: the shared panel filter hides every row whose displayed cell text (all `columns` joined) does not contain the typed text (case-insensitive substring). The search placeholder reads "Search `<label in lower case>`...".
- **Remote mode**: the placeholder reads "Type at least 2 letters to search `<label>`...". Typing fewer than 2 letters sends no request (the previous list stays, only filtered by the typed text). From 2 letters, after a 250 ms pause, the script fetches `data-url` with `q=<text>` (same-origin, `Accept: application/json`), ignores answers that are no longer the latest request, replaces the table body with the returned rows (columns = the keys of `columns`) and keeps those rows as the choosable set. An HTTP error or network failure is shown as an empty result; an empty result shows a "Not found." row. `data-url` is read at every search, so a script may change its query string (for example the `params`) on the live element.
- The server route (`devlib.lookup`, `GET /_devlib-lookup/{name}`, name `[A-Za-z0-9_-]+`) returns 404 when the definition has no `search`, `[]` for fewer than 2 letters, and at most 50 rows (each cast to an array). The middleware comes from `config('devlib.lookup_middleware', ['web'])`.

### Adding a new entry (`create`)

While typing, the button "+ Add new: <text>" appears when the text is not empty and no row of the current list has exactly that label (case-insensitive). A click:

- adds a row `{<key>: 'new:<text>', <label>: <text>}` to the list (shown with "(new)" in its first cell),
- sets the hidden input to `new:<text>`, the button text to `<text>`, closes the panel,
- dispatches the custom event `dl:lookup-create` (bubbles, `detail: {text}`) on the `.dl-lookup` element and shows a toast "Added: <text>".

Nothing is saved by DevLib and no `change` event or fill happens. The screen must save the new master record: on submit the server sees the value `new:<text>` instead of a key.

### Read-only and disabled display

Only the button is disabled (opacity 0.45); `readonly` adds the grey `is-readonly` background. In a locked `x-dl::form` the button reads as plain text (label only), and the hidden input is excluded from the submit by the browser.

## Data and events

### What the server receives

One request field `name`: the **key** of the chosen row as a string, `''` when nothing is chosen, or `new:<text>` for an entry added through `create`. The filled target fields arrive under their own names. The search box, the table and the label text are not posted.

```php
$request->validate(['supplier_id' => ['required']]);
if (str_starts_with($request->supplier_id, 'new:')) {   // only with create
    $supplier = Supplier::create(['name' => substr($request->supplier_id, 4)]);
}
```

Always re-read the master row on the server; the `fill` values are ordinary request fields that the user can edit afterwards.

### DOM events and hooks

| Hook | Detail |
|---|---|
| `change` | On the hidden input `[data-dl-value]` (bubbles) after a row is chosen; `e.target.name` is the field name, `e.target.value` the key. Not fired by `create`, nor by `Devlib.setField`/`Devlib.fill`. |
| `input` + `change` | Dispatched on every filled target field. |
| `dl:lookup-create` | Custom event, bubbles from the `.dl-lookup` element; `detail: {text}`. Fired only by the `create` button. Listen on `document` or the form and check `e.target.closest('[data-dl-field="supplier_id"]')`. |
| `data-dl-lookup` | The `.dl-dd.dl-lookup` element; its data attributes: `data-fill` (JSON), `data-rows` (JSON), `data-key`, `data-label`, `data-create`, `data-url`, `data-columns`. |
| `data-dl-value`, `data-dl-dd-text`, `data-dl-dd-toggle`, `data-dl-dd-panel`, `data-dl-dd-search`, `data-dl-dd-list`, `data-dl-lookup-create`, `data-dl-lookup-new` | Hidden input, button text, button, panel, search input, `tbody`, create button, the typed text inside it. |
| classes | `is-open` on the panel; `is-invalid`, `is-readonly` on the root; `is-filled` on a filled target's box. |
| `el.dlComponent` | On the `[data-dl-lookup]` element it is the `Lookup` instance: `rows` (current choosable rows), `fill`, `p` (the panel helper with `close()`). Internals, not a stable API. |

### JavaScript helpers

`Devlib.setField(form, name, key)` and `Devlib.fill` (also used by Undo) set the hidden input and the button text from the **embedded** rows; with no matching row (always the case in remote mode) the button shows the default "Choose..." text, ignoring the `placeholder` prop. No `change` event is fired. The wizard review reads the button text.

### Form integration

- The Save/Confirm check marks the field "Required" when the hidden input is empty. The red error is cleared only on an `input` event, which this control does not dispatch for itself (only for the filled targets), so it stays until the form is reloaded or Undone.
- A change marks the guarded form dirty (named hidden input). Filled targets mark it dirty too.
- Lint F201 (warning): in a `kind="document"` form a `field.lookup` without `value`, `readonly` or `options-default` is reported.

## Rules and pitfalls

- The posted value is the **key** column, the stored value must be that key, and `value` on edit must be the same key. Label changes in the master do not break stored data.
- Client mode serialises every row and every column into the page (`data-rows`). Use it for up to a few hundred rows without confidential columns; use remote mode for large or sensitive data.
- Remote mode needs `find`: without it the button shows the placeholder for a stored value even though the key is posted again on Save.
- `fill` writes into other fields by name or by `data-dl-field`. It reliably fills text-like inputs, selects and lookups. A field with its own display element (date, money, number) gets its raw value set but may not refresh what the user sees; fill those from a column already in the display format, or avoid filling them.
- `fill` runs after `change`: a `change` listener on the lookup cannot yet see the filled values.
- The fill values are not locked: the user can overwrite them, and the server must take its data from the master row, not from the filled fields, when they must stay consistent.
- `params` are fixed at render time, are visible in the URL and are client-controlled: they are context for `search`, never an authorisation. Check rights inside the `search` closure and set `lookup_middleware` to include authentication (the default `['web']` is open to guests).
- With `create`, the value `new:<text>` is posted instead of a key. Handle it in the controller (create the record, then use its key) and validate the text. No `change` event follows a creation, so the dirty guard is not triggered by it alone.
- A lookup `rows` closure must return a plain array; a Collection from a query needs `->all()` / `->toArray()`.
- Columns are plain text: values are escaped and not translated; headings are translated.
- Keyboard: the button is reachable with `Tab` and opens with `Enter`/`Space`; the search box is focused, so typing filters. The rows are plain `<tr>` elements without `tabindex`, roles or key handling, so the choice itself needs a pointer or touch. There is no `aria-expanded`.
- `disabled`/`readonly` disable only the button; a standalone disabled lookup still posts its key.
- Code oddity (not fixed): `src/Lookup.php:41` - `(array) $rows` on an iterable that is a Collection returns the object's internal properties instead of its items (checked: a one-row Collection yields garbage rows), so a `rows` closure returning a Collection silently breaks the field.
- Code oddity (not fixed): `resources/views/components/field/lookup.blade.php:3` and `:4` - the hidden input is never disabled, so `disabled` does not keep the value out of the request.
- Code oddity (not fixed): `resources/assets/js/core.js:672` - `setField` (used by `Devlib.fill` and form Undo) shows `T('f_choose')` for a cleared or unmatched lookup, not the field's `placeholder`; and it cannot resolve a remote lookup label because remote rows are not embedded.
- Code oddity (not fixed): `resources/assets/js/components/form.js:23` clears a "Required" error only on `input` events; a lookup choice dispatches only `change`, so the error stays.
- Code oddity (not fixed): `resources/assets/js/components/lookup.js:56-67` - the `create` click sets the hidden value but dispatches no `change`/`input`, so the form's dirty guard is not marked by it.

## Example

Client-mode lookup that fills two other fields (the definition lives in `config/devlib.php` or a service provider):

```php
\Devlib\Lookup::define('supplier', [
    'key' => 'id', 'label' => 'name', 'source' => 'master.suppliers',
    'columns' => ['code' => 'Code', 'name' => 'Supplier', 'city' => 'City', 'term' => 'Payment term'],
    'fill' => ['payment_term' => 'term', 'ship_address' => 'address'],
    'rows' => fn () => Supplier::query()->get(['id', 'code', 'name', 'city', 'term', 'address'])->toArray(),
]);
```

```blade
<x-dl::field.lookup name="supplier_id" lookup="supplier" label="Supplier" placeholder="Search supplier..."
    tooltip="Pick from the master; payment term and address are filled." :value="old('supplier_id', $po->supplier_id ?? null)"
    :error="$errors->first('supplier_id')" required create />
<x-dl::field.text name="payment_term" label="Payment term" :value="$po->payment_term ?? null" />
<x-dl::field.text name="ship_address" label="Ship address" multiline :value="$po->ship_address ?? null" />
```

Remote lookup for large data, with a screen-level context and a stored value:

```php
\Devlib\Lookup::define('area', [
    'key' => 'id', 'label' => 'name', 'source' => 'master.areas (server search)',
    'columns' => ['name' => 'Area', 'city' => 'City', 'postal' => 'Postal code'],
    'fill' => ['postal' => 'postal'],
    'search' => fn (string $q, array $ctx) => Area::where('name', 'like', "%{$q}%")->limit(50)->get()->toArray(),
    'find' => fn ($id) => Area::find($id)?->toArray(),
]);
```

```blade
<x-dl::field.lookup name="area_id" lookup="area" label="Delivery area" tooltip="Type at least 2 letters." :params="['customer_id' => $customer->id]" :value="$order->area_id ?? null" />
<x-dl::field.text name="postal" label="Postal code" :value="$order->postal ?? null" />
```

Saving an entry added through `create`:

```blade
<script>
    document.addEventListener('dl:lookup-create', e => {
        if (e.target.closest('[data-dl-field="supplier_id"]')) console.log('new supplier text:', e.detail.text);
    });
</script>
```

## Related

- `x-dl::field.text`: the full reference of the 14 inherited props and the shared field frame; also a typical `fill` target.
- `x-dl::field.select`: the same panel for a short fixed list, with `Devlib.h.setOptions` for dependent lists.
- `x-dl::lookup-grid`: a small editable grid for maintaining helper lists (a different object; it does not pick a value into a field).
- `x-dl::form`: required check, dirty guard, Undo and the `kind="document"` default-value lint (F201).
- `x-dl::grid`: master lists whose rows are the data of these definitions.
- `skills/devlib/form.md`, `skills/devlib/components.md`: project rules for forms and fields.
