## Overview

`<x-dl::field.date>` is the standard single-date input of DevLib. The user types a date or picks it in a small calendar popover. What is shown is always `dd-Mmm-yyyy` (`10-Oct-2026`, `10-Okt-2026`); what is posted to the server is always ISO `yyyy-mm-dd`. Use it for any date that has no time part (birth date, document date, due date). For a date with a time use `x-dl::field.date-time`; for two dates (start and end) use `x-dl::field.date-range`.

It is a thin class (`Devlib\Components\Field\Date`, no own props, `control()` = `date`) inside the shared field frame (label, `*`, tooltip, control, error, hint). The control itself is `resources/views/components/field/date.blade.php`, made alive by the `data-dl-date` branch of the shared `Field` script (`resources/assets/js/components/field.js`) and the date object `DlDate` in `core.js` (PHP counterpart: `Devlib\DateValue`).

### What it is not

- Not a native `<input type="date">` (that is `x-dl::field.date-range`). The browser date picker is never used here, so the look and the accepted typing are the same in every browser.
- Not a validator: it has no `min`, `max`, weekday filter or "no past dates" option. Range rules belong to the server.
- Not a date-time: a value such as `2026-10-10 14:30` is cut to its date part.

## Props

`Date` has no props of its own. All 14 constructor props come from the shared `Field` base; they are listed below with what they do on this control. Blade names are kebab-case; use `:prop="expr"` for anything that is not a plain string. The full reference of the inherited props is in `x-dl::field.text`.

### Inherited props

| Prop | Type, default | On this control |
|---|---|---|
| `name` | `string`, `''` | Name of the **hidden** input that carries the ISO value (`data-dl-value`). The visible text box has no `name`, so only the ISO value is posted. Also `data-dl-field` on the root. No name = nothing is posted. |
| `label` | `string`, `''` | Text of the label; `for` points at the visible text box (its id is the generated `uid`). Empty = no label row (and no `*`, no tooltip). |
| `tooltip` | `?string`, `null` | Small (i) bubble next to the label (needs a label). |
| `hint` | `?string`, `null` | Help line under the control; rendered whenever not null. |
| `error` | `?string`, `null` | Server error text under the control; non-empty marks the field `is-invalid`. Not translated. |
| `value` | `mixed`, `null` | Initial value, written to the hidden input. Read by `DlDate`: `yyyy-mm-dd` (also `yyyy-mm-dd hh:mm...`, date part only) and any form the user may type. A Carbon object works because it is cast to a string (`Y-m-d H:i:s`). An unreadable value is shown as empty and the hidden input is emptied on load. |
| `placeholder` | `?string`, `null` | Placeholder of the text box; default `dd-Mmm-yyyy` (not translated when left at the default). |
| `required` | `bool`, `false` | Red `*` and the `x-dl::form` check on Save/Confirm ("Required" when the hidden value is empty). No HTML `required` attribute; the server must check again. |
| `disabled` | `bool`, `false` | Disables the text box, the 📅 button and the hidden input (grey, nothing is posted). |
| `readonly` | `bool`, `false` | Text box read-only, the calendar button is not rendered, root gets `is-readonly`. The value is still posted. Forced on while `Field::$readonlyAll` is true. |
| `icon` | `?string`, `null` | Not used by this control (the 📅 button is fixed). |
| `size` | `string`, `'sm'` | CSS size class only: `sm` or `lg`. |
| `i18n` | `?string`, `null` | Written to `data-i18n` on the label for auth screens; normal screens never set it. |
| `source` | `?string`, `null` | Free text for the developer catalog; no visual effect. |

## Behavior

### Markup

Root `.dl-field.dl-field--date`, inside it `.dl-field__box.dl-pick[data-dl-date]` with:

- `input[type=hidden][name][data-dl-value]`: the value that is posted (ISO).
- `input[type=text].dl-input[data-dl-date-text]`: what the user sees and types, `autocomplete="off"`, id = `uid`.
- `button.dl-field__trigger[data-dl-dd-toggle]`: the 📅 button (title "Choose a date"). Not rendered when `readonly`.
- `.dl-pick__panel[data-dl-dd-panel]`: the calendar (header "Pick a date", `<` month `>`, weekday names, day grid, `Clear` and `Today`).

### Typing

The user may type in the text box (like an Excel cell). On leaving the box (native `change`) or pressing Enter the text is read by `DlDate.of`:

| Typed | Result |
|---|---|
| `10-10-2026`, `10/10/26`, `10.10.2026`, `10 10 2026` | 10 Oct 2026 (day first; `-`, `/`, `.` or spaces separate). |
| `10-Oct-2026`, `10 okt 2026`, `10 October 26` | Month by name: first three letters in the display language, in English, or the Indonesian spellings `mei`, `agt`, `agu`, `okt`, `des`, `peb`. |
| `10.10` | No year = the current year (browser clock). |
| `2026-10-10`, `2026-10-10 14:30` | ISO; the time part is ignored (no time-zone shift). |
| `31-02-2026` | Impossible date: rejected. |

A two-digit year is read as 20xx (`26` = 2026). A valid entry is shown reformatted as `dd-Mmm-yyyy`, the hidden input gets the ISO value and a `change` event is fired. An empty box clears the value (hidden = `''`). An unreadable entry only adds `is-invalid` to the field (red border); no message text is set, the typed text stays in the box and the hidden input keeps the previous value.

### Calendar popover

- Opens with the 📅 button, closes after picking a day, `Today` or `Clear`, when another picker opens, or on any click outside (document click handler in `boot.js`). There is no Escape handler.
- The panel is positioned `fixed` under the box (flips above the box when there is no room below), so it is not clipped by cards or scroll areas, and follows scroll/resize while open.
- The month title is `Mmm yyyy` with the 3-letter month of the display language. The week starts on Sunday. Weekday headings are two letters (`dow2()`; Indonesian `Mg Sn Sl Rb Km Jm Sb`).
- The selected day has class `is-sel`. There is no min/max, no disabled day and no marker for today.
- `Today` uses the application time zone: `data-dl-tz` on `<html>`, default `Asia/Jakarta`. The layout should copy `config('devlib.timezone')` into it (the demo layouts do).
- `Clear` is not translated (always English); `Today` and the header are translated.
- The shown month is set once at load (month of the value, or the current month when empty) and then only moves with `<` / `>`; typing a new date or reopening the popover does not re-centre it.

### Language and format

Month names, the parse of month words and the weekday names follow the display language: the server renders them from `Devlib\Locale` and the script reads `window.DL_NUM` (written by `x-dl::theme-boot`). `DL_NUM` is frozen when the page loads: switching the language flag without a reload does not change how already shown fields read or write dates. The posted value never depends on the language.

### Read-only and locked

`readonly`: the text box cannot be edited and there is no calendar button; the hidden input is still submitted. Inside a locked `x-dl::form` (the whole content is in a disabled fieldset) the text box, the hidden input and the button are disabled by the browser, so nothing is posted; the form also hides `.dl-field__trigger`.

## Data and events

### What the server receives

One field `name` with `yyyy-mm-dd`, or an empty string when empty. The visible text is never posted. For a Laravel validator use `date_format:Y-m-d` (add `nullable` for an optional field). To parse typed or stored values on the server use `Devlib\DateValue::of($v)`, which reads the same forms as the script (`->iso()`, `->display()`).

### Events

| Event | Target | When |
|---|---|---|
| `change` (bubbles) | the hidden `[data-dl-value]` input | A day is picked, `Today`, `Clear`, or a valid/empty entry is typed. Not fired on the first load. |
| `change` (bubbles) | the text box | Native, when the user leaves the box (before the script reads it). |

Because the hidden input has a `name`, these events mark an `x-dl::form` as dirty (dirty guard). Setting a value removes the field's `is-invalid` class. The field fires no `dl:*` event. `el.dlComponent` on the root `.dl-field` is the shared `Field` instance; it has no public method for this control. To set the value from script, use `Devlib.h.setField(form, name, value)` (also used by form Undo and `Devlib.fill`), which writes the hidden input and refreshes the text.

### Markup hooks

| Hook | Where |
|---|---|
| `data-dl-field="<name>"` | root `.dl-field` |
| `data-dl-date` | the picker box |
| `data-dl-value` | hidden ISO input |
| `data-dl-date-text` | visible text box |
| `data-dl-dd-toggle`, `data-dl-dd-panel` | calendar button, popover |
| `data-dl-cal-prev`, `-next`, `-days`, `-clear`, `-today`, `-title` | calendar parts |
| `data-d="yyyy-mm-dd"` | each day button |
| classes `is-invalid`, `is-readonly`, `is-open` (panel), `is-sel` (day) | state |

## Rules and pitfalls

- Pass the value as `yyyy-mm-dd` (`$model->date?->format('Y-m-d')` or the model attribute cast). Re-fill after a failed validation with `old('x', $model->x)`; the old value is the posted ISO string.
- Always validate on the server: the script only accepts real dates, but a hand-made request can post anything. `required` is only a client check in `x-dl::form`.
- The posted value is a calendar date, not an instant: there is no time zone conversion in either direction.
- For a two-field period use `x-dl::field.date-range` (native pickers) or two `x-dl::field.date`; there is no link between two date fields (no "end must be after start").
- An invalid typed entry leaves the **old** value in the hidden input while the box shows the bad text. Only a red border is shown. A user who ignores it submits the old value; add a server error.
- Accessibility as implemented: the text box is a normal input with a `<label for>`, the 📅 button and every day are real `<button>` elements (reachable with Tab, activated with Enter/Space), and Enter in the text box commits the typing. There are no arrow-key moves between days, no Escape, no focus management into the popover, no `aria-expanded` and no `aria-label` on the button (its accessible name is the emoji/title).

## Example

```blade
<x-dl::field.date name="tgl" label="Birth date" value="1969-10-01" />
```

Required, with a server error and old input after a failed Save:

```blade
<x-dl::field.date name="order_date" label="Order date" :value="old('order_date', $order->order_date?->format('Y-m-d') ?? now()->format('Y-m-d'))"
    :error="$errors->first('order_date')" required hint="Typed or picked; shown as dd-Mmm-yyyy." />
```

Read-only (still posted) with a custom placeholder:

```blade
<x-dl::field.date name="posted_on" label="Posted on" :value="$doc->posted_on" readonly placeholder="Not posted yet" />
```

## Related

- `x-dl::field.text`: the full reference of the 14 inherited props, shared wrapper and `can`/attribute handling.
- `x-dl::field.date-time`: date + hour/minute in one popover.
- `x-dl::field.time`: hour and minute only.
- `x-dl::field.date-range`: start and end date with an automatic duration line.
- `x-dl::field.date-slider`: pick a date or a period by sliding.
- `x-dl::form`: `required` validation, dirty guard, Undo, locked/read-only display.
- `Devlib\DateValue` (PHP) and `Devlib.h.DlDate` (JS): the date object behind typing, display and ISO storage; `Devlib\Locale` for month names and time zone.
