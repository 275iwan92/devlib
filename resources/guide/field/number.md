## Overview

`<x-dl::field.number>` is the whole-number input of DevLib: a text box that accepts digits only, with small up/down (▲ ▼) spin buttons at the right and an optional lower and upper limit. Use it for counts and quantities that are always integers (number of children, copies, days). It is a normal form field: label row, control, error line and hint line come from the shared field wrapper (`resources/views/components/field.blade.php`); only the control (`resources/views/components/field/number.blade.php`) and the small script branch `[data-dl-number]` in `resources/assets/js/components/field.js` are specific to it.

### What it is not

- Not for fractions or money: the box strips everything that is not a digit and blur truncates to an integer. Use `x-dl::field.decimal` or `x-dl::field.money`.
- Not for negative numbers in practice (see Rules and pitfalls).
- Not an optional field: an empty box is turned into the minimum on blur, so the control always holds a number.
- Not a server validator: `min`, `max` and `step` only drive the browser behaviour. There is no HTML `min`/`max`/`step` attribute on the input and the form has `novalidate`.

### Where it sits

The field lives inside `x-dl::form` (`x-dl::form.row` / `x-dl::form.col`) or any `<form>`. The input is a plain `<input type="text" inputmode="numeric">`, so it posts like any text field and works without the form object.

## Props

Constructor of `Devlib\Components\Field\Number` (17 props: 3 own, 14 inherited). Blade names are kebab-case; use `:prop="expr"` for anything that is not a plain string (`:max="20"`, `:value="2"`).

### Own props

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `min` | `int`, `0` | Lower limit. Written to `data-min` on the box. Spin down and blur clamp to it; the input is **never empty** because blur turns an empty or non-numeric box into `min`. It is also the displayed value when `value` is `null` (`value ?? min`). |
| `max` | `?int`, `null` | Upper limit, written to `data-max` (`""` when null = no upper limit, JS reads it as infinity). Spin up and blur clamp to it. |
| `step` | `int`, `1` | Amount added or removed by one click on ▲ / ▼ (`data-step`; `0` is treated as `1`). It is **not** enforced on typed values: 7 stays 7 with `step` 5. |

### Inherited props

All 14 come from `Devlib\Components\Field\Field`; the full reference is on `x-dl::field.text`. How this control uses them:

| Prop | Use in this control |
|---|---|
| `name` | `name` of the text input, `data-dl-field` on the root, slug part of the generated id. No name = nothing is posted. |
| `label` | Label text (`for` = the input id). Empty string = no label row (also no `*`, no tooltip). Translated. |
| `tooltip` | The (i) bubble next to the label (needs a label). Translated. |
| `hint` | Help line under the control; rendered whenever it is not `null`. Translated. |
| `error` | Server error text; non-empty marks the field invalid (`is-invalid`). Not translated. |
| `value` | Initial value, written into the box as-is (`value ?? min`). Pass a whole number (`:value="2"`). It is **not** clamped when rendered. |
| `placeholder` | Accepted but **not used**: the view renders no `placeholder` attribute (the box always shows a number). |
| `required` | Red `*` next to the label. The form check "Required" can practically never fire (the box is never empty, see Behavior). |
| `disabled` | Disables the input (not posted). The spin buttons stay rendered (see the oddity below). |
| `readonly` | Input `readonly` (posted), no spin buttons, no `has-spin` padding, root gets `is-readonly`. Forced on while `Field::$readonlyAll` is true. |
| `icon` | Not used by this control. |
| `size` | `sm` (default) or `lg`; only changes the CSS class `dl-field--sm` / `dl-field--lg`. |
| `i18n` | `data-i18n` key on the label for auth screens; normal screens never set it. |
| `source` | Free text for the developer catalog; default `input "<name>"`. No visual effect. |

## Behavior

### Markup

```html
<div class="dl-field dl-field--number dl-field--sm" data-dl-field="anak">
  <div class="dl-field__labelrow">…label…</div>
  <div class="dl-field__box" data-dl-number data-min="0" data-max="20" data-step="1">
    <input id="dlf-anak-xxxxx" type="text" inputmode="numeric" name="anak" class="dl-input has-spin" value="2">
    <div class="dl-spin"><button type="button" data-dl-spin="1">▲</button><button type="button" data-dl-spin="-1">▼</button></div>
  </div>
  <div class="dl-field__error" data-dl-field-error></div>
</div>
```

Extra HTML attributes (`id`, `class`, `data-*`) go on the root `div`, not on the input. `can="module.action"` removes the whole field when the user lacks the ability.

### Script

There is no dedicated class: the shared `Field` class (selector `.dl-field`) finds `[data-dl-number]` and wires it on mount:

| Event | What happens |
|---|---|
| Typing (`input`) | Every non-digit is removed at once (`replace(/\D/g, '')`): letters, spaces, `.`, `,` and `-` cannot stay in the box. Pasting `1.234` gives `1234`. |
| Blur | `parseInt(value, 10) \|\| 0`, then clamped to `min..max`. Empty or garbage becomes `min`; with a `0` minimum it becomes `0`. |
| Click ▲ / ▼ | `clamp((parseInt(value, 10) \|\| 0) + step * ±1)`. An empty box counts as 0. Nothing is formatted, no separators are added. |

There is no keyboard shortcut (the arrow keys do nothing: it is a text input) and no wheel handling.

### Read-only, disabled, locked

- `readonly`: no spin buttons; the value is posted.
- `disabled`: the input is not posted; the ▲ ▼ buttons are still rendered and still change the (disabled, unposted) value when the field is not inside a disabled fieldset.
- Inside a locked `x-dl::form` (Confirmed, Cancelled, ...) the form disables the whole fieldset, which also disables the spin buttons and stops the post.

### Language and number format

None. The box shows plain digits in every display language: no thousand separator, no decimal separator, and the value does not carry `data-dl-num` (so the `formdata` conversion to canonical numbers does not apply; none is needed for integers).

## Data and events

### What the server receives

`name` = the digits as text, for example `2`. No `name` attribute, no post. The value can only be a non-negative integer when it was typed; a clamped spin result with a negative `min` could be negative (see the oddity). Validate again on the server: type, `min`, `max`, and that the field is present.

### Markup hooks

| Hook | Where |
|---|---|
| `data-dl-field="<name>"` | root `div`, also marks the field for the form |
| `data-dl-number` with `data-min`, `data-max`, `data-step` | the `.dl-field__box` |
| `data-dl-spin="1"` / `"-1"` | the ▲ / ▼ buttons |
| `.has-spin`, `.dl-spin` | padding and container of the spin buttons (absent with `readonly`) |
| `.is-invalid`, `.is-readonly` | root state classes |

### Events and JavaScript

- No `dl:*` event is fired by this control and it has no public API. `el.dlComponent` on the root `.dl-field` is the shared `Field` instance (its `num` property is the `[data-dl-number]` box).
- Native events only: `input` while typing; `change` after typing when the box loses focus. Clicking ▲ / ▼ and the blur clamp set the value from script, so they fire **no** `input` / `change` event: a change made only with the spin buttons does not mark the form dirty (the dirty guard listens to these events) and the form's Undo stays "nothing to undo".
- Inside `x-dl::form`, typing a value removes the "Required" / server error mark of the field.

### Accessibility as implemented

The `<label for>` is tied to the input id; `inputmode="numeric"` opens the numeric keypad on phones. The spin buttons are focusable `<button type="button">` elements whose only text is the symbol (▲ ▼), with no `aria-label`, and the input has no `role="spinbutton"` or `aria-valuenow`.

## Rules and pitfalls

- Pass `:value="2"` (a number), not a formatted string. A string such as `"1.5"` is shown as-is and becomes `1` at the first blur.
- The initial value is **not** clamped: `:max="20" :value="50"` shows `50` until the user blurs or spins. Clamp on the server (or before rendering).
- `required` is cosmetic here: the box is never empty, because the blur that precedes a click on Save turns an empty box into `min`. Use `min` to express the lowest valid number and validate presence on the server.
- To allow "no answer", use another control (for example `x-dl::field.select` with an empty option); this control cannot be empty.
- `step` is only the spin increment; it does not reject typed values that are off-step.
- Use `x-dl::field.decimal` for fractions and `x-dl::field.money` for amounts; the digits-only rule makes a `.` or `,` impossible here.
- Do not add your own `<input type="number">` next to it; the object already covers the case, and its look follows the field theme.
- Code oddity (not fixed): (1) `resources/views/components/field/number.blade.php:3` and `resources/assets/js/components/field.js:166` leave the ▲ ▼ buttons active when only `disabled` is set (they change the value of a disabled input; harmless for the post, confusing for the user). (2) `field.js:167` removes `-` on every keystroke, so a negative `min` is reachable only by the spin buttons and the sign disappears at the next keystroke; the `int $min` type suggests negatives are meant to work.

## Example

```blade
<x-dl::form.row :cols="3">
    <x-dl::field.number name="children" label="Number of children" :max="20" :value="2" />
</x-dl::form.row>
```

```blade
{{-- quantity between 1 and 99, step of 5 on the spin buttons, restore after a failed save --}}
<x-dl::field.number name="copies" label="Copies" :min="1" :max="99" :step="5"
    :value="old('copies', $job->copies ?? 1)" :error="$errors->first('copies')" hint="Whole numbers only." required />
```

```blade
{{-- shown but not editable, still posted --}}
<x-dl::field.number name="visits" label="Visits" :value="$customer->visits" readonly />
```

## Related

- `x-dl::field.decimal`: number with decimals, a unit and the "00" decimals toggle.
- `x-dl::field.money`: amount with a currency selector and optional amount in words.
- `x-dl::field.number-words`: amount typed with the spelled-out words shown live.
- `x-dl::field.slider`: pick a number by dragging instead of typing.
- `x-dl::field.text`: full reference of the 14 inherited props, the shared wrapper and `required` handling.
- `x-dl::form`: the form shell that validates `required` and runs the dirty guard.
- `x-dl::lines` and `x-dl::sheet`: numeric cells in detail lines and grids (their own cell editors, not this field).
