## Overview

`<x-dl::field.checkbox>` is a single check box with its own text beside it ("Remember me", "I agree to the terms"). It is a yes/no input: ticked posts `name=1`, unticked posts nothing. For several independent boxes under one name use `x-dl::field.checkboxes`; for an on/off switch with a state word use `x-dl::field.toggle`.

It extends the abstract `Devlib\Components\Field\Field`, so it sits in the shared field frame (label row with `*` and tooltip, control, error line, hint line) and inherits the 14 common props. The control itself is one line of Blade (`resources/views/components/field/checkbox.blade.php`); the only own prop is `text`. There is no JavaScript of its own: the form scripts read it through the generic `change` / `input` events and `hasValue()`.

Note the two texts: `label` is the field caption above the box (optional), `text` is the sentence next to the box (clickable).

## Props

Constructor of `Devlib\Components\Field\Checkbox` (15 props: `text` + the 14 inherited). Blade names are kebab-case; use `:prop="expr"` for anything that is not a plain string.

### Own prop

| Prop | Type, default | Meaning |
|---|---|---|
| `text` | `string`, `''` | Text printed after the box inside the same `<label class="dl-choice dl-choice--single">`, so clicking it toggles the box. It is a standard text prop: translated through the dictionary (wrap data in `raw()`), HTML-escaped on output. |

### Inherited props

Full reference: `x-dl::field.text`. What they mean for this control:

| Prop | Type, default | Effect here |
|---|---|---|
| `name` | `string`, `''` | `name` of the `<input type="checkbox">`, `data-dl-field` on the root, and the slug of the generated id (`dlf-<name>-<rand5>`). No name = nothing is posted. |
| `label` | `string`, `''` | Caption row above the box; `<label for>` points at the checkbox id, so clicking the caption also toggles it. Empty = no row, and then no `*` and no tooltip. |
| `tooltip` | `?string`, `null` | (i) bubble next to the caption; only with a non-empty `label`. |
| `hint` | `?string`, `null` | Help line under the control; rendered whenever not `null`. |
| `error` | `?string`, `null` | Server error text under the control; non-empty adds `is-invalid` on the root. Only the message turns red: there is no `.dl-field__box` border to colour on this control. |
| `value` | `mixed`, `null` | Checked state, evaluated with PHP truthiness (`@checked($value)`): `true`, `1`, `'1'`, `'yes'`, `'false'` are checked; `false`, `0`, `'0'`, `''`, `null` are not. The posted value is always the literal `1`, whatever `value` is. |
| `placeholder` | `?string`, `null` | Not used by this control. |
| `required` | `bool`, `false` | Red `*` after the caption and form validation (Save/Confirm): "Required" unless the box is ticked, i.e. "must be ticked". Needs a non-empty `label` (see pitfalls). Not an HTML `required`, no server check. |
| `disabled` | `bool`, `false` | `disabled` on the input; not posted. |
| `readonly` | `bool`, `false` | Also renders the input `disabled` (a native checkbox has no read-only state), and root gets `is-readonly`. When it is ticked and not also `disabled`, a hidden `<input name="<name>" value="1">` is written (as in `x-dl::field.toggle`), so the value is still posted. Forced on while `Field::$readonlyAll` is true. |
| `icon` | `?string`, `null` | Not used by this control. |
| `size` | `string`, `'sm'` | Only changes the root class `dl-field--sm` / `dl-field--lg`. |
| `i18n` | `?string`, `null` | Written to `data-i18n` on the caption `<label>` (auth screens re-translate it on language change). Does not touch `text`. |
| `source` | `?string`, `null` | Label for the developer catalog (`source()`); default `input "<name>"`. No visual effect. |

Any other HTML attribute (`id`, `class`, `data-*`) and `can="module.action"` go on the root `.dl-field` div, not on the input.

## Behavior

### Markup

```html
<div class="dl-field dl-field--checkbox dl-field--sm" data-dl-field="agree">
  <div class="dl-field__labelrow"><label class="dl-field__label" for="dlf-agree-xxxxx">Persetujuan</label><span class="dl-field__req">*</span></div>
  <label class="dl-choice dl-choice--single"><input id="dlf-agree-xxxxx" type="checkbox" name="agree" value="1" checked> text</label>
  <div class="dl-field__error" data-dl-field-error></div>
</div>
```

The box uses the browser's native checkbox with `accent-color` set to the theme accent. The root always carries `data-dl`, `data-dl-class` and `data-dl-src` (catalog hooks).

### Posted state

An unticked HTML checkbox sends nothing, and the view writes no hidden "0" fallback. A ticked box sends exactly `name=1`. So the server must treat "key absent" as false, for example `$request->boolean('agree')`. The `value="1"` is fixed; the control cannot post another string.

### Required

`required` is evaluated by `x-dl::form` (and wizard/register/forgot) through `hasValue(field)`: for a checkbox it is `input.checked`. A required checkbox therefore means "must be ticked" (typical for "I agree"). The check reads the control even when it is disabled.

### Disabled and read-only

Both set the `disabled` attribute, so the box cannot be clicked or focused and the box itself is never posted. `readonly` additionally writes a hidden `<input name="<name>" value="1">` when the box is ticked, so a ticked read-only box still posts `name=1` (an unticked one posts nothing), and adds the `is-readonly` class; `disabled` posts nothing. Inside a locked `x-dl::form` (disabled fieldset) the box is disabled in the same way.

### Accessibility as implemented

A real `<input type="checkbox">`: keyboard (Tab, Space), label click and screen-reader state are native. The accessible name comes from `text` (wrapping label); the caption `label` is linked through `for` to the same input, so the control has both. There is no `role`, `aria-*` or `fieldset`.

## Data and events

| Item | Detail |
|---|---|
| Posted | `name=1` when ticked; nothing when not. |
| Read on the server | `$request->boolean('name')`; validate with `'name' => 'accepted'` (must be ticked) or `'boolean'`. |
| DOM events | `input` and `change` (bubble) on the checkbox. `x-dl::form` uses them for the dirty guard (`is-dirty`) and clears a "Required" mark on `input`. |
| Fill from code | `Devlib.fill(form, {agree: true})` / `Devlib.h.setField(form, 'agree', v)`: `true`, `'1'` and `'true'` tick the box (anything else, such as `'on'`, unticks), then a `change` event is fired. Form Undo uses the same path with a boolean. |
| Wizard review | `fieldText()` shows the label text of the ticked box. |
| Hooks | `data-dl-field="<name>"`, `.dl-choice--single`, `[data-dl-field-error]`. No `data-dl-*` hook of its own and no script branch of its own in `field.js`. |

## Rules and pitfalls

- Pass a real boolean: `:value="(bool) $m->agree"`. A string such as `'false'` or `'off'` is truthy and renders ticked; a numeric `'0'` is unticked.
- `required` only works with a non-empty `label`. The marker `.dl-field__req` that the form looks for is rendered only inside the label row, so `<x-dl::field.checkbox text="I agree" required />` shows no `*` and is never validated. Give it a `label` (it may be short, for example "Consent").
- Restoring after a failed validation: an unticked box posts nothing, so `old('agree', $m->agree)` falls back to the model value and re-ticks a box the user had cleared. Use `session()->hasOldInput() ? (bool) old('agree') : (bool) $m->agree`.
- Never trust the browser for "must be ticked": validate with `accepted` on the server; the form has `novalidate`.
- `text` and `label` are translated, so wrap data values (names, numbers) in `raw()`.
- Do not add your own `<input type="hidden" name="same_name" value="0">` to get a "0" for unticked: `Devlib.h.setField` acts on the first element with that name (the hidden one), so Undo and `Devlib.fill` would no longer tick the box. Read "absent = false" on the server instead.
- A ticked read-only box is posted as `name=1` through a hidden mirror, so saving a locked form does not turn the answer into "false".

## Example

```blade
{{-- Plain boxes --}}
<x-dl::field.checkbox name="remember" text="Remember me" />
<x-dl::field.checkbox name="agree" label="Consent" text="I agree with the terms and conditions" :value="true" />
```

```blade
{{-- Required, server error and restore after a failed validation --}}
<x-dl::field.checkbox name="agree" label="Consent" text="I agree with the terms and conditions" required
    :value="session()->hasOldInput() ? (bool) old('agree') : (bool) ($customer->agree ?? false)"
    :error="$errors->first('agree')" hint="Required to continue." />
```

```blade
{{-- Locked display inside a form --}}
<x-dl::field.checkbox name="domisili_same" label="Domicile" text="Same as ID card address" :value="(bool) $m->domisili_same" disabled />
```

## Related

- `x-dl::field.checkboxes`: several boxes under one name (`name[]`).
- `x-dl::field.toggle`: on/off switch with a visible state word.
- `x-dl::field.text`: full reference of the 14 inherited props, wrapper markup and shared behavior.
- `x-dl::form`: required-field validation, dirty guard, Undo and locked (disabled) fieldsets.
- `x-dl::field.radio`, `x-dl::field.toggle-group`: single choice among several.
