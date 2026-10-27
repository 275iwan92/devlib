## Overview

`<x-dl::field.select>` is the single-choice dropdown with a search box. It is **not** a native `<select>`: it renders a button that opens a panel (search input + list of options) and keeps the chosen value in a hidden `<input>`. Use it for a short to medium closed list (city, province, country, payment method, status). For a choice that must also fill other fields from a master table use `x-dl::field.lookup`; for several values use `x-dl::field.multi-select`; for two to four visible choices use `x-dl::field.radio` or `x-dl::field.toggle-group`.

Like every field it is wrapped by the shared field frame (`Devlib\Components\Field\Field`, view `components/field.blade.php`): label row (label, red `*`, tooltip bubble), the control, the error line and the optional hint. Extra HTML attributes (`id`, `class`, `data-*`) land on the root `<div class="dl-field dl-field--select ...">`, not on the button. `can="module.action"` removes the whole field when the user lacks the ability.

### What it is not

- Not a data source: all options are rendered into the page (no paging, no server search). For thousands of rows use `x-dl::field.lookup` in remote mode.
- Not a grouped or multi-column picker: options are flat `value => label` pairs.
- Not a validator: the value is a plain string posted by the browser; the server must check it is one of the allowed values.

## Props

Constructor of `Devlib\Components\Field\Select` (17 props). Blade names are kebab-case; use `:prop="expr"` for arrays and anything that is not a plain string.

### Own props

| Prop | Type, default | Meaning |
|---|---|---|
| `options` | `array`, `[]` | The choices. A **list** (`['Surabaya', 'Malang']`, `array_is_list`) is turned into `value = label = the item`. An **associative array** is `value => label` (`['ID' => 'Indonesia']`). The value is what is posted, the label is what is shown and searched. Order is kept. Each option becomes `<div class="dl-dd__item" data-value="...">label</div>`. Labels are **not** translated by the dictionary and are HTML-escaped. Items must be scalars: a nested array (an "option group") is not supported and breaks the view with an "Array to string" error. |
| `search-placeholder` | `string`, `'Type to search...'` | Placeholder of the search input inside the panel. Translated through the dictionary (`Lang::text`). |
| `add` | `array`, `[]` | Switches on the **Add New** feature (opt-in): a square plus button to the right of the dropdown that opens a small popup to create a new item; after Save the item is added to the list and selected. Empty (default) = the markup is exactly the one without this prop. Keys: `url` (required, POST endpoint, same origin only: `/path` or an absolute URL on the current host; anything else switches the feature off), `fields` (required, list of `['name' => 'name', 'label' => 'Name', 'required' => true, 'type' => 'text' or 'tel' or 'textarea' or 'radio' or 'select', 'maxlength' => 60, 'placeholder' => '...']`; `name` must be letters, digits and `_`, `type` defaults to `text`. Opt-in types `radio` and `select` take `'options' => [value => label]` (required; without options the field falls back to `text`) and an optional `'value'` (the initial choice, restored after every Save); a `radio` / `select` field sends its chosen value, `''` when nothing is chosen, and `required` works for both), `title` (popup title, translated), `icon` (optional Font Awesome name shown in the popup header, e.g. `fa-truck`), `label` (tooltip and `aria-label` of the plus button, default the dictionary word "Add New"), `note` (optional small help text at the top of the popup, translated), `value-key` (JSON key of the new value in the reply, default `id`), `label-key` (JSON key of the new label, default `name`). Texts are HTML-escaped. Not shown (no button, no popup) when the select is `disabled` or `readonly`, or when `Field::$readonlyAll` is on. |

### Inherited props

Defined once in `Field` and shared by every field; the full reference is in `x-dl::field.text`. Only the differences for this control are noted.

| Prop | Type, default | In this field |
|---|---|---|
| `name` | `string`, `''` | Name of the hidden input that is posted (one scalar value). Also `data-dl-field` on the root and the slug of the generated id (`dlf-<name>-<rand>`) placed on the **button**, so the `<label for>` points at it. |
| `label` | `string`, `''` | Label text (translated). Empty = no label row. |
| `tooltip` | `?string`, `null` | Text of the (i) bubble next to the label (translated); only with a non-empty `label`. |
| `hint` | `?string`, `null` | Help line under the control (translated). |
| `error` | `?string`, `null` | Server error text under the control; non-empty adds `is-invalid`. Not translated. |
| `value` | `mixed`, `null` | Selected **value** (an option key, compared after PHP array-key casting, so `1` and `'1'` match). Must be a scalar. Written to the hidden input as given, even when it is not one of the options (see pitfalls). |
| `placeholder` | `?string`, `null` | Text shown on the button while nothing valid is selected; translated. Default is the dictionary word `f_choose` ("Choose..."). It is also written to `data-empty` and reused whenever the field is reset. |
| `required` | `bool`, `false` | Red `*` and the `x-dl::form` "Required" check on Save/Confirm (hidden input empty). No HTML `required`, no server check. |
| `disabled` | `bool`, `false` | Disables the **button** (greyed, cannot open). The hidden input is not disabled, so the value is still posted (see pitfalls). |
| `readonly` | `bool`, `false` | Same effect as `disabled` on the button, plus the class `is-readonly` (grey background); value is posted. Forced on while `Field::$readonlyAll` is true. |
| `icon` | `?string`, `null` | Not used by this control. |
| `size` | `string`, `'sm'` | `sm` or `lg`; only changes the CSS class `dl-field--sm\|lg`. |
| `i18n` | `?string`, `null` | Written to `data-i18n` on the label for auth screens that re-translate on language change. |
| `source` | `?string`, `null` | Free text for the developer catalog (default `input "<name>"`); no visual effect. |

## Behavior

### Markup

```
div.dl-field.dl-field--select[data-dl-field=name]
  label[for=uid] ...
  div.dl-dd[data-dl-select][data-empty=<placeholder>]               (with `add`: wrapped in div.dl-selectadd[data-dl-select-add][data-url][data-value-key][data-label-key],
    input[type=hidden][name=name][data-dl-value]                     followed by button.dl-selectadd__btn[data-dl-sa-btn] and the popup x-dl::modal[data-dl-sa-modal])
    button#uid.dl-dd__btn[data-dl-dd-toggle]  > span[data-dl-dd-text] + ▼
    div.dl-dd__panel[data-dl-dd-panel]
      div.dl-dd__search > input[data-dl-dd-search]        (no name, never posted)
      div.dl-dd__list[data-dl-dd-list] > div.dl-dd__item[data-value]...
  div.dl-field__error[data-dl-field-error]
  div.dl-field__hint[data-dl-field-hint]                  (only when hint is not null)
```

### Display

The button text is the label of the option whose key equals `value`; if no option matches (null, `''`, unknown value) it shows the placeholder. To offer an explicit "none" entry, add an option with an empty key (`['' => '- none -'] + $options`): choosing it posts `''` and shows its label. Do this only with an associative `$options`: once an empty key is added the array is no longer a list, so a list of strings would get the keys `0, 1, ...` as values.

### Opening, searching, choosing

The shared panel helper `Devlib.h.bindPanel` handles the dropdown:

- Click on the button toggles the panel and closes every other open dropdown. The panel is placed with `position: fixed` under the button (or above it when there is more room above), so it is not clipped by cards with `overflow`. It follows scroll and resize while open. The list shows about 12 rows and then scrolls.
- On open the search box is cleared (all options visible again) and focused.
- Typing in the search box hides every option whose **label** does not contain the text (case-insensitive substring). The option value is not searched.
- Clicking an option sets the hidden input to its `data-value`, copies its text to the button, closes the panel and dispatches a bubbling `change` event on the hidden input.
- A click anywhere else on the page closes the panel. `Esc` does not close it.

### Read-only and disabled display

`disabled` and `readonly` only disable the button (CSS: opacity 0.45, no pointer events; `is-readonly` adds the grey background). Inside a locked `x-dl::form` (fieldset disabled, or `mode="view"`) the button also loses its border and arrow and reads as plain text, and the hidden input is excluded from the submit by the browser.

### Add New (prop `add`)

With `add` the select is wrapped in a flex row and a square button with a plus icon sits to the right of the dropdown, same height (theme and density through the tokens). The button is a real `<button>`: reachable with `Tab`, `aria-label` and tooltip = `add.label`. Inside a locked `x-dl::form` (fieldset disabled, or `mode="view"`) it is hidden.

- Click opens a small `x-dl::modal` titled `add.title` with the `fields` (built from `x-dl::field.text`), an error area and **Save** / **Cancel**. At page start the script moves the popup to `<body>`, so its inputs are never part of the document `<form>` (not validated, not submitted, not counted by the dirty guard). Each select has its own popup id (`<uid>-add`), so several selects with `add` can live on one page. In an iframe the popup is moved to that frame's own body.
- The script reads `data-url` of the wrapper on every Save (same-origin check each time), so a page may change it, for example to bind the new item to another choice: `wrapper.dataset.url = '/x?parent=7'`.
- A `radio` or `select` field in the popup is a normal `x-dl::field.radio` / `x-dl::field.select`; after Save it returns to its initial `value`, the text fields are cleared.
- `Enter` in a field (not in a textarea) saves; `Esc`, the X, **Cancel** or a click outside closes. Whatever closes it, the focus returns to the select button.
- Save checks the `required` fields (message "Required" under the field, no request), then sends `POST` JSON to `add.url`. While the request runs the button is disabled and reads "Saving..." (a double click or Enter+click sends one request).
- Success (HTTP 2xx with the JSON below): the item is appended to the list (if its value is already in the list, that item is selected and its label refreshed, so no duplicate), the hidden input and button text are set, a bubbling `change` is dispatched on the hidden input exactly like a normal choice (dirty guard and dependent logic work), the popup closes, its fields are cleared and the focus goes back to the select. `Devlib.fill`, Undo and `setField` find the new item because it is a normal `.dl-dd__item`; `Devlib.h.setOptions` replaces the whole list, so it also drops added items.
- HTTP 422 with a Laravel `errors` object: the first message of each field is shown under that field, messages for unknown keys go to the error area, the popup stays open. Any other failure (other status, body that is not JSON, network error, cross-origin URL) shows the generic message "Could not save. Try again." and keeps the popup open and the typed values.

### Dependent dropdowns

`Devlib.h.setOptions(form, name, opts, {disabled})` replaces the options of a select that is already on the page (see Data and events). It clears the current value, resets the button text to `data-empty` and disables the button when `disabled` is true or the list is empty. Combine it with a `change` listener on the parent field.

## Data and events

### What the server receives

One request field `name` with the **option key as a string** (`''` when nothing is chosen). The button and the search input have no name and are never posted.

```php
$request->validate(['city' => ['required', Rule::in(array_keys($cities))]]);
```

### Add New: request and reply contract

Request: `POST <add.url>`, headers `Accept: application/json`, `Content-Type: application/json`, `X-Requested-With: XMLHttpRequest` and `X-CSRF-TOKEN` (from `window.DL_CSRF`, else the `<meta name="csrf-token">`); body = one JSON object with every `fields` name (values trimmed, `''` when empty), e.g. `{"name": "CV Sumber Rejeki", "phone": "", "contact": ""}`. The session cookie is sent (`credentials: same-origin`).

| Reply | Meaning |
|---|---|
| `200` / `201` + `{"id": "S9", "name": "CV Sumber Rejeki"}` | Success. The keys are `add.value-key` and `add.label-key`; the pair `{"value": ..., "label": ...}` is accepted as well. The value is turned into a string; a reply without a value or label counts as a failure. |
| `422` + `{"message": "...", "errors": {"name": ["The name has already been taken."]}}` | Validation failed (the standard Laravel reply for a JSON request). Shown under the matching field; the popup stays open. |
| anything else | Generic error text in the popup. |

### DOM events and hooks

| Hook | Detail |
|---|---|
| `change` | Dispatched (bubbles) on the hidden input `[data-dl-value]` after a click on an option. `e.target.name` is the field name, `e.target.value` the key. Not fired by `setOptions`, `Devlib.setField` or `Devlib.fill`. There are no `dl:*` events. |
| `data-dl-select-add`, `data-dl-sa-btn`, `data-dl-sa-modal`, `data-dl-sa-err`, `data-dl-sa-save` | Add New only: wrapper (with `data-url`, `data-value-key`, `data-label-key`), plus button, popup (a `[data-dl-modal-id]`), error area, Save button. `el.dlComponent` of the wrapper is the `SelectAdd` instance. |
| `data-dl-select` | The `.dl-dd` element; present on the select control only (a lookup uses `data-dl-lookup`). |
| `data-dl-value`, `data-dl-dd-text`, `data-dl-dd-toggle`, `data-dl-dd-panel`, `data-dl-dd-search`, `data-dl-dd-list` | Hidden input, button text, button, panel, search input, option list. |
| `.dl-dd__item[data-value]` | One option. |
| `data-empty` | Placeholder text of this select. |
| classes | `is-open` on the panel; `is-invalid`, `is-readonly` on the root. |
| `data-dl-field="<name>"` | On the root; used by `Devlib.fill`, lookup `fill` and the form validation. |
| `el.dlComponent` | On the root `.dl-field` it is the shared `Field` instance (`sel` holds the select element). It has no value API; use the hidden input. |

### JavaScript helpers

```js
// dependent dropdown; opts = [[value, label], ...] or {value: label}
Devlib.h.setOptions(form, 'city', [['sby', 'Surabaya'], ['mlg', 'Malang']], {disabled: false});

// set the value from script; updates the hidden input and the button text, no change event
Devlib.setField(form, 'city', 'mlg');   // Devlib.fill(form, {city: 'mlg'}) does the same for many fields
```

`Devlib.fill` / `setField` find the item with `data-value` equal to the value; if there is none the button shows `data-empty` (the hidden input still holds the value). The wizard review reads the button text of the select; `data-dl-echo` reads the text of the chosen option item.

### Form integration

- The form's Save/Confirm check marks the field "Required" when the hidden input is empty; the error is cleared on `input` or `change`, and a choice fires `change`.
- A change marks the guarded form dirty (the hidden input has a name). Undo and Cancel-in-view-mode restore the select through `fillForm`.
- Lint F201 (warning): in a `kind="document"` form a `field.select` without `value`, `readonly` or `options-default` is reported ("input without an initial value"); a new record should carry sensible defaults.

## Rules and pitfalls

- Pass the **key** as `value`, not the label. For a list of strings key and label are the same text.
- A `value` that is not among the options is still written to the hidden input and posted, while the button shows the placeholder. Validate on the server and only pass values that exist.
- There is no clear button. If the value may be emptied again, include an option with an empty key.
- `setOptions` re-enables the button when the new list is not empty and `disabled` is not passed, even if the field was rendered with `disabled`. It also does not fire `change`, so a third dependent level must be refreshed by your own code.
- JSON objects reorder integer-like keys in JavaScript. When you pass options for `setOptions` as an object (`{10: 'A', 2: 'B'}`) the order is lost; use the pair form `[[value, label], ...]`.
- Option labels are data: they are not translated. Translate them yourself (`Lang::text`) when needed. Remember `placeholder`, `label`, `hint` and `tooltip` **are** translated, so wrap data values in `raw()`.
- Every option is in the DOM: a few hundred is fine, thousands slow the page. Use `x-dl::field.lookup` with a server `search` instead.
- Keyboard: the button can be reached with `Tab` and opened with `Enter`/`Space`, and the search box receives focus, so typing filters. The options are plain `<div>` elements without `tabindex`, roles or arrow-key handling, so the choice itself needs a pointer or touch. The button has no `aria-expanded`/`aria-haspopup`.
- Add New is only a convenience in the browser: the endpoint must authenticate, authorise and validate like any other write route, and the document that uses the new value must check on Save that the value exists. The popup can be bypassed by anyone, so never rely on it.
- `add.url` must be same origin (a relative `/path` is simplest); a cross-origin URL is ignored at render time and refused again by the script.
- The endpoint is hit when the popup is saved, **not** when the document is saved: a created item stays even if the user later cancels the document. Say so in the popup `note` or make the endpoint idempotent.
- Return the same key names as `value-key` / `label-key` (or `value` / `label`). Typical mistake: returning the whole model under a wrapper key (`{"data": {...}}`); the script reads top-level keys only (use `response()->json([...])` or `JsonResource::withoutWrapping()`).
- The names in `add.fields` are the JSON keys your endpoint validates. They cannot clash with the document fields, because the popup is moved out of the form.
- The server must re-check rights and the allowed values; hiding or disabling the field in the browser is not a security measure.
- Code oddity (not fixed): `resources/views/components/field/select.blade.php:6` and `:7` - `disabled` (and `readonly`) only disable the button; the hidden input stays enabled, so a standalone `disabled` select is still submitted. Only a disabled fieldset (locked form) keeps the value out of the request.
- Code oddity (not fixed): `resources/assets/js/core.js:220-250` (`bindPanel`) - option items are not focusable and there is no arrow/Enter/Esc handling, so a select cannot be completed from the keyboard.

## Example

Plain select with a placeholder and a default (list of strings, so the value is the text):

```blade
<x-dl::field.select name="city" label="City" placeholder="Choose city..."
    :options="['Surabaya', 'Malang', 'Jakarta']" :value="old('city', $customer->city ?? 'Surabaya')"
    :error="$errors->first('city')" required />
```

Associative options with an explicit "none" entry and a custom search placeholder:

```blade
<x-dl::field.select name="status" label="Status" search-placeholder="Find status..."
    :options="['' => '- none -', 'A' => 'Active', 'I' => 'Inactive']" :value="$row->status ?? 'A'" hint="Shown in the master list." />
```

Dependent dropdown (the child starts empty and disabled):

```blade
<x-dl::field.select name="province" label="Province" placeholder="Choose province..." :options="$provinces" :value="$row->province ?? 'EAST'" />
<x-dl::field.select name="city" label="City" placeholder="Choose city..." :options="[]" disabled />

<script>
    const cities = @json($citiesByProvince);   // { EAST: [['sby', 'Surabaya'], ['mlg', 'Malang']], ... }
    document.addEventListener('change', e => {
        if (e.target.name !== 'province') return;
        Devlib.h.setOptions(e.target.closest('form'), 'city', cities[e.target.value] || [], {disabled: !e.target.value});
    });
</script>
```

Add New (supplier field with a popup: Name required, Phone, Contact):

```blade
<x-dl::field.select name="supplier_id" label="Supplier" placeholder="Choose supplier..."
    :options="$suppliers->pluck('name', 'id')->all()" :value="old('supplier_id', $doc->supplier_id)" required
    :add="[
        'url' => route('suppliers.quick', [], false),
        'title' => 'Add Supplier', 'icon' => 'fa-truck',
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'required' => true, 'maxlength' => 80],
            ['name' => 'phone', 'label' => 'Phone', 'type' => 'tel', 'maxlength' => 30],
            ['name' => 'contact', 'label' => 'Contact name', 'maxlength' => 80],
        ],
    ]" />
```

The endpoint (`Route::post('/suppliers/quick', [SupplierController::class, 'quick'])->name('suppliers.quick');` inside the `web` + `auth` group, so CSRF applies):

```php
public function quick(Request $request)
{
    $this->authorize('create', Supplier::class);
    $data = $request->validate([                       // a failure becomes the 422 JSON the popup understands
        'name' => ['required', 'string', 'max:80', Rule::unique('suppliers', 'name')],
        'phone' => ['nullable', 'string', 'max:30'],
        'contact' => ['nullable', 'string', 'max:80'],
    ]);

    $supplier = Supplier::create($data);

    return response()->json(['id' => $supplier->id, 'name' => $supplier->name], 201);
}
```

## Related

- `x-dl::field.text`: the full reference of the 14 inherited props and the shared field frame.
- `x-dl::field.multi-select`: the same dropdown with checkboxes (several values).
- `x-dl::modal`: the popup of Add New is an `x-dl::modal` (size `sm`).
- `x-dl::field.lookup`: choose a master row from a table, with auto-fill of other fields and a remote search for large data.
- `x-dl::field.radio` and `x-dl::field.toggle-group`: visible single choice for very short lists.
- `x-dl::form`: required-field check, dirty guard, Undo and the `kind="document"` default-value lint (F201).
- `x-dl::code-chain`: a set of chained selects built on `Devlib.h.setOptions`.
- `skills/devlib/form.md`, `skills/devlib/components.md`: project rules for forms and fields.
