## Overview

`<x-dl::frame>` is the full-screen frame of DevLib for entry pages: a background picture (one image or a rotating set), a darkening veil, up to eight screen positions for widgets (brand, clock, date, greeting, weather, quote, announcement) and one content box (a panel on the left or right, a card in the center, a bar on top or bottom, or the whole screen). The slot goes into the box. It is the shell of the login, forgot-password and register pages (`x-dl::login-box`, `x-dl::forgot-box`, `x-dl::register-box`) and is meant to fill the window exactly: `width: 100%`, `height: 100vh` (`100dvh` where supported), `overflow: hidden`.

Use it only as the root of a standalone page. Pick another object when:

- the page is an application screen inside the shell/menu: use the layout of `x-dl::layer-shell` and put content in `x-dl::card` / `x-dl::form`;
- you need a titled box inside a page: `x-dl::card`;
- you need a modal dialog: `x-dl::modal`.

Generated markup (shortened):

```html
<div class="dl-frame dl-frame--box-right dl-frame--panel dl-frame--overlay-dark" data-dl="x-dl::frame box=right panel" data-dl-class="Frame ← DevlibComponent"
     data-dl-src="gambar: media/bg/devlib · teks: media/text/motivasi.txt"
     data-dl-frame data-images='[{"src":"/media/bg/devlib/a.jpg","portrait":"/media/bg/devlib/a-portrait.jpg"}]' data-order="random" data-interval="0" data-key="devlib"
     style="--dl-box-w:520px">                                  <!-- style only with box-width -->
  <div class="dl-frame__bg"><img alt="" data-layer="a"><img alt="" data-layer="b"></div>
  <div class="dl-frame__veil"></div>
  <div class="dl-frame__zone dl-frame__zone--top-left"> ...widgets at this position... </div>   <!-- one per used position -->
  <div class="dl-frame__box dl-frame__box--light" data-dl-frame-box>
    <div class="dl-frame__grip" title="Drag to widen" data-dl-frame-grip></div>              <!-- only with resizable and box left/right -->
    <div class="dl-frame__boxin"> ...slot... </div>
  </div>
</div>
```

Extra attributes (`id`, `class`, `data-*`) are merged onto the root. `can="module.action"` is accepted: without the ability the whole page content is not rendered.

## Props

Constructor of `Devlib\Components\Frame` (20 props). Blade names are kebab-case; numbers can be written as plain attributes (`bg-interval="8"`) or bound (`:lat="-7.25"`), but a non-numeric string for `bg-interval`, `quote-interval`, `lat` or `lon` throws a `TypeError` at render. Booleans are bare attributes (`resizable`).

### Background

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `bg` | `string`, `'devlib'` | A folder (or one file) under the media path: `media/bg/<bg>`. A folder gives every `*.jpg` / `*.jpeg` in it (sorted by name; other extensions are ignored; files with `-portrait.` in the name are not slides). A value such as `devlib/devlib-room.jpg` gives that one file. Each slide may have a portrait twin named `<name>-portrait.jpg` next to it, used when the screen is in portrait orientation. Also used as the key of the sequence counter (`data-key`). A folder that does not exist or is empty is not an error: `data-images` is `[]` and the page shows the navy gradient only. Set it in the project, never from user input. |
| `bg-order` | `string`, `'random'` | Which slide is shown first: `random` or `sequence` (next slide on every page load, remembered in `localStorage` key `dl-seq-bg-<bg>`). The shared helper also accepts `day` (slide by day of the year). Any other string behaves as random on load (see the oddity). |
| `bg-interval` | `int`, `0` | Seconds between slide changes while the page stays open. `0` = never change. Only active when there is more than one image. With `bg-order="random"` the next slide is random; any other order goes to the next in the list. Slides cross-fade over 0.9 s after the new image has loaded. |
| `overlay` | `string`, `'dark'` | The veil over the picture: `dark` (gradient from 55% dark navy to a lighter middle to 80% dark), `light` (white gradient) or `none` (no veil). The widget text color is always white, so use `light` only when the picture itself is dark enough. Another string adds a class with no style (no veil). |

### Content box

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `box` | `string`, `'right'` | Where the content box sits: `right` and `left` (full-height side panel), `center` (card in the middle, width `min(430px, 92vw)`, max height 92dvh), `top` / `bottom` (full-width bar, max height 60vh, content centered), `full` (whole screen). Widgets on the same side are moved away from a left/right panel; for the other values widgets are NOT moved and the box covers them. On screens 760px wide or narrower every box becomes full screen. An unknown value gives a box with no placement rule. |
| `box-style` | `?string`, `null` | `panel`, `card` or `glass`. `null` = `card` when `box="center"`, otherwise `panel`. `panel` has no extra style (flat). `card` adds a large radius and a modal-like shadow (a left/right card also gets `4vh` top/bottom and `3vw` side margins). `glass` makes the box 72% opaque with a 14px backdrop blur and a shadow (round corners when centered). |
| `color` | `string`, `'light'` | Box colors: `light` (page background and normal ink), `dark` (topbar color), `navy`, `accent`. They set the CSS variables `--dl-box-bg`, `--dl-box-ink`, `--dl-box-strong`, `--dl-box-muted` which login/forgot/register boxes read; buttons inside `dark`, `navy` and `accent` boxes get adapted link and primary colors. An unknown value leaves the box transparent. |
| `box-width` | `?string`, `null` | Width of a left/right panel as a CSS length (`520px`, `30vw`), written as `--dl-box-w` on the root. `null` = `clamp(260px, 15vw, 40vw)`, in practice 260px up to a 1733px wide window and about 288px at 1920px. The panel never goes under 260px. It also moves the widgets of that side. No effect for `center`, `top`, `bottom`, `full`. Not validated; do not bind user input. |
| `resizable` | `bool`, `false` | Adds a drag handle (`dl-frame__grip`, 8px, `col-resize`) on the inner edge of the panel. Only rendered when `box` is `left` or `right`. Mouse only (see Behavior). Hidden on screens 760px wide or narrower. |

### Widgets

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `widgets` | `string`, `''` | Comma-separated list `name@position`. Names: `brand`, `clock`, `date`, `greeting`, `weather`, `quote`, `announce` (`x-dl::brand`, `x-dl::clock`, `x-dl::clock mode="date"`, `x-dl::greeting`, `x-dl::weather`, `x-dl::quote`, `x-dl::announce`). Positions (`Frame::POSITIONS`): `top-left`, `top-center`, `top-right`, `bottom-left`, `bottom-center`, `bottom-right`, `middle-left`, `middle-right`. A missing `@position` or an unknown position means `top-left`. An unknown name is silently ignored (its zone `div` is still created, empty). Names are case-sensitive and trimmed. Several widgets at one position stack top to bottom with a 14px gap, in the order written. Empty = no widgets. |
| `quotes` | `string`, `'motivasi.txt'` | File in `media/text/` read by the `quote` widget. One line per quote, format `text \| author` (author optional); blank lines and lines starting with `#` are skipped. A missing file gives an empty list, and the quote widget hides itself. |
| `quote-order` | `string`, `'random'` | `random`, `sequence` (next on each page load, stored under `dl-seq-quote`) or `day` (line by day of the year, 366 lines). Passed to `x-dl::quote`. |
| `quote-interval` | `int`, `0` | Seconds between quote changes (the next line in the list). `0` = fixed. |
| `announce` | `string`, `'pengumuman.txt'` | File in `media/text/` read by the `announce` widget, one announcement per line, same comment rules as `quotes`. The rotation interval of `x-dl::announce` is not a prop of the frame (the widget default applies). |
| `city` | `string`, `'Surabaya'` | City name shown by the `weather` widget. Label only; the data comes from `lat` and `lon`. |
| `lat` | `float`, `-7.2575` | Latitude sent to the weather service by the browser. |
| `lon` | `float`, `112.7521` | Longitude sent to the weather service. The weather widget makes a request from the visitor's browser to `api.open-meteo.com` (no API key), so the page needs internet access for it. |

### Identity

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `brand` | `?string`, `null` | Application name in the `brand` widget. `null` = the site identity: container binding `devlib.brand`, else `config('devlib.brand.name')`, else `DEVLIB`. Used only when `widgets` contains `brand`. Not translated. |
| `tagline` | `?string`, `null` | Line under the name. `null` = identity tagline (`config('devlib.brand.tagline')`, else `developer library`). |
| `logo` | `?string`, `null` | URL of the logo image (for example `/media/brand/logo.svg`). `null` = identity logo (`config('devlib.brand.logo')`); with no logo the badge shows the default gradient. |
| `image-list` | `?array`, `null` | Background images supplied by the application instead of the folder `media/bg/<bg>`, for an application that keeps them in a database. A list of `['src' => url, 'portrait' => ?url]`. `null` = read the folder as before (`images()`). The order and the cross-fade still follow `bg-order` and `bg-interval`.
| `quote-list` | `?array`, `null` | Quotes for the `quote` widget supplied by the application instead of the file `media/text/<quotes>`. A list of `['t' => text, 'a' => author]`. `null` = read the file as before (`textLines()`). Not translated: the text is data.

## Behavior

### Background and portrait images

- The first slide is chosen once on load (`bg-order`). It is written into the back `<img>` and faded in when it has loaded; failure to load keeps the previous slide (or the gradient).
- When the window changes between landscape and portrait, the current slide is re-shown with its `-portrait` twin if one exists (media query `orientation: portrait`).
- Images are listed on the server at every render (`glob` in the media folder). Large uploads cost one directory scan per page view, not a database query.

### Layout of box and widgets

- A `right` panel sets `--dl-zone-right` to the panel width so the right-side widgets sit left of the panel; a `left` panel shifts the left-side widgets (`top-left`, `bottom-left`, `middle-left`) to the right of the panel. `*-center` widgets are never moved.
- With `center`, `top`, `bottom` or `full` the widgets keep their normal positions and the box (z-index above the widgets) covers any overlap. Put widgets where the box is not.
- A zone holds at most `min(620px, 70vw)` of width. The box scrolls vertically (`overflow-y: auto`) when the content is taller than the screen, so a long form (register) is usable.
- 760px and narrower: the box fills the screen (`inset: 0`, no radius, no margins, no transform), widgets and the grip are hidden. The picture is visible only through a `glass` box.

### Resizing the panel (`resizable`)

Dragging the grip (mouse down, move, up on the window) sets `--dl-box-w` on the root, clamped to at least `max(15% of window width, 260px)` and at most `40%` of the window width. While dragging the root has `is-resizing` (no text selection, `col-resize` cursor). The width is not saved; a reload returns to `box-width`. There is no touch handling, no keyboard control and no `aria-*` on the grip.

### Language

Only the grip tooltip (`Drag to widen`) is translated at render. Widgets translate and format themselves (clock, greeting, weather, quote) according to their own entries. File content (quotes, announcements), `brand`, `tagline` and `city` are shown as written.

### Accessibility as implemented

The pictures have `alt=""` (decorative). The frame adds no landmark roles; the box is a plain `div`. Widgets are text. Colors in the box come from the `color` variables, not from a contrast check: verify contrast of `dark`, `navy` and `accent` boxes with `glass`.

## Data and events

- Server: the frame posts nothing. A form inside the slot posts as normal (the login/register boxes contain their own `<form>`).
- Events: the component emits no `dl:*` event. The JS class `Frame` (`resources/assets/js/components/frame.js`, selector `[data-dl-frame]`) sets `el.dlComponent`; override with `data-dl-js` + `Devlib.register` if a project needs different behavior.
- Markup hooks: `data-dl-frame` (root), `data-images` (JSON list of `{src, portrait}`), `data-order`, `data-interval`, `data-key`, `data-dl-frame-box`, `data-dl-frame-grip`; classes `dl-frame--box-<box>`, `dl-frame--<box-style>`, `dl-frame--overlay-<overlay>`, `dl-frame__box--<color>`, `dl-frame__zone--<position>`; state `is-resizing` on the root and `is-on` on the visible background `<img>`.
- Public PHP helpers: `Frame::textLines(string $file)` returns `[['t' => text, 'a' => author], ...]` from a file in `media/text`; `images()` and `widgetMap()` feed the view.
- Configuration: `config('devlib.media_path')` (default `public_path('media')`), `config('devlib.media_url')` (default `/media`) and `config('devlib.brand')` (`name`, `tagline`, `logo`), or the container binding `devlib.brand` returning the same keys.

## Rules and pitfalls

- Put the frame at the root of a page with no other layout around it. It forces the window height and hides overflow; do not place it inside the application shell.
- Keep the picture folder small and sized for the screen (1920px wide JPEG). Only `.jpg` / `.jpeg` are picked up; PNG and WebP files in the folder are ignored without a message.
- Widgets are hidden below 760px, so anything essential (the product name, a warning) must also be inside the box.
- Choose widget positions away from the box when `box` is not `left` or `right`.
- The widget text is white on the veil. With `overlay="light"` or `none` over a bright picture the widgets lose contrast.
- Do not write your own `style` attribute on the frame (see the oddity); put page styling on a wrapper inside the slot.
- Do not bind request data to `bg`, `quotes`, `announce`, `box-width` or `widgets`; they are read from disk or written into attributes.
- The `weather` widget calls an external service from the browser; do not use it on pages that must work offline or must not contact third parties.
- `can` on the frame removes the entire page content, including the login box.
- No lint rule targets `x-dl::frame`. General `devlib:lint` rules L001 to L003 apply to the surrounding view (no raw `form`/`input`/`button` tags, no inline `<script>`, no hex colors).
- Code oddity (not fixed): `src/Components/Frame.php:25` documents `bg-order` as `random | sequence`, but the shared `pickIndex()` (`resources/assets/js/core.js:295-298`) also accepts `day`, and any other value silently behaves as random on load; `resources/assets/js/components/frame.js:15` then rotates sequentially for every value except the exact string `random`. A typo such as `bg-order="sequential"` gives a random first slide followed by an ordered rotation.
- Code oddity (not fixed): `resources/views/components/frame.blade.php:15` writes `style="--dl-box-w:..."` after the attribute bag; a caller-supplied `style` attribute makes a duplicate `style` and the browser keeps the first, so `box-width` is silently ignored (verified by rendering with `box-width` and `style` together).
- Code oddity (not fixed): `resources/assets/js/components/frame.js:15-16` and `:23`, `:28` register a `setInterval`, a `matchMedia` listener and window `mousemove`/`mouseup` listeners that are never removed; harmless on a full page load, but a frame mounted and discarded by a script keeps running.

## Example

From the catalog demo `site/resources/views/demos/login-1.blade.php` (panel on the right, resizable, three widgets):

```blade
<x-dl::frame bg="devlib" bg-order="random" box="right" color="light" resizable widgets="brand@top-left, clock@top-right, quote@bottom-left">
    <x-dl::login-box show="support, lang, title, username, password, forgot, button, register" :action="$action" :error="$error" :username="$username" />
</x-dl::frame>
```

Centered glass card with a rotating picture (`login-2.blade.php`):

```blade
<x-dl::frame bg="devlib" bg-order="sequence" bg-interval="8" box="center" box-style="glass" color="light" widgets="brand@top-left, weather@top-right, greeting@bottom-left, quote@bottom-left, date@bottom-right">
    <x-dl::login-box show="support, lang, brand, username, password, button" button="Masuk Aplikasi" :action="$action" :error="$error" :username="$username" />
</x-dl::frame>
```

Left navy panel and rotating quotes (`login-3.blade.php`), and a wide register panel (`register.blade.php`):

```blade
<x-dl::frame bg="devlib" box="left" color="navy" widgets="greeting@top-right, clock@top-right, weather@top-right, announce@bottom-right, quote@bottom-right" quote-interval="10">
    <x-dl::login-box show="lang, brand, title, email, password, remember, forgot, button" :action="$action" :error="$error" :username="$username" />
</x-dl::frame>

<x-dl::frame bg="devlib" bg-order="random" box="right" color="light" box-width="520px" widgets="brand@top-left, clock@top-right, quote@bottom-left">
    <x-dl::register-box :login-url="url('/lib/login-1')" />
</x-dl::frame>
```

## Related

- `x-dl::login-box`, `x-dl::forgot-box`, `x-dl::register-box`, `x-dl::login-card`: the content placed in the box slot.
- `x-dl::brand`, `x-dl::clock`, `x-dl::greeting`, `x-dl::weather`, `x-dl::quote`, `x-dl::announce`: the widgets the `widgets` list creates; their own entries describe formats and languages.
- `x-dl::device-frame`: a different object (laptop / tablet / smartphone preview frame for mockups), not the page frame.
- `x-dl::modal`: dialog over an existing page.
- `x-dl::card`: titled box for application screens.
- `skills/devlib/inheritance.md`: the login and security components are a reference for inheriting projects; `skills/devlib/examples/login.blade.php` is the project login example.
