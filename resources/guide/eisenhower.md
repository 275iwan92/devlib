## Overview

`<x-dl::eisenhower>` is a task list laid out as the Eisenhower matrix: four boxes, "Do first" (important and urgent), "Schedule" (important, not urgent), "Delegate" (urgent, not important) and "Eliminate" (neither). Each box has its own add field; a task can be ticked off, moved to another box with its drop-down, or deleted; "Clear completed" removes all ticked tasks.

Pick `x-dl::kanban` for team work with stages, or `x-dl::habit-tracker` for repeating habits.

### Generated markup

```html
<div class="dl-eis" data-dl-eis data-save="/tools/tasks" data-csrf="..." data-items='[{"id":"k1","text":"Report","q":1,"done":false}]'>
  <div class="dl-eis__grid">4 sections [data-dl-eis-q]: title, list, add form</div>
  <button data-dl-eis-clear>Clear completed</button>
</div>
```

## Props

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `items` | array | `[]` | Tasks: `['id', 'text' (max 200), 'q' => 1..4 (1 Do first, 2 Schedule, 3 Delegate, 4 Eliminate), 'done' => bool]`. |
| `save-url` | string | empty | POST address; the body is JSON `{items: [...]}` with the whole list. Empty = preview without a server. |
| `csrf` | string | empty | Value for the `X-CSRF-TOKEN` header. |

## Behavior

- Moving uses a drop-down, not dragging, so the keyboard works.
- Text is shown with `textContent`, never as HTML. Every change saves 300 ms later; a failed save shows a red toast.

## Data and events

POST `save-url` with `{items}`; the server validates text length and the box number and replaces the stored list. No custom event.

## Rules and pitfalls

- The server must validate (max 200 tasks, `q` between 1 and 4).
- Ticked tasks stay in their box (struck through) until "Clear completed".

## Example

```blade
<x-dl::eisenhower :items="$tasks" save-url="/v2/tools/tasks" :csrf="csrf_token()" />
```

## Related

`x-dl::kanban`, `x-dl::sticky-notes`, `x-dl::habit-tracker`.
