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
| `tasks` | `array`, `[]` | `[['id', 'group' (key in groups), 'title', 'summary', 'image' (screenshot url), 'flow' => [label, ...], 'steps' => [[x%, y%, title, text], ...], 'tips' => [text, ...], 'related' => [id, ...], 'pin' => bool (extra flow card), 'href' => url of the full guide], ...]`. Text is data: it is not translated. |
| `groups` | `array`, `[]` | `[key => ['title', 'tag', 'hue' (0-360)]]`. The hue colours the tag, the flow card and the chapter hero. |
| `title` | `?string`, `null` | Heading of the board. Empty = "Illustrated guide". |
| `presentUrl` | `?string`, `null` | Url with `{id}` for the Present button (for example the page that plays the deck of the task). Empty = no button. |
| `tryUrl` | `?string`, `null` | Url with `{id}` for the Try button. Empty = no button. |
| `source` | `?string`, `null` | Data source text for the Developer mode label. |

## Behavior

- Search needs every typed word in the card text (title, summary, group, flow). A chip keeps one group. Nothing found shows "No task matches."
- A card or a link opens the chapter and puts `#ch-<id>` in the address, so the chapter can be shared; the address with that hash opens the chapter directly. "All tasks" returns to the board.
- The chapter table of contents jumps to the flow, each step, the tips and the next links. On a narrow screen it is not sticky.
- Language: only the button and heading labels are translated (Search a task, All, Flow, Read, Present, Try, Full guide, steps, All tasks, In this chapter, The flow, Step by step, Tips, Read next, Previous, Next, Related, No task matches., Illustrated guide).
