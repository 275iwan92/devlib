## Overview

`<x-dl::countdown>` is a personal countdown timer: a ring with the time left, minus / plus and a number box for the minutes (1 to 600), Start / Pause and Reset, a row of presets and a "Save as preset" form. Clicking a preset sets its minutes. When the time is over it plays the `timer` tone (`Devlib.sound`, off until the user turns it on in Setup > Sound), shows a toast and puts "Timer finished" in the tab title until the next click.

The running state lives in the browser, computed from the clock, so it stays right after a page change or a reload; a countdown that ended while the page was closed is closed silently. Only the presets are sent to the server.

Pick another object for work and break rounds (`x-dl::pomodoro`), for counting up (`x-dl::stopwatch`) or for a time of day (`x-dl::alarm-list`).

### Generated markup

```html
<div class="dl-cd" data-dl-cd data-key="dl-countdown" data-minutes="5" data-save="/tools/timer" data-csrf="..." data-items='[{"id":"t1","label":"Tea","minutes":3}]'>
  <div class="dl-cd__top"><div class="dl-cd__ring">svg ring + time</div><div class="dl-cd__ctl">minus, minutes box, plus, Start, Reset</div></div>
  <ul class="dl-cd__presets" data-dl-cd-presets>...</ul>
  <form class="dl-cd__add" data-dl-cd-add>name + "Save as preset"</form>
</div>
```

## Props

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `presets` | array | `[]` | Saved presets: `['id', 'label', 'minutes' => 1..600]`. |
| `minutes` | int | 5 | Minutes at the first load (clamped 1 to 600); later the last used value is remembered. |
| `save-url` | string | empty | POST address; the body is JSON `{items: presets}`. Empty = preview. |
| `csrf` | string | empty | Value for the `X-CSRF-TOKEN` header. |
| `storage-key` | string | `dl-countdown` | localStorage key of the running state; give each user their own. |

## Behavior

- Minutes can be changed only while the timer is stopped.
- The ring drains from full to empty; reduced screen animation (`Devlib.motion` off) stops its movement.
- Two tabs of one user: only one closes the countdown.

## Data and events

POST `save-url` with `{items}` after the last preset change (300 ms); a failed save shows a red toast. No custom event.

## Rules and pitfalls

- The server must validate the presets (label length, minutes 1 to 600).
- The tone and toast need the page open; a closed tab does not ring.

## Example

```blade
<x-dl::countdown :presets="$presets" save-url="/v2/tools/timer" :csrf="csrf_token()" storage-key="timer-12" />
```

## Related

`x-dl::stopwatch`, `x-dl::pomodoro`, `x-dl::alarm-list`, `x-dl::timer`.
