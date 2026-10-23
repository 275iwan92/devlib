## Overview

`<x-dl::image-gallery>` is a grid of small pictures with a caption under each and an optional title and size note above. A tap opens the picture at full size in a new tab. It is made for reference screenshots of an object (for example the states of the phone menu), kept as small image files in the project's `public/` folder.

Pick another object when you need a picture inside a form field (`x-dl::field.*`), a thumbnail with a lightbox in a grid (grid column type `image`), or a slideshow (there is none).

### Generated markup

```html
<figure class="dl-gallery" data-dl="x-dl::image-gallery" style="--dl-gal-min:150px">
  <figcaption class="dl-gallery__head"><b>Visual reference</b> <span>Tested at 360 x 790</span></figcaption>
  <div class="dl-gallery__grid">
    <a class="dl-gallery__item" href="/ref/phone/phone-menu-A.png" target="_blank" rel="noopener"><img src="/ref/phone/phone-menu-A.png" alt="State A" loading="lazy"><span>State A</span></a>
  </div>
</figure>
```

Extra attributes (`class`, `id`, `data-*`) go on the root `figure`; `can="module.action"` works as for every component.

## Props

Constructor of `Devlib\Components\ImageGallery` (4 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `items` | `array`, `[]` | The pictures: `[['src' => '/ref/a.png', 'caption' => 'State A', 'alt' => '...'], ...]`. `src` is used for both the picture and the link to full size; `alt` empty = the caption. An item without `caption` shows no text. An empty array shows an empty grid. |
| `title` | `string`, `''` | Bold title above the grid (translated through the dictionary). Empty = no title. |
| `note` | `string`, `''` | Small text next to the title, for example the test sizes. Empty = none. |
| `columns` | `int`, `150` | Minimum width of one picture card in px (never below 80); the grid fits as many columns as the width allows. |

## Behavior

- No JavaScript: the grid is CSS only (`grid-template-columns: repeat(auto-fill, minmax(...))`) and pictures load lazily.
- The link opens the file in a new tab (`rel="noopener"`).

## Data and events

- No events, no `el.dlComponent`. Browser to server: only the picture requests and the navigation to the file.

## Rules and pitfalls

- Keep the files small (WebP or PNG, about 360 px wide is enough for a thumbnail) and put them under the project's `public/`.
- A reference picture must not show real data or names: use made-up sample data only.
- Related lint rules: none.

## Example

```blade
<x-dl::image-gallery title="Visual reference" note="Tested at 360 x 790"
    :items="[['src' => '/ref/phone/phone-menu-A.png', 'caption' => 'State A'], ['src' => '/ref/phone/phone-menu-B.png', 'caption' => 'State B']]" />
```

## Related

- `x-dl::device-frame`: shows the live screen the pictures were taken from.
- `x-dl::phone-shell`: the object whose states the gallery documents on the catalog page `p7-phone`.
