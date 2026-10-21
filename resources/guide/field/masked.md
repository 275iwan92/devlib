## Overview

`<x-dl::field.masked>` is a digits-only input with a fixed display pattern, for identifiers whose length and grouping never change: a national ID number (NIK, 16 digits as `6.6.4`), a tax number (NPWP, 15 digits), a postal code, a bank account written in groups. While the user types, the script keeps only digits, cuts them to the number of digit slots and re-inserts the pattern's punctuation. When the field is left with fewer digits than the pattern needs, it shows an error line.

It is a thin control on the shared field frame: two own props (`mask`, `invalid-text`) and the 14 inherited ones (full reference: `x-dl::field.text`). It is not a general mask engine: only `9` means "one digit"; letters cannot be typed.

## Props

Constructor of `Devlib\Components\Field\Masked` (16 props: 2 own + 14 inherited). Blade names are kebab-case.

### Own props

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `mask` | `string`, `'999999.999999.9999'` | The pattern. Each `9` is one digit slot; every other character is a literal separator inserted automatically (`.`, `-`, space, `/`). Written to `data-dl-mask`; the number of `9`s is the required digit count, and `maxlength` is set to the total pattern length (`strlen($mask)`). The default is the 16-digit NIK. Example: `99.999.999.9-999.999` (15-digit NPWP). Do not put digits in the literal part (see pitfalls). |
| `invalid-text` | `?string`, `null` | Error message shown on blur when the digits are incomplete. `null` uses the dictionary text "Must be :n digits" with `:n` = the number of `9`s ("Harus 16 digit" in Indonesian), chosen by the language of the page when it is rendered. A custom text is used as given and is not translated. Written to `data-invalid`. |

### Inherited props

Full reference: `x-dl::field.text`. What they mean for this control:

| Prop | Type, default | Effect here |
|---|---|---|
| `name` | `string`, `''` | `name` of the single `<input type="text" inputmode="numeric">`; also `data-dl-field` and the id slug. No name = nothing posted. |
| `label` | `string`, `''` | Label row (`<label for=uid>`). Empty = no row, no `*`, no tooltip. Translated. |
| `tooltip` | `?string`, `null` | (i) bubble next to a non-empty label. Translated. |
| `hint` | `?string`, `null` | Help line under the box (a good place for an example). Translated. |
| `error` | `?string`, `null` | Server error text; non-empty adds `is-invalid`. Not translated. The first blur of the field clears it (see oddity). |
| `value` | `mixed`, `null` | Initial text written to the input. It is NOT re-masked on load: `3578011505980001` is shown as typed in the markup until the user edits it. Pass the already formatted text (`357801.150598.0001`) if the unformatted form should not appear. |
| `placeholder` | `?string`, `null` | Placeholder text. Translated. Show the pattern, for example `357801.150598...`. |
| `required` | `bool`, `false` | Red `*` and `x-dl::form` "Required" check on an empty value. A partial value counts as filled for this check. Not the HTML `required`, no server check. |
| `disabled` | `bool`, `false` | Input `disabled` (not posted). |
| `readonly` | `bool`, `false` | Input `readonly` (posted) and `is-readonly` on the root; also forced by `Field::$readonlyAll`. |
| `icon` | `?string`, `null` | Not used by this control (the view has no icon slot). |
| `size` | `string`, `'sm'` | Root class `dl-field--sm` / `dl-field--lg` only. |
| `i18n` | `?string`, `null` | Key for `data-i18n` on the label (auth screens). |
| `source` | `?string`, `null` | Developer-catalog text; no runtime effect. |

## Behavior

### Markup

```html
<div class="dl-field__box">
  <input id="dlf-nik-xxxxx" type="text" inputmode="numeric" name="nik" class="dl-input" value="" placeholder=""
         data-dl-mask="999999.999999.9999" data-invalid="Must be 16 digits" maxlength="18">
</div>
```

### Masking on input

On every `input` event the script (in the shared `Field` class):

1. takes the current text and removes everything that is not a digit;
2. cuts the digits to the number of `9`s in the mask;
3. rebuilds the text left to right: each `9` takes the next digit, each other pattern character is copied, and it stops as soon as the digits run out.

Consequences: nothing is shown for a literal before its first digit and none after the last typed digit (typing 2 digits of an NPWP shows `12`, the third gives `12.3`); letters and symbols are dropped silently; pasting `3578-0115-9800-01` or a formatted number is cleaned the same way; the caret jumps to the end after each change, so editing in the middle is awkward.

### Validation

- On blur: `0 < digits < required` marks the field invalid (`is-invalid`) and prints `data-invalid` in the error line; any other state (empty, complete) clears the error and `is-invalid`.
- Empty is valid for the field. "Must be filled" is the `required` prop (form-level).
- The check is advisory: the form still submits an incomplete value, because `x-dl::form` validates only `required`. The server must check the digit count.
- No checksum (the NIK date part, NPWP check digit) is verified.

### Read-only, disabled and locked display

`readonly` / `disabled` act on the input only; the blur check still runs on a read-only field. A locked `x-dl::form` disables the field like any other. The text shows exactly as stored (see `value` above).

### Locale and language

No number formatting (digits only, no thousand separators). Language only affects the dictionary translation of `label`, `tooltip`, `hint`, `placeholder`, and the default "Must be :n digits" text, which is fixed when the page is rendered (it does not follow a later in-page language switch). `invalid-text` is not translated.

### Accessibility as implemented

Real `<label for>` to the input; `inputmode="numeric"` opens the numeric keypad on touch devices. No `aria-*`: `required` is a visual `*`, the error line has no `role="alert"`/`aria-live`, the input has no `aria-invalid` or `aria-describedby`. Keyboard is the normal text input.

## Data and events

### What the server receives

`name=<text as shown>`: the masked text including the literals, for example `357801.150598.0001`, or whatever was in `value` if the user never edited it (possibly unformatted digits). A partial value is posted too. Always strip it on the server: `preg_replace('/\D/', '', $request->nik)`, then check the length. Disabled = nothing sent.

### Events and hooks

- Native `input` (also when pasting), `change`, `blur` on the input. No `dl:*` event.
- `data-dl-mask` marks the input; `data-invalid` holds the message; `data-dl-field-error` is the error line the blur check writes to.
- `el.dlComponent` on the root `.dl-field` is the `Field` instance (`mask` property = the input). `Devlib.h.fillForm`/`setField` (Undo, `Devlib.fill`) set the value and dispatch `input`, so a restored value is re-masked.

## Rules and pitfalls

- Always re-validate and normalise on the server (digits only, exact length); the client check never blocks Save.
- `required` passes with a partial value. Combine with a server rule such as `digits:16`.
- Keep literals to punctuation and spaces. A digit inside the literal part is read back as a typed digit and breaks the pattern (see oddity).
- `maxlength` is generated from the pattern length; do not add your own.
- A pre-existing value from the database appears unformatted: format it when you render (`:value="$fmt"`) if you want the separators from the start.
- The mask does not know about `-` or spaces typed by the user: they are removed and re-inserted by the pattern.
- A server `error` is removed by the first blur, even if the user changed nothing. Re-render the error, or add your own message in the hint, if it must stay visible.
- Code oddity (not fixed): the blur handler (`resources/assets/js/components/field.js:114`) always rewrites the error line and the `is-invalid` class, so a message passed by the server in `error` disappears on the first blur of a masked field.
- Code oddity (not fixed): the input handler (`resources/assets/js/components/field.js:106-113`) strips with `/\D/` and counts only the `9`s, so a mask with literal digits (for example `+62 999-999`) re-reads those digits as typed digits and the text turns into nonsense; and the initial `value` is never passed through the mask (no call on init).

## Example

Copied in shape from the catalog demo and the master/document form demos.

```blade
<x-dl::field.masked name="nik" label="No. KTP" placeholder="357801.150598..." value="3578011505980001" />
<x-dl::field.masked name="npwp" label="NPWP" mask="99.999.999.9-999.999" />
```

In a form, restoring the old value and showing the server error:

```blade
<x-dl::field.masked name="npwp" label="NPWP Supplier" mask="99.999.999.9-999.999" :value="old('npwp', $supplier->npwp ?? null)"
    :error="$errors->first('npwp')" hint="15 digits, for example 01.234.567.8-901.000" />
```

Custom message:

```blade
<x-dl::field.masked name="zip" label="Postal code" mask="99999" invalid-text="Postal code must be 5 digits" required />
```

## Related

- `x-dl::field.text`: full reference for the inherited props, the shared frame and the shared scripts.
- `x-dl::field.phone`: digits with a country-aware pattern, validated and posted in international form.
- `x-dl::field.number`, `x-dl::field.decimal`: numeric quantities (values you calculate with); use masked only for identifiers.
- `x-dl::form`: required-field validation and the dirty guard.
