## Overview

`<x-dl::field.date-time>` picks a date **and** an hour:minute in one popover (calendar on top, hour and minute drop-downs below, an `Apply` button). The box shows `dd-Mmm-yyyy HH:mm` followed by the time-zone label (`10-Oct-2026 14:30 WIB`); what is posted is `yyyy-mm-dd HH:mm` (24-hour, no seconds, no zone). Use it for an appointment, a schedule or a deadline with a time. For a date alone use `x-dl::field.date`; for a time alone use `x-dl::field.time`.

The class is `Devlib\Components\Field\DateTime` (`control()` = `date-time`, one own prop `minute-step`). The control is `resources/views/components/field/date-time.blade.php`; behavior is the `data-dl-datetime` branch of the shared `Field` script (`resources/assets/js/components/field.js`), using `DlDateTime` from `core.js` (PHP counterpart: `Devlib\DateTimeValue`).

### What it is not

- Not typed: the text box is always `readonly`; the user can only click it (it opens the popover) or use the popover. For a date the user types, use `x-dl::field.date`.
- Not a time-zone converter: the label (`WIB`) is only text. The stored value is a wall-clock time of the application time zone.
- Not a validator: no min/max, no "no past dates", no business-hours limit.

## Props

### Own props

| Prop | Type, default | Meaning |
|---|---|---|
| `minute-step` | `int`, `5` | Step of the minute drop-down: options `00, step, 2*step, ...` below 60 (`5` = 00, 05, ... 55; `15` = 00, 15, 30, 45). Rendered by a PHP loop in the view; the constructor clamps the value to `1..30`, so `0`, a negative number or `60` or more cannot hang the page or break the script. It also controls how a loaded minute is rounded (see Behavior). Written as `:minute-step="15"` in the demo. |

### Inherited props

All 14 come from the shared `Field` base. The full reference is in `x-dl::field.text`.

| Prop | Type, default | On this control |
|---|---|---|
| `name` | `string`, `''` | Name of the **hidden** input (`data-dl-value`) that carries the posted value; the visible box has no `name`. Also `data-dl-field` on the root. |
| `label` | `string`, `''` | Label text; `for` points at the visible (read-only) box. Empty = no label row. |
| `tooltip` | `?string`, `null` | (i) bubble next to the label (needs a label). |
| `hint` | `?string`, `null` | Help line under the control; rendered whenever not null. |
| `error` | `?string`, `null` | Server error text; non-empty marks the field `is-invalid`. Not translated. |
| `value` | `mixed`, `null` | Initial value, written to the hidden input as given. Use `yyyy-mm-dd HH:mm` (zero-padded; `T` instead of the space also works). Also see the pitfalls about minutes, seconds and time zones. |
| `placeholder` | `?string`, `null` | Placeholder of the box; default `—`. |
| `required` | `bool`, `false` | Red `*` and the `x-dl::form` Save/Confirm check (hidden value empty = "Required"). No HTML `required`; the server must check again. |
| `disabled` | `bool`, `false` | Disables the box and the calendar button. See the oddity: the hidden input is still posted. |
| `readonly` | `bool`, `false` | Disables the calendar button (the box is read-only anyway) and gives the root `is-readonly`. The value is still posted. Forced on while `Field::$readonlyAll` is true. |
| `icon` | `?string`, `null` | Not used by this control (the 📅 button is fixed). |
| `size` | `string`, `'sm'` | CSS size class only: `sm` or `lg`. |
| `i18n` | `?string`, `null` | `data-i18n` on the label for auth screens; normal screens never set it. |
| `source` | `?string`, `null` | Free text for the developer catalog; no visual effect. |

## Behavior

### Markup

Root `.dl-field.dl-field--date-time`, inside it `.dl-field__box.dl-pick[data-dl-datetime][data-tz-label]` with: the hidden `input[name][data-dl-value]`, the read-only text box `[data-dl-date-text]` (id = `uid`), the 📅 button `[data-dl-dd-toggle]`, and the popover `[data-dl-dd-panel]`: header "Pick a date & time", month navigation, weekday names, day grid, hour select `[data-dl-time-h]` (00-23), minute select `[data-dl-time-m]`, a preview line `[data-dl-dt-preview]` ("Selected: 10-Oct-2026 14:30 WIB") and the buttons `Clear` and `Apply`.

### Choosing a value

1. Click the box or the 📅 button: the popover opens (the box click simply clicks the button, so it does nothing when the button is disabled).
2. Click a day: it is only *selected* (the popover stays open and the preview updates). Change hour/minute: the preview updates.
3. `Apply` writes the hidden input as `yyyy-mm-dd HH:mm`, shows `dd-Mmm-yyyy HH:mm <label>` in the box, closes the popover and fires `change`. If no day is selected, `Apply` uses **today** (the browser's local date).
4. `Clear` empties the hidden input and the box and closes the popover (no event, see the oddity).

Closing the popover by clicking outside throws the pending choice away *from the hidden input*, but the selected day stays highlighted and in the preview until the page is reloaded, so the popover can show a selection that was never applied.

### Loading an existing value

The hidden input keeps the value exactly as rendered. The script splits it at the first space or `T`; the date becomes the selected day and the calendar month, the hour select takes the first two characters of the time, and the minute is **rounded to the nearest option** of the minute select (`14:32` with the 5-minute step shows `14:30`). The box shows the rounded time. The hidden input is not touched until the user applies, so `14:32:45` stays `14:32:45` on the next Save even though `14:30` is shown. Time-zone offsets in the value (`+07:00`, `Z`) are ignored, not converted.

### Time zone label

`data-tz-label` comes from `config('devlib.timezone_label')` (default `WIB`), appended to the box text and the preview. The label does not change the posted value. The PHP class comment (`1 Okt 1969, 14:30 WIB`) is outdated; the real text is `01-Okt-1969 14:30 WIB`.

### Language and format

Month names in the title and the box follow the display language (`window.DL_NUM`, frozen at page load; see `x-dl::field.date`). The week starts on Sunday; the weekday header is the one rendered by the server (Carbon `isoFormat('dd')`). The header, hour/minute labels and the preview word follow the display language; `Clear` and `Apply` are always English.

### Read-only and locked

`readonly`: calendar button disabled, box not clickable. In a locked `x-dl::form` (disabled fieldset) the hidden input is disabled by the browser and not posted.

## Data and events

### What the server receives

One field `name` with `yyyy-mm-dd HH:mm` after the user applied, or the original value unchanged, or an empty string. Never seconds, never a zone. Validate with `date_format:Y-m-d H:i` (the original value is posted as rendered, so also render it in this form). `Devlib\DateTimeValue::of($v)` reads the same forms on the server (`->iso()` gives `Y-m-d H:i:s`, `->display()` gives `dd-Mmm-yyyy HH:mm`).

### Events

| Event | Target | When |
|---|---|---|
| `change` (bubbles) | the hidden `[data-dl-value]` input | `Apply` only. Not fired by `Clear`, nor on load. |

No `dl:*` event. Because the hidden input is named, `Apply` marks an `x-dl::form` as dirty. `el.dlComponent` on the root `.dl-field` is the shared `Field` instance with no public method for this control.

### Markup hooks

| Hook | Where |
|---|---|
| `data-dl-datetime`, `data-tz-label` | the picker box |
| `data-dl-value`, `data-dl-date-text` | hidden input, visible box |
| `data-dl-dd-toggle`, `data-dl-dd-panel` | button, popover |
| `data-dl-cal-prev`, `-next`, `-days`, `-title`, `-clear` | calendar parts |
| `data-dl-time-h`, `data-dl-time-m` | hour, minute selects |
| `data-dl-dt-preview`, `data-dl-dt-apply` | preview line, Apply button |
| `data-d="yyyy-mm-dd"` | each day button |

## Rules and pitfalls

- Render stored values as `$model->at?->format('Y-m-d H:i')`; do not pass seconds, `T...Z` or offsets. Convert to the application time zone on the server first: the field does not.
- Choose a `minute-step` that matches the data. Existing minutes that are not on the step are only *displayed* rounded and stay unchanged in the hidden input until the user applies.
- The user must press `Apply`; picking a day alone changes nothing. Tell users in the hint when that matters.
- `Clear` fires no `change`, so a clear alone does not mark the form dirty and any listener on the hidden input misses it.
- `required` only checks that the hidden value is not empty; whether the time is sensible is a server rule.
- Accessibility as implemented: the box is a read-only input with a `<label for>`; the 📅 button, day buttons, selects and `Apply`/`Clear` are native controls reachable with Tab; clicking the box opens the popover but there is no key to open it from the box itself, no arrow-key day navigation, no Escape, no focus management and no ARIA state on the button. The hour and minute selects are labelled.
- Form Undo / `Devlib.h.fillForm` restores the hidden input but has no branch for this control, so the visible text can stay at the old value after Undo (per `setField` in `core.js`). Re-render or reload when you set values from script.
- Code oddity (not fixed): `disabled` disables the box and the button, but the hidden input is not disabled (`date-time.blade.php`), so a disabled date-time is still posted.
- Code oddity (not fixed): the same month navigation overflow as in `x-dl::field.date` (`setMonth(± 1)` from the 29th-31st), and `Apply` without a selected day takes the browser's local date instead of the application time zone used by `Today` in `x-dl::field.date`.
- Code oddity (not fixed): the PHP comment in `DateTime.php` says the display is `1 Okt 1969, 14:30 WIB`; the code shows `01-Okt-1969 14:30 WIB`.

## Example

```blade
<x-dl::field.date-time name="rapat" label="Meeting" tooltip="Date and time in one popup, WIB" />
```

With a value and a 15-minute step:

```blade
<x-dl::field.date-time name="shift" label="Shift start (per 15 minutes)" :minute-step="15" value="2026-09-24 07:00" />
```

Required, old input and server error:

```blade
<x-dl::field.date-time name="send_at" label="Send at" :value="old('send_at', $mail->send_at?->format('Y-m-d H:i'))"
    :error="$errors->first('send_at')" required hint="Pick a day, then press Apply." />
```

## Related

- `x-dl::field.text`: the full reference of the 14 inherited props and the shared wrapper.
- `x-dl::field.date`: date only, typed or picked.
- `x-dl::field.time`: hour and minute only, typed or picked.
- `x-dl::field.date-range`: start and end date with duration.
- `x-dl::form`: required check, dirty guard, Undo, locked display.
- `Devlib\DateTimeValue` (PHP) and `Devlib.h.DlDateTime` (JS): the date-time object; `Devlib\Locale::tzLabel()` gives the zone label.
