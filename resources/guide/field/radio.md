## Overview

`<x-dl::field.radio>` is a single choice shown as a row of radio buttons: all options are visible at once and exactly one can be selected. Use it for short lists (gender, type, yes/no). For a long list use `x-dl::field.select`; for a segmented button look use `x-dl::field.toggle-group`; for several answers use `x-dl::field.checkboxes`.

It extends the abstract `Devlib\Components\Field\Field` (shared frame: caption row, control, error line, hint line) and inherits the 14 common props. The control is plain Blade (`resources/views/components/field/radio.blade.php`) with no script of its own. The one own prop is `options`.

## Props

Constructor of `Devlib\Components\Field\Radio` (15 props: `options` + the 14 inherited). Blade names are kebab-case; arrays need the `:` binding.

### Own prop

| Prop | Type, default | Meaning |
|---|---|---|
| `options` | `array`, `[]` | The buttons, in order. A plain list `['Male', 'Female']` makes value = label. An associative array `['M' => 'Male', 'F' => 'Female']` makes key = posted value, value = label. A list is detected with `array_is_list()`, so keys `0..n-1` in order count as a list (see pitfalls). Labels are HTML-escaped and not translated. |

### Inherited props

Full reference: `x-dl::field.text`. What they mean for this control:

| Prop | Type, default | Effect here |
|---|---|---|
| `name` | `string`, `''` | `name` shared by all radios of the group (that is what makes them one choice), `data-dl-field` on the root, slug of the generated id. |
| `label` | `string`, `''` | Group caption above the buttons. Empty = no caption row, and then no `*` and no tooltip. The `<label for>` points at an id that no element carries (see Behavior). |
| `tooltip` | `?string`, `null` | (i) bubble next to the caption; only with a non-empty `label`. |
| `hint` | `?string`, `null` | Help line under the group; rendered whenever not `null`. |
| `error` | `?string`, `null` | Server error text; non-empty adds `is-invalid` on the root (message only, no bordered box). |
| `value` | `mixed`, `null` | The selected option value. Compared as strings (`(string) $value === (string) $key`), so `1` and `'1'` match. No match or `null` = nothing selected; the first option is NOT selected for you. |
| `placeholder` | `?string`, `null` | Not used by this control. |
| `required` | `bool`, `false` | Red `*` and form validation (Save/Confirm): "Required" when no radio of the group is selected. Needs a non-empty `label`. Not an HTML `required`, no server check. |
| `disabled` | `bool`, `false` | `disabled` on every radio; nothing is posted. |
| `readonly` | `bool`, `false` | Radios are rendered `disabled`; when not also `disabled` and `value` is not `null`, a hidden `<input name="<name>" value="<value>">` is added so the selection is still posted. Root gets `is-readonly`. Forced on while `Field::$readonlyAll` is true. |
| `icon` | `?string`, `null` | Not used by this control. |
| `size` | `string`, `'sm'` | Only changes the root class `dl-field--sm` / `dl-field--lg`. |
| `i18n` | `?string`, `null` | Written to `data-i18n` on the caption `<label>`. Does not touch the option labels. |
| `source` | `?string`, `null` | Label for the developer catalog (`source()`); default `input "<name>"`. No visual effect. |

Other HTML attributes (`id`, `class`, `data-*`) and `can="module.action"` go on the root `.dl-field` div.

## Behavior

### Markup

```html
<div class="dl-field dl-field--radio dl-field--sm" data-dl-field="gender">
  <div class="dl-field__labelrow"><label class="dl-field__label" for="dlf-gender-xxxxx">Gender</label></div>
  <div class="dl-choices">
    <label class="dl-choice"><input type="radio" name="gender" value="M" checked> Male</label>
    <label class="dl-choice"><input type="radio" name="gender" value="F"> Female</label>
  </div>
  <div class="dl-field__error" data-dl-field-error></div>
</div>
```

`.dl-choices` is a wrapping flex row (gap 8px). Every radio has its own wrapping `<label>`, so the text is clickable.

### Posted state

The selected option posts `name=<value>`. When nothing is selected nothing is posted (key absent). Once the user picks an option the browser gives no way to clear it again; offer an explicit "none" option if that is a valid answer.

### Required

`hasValue()` is true when any named input in the root is checked. A required group needs a selection; with an initial `value` it is satisfied from the start. In read-only mode the hidden mirror also counts (non-empty value).

### Disabled and read-only

`disabled` blocks everything and posts nothing. `readonly` really blocks clicking (the radios carry `disabled`) and keeps the current value in the post through the hidden input (only when `value` is not `null`). In a locked `x-dl::form` the whole fieldset is disabled and nothing is posted.

### Accessibility as implemented

Native radios: the browser provides the group semantics for the shared `name`, Tab moves into and out of the group, arrow keys move the selection. There is no `<fieldset>`/`<legend>` and no `role="radiogroup"`; the caption is a `<label for>` pointing at an id that no element in this control has, so it does not name the group for a screen reader and clicking it does nothing. Each radio is named by its own option text.

## Data and events

| Item | Detail |
|---|---|
| Posted | `name=<option value>`; key absent when nothing is selected. |
| Read on the server | `$request->input('name')`; validate with `'name' => ['required', Rule::in([...])]`. |
| DOM events | `change` and `input` on the newly selected radio (bubble). `x-dl::form` uses them for the dirty guard and to clear a "Required" mark on `input`. |
| Fill from code | `Devlib.fill(form, {gender: 'F'})` / `Devlib.h.setField(form, 'gender', 'F')` checks the radio whose value equals the string and unchecks the others. It fires no event. Form Undo uses the same path. |
| Wizard review | `fieldText()` shows the label text of the checked radio. |
| Hooks | `data-dl-field="<name>"`, `.dl-choices`, `.dl-choice`. No `data-dl-*` hook of its own and no script branch in `field.js`. |

## Rules and pitfalls

- Option keys that form the list `0, 1, 2...` are treated as a list: `[0 => 'No', 1 => 'Yes']` posts `No` / `Yes`, not `0` / `1`. Use keys that are not `0..n-1` (start from 1, or use string keys such as `'n'` / `'y'`); PHP turns numeric-string keys like `'0'` into integers.
- A list of labels posts the label itself as value. That is fine for fixed words but wrong if the label may be re-worded or translated: use an associative array with stable keys.
- An option whose key is `''` is pre-selected when `value` is `null` (`(string) null === ''`). Do not use an empty key as a real answer; add the "none" option with a non-empty key or leave the group unselected.
- Restoring after a failed validation: `old('gender', $m->gender)` works as usual, because a selected radio always posts. When nothing was selected the key is absent and the model value comes back.
- `required` only works with a non-empty `label`: the `*` marker the form looks for lives in the label row.
- Option labels are not translated and are not in the dictionary; translate them before passing them in.
- No default selection: set `value` to the default key if one choice must be pre-selected.
- Code oddity (not fixed): `resources/views/components/field.blade.php:4` writes `for="<uid>"` on the caption, but `radio.blade.php` puts no `id` on any element, so the caption is a dangling label and the group has no accessible group name (same in `checkboxes` and `toggle-group`).

## Example

```blade
{{-- Associative options: keys are posted, labels are shown --}}
<x-dl::field.radio name="gender" label="Gender" :options="['M' => 'Male', 'F' => 'Female']" :value="$emp->gender ?? null" />
```

```blade
{{-- A list: value = label --}}
<x-dl::field.radio name="gender" label="Gender" :options="['Male', 'Female']" value="Male" />
```

```blade
{{-- Required, error from the server, value restored after a failed validation --}}
<x-dl::field.radio name="priority" label="Priority" required
    :options="['L' => 'Low', 'N' => 'Normal', 'H' => 'High']"
    :value="old('priority', $ticket->priority ?? 'N')"
    :error="$errors->first('priority')" />
```

## Related

- `x-dl::field.toggle-group`: one choice as segmented buttons (always posts its hidden input, an empty string when nothing is selected).
- `x-dl::field.select`: one choice from a long list in a dropdown.
- `x-dl::field.checkboxes`: several answers.
- `x-dl::field.text`: full reference of the 14 inherited props, wrapper markup and shared behavior.
- `x-dl::form`: required-field validation, dirty guard, Undo and locked fieldsets.
