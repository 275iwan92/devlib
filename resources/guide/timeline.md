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

Constructor of `Devlib\Components\Timeline` (3 props). Use `:items="$items"` (array expression).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `items` | `array`, `[]` | Events in the order given (the screen sorts, newest first). Keys, all optional: `title` (bold line, data, not translated), `meta` (small text after the title, usually the date), `body` (one line under the title), `tone` (`accent`, `success`, `warn`, `danger`, `info`, `violet`, `neutral`; default `neutral`; colours the dot and the title on hover), `icon` (a Font Awesome class or an `Icon` meaning, shown in the dot), `url` (turns the title into a link). |
| `empty` | `string`, `'Nothing has happened yet.'` | Text shown when `items` is empty; translated through the dictionary. |
| `source` | `?string`, `null` | Data source label shown in Developer mode (F10); the screen names the tables the events come from. |

Inherited from `DevlibComponent`: `can="module.action"` hides the whole timeline without the ability; extra attributes are merged onto the root `div`.

## Pitfalls

- Long lists are not paged: the screen should cut the list (for example the latest 40) and say so.
- A `tone` outside the list falls back to the neutral colour (the class simply has no rule).

## Behavior

- Items are drawn in the order given; the object never sorts, groups or pages them. The first item is at the top.
- Each item has a dot (the `icon` inside, if any) joined by a vertical line, a title line (the title is a link when `url` is set, followed by the small `meta` text) and an optional `body` line.
- `tone` colours the dot; a missing or unknown tone is drawn neutral. With no items only the `empty` text is shown.
- `empty` is translated through the dictionary; `title`, `meta` and `body` are data and are not translated.
- Text is escaped; it is not clickable except the title link.

## Data and events

Data in: only the `items` prop. No request, no script, no custom event and no `el.dlComponent`. Opening a link is a normal page visit through `url`.

## Rules and pitfalls

- Sort and cut the list on the server (newest first, for example the latest 40) and say so on the screen if more exist.
- Keep `body` to one line; a long text belongs to `x-dl::feed`.
- Give `meta` already formatted dates in the language of the screen.
- Do not put secrets or personal data in the items that the viewer should not see; the object shows everything it is given.
- Do not use hex colors or `<script>` in the screen around it: lint rules L001, L002 and L003 apply. There is no Timeline-specific lint rule.

## Example

```blade
<x-dl::timeline source="crm.activities" :items="[
    ['title' => 'Order SO-2609-0012', 'meta' => '07-Sep-2026', 'body' => '50,000 snack boxes', 'tone' => 'success', 'icon' => 'fa-bag-shopping', 'url' => '/orders/12'],
    ['title' => 'Quotation sent', 'meta' => '02-Sep-2026', 'tone' => 'accent', 'icon' => 'fa-file-invoice'],
    ['title' => 'Complaint: colour too dark', 'meta' => '28-Aug-2026', 'tone' => 'danger'],
]" />
```

## Related

- `x-dl::feed`: longer entries read as articles.
- `x-dl::grid`: the same events as a sortable, filterable table.
- `x-dl::form`: the History tab of one document.
- `x-dl::profile-banner`: the header of the profile screen this usually sits under.
