## Overview

`<x-dl::field.tolerance>` is one compact control for a tolerance: a direction dropdown in front, a number in the middle and a unit dropdown at the back, for example `± 10 %` or `− 100 pcs`. It replaces three separate fields (direction, value, unit) with one box. The number follows `x-dl::field.decimal` (typed raw, shown in the display language, posted canonical) but the whole part is limited to `digits` digits (default 4, so at most `9999`).

The control posts three values: `name` (the canonical number), `dir-name` (direction code) and `unit-name` (unit code). What a tolerance means (range, rounding, what counts as done) is the business of the project; this control only collects the three values.

It uses the shared wrapper `resources/views/components/field.blade.php`, the control view `resources/views/components/field/tolerance.blade.php`, the `[data-dl-decimal]` branch of the shared `Field` class in `field.js` (with `data-dl-maxint`) and the `.dl-tol*` rules in `devlib.css`.

### What it is not

- Not a validator: no minimum, no range check, no rule that a unit fits the number (percent above 100). The server must check.
- Not a two-sided range input (from / to); it is one signed size.

## Props

Constructor of `Devlib\Components\Field\Tolerance` (22 props: 8 own, 14 inherited).

### Own props

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `dir-name` | `string`, `''` | `name` of the direction select. Empty = `<name>_dir` (empty when `name` is empty). |
| `unit-name` | `string`, `''` | `name` of the unit select. Empty = `<name>_unit`. |
| `dir` | `string`, `'BOTH'` | Selected direction code. |
| `unit` | `string`, `'PCT'` | Selected unit code. |
| `dirs` | `array`, `['BOTH' => '±', 'PLUS' => '+', 'MINUS' => '−']` | Direction options, code => symbol shown. Escaped, not translated. |
| `units` | `array`, `['PCT' => '%', 'QTY' => 'pcs']` | Unit options, code => text shown. |
| `decimals` | `int`, `2` | Decimals shown after leaving the box (`data-dl-decimal`); not a limit on typing. |
| `digits` | `int`, `4` | Maximum digits of the whole part, enforced while typing (`data-dl-maxint`): `12345` becomes `1234`. `0` = no limit. |

### Inherited props

The 14 props of `Devlib\Components\Field\Field` (full reference on `x-dl::field.text`). `name` is the number input; `label` gives the label row (for = the number input); `value` is the canonical number; `required`, `hint`, `error`, `tooltip` work as on `x-dl::field.decimal`. `disabled` disables all three controls (nothing posted); `readonly` locks the dropdowns (disabled, not posted) and makes the number read-only (posted). `icon` is not used. `placeholder` is the hint of the number box (default zero with `decimals`); `size` is `sm` (default) or `lg` (CSS class only); `i18n` is the label key for auth screens, normal screens never set it; `source` is free catalog text with no visual effect.

## Behavior

### Markup

```html
<div class="dl-field__box dl-tol">
  <span class="dl-tol__sw"><select class="dl-tol__sel dl-tol__dir" name="ship_tol_dir">…</select></span>
  <input type="text" inputmode="decimal" name="ship_tol_value" class="dl-input dl-tol__num" data-dl-decimal="2" data-dl-maxint="4" data-dl-num>
  <span class="dl-tol__sw"><select class="dl-tol__sel dl-tol__unit" name="ship_tol_mode">…</select></span>
</div>
```

The box is as wide as its three parts (`width: max-content`), about 190 px at the default size, so it fits one narrow column.

### Typing

Same as `x-dl::field.decimal` (raw text on focus, formatted on blur, only digits `.` `,` `-` accepted) plus the cap on whole digits. The "00" toggle is not shown.

## Data and events

The server receives `name` as a canonical number (`10.5`; empty stays empty), `dir-name` and `unit-name` as the chosen codes. Disabled controls are not posted. No `dl:*` event; native `input` / `change` as on the decimal field.

## Rules and pitfalls

- An empty number with a direction and unit chosen is still posted with those two codes; treat an empty number as "no tolerance" on the server and ignore the other two.
- Pass `value` canonical (`10`, `"2.5"`); pass `dir` / `unit` as codes that exist in `dirs` / `units`, otherwise the first option is shown.
- `digits` limits typing only; a pasted or filled value can still exceed it. Validate on the server.
- Keep the symbols short (one or two characters, `pcs`); the dropdown widths are fixed by padding, not by content.

## Example

```blade
<x-dl::field.tolerance name="ship_tol_value" dir-name="ship_tol_dir" unit-name="ship_tol_mode"
    label="Shipping Tolerance" :value="$order->ship_tol_value" :dir="$order->ship_tol_dir ?: 'BOTH'" :unit="$order->ship_tol_mode ?: 'PCT'" />
```

## Related

- `x-dl::field.decimal`: the number behavior this control reuses.
- `x-dl::field.money`: amount with a currency selector.
- `x-dl::field.text`: full reference of the 14 inherited props.
