## Overview

`<x-dl::field.money>` is the amount input: a number box with a currency button on its left. The user picks a currency from a searchable list (default list: IDR, USD, EUR, SGD, MYR, AUD, JPY, CNY, GBP) and types the amount. With `words` the amount is also spelled out below the box in the currency chosen ("Satu juta dua ratus lima puluh ribu rupiah" / "One million two hundred fifty thousand rupiah", following the active display language). Like `x-dl::field.decimal`, the amount behaves like a spreadsheet cell (raw text while editing, formatted on leaving) and the "00" button changes the decimals; the server always receives the canonical number plus the currency code.

Files: the PHP class `src/Components/Field/Money.php`, the view `resources/views/components/field/money.blade.php` with the partial `_num-toggle.blade.php`, and the `[data-dl-prefix]` branch of the shared `Field` class in `resources/assets/js/components/field.js`. Number rules: `Devlib\Locale`, `Devlib\NumberValue`, `core.js` (`NUM`, `numStr`, `parseNum`, `fmt`, `words`); wording: `Devlib\Terbilang`.

### What it is not

- Not a currency converter or exchange-rate tool: the currency is only a label and a code that is posted. No amount is recalculated when the currency changes.
- Not a rule engine: no minimum, maximum or sign check.
- Not the field for spelling out cents: the words cover the whole part only (see Rules and pitfalls). Use `x-dl::field.number-words` for that.

## Props

Constructor of `Devlib\Components\Field\Money` (19 props: 5 own, 14 inherited). Blade names are kebab-case; use `:prop="expr"` for anything that is not a plain string.

### Own props

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `currencies` | `array`, `[]` | Rows of the currency list, each `['code' => 'IDR', 'symbol' => 'Rp']`. Only `code` is used by the view (button text, list items, posted code). Empty = all codes of `Devlib\Terbilang::CURRENCIES`. |
| `currency` | `string`, `'IDR'` | Code selected at start. If it is not in the list, the first row is used. Written to the hidden field `<name>_currency`. |
| `words` | `bool`, `false` | Adds the line `[data-dl-money-words]` below the field with the amount in words, in the currency chosen and the active display language. The script reads the names from `Terbilang::CURRENCIES` (put in `data-currencies`), also when `currencies` is customised: a code that is not there is spelled without a currency name. |
| `decimals` | `int`, `2` | Starting number of decimals (`data-dl-decimals`). `0` = integer mode (thousand separators while typing, digits only); anything else = spreadsheet mode. The user can change it with the "00" button. It is not taken from the currency (JPY or IDR still start with 2 unless you pass `:decimals="0"`). |
| `toggle` | `bool`, `true` | Shows the "00" button (0 / 2 / 4 decimals / mini calculator). Off automatically for `readonly` and `disabled`; `:toggle="false"` removes it from an editable field. |

### Inherited props

All 14 come from `Devlib\Components\Field\Field`; the full reference is on `x-dl::field.text`. How this control uses them:

| Prop | Use in this control |
|---|---|
| `name` | `name` of the amount input; the currency is posted as `<name>_currency`. No name = nothing sensible is posted. |
| `label` | Label text (`for` = the amount input id). Empty string = no label row. Translated. |
| `tooltip` | The (i) bubble next to the label (needs a label). Translated. |
| `hint` | Help line under the control (below the words line when `words` is on). Translated. |
| `error` | Server error text; non-empty marks the field invalid. Not translated. |
| `value` | Initial amount, canonical (`1250000`, `"1250000.50"`). Rendered formatted with `decimals` in the server's display language (`1.250.000,00`). |
| `placeholder` | Placeholder text (translated); default is the zero formatted with `decimals`. |
| `required` | Red `*`; the form check marks an empty **amount** "Required" (the always-filled currency code is ignored). `0,00` counts as a value. |
| `disabled` | Disables the amount input (not posted) and removes the "00" button. The currency button and the hidden `<name>_currency` are disabled too, so neither the amount nor the currency is posted. |
| `readonly` | Read-only amount (posted), currency button disabled (the currency is posted), no "00" button, root gets `is-readonly`. Forced on while `Field::$readonlyAll` is true. |
| `icon` | Not used by this control. |
| `size` | `sm` (default) or `lg`; only the CSS class `dl-field--sm` / `dl-field--lg`. |
| `i18n` | `data-i18n` key on the label for auth screens; normal screens never set it. |
| `source` | Free text for the developer catalog; default `input "<name>"`. No visual effect. |

## Behavior

### Markup

```html
<div class="dl-prefix has-numtg" data-dl-prefix data-dl-money>
  <input type="hidden" name="harga_currency" value="IDR" data-dl-prefix-value>
  <button type="button" class="dl-prefix__btn" data-dl-dd-toggle><span data-dl-prefix-b>IDR</span></button>
  <input id="dlf-harga-xxxxx" type="text" inputmode="decimal" name="harga" class="dl-input dl-input--boxed dl-input--prefixed"
         value="1.250.000,00" data-dl-money-input data-dl-decimals="2" data-dl-num>
  <button type="button" class="dl-field__trigger dl-numtg" data-dl-dec-toggle tabindex="-1">00</button>
  <div class="dl-dd__panel" data-dl-dd-panel>…search box and one .dl-dd__item per currency…</div>
</div>
<div class="dl-words__text" data-dl-money-words data-currencies="{…}"></div>   {{-- only with words --}}
```

Extra HTML attributes go on the root `div.dl-field`, not on the input.

### Two number modes

| `decimals` (state of the "00" button) | Typing | Leaving the box |
|---|---|---|
| `0` (integer mode) | Everything but digits is removed on every keystroke; thousands are grouped at once (`1.250.000`). No decimal separator and no minus sign can be typed. | Nothing more. |
| `2`, `4` or calculator | Only digits, `.`, `,` and `-` are kept. Focus shows the raw value (`1250000,5`) and selects it. | Formatted with that number of decimals (`1.250.000,50`); calculator state = as needed, at most 4. |

On mount a server value is normalised once: a raw database value such as `1250000.00` is rounded (integer mode) or formatted (spreadsheet mode) instead of having its dot removed. In integer mode a value like `1.250` is kept as 1250 in a language whose grouping separator is the dot.

### The "00" toggle

Same toggle as `x-dl::field.decimal`: `0` → `2` → `4` → calculator → `0` ... The label shows the state (`0`, `,00`, `,0000`, a calculator icon); each step re-formats the amount, fires `change` on the amount input, and re-computes the words. Going to `0` rounds the amount to a whole number. The calculator panel (`+ − × ÷ %`, `Enter` = result, `Esc` closes) writes its result (at most 4 decimals) into the box and stays open. The button has `tabindex="-1"`: mouse only.

### Currency list

Clicking the code button opens a panel with a search box and the list; choosing a row sets the hidden `<name>_currency`, the button text, and refreshes the words. Nothing else is recalculated and **no event is fired** for this choice.

### Amount in words

With `words`, the line under the field is rewritten on every input, every currency change, every toggle step and at mount. It spells the **integer part of the absolute value** of the amount, with the currency name of the chosen code (`IDR` → rupiah, `USD` → dolar Amerika Serikat / US dollars, …). Fractions (cents) and the minus sign are not spelled. The language is the live display language (`Devlib.lang`); the sentence is not refreshed by a language switch until the next input. The same text for print comes from the PHP class `Devlib\Terbilang::words($amount, 'IDR')`.

### Read-only, disabled, locked

`readonly` keeps the value posted, removes the "00" button and does not switch to raw text on focus. `disabled` removes the "00" button and the amount from the post. Inside a locked `x-dl::form` the fieldset is disabled, which disables the currency button as well and posts neither the amount nor the currency.

### Language and number format

`1.250.000,50` (Indonesian) or `1,250,000.50` (English), from `window.DL_NUM` (`x-dl::theme-boot` / `Locale::jsSpec()`), frozen at page load: a language switch without reload does not change how existing boxes are read or shown. Without `x-dl::theme-boot` the Indonesian rules apply.

## Data and events

### What the server receives

| Field | Value |
|---|---|
| `<name>` | Canonical number, `.` as decimal separator, no grouping: `1250000.5`. Rewritten from the displayed text by the `formdata` handler in `core.js` (both for a normal submit and `new FormData(form)`) because the input has `data-dl-num`. Empty stays empty; text that is not a number is posted unchanged. |
| `<name>_currency` | The currency code, for example `IDR`. Present unless the field is `disabled`. |

The amount is not tied to the currency on the server: store both, and round to the currency's decimals there.

### Markup hooks

| Hook | Where |
|---|---|
| `data-dl-prefix`, `data-dl-money` | the wrapper `div.dl-prefix` (the script finds the control through `[data-dl-prefix]`) |
| `data-dl-money-input`, `data-dl-decimals`, `data-dl-num` | the amount input |
| `data-dl-prefix-value`, `data-dl-prefix-b` | hidden currency input; code text on the button |
| `data-dl-dd-toggle`, `data-dl-dd-panel`, `data-dl-dd-search`, `.dl-dd__item[data-value]` | currency dropdown |
| `data-dl-dec-toggle` | the "00" button |
| `data-dl-money-words` with `data-currencies` | the words line (only with `words`) |

### Events and JavaScript

- No `dl:*` event and no public API. `el.dlComponent` on the root `.dl-field` is the shared `Field` instance (`pre` = the `[data-dl-prefix]` wrapper).
- Native `input` while typing, `change` after typing + blur; the toggle and the calculator also dispatch `change` on the amount input. Choosing a currency dispatches nothing.
- Typing in the amount marks an `x-dl::form` dirty and clears the "Required" mark; a currency change alone does not mark it dirty.

### Accessibility as implemented

The `<label for>` is tied to the amount input; `inputmode="decimal"` opens the decimal keypad on phones. The currency button is a focusable `<button>` with the code as its only text and no `aria-label`; the list is a plain `div` list without listbox roles. The "00" button has `tabindex="-1"` and a `title` only. The words line is not an `aria-live` region.

## Rules and pitfalls

- Hand the amount over canonical (`1250000`, `"1250000.50"`, a PHP number), never as text formatted for a language.
- Always store `<name>_currency` with the amount; the amount alone is not an amount of money.
- `decimals` does not follow the currency: pass `:decimals="0"` for JPY/IDR style screens. The `words` line also ignores the decimals of the currency (it never spells cents).
- Integer mode (`:decimals="0"`) cannot hold a fraction or a negative amount: the box is digits only. A user who toggles to 2 decimals can type both.
- The words are for convenience; on the printout use `Terbilang::words()` on the stored value so that the document and the screen agree.
- A currency code that is not in `Devlib\Terbilang::CURRENCIES` still works as a label but is spelled without a currency name.
- `required` ignores the currency, so an amount of `0,00` is accepted; check "greater than zero" on the server.
- Custom `currencies` rows must have the `code` key: the view reads `$c['code']` and a row without it fails with an undefined-key error.
- Precision: more than about 15 significant digits lose precision on blur, because the value passes through a JavaScript number.
- Code oddity (not fixed): (1) `resources/assets/js/components/field.js:132-136` fires no event when a currency is chosen, so a change of currency alone does not mark the form dirty and no `change` reaches listeners on the amount. (2) With `words`, an empty amount leaves the empty tinted `.dl-words__text` bar visible (`field.js:130` never toggles `is-empty`, unlike `number-words.js`). (3) `Devlib.fill()` / `setField` (`core.js:663-677`) writes the text and dispatches `input`; on an integer-mode amount the input handler keeps digits only, so filling `1250000.50` gives `125000050`. The mount path guards against this case, the input path does not. In spreadsheet mode the filled canonical text is shown unformatted until the next focus/blur.

## Example

```blade
<x-dl::form.row :cols="3">
    <x-dl::field.money name="price" label="Price" currency="IDR" value="1250000" />
    <x-dl::field.money name="price_words" label="Price with words" currency="IDR" value="1250000" words />
    <x-dl::field.money name="price_usd" label="Price with words (USD)" currency="USD" value="1250" words />
</x-dl::form.row>
```

```blade
{{-- whole amounts only, restricted list of currencies --}}
<x-dl::field.money name="cash" label="Cash received" currency="IDR" :decimals="0" words required
    :currencies="[['code' => 'IDR', 'symbol' => 'Rp'], ['code' => 'USD', 'symbol' => '$']]"
    :value="old('cash', $voucher->amount ?? null)" :error="$errors->first('cash')" />
```

```blade
{{-- fixed precision, amount shown but not editable, still posted --}}
<x-dl::field.money name="total" label="Total" currency="IDR" :value="$order->total" :toggle="false" readonly />
```

## Related

- `x-dl::field.decimal`: the same decimals behavior and "00" toggle without a currency.
- `x-dl::field.number-words`: amount typed together with its spelled-out words and (optional) currency picker; spells cents too.
- `x-dl::field.number`: whole numbers with spin buttons.
- `x-dl::field.text`: full reference of the 14 inherited props and the shared field wrapper.
- `x-dl::form`: required-field check, dirty guard and Undo.
- `x-dl::theme-boot`: writes `window.DL_NUM` (number rules of the display language).
- `Devlib\Terbilang` and `Devlib\Locale` (PHP): amount in words and number formatting for print and server-side code.
