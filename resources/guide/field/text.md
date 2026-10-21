## Overview

`<x-dl::field.text>` is the standard free-text input of DevLib: one line (an `<input>`) or several lines (an auto-growing `<textarea>`), with an optional icon, prefix/suffix text, character counter and the three-state case button `aa / Aa / AA`. It is the base reference for every field: this entry explains the 14 props inherited from the abstract `Devlib\Components\Field\Field`, the shared frame (label row, error line, hint line) and the shared scripts; the other `x-dl::field.*` entries only list what is different.

Pick another object when the data is not plain text: `x-dl::field.number`, `x-dl::field.decimal` and `x-dl::field.money` for numbers (locale-aware, posted canonical), `x-dl::field.password` for secrets, `x-dl::field.masked` for fixed-pattern digits, `x-dl::field.phone`, `x-dl::field.date`, `x-dl::field.select`, `x-dl::field.rich-text` for formatted text.

### Shared frame (every field)

```html
<div class="dl-field dl-field--text dl-field--sm [is-invalid] [is-readonly]" data-dl-field="<name>">
  <div class="dl-field__labelrow"><label class="dl-field__label" id="dlf-<name>-xxxxx-lbl" for="dlf-<name>-xxxxx">Label</label><span class="dl-field__req">*</span> (i) tooltip</div>
  <div class="dl-field__box [has-icon]"> ...control... </div>
  <div class="dl-field__counter" data-dl-counter data-max="120"></div>   <!-- only with counter -->
  <div class="dl-field__error" data-dl-field-error>server error text</div> <!-- always present -->
  <div class="dl-field__hint" data-dl-field-hint>hint</div>               <!-- only when hint is not null -->
</div>
```

Extra HTML attributes (`id`, `class`, `style`, `data-*`) and `can="module.action"` go on this root `div`, not on the `<input>`. Without the ability, the whole field is not rendered. The root also carries `data-dl`, `data-dl-class` and `data-dl-src` (developer catalog / Manifest overlay).

## Props

Constructor of `Devlib\Components\Field\Text` (23 props: 9 own + 14 inherited). Blade names are kebab-case; use `:prop="expr"` for anything that is not a plain string (`:case-toggle="false"`, `:maxlength="120"`). Booleans can be written as bare attributes (`multiline`, `code`, `counter`, `readonly`).

### Own props

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `multiline` | `bool`, `false` | Renders a `<textarea rows="1" data-dl-autogrow>` instead of the `<input>`. The height follows the content (script sets `height = scrollHeight` on `input`, when the case mode changes and when the width changes; there is no maximum). With `multiline` the props `type`, `autocomplete` and `code` have no effect (the view does not write them on the textarea). The case button, `maxlength`, `counter`, `prefix`, `suffix` and `icon` still work. |
| `case-toggle` | `bool`, `true` | Shows the badge button `aa` at the right end of the box. Clicking cycles none (`aa`) -> Title Case (`Aa`) -> UPPER (`AA`) -> none, and rewrites the real input value. The badge is a project rule (mandatory on text inputs); opt out with `:case-toggle="false"` only for search boxes, codes, usernames and similar. The badge is also omitted automatically (decided in PHP, `showCase()`) when `readonly` or `disabled` is on, or when `type` is `email`, `number`, `url` or `tel`. Any other `type` (including `password`, `date`, `search`) still gets the badge. `code` does NOT hide it. |
| `type` | `string`, `'text'` | The `type` of the `<input>`: `text`, `email`, `url`, `tel`, `number`, `search`, ... Not validated by DevLib; the browser's native input behaviour applies. Ignored by `multiline`. For secrets use `x-dl::field.password`; for locale-aware numbers use the number fields (a native `type="number"` shows the browser's own format). |
| `autocomplete` | `?string`, `null` | Written to the `autocomplete` attribute of the `<input>` when not empty (`email`, `username`, `off`, ...). `null` writes nothing, so the browser default applies. Ignored by `multiline`. |
| `code` | `bool`, `false` | Adds `dl-input--code` to the `<input>`: monospace font for codes, numbers and IDs. Purely visual. Not applied to a `multiline` textarea. |
| `prefix` | `?string`, `null` | Fixed text before the input inside the box (`<span class="dl-field__affix">`), for example `IDR`. Visual only: it is NOT part of the posted value and is not translated. |
| `suffix` | `?string`, `null` | Fixed text after the input (`dl-field__affix--end`), for example `%`. Same rules as `prefix`. |
| `maxlength` | `?int`, `null` | Writes the HTML `maxlength` attribute on the input or textarea (the browser stops typing and truncates paste). It is the only length rule; the server must enforce it too. Also the limit shown by `counter`. |
| `counter` | `bool`, `false` | Adds `<div class="dl-field__counter" data-dl-counter data-max="<maxlength>">` under the box. The `Counter` script prints `n / max characters` (or only `n characters` when `maxlength` is not set; the word follows the page language) after every `input` event, and turns it red (`is-full`) when `n >= max`. |

### Inherited props

Common to all fields, from `Devlib\Components\Field\Field`. What each one does for the text input (the other field entries refer here):

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `name` | `string`, `''` | `name` of the posted `<input>`/`<textarea>`, `data-dl-field` on the root, and the slug part of the generated id `dlf-<name>-<rand5>` (public `$uid`, used as the control's `id` and the label's `for`). No name = nothing is posted and the id has no slug. A name with `[ ]` (arrays) is allowed in HTML but the slug is derived with `Str::slug`, so the id stays clean. |
| `label` | `string`, `''` | Text of the `<label for=uid>`. Translated through the dictionary (it is a standard text prop); wrap data values in `raw()`. An empty label removes the whole label row, and with it the `*` and the tooltip. |
| `tooltip` | `?string`, `null` | Text of the small (i) bubble next to the label. Translated. Only rendered together with a non-empty `label`. The bubble is a plain `span` (CSS hover); it is not focusable. |
| `hint` | `?string`, `null` | Help line under the control (`data-dl-field-hint`). Rendered whenever it is not `null` (also for `''`). Translated. Some screens (register/forgot) temporarily replace it with a green success message and restore it later. |
| `error` | `?string`, `null` | Server error text printed in `data-dl-field-error`; any non-empty value adds `is-invalid` to the root (red border). NOT translated. Typical: `:error="$errors->first('name')"`. The error line `div` is always rendered (empty when no error) so scripts can fill it. |
| `value` | `mixed`, `null` | Initial value. Written with `{{ }}` (escaped) to the input `value` or between the `<textarea>` tags. Use `old('name', $model->name)` to refill after a failed submit. Not translated, not trimmed. |
| `placeholder` | `?string`, `null` | Placeholder text of the input/textarea. Translated through the dictionary. |
| `required` | `bool`, `false` | Shows the red `*` after the label (only when `label` is not empty) and makes `x-dl::form` Save/Confirm mark the field "Required" when it has no value. It does NOT write the HTML `required` attribute and does not validate on the server. |
| `disabled` | `bool`, `false` | Renders the control `disabled`: grayed, not focusable, NOT submitted. Also hides the case badge. |
| `readonly` | `bool`, `false` | Renders the control `readonly` (focusable, selectable, IS submitted) and adds `is-readonly` to the root. Forced `true` for every field constructed while the static switch `Field::$readonlyAll` is `true` (read-only side of pages and locked blocks). Hides the case badge. |
| `icon` | `?string`, `null` | Font Awesome class, for example `fa-user`. Rendered as `<i class="fa-solid fa-user dl-field__icon">` at the left inside the box and adds `has-icon` to the box. Because the view hard-codes `fa-solid`, only solid-style icon names work. |
| `size` | `string`, `'sm'` | `sm` (standard forms) or `lg` (large, login mockup). Only changes the root class `dl-field--sm` / `dl-field--lg`; any other string is written as-is and has no style. |
| `i18n` | `?string`, `null` | Key written to `data-i18n` on the label. Only the auth screens (login, register, forgot) use it so `authLang` can re-translate the label when the language is switched. Normal screens never set it. |
| `source` | `?string`, `null` | Free text naming where the data comes from, shown by the developer catalog / Manifest (`source()`); default `input "<name>"`. No visual or runtime effect. |

## Behavior

### Markup of the control

One line:

```html
<div class="dl-field__box has-icon">
  <i class="fa-solid fa-user dl-field__icon"></i>
  <span class="dl-field__affix">IDR</span>
  <input id="dlf-x-xxxxx" name="x" type="text" class="dl-input dl-input--code" value="..." placeholder="..." autocomplete="..." maxlength="..." >
  <span class="dl-field__affix dl-field__affix--end">%</span>
  <button type="button" class="dl-field__trigger dl-case" data-dl-case title="Change case: lower / Title Case / UPPER">aa</button>
</div>
```

The parts `icon`, `prefix`, `suffix`, the badge and the counter exist only when their prop (and condition) says so.

### Case badge (aa / Aa / AA)

- The mode lives only in the browser; it is not a prop and is not posted. What is posted is the text as it is shown: after clicking `AA` the input value IS upper case.
- The script keeps the text as typed (`raw`) so `aa` always restores the original. New typing is merged into `raw` by comparing prefix and suffix with the shown text, so editing in the middle while `AA` is active is kept (and the caret is restored).
- `Aa` uppercases the first letter of each word and leaves the other letters as they are (it does not lower-case them). A word starts after anything that is not a letter, digit or `_` (Unicode aware): `étienne` becomes `Étienne`, `o'neil` becomes `O'Neil`.
- Clicking the badge does not fire `input` or `change` (see Data and events).
- The initial mode is always `aa` (no change); `value` is never transformed on load.

### Read-only, disabled and locked display

- `readonly`: normal-looking box, text selectable, submitted, no case badge, `is-readonly` on the root. Use for server-made values (a document number).
- `disabled`: not submitted. In a locked `x-dl::form` (status Confirmed, `mode="view"`) the whole fieldset is disabled by the form instead; no per-field prop is needed.
- Prefix, suffix and icon remain visible in both states.

### Validation

- Client side: only `maxlength` (native) and, through `x-dl::form`, the `required` check (the field is "empty" when the trimmed value of its named input is `''`). There is no pattern, email or format check; `type="email"` only changes the keyboard and the native browser validity, and the form has `novalidate`.
- The form clears the "Required" error as soon as the user types a value into that field.
- The server must validate everything again and return messages through `error`.

### Locale and language

The text field has no number or date formatting. Language only affects: the dictionary translation of `label`, `tooltip`, `hint`, `placeholder` (at render), the badge `title`, and the counter word (`characters` / `karakter`, from the current page language at run time). `value`, `prefix`, `suffix` and `error` are never translated.

### Accessibility as implemented

- The visible label is a real `<label for>` pointing at the control id, so clicking the label focuses the input.
- The case badge and the (i) tooltip are not announced specially: the badge is a native `<button type="button">` with only a `title`; the tooltip is a plain `span`.
- There are no `aria-*` attributes on the field. `required` is visual (`*`) and form-level only (no `aria-required`), and the error line has no `role="alert"`, `aria-live` or `aria-describedby`, and the input gets no `aria-invalid`. Add them in the project theme if screen-reader support is required.
- Keyboard: normal browser behaviour; the badge is in the Tab order and works with Enter/Space. `Enter` in a single-line field submits the form (browser default).

## Data and events

### What the server receives

| Case | Posted |
|---|---|
| Normal | `name=<text as shown>` (string, can be empty `''`). |
| `multiline` | The textarea text; browsers normalise line breaks to CRLF on submit. |
| `disabled` | Nothing. |
| `readonly` | The value (submitted). |
| No `name` | Nothing. |
| `prefix` / `suffix` / counter | Never posted. |

DevLib does not trim the text. In a default Laravel app the `TrimStrings` and `ConvertEmptyStringsToNull` middleware do it on the server.

### Events

- Native `input` bubbles on every keystroke (the dirty guard of `x-dl::form` and the counter listen to it). Native `change` fires on blur after an edit. The case badge dispatches a bubbling `input` after it rewrites the value (so the dirty guard sees it) and no `change`; DevLib also dispatches a non-bubbling `dl:grow` event on the input to resize the textarea.
- No `dl:*` custom event is emitted by this field. Screens listen to `input` / `change` on the input (bubbles to the form) or read `form.dlComponent.values()`.
- `Devlib.h.fillForm` / `setField` (used by Undo, `Devlib.fill`) set `input.value` and dispatch `input`, so the case state, counter and textarea height follow.

### Markup hooks and JS

| Hook | Where |
|---|---|
| `data-dl-field="<name>"` | root; also what register/forgot scripts use to find a field. |
| `.dl-input` | the control (first `.dl-input` in the root is `Field.input`). |
| `data-dl-case` | case badge button. |
| `data-dl-autogrow` | textarea that grows. |
| `data-dl-counter`, `data-max` | counter line. |
| `data-dl-field-error`, `data-dl-field-hint` | error and hint lines. |
| `.dl-field__req` | the `*`; `x-dl::form` validation looks for it. |

`el.dlComponent` on the root `.dl-field` is the `Field` instance (properties such as `input`, `caseBtn`); on the counter it is the `Counter` instance. Override with `data-dl-js="MyField"` + `Devlib.register(class MyField extends Devlib.classes.Field {...})` if a project needs different behaviour.

## Rules and pitfalls

- The case badge is mandatory on text inputs (project rule, marked LOCKED in the `Text` class header). Switch it off only for search boxes, codes and cases where the exact text matters, and say why next to the field.
- `required` is visual + form-level. Never rely on it for integrity; validate on the server and send the message back with `:error`.
- The `*` and the tooltip live inside the label row: a field with `label=""` has neither.
- `placeholder`, `label`, `hint` and `tooltip` are translated; data is not. Wrap names and numbers from the database in `raw()` when they are put into those props.
- `error` is not translated: translate it on the server (Laravel validation messages).
- `readonly` is submitted, `disabled` is not. For a server-made number use `readonly` and still ignore the posted value on the server.
- `code` only changes the font. It does not remove the case badge: add `:case-toggle="false"` for codes that must stay as typed.
- `type="password"` on this field still shows the badge (and it can change the case of a secret). Use `x-dl::field.password`.
- `maxlength` must be bound as an integer (`:maxlength="120"`); the browser counts UTF-16 units, so some emoji count as 2.
- Do not put numbers with thousand separators into a text field expecting canonical numbers: the number fields post canonical `1234.5`, this one posts what the user typed.
- `prefix` / `suffix` text is not part of the value: do not expect `IDR` or `%` to arrive on the server.
- An `old()` / `$model` value that starts with a line break loses it inside a textarea (HTML parser rule).

## Example

Names and values copied in shape from the catalog demo.

```blade
<x-dl::field.text name="nama" label="Nama Lengkap" tooltip="Nama & deskripsi: badge aa/Aa/AA" placeholder="Masukkan nama..." value="Budi Santoso" />
<x-dl::field.text name="alamat" label="Alamat (banyak baris)" multiline />
<x-dl::field.text name="kode" label="Kode Barang" code :case-toggle="false" value="COLOR_OFF" />
<x-dl::field.text name="email" type="email" label="Email" />
<x-dl::field.text name="no_dok" label="No. Dokumen (read-only)" readonly value="PO-2609-0001" />
<x-dl::field.text name="login" label="Username (besar, berikon)" icon="fa-user" size="lg" :case-toggle="false" />
```

Prefix, suffix and counter:

```blade
<x-dl::field.text name="harga" label="Harga (prefix)" prefix="IDR" value="1.250.000" code />
<x-dl::field.text name="diskon" label="Diskon (suffix)" suffix="%" value="10" code />
<x-dl::field.text name="keterangan" label="Keterangan (maks. 120 karakter)" multiline :maxlength="120" counter />
```

Inside a form: required, server error and old value restored.

```blade
<x-dl::form.row :cols="3">
    <x-dl::field.text name="code" label="Code Prefix" :value="old('code', $category->code ?? null)" code :case-toggle="false" required :readonly="! $isNew" maxlength="3" :error="$errors->first('code')" hint="Exactly 3 letters or digits." />
    <x-dl::field.text name="name" label="Category Name" :value="old('name', $category->name ?? null)" required :maxlength="100" :error="$errors->first('name')" />
</x-dl::form.row>
```

## Related

- `x-dl::form`, `x-dl::form.row`, `x-dl::form.col`: the form shell and layout that hold the field; its Save/Confirm runs the `required` check and its dirty guard listens to `input`.
- `x-dl::field.password`, `x-dl::field.masked`, `x-dl::field.phone`, `x-dl::field.credit-card`, `x-dl::field.otp`: text-like fields with their own behaviour; each lists only the differences from this entry.
- `x-dl::field.number`, `x-dl::field.decimal`, `x-dl::field.money`, `x-dl::field.number-words`: numeric input with locale display and canonical posting.
- `x-dl::field.rich-text`, `x-dl::field.select`, `x-dl::field.date`: other inputs that share the same frame and inherited props.
- `x-dl::field.display`: a read-only value shown in the same frame.
- `skills/devlib/form.md`, `skills/devlib/components.md`: project rules and the generated prop list.
