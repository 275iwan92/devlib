## Overview

`<x-dl::field.password>` is a masked single-line input with an eye button that shows or hides the characters. It is the field for passwords and other secrets typed by a person (login, new password, unlock). It is a thin control on the shared field frame: it adds one own prop (`autocomplete`) and inherits the other 14 from `Devlib\Components\Field\Field` (full reference: `x-dl::field.text`).

It does no checking by itself: no length, strength or "confirm" rule. Those exist only in the register and forgot-password boxes (see Rules and pitfalls). The component never hashes or stores anything; the browser posts the text as typed and the server must hash it.

## Props

Constructor of `Devlib\Components\Field\Password` (15 props: 1 own + 14 inherited). Blade names are kebab-case.

### Own prop

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `autocomplete` | `string`, `'current-password'` | Written to the `autocomplete` attribute of the input, so password managers know what to do. Use `current-password` to sign in or unlock, `new-password` when the user chooses a password (register, reset, change), `off` to ask the browser to leave it alone. Unlike `x-dl::field.text`, it is always written (never `null`). |

### Inherited props

Full reference: `x-dl::field.text`. What they mean for this control:

| Prop | Type, default | Effect here |
|---|---|---|
| `name` | `string`, `''` | `name` of the `<input type="password">`, `data-dl-field` on the root, slug of the id `dlf-<name>-<rand5>`. No name = nothing posted. |
| `label` | `string`, `''` | Label row above the box (`<label for=uid>`). Empty = no row, no `*`, no tooltip. Translated. |
| `tooltip` | `?string`, `null` | (i) bubble next to a non-empty label. Translated. |
| `hint` | `?string`, `null` | Help line under the control (for example the password rule). Rendered whenever not `null`. Translated. |
| `error` | `?string`, `null` | Server error text; non-empty adds `is-invalid`. Not translated. |
| `value` | `mixed`, `null` | Written as `value="..."` into the HTML. Leave it empty: a real password here is readable in the page source and through the eye button. |
| `placeholder` | `?string`, `null` | Placeholder text. Translated. Shown while empty. |
| `required` | `bool`, `false` | Red `*` after the label; `x-dl::form` Save/Confirm marks the field "Required" when empty. Not the HTML `required` attribute, no server check. |
| `disabled` | `bool`, `false` | Input `disabled` (not posted). The eye button is still clickable. |
| `readonly` | `bool`, `false` | Input `readonly` (posted), `is-readonly` on the root; also forced by `Field::$readonlyAll`. The eye button still works. |
| `icon` | `?string`, `null` | Font Awesome solid class (`fa-lock`, `fa-key`) shown at the left inside the box; adds `has-icon`. Used by the login, register, forgot and lock screens. |
| `size` | `string`, `'sm'` | `sm` or `lg` (login mockup): root class `dl-field--sm` / `dl-field--lg` only. |
| `i18n` | `?string`, `null` | Key written to `data-i18n` on the label for the auth screens (`l_pass`, `r_pass`, `f_new`, `r_conf`). |
| `source` | `?string`, `null` | Developer-catalog text; no runtime effect. |

## Behavior

### Markup

```html
<div class="dl-field__box has-icon">
  <i class="fa-solid fa-lock dl-field__icon"></i>
  <input id="dlf-password-xxxxx" name="password" type="password" class="dl-input" value="" placeholder="" autocomplete="current-password">
  <button type="button" class="dl-field__trigger" data-dl-eye title="Show / hide password"><i class="fa-solid fa-eye"></i></button>
</div>
```

### Eye button

- The shared `Field` script finds `[data-dl-eye]` and, on click, flips the input between `type="password"` and `type="text"` and swaps the icon `fa-eye` / `fa-eye-slash`.
- The state is per page view only. It is not stored and not reset on blur or submit.
- The button is a native `<button type="button">` in the Tab order; its only accessible text is the `title` ("Show / hide password", from the dictionary). It has no `aria-pressed` and no `aria-label`.

### Read-only, disabled and locked display

`readonly` and `disabled` set the matching attribute on the input only. The eye button is not disabled in either state, so a pre-filled `value` can still be revealed. A locked `x-dl::form` (fieldset disabled) disables the input and the button together. The form's QR extra masks password fields.

### Validation

None in the field. `required` only works through `x-dl::form` (empty check). The register and forgot boxes add a rule in their own script (`passCheck`): at least 8 characters, at least one letter and one digit, and the confirm field must equal the first; the result is shown in the hint/error lines of the two fields. A page that wants the same must build it itself.

### Locale and language

No number or date formatting. Only the dictionary translation of `label`, `tooltip`, `hint`, `placeholder` and the eye `title` (at render time) applies.

### Accessibility as implemented

Real `<label for>` to the input id; native password input (managers and screen readers announce it as a password). No `aria-*` attributes: `required` is a visual `*`, the error line has no `role="alert"`/`aria-live`, and the input has no `aria-invalid`/`aria-describedby`. `autocomplete` is the main help for password managers.

## Data and events

### What the server receives

`name=<text exactly as typed>` as a plain string (empty string if empty), also while the eye shows it. `disabled` = not sent. No hashing, no trimming, no confirmation. Laravel's `TrimStrings` middleware skips only the default names (`current_password`, `password`, `password_confirmation`), so a field with another name (for example `new_password`) is trimmed on the server unless you add it to the middleware exceptions.

### Events and hooks

- Native `input` / `change` only. No `dl:*` event is emitted by the field. `x-dl::form` dirty guard and the "Required" clearing listen to `input`.
- `data-dl-eye` marks the button; `.dl-input` is the input; `el.dlComponent` on the root `.dl-field` is the `Field` instance (`input`, `eye`).
- The register and forgot boxes read the value with `[name="password"]` / `[name="password_confirmation"]` (register) and `new_password` / `new_password_confirmation` (forgot); keep those names if you use those boxes.

## Rules and pitfalls

- Never pass a real password through `value` (or `old()`), and never log it. Laravel does not flash `password` fields by default, so after a failed submit the field comes back empty; leave it that way.
- Use `autocomplete="new-password"` for any field where the user creates a password, otherwise managers offer to fill the saved one. For sign-in keep the default.
- Posting is plain text: serve the form over HTTPS, hash on the server, and do not rely on the eye or the type for security.
- A password confirmation is a second `x-dl::field.password` (`name="password_confirmation"`); the `confirmed` Laravel rule does the real check. The client-side match message exists only inside the register and forgot boxes.
- A visible password stays visible after Save if the page does not reload; screens that post by script should hide it again (click the eye or set `type="password"`).
- The field has no case badge, `maxlength` or counter. If a rule needs a maximum length, validate on the server.
- Code oddity (not fixed): the eye button is not disabled by `readonly` or `disabled` (`resources/views/components/field/password.blade.php:4`), so a locked field that was given a `value` can still be shown in clear text.

## Example

Copied in shape from the catalog demo.

```blade
<x-dl::field.password name="password" label="Password" tooltip="Tombol mata untuk melihat" />
<x-dl::field.password name="password2" label="Password (besar, berikon)" icon="fa-lock" size="lg" />
```

Sign-up style pair (new password plus confirmation, shown as in the register box):

```blade
<x-dl::field.password name="password" label="Password" icon="fa-lock" autocomplete="new-password" required hint="Min. 8 characters, a mix of letters & numbers." :error="$errors->first('password')" />
<x-dl::field.password name="password_confirmation" label="Confirm" icon="fa-lock" autocomplete="new-password" required />
```

## Related

- `x-dl::field.text`: full reference for the 14 inherited props and the shared frame.
- `x-dl::login-box`, `x-dl::register-box`, `x-dl::forgot-box`, `x-dl::lock-screen`: the screens that use this field and (register, forgot) the strength and match check.
- `x-dl::form`: required-field validation and the dirty guard.
- `x-dl::field.otp`: one-time codes, per digit.
