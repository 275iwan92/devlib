## Overview

`<x-dl::field.time>` is the hour:minute input of DevLib: a 5-character text box (`HH:MM`, 24-hour) with a small popover of two drop-downs. The user normally types digits (`830` becomes `08:30`); the popover is optional. The text box itself is the posted field, so what the user sees (`08:30`) is what the server receives.

The class is `Devlib\Components\Field\Time` (no own props, `control()` = `time`). The control is `resources/views/components/field/time.blade.php`; behavior is the `data-dl-time` branch of the shared `Field` script (`resources/assets/js/components/field.js`) with the time object `DlTime` in `core.js` (PHP counterpart: `Devlib\TimeValue`).

### What it is not

- Not a date-time (`x-dl::field.date-time`) and not a duration or a stopwatch: only a time of day, `00:00` to `23:59`. No seconds.
- Not zone-aware: no time-zone label, no conversion.
- Not a validator: no min/max, no step option (the popover minute step is fixed at 5, typing accepts any minute).

## Props

`Time` has no props of its own. All 14 constructor props come from the shared `Field` base. The full reference of the inherited props is in `x-dl::field.text`.

### Inherited props

| Prop | Type, default | On this control |
|---|---|---|
| `name` | `string`, `''` | Name of the **text box itself** (there is no hidden input), so the posted value is the visible text. Also `data-dl-field` on the root. |
| `label` | `string`, `''` | Label text; `for` points at the text box. Empty = no label row. |
| `tooltip` | `?string`, `null` | (i) bubble next to the label (needs a label). |
| `hint` | `?string`, `null` | Help line under the control; rendered whenever not null. |
| `error` | `?string`, `null` | Server error text; non-empty marks the field `is-invalid`. Not translated. |
| `value` | `mixed`, `null` | Initial text, written to the box **as given** (not normalized on load). Pass `HH:MM`. |
| `placeholder` | `?string`, `null` | Placeholder of the box; default `14:30`. |
| `required` | `bool`, `false` | Red `*` and the `x-dl::form` Save/Confirm check (empty text = "Required"). No HTML `required`; the server must check again. |
| `disabled` | `bool`, `false` | Disables the text box (not posted by the browser). The ⏰ button is disabled too. |
| `readonly` | `bool`, `false` | Text box read-only, ⏰ button not rendered, root gets `is-readonly`. The value is still posted. Forced on while `Field::$readonlyAll` is true. |
| `icon` | `?string`, `null` | Not used by this control (the ⏰ button is fixed). |
| `size` | `string`, `'sm'` | CSS size class only: `sm` or `lg`. |
| `i18n` | `?string`, `null` | `data-i18n` on the label for auth screens; normal screens never set it. |
| `source` | `?string`, `null` | Free text for the developer catalog; no visual effect. |

## Behavior

### Markup

Root `.dl-field.dl-field--time`, inside it `.dl-field__box.dl-pick[data-dl-time]` with: the text input `[data-dl-time-text]` (`name`, id = `uid`, `maxlength="5"`), the ⏰ button `[data-dl-dd-toggle]` (title "Pick a time"; not rendered when `readonly`) and the popover `[data-dl-dd-panel]` (header "Pick a time", hour select `00-23`, minute select `00, 05, ... 55`, buttons `Clear` and `Apply`).

### Typing

While typing, every non-digit is dropped, at most four digits are kept and a `:` is inserted after the second digit (`1430` shows `14:30`). The colon cannot be typed. Because the colon is inserted by position, three digits briefly look like `83:0` while typing `830`; this is only the live display.

When the box is left (native `change`) the text is read by `DlTime.of`:

| Typed | Result |
|---|---|
| `8` or `08` | `08:00` |
| `830`, `0830` | `08:30` (`8:30` and `08.30` give the same, because separators are dropped while typing or pasting). |
| `1430` | `14:30` |
| `24`, `2460`, `1275` | Not a time: field gets `is-invalid`, the text stays as typed. |
| empty | Clears the mark; the empty string is posted. |

A valid entry is rewritten as `HH:MM`. A value rendered from the server as `14:30:00` is only tidied when the user edits the box; `DlTime` can read seconds but displays and posts `HH:MM`.

### Popover

Opens with the ⏰ button (fixed position under the box, flips above when there is no room, closes on outside click or when another picker opens; no Escape handler). On open the selects take the hour and minute of the text; the minute is rounded to the nearest 5 (a minute of 58 becomes `00` without carrying to the next hour). `Apply` writes `HH:MM` into the box and closes; `Clear` empties the box and closes. Neither fires an event (see the oddities).

### Language and format

The time is always 24-hour `HH:MM` in every language. Only the popover words (header, hour, minute labels, tooltip) follow the display language; `Clear` and `Apply` are always English.

### Read-only and locked

`readonly`: text box not editable, no ⏰ button, value posted. In a locked `x-dl::form` (disabled fieldset) the box is disabled and not posted, and the form hides the button.

## Data and events

### What the server receives

One field `name` with the text of the box: `HH:MM` when valid, otherwise whatever was left in it (an invalid entry such as `24:60` **is posted as typed**), or an empty string. Validate with `date_format:H:i`. `Devlib\TimeValue::of($v)` reads the same forms on the server (`->display()` gives `HH:MM`, `->iso()` gives `HH:MM:SS`).

### Events

| Event | Target | When |
|---|---|---|
| `input` (bubbles) | the text box | Each keystroke (native). |
| `change` (bubbles) | the text box | Native, when the user leaves the box; the script reformats after it. |

`Apply` and `Clear` set the value and dispatch `input` then `change` on the box, like typing. No `dl:*` event. Because the box has a `name`, typing, `Apply` and `Clear` mark an `x-dl::form` as dirty and clear a "Required" error as soon as the box has a value. `el.dlComponent` on the root `.dl-field` is the shared `Field` instance with no public method for this control; set the value from script with `Devlib.h.setField(form, name, value)`.

### Markup hooks

| Hook | Where |
|---|---|
| `data-dl-time` | the picker box |
| `data-dl-time-text` | the text box (posted) |
| `data-dl-dd-toggle`, `data-dl-dd-panel` | button, popover |
| `data-dl-time-h`, `data-dl-time-m` | hour, minute selects |
| `data-dl-time-apply`, `data-dl-time-clear` | popover buttons |
| classes `is-invalid`, `is-readonly`, `is-open` (panel) | state |

## Rules and pitfalls

- Pass `HH:MM` (`$model->start_time ? substr($model->start_time, 0, 5) : null`, or `Carbon::format('H:i')`). A database `time` value like `14:30:00` is shown and posted as `14:30:00` until the user edits it.
- Always validate on the server (`date_format:H:i`, `nullable` when optional): an unreadable entry is marked red but still posted.
- `required` only checks that the box is not empty.
- Use the popover only as a helper: its minute step is 5; for other minutes the user types.
- A time of day has no date or zone. For a point in time use `x-dl::field.date-time`.
- Accessibility as implemented: the box is a normal labelled input; the ⏰ button, both selects and `Apply`/`Clear` are native controls reachable with Tab. There is no key to open the popover from the box, no Escape, no focus management and no ARIA state on the button.

## Example

```blade
<x-dl::field.time name="jam" label="Birth time" value="14:30" />
```

Required with a server error and old input:

```blade
<x-dl::field.time name="open_at" label="Opens at" :value="old('open_at', $shop->open_at)" :error="$errors->first('open_at')" required placeholder="08:00" />
```

Read-only display (still posted):

```blade
<x-dl::field.time name="closed_at" label="Closed at" value="17:00" readonly />
```

## Related

- `x-dl::field.text`: the full reference of the 14 inherited props and the shared wrapper.
- `x-dl::field.date-time`: date and time in one popover (posted `yyyy-mm-dd HH:mm`).
- `x-dl::field.date`: date only.
- `x-dl::form`: required check, dirty guard, Undo, locked display.
- `Devlib\TimeValue` (PHP) and `Devlib.h.DlTime` (JS): the time object behind typing, display and storage.
