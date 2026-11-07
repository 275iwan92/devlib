## Overview

`<x-dl::pomodoro>` is a Pomodoro timer card: a progress ring with a round Play / Pause button, the time left between a minus and a plus button, a Focus | Break switch, the number of Focus sessions finished today and a "..." menu.

- Default 25 minutes Focus and 5 minutes Break. Minus / plus change the minutes of the phase on screen by one (1 to 90) while the timer is paused.
- A finished Focus adds one session, plays the `pomodoro` tone (two soft low notes) and, if "Start Break automatically" is on, starts the Break. A finished Break plays a shorter tone and waits for Play.
- When a phase ends the tab title shows "Focus finished" (or "Break finished") until the next click, and a browser notification is sent if the user switched "Browser notification" on in the menu (permission is asked only then).
- The ring animation stops when screen animation is reduced (`Devlib.motion` off, or the system asks for reduced motion).

Pick another object for a plain stopwatch display (`x-dl::timer`) or a progress percentage (`x-dl::progress-ring`).

### Generated markup

```html
<div class="dl-pomo" data-dl-pomo data-key="dl-pomodoro" data-focus="25" data-break="5" data-auto="1" data-sessions="0" data-save="" data-csrf="">
  <div class="dl-pomo__ring"><svg>...track + progress circle (data-dl-pomo-bar)...</svg><button class="dl-pomo__play" data-dl-pomo-play>...</button></div>
  <div class="dl-pomo__main">
    <div class="dl-pomo__time"><button data-dl-pomo-minus>-</button><output data-dl-pomo-time>25:00</output><button data-dl-pomo-plus>+</button></div>
    <div class="dl-pomo__row"><div class="dl-pomo__seg">Focus | Break radios</div><span>Sessions today: <b data-dl-pomo-sessions>0</b></span><details class="dl-pomo__menu">Reset, auto Break, notification</details></div>
  </div>
</div>
```

## Props

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `focus` | int | 25 | Focus minutes (1 to 90). |
| `break` | int | 5 | Break minutes (1 to 90). |
| `auto` | bool | true | Start the Break by itself when Focus ends. |
| `sessions` | int | 0 | Focus sessions finished today (the server counts). |
| `save-url` | string | empty | POST address for settings and finished sessions; empty = preview without a server. |
| `csrf` | string | empty | Value for the `X-CSRF-TOKEN` header of that POST. |
| `storage-key` | string | `dl-pomodoro` | localStorage key of the running state; give each user their own key. |
| `compact` | bool | false | Smaller card for a widget board. |

## Behavior

- The running state (phase, end time, time left) lives in `localStorage[storage-key]` and is computed from the clock, so it stays right after a page change, a reload or a hidden tab. A phase that ended long ago while the page was closed is closed silently (no tone).
- Only one open tab closes a phase: the others read the new state from storage.
- Sound: tones come from `Devlib.sound` and are OFF until the user turns "Pomodoro" on in Setup > Sound.

## Data and events

POST `save-url` with JSON `{focus, break, auto, done}` (`done: true` once per finished Focus); the server must answer `{sessions}`. The server decides the day change and the count; the object never trusts its own count.

## Rules and pitfalls

- Give every user a different `storage-key`, or two people on one browser share a running timer.
- In `x-dl::widget-board` use the widget type `pomodoro` (data: focus, break, auto, sessions, save_url, csrf, key).

## Example

```blade
<x-dl::pomodoro :focus="25" :break="5" :sessions="3" save-url="/tools/pomodoro" :csrf="csrf_token()" storage-key="pomo-12" />
```

## Related

`x-dl::timer`, `x-dl::progress-ring`, `x-dl::setup-media` (Sound page), `x-dl::widget-board`.
