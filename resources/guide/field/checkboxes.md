## Overview

`<x-dl::field.checkboxes>` is a group of check boxes under one field caption: the user may tick none, one or several options. It posts an array (`name[]`). For a single yes/no use `x-dl::field.checkbox`; for exactly one choice use `x-dl::field.radio` or `x-dl::field.toggle-group`; for a long list inside a dropdown use `x-dl::field.multi-select`.

It extends the abstract `Devlib\Components\Field\Field` (shared frame: caption row, control, error line, hint line) and inherits the 14 common props. The control is plain Blade (`resources/views/components/field/checkboxes.blade.php`) with no script of its own. The one own prop is `options`.

## Props

Constructor of `Devlib\Components\Field\Checkboxes` (15 props: `options` + the 14 inherited). Blade names are kebab-case; arrays need the `:` binding.

### Own prop

| Prop | Type, default | Meaning |
|---|---|---|
| `options` | `array`, `[]` | The boxes, in order. Two shapes. A plain list `['Reading', 'Sport']` makes value = label for each. An associative array `['rd' => 'Reading', 'sp' => 'Sport']` makes key = posted value, value = label. A list is detected with `array_is_list()`, so keys `0..n-1` in order count as a list (see pitfalls). Labels are HTML-escaped and not translated. An empty array renders no boxes. |

### Inherited props

Full reference: `x-dl::field.text`. What they mean for this control:

| Prop | Type, default | Effect here |
|---|---|---|
| `name` | `string`, `''` | Written as `name="<name>[]"` on every box (write it WITHOUT brackets), `data-dl-field="<name>"` on the root, and the slug of the generated id. |
| `label` | `string`, `''` | Group caption above the boxes. Empty = no caption row, and then no `*` and no tooltip. The caption is tied to the group with `aria-labelledby` (see Accessibility). |
| `tooltip` | `?string`, `null` | (i) bubble next to the caption; only with a non-empty `label`. |
| `hint` | `?string`, `null` | Help line under the group; rendered whenever not `null`. |
| `error` | `?string`, `null` | Server error text under the group; non-empty adds `is-invalid` on the root (message only, there is no bordered box). |
| `value` | `mixed`, `null` | The ticked options: an array of option values, or a single scalar (cast with `(array)`). Each entry is cast to string and compared strictly (`in_array(..., true)`) with the option value (also cast to string), so `5` and `'5'` match. `null` = nothing ticked. |
| `placeholder` | `?string`, `null` | Not used by this control. |
| `required` | `bool`, `false` | Red `*` and form validation (Save/Confirm): "Required" when no box in the group is ticked. Needs a non-empty `label`. Not an HTML `required`, no server check. |
| `disabled` | `bool`, `false` | `disabled` on every box; nothing is posted. |
| `readonly` | `bool`, `false` | Boxes are rendered `disabled` (cannot be changed) and, when not also `disabled`, one hidden `<input name="<name>[]">` per entry of `value` is added so the current selection is still posted. Root gets `is-readonly`. Forced on while `Field::$readonlyAll` is true. |
| `icon` | `?string`, `null` | Not used by this control. |
| `size` | `string`, `'sm'` | Only changes the root class `dl-field--sm` / `dl-field--lg`. |
| `i18n` | `?string`, `null` | Written to `data-i18n` on the caption `<label>`. Does not touch the option labels. |
| `source` | `?string`, `null` | Label for the developer catalog (`source()`); default `input "<name>"`. No visual effect. |

Other HTML attributes (`id`, `class`, `data-*`) and `can="module.action"` go on the root `.dl-field` div.

## Behavior

### Markup

```html
<div class="dl-field dl-field--checkboxes dl-field--sm" data-dl-field="hobi">
  <div class="dl-field__labelrow"><label class="dl-field__label" id="dlf-hobi-xxxxx-lbl">Hobi</label></div>
  <div class="dl-choices" role="group" aria-labelledby="dlf-hobi-xxxxx-lbl">
    <label class="dl-choice"><input type="checkbox" name="hobi[]" value="Coding" checked> Coding</label>
    ...
  </div>
  <div class="dl-field__error" data-dl-field-error></div>
</div>
```

`.dl-choices` is a wrapping flex row (gap 8px), so many options flow onto several lines. Every box has its own wrapping `<label>`, so the text is clickable.

### Posted state

Only ticked boxes are sent, each as one element of `name[]`: `hobi[]=Coding&hobi[]=Sport`. With nothing ticked the key is absent (not an empty array), so read it with a default: `$request->input('hobi', [])`. Option order in the request follows DOM order.

### Required

`hasValue()` is true when any named input in the root is checked, so a required group needs at least one tick. In read-only mode the hidden mirror inputs count as a value too (non-empty value), and a disabled ticked box also counts: `required` does not look at `disabled`.

### Disabled and read-only

`disabled` blocks everything and posts nothing. `readonly` blocks clicking (boxes are `disabled`) but still posts the initial selection through hidden inputs, one per entry in `value` (also entries that match no option). In a locked `x-dl::form` the whole fieldset is disabled and nothing is posted.

### Accessibility as implemented

Native checkboxes, native keyboard (Tab to each box, Space to tick). The boxes sit in a `div` with `role="group"` and `aria-labelledby` pointing at the caption, so a screen reader announces the group name. The caption has no `for` (nothing in this control can be labelled as a whole), so clicking it does nothing; with an empty `label` there is no `aria-labelledby`. There is no `<fieldset>`/`<legend>`. Each box is named by its own option text.

## Data and events

| Item | Detail |
|---|---|
| Posted | `name[]=<value>` per ticked box; key absent when none. |
| Read on the server | `$request->input('name', [])`; validate with `'name' => 'array'` and `'name.*' => Rule::in([...])`, `'required'`/`'min:1'` if at least one is needed. |
| DOM events | `input` and `change` on each box (bubble). `x-dl::form` uses them for the dirty guard and to clear a "Required" mark on `input`. |
| Fill from code | Not supported through `Devlib.fill` / Form Undo: `fillForm` skips every name containing `[` (see oddity). |
| Wizard review | `fieldText()` joins the label texts of the ticked boxes with commas. |
| Hooks | `data-dl-field="<name>"`, `.dl-choices`, `.dl-choice`. No `data-dl-*` hook of its own and no script branch in `field.js`. |

## Rules and pitfalls

- Give `name="hobi"`, not `name="hobi[]"`; the view appends `[]`, and a name with brackets would become `hobi[][]`.
- Option keys that form the list `0, 1, 2...` are treated as a list: `[0 => 'No', 1 => 'Yes']` posts `No` / `Yes`, not `0` / `1`. Use keys that are not `0..n-1` (start from 1, or use string keys such as `'n'` / `'y'`); PHP turns numeric-string keys like `'0'` into integers.
- `value` must be an array of scalars (or one scalar). A Collection or nested arrays are not handled; call `->all()` / `->pluck('id')->all()`.
- Restoring after a failed validation: when nothing is ticked the key is absent, so `old('hobi', $m->hobi)` re-ticks the model values the user had cleared. Use `session()->hasOldInput() ? (array) old('hobi', []) : $m->hobi`.
- `required` only works with a non-empty `label`: the `*` marker the form looks for lives in the label row.
- Option labels are not translated and are not in the dictionary; translate them before passing them in.
- Code oddity (not fixed): `resources/assets/js/core.js:681` (`fillForm`) ignores field names containing `[`, so Form Undo and `Devlib.fill` never restore or clear the boxes of this control after the user changed them.

## Example

```blade
<x-dl::field.checkboxes name="hobi" label="Hobbies" :options="['Reading', 'Sport', 'Coding']" :value="['Coding']" />
```

```blade
{{-- Keys are posted, labels are shown; error and restore after a failed validation --}}
<x-dl::field.checkboxes name="channels" label="Notify by" required
    :options="['mail' => 'E-mail', 'sms' => 'SMS', 'push' => 'Push']"
    :value="session()->hasOldInput() ? (array) old('channels', []) : ($user->channels ?? ['mail'])"
    :error="$errors->first('channels')" hint="Pick at least one." />
```

```blade
{{-- Locked display: shown, not editable, still posted --}}
<x-dl::field.checkboxes name="channels" label="Notify by" :options="['mail' => 'E-mail', 'sms' => 'SMS']" :value="['mail']" readonly />
```

## Related

- `x-dl::field.checkbox`: one box with its own text.
- `x-dl::field.multi-select`: many choices in a dropdown with search.
- `x-dl::field.radio` and `x-dl::field.toggle-group`: exactly one choice.
- `x-dl::field.text`: full reference of the 14 inherited props, wrapper markup and shared behavior.
- `x-dl::form`: required-field validation, dirty guard, Undo and locked fieldsets.
