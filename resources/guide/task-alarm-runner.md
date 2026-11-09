## Overview

`<x-dl::task-alarm-runner>` is the invisible bell for `x-dl::eisenhower` tasks. Every 5 seconds it compares the device clock with the due time of each task that is not done and has its alarm on. When the due time arrives (at most 12 hours ago) it plays the notification tone (`Devlib.sound.play('notif')`, follows Setup > Sound), shows a small toast with the task text and puts the task in the tab title until the next click. Each task rings once per due time.

Put ONE on the parent page that stays open (the Home shell). Use `x-dl::alarm-runner` for the daily alarm list.

### Generated markup

```html
<span hidden data-dl-task-alarm data-key="tmerp-tasks-7" data-items='[{"id":"k1","text":"Report","due":"2026-11-09 14:00","alarm":true}]' data-l-ring="Task due"></span>
```

## Props

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `items` | array | `[]` | Tasks from the server: `['id', 'text', 'done', 'due' => 'Y-m-d H:i', 'alarm' => bool]`. Used until the task screen has copied a newer list. |
| `storage-key` | string | `dl-tasks` | The same key as `storage-key` of `x-dl::eisenhower`. The list copied there wins; rung tasks are remembered under `<key>-rung`. |

## Behavior

- The tone is silent until the user turns Notification on in Setup > Sound, and the browser only plays sound after the first click or key on the page.
- Rings only in the top window (not in a screen inside an iframe) and only while the page is open.
- A task whose due time passed more than 12 hours ago does not ring (the page was closed); a ticked task never rings.

## Data and events

No request. It reads the list from `localStorage[storageKey]` and listens to the `storage` event for changes made on the task screen.

## Rules and pitfalls

- Due times are device local times; the server and the device should share a time zone.
- The runner does not save anything to the server; "already rung" lives in this browser only.

## Example

```blade
<x-dl::task-alarm-runner :items="$tasks" :storage-key="'tasks-'.auth()->id()" />
```

## Related

`x-dl::eisenhower`, `x-dl::alarm-runner`, `x-dl::alert-strip`.
