## Overview

`<x-dl::habit-tracker>` is a personal habit tracker. Each habit is a card with a "Mark as done" button for today, the streak (days in a row up to today, or up to yesterday while today is not yet ticked), a grid of the last 14 days that can be clicked to correct a day, and a delete button. A form adds a new habit.

Pick `x-dl::checklist`-like objects for one-time tasks (`x-dl::eisenhower`) and `x-dl::calendar` for dated events.

### Generated markup

```html
<div class="dl-habit" data-dl-habit data-save="/tools/habit" data-csrf="..." data-items='[{"id":"h1","name":"Walk","log":["2026-11-08"]}]'>
  <ul class="dl-habit__list">cards: name, streak, done button, delete, 14-day grid</ul>
  <form class="dl-habit__add">new habit + "Add habit"</form>
</div>
```

## Props

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `items` | array | `[]` | Habits: `['id', 'name', 'log' => ['YYYY-MM-DD', ...]]` (the days it was done, in the user's device date). |
| `save-url` | string | empty | POST address; the body is JSON `{items: [...]}` with the whole list. Empty = preview without a server. |
| `csrf` | string | empty | Value for the `X-CSRF-TOKEN` header. |

## Behavior

- Days are the date of the user's device; entries older than 400 days are dropped in the browser.
- Every change redraws the list and saves 300 ms later; a failed save shows a red toast.

## Data and events

POST `save-url` with `{items}`; the server validates name length and date format and replaces the stored list. No custom event.

## Rules and pitfalls

- The server must validate the shape; dates are strings in `Y-m-d`.
- Streaks are computed in the browser from the log; nothing else is stored.

## Example

```blade
<x-dl::habit-tracker :items="$habits" save-url="/v2/tools/habit" :csrf="csrf_token()" />
```

## Related

`x-dl::eisenhower`, `x-dl::alarm-list`, `x-dl::calendar`.
