## Overview

`<x-dl::field.phone>` is a telephone input that knows the numbering rules of nine countries. The user picks a country (flag + dial code button with a searchable list) and types the number in any usual way: `0812...`, `812...` or `+62 812...`. The script strips the leading `0` or `+code`, applies the country's display pattern, checks the pattern and the minimum length, and shows how the number was understood. What the form posts is the clean international form (`+6281234567890`) plus the country code.

It is a control on the shared field frame with three own props (`countries`, `country`, `kind`) and the 14 inherited ones (full reference: `x-dl::field.text`). The rules come from one table, `Devlib\PhoneFormat::COUNTRIES`, used by PHP (the country list) and by the script (it receives the table as JSON in `data-countries`).

## Props

Constructor of `Devlib\Components\Field\Phone` (17 props: 3 own + 14 inherited). Blade names are kebab-case; arrays need `:countries="[...]"`.

### Own props

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `countries` | `array`, `[]` | ISO codes offered in the list, for example `:countries="['ID','SG']"`. Empty = all nine. Codes not in the table are ignored and the list keeps the order of the table, not of your array. |
| `country` | `string`, `'ID'` | Country selected at render. If it is not in the (filtered) list, the first country of the list is used. The script never guesses the country from the value (see pitfalls). |
| `kind` | `string`, `'mobile'` | Which number family is accepted: `mobile` (cellular), `landline` (home/office, with area code) or `any` (either; a number is accepted if it matches a mobile or a landline rule). Sets `data-kind`, the `autocomplete` of the input (`tel` for landline, `tel-national` otherwise) and the example number used as placeholder. Any other value breaks the script (the rule list is read as `rules[kind]`). |

Countries in `PhoneFormat` (dial code; `trunk` = a leading `0` is used inside the country and is removed):

| Code | Country | Dial | Trunk 0 | Mobile starts | Min digits (mobile / landline) |
|---|---|---|---|---|---|
| `ID` | Indonesia | +62 | yes | `8` | 9 / 9 |
| `SG` | Singapore | +65 | no | `8` or `9` | 8 / 8 |
| `MY` | Malaysia | +60 | yes | `1` | 9 / 8 |
| `US` | United States | +1 | no | `2`-`9` | 10 / 10 |
| `AU` | Australia | +61 | yes | `4` | 9 / 9 |
| `JP` | Japan | +81 | yes | `70`, `80`, `90` | 10 / 9 |
| `CN` | China | +86 | yes | `1` | 11 / 10 |
| `IN` | India | +91 | yes | `6`-`9` | 10 / 10 |
| `GB` | United Kingdom | +44 | yes | `7` | 10 / 10 |

### Inherited props

Full reference: `x-dl::field.text`. What they mean for this control:

| Prop | Type, default | Effect here |
|---|---|---|
| `name` | `string`, `''` | Name of the hidden input that carries the number; the country goes to `<name>_country`. The visible input has no name and is never posted. `data-dl-field` and the id slug use the base name. |
| `label` | `string`, `''` | Label row; `<label for>` points at the visible number input. Empty = no row, no `*`, no tooltip. Translated. |
| `tooltip` | `?string`, `null` | (i) bubble next to a non-empty label. Translated. |
| `hint` | `?string`, `null` | Help line under the control (good for "you may type 0812..."). Translated. |
| `error` | `?string`, `null` | Server error text; non-empty adds `is-invalid`. Not translated. It stays until the user types in the number box (the blur check does not overwrite it). |
| `value` | `mixed`, `null` | The stored number, written to the hidden input and copied into the visible one. Best: international `+62812...` of the SAME country as `country`. National digits (`0812...`) are accepted too. It is normalised on load and posted back normalised (see Behavior). |
| `placeholder` | `?string`, `null` | Rendered but overwritten at once by the script with the example number of the selected country and `kind` (for example `812-3456-7890`). Effectively unused. |
| `required` | `bool`, `false` | Red `*` and `x-dl::form` "Required" check: passes when the hidden number is not empty (the country input is excluded from this test). Not the HTML `required`, no server check. |
| `disabled` | `bool`, `false` | Disables the number input and the country button. The two hidden inputs are disabled too, so nothing is posted. |
| `readonly` | `bool`, `false` | Number input `readonly`, country button disabled, `is-readonly` on the root; also forced by `Field::$readonlyAll`. The number and country are posted. |
| `icon` | `?string`, `null` | Not used by this control (the flag button takes that place). |
| `size` | `string`, `'sm'` | Root class `dl-field--sm` / `dl-field--lg` only. |
| `i18n` | `?string`, `null` | Key for `data-i18n` on the label (auth screens). |
| `source` | `?string`, `null` | Developer-catalog text; no runtime effect. |

## Behavior

### Markup

```html
<div class="dl-prefix" data-dl-prefix data-dl-phonebox data-kind="mobile" data-countries='{"ID":{...}}'>
  <input type="hidden" name="hp" value="+6281234567890" data-dl-phone-value>
  <input type="hidden" name="hp_country" value="ID" data-dl-prefix-value>
  <button type="button" class="dl-prefix__btn" data-dl-dd-toggle><span data-dl-prefix-a>flag</span><span data-dl-prefix-b>+62</span></button>
  <input id="dlf-hp-xxxxx" type="tel" inputmode="tel" autocomplete="tel-national" class="dl-input dl-input--boxed dl-input--prefixed" data-dl-phone>
  <div class="dl-dd__panel" data-dl-dd-panel> search + list of countries </div>
</div>
<div class="dl-phone__read" data-dl-phone-read></div>
```

### Typing and display

On every `input` event, and after a country is chosen, the `Phone` script:

1. keeps the digits of what was typed. If the text starts with `+` or `00` and the digits then start with the selected country's dial digits, that prefix is removed. If the country uses a trunk `0`, one leading `0` is removed. (A `+` number of ANOTHER country is not recognised; its digits are read as national digits of the selected country.)
2. picks the first rule of the kind whose pattern matches the national digits (for `any`: mobile rules first, then landline). No match: the first rule's pattern is used for display only.
3. cuts the digits to the longest mask of that rule and formats them (`812-3456-7890`); a rule can have several masks separated by `|`, the first one with enough slots is used.
4. writes the hidden number as `<dial><digits>` (`+6281234567890`), or empty when there are no digits.
5. sets the placeholder to the example number and fills the line under the control: `Read as: +62 812-3456-7890 · local 0812-3456-7890 · Indonesia, cellular` (the "local" part only for trunk countries; the kind appears only when a rule matched).

On load the same pass runs on the value, so `value="08123456789"` becomes `+628123456789` and an old number that does not fit the rules is truncated to the longest mask. Changing the country re-runs the pass on the digits already typed (they are cut to the new country's length, never re-mapped).

### Validation

- On blur (and after choosing a country when digits are present): no digits = no error; digits that match no rule = `The <country> <what> number does not match the pattern, e.g. +62 812-3456-7890`; a match with fewer digits than the rule's minimum = `Too short: at least N digits after +62`. Messages are in the current page language and show in the error line (`is-invalid`).
- Too many digits cannot occur (they are cut). Empty is valid; use `required` for "must be filled".
- The check is advisory: an invalid number is still posted, because `x-dl::form` validates only `required`. Validate again on the server.

### Read-only, disabled and locked display

`readonly`: number input read-only, country button disabled, the read-out line still shows the interpretation. `disabled` additionally disables the input and the two hidden inputs. A locked `x-dl::form` (fieldset disabled) disables everything including the hidden inputs.

### Locale and language

Error and read-out texts come from the dictionary at run time (`ph_*` keys, Indonesian or English by the current `Devlib.lang`). Country names in the list and in messages are English from `PhoneFormat`. There is no number/date formatting beyond the mask.

### Accessibility as implemented

Real `<label for>` to the visible number input; `type="tel"`, `inputmode="tel"` and the `autocomplete` value help phones and password managers. The country button is a native button (accessible name = flag + dial code); the list items are plain `div`s opened and chosen with the pointer, the list closes on an outside click, and there is no arrow-key or Escape handling. No `aria-*` anywhere: `required` is a visual `*`, the error line has no `role="alert"`, and the input has no `aria-invalid`.

## Data and events

### What the server receives

| Field | Value |
|---|---|
| `<name>` | `+` + dial digits + national digits, no spaces or dashes (`+6281234567890`), or `''` when empty. |
| `<name>_country` | ISO code of the selected country (`ID`, `SG`, ...). |

The visible number box is not posted. Store `<name>` as it comes (it is already E.164 style) and use `<name>_country` only for display. A phone that is `disabled` posts nothing.

### Events and hooks

- No `dl:*` event. When the number changes by typing or by choosing a country, the script dispatches a bubbling `input` on the hidden `<name>` (not while `Devlib.fill` / Undo fill it); a country choice also fires `input` and `change` on the hidden `<name>_country`. Listen to `input` / `blur` on `[data-dl-phone]` (they bubble) or read `[data-dl-phone-value]`.
- Hooks: `data-dl-phonebox` (root of the control), `data-dl-phone` (visible input), `data-dl-phone-value` (number), `data-dl-prefix-value` (country), `data-dl-phone-read` (read-out line), `data-kind`, `data-countries`.
- `el.dlComponent` on the `[data-dl-phonebox]` element is the `Phone` instance (`inp`, `hid`, `cc`, `C`, `kind`, methods `upd()`, `check()`, `clean()`, `pick()`); on the root `.dl-field` it is the `Field` instance, which runs the country list.
- `Devlib.h.setField` / `fillForm` (Undo, `Devlib.fill`) choose the country first (key `<name>_country`, unknown codes are ignored), then write the number into the visible input and dispatch `input`.

## Rules and pitfalls

- Store and compare the posted `+...` string; never rebuild it from the visible text.
- Give `country` the country of the stored number. `value="+6512345678"` with the default `country="ID"` is read as an Indonesian number and posted back as `+626512345678`, silently changing the data. For mixed-country data, store `<name>_country` and pass it back as `:country`.
- `kind` must be `mobile`, `landline` or `any`. Use `any` when the column holds both, and expect the example placeholder of a mobile number.
- `countries` must contain at least one valid code; a list with only unknown codes leaves the view with no country to show and raises an undefined-key error.
- Pattern and length errors do not stop Save. Re-check on the server (for example with a phone library), and decide whether an unusual number is allowed.
- The input needs no custom `maxlength`; the script already limits the digits.
- Changing the country cuts the typed digits to the new country's length; tell users to pick the country first.
- Typing in the number box or changing the country marks an `x-dl::form` as changed through the hidden `<name>` (the visible input has no `name`), so the dirty guard, Undo and the "Required" clearing react.

## Example

Copied in shape from the catalog demo.

```blade
<x-dl::field.phone name="hp" label="No. HP (Indonesia, seluler)" hint="Boleh ketik 0812..., 812..., atau +62 812..." />
<x-dl::field.phone name="telp" label="Telepon Kantor (rumah/kantor, kode area)" kind="landline" value="+62315551234" />
<x-dl::field.phone name="hp_sg" label="No. HP Singapura" country="SG" />
<x-dl::field.phone name="telp_any" label="Telepon apa saja (HP atau kantor), pilih negara" kind="any" country="MY" value="+60123456789" />
```

In a master form, restoring the old value and the stored country, limited to two countries:

```blade
<x-dl::field.phone name="contact_phone" label="No. HP Kontak" :countries="['ID','SG']" :country="old('contact_phone_country', $contact->phone_country ?? 'ID')"
    :value="old('contact_phone', $contact->phone ?? null)" :error="$errors->first('contact_phone')" />
```

## Related

- `x-dl::field.text`: full reference for the inherited props, the shared frame and the shared scripts.
- `x-dl::field.masked`: fixed-pattern digits without country logic (ID numbers, tax numbers).
- `x-dl::form`: required-field validation and the dirty guard.
- `x-dl::register-box`: uses a phone-style number (WhatsApp) with an OTP step; `x-dl::field.otp` is the code field.
- `Devlib\PhoneFormat`: the country table (add a country there, once, for PHP and JS).
