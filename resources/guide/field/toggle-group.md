## Overview

`<x-dl::field.toggle-group>` is a segmented choice: a row of joined buttons of which exactly one is active (Daily / Weekly / Monthly, Non PPN / PPN). Visually it is a button bar, functionally it is a radio group that posts one value through a hidden input. Use it for two to about five short alternatives; use `x-dl::field.radio` when the plain radio look is wanted or the list is longer, and `x-dl::field.toggle` for a single on/off.

It extends the abstract `Devlib\Components\Field\Field` (shared frame: caption row, control, error line, hint line) and inherits the 14 common props. The view is `resources/views/components/field/toggle-group.blade.php`; the click behaviour is the small `Segment` class in `resources/assets/js/components/segment.js` (selector `[data-dl-segment]`), not the `Field` class. The one own prop is `options`.

## Props

Constructor of `Devlib\Components\Field\ToggleGroup` (15 props: `options` + the 14 inherited). Blade names are kebab-case; arrays need the `:` binding.

### Own prop

| Prop | Type, default | Meaning |
|---|---|---|
| `options` | `array`, `[]` | The buttons, in order. A plain list `['Daily', 'Weekly']` makes value = label. An associative array `['day' => 'Daily', 'week' => 'Weekly']` makes key = posted value (`data-value`), value = label. A list is detected with `array_is_list()`, so keys `0..n-1` in order count as a list (see pitfalls). Labels are HTML-escaped and not translated. |

### Inherited props

Full reference: `x-dl::field.text`. What they mean for this control:

| Prop | Type, default | Effect here |
|---|---|---|
| `name` | `string`, `''` | `name` of the hidden input that carries the choice, `data-dl-field` on the root, slug of the generated id (the id itself is not placed on any element here). |
| `label` | `string`, `''` | Caption above the bar. Empty = no caption row, and then no `*` and no tooltip. The caption is tied to the bar with `aria-labelledby` (see Accessibility). |
| `tooltip` | `?string`, `null` | (i) bubble next to the caption; only with a non-empty `label`. |
| `hint` | `?string`, `null` | Help line under the bar; rendered whenever not `null`. |
| `error` | `?string`, `null` | Server error text; non-empty adds `is-invalid` on the root (message only, no bordered box). |
| `value` | `mixed`, `null` | The active option value, compared as strings with the option key. It is also written, unchanged, into the hidden input, whether or not it matches an option. `null` = hidden value empty and no button active. |
| `placeholder` | `?string`, `null` | Not used by this control. |
| `required` | `bool`, `false` | Red `*` and form validation (Save/Confirm): "Required" when the hidden value is empty. Needs a non-empty `label`. Not an HTML `required`, no server check. |
| `disabled` | `bool`, `false` | `disabled` on every button and on the hidden input, so nothing is posted. |
| `readonly` | `bool`, `false` | Same as `disabled` for the buttons (they carry `disabled`, so the choice cannot change); the hidden input keeps the value in the post. Root gets `is-readonly`. Forced on while `Field::$readonlyAll` is true. |
| `icon` | `?string`, `null` | Not used by this control. |
| `size` | `string`, `'sm'` | Only changes the root class `dl-field--sm` / `dl-field--lg`. |
| `i18n` | `?string`, `null` | Written to `data-i18n` on the caption `<label>`. Does not touch the button labels. |
| `source` | `?string`, `null` | Label for the developer catalog (`source()`); default `input "<name>"`. No visual effect. |

Other HTML attributes (`id`, `class`, `data-*`) and `can="module.action"` go on the root `.dl-field` div.

## Behavior

### Markup

```html
<div class="dl-field dl-field--toggle-group dl-field--sm" data-dl-field="periode">
  <div class="dl-field__labelrow"><label class="dl-field__label" id="dlf-periode-xxxxx-lbl">Period</label></div>
  <div class="dl-segment" data-dl-segment role="group" aria-labelledby="dlf-periode-xxxxx-lbl">
    <input type="hidden" name="periode" value="week" data-dl-value>
    <button type="button" class="dl-segment__opt" data-value="day">Daily</button>
    <button type="button" class="dl-segment__opt is-active" data-value="week">Weekly</button>
  </div>
  <div class="dl-field__error" data-dl-field-error></div>
</div>
```

### Click behaviour

The `Segment` script moves the class `is-active` to the clicked button, writes its `data-value` into the hidden input `[data-dl-value]` and dispatches a bubbling `change` event on that hidden input. Buttons are `type="button"`, so they never submit a form. A click on the already active button changes nothing visible but still dispatches `change` (the form is marked dirty). There is no way to deselect; offer an explicit "none" option if needed.

### Posted state

The hidden input is always posted (`periode=week`), with an empty string when nothing is chosen. So the key is always present, unlike checkbox/radio.

### Required

`hasValue()` reads the hidden input: required fails when its value is empty (after trim). A group with an initial `value` is satisfied from the start.

### Disabled and read-only

`disabled` and `readonly` both put `disabled` on every button: no mouse or keyboard change is possible. `disabled` also disables the hidden input, so nothing is posted; `readonly` keeps the hidden input enabled, so the value is still posted (a disabled fieldset of a locked `x-dl::form` stops it as well). The bar gets no disabled styling (see oddity).

### Accessibility as implemented

Plain `<button>` elements: Tab goes through each one, Enter and Space activate it, and the browser focus ring shows. The bar has `role="group"` with `aria-labelledby` pointing at the caption (with a non-empty `label`); there is no `role="radiogroup"`/`radio`, no `aria-pressed` or `aria-checked` and no arrow-key handling. The active state is only visible, not announced.

## Data and events

| Item | Detail |
|---|---|
| Posted | `name=<option value>` always; empty string when no option is chosen. |
| Read on the server | `$request->input('name')`; validate with `'name' => ['required', Rule::in([...])]` (the value is whatever the browser sends). |
| DOM events | `change` (bubbles) from the hidden input `[data-dl-value]`, dispatched by the click handler. No `input` event. `x-dl::form` uses `change` for the dirty guard. |
| Fill from code | `Devlib.fill` / `Devlib.h.setField` write the hidden value and dispatch `input`; the script listens to it and moves `is-active` to the matching button. |
| Listen from a screen | `form.addEventListener('change', e => { if (e.target.name === 'periode') console.log(e.target.value); })`. |
| Hooks | `data-dl-segment` (root of `Segment`, `el.dlComponent`), `data-dl-value` (hidden input), `.dl-segment__opt` with `data-value`, `.is-active`. JS: `resources/assets/js/components/segment.js`. |

## Rules and pitfalls

- Option keys that form the list `0, 1, 2...` are treated as a list: `[0 => 'Off', 1 => 'On']` posts `Off` / `On`, not `0` / `1`. Use keys that are not `0..n-1` (start from 1, or use string keys such as `'off'` / `'on'`); PHP turns numeric-string keys like `'0'` into integers.
- The hidden input takes `value` as given even when it matches no option. Validate the submitted value against the allowed keys on the server (`Rule::in`).
- `disabled` removes the value from the request; `readonly` keeps it.
- Keep the labels short: the bar is `inline-flex` with `overflow:hidden` and does not wrap onto a second line.
- Option labels are not translated and are not in the dictionary; translate them before passing them in.
- Restoring after a failed validation: `old('periode', $m->periode)` works as usual, because the hidden input always posts.
- `required` only works with a non-empty `label`: the `*` marker the form looks for lives in the label row.
- Code oddity (not fixed): `resources/assets/devlib.css:831-835` has no `:disabled` style for `.dl-segment__opt`, so a disabled or read-only bar looks active.

## Example

```blade
<x-dl::field.toggle-group name="periode" label="Period" :options="['day' => 'Daily', 'week' => 'Weekly', 'month' => 'Monthly']" value="week" />
```

```blade
{{-- Two alternatives, state from a model, error from the server --}}
<x-dl::field.toggle-group name="ppn" label="Invoice header" required
    :options="['non' => 'Non PPN', 'ppn' => 'PPN']"
    :value="old('ppn', $order->ppn ?? 'non')"
    :error="$errors->first('ppn')" />
```

```blade
{{-- Locked display: the buttons cannot be clicked, the value is still posted --}}
<x-dl::field.toggle-group name="periode" label="Period" :options="['day' => 'Daily', 'week' => 'Weekly']" value="day" readonly />
```

## Related

- `x-dl::field.radio`: one choice as classic radio buttons (key absent when nothing is selected).
- `x-dl::field.toggle`: a single on/off switch.
- `x-dl::field.select`: one choice from a long list.
- `x-dl::field.text`: full reference of the 14 inherited props, wrapper markup and shared behavior.
- `x-dl::form`: required-field validation, dirty guard, Undo and locked fieldsets.
