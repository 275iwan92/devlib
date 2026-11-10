## Overview

`<x-dl::shot>` shows one part of a screenshot: the full picture is enlarged around a point you choose and a pulsing ring marks that point. The point stays where it is while the picture grows, so the ring always sits on the right button.

Use it to illustrate one step of a guide. Pick another object when:

- the reader should click the parts themselves: `x-dl::tour`;
- all steps of a task should read as an article: `x-dl::chapters` (it uses this object for every card and step);
- the pictures follow one another in a window: `x-dl::slideshow`.

Generated markup:

```html
<div class="dl-shot" data-dl="x-dl::shot" style="--ratio: 16/10" aria-hidden="true">
  <img src="/images/home.png" alt="" style="transform-origin: 30% 11%; --z: 2.2">
  <i class="dl-shot__ring" style="left: 30%; top: 11%"></i>
</div>
```

## Props

Constructor of `Devlib\Components\Shot` (8 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `src` | `string`, `''` | Url of the full screenshot. |
| `x`, `y` | `float`, `50` | The point, in percent of the picture width and height. |
| `zoom` | `float`, `2.2` | Enlargement around the point. `1` shows the whole picture. |
| `ring` | `bool`, `true` | Show the pulsing ring on the point. |
| `ratio` | `string`, `'16/10'` | Aspect ratio of the frame. |
| `alt` | `string`, `''` | Alternative text. Empty = decoration (hidden from screen readers). |
| `source` | `?string`, `null` | Data source text for the Developer mode label. |

## Behavior

- Pure CSS, no script. The picture is lazy loaded.
- The card or step that contains the shot may enlarge it a little more on hover.
- With reduced motion the ring does not pulse and the picture does not animate.
