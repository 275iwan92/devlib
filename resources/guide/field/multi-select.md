## Overview

`<x-dl::field.multi-select>` is a dropdown where the user ticks any number of options. It renders a button that opens a panel (search input + a list of checkboxes). The button text summarises the ticked options; the values are posted as an array. Use it for "choose all that apply" lists (languages, skills, allowed branches). For one value use `x-dl::field.select`; for a few always-visible checkboxes use `x-dl::field.checkboxes`; for free-text labels use `x-dl::field.tags`.

It sits in the shared field frame (`Devlib\Components\Field\Field`): label row, the control, the error line and the optional hint. Extra HTML attributes (`id`, `class`, `data-*`) land on the root `<div class="dl-field dl-field--multi-select ...">`. `can="module.action"` removes the whole field when the user lacks the ability.

### What it is not

- Not a native `<select multiple>`: the real inputs are checkboxes inside the panel.
- Not a server search: every option is rendered into the page.
- Not a validator: the server must check the posted values against the allowed list.

## Props

Constructor of `Devlib\Components\Field\MultiSelect` (17 props). Blade names are kebab-case; use `:prop="expr"` for arrays.

### Own props

| Prop | Type, default | Meaning |
|---|---|---|
| `options` | `array`, `[]` | The choices. A list (`['Indonesian', 'English']`) means value = label = the item; an associative array is `value => label`. Each option is a `<label class="dl-dd__item"><span>label</span><input type="checkbox" name="<name>[]" value="<value>"></label>`. Labels are escaped and **not** translated. Nested arrays (groups) are not supported. |
| `empty-text` | `string`, `'Choose (0 Selected)'` | Button text while nothing is ticked. Translated (`Lang::text`); also written to `data-empty`. |
| `noun` | `string`, `'Selected'` | The word in the summary of two or more ticked options: `2 Selected (a, b)`. Translated; written to `data-noun`. |

### Inherited props

Defined once in `Field`; the full reference is in `x-dl::field.text`. Differences for this control:

| Prop | Type, default | In this field |
|---|---|---|
| `name` | `string`, `''` | Base name. Every checkbox is named `<name>[]`, so write the name **without** brackets. Also `data-dl-field` on the root and the slug of the generated id placed on the button (the `<label for>` target). |
| `label` | `string`, `''` | Label text (translated). Empty = no label row. |
| `tooltip` | `?string`, `null` | Text of the (i) bubble (translated); only with a non-empty `label`. |
| `hint` | `?string`, `null` | Help line under the control (translated). |
| `error` | `?string`, `null` | Server error text; non-empty adds `is-invalid`. Not translated. |
| `value` | `mixed`, `null` | The ticked values: an array of option keys (a single scalar is treated as a one-item array, `null` as none). Compared as strings, strictly. Typical: `old('x', $model->x ?? [])`. |
| `placeholder` | `?string`, `null` | **Not used.** The button text comes from `empty-text`. |
| `required` | `bool`, `false` | Red `*` and the `x-dl::form` "Required" check (at least one box ticked). No HTML `required`, no server check. |
| `disabled` | `bool`, `false` | Disables the **button**, so the panel cannot be opened, and the checkboxes, so nothing is posted. |
| `readonly` | `bool`, `false` | Same as `disabled` on the button, plus the class `is-readonly`; the checkboxes stay enabled, so the ticked values are posted. Forced on while `Field::$readonlyAll` is true. |
| `icon` | `?string`, `null` | Not used by this control. |
| `size` | `string`, `'sm'` | `sm` or `lg`; only the CSS class `dl-field--sm\|lg`. |
| `i18n` | `?string`, `null` | Written to `data-i18n` on the label for auth screens. |
| `source` | `?string`, `null` | Free text for the developer catalog (default `input "<name>"`); no visual effect. |

## Behavior

### Markup

```
div.dl-field.dl-field--multi-select[data-dl-field=name]
  label[for=uid] ...
  div.dl-dd[data-dl-multi][data-empty][data-noun]
    button#uid.dl-dd__btn[data-dl-dd-toggle] > span[data-dl-dd-text] + ▼
    div.dl-dd__panel[data-dl-dd-panel]
      div.dl-dd__search > input[data-dl-dd-search]               (no name, not posted)
      div.dl-dd__list > label.dl-dd__item.dl-dd__item--split > span + input[type=checkbox][name="name[]"]...
```

### Opening and searching

The shared `Devlib.h.bindPanel` helper opens and closes the panel: a click on the button toggles it and closes every other open dropdown; a click outside closes it; clicks inside the panel do not close it (the user keeps ticking). The panel is `position: fixed` under (or above) the button so cards with `overflow` do not clip it; the list shows about 12 rows and scrolls. `Esc` does not close it.

The search box hides the options whose label does not contain the typed text (case-insensitive). Its placeholder is fixed (`Search...`, translated) - there is no prop for it. Unlike `x-dl::field.select`, opening the panel neither clears the search box nor focuses it, so a previous filter stays until it is erased. Ticked options that are filtered out stay ticked and are still posted.

### Button summary

After every change the script rebuilds the button text from the ticked boxes:

| Ticked | Text |
|---|---|
| none | `empty-text` |
| one | that option's **value** |
| two or more | `<n> <noun> (<value1>, <value2>, ...)` |

The summary uses the option **label** (the checkbox `data-label`), not the key, so an associative `options` shows the labels. The text is computed once at page load, so the initial `value` is reflected at once.

### Read-only and disabled

The button is disabled (opacity 0.45) for both flags and `disabled` also disables the checkboxes (nothing is posted); `readonly` adds the grey `is-readonly` background and keeps the checkboxes enabled (posted). In a locked `x-dl::form` (disabled fieldset, or `mode="view"`) the button reads as plain text with its summary and the checkboxes are excluded from the submit by the browser.

## Data and events

### What the server receives

One repeated request field `name[]` per ticked checkbox, each with the option key as a string. **When nothing is ticked, the field is absent from the request** (not an empty array), so read it with a default and treat "absent" as "empty selection":

```php
$langs = $request->input('languages', []);        // [] when nothing was ticked
$request->validate(['languages' => ['array'], 'languages.*' => [Rule::in(array_keys($all))]]);
```

### DOM events and hooks

| Hook | Detail |
|---|---|
| `change` / `input` | Native events of the clicked checkbox (they bubble; `e.target.name` is `<name>[]`, `e.target.checked`, `e.target.value`). There is no single event for "the selection changed"; read the ticked boxes: `form.querySelectorAll('input[name="languages[]"]:checked')`. There are no `dl:*` events. |
| `data-dl-multi` | The `.dl-dd` element of this control (`data-empty`, `data-noun` hold the two texts). |
| `data-dl-dd-toggle`, `data-dl-dd-panel`, `data-dl-dd-text`, `data-dl-dd-search` | Button, panel, button text, search input. |
| `data-dl-field="<name>"` | Root attribute (used by `Devlib.fill`, validation). |
| classes | `is-open` on the panel; `is-invalid`, `is-readonly` on the root. |
| `el.dlComponent` | On the root `.dl-field` it is the shared `Field` instance (`multi` holds the `.dl-dd` element). No value API. |

### Form integration

- The Save/Confirm check marks the field "Required" when no checkbox is ticked; ticking a box clears the error again (the checkbox fires `input`).
- A change marks a guarded form dirty.
- `Devlib.fill`, `Devlib.setField` and the form's Undo / Cancel-in-view-mode (`fillForm`) restore the checkboxes: the key `<name>[]` (or `<name>`) takes an array of the values to tick; the summary follows through the `change` event the restore dispatches. `clear=true` unticks all when the key is absent.
- The extra QR button of a plain `x-dl::form` merges the repeated `name[]` values into one array.

## Rules and pitfalls

- Write the `name` without `[]`; `name="x[]"` becomes `x[][]`.
- An empty selection is simply missing in the request. Update stored data with `$request->input('x', [])`, otherwise a user can never clear the last value.
- `value` must be an array of keys. Keys are compared as strings, so `1` and `'1'` match; a value that is not an option is ignored (nothing is ticked, nothing is posted for it).
- The summary shows keys, not labels (see above). If the key is a code (`'ID'`) and the label a description, the user sees the code in the button. Use a list of readable strings as keys when the summary should read well.
- `disabled` keeps the values out of the request; `readonly` posts them. A disabled fieldset also stops them.
- Script changes must go through the checkboxes: set `checked`, then dispatch `new Event('change', {bubbles: true})` on one of them so the summary and the dirty guard update.
- All options are in the DOM; thousands of options make the page slow.
- Option labels are data and not translated; `empty-text` and `noun` are.
- Keyboard: the button is reachable with `Tab` and opens with `Enter`/`Space`; after opening, `Tab` moves into the search box and then through the checkboxes, and `Space` ticks one. There is no `aria-expanded` and no `Esc` to close; the panel closes on an outside click.

## Example

List of strings (value = label) with defaults from the model:

```blade
<x-dl::field.multi-select name="languages" label="Languages" :options="['Indonesian', 'English', 'Mandarin', 'Japanese']"
    :value="old('languages', $employee->languages ?? ['Indonesian'])" :error="$errors->first('languages')" />
```

Associative options and custom texts:

```blade
<x-dl::field.multi-select name="branches" label="Allowed branches" tooltip="Leave empty for all branches."
    :options="['SBY' => 'Surabaya', 'MLG' => 'Malang', 'JKT' => 'Jakarta']" :value="$user->branches ?? []"
    empty-text="All branches" noun="branches" required />
```

Reading it in the controller:

```php
$branches = $request->input('branches', []);   // absent when nothing is ticked
```

## Related

- `x-dl::field.text`: the full reference of the 14 inherited props and the shared field frame.
- `x-dl::field.select`: single choice with the same panel and a search box.
- `x-dl::field.checkboxes`: a visible group of checkboxes (no dropdown).
- `x-dl::field.tags`: free-text labels, posted as `name[]` too.
- `x-dl::form`: required check, dirty guard and Undo.
- `skills/devlib/form.md`, `skills/devlib/components.md`: project rules for forms and fields.
