## Overview

`<x-dl::timeline>` shows events one under another along a vertical line, newest first: a coloured dot per kind of event, a bold title with a small date, and an optional one-line body. It is the history of a relationship (customer, supplier, machine): a call, a quotation, an order and a complaint on one line. The object only lays out what the screen gives it; it reads no database, sorts nothing and translates nothing.

Use it for a short chronological story (10 to 40 lines) on a profile screen. Pick another object when:

- the entries are long narratives read as articles: `x-dl::feed`;
- the data is tabular and must be sorted or filtered: `x-dl::grid`;
- you show the log of one document (who changed what): the History tab of `x-dl::form`.

Generated markup:

```html
<div class="dl-timeline" data-dl="x-dl::timeline" ...>
  <div class="dl-timeline__item dl-timeline__item--success">
    <span class="dl-timeline__dot"><i class="fa-solid fa-bag-shopping"></i></span>
    <div class="dl-timeline__main">
      <div class="dl-timeline__title"><a href="...">Title</a> <small class="dl-timeline__meta">07-Sep-2026</small></div>
      <div class="dl-timeline__body">One line.</div>
    </div>
  </div>
  <!-- no items: --> <p class="dl-timeline__empty">Nothing has happened yet.</p>
</div>
```

## Props

Constructor of `Devlib\Components\Timeline` (2 props). Use `:items="$items"` (array expression).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `items` | `array`, `[]` | Events in the order given (the screen sorts, newest first). Keys, all optional: `title` (bold line, data, not translated), `meta` (small text after the title, usually the date), `body` (one line under the title), `tone` (`accent`, `success`, `warn`, `danger`, `info`, `violet`, `neutral`; default `neutral`; colours the dot and the title on hover), `icon` (a Font Awesome class or an `Icon` meaning, shown in the dot), `url` (turns the title into a link). |
| `empty` | `string`, `'Nothing has happened yet.'` | Text shown when `items` is empty; translated through the dictionary. |

Inherited from `DevlibComponent`: `can="module.action"` hides the whole timeline without the ability; extra attributes are merged onto the root `div`.

## Pitfalls

- Long lists are not paged: the screen should cut the list (for example the latest 40) and say so.
- A `tone` outside the list falls back to the neutral colour (the class simply has no rule).
