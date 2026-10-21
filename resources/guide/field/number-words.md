## Overview

`<x-dl::field.number-words>` is an amount box that spells the number out while the user types: type `1250000` and the line below reads "Satu juta dua ratus lima puluh ribu rupiah" (Indonesian display language) or "One million two hundred fifty thousand rupiah" (English). It can be a plain number ("Dua belas koma lima" / "Twelve point five"), a number with a fixed currency, or a number with a currency the user picks from a list. The text language follows the active display language, the number is posted in canonical form (`1234.56`), and the currency code is posted next to it.

Files: `src/Components/Field/NumberWords.php`, the view `resources/views/components/field/number-words.blade.php`, the class `NumberWords` in `resources/assets/js/components/number-words.js` (selector `[data-dl-words]`) and the dropdown branch `[data-dl-prefix]` of the shared `Field` class (`field.js`). Wording comes from the same dictionary in PHP and JavaScript: `Devlib\Terbilang` and `words()` in `core.js`.

### What it is not

- Not `x-dl::field.money`: no "00" decimals toggle, no spreadsheet-style raw/formatted text, and the thousand separators are applied while typing. In exchange it spells the cents too.
- Not a calculator or converter: the currency is a label with a code, and its list is fixed to `Devlib\Terbilang::CURRENCIES`.
- Not a validator: no minimum or maximum; the server must validate.

## Props

Constructor of `Devlib\Components\Field\NumberWords` (17 props: 3 own, 14 inherited). Blade names are kebab-case; use `:prop="expr"` for anything that is not a plain string (`:decimals="3"`); `picker` alone means `true`.

### Own props

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `currency` | `?string`, `null` | Currency code: one of `IDR`, `USD`, `EUR`, `SGD`, `MYR`, `AUD`, `JPY`, `CNY`, `GBP` (upper case). With a currency the box gets a code button on its left, the hidden field `<name>_currency` is posted, the currency name is added to the words, and the number of decimals is the currency's (IDR 0, JPY 0, the others 2). Without it the field is a plain number. |
| `picker` | `bool`, `false` | The user can choose the currency from a searchable list. Without `picker` the code button is rendered `disabled` (a fixed label). `picker` without `currency` sets `currency` to `IDR` (done in the constructor). |
| `decimals` | `int`, `0` | Maximum decimals the user can type, **only when there is no currency** (`data-decimals`). With a currency, its own decimals win and this prop is ignored. |

### Inherited props

All 14 come from `Devlib\Components\Field\Field`; the full reference is on `x-dl::field.text`. How this control uses them:

| Prop | Use in this control |
|---|---|
| `name` | `name` of the hidden input that carries the canonical number; the visible input has **no** name. With a currency, `<name>_currency` is posted too. |
| `label` | Label text (`for` = the visible input id). Empty string = no label row. Translated. |
| `tooltip` | The (i) bubble next to the label (needs a label). Translated. |
| `hint` | Help line under the control (under the words line). Translated. |
| `error` | Server error text; non-empty marks the field invalid. Not translated. |
| `value` | Initial number, canonical (`1250000`, `"1234.56"`, `12.5`). Written unformatted into the hidden input; the script then shows it in the box. |
| `placeholder` | Placeholder of the visible input (translated); default `0`. |
| `required` | Red `*`; the form check marks an empty number "Required" (it tests the hidden input; the currency code is ignored). `0` counts as a value. |
| `disabled` | Disables the visible input (and with a picker nothing else). The hidden number and currency are still posted (see the oddity). |
| `readonly` | Read-only visible input; root gets `is-readonly`. Forced on while `Field::$readonlyAll` is true. The value is posted. |
| `icon` | Not used by this control. |
| `size` | `sm` (default) or `lg`; only the CSS class `dl-field--sm` / `dl-field--lg`. |
| `i18n` | `data-i18n` key on the label for auth screens; normal screens never set it. |
| `source` | Free text for the developer catalog; default `input "<name>"`. No visual effect. |

## Behavior

### Markup

```html
<div class="dl-words" data-dl-words data-currencies="{…}" data-decimals="0">
  <input type="hidden" name="nilai" value="1234.56" data-dl-words-value>            <!-- posted -->
  <div class="dl-prefix" data-dl-prefix>                                           <!-- only with currency -->
    <input type="hidden" name="nilai_currency" value="USD" data-dl-prefix-value>     <!-- posted -->
    <button type="button" class="dl-prefix__btn" data-dl-dd-toggle>…USD…</button>   <!-- disabled without picker -->
    <input id="dlf-nilai-xxxxx" type="text" inputmode="decimal" class="dl-input dl-input--boxed dl-input--prefixed dl-words__input" data-dl-words-input>
    <div class="dl-dd__panel" data-dl-dd-panel>…search + one item per currency…</div> <!-- only with picker -->
  </div>
  <div class="dl-words__text" data-dl-words-text></div>
</div>
```

Without a currency the visible input is inside a plain `.dl-field__box`. Extra HTML attributes go on the root `div.dl-field`.

### Typing (`upd()` in `number-words.js`)

On every `input` the visible text is re-read and rewritten:

1. The decimal separator of the display language (`NUM.dec`: `,` Indonesian, `.` English) is the only separator kept; everything except digits and that separator is removed. A leading `-` is kept (negative numbers are allowed: "Minus ...").
2. The integer part loses leading zeros, is cut to 15 digits and is shown with thousand separators (`1.234.567`). The decimal part is cut to `places` digits, where `places` is the decimals of the chosen currency, or `decimals` when there is no currency. If `places` is 0 the decimal separator is dropped.
3. The hidden input receives the canonical text: sign, integer, `.`, fraction (`1234.56`); `''` when nothing is typed. The words line is the spelled-out hidden value and gets the class `is-empty` (hidden) when there is no number.

Because the text is rebuilt on every keystroke, input from another number format is misread: pasting `1,234.56` into an Indonesian box gives `1,23` with two places (the comma is the decimal separator, the dot is dropped). Unlike `x-dl::field.decimal`, the box does not use the smart parser.

### Words

`words()` (the same algorithm as `Devlib\Terbilang::words()`) spells the integer part and then:

- with a currency: the currency name (`rupiah`, `dolar Amerika Serikat` / `US dollars`, `euro`, ...) and, when the currency has decimals and the fraction is not zero, `dan` / `and` + the fraction as a number + the sub-unit (`sen`, `cents`): "Seribu dua ratus tiga puluh empat dolar Amerika Serikat dan lima puluh enam sen";
- without a currency: ` koma ` / ` point ` + each decimal digit spoken one by one, trailing zeros dropped: 12.5 gives "Dua belas koma lima" / "Twelve point five";
- zero gives "Nol" / "Zero"; a leading minus gives "Minus ...". The first letter is a capital.

The language of the words is the live display language (`Devlib.lang`: the `?lang=` parameter, `DL_LANG_ACTIVE`, then Indonesian). The line is rewritten on typing and on currency choice; it is not refreshed when the language is switched, and the separator rules (`NUM`) are frozen at page load.

### Currency picker

With `picker` the button opens a searchable list (`CODE · name` and the symbol column). Choosing a row sets `<name>_currency`, the button text, re-applies `places` to the typed text (switching from USD to IDR drops the typed cents), refreshes the words and fires a bubbling `change` event on the hidden number input. The code appears twice on the button (`IDR IDR`) because the first element of each `Terbilang::CURRENCIES` row is now the code itself.

### Mount

If the hidden input has a value, the script writes it into the box with the first `.` replaced by `,` and then runs `upd()`. Digits beyond `places` are cut: a value `1250000.50` with `currency="IDR"` becomes `1250000` in the hidden input as soon as the page mounts.

### Read-only, disabled, locked

`readonly` and `disabled` apply to the visible input only. Inside a locked `x-dl::form` the fieldset is disabled, which disables the visible input and the currency button and stops the post of the hidden inputs.

## Data and events

### What the server receives

| Field | Value |
|---|---|
| `<name>` | The hidden canonical number: `.` as decimal separator, no grouping, `-` for negatives (`1234.56`), `''` when empty. It is **not** `data-dl-num`; the script already keeps it canonical. |
| `<name>_currency` | The currency code, only when `currency` (or `picker`) is set. |

The visible input has no `name` and is never posted.

### Markup hooks

| Hook | Where |
|---|---|
| `data-dl-words`, `data-currencies`, `data-decimals` | the root `.dl-words` (selector of the `NumberWords` class) |
| `data-dl-words-value` | hidden input with the posted number |
| `data-dl-words-input` | the visible input |
| `data-dl-words-text` | the words line (`.is-empty` while there is no number) |
| `data-dl-prefix`, `data-dl-prefix-value`, `data-dl-prefix-a`, `data-dl-prefix-b` | currency wrapper, hidden code, button parts |
| `data-dl-dd-toggle`, `data-dl-dd-panel`, `data-dl-dd-search`, `.dl-dd__item` | the picker |

### Events and JavaScript

- No `dl:*` event. `el.dlComponent` on the `.dl-words` element is the `NumberWords` instance (`inp`, `hid`, `out`, `cc`, `C`; methods `places()` and `upd()`); on the outer `.dl-field` it is the shared `Field` instance.
- The visible input emits native `input` events (it has no name). A currency choice fires a bubbling `change` on the **hidden** input.
- To set a value from script, assign the visible input and dispatch `input` on it; setting only the hidden input does not update the box or the words (this is what `Devlib.fill()` and Undo do, see the oddity).
- Typing in the box does not mark an `x-dl::form` dirty (the dirty guard only reacts to named inputs); choosing a currency does.

### Accessibility as implemented

The `<label for>` is tied to the visible input; `inputmode="decimal"` opens the decimal keypad. The currency button is a `<button>` with the code as text and no `aria-label`; the words line is a plain `div`, not an `aria-live` region.

## Rules and pitfalls

- Pass `currency` exactly as in `Terbilang::CURRENCIES` (upper case). A lowercase or unknown code makes `places()` throw a `TypeError` while the field mounts, which can keep later components from initialising. `Terbilang::words()` in PHP is more tolerant (it upper-cases), the script is not.
- Pass `value` canonical (`1234.56`), never a formatted text. It is printed unformatted into the hidden input.
- Decimals beyond the currency's own are cut at mount and while typing. For a price list in IDR use `x-dl::field.money`.
- The visible text and the posted number can differ in format by design: store only the posted value, and print the words from the stored value with `Devlib\Terbilang::words($value, $currency)`.
- `required` tests the hidden number: it fails when the box is empty, and `0` passes.
- Do not read the visible input on the server or in your script; it has no name. Listen to `input` on `[data-dl-words-input]` or read `[data-dl-words-value]`.
- Code oddity (not fixed): (1) `resources/assets/js/components/number-words.js:16` always replaces the dot of the initial value with a comma, but in an English display language `NUM.dec` is `.`, so `upd()` strips that comma and a value such as `12.5` becomes `125` (and is posted as `125`). (2) `number-words.blade.php:2-6`: the hidden number and currency inputs are not disabled by `disabled`, so a disabled field still posts. (3) Typing never marks the form dirty and the Undo button stays "nothing to undo" after typing only (the visible input has no name; `number-words.js` changes the hidden input without an event). (4) `Devlib.fill()` / Undo (`core.js:663-677`) set only the hidden input, so the box and the words keep showing the old text while the old value is posted. (5) The button shows the code twice (`IDR IDR`), see Currency picker.

## Example

```blade
<x-dl::form.row :cols="2">
    <x-dl::field.number-words name="quantity" label="Plain number" value="1200" />
    <x-dl::field.number-words name="weight" label="Number with decimals" :decimals="3" value="12.5" />
    <x-dl::field.number-words name="amount_idr" label="Rupiah" currency="IDR" value="1250000" />
    <x-dl::field.number-words name="amount" label="Currency can be chosen" picker currency="USD" value="1234.56" />
</x-dl::form.row>
```

```blade
{{-- voucher amount: required, restored after a failed save, error under the field --}}
<x-dl::field.number-words name="amount" label="Amount" currency="IDR" required
    :value="old('amount', $voucher->amount ?? null)" :error="$errors->first('amount')" />
```

```blade
{{-- printout / server side: the same words as on screen --}}
{{ \Devlib\Terbilang::words($voucher->amount, $voucher->amount_currency) }}
```

## Related

- `x-dl::field.money`: amount with currency, "00" decimals toggle and an optional words line.
- `x-dl::field.decimal`: number with decimals and a unit, without words.
- `x-dl::field.number`: whole numbers with spin buttons.
- `x-dl::field.text`: full reference of the 14 inherited props and the shared field wrapper.
- `x-dl::form`: required-field check, dirty guard and Undo.
- `Devlib\Terbilang` and `Devlib\Locale` (PHP): the wording and number formatting used for print and server-side code.
