## Overview

`<x-dl::calendar>` is the event calendar of DevLib: a toolbar (Today, previous/next, title, Day / Week / Month switch), a main area that draws the chosen view, and (by default) two side panels: on the left a "Create Event" button, a mini month calendar and an event search box, on the right "Today's events" and the details of the selected day. Events are plain arrays; the whole calendar runs in the browser (`calendar.js`). Users can double-click an empty slot to create an event, double-click an event to edit it, and drag an event to another day or hour.

The component does NOT save anything. Every change is made on the in-page copy of the events and announced with the `dl:calendar` event; the page (or project JS) must listen to it and call the server. A reload shows the events passed in `events` again.

Use it for appointments, meetings, maintenance windows, deliveries: things with a date and usually a time. Pick another object when:

- You need a whole year of holidays or working days at a glance, coloured per day: use `x-dl::year-calendar` (server rendered, no editing).
- The items are production jobs on machines over hours: use `x-dl::gantt` (resource x time board).
- The items move through stages, not through dates: use `x-dl::kanban`.
- You only need to pick one date inside a form: use `x-dl::field.date`.

### Layout and size

Root: `<div class="dl-cal2 [no-side]" data-dl-calendar ...>`. The calendar is a flex column with a fixed height `min(760px, 100vh - 140px)` (minimum 520px) and a CSS container query: when the calendar is narrower than 1150px the right panel is hidden, and below 720px the left panel is hidden too, so the calendar adapts to the width of its container, not of the window.

## Props

Constructor of `Devlib\Components\Calendar` (7 props, none inherited from a field). Blade names are kebab-case; use `:prop="expr"` for arrays, integers and booleans.

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `events` | `array` | `[]` | The events (shape below). JSON-encoded into `data-events` and parsed once when the page loads. |
| `display` | `string` | `'week'` | The view shown first: `day`, `week` or `month`. (The name is `display` because `$view` is reserved by Laravel's Blade Component.) The user can switch afterwards; the choice is not remembered. Any other string is not validated: the main area then draws the day layout while no segment button is marked active. |
| `date` | `?string` | `null` | The date the calendar opens on, `YYYY-MM-DD` (a time part is accepted and ignored for the view). `null` = today in the application time zone (`data-dl-tz` on `<html>`, default `Asia/Jakarta`). The selected day of the side panels starts on the same date. |
| `week-start` | `int` | `0` | First day of the week for the week view, the month grid and the mini calendar: `0` = Sunday, `1` = Monday ... `6` = Saturday. Not validated (use 0 to 6). |
| `scroll-hour` | `int` | `7` | In the Day and Week views the hour grid (24 rows of 44px) is scrolled so that this hour is at the top. Applied every time the time grid is drawn (see pitfalls). Ignored by the Month view. |
| `sidebar` | `bool` | `true` | `false` removes both side panels and adds `no-side` to the root: only the toolbar and the main area remain. Without the sidebar there is no "Create Event" button and no search box; new events can then only be made by double-clicking a slot. |
| `source` | `?string` | `null` | Free text naming where the data comes from, shown by the developer catalog / Manifest; default `acara kalender`. No visual or runtime effect. |

Common attributes: any extra HTML attribute (`id`, `class`, `style`, `data-*`) is merged onto the root `div`; `can="module.action"` hides the whole calendar when the user lacks the ability (inherited from `DevlibComponent`).

### The `events` array

Each event is an associative array:

| Key | Type | Meaning |
|---|---|---|
| `id` | `int\|string` | Identity used by edit, drag and the emitted event. Must be unique in the calendar (compared as strings). |
| `title` | `string` | Text on the chip/block. Required by the editor (an empty title is refused). Escaped; not translated (data). |
| `start` | `string` | `'YYYY-MM-DD HH:MM'` (also accepted with a `T` between date and time) or `'YYYY-MM-DD'` for a day without time. Wall-clock time in the application time zone, no offset. |
| `end` | `?string` | Same formats. Optional. Default: all-day event = same day as `start`; timed event = `start` + 1 hour. |
| `allDay` | `bool` | `true` = shown in the all-day strip (week/day) or as a chip without time (month). For all-day events the end DATE is inclusive: `start 10-23`, `end 10-24` covers two days. |
| `tone` | `string` | Colour: `accent` (default), `success`, `warn`, `danger`, `violet`, `neutral`. Written into the class `dl-cal2--<tone>`; an unknown name has no colour variable. |
| `desc` | `?string` | Description, shown in the side panels and searched by the search box. |

Extra keys are ignored by the editor and are NOT included in `dl:calendar` (only the seven keys above are emitted).

## Behavior

### Toolbar and views

- **Today** jumps to the current date; **previous / next** move by one day, one week or one month depending on the view; the title shows the range (`Wednesday, 21 October 2026`, `18 – 24 October 2026`, `October 2026`). Month and weekday names come from `Intl` in the display language (`window.DL_NUM`), so they follow the language switch.
- **Day / Week / Month** segment: the active one gets `is-active`.
- **Week and Day**: a header with the date buttons (click = open that day in Day view), an all-day strip, and the scrollable hour grid. A red line marks the current time (updated every minute) on today's column. Overlapping timed events are placed side by side inside their overlap group.
- **Month**: a 6-week grid; each cell lists up to 3 chips and a "+n more" button that opens that day in Day view. Days outside the month are dimmed.
- Timed events that span several days appear as a chip in the all-day strip of every day they touch; in the hour grid a block is drawn only on the start day.

### Side panels (when `sidebar` is true)

- **Create Event** opens the editor for a new event on the selected day (next full hour).
- **Mini calendar**: its own month navigation (does not move the main view); clicking a day jumps the main view to it; days with events get a dot.
- **Search**: filters by title and description (case-insensitive). Non-matching events are dimmed (`is-dim`) in all views, and up to 8 hits are listed below the box with "n found"; clicking a hit opens its day.
- **Right panel**: today's events, and the selected day with its event count; the empty state tells the user to double-click an empty slot.

### Creating, editing, moving

- Double-click an empty hour slot (Day/Week): new event at that hour. Double-click a Month cell: new event at 09:00. Double-click the all-day strip: new all-day event. Nothing exists until the user presses Save in the editor.
- Double-click an event: edit. The editor (class `dl-mov` overlay + `dl-modal--sm`) has Title (required), All day, Start, End, a colour picker (the six tones) and Description. Save is refused with a message when the title is empty or the end is before the start. Delete (existing events only) asks for confirmation.
- Drag an event (HTML5 drag and drop): dropping on a Month cell or on an all-day strip moves it to that date and keeps its time (dropping on the strip turns it into an all-day event); dropping on an hour column moves it to that date and time, snapped to 30 minutes, and keeps the duration. A toast confirms the move.
- Clicking a chip/block only selects its day (updates the mini calendar and the right panel).

### Keyboard and accessibility

There is no keyboard support for the calendar surface: chips and blocks are `div` elements with `draggable="true"` and no `tabindex`, so they cannot be focused, opened or moved with the keyboard; the Escape key does not close the editor (click outside the dialog or the X/Cancel). Drag and drop does not work with touch input. The toolbar and mini-calendar controls are real buttons; the previous/next month buttons and the search box carry `aria-label`; the editor has `role="dialog" aria-modal="true"` and focuses the title field. Treat the calendar as a mouse tool and provide another way (a grid, a form) when keyboard access is required.

### Language and time zone

- Text written by the component (Day / Week / Month, Today, search placeholder, "+n more", "n events", editor labels and messages) follows the display language through `Devlib\Lang` (`c_*` keys). Event titles and descriptions are data and are not translated.
- Some words are fixed English: the "Create Event" button label, and the editor buttons Delete / Cancel / Save.
- The clock (today, now line, default date) uses `data-dl-tz` of `<html>`, but the time zone label in the grid header and in each event's time text is the literal `WIB`, whatever the configured zone.

## Data and events

### Root and hook attributes

| Attribute / class | Meaning |
|---|---|
| `data-dl="x-dl::calendar"`, `data-dl-class`, `data-dl-src` | Component identity (catalog, Technical Guide link). |
| `data-dl-calendar` | Mount selector of `calendar.js`. |
| `data-events` | JSON of `events`. |
| `data-view` | `display`. |
| `data-date` | `date` (empty when null). |
| `data-week-start` | `week-start`. |
| `data-scroll` | `scroll-hour`. |
| `dl-cal2`, `no-side` | Root class; `no-side` when `sidebar` is false. |
| `dl-cal2__top`, `__body`, `__left`, `__main`, `__right` | Toolbar, grid body, left panel, main area, right panel. |
| `dl-cal2--accent/success/warn/danger/violet/neutral` | Tone classes on chips, blocks and side-panel items (set `--dl-ce`). |
| `data-c-today`, `data-c-prev`, `data-c-next`, `data-c-views`, `data-c-title`, `data-c-create`, `data-c-mini`, `data-c-search`, `data-c-found`, `data-c-main`, `data-c-today-list`, `data-c-day-list` | Inner hooks used by the script. |
| `data-c-ev="<id>"`, `data-c-day`, `data-c-col`, `data-c-drop`, `data-c-allday`, `data-c-go` | Inside the generated views: event, day cell, hour column, drop target, all-day strip, "go to date" button. |

### Event `dl:calendar` (bubbles, not cancelable)

Dispatched on the root after each change, after the on-screen state is already updated:

```js
el.addEventListener('dl:calendar', e => {
  const {type, event} = e.detail;   // type: 'create' | 'update' | 'delete' | 'move'
  // event = {id, title, start, end, allDay, tone, desc}
});
```

`start` / `end` are `'YYYY-MM-DD HH:MM'`, or `'YYYY-MM-DD'` for all-day events (the end date is then always present: equal to the start date when the event covers one day). `desc` is `''` when empty. A new event has a temporary `id` such as `n1788000000001` (string, `n` plus a counter): the server must give the real id and the page should keep a map if later edits need it. `move` is emitted by drag and drop only; the editor emits `create`, `update` or `delete`.

### JS access

- `el.dlComponent` is the `Calendar` instance (every DevLib component has it).
- `el.dlCalendar` = `{events, render(), go(date, view?)}`. `go('2026-11-02', 'month')` moves the main view to that date (and selects it), optionally switching the view; `render()` redraws. `events` is the internal list (each item also carries parsed `Date` objects `s` and `e`); push/remove items and call `render()` to refresh, keeping `start`, `end`, `title`, `id` consistent.

There is no URL contract: the component never calls the server.

## Rules and pitfalls

### Rules

- Declare events as data in the Blade view; do not build calendars from raw HTML. Behaviour that reacts to `dl:calendar` belongs in project JS registered with DevLib, not in an inline `<script>` in the screen (lint rule L002; L001 for raw `button`/`input`/`select`/`table`/`form` tags, L003 for hex colours). There is no calendar-specific lint rule.
- Persist every change yourself: create/update/delete/move are only in the browser until your listener posts them.
- Use ids that are stable and unique; the emitted temporary id of a new event is replaced by the server's id on your side.
- Keep the times in one zone (the application zone) as text; the component does no offset conversion.

### Common mistakes

- Expecting the calendar to save: reloading brings back the `events` array you rendered.
- Passing `end` for an all-day event as the next day to mean "one day": the end date is inclusive here, so this shows two days.
- Passing `week-start` as a weekday name: it is an integer property (`:week-start="1"`), so `week-start="Monday"` fails with a type error; numbers outside 0 to 6 are not checked and give a shifted week.
- Setting `scroll-hour` and expecting the grid to keep the scroll the user chose: every redraw (navigation, drag, save, search typing) scrolls back to that hour.
- Very long timed events or events past midnight: the block is drawn from the start time with a height of its duration, so an event that ends after 24:00 is cut by the bottom of the grid on its start day and is not drawn on the next day's hour grid (it does show in the all-day strip).
- Using the calendar where keyboard-only operation is required (see Keyboard and accessibility).

### Performance

Every redraw rebuilds the whole main area as an HTML string, and each day cell filters the full event list. Hundreds of events are fine; thousands in the Month view will be slow. Pass only the events of the visible period if the data set is large (and reload on navigation yourself, since the component has no "range changed" event).

### Notes on inconsistencies found while writing this entry (not fixed)

- The header comment says the time is WIB through `Devlib\Locale`; the script reads the configured zone from `data-dl-tz`, but the visible labels (`WIB` in the grid header and in every event's time text) are hard-coded.
- `scroll-hour` is applied on every `timeView()` draw, not only the first one.
- An invalid `display` value is accepted silently (day layout, no active segment button).
- Editor buttons (`Delete`, `Cancel`, `Save`) and the `Create Event` button are literal English strings (the `Button` object does not translate its label); the other texts use the `c_*` dictionary keys.
- The time grid draws a timed event only on its start day, but the all-day strip lists it for every day it spans.
- The `desc` and other custom keys of an event are kept in memory but any key other than the seven documented ones is dropped from `dl:calendar`.
- The `setInterval` that moves the "now" line is never cleared (no teardown for the component).
- Chips and blocks are not keyboard accessible and Escape does not close the editor.

## Example

From the catalog demo `calendar-events` (data made fictional), week view, Monday first:

```blade
@php
    $events = [
        ['id' => 1, 'title' => 'Weekly production meeting', 'start' => '2026-10-21 08:00', 'end' => '2026-10-21 09:00', 'tone' => 'accent', 'desc' => 'Review the print schedule and machine issues.'],
        ['id' => 2, 'title' => 'Customer visit', 'start' => '2026-10-21 10:00', 'end' => '2026-10-21 11:30', 'tone' => 'success'],
        ['id' => 3, 'title' => 'Colour proof approval', 'start' => '2026-10-21 10:30', 'end' => '2026-10-21 12:00', 'tone' => 'warn', 'desc' => 'Physical proof, approval by the customer.'],
        ['id' => 4, 'title' => 'Press maintenance', 'start' => '2026-10-22 13:00', 'end' => '2026-10-22 17:00', 'tone' => 'danger'],
        ['id' => 5, 'title' => 'Warehouse stock count', 'start' => '2026-10-23', 'end' => '2026-10-24', 'allDay' => true, 'tone' => 'violet'],
        ['id' => 6, 'title' => 'Public holiday (sample)', 'start' => '2026-10-26', 'allDay' => true, 'tone' => 'danger'],
    ];
@endphp

<x-dl::calendar :events="$events" display="week" date="2026-10-21" :week-start="1" :scroll-hour="7" />
```

Listening to the changes (project JS, not an inline script in the view):

```js
document.addEventListener('dl:calendar', e => {
  const {type, event} = e.detail;     // create | update | delete | move
  fetch('/schedule/events/' + type, {
    method: 'POST', credentials: 'same-origin',
    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.DL_CSRF},
    body: JSON.stringify(event),
  });
});
```

(The first block is adapted from the real demo; the listener is a sketch of the project side, `/schedule/events/*` is an assumed route.) Verified by reading `Calendar.php`, `calendar.js` and `devlib.css`; the calendar was not exercised in a browser while writing this entry.

## Related

- `x-dl::year-calendar`: twelve months on one page for holidays and working days, server-rendered, links instead of editing.
- `x-dl::gantt`: resource x hours scheduling board with drag, resize, impact check and Draft/Submit flow.
- `x-dl::kanban`: cards across stages when the flow matters more than the date.
- `x-dl::field.date`, `x-dl::field.date-range`, `x-dl::field.date-time`: picking a single date or range inside a form.
- `x-dl::button`, `x-dl::icon-button`: used for the toolbar and the Create Event button.
- `skills/devlib/components.md`: generated prop list.
