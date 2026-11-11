## Overview

`<x-dl::slideshow>` is a modal window that shows pictures one at a time, each with a caption: Previous and Next buttons, a row of dots, and the left and right arrow keys. It is made for step-by-step guides that are screenshots of a screen. It opens like `x-dl::modal`: any element with `data-dl-open="<id>"` opens it, and it always starts at slide 1.

Pick another object when you need all pictures visible at once (`x-dl::image-gallery`), a modal with a form or text (`x-dl::modal`), or a picture inside a form field (`x-dl::field.*`).

### Generated markup

```html
<div class="dl-mov dl-slides" data-dl="x-dl::slideshow id=emp-guide" data-dl-modal-id="emp-guide" data-dl-slideshow data-items='[{"src":"/guide/emp/01.png","caption":"Press Deactivate"}]' role="dialog" aria-modal="true" aria-label="Guide">
  <div class="dl-modal dl-modal--xl">
    <div class="dl-modal__head"><h3>Guide</h3><span class="dl-slides__n" data-sl-n></span><button class="dl-modal__x" data-dl-close-modal>&times;</button></div>
    <div class="dl-modal__body"><figure class="dl-slides__fig"><img data-sl-img alt=""><figcaption data-sl-cap></figcaption></figure></div>
    <div class="dl-modal__foot dl-slides__foot">[Previous] <span class="dl-slides__dots" data-sl-dots></span> [Next]</div>
  </div>
</div>
```

Extra attributes (`class`, `id`, `data-*`) go on the root; `can="module.action"` works as for every component.

## Props

Constructor of `Devlib\Components\Slideshow` (7 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `id` | `string`, `'slideshow'` | Modal id; the opener uses `data-dl-open="<id>"`. |
| `title` | `?string`, `null` | Title in the modal head (translated through the dictionary). Empty = "Guide". |
| `items` | `array`, `[]` | The slides: `[['src' => '/guide/a/01.png', 'caption' => 'Press Deactivate', 'alt' => '...'], ...]`. `alt` empty = the caption. An empty array shows an empty window. |
| `size` | `string`, `'xl'` | Modal width class: `sm`, `md`, `lg` or `xl`. |
| `open` | `bool`, `false` | Open when the page loads (rare; normally a button opens it). |
| `more-url` | `?string`, `null` | Address of the full written guide. When set, a link button (book icon) in the window head, always visible, opens it in a new tab; empty = no link. |
| `more-label` | `string`, `'Full guide'` | Text of that link (translated through the dictionary). |

## Behavior

- Opening (by `data-dl-open` or `open`) resets to slide 1. The picture is loaded when its slide is shown, not when the page loads.
- Previous is disabled on the first slide and Next on the last; the counter in the head shows `3 / 11`.
- Keyboard: left and right arrow keys move between slides while the window is open; Esc and a click outside close it (the library modal rules).
- Each dot jumps to its slide; the current dot is accent coloured.
- With `moreUrl` the link sits in the window head on every slide and opens in a new tab (`target="_blank"`).

## Data and events

- Browser only: the picture requests. No events, no `el.dlComponent`; the slide list is the `data-items` JSON written by the server.

## Rules and pitfalls

- Keep the pictures under the project's `public/` (a slide address must be reachable by the browser), and keep them free of real data.
- One caption per slide, short, in the language of the screen.
- Related lint rules: none.

## Example

```blade
<x-dl::button label="Guide" icon="fa-circle-play" data-dl-open="emp-guide" />
<x-dl::slideshow id="emp-guide" title="Deactivate and delete an employee"
    :items="[['src' => '/guide/emp/01.png', 'caption' => 'Press Deactivate'], ['src' => '/guide/emp/02.png', 'caption' => 'Choose a reason']]" />
```

## Related

- `x-dl::modal`: the window this object is built on (same open and close rules).
- `x-dl::image-gallery`: all pictures at once, each opening in a new tab.
