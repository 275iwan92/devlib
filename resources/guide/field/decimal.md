## Overview

`<x-dl::field.decimal>` is the input for quantities with a fraction (weight, length, rate, percentage). It works like a spreadsheet cell: while the user is typing, the box holds the raw text (`1234,5`); when the user leaves it, the value is formatted in the display language with a fixed number of decimals (`1.234,50`). The server always receives the canonical number `1234.5`, never the display text. An optional unit label sits inside the box, and a small "00" button lets the user switch between 0, 2 and 4 decimals or open a mini calculator.

The field uses the shared wrapper (`resources/views/components/field.blade.php`), the control view `resources/views/components/field/decimal.blade.php`, the toggle partial `_num-toggle.blade.php` and the branch `[data-dl-decimal]` of the shared `Field` class in `resources/assets/js/components/field.js`. Number rules come from `Devlib\Locale` / `Devlib\NumberValue` (PHP) and their twins in `resources/assets/js/core.js` (`NUM`, `numStr`, `parseNum`, `fmt`, `DlNumber`).

### What it is not

- Not for whole numbers with spin buttons (`x-dl::field.number`) and not for amounts with a currency (`x-dl::field.money`).
- Not a validator: there is no minimum, maximum or step. Range and precision must be checked on the server.
- Not tied to a currency or to a unit conversion: `unit` is a text label only.

## Props

Constructor of `Devlib\Components\Field\Decimal` (17 props: 3 own, 14 inherited). Blade names are kebab-case; use `:prop="expr"` for anything that is not a plain string.

### Own props

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `decimals` | `int`, `2` | Number of decimals shown after leaving the box and in the server-rendered value (`Locale::number($value, $decimals)`). Written to `data-dl-decimal`. It is also the starting state of the "00" toggle and the default placeholder (`0,00`). The value is **rounded** to it on blur; it is not a limit on what can be typed. |
| `unit` | `?string`, `null` | Short text shown at the right inside the box (`kg`, `%`, `m`). Adds class `has-unit` (extra right padding) and a `<span class="dl-field__unit">`. Escaped, not translated, not part of the posted value. |
| `toggle` | `bool`, `true` | Shows the "00" button (0 / 2 / 4 decimals / mini calculator). It is switched off automatically for `readonly` and `disabled` fields (`showToggle()`); pass `:toggle="false"` to remove it from an editable field. |

### Inherited props

All 14 come from `Devlib\Components\Field\Field`; the full reference is on `x-dl::field.text`. How this control uses them:

| Prop | Use in this control |
|---|---|
| `name` | `name` of the text input, `data-dl-field` on the root, slug part of the generated id. No name = nothing is posted. |
| `label` | Label text (`for` = the input id). Empty string = no label row. Translated. |
| `tooltip` | The (i) bubble next to the label (needs a label). Translated. |
| `hint` | Help line under the control; rendered whenever not `null`. Translated. |
| `error` | Server error text; non-empty marks the field invalid. Not translated. |
| `value` | Initial value. Pass the **canonical** number (`62.5`, `"1250.50"`, `1250.5`); see Rules and pitfalls. If it can be read as a number it is rendered formatted (`62,50`), otherwise as given. |
| `placeholder` | Placeholder text (translated); default is the zero formatted with `decimals` (`0,00`). |
| `required` | Red `*` and the form check: an empty box is marked "Required". `0,00` counts as a value. |
| `disabled` | Disables the input (not posted) and removes the "00" button. |
| `readonly` | Read-only input (posted), no "00" button, root gets `is-readonly`. Forced on while `Field::$readonlyAll` is true. |
| `icon` | Not used by this control. |
| `size` | `sm` (default) or `lg`; only the CSS class `dl-field--sm` / `dl-field--lg`. |
| `i18n` | `data-i18n` key on the label for auth screens; normal screens never set it. |
| `source` | Free text for the developer catalog; default `input "<name>"`. No visual effect. |

## Behavior

### Markup

```html
<div class="dl-field__box has-numtg">
  <input id="dlf-berat-xxxxx" type="text" inputmode="decimal" name="berat" class="dl-input has-unit"
         value="62,50" placeholder="0,00" data-dl-decimal="2" data-dl-num>
  <span class="dl-field__unit">kg</span>
  <button type="button" class="dl-field__trigger dl-numtg" data-dl-dec-toggle tabindex="-1">00</button>
</div>
```

The "00" button is painted by script (label, tooltip). Extra HTML attributes go on the root `div`, not on the input.

### Typing, focus and blur

The branch `[data-dl-decimal]` of `Field` (`field.js`, property `dec`) runs when the page mounts:

| Moment | What happens |
|---|---|
| Mount | The server-rendered text is parsed and re-formatted once (`out()`); text that is not a number is left as it is. |
| Focus (not read-only) | The box switches to the **raw** value for editing: no thousand separators, the decimal separator of the display language (`1234,5`), and the text is selected. |
| Typing (`input`) | Every character except digits, `.`, `,` and `-` is removed. Nothing else is validated while typing. |
| Blur | If the text is a number it is formatted: exactly `decimals` digits (`1.234,50`), or "as needed, at most 4" in the calculator state. Text that is not a number stays as typed. |

### Reading what the user typed

`parseNum` / `numStr` (the same rules as `Devlib\Locale::parse` in PHP) read the text: if both `.` and `,` occur, the last one is the decimal separator whatever the language (pasting from a spreadsheet works); with one kind only, the separator of the display language is decimal (`1,5` in Indonesian), a pattern like `1.234.567` is a thousand grouping, and a single other separator is decimal. `12,` (still typing) reads as `12`. Currency words and `%` are ignored (`Rp`, `IDR`, `USD`, `EUR`, `$`, `€`, `¥`, `%`).

### The "00" toggle

The button cycles `0` decimals → `2` → `4` → calculator → `0` ...; its label shows the state (`0`, `,00`, `,0000`, a calculator icon). If `decimals` is not one of 0, 2, 4 the cycle starts as if it were 2 (so `:decimals="3"` goes to 4 on the first click). On every step the current number is re-formatted and a `change` event is fired; going to 0 or 2 decimals **rounds the number in the box** (the rounded value is what is posted).

The calculator state opens a small calculator panel (`+ − × ÷ %`, `Enter` = result, `Esc` closes). `=` writes the result rounded to 4 decimals into the box (shown "as needed, at most 4"), keeps the panel open and fires `change`. The panel closes on a click outside, on scroll and on `Esc`. The button has `tabindex="-1"`: it is for the mouse only.

### Read-only, disabled, locked

`readonly` and `disabled` remove the toggle; a read-only box does not switch to raw text on focus; only the read-only value is posted. Inside a locked `x-dl::form` the fieldset is disabled, so nothing is posted. In a read-only `x-dl::form` view the toggle is hidden by CSS.

### Language and number format

Display and parsing follow the active language: `1.234,56` (Indonesian) or `1,234.56` (English). The rules come from `window.DL_NUM`, written by `x-dl::theme-boot` from `Locale::jsSpec()` (source: `sys_languages`/`Locale::languagesUsing()` or `config('devlib.languages')`). They are frozen when the page loads: switching the display language without reloading does not change how existing boxes are read or shown. A page without `x-dl::theme-boot` falls back to the Indonesian rules.

## Data and events

### What the server receives

`name` = the **canonical** number: `.` as decimal separator, no thousand separators, `-` for negatives, for example `1234.5`. The `formdata` handler in `core.js` rewrites every enabled, named `[data-dl-num]` input for both a normal submit and `new FormData(form)`. Empty stays empty; text that is not a number is posted unchanged, so the server must treat it as invalid. Disabled fields are not posted.

### Markup hooks

| Hook | Where |
|---|---|
| `data-dl-decimal="<decimals>"` | the input; selector of the script branch |
| `data-dl-num` | the input; marks the value for canonical posting |
| `data-dl-dec-toggle` | the "00" button (`.dl-numtg`) |
| `.has-numtg`, `.has-unit` | box / input classes when the toggle / unit is shown |
| `.dl-field__unit` | the unit label |

### Events and JavaScript

- No `dl:*` event and no public API. `el.dlComponent` on the root `.dl-field` is the shared `Field` instance (`dec` property = the input).
- Native `input` while typing and `change` after typing + blur. The toggle and the calculator also dispatch `change` on the input. Blur formatting itself fires nothing.
- Because the box is a named input, typing marks the `x-dl::form` dirty (dirty guard) and clears the "Required" mark.
- `Devlib.fill()` / Undo write the canonical text into the box and dispatch `input`; the box is formatted only at its next focus/blur.

### Accessibility as implemented

The `<label for>` is tied to the input id; `inputmode="decimal"` opens the decimal keypad on phones. The "00" button is not reachable by keyboard (`tabindex="-1"`) and has only a `title` tooltip ("Decimals: none / 2 / 4 / mini calculator"); the calculator keys are operable by keyboard once the panel is focused.

## Rules and pitfalls

- Always hand the value to the field in canonical form (`62.5`, `1250.50`) or as a PHP number, never as text formatted for a language. Strings that are not canonical are re-read as typed text (`"1.250.000"` is read as a grouping in Indonesian but is rejected in English).
- A canonical string with three decimals, such as `"1.250"` (typical for a database `DECIMAL(10,3)`), is **ambiguous when read as text**: in Indonesian it is read as 1250. In the Blade `value` this is safe (a canonical string is used as-is), but never feed such strings back through `Devlib.fill()`/script as typed text.
- `decimals` rounds on blur. A number typed with more decimals is shortened in the box (and so in the post), but a form submitted with Enter without leaving the box posts the text as typed (`1.23456`). Round and validate on the server.
- Switching the "00" toggle to a smaller number of decimals rounds the stored value (`1,75` → `2`). Use `:toggle="false"` for fields where the user must not change the precision (prices that are always 2 decimals).
- Very long numbers (more than about 15 significant digits) lose precision once they go through the JavaScript number on blur.
- There is no minimum or maximum. Negative numbers are accepted by the box.
- `required` only checks that the box is not empty; `0,00` passes.
- Do not wrap the label or unit in your own markup to show a currency; use `x-dl::field.money`.
- Code oddity (not fixed): `resources/assets/js/core.js:663-677` (`setField`, used by Undo and `Devlib.fill`) writes the canonical string unformatted, and `numStr` then reads `1.250`-like strings as thousand groups in Indonesian. Example: `:decimals="3"` with the stored weight `1.250`, then the user edits and presses Undo: the box shows `1.250`, which is read as 1250 at the next focus or submit.

## Example

```blade
<x-dl::form.row :cols="3">
    <x-dl::field.decimal name="weight" label="Body weight" unit="kg" value="62.5" />
</x-dl::form.row>
```

```blade
{{-- fixed 3 decimals, no precision toggle, values restored after a failed save --}}
<x-dl::field.decimal name="length" label="Length" unit="m" :decimals="3" :toggle="false"
    :value="old('length', $part->length)" :error="$errors->first('length')" required />
```

```blade
{{-- read-only calculated value: no toggle, still posted --}}
<x-dl::field.decimal name="ratio" label="Ratio" unit="%" :value="$ratio" readonly hint="Calculated from the lines." />
```

## Related

- `x-dl::field.number`: integer with spin buttons and `min` / `max`.
- `x-dl::field.money`: amount with a currency selector, the same "00" toggle and optional amount in words.
- `x-dl::field.number-words`: number whose spelled-out words are shown while typing.
- `x-dl::field.slider`: pick a value by dragging.
- `x-dl::field.text`: full reference of the 14 inherited props and the shared field wrapper.
- `x-dl::form`: required-field check, dirty guard and Undo.
- `x-dl::theme-boot`: writes `window.DL_NUM` (number rules of the display language) that this field reads.
