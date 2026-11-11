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

Constructor of `Devlib\Components\Shot` (9 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `src` | `string`, `''` | Url of the full screenshot. |
| `x`, `y` | `float`, `50` | The point, in percent of the picture width and height. |
| `zoom` | `float`, `2.2` | Enlargement around the point. `1` shows the whole picture. |
| `ring` | `bool`, `true` | Show the pulsing ring on the point. |
| `ratio` | `string`, `'16/10'` | Aspect ratio of the frame. |
| `anim` | `string`, `''` | Url of a looping animation (WebP or GIF) that is already framed. When set it is shown instead of the zoom and the ring (`x`, `y`, `zoom` and `ring` are then ignored). People who ask for reduced motion get the static `src` instead, and if the animation fails to load `src` is used too. |
| `alt` | `string`, `''` | Alternative text. Empty = decoration (hidden from screen readers). |
| `source` | `?string`, `null` | Data source text for the Developer mode label. |

## Behavior

- Pure CSS, no script. The picture is lazy loaded.
- The card or step that contains the shot may enlarge it a little more on hover.
- With reduced motion the ring does not pulse and the picture does not animate.

## Data and events

Data in: only the props. No request, no script, no custom event and no `el.dlComponent`. The root is `div.dl-shot` with the CSS variable `--ratio`; with `anim` the markup is a `<picture>` whose reduced-motion `<source>` is `src`.

## Rules and pitfalls

- `src` is always needed, also with `anim`: it is the fallback for reduced motion and for a failed load.
- Give `x` and `y` in percent (0-100) of the full picture, not pixels; the ring and the zoom share the same point.
- Without `alt` the picture is hidden from screen readers; the text of the step must carry the meaning.
- Keep the screenshots under `public/` and free of real data.
- Do not use hex colors or `<script>` in the screen around it: lint rules L001, L002 and L003 apply. There is no Shot-specific lint rule.

## Example

```blade
<x-dl::shot src="/guide/home.png" :x="30" :y="11" :zoom="2.2" alt="The New button" />
<x-dl::shot src="/guide/home.png" anim="/guide/home.webp" ratio="16/9" />
```

## Related

- `x-dl::chapters`: uses this object for every card and step.
- `x-dl::deck`: slides with `shot` and `walk` pictures.
- `x-dl::tour`: the reader clicks the parts of the picture.
- `x-dl::slideshow`: pictures that follow one another in a window.
