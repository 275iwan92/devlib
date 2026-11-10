## Overview

`<x-dl::deck>` is a slide presentation that lives in the page: one slide is visible, its items appear one after another, and Play moves to the next slide by itself every few seconds, like a short video. A progress bar shows the time left on the slide.

Use it to present a guide in 5 to 12 short slides. Pick another object when:

- the slides are screenshots opened in a window: `x-dl::slideshow`;
- you need all pictures visible at once: `x-dl::image-gallery`;
- the text is a long article: `x-dl::feed` or `x-dl::review-feed`.

Generated markup:

```html
<section class="dl-deck" data-dl="x-dl::deck" data-dl-deck data-interval="7" data-autoplay="0" tabindex="0">
  <div class="dl-deck__stage">
    <article class="dl-deck__slide is-on"><h2>Title</h2><p class="dl-deck__lead">Lead</p><ol class="dl-deck__items"><li style="--i: 0">Item</li></ol></article>
  </div>
  <div class="dl-deck__bar">[Previous] [Play] [Next] <span class="dl-deck__prog"><i></i></span> <span class="dl-deck__n">1 / 2</span> [Full screen]</div>
</section>
```

## Props

Constructor of `Devlib\Components\Deck` (5 props). Use `:slides="$slides"` (array expression).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `slides` | `array`, `[]` | `[['title', 'lead' (optional sentence), 'items' => [text, ...] (appear in order), 'image' => url (optional), 'tag' => small text above the title, 'hero' => true (opening slide: gradient and floating orbs), 'flow' => true (items become a row of numbered boxes), 'shot' => ['src', 'x', 'y'] (screenshot in a browser frame that zooms slowly from the point; 'kb' => 'out' zooms out instead), 'walk' => ['src', 'steps' => [[x%, y%, title, text], ...]] (a camera that walks through the steps on a screenshot)], ...]`. Text is data: it is not translated. Empty = the text "No slides." |
| `title` | `?string`, `null` | The accessible name of the deck. Empty = "Presentation". |
| `autoplay` | `bool`, `false` | `true` = starts playing when the page opens. |
| `interval` | `int`, `7` | Seconds per slide while playing (minimum 2). |
| `source` | `?string`, `null` | Data source text for the Developer mode label. |

## Behavior

- Previous / Next (arrow keys when the deck has focus), Play / Pause (space), Full screen (the browser full screen of the deck).
- Play on the last slide starts again from slide 1; when playing reaches the last slide it stops and the bar stays full.
- Going to a slide by hand while playing restarts the timer of that slide.
- Slide change uses a short slide-and-fade; the items of the visible slide appear one by one.
- Language: only the button labels are translated (Previous, Play, Pause, Next, Full screen, Presentation, No slides.).

## Picture slides

- `shot`: the slide becomes two columns, text on the left and the screenshot in a browser frame on the right. While the slide is visible the picture zooms slowly (in by default, out with `'kb' => 'out'`).
- `walk`: the camera starts on the whole picture, then every 3.4 seconds zooms to the next step, moves the ring and shows the step title and text under the picture, and repeats. While playing, the slide stays long enough for all steps (3.4 seconds per step plus 1.5 seconds).
- `hero` can be combined with `shot`. `flow` needs `items`.
- With reduced motion the zoom, the orbs and the ring do not animate.
