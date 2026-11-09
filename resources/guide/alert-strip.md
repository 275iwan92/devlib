## Overview

`<x-dl::alert-strip>` is the notification strip at the top of a dashboard. One notification shows as still text with an action button. Two or more run as a scrolling line (ticker) that stops while the pointer is over it; each notification is a link to its source. The strip takes the colour of the most urgent notification (danger, then warn, info, success) and each notification keeps the colour of its own kind.

Use `x-dl::alert` for one message inside a form or page, and `x-dl::toast` for a message that disappears by itself.

### Generated markup

```html
<div class="dl-astrip dl-astrip--danger is-run" data-dl-astrip role="status">
  <i class="fa-solid fa-bell-concierge"></i>
  <div class="dl-astrip__view"><div class="dl-astrip__track" style="--dl-astrip-dur: 20s">
    <div class="dl-astrip__set"><a class="dl-astrip__item dl-astrip__item--danger" href="...">TEXT</a> ...</div>
    <div class="dl-astrip__set" aria-hidden="true">same items again</div>
  </div></div>
</div>
```

## Props

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `items` | array | `[]` | `['text', 'tone' => danger / warn / info / success, 'href' => link, 'open' => id of a modal (a click opens that modal instead of leaving the page; `href` stays as fallback), 'label' => button text (single notification only), 'icon' => fa-xxx]`. Empty = nothing is drawn. |

## Behavior

- Text is shown in capital letters (CSS), as plain text, never as HTML.
- The second copy of the list only makes the loop seamless; it is hidden from screen readers and not focusable. Speed is fixed (about 3 letters per second); the more text, the longer the loop.
- Pointer over the strip or keyboard focus on a link pauses the movement. With "reduce motion" on in the system, nothing moves and the line can be scrolled sideways.

## Data and events

No request and no JavaScript. The page decides which notifications exist and who sees them.

## Rules and pitfalls

- Filter by permission on the server before passing `items`; the strip does not know who may see what.
- Keep each text short (one sentence); long text makes the loop slow.

## Example

```blade
<x-dl::alert-strip :items="[
    ['text' => 'Material: 14 materials need attention', 'tone' => 'danger', 'href' => '/v2/material-planning'],
    ['text' => 'Tasks: 4 not done', 'tone' => 'warn', 'href' => '/v2/tasks'],
]" />
```

## Related

`x-dl::alert`, `x-dl::toast`, `x-dl::notification-list`.
