## Overview

`<x-dl::field.toggle>` is an on/off switch with a state word next to it ("Active" / "Non-Active" by default). It is the same data as a single check box (on posts `name=1`, off posts nothing) but shown as a sliding switch that names its own state. Use it for a setting that applies at once in the reader's mind (active, PPN, notify me); use `x-dl::field.checkbox` for an acknowledgement ("I agree") and `x-dl::field.toggle-group` when there are two or more named alternatives.

It extends the abstract `Devlib\Components\Field\Field` (shared frame: caption row, control, error line, hint line) and inherits the 14 common props. The view is `resources/views/components/field/toggle.blade.php`; the state word is kept in step by the `data-dl-toggle` branch of the shared `Field` class in `resources/assets/js/components/field.js`. Own props: `on-label`, `off-label`.

## Props

Constructor of `Devlib\Components\Field\Toggle` (16 props: `on-label`, `off-label` + the 14 inherited). Blade names are kebab-case; use `:prop="expr"` for anything that is not a plain string.

### Own props

| Prop | Type, default | Meaning |
|---|---|---|
| `on-label` | `string`, `'Active'` | Word shown while the switch is on. Written to `data-on` on the switch row and used as the initial text when `value` is truthy. Not translated (not a standard text prop): pass the final text. |
| `off-label` | `string`, `'Non-Active'` | Word shown while the switch is off. Written to `data-off`; same rules as `on-label`. |

### Inherited props

Full reference: `x-dl::field.text`. What they mean for this control:

| Prop | Type, default | Effect here |
|---|---|---|
| `name` | `string`, `''` | `name` of the checkbox inside the switch, `data-dl-field` on the root, slug of the generated id. No name = nothing is posted. |
| `label` | `string`, `''` | Caption above the switch; `<label for>` points at the checkbox id, so clicking the caption also flips the switch. Empty = no caption row, and then no `*` and no tooltip. |
| `tooltip` | `?string`, `null` | (i) bubble next to the caption; only with a non-empty `label`. |
| `hint` | `?string`, `null` | Help line under the control; rendered whenever not `null`. |
| `error` | `?string`, `null` | Server error text; non-empty adds `is-invalid` on the root (message only, no bordered box). |
| `value` | `mixed`, `null` | On/off state with PHP truthiness (`@checked($value)` and `$value ? $onLabel : $offLabel`): `true`, `1`, `'1'`, `'yes'`, `'false'` are ON; `false`, `0`, `'0'`, `''`, `null` are OFF. The posted value is always the literal `1`. |
| `placeholder` | `?string`, `null` | Not used by this control. |
| `required` | `bool`, `false` | Red `*` and form validation (Save/Confirm): "Required" while the switch is OFF, i.e. "must be on". Needs a non-empty `label`. Rarely what you want for a setting. Not an HTML `required`, no server check. |
| `disabled` | `bool`, `false` | `disabled` on the checkbox; the switch cannot be flipped and nothing is posted. |
| `readonly` | `bool`, `false` | Checkbox rendered `disabled`; when not also `disabled` and `value` is truthy, a hidden `<input name="<name>" value="1">` is added so an ON state is still posted (OFF posts nothing, as usual). Root gets `is-readonly`. Forced on while `Field::$readonlyAll` is true. |
| `icon` | `?string`, `null` | Not used by this control. |
| `size` | `string`, `'sm'` | Only changes the root class `dl-field--sm` / `dl-field--lg`. |
| `i18n` | `?string`, `null` | Written to `data-i18n` on the caption `<label>`. Does not touch the on/off words. |
| `source` | `?string`, `null` | Label for the developer catalog (`source()`); default `input "<name>"`. No visual effect. |

Other HTML attributes (`id`, `class`, `data-*`) and `can="module.action"` go on the root `.dl-field` div.

## Behavior

### Markup

```html
<div class="dl-field dl-field--toggle dl-field--sm" data-dl-field="status">
  <div class="dl-field__labelrow"><label class="dl-field__label" for="dlf-status-xxxxx">Account status</label></div>
  <div class="dl-switchrow" data-dl-toggle data-on="Active" data-off="Non-Active">
    <label class="dl-switch"><input id="dlf-status-xxxxx" type="checkbox" name="status" value="1" checked><span class="dl-switch__slider"></span></label>
    <span class="dl-switchrow__label" data-dl-toggle-label>Active</span>
  </div>
  <div class="dl-field__error" data-dl-field-error></div>
</div>
```

The visible switch is the `.dl-switch__slider` span (36 x 18 px, accent colour when on); the real checkbox is transparent (`opacity:0; width:0; height:0`) but still in the DOM and focusable.

### State word

On `change` of the checkbox the shared `Field` script sets the text of `[data-dl-toggle-label]` to `data-on` or `data-off`. The initial text is rendered by the server from `value`. Anything that changes the checkbox through `Devlib.h.setField` (Form Undo, `Devlib.fill`) fires `change`, so the word follows.

### Posted state

ON posts `name=1`; OFF posts nothing and no hidden "0" fallback is written. The server must treat an absent key as OFF: `$request->boolean('status')`. The on/off words are display only and are never posted.

### Required

`hasValue()` is true when a named input in the root is checked (or, read-only, when the hidden mirror carries `1`). A required toggle therefore fails while it is OFF.

### Disabled and read-only

`disabled` and `readonly` both put `disabled` on the checkbox, so the switch cannot be clicked, focused or flipped by keyboard. `readonly` keeps an ON state in the post through the hidden input (placed before the switch). In a locked `x-dl::form` the fieldset is disabled and nothing is posted. There is no special styling for the disabled state (see oddity).

### Accessibility as implemented

A native `<input type="checkbox">` underneath: Tab focuses it, Space flips it, the caption `<label for>` names it, and screen readers announce it as a check box with checked state. There is no `role="switch"`, and the state word is plain text without `aria-live`. The focus ring cannot be seen (the input is transparent and the stylesheet has no focus style for `.dl-switch`).

## Data and events

| Item | Detail |
|---|---|
| Posted | `name=1` when on; nothing when off. |
| Read on the server | `$request->boolean('name')`; validate with `'name' => 'boolean'`. |
| DOM events | `input` and `change` (bubble) on the checkbox. The shared `Field` script listens to `change` for the state word; `x-dl::form` uses them for the dirty guard and to clear a "Required" mark on `input`. |
| Fill from code | `Devlib.fill(form, {status: true})` / `Devlib.h.setField(form, 'status', v)`: `true`, `'1'`, `'true'` switch on, anything else off; a `change` event is fired. |
| Wizard review | `fieldText()` shows the current state word (the text of `[data-dl-toggle-label]`). |
| Hooks | `data-dl-toggle`, `data-on`, `data-off`, `data-dl-toggle-label`, classes `dl-switchrow`, `dl-switch`, `dl-switch__slider`, `dl-switchrow__label`. JS branch: `this.tg` in `resources/assets/js/components/field.js` (class `Field`). |

## Rules and pitfalls

- Pass a real boolean: `:value="(bool) $m->active"`. `'false'`, `'off'` and `'no'` are truthy strings and render ON.
- The posted value is always `1`. A column that stores the words (`Active` / `Non-Active`) must map them on the server: `$request->boolean('status') ? 'Active' : 'Non-Active'`.
- Absent key = OFF. Do not use `$request->input('status', $model->status)` as a default, or an OFF switch keeps the old ON value.
- Restoring after a failed validation: OFF posts nothing, so `old('status', $m->active)` re-switches ON a switch the user turned off. Use `session()->hasOldInput() ? (bool) old('status') : (bool) $m->active`.
- Change `on-label` / `off-label` together with the meaning of the field ("PPN" / "Non PPN"); the defaults are English and are not translated.
- `required` only works with a non-empty `label`, and means "must be ON".
- Code oddity (not fixed): `resources/assets/devlib.css:281-287` has no `:disabled` or `:focus` style for `.dl-switch`, so a disabled or read-only toggle looks exactly like an enabled one and the keyboard focus is invisible.

## Example

```blade
<x-dl::field.toggle name="status" label="Account status" :value="true" />
```

```blade
{{-- Own words, state from a model, restore after a failed validation --}}
<x-dl::field.toggle name="ppn" label="Tax invoice (PPN)" on-label="PPN" off-label="Non PPN"
    :value="session()->hasOldInput() ? (bool) old('ppn') : (bool) ($order->ppn ?? true)"
    :error="$errors->first('ppn')" />
```

```blade
{{-- Locked display that still posts an ON state --}}
<x-dl::field.toggle name="active" label="Active" :value="(bool) $m->active" readonly />
```

## Related

- `x-dl::field.checkbox`: the same yes/no data as a plain box with text.
- `x-dl::field.toggle-group`: two or more named alternatives as segmented buttons.
- `x-dl::field.radio`: one choice from a short list.
- `x-dl::field.text`: full reference of the 14 inherited props, wrapper markup and shared behavior.
- `x-dl::form`: required-field validation, dirty guard, Undo and locked fieldsets.
- `x-dl::grid`: a `toggle` column type shows the same switch inside a list row (`toggle-url`).
