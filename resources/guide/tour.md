## Overview

`<x-dl::tour>` is a set of small interactive missions. The reader picks a mission, sees a real screenshot with pulsing dots, and clicks each dot; a popup explains that part of the screen, the step list ticks off and a progress counter grows. When every dot has been seen the mission is complete, the score goes up and a button offers the next mission. Progress is kept in the browser.

Use it to teach one screen by letting the reader find the parts. Pick another object when:

- the guide should play on its own like a video: `x-dl::deck`;
- the pictures simply follow one another in a window: `x-dl::slideshow`;
- the text is a long article: `x-dl::feed` or `x-dl::review-feed`.

Generated markup:

```html
<section class="dl-tour" data-dl="x-dl::tour" data-dl-tour data-key="default" data-total="2">
  <div class="dl-tour__list" data-tr-list>
    <header class="dl-tour__head"><h3>Interactive missions</h3><div class="dl-tour__score"><b data-tr-score>0</b> / 2 missions done ...</div></header>
    <section class="dl-tour__group"><h4>Sales</h4><div class="dl-tour__cards"><button class="dl-tour__card" data-tr-open="demo-list">...</button></div></section>
  </div>
  <div class="dl-tour__stage" data-tr-stage="demo-list" hidden>
    <div class="dl-tour__cam"><img src="..."><button class="dl-tour__hot" data-tr-step="0" style="left: 30%; top: 11%"></button><div class="dl-tour__pop" data-tr-pop hidden>...</div></div>
    <aside class="dl-tour__side"><ol class="dl-tour__steps">...</ol><div class="dl-tour__win" hidden>...</div></aside>
  </div>
</section>
```

## Props

Constructor of `Devlib\Components\Tour` (4 props). Use `:missions="$missions"` (array expression).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `missions` | `array`, `[]` | `[['id', 'group' (heading in the list, optional), 'title', 'summary', 'image' (screenshot url), 'width', 'height' (image pixels, optional), 'steps' => [[x, y, title, text], ...]], ...]`. `x` and `y` are percent of the image width and height. Text is data: it is not translated. Empty = the text "No missions." |
| `title` | `?string`, `null` | Heading of the list. Empty = "Interactive missions". |
| `key` | `string`, `'default'` | Name of the browser storage slot (`dl.tour.<key>`). Use the user id so two accounts on one device do not share progress. |
| `source` | `?string`, `null` | Data source text for the Developer mode label. |

## Behavior

- A dot click marks the step as seen (the dot and the step in the list turn green), opens the popup next to the dot (on the other side near the right or bottom edge) and updates the counter. A step in the list scrolls to its dot and does the same. Esc or the cross closes the popup.
- All dots seen = the mission is done: a green check on its card, the score and the score bar grow, and "Next mission" opens the next undone mission. Seen dots stay green when the mission is opened again.
- "Start over" (shown once there is progress) clears the progress of this key.
- Progress lives in `localStorage` only: it does not follow the user to another device and is lost when site data is cleared.
- Language: only the button labels are translated (All missions, Mission complete!, Next mission, Start over, steps, missions done, Close, No missions.).

## Data and events

- Data in: `missions` only. Nothing is requested and no custom event is fired; there is no `el.dlComponent` API.
- Progress is stored in `localStorage` under `dl.tour.<key>` as `{done: [mission ids], seen: {mission id: [step numbers]}}`. Reading and writing are wrapped in try/catch, so with storage blocked the tour still works but forgets on reload.
- The counters `n / total` are drawn by the script from that state; the server never learns the progress.

## Rules and pitfalls

- Give a different `key` per user (for example the user id), or two accounts on one device share progress; use a different key per tour on one page too.
- Mission `id` must be unique; removing or renaming a mission leaves its old id in the stored progress, which is harmless.
- `steps` are `[x, y, title, text]` with `x` and `y` in percent of the picture, so the dots stay on the right place at any width. Give `width` and `height` to avoid the layout jumping while the picture loads.
- Keep the picture an honest screenshot with demo data only, under `public/`. Texts are data and are not translated.
- Do not use hex colors or `<script>` in the screen around it: lint rules L001, L002 and L003 apply. There is no Tour-specific lint rule.

## Example

```blade
<x-dl::tour title="Learn the order screen" :key="'u'.auth()->id()" :missions="[
    ['id' => 'co-list', 'group' => 'Sales', 'title' => 'The order list', 'summary' => 'Find your way around.',
     'image' => '/guide/co-list.png', 'width' => 1280, 'height' => 800,
     'steps' => [[30, 11, 'New button', 'Starts an order.'], [60, 30, 'Filters', 'Narrow the list.']]],
]" />
```

## Related

- `x-dl::deck`: the same lesson that plays on its own.
- `x-dl::chapters`: a searchable illustrated guide with the same step lists.
- `x-dl::slideshow`: pictures that follow one another in a window.
- `x-dl::shot`: one enlarged part of a screenshot with a ring.
