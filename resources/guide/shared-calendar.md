## Overview

`<x-dl::shared-calendar>` is a calendar people share. It wraps `x-dl::calendar` (Day / Week / Month) and adds: invite people with `@name`, answer an invitation with Accept / Tentative / Decline, see a colleague's calendar, pick a meeting room, and set a reminder. One door for a meeting: an appointment with participants is a meeting.

The server decides who sees what; the object only shows and sends. Use `x-dl::appointments` for a private list saved as a whole, and `x-dl::calendar` alone for a calendar the project owns.

### Generated markup

```html
<div class="dl-shared" data-dl-shared data-save="/calendar/events" data-delete="/calendar/events/:id" data-rsvp="/calendar/events/:id/rsvp" data-team="/calendar/team" data-open="/tools/calendar?mode=task" data-csrf="...">
  <div class="dl-shared__bar">... "Show calendar of" @name chips (added by JS when data-team is set) ...</div>
  <div class="dl-cal2" data-dl-calendar data-people='[{"id":1,"name":"Nisa"}]' data-rooms='{"7":"Meeting Room A"}'>... the x-dl::calendar ...</div>
</div>
```

## Props

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `events` | array | `[]` | Calendar events plus `people` (`[id, name, rsvp]`), `room` (id), `roomName`, `remind` (minutes), `vis` (`PRIVATE`, `BUSY`, `DETAIL`); `ro => true` = read-only (a task, a colleague's busy time); `invite => [rsvp, by]` = someone else's event I am invited to. |
| `people` | array | `[]` | Who can be mentioned: `[id, name, role]`. Never include the signed-in user. |
| `rooms` | array | `[]` | `[id => name]`; empty = no Room field. |
| `display` | string | `week` | First view. |
| `save-url` | string | empty | POST `{event}`; answer `{event, warnings[]}`. An id starting with `n` is new. Empty = preview without a server. |
| `delete-url` | string | empty | DELETE; `:id` is replaced by the event id. |
| `rsvp-url` | string | empty | POST `{rsvp}` (`ACCEPTED`, `TENTATIVE`, `DECLINED`); `:id` replaced. |
| `team-url` | string | empty | GET `?users=1,2` answers `{events[]}` (read-only, already filtered by the server). Empty = no "Show calendar of" bar. |
| `open-url` | string | empty | Where a double-click on a read-only task event goes. |
| `csrf` | string | empty | Value for `X-CSRF-TOKEN`. |

## Behavior

- Double-click an empty slot to create; the dialog has Title, time, colour, Participants (type `@ni`, pick with Enter or click, Backspace removes the last), Room, Reminder, Visible to others, description.
- An invitation not yet answered is drawn dashed with an envelope; double-click opens Accept / Tentative / Decline. Decline removes it from this calendar.
- Read-only events cannot be dragged or edited. A save that fails removes a new event, or reloads the page for an edit.
- `warnings` from the server (a participant is busy) are shown as yellow toasts.

## Data and events

Each change is one request, not the whole list. The inner calendar keeps emitting `dl:calendar` (`create`, `update`, `move`, `delete`, and for sharing `open`, `rsvp`).

## Rules and pitfalls

- The server must check owner, participants, visibility and room clashes; the browser is not trusted.
- Do not put the signed-in user in `people`; the owner is implicit.

## Example

```blade
<x-dl::shared-calendar :events="$events" :people="$people" :rooms="$rooms" :save-url="route('calendar.save')" :delete-url="route('calendar.delete', ':id')" :rsvp-url="route('calendar.rsvp', ':id')" :team-url="route('calendar.team')" :csrf="csrf_token()" />
```
