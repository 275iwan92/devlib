## Overview

`<x-dl::stopwatch>` is a personal stopwatch: the time as hh:mm:ss.cc, Start / Stop, Lap and Reset, and a list of laps (newest on top) with the gap to the previous lap. It runs only in the browser; the state is kept in `localStorage[storage-key]` and the time is computed from the clock, so it stays right after a page change or a reload, even while it runs.

Pick `x-dl::timer` to show how long a job has run (seconds given by the server), `x-dl::countdown` to count down.

### Generated markup

```html
<div class="dl-sw" data-dl-sw data-key="dl-stopwatch">
  <output class="dl-sw__time" data-dl-sw-time>00:00:00.00</output>
  <div class="dl-sw__btns">Start / Stop, Lap, Reset</div>
  <ol class="dl-sw__laps" data-dl-sw-laps reversed>...</ol>
</div>
```

## Props

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `storage-key` | string | `dl-stopwatch` | localStorage key of the state; give each user their own. |

## Behavior

- Lap stores the total time at that moment; the second figure of a lap is the gap to the lap before.
- Reset clears the time and all laps. The display refreshes every 50 ms while visible.

## Data and events

None sent to a server; nothing else is stored. No custom event.

## Rules and pitfalls

- Clearing browser data clears the stopwatch (it is a temporary tool by design).

## Example

```blade
<x-dl::stopwatch storage-key="stopwatch-12" />
```

## Related

`x-dl::countdown`, `x-dl::timer`, `x-dl::pomodoro`.
