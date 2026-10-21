## Overview

`<x-dl::media>` renders one media element from a URL: an image (`<img>`), a document frame (`<iframe>`) or a video (`<video controls>`). It exists so that screens do not write raw media tags; it adds almost nothing of its own (no wrapper, no sizing logic) and passes every extra attribute and class to the element, so the look comes from the classes you pass (for example a page class that sizes a flow map or a file preview).

Use it for a picture or a file preview that comes from a URL the screen already has (a flow map, the source file of a supplier quotation). Pick another object when:

- the user picks or uploads a file or photo: `x-dl::field.upload` or `x-dl::photo-box`;
- the image is a grid cell: the grid `image` column type (with the lightbox);
- you need a logo or brand mark: `x-dl::brand`;
- you need a QR code or barcode: `x-dl::qr` or `x-dl::barcode`;
- you need a chart: `x-dl::chart`.

Generated markup (one element, selected by `as`):

```html
<img   {attributes} src="<src>" alt="<alt>">                                           <!-- as="img" (default) and any unknown value -->
<iframe {attributes} src="<src>" title="<alt>"></iframe>                               <!-- as="frame" -->
<video class="dl-media-video" {attributes} src="<src>" title="<alt>" controls preload="metadata"></video>   <!-- as="video" -->
```

There is no root wrapper and no `data-dl` hook on the element. `can="module.action"` is read by the base class: without the ability nothing is rendered.

## Props

Constructor of `Devlib\Components\Media` (3 props). All other attributes you write on the tag (`class`, `style`, `width`, `height`, `loading="lazy"`, `id`, `data-*`, ...) go to the element as they are.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `src` | `string`, `''` | URL written to the `src` attribute (HTML-escaped, nothing more). An empty value renders `src=""`: the browser shows a broken image or reloads the page in a frame, so do not render the tag when there is no file. DevLib does not check the scheme, host or existence of the URL. |
| `alt` | `string`, `''` | For `img`: the `alt` text (empty means decorative to screen readers). For `frame` and `video`: the `title` attribute (the accessible name of the frame / player). Not translated by the dictionary (it is not a standard text prop): pass the final text. Use `raw()` for data. |
| `as` | `string`, `'img'` | `img` (default), `frame` or `video`. Any other value renders an `<img>`. `video` adds the class `dl-media-video` (block, `width:100%`, `max-width:960px`, border and radius), the `controls` attribute and `preload="metadata"`; these cannot be switched off with props. |

## Behavior

- Pure markup: no JavaScript is attached and no event is emitted. Loading, errors, caching, autoplay and fullscreen are the browser's own. Extra attributes such as `loading="lazy"`, `autoplay muted` or `allowfullscreen` are passed through.
- Size and layout: `img` and `iframe` have no DevLib class and no size, so they show at their natural size (an iframe at the browser default 300x150) until you pass a class or `style`. Give it the page class that sets width/height (for example a preview class in the project CSS). Only `video` has a default style.
- The element is not wrapped, so surrounding layout (`x-dl::form.row`, cards, boxes) controls where it sits.
- Language: nothing is translated; `alt` is the only text.
- Accessibility: image `alt` and frame/video `title` come only from `alt`. Always write a meaningful `alt` for content images; the video player uses the browser's native controls (keyboard operable).

## Data and events

- Input is a URL; the component never fetches or stores anything. Typical sources: a file URL stored for the document (an attachment, a quotation source file), a generated map URL, or `asset('...')`.
- Output is the element only; nothing is posted to the server.
- No `dl:*` event and no `dlComponent` instance. To react to loading, add your own listener on the element by `id`/`data-*` in project JS.

## Rules and pitfalls

- Never put a user-controlled URL in `src` without checking it on the server. The value is only HTML-escaped: a `javascript:` URL in an `iframe` would run, and an `iframe` has no `sandbox` unless you pass `sandbox` as an attribute. Allow only your own storage URLs or `https` hosts you trust.
- Do not render it when there is no file: wrap in `@if ($url)` or show `x-dl::empty-state`.
- A frame shows a PDF only if the server sends it inline (not `Content-Disposition: attachment`) and the site does not forbid framing (`X-Frame-Options`).
- Give images an explicit size by class, `width`/`height` attributes or `style`, to avoid layout jumps; add `loading="lazy"` for long pages.
- Hand-written `<img>`, `<iframe>` and `<video>` are the practice this object replaces; the lint rules do not flag them (L001 only checks `table`, `button`, `input`, `select`, `textarea`, `form`), so the convention must be kept in review.
- Code oddity (not fixed): for `as="video"` the view writes `class="dl-media-video"` before `{{ $attributes }}` (`src/Components/Media.php:32`), so a `class` you pass becomes a second `class` attribute on the same tag and the browser ignores it. For `img` and `frame` the passed class works because there is no built-in class. Put your sizing in `style` or on a wrapper when `as="video"`.
- Code oddity (not fixed): the view never calls `$dl()` (`src/Components/Media.php:30-38`), so the element has no `data-dl` / `data-dl-class` / `data-dl-src` attributes, unlike other objects, and the developer catalog overlay cannot point at it.

## Example

No catalog demo uses this object in this repository; the example follows the view contract (fictional data).

```blade
{{-- image with a page class from the project CSS --}}
<x-dl::media class="po-flowmap" src="/files/flow/purchase-order.svg" alt="Purchase Order flow map" />

{{-- document frame: preview of the source file of a supplier quotation --}}
@if ($quotation->source_url)
    <x-dl::media as="frame" class="pq-file-preview" :src="$quotation->source_url" alt="Source file of quotation QT-0001" />
@endif

{{-- video with player controls --}}
<x-dl::media as="video" src="/files/training/receiving.mp4" alt="Goods receiving training" />
```

## Related

- `x-dl::box`: neutral wrapper when the media needs a container with a page class.
- `x-dl::field.upload`, `x-dl::photo-box`: where a file or photo is chosen and sent to the server.
- `x-dl::empty-state`: what to show when there is no file.
- `x-dl::modal`: a place to show a larger preview on demand.
