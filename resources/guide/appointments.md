## Overview

`<x-dl::appointments>` is a personal agenda: the full `x-dl::calendar` (Day / Week / Month, create, edit, drag to move, delete) whose events are saved to the server by themselves after every change. The user sees only their own appointments.

It does not remind: an appointment only shows on the calendar. Pick `x-dl::calendar` alone for a read-only or project-owned calendar, and `x-dl::alarm-list` for a time-of-day alert.

### Generated markup

```html
<div class="dl-appt" data-dl-appt data-save="/tools/appointments" data-csrf="..." data-items='[{"id":"n1","title":"Dentist","start":"2026-11-09 10:00","end":"2026-11-09 11:00","allDay":false,"tone":"accent","desc":""}]'>
  <div class="dl-cal2" data-dl-calendar>... the x-dl::calendar ...</div>
</div>
```

## Props

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `items` | array | `[]` | Appointments in the `x-dl::calendar` event shape: `['id', 'title', 'start' => 'YYYY-MM-DD HH:MM' or 'YYYY-MM-DD' (all day), 'end', 'allDay', 'tone', 'desc']`. |
| `display` | string | `week` | First view: `day`, `week` or `month`. |
| `save-url` | string | empty | POST address; the body is JSON `{items: [...]}` with the whole list. Empty = preview without a server. |
| `csrf` | string | empty | Value for the `X-CSRF-TOKEN` header. |

## Behavior

- The list follows the `dl:calendar` events (`create`, `update`, `move`, `delete`) and is sent 300 ms after the last change; a failed save shows a red toast.
- Times are the calendar's own (WIB by default, `Devlib\Locale`).

## Data and events

POST `save-url` with `{items}`; the server validates the shape (title length, date formats, tone) and replaces the stored list. The inner calendar still emits `dl:calendar`.

## Rules and pitfalls

- The server must validate every field; the browser list is not trusted.
- The whole list is replaced on each save: two browsers editing at once overwrite each other.

## Example

```blade
<x-dl::appointments :items="$appointments" save-url="/v2/tools/appointments" :csrf="csrf_token()" />
```

## Related

`x-dl::calendar`, `x-dl::alarm-list`, `x-dl::world-clock`.
