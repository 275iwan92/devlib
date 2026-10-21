## Overview

`<x-dl::field.otp>` is a one-time-code input made of one small box per digit (6 by default). Typing a digit moves the focus to the next box, Backspace moves it back, anything that is not a digit is removed. The digits are collected in one hidden input, so the form posts a single value (`123456`).

It is a control on the shared field frame with one own prop (`length`) and the 14 inherited ones (full reference: `x-dl::field.text`). The component only collects the code. It does not send, expire, verify or rate-limit anything: issuing and checking the code is the project's job on the server. The register box uses it for the WhatsApp code; the Approve dialog of `x-dl::form` has its own, separate OTP input.

## Props

Constructor of `Devlib\Components\Field\Otp` (15 props: 1 own + 14 inherited). Blade names are kebab-case.

### Own prop

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `length` | `int`, `6` | Number of digit boxes (the view loops `0 .. length-1`). Use `:length="4"` for a 4-digit code. It is only the number of boxes: nothing checks that all of them are filled, and the hidden value is as long as what was typed. |

### Inherited props

Full reference: `x-dl::field.text`. What they mean for this control:

| Prop | Type, default | Effect here |
|---|---|---|
| `name` | `string`, `''` | Name of the hidden input that carries the code; the boxes themselves have no name. Also `data-dl-field` and the id slug (the id goes on the FIRST box). No name = nothing posted. |
| `label` | `string`, `''` | Label row; `<label for>` focuses the first box. Empty = no row, no `*`, no tooltip. Translated. |
| `tooltip` | `?string`, `null` | (i) bubble next to a non-empty label. Translated. |
| `hint` | `?string`, `null` | Help line under the boxes ("the code was sent to ..."). Translated. |
| `error` | `?string`, `null` | Server error text (for example "wrong code"); non-empty adds `is-invalid`. Not translated. |
| `value` | `mixed`, `null` | Not used: the view writes no value into the hidden input or the boxes. A code cannot be pre-filled. |
| `placeholder` | `?string`, `null` | Not used. |
| `required` | `bool`, `false` | Red `*` and `x-dl::form` "Required" check: empty hidden value = error. A partial code (3 of 6 digits) passes. Not the HTML `required`, no server check. |
| `disabled` | `bool`, `false` | Disables the boxes. The hidden input is not disabled and still posts (an empty `name=`). |
| `readonly` | `bool`, `false` | Boxes `readonly`, `is-readonly` on the root; also forced by `Field::$readonlyAll`. |
| `icon` | `?string`, `null` | Not used. |
| `size` | `string`, `'sm'` | Root class `dl-field--sm` / `dl-field--lg` only (the box size is fixed in CSS). |
| `i18n` | `?string`, `null` | Key for `data-i18n` on the label (the register box uses `r_otp`). |
| `source` | `?string`, `null` | Developer-catalog text; no runtime effect. |

## Behavior

### Markup

```html
<div class="dl-otp" data-dl-otp>
  <input type="hidden" name="otp" data-dl-value>
  <input type="text" inputmode="numeric" maxlength="1" class="dl-otp__box" id="dlf-otp-xxxxx">
  <input type="text" inputmode="numeric" maxlength="1" class="dl-otp__box">
  ... (length boxes in total)
</div>
```

### Typing

Handled by the shared `Field` script on the `keyup` event of every box:

1. the box keeps only digits (`\D` removed);
2. if the key was a digit and this is not the last box, the next box gets the focus;
3. if the key was Backspace and this is not the first box, the previous box gets the focus (the digit of the current box has already been deleted by the browser);
4. the hidden value is set to the boxes joined in order.

So a half-filled code is `123` (no padding), and the hidden value changes only on `keyup`.

### Validation

None on the client: no length check, no "only digits" message, no auto-submit when the last digit is typed. `required` is the form-level empty check. The screens that use the field check the length in their own script (the register box rejects a code shorter than 4). The server decides whether the code is right.

### Read-only, disabled and locked display

`readonly` / `disabled` act on the boxes. A locked `x-dl::form` disables them (and the hidden input) too. Boxes are never pre-filled, so a read-only OTP is always empty.

### Locale and language

No number or date formatting and no script messages. Only `label`, `tooltip` and `hint` go through the dictionary.

### Accessibility as implemented

The label points at the first box (`for` = its id). The other boxes have no label, `aria-label` or `aria-describedby`, so a screen reader announces them as unnamed edit fields; they have `inputmode="numeric"` for the numeric keypad. There is no `autocomplete="one-time-code"` (SMS autofill does not target these boxes). Arrow keys are not handled; Tab and Shift+Tab move box by box. There is no `aria-*` on the field: `required` is a visual `*`, the error line has no `role="alert"`.

## Data and events

### What the server receives

One field: `<name>` = the digits in order, for example `123456`; shorter if not all boxes were filled; `''` if none. The boxes are not posted. Always check the length on the server.

### Events and hooks

- Native `input`, `keyup` and `keydown` from the boxes bubble. The hidden value is set by script and never fires an event; no `dl:*` event is emitted. To react to a complete code, listen to `keyup` inside the field and compare the hidden value's length with `length`.
- Hooks: `data-dl-otp` (wrapper), `.dl-otp__box` (the boxes), `data-dl-value` (the hidden code). Screens read it with `form.querySelector('[name="otp"]').value`.
- `el.dlComponent` on the root `.dl-field` is the `Field` instance (`otp` = the wrapper).

## Rules and pitfalls

- The code is meaningless unless the server issues it, limits attempts, expires it and compares it in constant time. None of that is in the component.
- Do not rely on `required` for a complete code: check `strlen($otp) === 6` (or your length) on the server.
- Pasting a whole code into a box does not spread over the boxes: `maxlength="1"` keeps only the first character, and there is no paste handler. Ask users to type it; if pasting matters, add a project script that fills the boxes and then dispatches a `keyup`.
- A script that sets a box value (autofill, test tools, IME input that sends no `keyup`) does not update the hidden value until a `keyup` happens on that box.
- The "Required" error is cleared one keystroke late: the form removes it on `input` when the hidden value is not empty, but the hidden value is only written on the following `keyup`.
- Typing in the boxes does not mark an `x-dl::form` as dirty and a leave warning will not appear.
- The boxes are plain `type="text"` inputs: the code is always visible on screen.
- Do not use this field for a password or PIN that must stay hidden.
- Code oddity (not fixed): Undo / `Devlib.fill` (`setField` in `resources/assets/js/core.js:663`) clear or restore only the hidden input, not the digit boxes, so after Undo the boxes still show the old digits while the posted code is empty.
- Code oddity (not fixed): the boxes have no `name`, so `markDirty` (`resources/assets/js/core.js:460`) ignores them; typing a code never sets `is-dirty` on the form.
- Code oddity (not fixed): `disabled` does not disable the hidden input (`resources/views/components/field/otp.blade.php:2`), so a disabled OTP field still posts an empty `name=`.
- Code oddity (not fixed): the `value` prop is accepted by the constructor but never rendered (`otp.blade.php` has no value or box fill), so it silently does nothing.

## Example

Copied in shape from the catalog demo and the register box.

```blade
<x-dl::field.otp name="otp" label="OTP Code (6 Digit)" />
```

Four digits, with a hint and a server error:

```blade
<x-dl::field.otp name="otp" label="OTP code from WhatsApp" :length="4" hint="Enter the 4 digits we sent." :error="$errors->first('otp')" required />
```

## Related

- `x-dl::field.text`: full reference for the inherited props and the shared frame.
- `x-dl::register-box`: uses this field for the WhatsApp code (`dl:send-otp`, `dl:verify-otp` events of that box).
- `x-dl::field.phone`: the number the code is sent to.
- `x-dl::form`: required-field validation; its Approve dialog for rejected documents has its own OTP box (`otp-url`).
- `x-dl::field.password`: hidden secrets typed by a person.
