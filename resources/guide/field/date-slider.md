## Overview

`<x-dl::field.date-slider>` picks a date, or a period, by sliding a handle along a calendar scale instead of typing or opening a calendar. Use it when the choice is "roughly when, within a known window" (report period, delivery date within the quarter, leave days). The visible result is always `dd-Mmm-yyyy`; the posted value is ISO `yyyy-mm-dd`.

It is a `Devlib\Components\Field\Slider` (the numeric slider) specialised for dates: the class `Devlib\Components\Field\DateSlider` converts `from`, `until`, `value` and `value-to` into day offsets and lets the shared slider view and script (`resources/views/components/field/slider.blade.php`, `resources/assets/js/components/slider.js`) do the rest. Position `0` is `from`, the last position is `until`, one step is one day. Without `range` there is one handle (one date); with `range` there are two handles (start and end) and the field shows the number of days in between.

### What it is not

- Not a typed or free date: the user can only choose a day between `from` and `until`. For any date use `x-dl::field.date`.
- Not an optional value: the slider always has a position, so it always posts a date (see Rules and pitfalls).
- Not the numeric slider: `min`, `max`, `step`, `format` and `ticks` of `x-dl::field.slider` are not props here (min is 0, max is the number of days, step is 1 day, tick labels are the months).

## Props

### Own props

| Prop | Type, default | Meaning |
|---|---|---|
| `from` | `string`, `''` | First selectable date, `yyyy-mm-dd`. Empty = the first day of the current month in the application time zone (`Devlib\Locale::timezone()`). |
| `until` | `string`, `''` | Last selectable date, `yyyy-mm-dd`, inclusive. Empty = the last day of the month two months from now, in the application time zone and without day overflow (from 31 December: 28 February). Must not be before `from`. The scale has `days between + 1` positions. |
| `range` | `bool`, `false` | `true` = two handles (period). Adds class `is-range`, a second hidden input and the day count. Written as the bare attribute `range` or `:range="true"`. |
| `value-to` | `mixed`, `null` | End date of the period (`range` only), `yyyy-mm-dd`. Empty = `until`. Ignored without `range`. |
| `name-to` | `?string`, `null` | `name` of the end hidden input (`range` only). Empty = `<name>_to`. |
| `orientation` | `string`, `'horizontal'` | `horizontal` or `vertical`; becomes the class `dl-slider--<orientation>`. Only these two have styles. |
| `height` | `string`, `'220px'` | Length of a **vertical** slider (any CSS length), set as `--dl-slider-h`. No visual effect when horizontal. |

### Inherited props

All 14 come from the shared `Field` base. The full reference is in `x-dl::field.text`.

| Prop | Type, default | On this control |
|---|---|---|
| `name` | `string`, `''` | Name of the (start) hidden input `[data-dl-slider-a]` that is posted. The handles themselves have no name. Also `data-dl-field` on the root. |
| `label` | `string`, `''` | Label text; `for` points at the first handle (its id is `uid`). The same text is the `aria-label` of the handle(s): `<label>` for one handle, `<label> (from)` and `<label> (to)` for a range. Empty = no label row and an empty `aria-label`. |
| `tooltip` | `?string`, `null` | (i) bubble next to the label (needs a label). |
| `hint` | `?string`, `null` | Help line under the control; rendered whenever not null. |
| `error` | `?string`, `null` | Server error text; non-empty marks the field `is-invalid`. Not translated. |
| `value` | `mixed`, `null` | Start date, `yyyy-mm-dd`, converted to a day offset from `from`. Empty = `from`. A date outside `from..until` is clamped by the browser to the nearest end. |
| `placeholder` | `?string`, `null` | Not used by this control. |
| `required` | `bool`, `false` | Red `*` only. The `x-dl::form` check can never fail because the hidden input always has a date. |
| `disabled` | `bool`, `false` | Disables the handle(s) (greyed, not draggable). The hidden inputs are not disabled and are still posted, see the oddity. |
| `readonly` | `bool`, `false` | Same as `disabled` for the handle(s) (they get `disabled`), plus the root class `is-readonly`. Forced on while `Field::$readonlyAll` is true. The value is still posted. |
| `icon` | `?string`, `null` | Not used by this control. |
| `size` | `string`, `'sm'` | CSS size class only: `sm` or `lg`. |
| `i18n` | `?string`, `null` | `data-i18n` on the label for auth screens; normal screens never set it. |
| `source` | `?string`, `null` | Free text for the developer catalog; no visual effect. |

## Behavior

### Markup

Root `.dl-field.dl-field--slider` (the control name is `slider`), inside it `.dl-slider.dl-slider--<orientation>[.is-range][data-dl-slider][data-kind="date"][data-start="<from>"][data-format="{v}"]`: hidden `[data-dl-slider-a]` (and `[data-dl-slider-b]` with `range`), a track with the rail, the filled part and one or two `input[type=range].dl-slider__input` (`min=0`, `max=<days>`, `step=1`), the scale `.dl-slider__ticks`, and the text `.dl-slider__value`.

### The scale

Tick labels are placed at the first day of each month inside the window. The first label (at position 0) is the month of `from` with its year; later labels are the month name, with the year added for January. Month names come from the display language at render time (`Devlib\Locale::spec()['months']`, short names). When there would be more than 14 labels only every third one is kept.

### Moving the handles

Every `input` event of a handle updates the hidden input(s) with `iso(offset)` (offsets are added to `from` in UTC, so daylight saving cannot shift a date), the fill between the handles, and the text:

- one handle: `10-Oct-2026`
- range: `01-Mar-2026 – 30-Jun-2026 · 122 days` (the count includes both end days; the word follows the display language).

In a range the handles cannot cross: the handle being moved stops at the other one. The script also runs once on load, so the hidden inputs and the text are filled from the initial positions straight away.

### Language and format

The date text uses the same short format as the rest of DevLib (`dd-Mmm-yyyy`, month in the display language through `window.DL_NUM`, frozen at page load). It is calculated for the application time zone (`data-dl-tz` on `<html>`).

### Keyboard and accessibility as implemented

The handles are native range inputs: Tab focuses a handle; arrow keys move it by one day, Home/End jump to the ends (the browser decides the exact keys, also for vertical). There is no `aria-valuetext`, so a screen reader announces the numeric offset (0...N), not the date, and the visible date text is not an `aria-live` region.

### Read-only and locked

`disabled` / `readonly` disable the handles. In a locked `x-dl::form` (disabled fieldset) the handles and the hidden inputs are disabled by the browser; the hidden inputs are then not posted.

## Data and events

### What the server receives

`name` with `yyyy-mm-dd`, and with `range` also `name-to` (default `<name>_to`) with `yyyy-mm-dd`. Both always carry a date between `from` and `until`; there is no "empty". The server must still validate (`date_format:Y-m-d`, and for a period `after_or_equal:<name>`, plus the window itself) because the form can be posted by hand.

### Events

| Event | Target | When |
|---|---|---|
| `input` (bubbles) | each `.dl-slider__input` handle | Native, while dragging or using the keys. |
| `change` (bubbles) | each handle | Native, on release. |

The hidden inputs get **no** event when they are updated and the handles have no `name`, so an `x-dl::form` is not marked dirty by moving the slider. No `dl:*` event. To read the value from script, read the hidden inputs (`[data-dl-slider-a]`, `[data-dl-slider-b]`) or listen to `input` on the field root. `el.dlComponent` on the `.dl-slider` element (`[data-dl-slider]`) is the `Slider` instance (internal fields `ia`, `ib`, `ha`, `hb`, `min`, `max`; method `iso(n)` gives the date of an offset); it is not a public API.

### Markup hooks

| Hook | Where |
|---|---|
| `data-dl-slider` | the slider element (JS class `Slider`) |
| `data-kind="date"`, `data-start`, `data-format` | date mode, offset 0 date, number format (unused for dates) |
| `data-dl-slider-a`, `data-dl-slider-b` | hidden inputs (start, end) |
| `.dl-slider__input` | the handle(s) |
| `data-dl-slider-value` | the text with the chosen date(s) |
| classes `is-range`, `dl-slider--horizontal`, `dl-slider--vertical` | state, orientation |

## Rules and pitfalls

- Always set `from` and `until` explicitly. The defaults depend on today's date (and they differ between runs), so a form re-rendered later may not contain the saved date.
- A saved date outside the window is silently moved to the nearest end by the browser, and the next Save posts the moved date. Keep `from <= value <= until` (for `range` also `value-to`).
- `from`, `until`, `value` and `value-to` must be readable by PHP `DateTimeImmutable`; use `yyyy-mm-dd`. An unreadable string throws while the page is rendered. Format model dates with `->format('Y-m-d')`.
- Do not use it for an optional date: it always posts a position (the start of the window when no `value`, `until` for `value-to`). Use `x-dl::field.date` and let the user leave it empty.
- The scale has one position per day, so a very long window makes each position very narrow to drag; the arrow keys always move one day.
- Re-fill with `old('x', $model->x)` after a failed validation so the handle returns to the posted date.
- `Devlib.h.fillForm` / form Undo write the hidden input only; per `setField` in `core.js` the handles and the text are not moved, so after Undo they can disagree with the posted value until the user touches the slider again.
- Code oddity (not fixed): `slider.js` shows the date with `fmt.date(iso + 'T12:00:00+07:00')`, a fixed `+07:00` offset. For an application time zone at UTC-6 or further west the shown date can be one day earlier than the posted ISO value (the hidden value stays correct).
- Code oddity (not fixed): `disabled`/`readonly` only disable the range inputs (`slider.blade.php`); the hidden inputs are still posted. Moving the slider also fires no event on them and does not mark the form dirty, because the handles have no `name`.

## Example

Period (two handles) and a single date, copied in shape from the demo:

```blade
<x-dl::field.date-slider name="periode" label="Report period" from="2026-01-01" until="2026-12-31" value="2026-03-01" value-to="2026-06-30" range />
<x-dl::field.date-slider name="kirim" label="Send date" from="2026-09-23" until="2026-12-31" value="2026-10-05" />
```

Vertical, with a chosen end field name and a server error:

```blade
<x-dl::field.date-slider name="leave_from" name-to="leave_to" label="Leave" from="2026-10-01" until="2026-12-31"
    :value="old('leave_from', '2026-10-20')" :value-to="old('leave_to', '2026-10-24')" range orientation="vertical" height="260px"
    :error="$errors->first('leave_from')" />
```

## Related

- `x-dl::field.text`: the full reference of the 14 inherited props and the shared wrapper.
- `x-dl::field.slider`: the numeric slider this field is built on (`min`, `max`, `step`, `format`, `ticks`).
- `x-dl::field.date`: one typed or picked date, can be empty.
- `x-dl::field.date-range`: start and end date with two native date boxes and a duration line.
- `x-dl::form`: required check, dirty guard, Undo, locked display.
- `Devlib\Locale` (month names, time zone) and `Devlib.h.fmt.date` (the display format).
