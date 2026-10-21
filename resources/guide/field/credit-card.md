## Overview

`<x-dl::field.credit-card>` is a composite card input: card number (grouped per brand), expiry `MM/YY`, CVV and, optionally, the name on the card, with a live preview card above. As the user types it detects the brand (Visa, Mastercard, American Express, JCB, Discover, Diners Club, UnionPay), formats the number in that brand's groups, limits the number and CVV length, and on leaving a box checks the length, the Luhn checksum, the expiry date and the CVV length.

It is a control on the shared field frame with two own props (`holder`, `preview`) and the 14 inherited ones (full reference: `x-dl::field.text`). One field posts five values (`name`, `name_exp`, `name_cvv`, `name_holder`, `name_brand`). DevLib only collects and checks the shape of the data; it is not a payment integration. Do not store card data without a payment provider and the rules that come with it.

## Props

Constructor of `Devlib\Components\Field\CreditCard` (16 props: 2 own + 14 inherited). Blade names are kebab-case; booleans off with `:holder="false"` / `:preview="false"`.

### Own props

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `holder` | `bool`, `true` | Renders the "Name on card" input (`<name>_holder`, `autocomplete="cc-name"`) with its own case button (default upper case `AA`). `false` removes the input and the button; the preview card then keeps showing the placeholder text "CARD HOLDER" ("NAMA PEMEGANG"). |
| `preview` | `bool`, `true` | Renders the decorative card above the inputs (chip, brand logo, number with `•` for missing digits, holder, expiry; background colour follows `data-brand`). `false` omits the card; the inputs work the same. |

### Inherited props

Full reference: `x-dl::field.text`. What they mean for this control:

| Prop | Type, default | Effect here |
|---|---|---|
| `name` | `string`, `''` | Base name. The card number input is `name`; the others add suffixes: `<name>_exp`, `<name>_cvv`, `<name>_holder` (only with `holder`) and the hidden `<name>_brand`. Also `data-dl-field` and the id slug. |
| `label` | `string`, `''` | Label row; `<label for>` points at the card NUMBER input. Empty = no row, no `*`, no tooltip. Translated. The boxes inside have their own fixed captions (Card number, Valid thru (MM/YY), CVV, Name on card). |
| `tooltip` | `?string`, `null` | (i) bubble next to a non-empty label. Translated. |
| `hint` | `?string`, `null` | Help line under the control (the demo lists test card numbers there). Translated. |
| `error` | `?string`, `null` | Server error text under the control; non-empty adds `is-invalid`. Not translated. Blurring the number, expiry or CVV rewrites the error line (see oddity). |
| `value` | `mixed`, `null` | Not used: the view does not write it into any input. Pre-filling card data is deliberately not supported. |
| `placeholder` | `?string`, `null` | Not used: the boxes have fixed placeholders (`1234 5678 9012 3456`, `MM/YY`, `•••`). |
| `required` | `bool`, `false` | Red `*` and `x-dl::form` "Required" check. The check looks at all named inputs of the field (number, expiry, CVV, holder AND the brand hidden): ANY of them filled counts as "has a value" (see pitfalls). Not the HTML `required`, no server check. |
| `disabled` | `bool`, `false` | Disables the number, expiry, CVV and holder inputs (not posted). The hidden brand input stays enabled and posts `<name>_brand`. |
| `readonly` | `bool`, `false` | The four inputs `readonly` (posted), `is-readonly` on the root; also forced by `Field::$readonlyAll`. The holder case button is NOT disabled (see oddity). |
| `icon` | `?string`, `null` | Not used: the number box shows the brand icon by itself. |
| `size` | `string`, `'sm'` | Root class `dl-field--sm` / `dl-field--lg` only. |
| `i18n` | `?string`, `null` | Key for `data-i18n` on the label (auth screens). |
| `source` | `?string`, `null` | Developer-catalog text; no runtime effect. |

## Behavior

### Markup

```html
<div class="dl-cc" data-dl-cc>
  <div class="dl-cc__card" data-dl-cc-card> ...chip, logo, number, holder, expiry (preview)... </div>
  <input type="hidden" name="kartu_brand" data-dl-cc-brand>
  <div class="dl-field__box"><i data-dl-cc-icon></i><input id="dlf-kartu-xxxxx" type="text" inputmode="numeric" autocomplete="cc-number" name="kartu" maxlength="23" data-dl-cc-num><span data-dl-cc-brandname></span></div>
  <div class="dl-cc__grid">
    <input type="text" inputmode="numeric" autocomplete="cc-exp" name="kartu_exp" maxlength="5" data-dl-cc-exp>
    <input type="password" inputmode="numeric" autocomplete="cc-csc" name="kartu_cvv" maxlength="3" data-dl-cc-cvv>
  </div>
  <input type="text" autocomplete="cc-name" name="kartu_holder" data-dl-cc-holder> + <button data-dl-cc-case>AA</button>
</div>
```

### Brands

The first row whose prefix matches the digits wins (checked in this order). The same table is used for grouping, valid lengths and the CVV length.

| Brand (`_brand` value) | Starts with | Groups | Valid lengths | CVV |
|---|---|---|---|---|
| American Express (`amex`) | 34, 37 | 4-6-5 | 15 | 4 |
| Diners Club (`diners`) | 300-305, 36, 38, 39 | 4-6-4 | 14 | 3 |
| Visa (`visa`) | 4 | 4-4-4-4-3 | 13, 16, 19 | 3 |
| Mastercard (`mastercard`) | 51-55, 2221-2720 range (`2(2[2-9]\|[3-6]\d\|7[01]\|720)`) | 4-4-4-4 | 16 | 3 |
| JCB (`jcb`) | 35 | 4-4-4-4-3 | 16, 19 | 3 |
| Discover (`discover`) | 6011, 65, 644-649 | 4-4-4-4-3 | 16, 19 | 3 |
| UnionPay (`unionpay`) | 62 | 4-4-4-4-3 | 16-19 | 3 |

Unknown prefix: groups 4-4-4-4-3, at most 19 digits, CVV 3, `_brand` empty. The brand name appears at the right end of the number box and the brand icon replaces the generic card icon.

### Typing

- Number: only digits are kept, cut to the longest valid length of the brand, grouped with spaces (`4111 1111 1111 1111`).
- Expiry: digits only, at most four; a first digit above `1` becomes `0x` (`5` -> `05`); the slash is inserted after the month (typing `0125` gives `01/25`; deleting backwards past the slash works).
- CVV: digits only, at most the brand's CVV length (3, or 4 for Amex); the placeholder shows that many `•`. Changing the brand later changes the limit but does not cut digits already typed.
- Holder: the text is converted by the case mode as you type; the mode starts at `AA` (UPPER), the button cycles `aa` -> `Aa` -> `AA`. The posted value is the converted text. The preview always shows the holder in capitals.
- Preview card: number grouped with `•` for the missing digits (digits you typed are shown in full), expiry, holder, `data-brand` for the colours.

### Validation

Run when the number, expiry or CVV box loses focus. All messages are collected and shown joined with ` · ` in the error line (`is-invalid`); nothing is shown while the field is clean.

| Check | Message (English) |
|---|---|
| Digits present but no brand matches | Unknown card type |
| Digit count not one of the brand's valid lengths | A `<brand>` number must have `<n>` digits |
| Luhn checksum fails (only when brand and length are right) | The card number is not valid (check the digits) |
| Expiry typed but not `MM/YY` | The expiry must be MM/YY |
| Month outside 01-12 | The expiry month is 01-12 |
| Expiry before the current month | The card has expired |
| CVV typed with fewer digits than the brand requires | The CVV must have `<n>` digits |

The expiry is compared with "now" in the application time zone: the `data-dl-tz` attribute of `<html>` (the site layout writes it from `config('devlib.timezone')`; your own layout must do the same), `Asia/Jakarta` (WIB) when the attribute is absent. The card is valid through the end of the month printed; the year is read as `20yy` with no upper limit. Empty number, expiry and CVV give no error; the holder is never checked. The checks are advisory: Save still submits an invalid card, because `x-dl::form` validates only `required`. The server (or the payment provider) must validate again.

### Read-only, disabled and locked display

`readonly` / `disabled` act on the four text inputs only (see the prop table). A locked `x-dl::form` disables them, the case button and the hidden brand input together. The preview card stays visible and shows what was typed or stored by script; because `value` is not supported, a read-only card is empty unless a script fills it with `Devlib.fill`.

### Locale and language

No number or date formatting. Captions (Card number, Valid thru, Name on card, As printed, Holder) are dictionary text at render time; `CVV` and `MM/YY` are literal. The validation messages are dictionary texts (`cc_*` keys) in the current page language at the moment of the check. The preview holder placeholder (`cc_holder`) is rendered by the server in the page language.

### Accessibility as implemented

The field label is a real `<label for>` for the number input; the expiry, CVV and holder inputs sit inside their own `<label>` wrappers (their captions are clickable), but the "Card number" caption is a plain `span`. `autocomplete` is `cc-number`, `cc-exp`, `cc-csc`, `cc-name`, and `inputmode="numeric"` on the digit boxes, so browsers and phones can autofill. The CVV box is `type="password"` with no eye button. There is no `aria-*`: the preview card is not hidden from assistive technology, the error line has no `role="alert"`/`aria-live`, and the inputs have no `aria-invalid`.

## Data and events

### What the server receives

| Field | Value |
|---|---|
| `<name>` | The number with spaces as shown (`4111 1111 1111 1111`) or `''`. |
| `<name>_exp` | `MM/YY` as typed, or a partial text such as `01/2` if the user stopped early. |
| `<name>_cvv` | Digits only. |
| `<name>_holder` | The name in the case mode shown (UPPER by default). Only with `holder`. |
| `<name>_brand` | `amex`, `diners`, `visa`, `mastercard`, `jcb`, `discover`, `unionpay` or `''`. Derived in the browser: never trust it. |

Strip the spaces on the server and validate length, Luhn and expiry again. Disabled inputs are not posted (the brand hidden still is).

### Events and hooks

- Native `input` / `change` / `blur` on the inputs bubble. The field emits no `dl:*` event, and the holder case button changes its text without firing `input`.
- Hooks: `data-dl-cc` (root of the control), `data-dl-cc-num`, `-exp`, `-cvv`, `-holder`, `-brand`, `-case`, `-card`, `-icon`, `-brandname`, `-shown`, `-exp-shown`, `-holder-shown`, `-logo`.
- `el.dlComponent` on the `[data-dl-cc]` element is the `CreditCard` instance (`brand` = the matched table row or `null`, methods `upd()` and `check()`); on the root `.dl-field` it is the `Field` instance. Because the inputs are named, they take part in the dirty guard, `x-dl::form` Undo (`fillForm`) and `Devlib.fill`.

## Rules and pitfalls

- Never trust `_brand`, the formatting or the client checks; validate on the server (digits only, length, Luhn, date) or, better, let a payment provider validate.
- Posting the number and the CVV to your own server puts that server in the payment-card security scope. Do not log, store or echo the CVV, and do not put a card into the database without a provider's tokenization.
- `required` is satisfied as soon as ANY part (even only the holder or the CVV) has text. Combine it with a server rule that all parts are present.
- `value` and `placeholder` do nothing; do not use `old()` to refill a card after an error (the CVV should never be sent back).
- The holder case mode defaults to UPPER and is not a prop. `Aa` uses the same ASCII-only word test as `x-dl::field.text` (see its oddity).
- With `:holder="false"` the preview still shows the text "CARD HOLDER"; also set `:preview="false"` if there is no holder to show.
- A card that expires this month is still valid; do not apply an extra "next month" rule without saying so.
- The time zone for the expiry comes from the page, not from the user's device.
- Code oddity (not fixed): the holder case button is not disabled by `readonly` or `disabled` (`resources/views/components/field/credit-card.blade.php:17`, handler `resources/assets/js/components/credit-card.js:27`), so a locked field can still have its holder text changed.
- Code oddity (not fixed): the case button rewrites the holder value without firing `input`/`change` (`resources/assets/js/components/credit-card.js:27`), so it does not mark the form dirty.
- Code oddity (not fixed): `check()` (`resources/assets/js/components/credit-card.js:71`) always rewrites the error line, so a server `error` disappears on the first blur of the number, expiry or CVV.
- Code oddity (not fixed): `hasValue` (`resources/assets/js/core.js:689`) counts every named input, including `<name>_brand`, so the form-level required check passes with a single sub-input filled.

## Example

Copied in shape from the catalog demo.

```blade
<x-dl::field.credit-card name="kartu" label="Kartu Kredit / Debit" hint="Nomor uji: 4111 1111 1111 1111 (Visa), 5555 5555 5555 4444 (Mastercard), 3782 822463 10005 (Amex)" />
```

Without the holder name and the preview card (compact), required, with a server error:

```blade
<x-dl::field.credit-card name="card" label="Card" :holder="false" :preview="false" required :error="$errors->first('card')" />
```

## Related

- `x-dl::field.text`: full reference for the inherited props, the shared frame and the case badge.
- `x-dl::field.masked`: fixed-pattern digits (identifiers) without a brand table.
- `x-dl::form`: required-field validation, dirty guard and Undo.
