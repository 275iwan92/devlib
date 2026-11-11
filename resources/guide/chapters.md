## Overview

`<x-dl::chapters>` is an illustrated guide in the style of a blog or a pin board. The first view is a board of cards: each card shows a real screenshot enlarged on the first step, the task name, a summary and the number of steps; flow cards draw the task as a vertical line of steps. A search box and group chips filter the board. A card opens a chapter: a hero picture, the flow as a row of numbered boxes that appear one after the other, every step with its own enlarged picture and ring, tips, and links to the next, previous and related chapters.

Use it to teach tasks with pictures. Pick another object when:

- the reader should click the parts of one screen: `x-dl::tour`;
- the same task should play on its own like a video: `x-dl::deck` (it accepts the same steps in a `walk` slide);
- the text is an article without pictures: `x-dl::feed` or `x-dl::review-feed`.

Generated markup:

```html
<section class="dl-ch" data-dl="x-dl::chapters" data-dl-chapters>
  <div class="dl-ch__home" data-ch-home>
    <header class="dl-ch__head">...<input data-ch-search> <button data-ch-group="sales">Sales</button></header>
    <div class="dl-ch__masonry"><article class="dl-ch__card" data-ch-card data-g="sales" data-t="..."><div class="dl-shot">...</div> ... <button data-ch-open="co-form">Read</button></article></div>
  </div>
  <article class="dl-ch__art" data-ch-art="co-form" hidden>...hero, toc, flow, steps with x-dl::shot, tips, next...</article>
</section>
```

## Props

Constructor of `Devlib\Components\Chapters` (6 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `tasks` | `array`, `[]` | `[['id', 'group' (key in groups), 'title', 'summary', 'image' (screenshot url), 'flow' => [label, ...], 'steps' => [[x%, y%, title, text], ...], 'tips' => [text, ...], 'related' => [id, ...], 'anim' => url of a framed looping animation used instead of the zoom on the card and the steps (optional), 'pin' => bool (extra flow card), 'href' => url of the full guide], ...]`. Text is data: it is not translated. |
| `groups` | `array`, `[]` | `[key => ['title', 'tag', 'hue' (0-360)]]`. The hue colours the tag, the flow card and the chapter hero. |
| `title` | `?string`, `null` | Heading of the board. Empty = "Illustrated guide". |
| `present-url` | `?string`, `null` | Url with `{id}` for the Present button (for example the page that plays the deck of the task). Empty = no button. |
| `try-url` | `?string`, `null` | Url with `{id}` for the Try button. Empty = no button. |
| `source` | `?string`, `null` | Data source text for the Developer mode label. |

## Behavior

- Search needs every typed word in the card text (title, summary, group, flow). A chip keeps one group. Nothing found shows "No task matches."
- A card or a link opens the chapter and puts `#ch-<id>` in the address, so the chapter can be shared; the address with that hash opens the chapter directly. "All tasks" returns to the board.
- The chapter table of contents jumps to the flow, each step, the tips and the next links. On a narrow screen it is not sticky.
- Language: only the button and heading labels are translated (Search a task, All, Flow, Read, Present, Try, Full guide, steps, All tasks, In this chapter, The flow, Step by step, Tips, Read next, Previous, Next, Related, No task matches., Illustrated guide).

## Data and events

- Data in: `tasks` and `groups` only (plain arrays; nothing is read from a table). A task whose `group` is not a key of `groups` is drawn with an empty tag and hue 215. `related` ids that match no task are ignored.
- `present-url` and `try-url` are templates: `{id}` is replaced by the task id, and the result is an ordinary link (`<a href>`) to a screen of the project. Nothing is requested by the object itself.
- Address: opening a chapter writes `#ch-<id>` with `history.replaceState` (no new history entry); "All tasks" removes it. The hash is read only when the page loads.
- No custom event and no `el.dlComponent`. The script (`chapters.js`) only filters cards, switches the board and the chapter, and scrolls to a part.
- Each card and step is drawn by `x-dl::shot` (so the `anim` key of a task is passed to it).

## Rules and pitfalls

- Make `id` unique and url-safe; it becomes the hash and a DOM id.
- `steps` points are `[x%, y%, title, text]` in percent of the picture; the first step is also the point that the card zooms to. A task without `steps` still opens but has no step pictures.
- Keep the screenshots under `public/` and free of real data; the text is data and is not translated, so write it in the language of the screen.
- The hue (0-360) comes from `groups`, so no hex color is needed; do not put hex colors or `<script>` in the screen around it (lint rules L001, L002, L003 apply). There is no Chapters-specific lint rule.
- Put one chapters board per page: the hash `#ch-<id>` is not scoped to a board.

## Example

```blade
<x-dl::chapters title="How do I...?" source="docs.tasks"
    present-url="/guide/present/{id}" try-url="/guide/try/{id}"
    :groups="['sales' => ['title' => 'Sales', 'tag' => 'Sales', 'hue' => 215], 'stock' => ['title' => 'Stock', 'tag' => 'Stock', 'hue' => 150]]"
    :tasks="[
        ['id' => 'co-form', 'group' => 'sales', 'title' => 'Create a customer order', 'summary' => 'From a quotation to a confirmed order.',
         'image' => '/guide/co-form.png', 'flow' => ['Quotation', 'Order', 'Confirm'],
         'steps' => [[30, 11, 'Press New', 'Opens an empty order.'], [62, 40, 'Pick the customer', 'Type a few letters.']],
         'tips' => ['Save a draft often.'], 'related' => ['stock-in'], 'pin' => true],
    ]" />
```

## Related

- `x-dl::shot`: draws the picture of every card and step.
- `x-dl::deck`: plays the same steps on its own (its `walk` slide takes them).
- `x-dl::tour`: the reader finds the parts of one screen by clicking.
- `x-dl::slideshow`: pictures that follow one another in a window.
- `x-dl::feed` and `x-dl::review-feed`: text articles without pictures.
