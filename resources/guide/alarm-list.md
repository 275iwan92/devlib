## Overview

`<x-dl::alarm-list>` is the list of a user's alarms: one row per alarm with a big time, the label and repeat days, an on / off checkbox and a delete button, plus an "Add alarm" form (time, label, repeat days). An alarm with no repeat day rings once and then switches itself off.

It only edits the list. Ringing is done by `x-dl::alarm-runner`, which must sit on a page that stays open (the shell page). Every change is copied to `localStorage[storage-key]` (the runner reads it) and the whole list is sent to the server.

Pick another object for a countdown (`x-dl::countdown`), a work timer (`x-dl::pomodoro`) or dated appointments (`x-dl::calendar`).

### Generated markup

```html
<div class="dl-alarm" data-dl-alarm data-key="dl-alarms" data-save="/tools/alarm" data-csrf="..." data-items='[{"id":"a1","time":"06:30","label":"Shift","days":[1,2,3,4,5],"on":true}]'>
  <ul class="dl-alarm__list" data-dl-alarm-list>...rows drawn by the script...</ul>
  <p class="dl-alarm__empty" data-dl-alarm-empty>No alarms yet.</p>
  <form class="dl-alarm__add" data-dl-alarm-add>time, label, day checkboxes, "Add alarm"</form>
</div>
```

## Props

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `items` | array | `[]` | Alarms: `['id', 'time' => 'HH:MM', 'label', 'days' => [0 Sunday .. 6 Saturday] (empty = once), 'on' => bool]`. |
| `save-url` | string | empty | POST address; the body is JSON `{items: [...]}` with the whole list. Empty = preview. |
| `csrf` | string | empty | Value for the `X-CSRF-TOKEN` header. |
| `storage-key` | string | `dl-alarms` | localStorage key shared with `x-dl::alarm-runner`; give each user their own. |

## Behavior

- Adding sorts the list by time; the switch turns one alarm on or off without deleting it.
- Saving waits 300 ms after the last change; a failed save shows a red toast and the list stays on screen.

## Data and events

POST `save-url` with `{items}`; the server validates the shape and replaces the stored list. No custom event.

## Rules and pitfalls

- The server must validate every field (time format, days 0-6, label length); the browser list is not trusted.
- Without an `x-dl::alarm-runner` on the shell page nothing rings.

## Example

```blade
<x-dl::alarm-list :items="$alarms" save-url="/v2/tools/alarm" :csrf="csrf_token()" storage-key="alarms-12" />
```

## Related

`x-dl::alarm-runner`, `x-dl::setup-media` (Sound page), `x-dl::pomodoro`, `x-dl::calendar`.
