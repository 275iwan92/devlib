## Overview

`<x-dl::sticky-notes>` is a wall of colored sticky notes. "Add note" puts a new note on top; the text is typed straight into the note, a row of five color dots changes its color and a trash button deletes it. Everything is saved by itself while the user types.

Pick `x-dl::eisenhower` for tasks that are ticked off, or `x-dl::rich-text` fields for long documents.

### Generated markup

```html
<div class="dl-notes" data-dl-notes data-save="/tools/notes" data-csrf="..." data-items='[{"id":"n1","text":"Call","color":"yellow"}]'>
  <div class="dl-notes__bar">Add note</div>
  <div class="dl-notes__wall">notes: textarea, 5 color dots, delete</div>
</div>
```

## Props

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `items` | array | `[]` | Notes: `['id', 'text' (max 1000), 'color' => yellow, green, blue, pink or orange]`. |
| `save-url` | string | empty | POST address; the body is JSON `{items: [...]}` with the whole list. Empty = preview without a server. |
| `csrf` | string | empty | Value for the `X-CSRF-TOKEN` header. |

## Behavior

- The text is typed into a plain `<textarea>`; it is never shown as HTML.
- Saving waits 600 ms after typing stops, and happens at once when the note loses focus, changes color or is deleted.

## Data and events

POST `save-url` with `{items}`; the server validates length and color and replaces the stored list. No custom event.

## Rules and pitfalls

- The server must validate (max 60 notes, 1000 characters, color from the list).
- The wall redraws on add, delete and color change, not while typing, so the cursor stays where it is.

## Example

```blade
<x-dl::sticky-notes :items="$notes" save-url="/v2/tools/notes" :csrf="csrf_token()" />
```

## Related

`x-dl::eisenhower`, `x-dl::habit-tracker`.
