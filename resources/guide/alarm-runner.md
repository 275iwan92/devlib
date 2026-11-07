## Overview

`<x-dl::alarm-runner>` is the invisible part of the alarm tool. Every 5 seconds it compares the browser clock with the active alarms; when the time (and the weekday, for repeating alarms) matches it plays the `alarm` tone (`Devlib.sound`, off until the user turns it on in Setup > Sound), shows a toast and puts the alarm in the tab title until the next click. An alarm with no repeat day is switched off after it rings.

Place ONE runner on the page that stays open, normally the shell page. It does nothing inside an iframe, so screens opened in tabs never ring twice.

Pick `x-dl::alarm-list` for the list the user edits; the two share `storage-key`.

### Generated markup

```html
<span hidden data-dl-alarm-runner data-key="dl-alarms" data-save="/tools/alarm" data-csrf="..." data-items='[...]'></span>
```

## Props

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `items` | array | `[]` | Alarms at page load (same shape as `x-dl::alarm-list`). |
| `save-url` | string | empty | POST address used only to switch off a finished one-time alarm. |
| `csrf` | string | empty | Value for the `X-CSRF-TOKEN` header. |
| `storage-key` | string | `dl-alarms` | localStorage key written by `x-dl::alarm-list`; a change there reaches the runner through the `storage` event. |

## Behavior

- Rings once per alarm per minute; reloading inside the same minute can ring again.
- The browser must keep the page open: a closed tab or a sleeping computer does not ring.

## Data and events

Reads `localStorage[storage-key]` and the `storage` event; writes the same key and POSTs to `save-url` only when a one-time alarm rang.

## Rules and pitfalls

- Use the same `storage-key` as the list, one per user.
- Sound needs one click on the page after loading (browser rule); the toast and the tab title always show.

## Example

```blade
<x-dl::alarm-runner :items="$alarms" save-url="/v2/tools/alarm" :csrf="csrf_token()" storage-key="alarms-12" />
```

## Related

`x-dl::alarm-list`, `x-dl::setup-media`, `x-dl::layer-shell`.
