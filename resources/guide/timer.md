## Overview

`<x-dl::timer>` is a big stopwatch display `hh:mm:ss` with an optional label and a colour tone. The server gives the number of seconds already elapsed when the page was made; while `running` is on, the browser keeps adding one second at a time. With `running` off, it shows that still time (paused, finished, waiting).

Use it for a job clock on an operator screen (setup time, run time, machine waiting for material) or anywhere an elapsed duration must be visible and alive.

Pick another object when you need:

- The current time of day or date: `x-dl::clock`.
- A countdown, an alarm, or a timer with Start / Pause buttons: there is no such object; this one only counts up and has no controls and no event.
- A static duration in a table or a report: print it as text; no timer is needed.
- A progress toward a target: `x-dl::progress`, `x-dl::progress-ring`, `x-dl::gauge`.

### Generated markup

```html
<div class="dl-timer dl-timer--accent" data-dl="x-dl::timer" data-dl-class="Timer ← DevlibComponent"
     data-dl-src="seconds elapsed (server), counted on by the browser while running" data-dl-timer data-seconds="2380" data-running="1">
  <span class="dl-timer__t" data-dl-timer-text>00:39:40</span><span class="dl-timer__l">run time</span>   <!-- label only when given -->
</div>
```

The server renders the starting time (method `text()`), so the first paint is already correct and there is no flash. Extra HTML attributes (`id`, `class`, `style`, `data-*`) go on the root `div`; `can="module.action"` also works (without the ability nothing is rendered).

## Props

Constructor of `Devlib\Components\Timer` (4 props). Use `:prop="expr"` for numbers and booleans (`:seconds="2380"`, `:running="false"`).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `seconds` | `int`, `0` | Seconds already elapsed at the moment the page is rendered. Negative values are clamped to `0` (both in `text()` and in `data-seconds`). Compute it on the server from a stored start time (`now - started_at`), not from the browser. Shown as `HH:MM:SS`; hours are not wrapped at 24 (`100:00:00` is possible, the field simply grows). |
| `running` | `bool`, `true` | `true`: the browser adds the seconds that pass after the script starts. `false`: the display stays at `seconds` and no interval is created. Written as `data-running="1"` / `"0"`. |
| `label` | `?string`, `null` | Small text after the time (`run time`). Translated through the dictionary (it is a standard text prop); wrap data values in `raw()`. `null` or empty = no label element. |
| `tone` | `string`, `'accent'` | Colour of the whole timer: `accent` (default), `warn`, `success`, `danger`, `muted`. Becomes the class `dl-timer--<tone>`; only the five values have styling, any other string falls back to the default colour. Use it to tell a state at a glance (muted = paused, warn = setup, success = running well, danger = over limit). The tone does not change by itself; the server chooses it when rendering. |

## Behavior

### Counting

- `init()` reads `data-seconds` as the base and remembers `Date.now()` as the start (`t0`). Each tick shows `base + floor((Date.now() - t0) / 1000)` when running, otherwise `base`.
- Because the display is computed from the clock difference, and not by adding one per tick, a throttled background tab or a delayed timer does not make it drift: the next tick shows the right value.
- The count starts when the script mounts, not at the exact moment the server computed `seconds`. The time between render and mount (normally well below a second, longer on a slow page) is not counted, so the display runs slightly behind a server-side computation. For long jobs this is negligible; if exact elapsed time matters, reload from the server or compute it there.
- The interval is `setInterval(tick, 1000)` and is created only when `running` is true at mount. A timer that starts paused cannot be started later without re-rendering.
- It counts up only. There is no maximum, no alarm and no event at a threshold.

### Language and locale

The digits are `00-99` zero-padded; there is no locale formatting and no language dependency. Only `label` is translated (on the server, at render time). A language switch does nothing to the timer.

### Offline and failure

No network is used. It keeps running offline. The count follows the device clock; if the user changes the device time while the page is open, the value jumps accordingly (negative differences are not clamped in the browser).

### Update interval and cleanup

- Tick every second while running. The interval is never cleared (no destroy step). A page that replaces its DOM without reload and mounts the timer again keeps the old interval alive.
- Mounting is idempotent (`Devlib.mount(root)` skips elements already mounted); call it after inserting HTML dynamically.

### Accessibility as implemented

Plain `div`/`span` text, no `role="timer"` and no `aria-live`, so a screen reader is not interrupted each second. The tone is colour only; the label should say the state in words when it matters.

## Data and events

- Server to browser: `data-seconds`, `data-running`; the label is plain markup.
- Browser to server: nothing is sent; no `dl:*` event is emitted; the timer does not post a value.
- `el.dlComponent` is the `Timer` instance with `out` (the text element), `base`, `t0`, `running` and `tick()`. Setting `el.dlComponent.running = false` does not freeze the display at the current value: the next tick shows `base` again. Do not control the timer that way; re-render with the new `seconds` / `running` instead (for example after a status change from the server).
- Hooks: `[data-dl-timer]` (mount selector), `[data-dl-timer-text]`, `data-seconds`, `data-running`. The PHP class has no `jsClass()`; override with `Devlib.define("Timer", MyTimer)` or `data-dl-js="MyTimer"` + `Devlib.register(MyTimer)`.

## Rules and pitfalls

- Store the start time (or the accumulated seconds and the last resume time) in your own data and compute `seconds` on the server at render. The browser value is display only and is lost on reload.
- `running` and `tone` are chosen at render time. When a job pauses, finishes or exceeds a limit, the page (or the part of it) must be re-rendered; the timer will not notice.
- Big font on purpose: `.dl-timer__t` is 46 px scaled by the user's font-scale setting. Use a container wide enough for `hh:mm:ss` plus label.
- Do not put a timer on every row of a long grid: every instance owns an interval that is never cleared. Print the duration as text in grids.
- Related lint rules: none (`src/Lint` has no rule for this object).
- Code oddity (not fixed): the interval is never cleared (`resources/assets/js/components/timer.js:14`) and the component has no way to pause or resume after mount (`running` is read once, `resources/assets/js/components/timer.js:12`).
- Code oddity (not fixed): `t0` is taken when the script mounts (`resources/assets/js/components/timer.js:11`), not when the server computed `seconds`, so the display lags the server value by the render-to-mount delay.

## Example

Copied from the catalog demo (`site/resources/views/demos/timer.blade.php`):

```blade
<x-dl::timer :seconds="2380" label="run time" />
<x-dl::timer :seconds="1080" tone="warn" label="setup time" style="margin-top:12px" />
<x-dl::timer :seconds="840" :running="false" tone="muted" label="paused (material wait)" style="margin-top:12px" />
```

Computing the elapsed seconds on the server (fictional model; the cast avoids a fractional value for the `int` prop):

```blade
<x-dl::timer :seconds="(int) $job->started_at->diffInSeconds(now())" :running="$job->status === 'running'" :tone="$job->status === 'running' ? 'success' : 'muted'" label="run time" />
```

## Related

- `x-dl::clock`: the current time and date (live, time zone aware), not an elapsed time.
- `x-dl::progress`, `x-dl::progress-ring`, `x-dl::gauge`: progress toward a target.
- `x-dl::stat-box`, `x-dl::kpi`: other ways to show a duration as a figure.
- `skills/devlib/object.md`: uses `Timer.php` and `timer.js` as the short example of a PHP + JS + CSS object.
- `skills/devlib/components.md`: generated prop list.
