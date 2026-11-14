## Overview

`<x-dl::wallpaper>` is a decorative glass-style background: a soft SVG scene (ribbons, bubbles, folded glass or orbs) over a gradient, with one short quote placed in a corner. The SVG is built on the server from `kind`, `palette` and `seed`; there is no script and no image file.

Use it as the backdrop of a greeting card, a sign-in panel or a dashboard header (later steps wire it into those objects; for now place it yourself). Do NOT use it as a data chart, as a photo, or as the page background of a form screen (it has a fixed aspect ratio and a quote on top).

### Markup

```html
<div class="dl-wallpaper dl-wallpaper--ribbon dl-wallpaper--sage" data-dl="x-dl::wallpaper" style="aspect-ratio:5/4;--wp-ink:#2c4a35">
  <svg viewBox="0 0 600 480" aria-hidden="true">...</svg>
  <q class="dl-wallpaper__quote dl-wallpaper__quote--bottom-left">Quote</q>   <!-- only with quote -->
</div>
```

Extra HTML attributes (`id`, `class`, `style`, `data-*`) are merged into the root; `can="module.action"` hides it without the ability.

## Props

Constructor of `Devlib\Components\Wallpaper` (8 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `kind` | `string`, `'ribbon'` | Scene shape: `ribbon`, `waves`, `bubbles`, `shards`, `ripples`, `blob`, `folds`, `orbs`, `loop`, `panels`, `soft`, `cards` (12). Anything else falls back to `ribbon`. `soft` draws embossed controls on a dotted ground (dark with `night`), `cards` three frosted cards with empty text bars (no words). Each kind has its own quote font (safe font stacks, no font files). |
| `palette` | `string`, `'sage'` | Colour set: `sage` (default), `sky`, `rose`, `mint`, `amber`, `violet`, `night` (dark, light text). Unknown falls back to `sage`. |
| `quote` | `?string`, `null` | The quote text. `null` or empty draws no quote. Escaped and printed as given (not translated: pass it already in the display language). Long text is clipped inside a safe area of 46% of the width. |
| `seed` | `?int`, `null` | Random seed. The same seed, kind and palette always give the same picture. `null` picks a new random seed on every render; pass a fixed number for a stable background. |
| `quote-position` | `?string`, `null` | `top-left`, `bottom-left`, `top-right`, `bottom-right`. `null` chooses one from the seed (random but stable with a fixed seed); for kinds with one main shape (`ribbon`, `waves`, `shards`, `ripples`, `blob`) the quote goes to the side opposite the shape, top or bottom by seed, so it avoids the shape; an unknown value is treated as `null`. |
| `static` | `bool`, `false` | `true` with no `seed`: the seed is derived from `kind` + `palette` + `quote`, so every render gives the same picture without you choosing a number. An explicit `seed` always wins. |
| `auto-quote` | `bool`, `false` | `true` with an empty `quote`: picks one of 12 built-in Indonesian quotes (`Wallpaper::QUOTES`) from the seed (stable with `seed` or `static`). A given `quote` always wins. |
| `ratio` | `string`, `'5/4'` | CSS aspect ratio `width/height` of the box (digits only, e.g. `16/9`); an invalid value falls back to `5/4`. The width fills the parent; the scene is sliced to fit. |

## Behavior

- Static: no script, no event, no state. The quote size follows the box width (container query, 10px to 24px).
- The scene is drawn in a 600x480 canvas and cropped (`slice`) to the ratio, so very wide ratios hide the top and bottom.
- `ribbon`, `waves`, `bubbles`, `shards`, `ripples` and `blob` stand on a water line with a faded reflection; the other kinds fill the whole box. The quote is limited to 4 lines and 36% of the height, so small tiles clip it instead of overflowing.
- Accessibility: the SVG is `aria-hidden`; the quote is plain text in a `q` element. Text colour comes from the palette (`--wp-ink`) and is chosen for contrast with that palette's background.

## Data and events

- Reads and posts nothing. No `dl:*` event and no `el.dlComponent`.

| Hook | Where |
|---|---|
| `.dl-wallpaper--<kind>`, `.dl-wallpaper--<palette>` | root modifiers. |
| `.dl-wallpaper__quote--<position>` | the quote, one class per corner. |
| `--wp-ink` | CSS variable on the root: quote colour. |

## Rules and pitfalls

- Give a fixed `seed` when the picture must not change between page loads (for example a saved login background); without it every render is different.
- Keep quotes short (about 60 characters); longer text is clipped, not wrapped beyond the safe area.
- The quote is not translated; pass text already in the display language.
- Do not draw controls on top without checking contrast; the `night` palette is dark, the others are light.
- Opt-in object: nothing else in DevLib uses it yet. Wiring into greeting card, login and dashboard, and the module pack entry, come in a later step.
- Lint: no own rule.

## Example

```blade
<x-dl::wallpaper kind="ribbon" palette="sage" :seed="7" quote="Mulai dari yang kecil, selesaikan dengan baik." />

<x-dl::wallpaper kind="orbs" palette="sky" :seed="3" quote-position="top-right" ratio="16/9" quote="Satu langkah hari ini cukup." />
```

## Related

- `x-dl::button` with `variant="glass"`: the glass button that sits well on top of this background.
- `x-dl::greeting-card`, `x-dl::login`: planned places to use it as a backdrop (later step).
- `skills/devlib/components.md`: generated prop list.
