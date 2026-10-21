## Overview

`<x-dl::clock>` shows a running time (`hh:mm:ss`, updated every second) with a short zone label (`WIB`) and the long date ("Rabu, 21 Oktober 2026"), or the date alone (`mode="date"`). The time is the visitor's device clock converted to a configured IANA time zone, formatted in the active page language. The server prints only placeholders (`--:--:--`, `—`).

Use it as a header or hero widget (home screen, login frame) so that everybody sees the same business time regardless of the device zone.

Pick another object when you need:

- A stopwatch that counts from a known number of seconds: `x-dl::timer`.
- A time that must be right for the business (attendance, cut-off, audit stamps): decide on the server; the clock is the device clock and can be wrong. For GPS clock in / out use `x-dl::geo-clock`.
- A salutation: `x-dl::greeting`.
- A date picker or a calendar: `x-dl::field.date`, `x-dl::calendar`.

### Generated markup

```html
<div class="dl-clock dl-clock--clock" data-dl="x-dl::clock mode=clock" data-dl-class="Clock ← DevlibComponent"
     data-dl-src="jam sistem, zona Asia/Jakarta (WIB)" data-dl-clock data-tz="Asia/Jakarta">
  <div class="t"><span data-dl-clock-time>--:--:--</span> <small class="dl-clock__tz">WIB</small></div>   <!-- mode="clock" only -->
  <div class="d" data-dl-clock-date>—</div>
</div>
```

With `mode="date"` the class is `dl-clock--date`, the `.t` block does not exist and only the larger date line is shown. Extra HTML attributes (`id`, `class`, `style`, `data-*`) go on the root `div`; `can="module.action"` also works (without the ability nothing is rendered).

## Props

Constructor of `Devlib\Components\Clock` (3 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `mode` | `string`, `'clock'` | `clock` = time + zone label + date; anything else (the documented value is `date`) = date only. The value also becomes the CSS class `dl-clock--<mode>` and is not validated, so a typo silently gives the date-only layout. |
| `timezone` | `?string`, `null` | IANA time zone used to format time and date (`Asia/Jakarta`, `Asia/Makassar`, `Asia/Jayapura`, `UTC`). `null` = `config('devlib.timezone')` (default `Asia/Jakarta`). Written to `data-tz`. `''` is kept and makes the browser use the visitor's own zone. An invalid name is NOT checked by PHP and throws in the browser (first code oddity below). Abbreviations such as `WIB` are not valid IANA names. |
| `tz-label` | `?string`, `null` | Short text printed next to the time (`WIB`). `null` = `config('devlib.timezone_label')` (default `WIB`). Pure text: it is not derived from `timezone`, so when you change one, change the other. Shown only in `clock` mode. Not translated. |

## Behavior

### What is displayed

- Time: `toLocaleTimeString(locale, {hour12: false, timeZone})`, 24-hour, seconds included.
- Date: `toLocaleDateString(locale, {weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone})`; the CSS capitalises the first letter of each word (`text-transform: capitalize`).
- Locale: `Devlib.lang === 'id'` gives `id-ID`, anything else gives `en-US`. In `id-ID` the browser writes the time separators as dots (`14.05.30`), in `en-US` as colons (`14:05:30`); this depends on the browser's ICU data.
- Placeholders: `--:--:--` and `—` stay until the first tick, which runs inside `init()`, so they are visible only if the script fails.

### Timezone and the clock source

- The instant is the visitor's device clock (`new Date()`); the zone only changes how it is written. A device with a wrong clock shows a wrong time, even though the data source text says `jam sistem, zona ...` ("system" means the application's configured zone, not the server's clock).
- The label and the zone are independent. A zone with daylight saving time would need the label to change by hand; the label is static text.
- The server does not render the time at all, so there is no server/browser mismatch on first paint, but also no server-rendered fallback without JavaScript.

### Language switching

On a `dl:lang` event on `document` (the flags of `x-dl::lang-switch`) the clock redraws immediately in the new language, without a reload. Only `id` and `en` have a locale mapping; for any other language the clock falls back to English (`en-US`), even if the dictionary and the number format spec have that language (`DL_NUM.intl`). The label and the date are not read from the dictionary.

### Update interval and cleanup

- `setInterval(tick, 1000)`, started in `init()`. The interval is not aligned to the second boundary, so the display can lag the true second by up to about one second and occasionally repeat or skip a second. Browsers slow timers in background tabs, and the next tick shows the correct time again because the time is read from the clock each tick.
- The interval and the `dl:lang` listener are never cleared (no destroy step). A page that replaces its DOM without reload and mounts the clock again keeps the old timers alive.
- Mounting is idempotent (`Devlib.mount(root)` skips elements already mounted); call it after inserting HTML dynamically.

### Offline and failure

No network is used: it works offline. The only failure is an invalid `timezone`: `toLocaleTimeString` throws a `RangeError`, the clock stays at placeholders and the exception stops the rest of the page boot (see the first code oddity).

### Accessibility as implemented

Plain `div`/`span` text, no `role="timer"` and no `aria-live`, so a screen reader is not interrupted every second. The `.t` and `.d` colours use the "on dark" colour tokens (`--dl-on-dark`, `--dl-on-dark-muted`), made for photo or dark hero backgrounds; on a light surface the text can be hard to read, so put the clock inside a dark container.

## Data and events

- Server to browser: `data-tz` only (the label is plain text in the markup).
- Browser to server: nothing is sent. No `dl:*` event is emitted. It listens to `dl:lang` on `document`.
- `el.dlComponent` is the `Clock` instance with `t` (the time `span`, `null` in `date` mode), `d` (the date element), `timeZone`, `init()` and `tick()` (auto-bound, `el.dlComponent.tick()` redraws on demand).
- Hooks: `[data-dl-clock]` (mount selector), `[data-dl-clock-time]`, `[data-dl-clock-date]`, `data-tz`. The PHP class has no `jsClass()`; override with `Devlib.define("Clock", MyClock)` or put `data-dl-js="MyClock"` on the tag and `Devlib.register(MyClock)`.

## Rules and pitfalls

- Use `config('devlib.timezone')` and `config('devlib.timezone_label')` for the whole application and pass the props only when one clock must differ; then pass both `timezone` and `tz-label`.
- Give a valid IANA zone name, never a label such as `WIB`.
- Never use the clock as the business time of a transaction: stamp on the server.
- Put it on a dark or photo surface.
- `mode` only has the two documented behaviours; do not invent other modes.
- Related lint rules: none (`src/Lint` has no rule for this object).
- Code oddity (not fixed): an invalid `timezone` makes `toLocaleTimeString` throw `RangeError` inside `init()` (`resources/assets/js/components/clock.js:17`). `boot.js:19` mounts the clock inside the single `DOMContentLoaded` handler without a `try`, so every component mounted after it (the rest of `resources/assets/js/boot.js`, including the final `Devlib.mount(document)`) is never mounted on that page. PHP does not validate the value.
- Code oddity (not fixed): the locale is chosen with `Devlib.lang === 'id' ? 'id-ID' : 'en-US'` (`resources/assets/js/components/clock.js:16`), ignoring `DL_NUM.intl` and the other configured languages.
- Code oddity (not fixed): the interval and the `dl:lang` listener are never cleared (`resources/assets/js/components/clock.js:12`).
- Code oddity (not fixed): `Clock.php:5` and `Clock.php:15` describe the time as "zona waktu SISTEM", but the instant comes from the browser clock and only the zone is configured (`resources/assets/js/components/clock.js:16-18`).

## Example

From the greeting card (`resources/views/components/greeting-card.blade.php:33`) and the login frame (`resources/views/components/frame.blade.php:20-21`):

```blade
{{-- Time + date in the application zone --}}
<x-dl::clock />

{{-- Date only --}}
<x-dl::clock mode="date" />

{{-- A different zone: change the zone and its label together --}}
<x-dl::clock timezone="Asia/Makassar" tz-label="WITA" />

{{-- As frame widgets: "name@position" --}}
<x-dl::frame box="right" widgets="clock@top-right, date@bottom-left">
    ...
</x-dl::frame>
```

## Related

- `x-dl::greeting`: salutation by the hour, same default zone.
- `x-dl::weather`: the other live header widget.
- `x-dl::greeting-card`, `x-dl::dashboard`, `x-dl::frame`: containers that place the clock in a dark hero or over a photo.
- `x-dl::timer`: stopwatch from a server-given number of seconds.
- `x-dl::geo-clock`: GPS clock in / out; the server decides, not the device clock.
- `x-dl::lang-switch`: triggers the `dl:lang` redraw.
- `skills/devlib/components.md`: generated prop list.
