## Overview

`<x-dl::field.date-range>` is a start date and an end date side by side, with an automatic duration line below (`Duration: 6 Years 7 Months`). Use it for a period that belongs together: employment period, contract term, validity.

Unlike `x-dl::field.date`, it is built from two **native** `<input type="date">` boxes. The browser draws the picker and shows the date in the user's own browser format; the server always receives ISO `yyyy-mm-dd` in two separate fields. There is no `dd-Mmm-yyyy` display, no typed shorthand (`10/10/26`) and no DevLib calendar here.

The class is `Devlib\Components\Field\DateRange` (`control()` = `date-range`, three own props `name-end`, `value-end`, `duration`). The control is `resources/views/components/field/date-range.blade.php`; the duration text is the `data-dl-daterange` branch of the shared `Field` script (`resources/assets/js/components/field.js`).

### What it is not

- Not a range validator: nothing forces end >= start, and there is no `min`/`max`. The duration line only says the end date "is not valid" when it is before the start.
- Not one value: it is two fields, so the server reads two request keys.
- Not a date-time: only dates.

## Props

### Own props

| Prop | Type, default | Meaning |
|---|---|---|
| `name-end` | `?string`, `null` | `name` of the end input. When not set the name is `<name>_end` (for `name="awal"`: `awal_end`). Set it when the form or table already has a column for it. |
| `value-end` | `mixed`, `null` | Initial value of the end input, written to its `value` attribute. Must be `yyyy-mm-dd`; a native date box shows nothing for any other form (for example `Y-m-d H:i:s` from a Carbon object). |
| `duration` | `bool`, `true` | Renders the duration line (`<span class="dl-field__hint" data-dl-duration>`) below the two boxes. `:duration="false"` removes the line and the calculation. |

### Inherited props

All 14 come from the shared `Field` base. The full reference is in `x-dl::field.text`.

| Prop | Type, default | On this control |
|---|---|---|
| `name` | `string`, `''` | Name of the **start** input. Also `data-dl-field` on the root. |
| `label` | `string`, `''` | Label text; `for` points at the start input only. Empty = no label row. |
| `tooltip` | `?string`, `null` | (i) bubble next to the label (needs a label). |
| `hint` | `?string`, `null` | Help line under the control (separate from the duration line); rendered whenever not null. |
| `error` | `?string`, `null` | Server error text; non-empty marks the field `is-invalid` (red border on both boxes). One message for the pair. Not translated. |
| `value` | `mixed`, `null` | Initial value of the start input; `yyyy-mm-dd`. |
| `placeholder` | `?string`, `null` | Not used by this control (native date boxes have none). |
| `required` | `bool`, `false` | Red `*` and the `x-dl::form` Save/Confirm check. The check passes as soon as **one** of the two boxes has a value. No HTML `required`; the server must check again. |
| `disabled` | `bool`, `false` | Both boxes get the native `disabled`: not posted, not editable. |
| `readonly` | `bool`, `false` | Both boxes get the native `readonly` (posted); root gets `is-readonly`. Forced on while `Field::$readonlyAll` is true. |
| `icon` | `?string`, `null` | Not used by this control. |
| `size` | `string`, `'sm'` | CSS size class only: `sm` or `lg`. |
| `i18n` | `?string`, `null` | `data-i18n` on the label for auth screens; normal screens never set it. |
| `source` | `?string`, `null` | Free text for the developer catalog; no visual effect. |

## Behavior

### Markup

Root `.dl-field.dl-field--date-range`, inside it `.dl-daterange[data-dl-daterange]` (a two-column grid, 8px gap) with two `input[type=date].dl-input.dl-input--boxed`: start (`name`, id = `uid`) and end (`name-end`). Then, with `duration`, the span `[data-dl-duration]`, then the error and hint lines of the frame.

### Picker, format and typing

Everything about the boxes is the browser's: the picker, the display format and language (follow the browser or system locale, not DevLib's display language) and keyboard entry segment by segment. No `min` or `max` is set on the boxes. A box only reports a value once the date is complete and real; an incomplete entry counts as empty.

### Duration line

On load and on every `change` of either box:

- If one box is empty, the line is **not updated** (it keeps its last text, or stays empty on load).
- If end < start: `Duration: the end date is not valid.`
- Otherwise it counts whole calendar months: `(end year - start year) * 12 + (end month - start month)`, shown as `Duration: {y} Years {m} Months`. Days are ignored (`2026-01-31` to `2026-02-01` is 1 month, `2026-01-01` to `2026-01-31` is 0 years 0 months). Same day is `0 Years 0 Months`.

The words follow the active display language (dictionary keys `fd_duration`, `fd_dur_invalid`; Indonesian `Durasi: {y} Tahun {m} Bulan`).

### Read-only and locked

`readonly` and `disabled` are the native attributes on both boxes. In a locked `x-dl::form` (disabled fieldset) both boxes are disabled and not posted.

## Data and events

### What the server receives

Two fields: `name` (start) and `name-end` (end, default `<name>_end`), each `yyyy-mm-dd` or an empty string. Disabled boxes are not sent. Validate with `date_format:Y-m-d`, and for the order `after_or_equal:<name>` on the end field (DevLib does not do it).

### Events

| Event | Target | When |
|---|---|---|
| `input` / `change` (bubble) | each date box | Native browser events. The script listens to `change` for the duration line. |

No `dl:*` event and no hidden input. Both boxes are named, so `x-dl::form` marks itself dirty on a change and removes a "Required" error as soon as a box has a value. `el.dlComponent` on the root `.dl-field` is the shared `Field` instance; the duration is internal, there is no public method.

### Markup hooks

| Hook | Where |
|---|---|
| `data-dl-field="<name>"` | root `.dl-field` |
| `data-dl-daterange` | the wrapper of the two boxes |
| `data-dl-duration` | the duration line |
| class `dl-daterange` | the two-column grid |

## Rules and pitfalls

- Render both values with `->format('Y-m-d')`: `:value="old('awal', $c->start_date?->format('Y-m-d'))" :value-end="old('akhir', $c->end_date?->format('Y-m-d'))"`.
- Read both keys on the server and check the order there. The duration line is only a visual aid; a user can submit an end before the start.
- `required` is satisfied by one box. If both dates are mandatory, validate the second on the server (or add a second `required` field).
- The error and hint are shared by both boxes; show one message for the pair.
- If you need `dd-Mmm-yyyy`, typing shorthand or the same look as the rest of the form, use two `x-dl::field.date`; you then lose the duration line.
- Accessibility as implemented: the label is bound to the start box only; the end box has no label, `aria-label` or visible caption, so assistive technology announces it without a name. The native date inputs are fully keyboard operable by the browser.
- Code oddity (not fixed): when one of the two dates is cleared after both were set, the duration line (`field.js`, `data-dl-daterange`) is not cleared and keeps showing the old duration.
- Code oddity (not fixed): the duration is computed with `new Date('yyyy-mm-dd')` (UTC midnight) and local `getMonth()`; in browsers west of UTC both dates move to the previous day, so a start on the 1st counts as the previous month and the number of months can be off by one.

## Example

```blade
<x-dl::field.date-range name="awal" name-end="akhir" label="Employment period" value="2020-01-01" value-end="2026-08-31" />
```

Without duration line, required, with old input and server error:

```blade
<x-dl::field.date-range name="valid_from" name-end="valid_to" label="Valid" :duration="false" required
    :value="old('valid_from', $p->valid_from?->format('Y-m-d'))" :value-end="old('valid_to', $p->valid_to?->format('Y-m-d'))"
    :error="$errors->first('valid_to')" />
```

Default end name (`period_start` and `period_start_end`):

```blade
<x-dl::field.date-range name="period_start" label="Period" />
```

## Related

- `x-dl::field.text`: the full reference of the 14 inherited props and the shared wrapper.
- `x-dl::field.date`: one DevLib date (typed or picked, `dd-Mmm-yyyy`).
- `x-dl::field.date-slider`: pick a period by sliding (`range`).
- `x-dl::field.date-time`: date and time in one popover.
- `x-dl::form`: required check, dirty guard, locked display.
