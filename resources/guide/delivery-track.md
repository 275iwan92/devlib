## Overview

`<x-dl::delivery-track>` is a schematic delivery route (an SVG, no map, no GPS): a warehouse dot on the left, a customer dot on the right, a curved road between them, and a small truck that drives along the road and stops at the point that belongs to the STAGE of the delivery document. Under the route a four-step bar (Draft, Confirmed, Shipped, Delivered) shows which steps are done, which is current, and the time of each step when the project gives it. Two extra stages show a problem: `cancelled` (the route stops at the warehouse) and `incident` (an accident on the way, the truck blinks with a warning sign).

The object knows nothing about a database or a delivery table. The project maps its own document status to one of the stage keys and passes it as `stage`. The truck position is a FIXED point per stage, unless `position` is given.

Use it when:

- a delivery order (or any shipment document) should show "where is it" in a friendly way on its form or dashboard, driven only by the document status;
- you want a small animated stage indicator that replays when scrolled into view.

Do NOT use it when:

- you need real vehicle tracking on a map (this object has no coordinates and no GPS);
- the flow has other steps than the four above: use `x-dl::flow` (process diagram), `x-dl::approval` (approval steps) or `x-dl::status-card` (a status card);
- you only need a progress bar: use `x-dl::progress` or `x-dl::progress-ring`.

### Stages

| `stage` | Truck stands at (`position` 0 = warehouse, 1 = customer) | Step bar | Colour token |
|---|---|---|---|
| `draft` (default) | 0.0 (truck dimmed) | Draft is current | amber |
| `confirmed` | 0.0 (truck pulses) | Draft done, Confirmed current | orange |
| `shipped` | 0.55, road shows moving dashes | Draft and Confirmed done, Shipped current | teal |
| `delivered` | 1.0, customer dot filled green | all four done | green |
| `cancelled` | 0.0 | all four pending, plus a `Cancelled` line | red |
| `incident` | 0.45, truck replaced by a blinking warning sign | like `shipped`, plus a line `Accident on the way` | red |

### Markup produced

```html
<div class="dl-track dl-track--shipped" data-dl="x-dl::delivery-track" data-dl-class="DeliveryTrack ← DevlibComponent" data-dl-src="..." data-dl-js="DeliveryTrack"
     data-dl-track data-target="0.55" data-moving="1">
  <div class="dl-track__head"><b>Title</b> <span>Subtitle</span> <button class="dl-iconbtn" data-dl-track-replay title="Replay"></button></div>
  <svg viewBox="0 0 600 170" role="img" aria-label="Warehouse → Customer: Shipped">
    <path class="dl-track__road" data-dl-track-road d="M60 120 C160 120 170 45 300 62 S450 140 540 52"/>   <!-- road, trail and moving dashes use the same path -->
    <path class="dl-track__trail" data-dl-track-trail d="..."/> <path class="dl-track__flow" d="..."/>
    <g class="dl-track__node dl-track__node--from"> circle + from text </g>   <g class="dl-track__node dl-track__node--to"> circle + to text </g>
    <g class="dl-track__truck" data-dl-track-truck> circle, body, x, warn paths </g>
  </svg>
  <ol class="dl-track__steps"><li class="is-done|is-current|is-pending"><i></i><b>Draft</b><small>07-Oct-2026</small></li> ... 4 items ...</ol>
  <div class="dl-track__cancel">...</div>   <!-- only for cancelled / incident -->
</div>
```

The root class is `dl-track--<stage>`; the colour token `--t` is set per stage (see table). The block is at most 640px wide; the steps are a 4-column grid.

## Props

Constructor of `Devlib\Components\DeliveryTrack` (7 props). Blade names are kebab-case; use `:prop="expr"` for floats and arrays.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `stage` | `string`, `'draft'` | Stage key: `draft`, `confirmed`, `shipped`, `delivered`, `cancelled`, `incident`. Decides the root class, the truck point, the step states, the colour and the banner. Any other value is silently replaced by `draft`. Map the document status in the project (for example Draft, Confirmed, Shipped, Delivered, Cancelled, Accident). |
| `from` | `string`, `'Warehouse'` | Caption under the left (start) dot and the first part of the SVG `aria-label`. Not translated (it is data when it names a place). |
| `to` | `string`, `'Customer'` | Caption above the right (end) dot and the second part of the `aria-label`. Not translated. Typically the customer name. |
| `position` | `?float`, `null` | Truck position along the road, 0 (warehouse) to 1 (customer); values outside are clamped. `null` = the fixed point of the stage (table above). Use it to show partial progress when the project knows it (for example 0.7). It does not change the stage, steps or colour. |
| `times` | `array`, `[]` | `stage => time text` shown under the step label, for the four step keys `draft`, `confirmed`, `shipped`, `delivered`. Values are strings that the caller has already formatted for the user, wrapped in `raw()` so they are not translated; keys not given show nothing; keys `cancelled` / `incident` are ignored. |
| `title` | `string`, `''` | Bold heading above the route. Empty = none. Translated through the dictionary. |
| `subtitle` | `string`, `''` | Grey text beside the title. Empty = none. Translated. |

Common attribute: `can="module.action"`; without the ability nothing is rendered. Extra HTML attributes (`class`, `style`, `id`, `data-*`) are merged onto the root `div.dl-track`.

Interactions: `position` overrides only the truck point; `stage` still drives everything else. `shipped` is the only stage that animates the moving dashes on the road (`data-moving="1"`).

## Behavior

### Animation (needs JavaScript)

- When at least 60% of the block is visible (`IntersectionObserver`, threshold 0.6) the truck drives from the warehouse to its point in 1.4 s times the distance (at least 0.3 of that), easing in and out, and a coloured trail follows it. When the block leaves the screen it is reset to the warehouse, so it plays again each time it appears. Without `IntersectionObserver` it plays once at load.
- The `Replay` button (icon button in the header) plays the animation again from the start at any time.
- The animation uses Anime.js (bundled in the package, `resources/assets/js/vendor/anime.umd.min.js`, global `anime`). If `anime` is missing, or the user prefers reduced motion (`prefers-reduced-motion: reduce`), the truck is placed at its point at once, without motion and without the moving dashes.
- The markup has no starting position for the truck: before the script runs it sits at the top-left corner of the picture.

### Stage rules for the step bar

- `draft`: Draft current, others pending. `confirmed`: Draft done, Confirmed current. `shipped` and `incident`: Draft and Confirmed done, Shipped current. `delivered`: all done. `cancelled`: all pending.
- The state is a CSS class (`is-done`, `is-current`, `is-pending`) drawn as a bar above each label; there is no text or `aria-current` for it.

### Screen text, language and rights

- Translated: `title`, `subtitle` and the `Replay` caption (through the dictionary). The step names, the two banner lines (`Cancelled`, `Accident on the way`) and the defaults `Warehouse` / `Customer` are written in English in the view and are NOT passed through the dictionary (see the oddities).
- `times` and `to` are data: format dates and names in the project's locale before passing them.
- Access: `can="module.action"` on the tag.

### Accessibility as implemented

- The SVG has `role="img"` and `aria-label="<from> → <to>: <stage label>"` (English stage label). The steps are an ordered list.
- The step state and the stage colour are visual only. The `Replay` button is a native button with `title` and `aria-label`.
- Motion respects `prefers-reduced-motion`.

## Data and events

### What the server receives

Nothing. The object has no input and sends no request.

### Events

The object emits no event. It listens for one, to update the truck without reloading the page:

```js
document.querySelector('.dl-track').dispatchEvent(new CustomEvent('dl:track', { detail: { target: 1, moving: false } }));
```

`detail.target` (0..1) is the new truck point and `detail.moving` (bool) switches the moving dashes. The truck animates from where it stands to the new point. Only the truck and the dashes change: the step bar, the colour, the stage class and the banner are rendered by the server for the stage, so after a real status change re-render the object (or reload).

### Markup hooks and JS

| Hook | Where |
|---|---|
| `data-dl-track`, `data-target`, `data-moving` | root and its start values; the class `DeliveryTrack` in `resources/assets/js/components/delivery-track.js` mounts on it. |
| `data-dl-track-road`, `data-dl-track-trail`, `data-dl-track-truck` | the road path (used to measure the length), the coloured trail, the truck. |
| `data-dl-track-replay` | the Replay button. |

The root carries `data-dl-js="DeliveryTrack"` (the PHP class returns `jsClass() = 'DeliveryTrack'`). `el.dlComponent` is the instance (methods `play()`, `rewind()`, `go(target, moving)`, `place(p)`). To change behaviour, register a subclass: `Devlib.register(class MyTrack extends Devlib.classes.DeliveryTrack {...})` and make the PHP side return that name.

## Rules and pitfalls

- Map the document status to a stage key in the project; do not add stage keys here. Keep the mapping in one data class so every screen shows the same stage.
- The route is schematic. Never present it as a live position; say so in the subtitle (the demo does: "position by stage, no GPS").
- `position` is only a visual hint; keep the real quantity (delivered vs ordered) in the document, not in the picture.
- Pass `times` already formatted and wrapped in `raw()`; do not pass `DateTime` objects.
- Put a `title` or the document number next to the object when several are on one page, so users know which delivery each route belongs to.
- Printing: the truck position is set by script (and reset to the warehouse when the block is off screen), so a printed page can show the truck at the start; do not rely on the object for printed documents.
- Code oddity (not fixed): in `src/Components/DeliveryTrack.php` (line 109) a stray literal `` `n `` (a PowerShell line-break escape) sits between `@endif` and the next `@if`, outside any directive, so the text `` `n `` is printed inside `div.dl-track` after the step bar in every stage. Found by reading the code, not checked in a browser.
- Code oddity (not fixed): the banner `div.dl-track__cancel` and the cross path `.dl-track__x` have no rule in `resources/assets/devlib.css`. The class header says "cancelled = route stops, red", but the `Cancelled` / `Accident on the way` line is unstyled text, and the cross inside the truck has no stroke or fill area, so it is not visible; the cancelled truck is the normal truck, only red.
- Code oddity (not fixed): `DeliveryTrack::source()` returns a text that names tables and a class of an application (`status Delivery Order (do_delivery.status + do_delivery_pod) lewat App\Support\V2\Data\DeliveryTrack`), and the class header comment repeats the class name. The object should be neutral (no application names); this text is shown in the developer catalog as the data source. A `source` prop like other objects would fix it.
- Code oddity (not fixed): the class header lists the stages as `draft -> confirmed -> shipped -> delivered; cancelled`, but the code also supports `incident` (constant `AT`, `stageLabel()`, banner, CSS); `STAGES` (the four step keys) does not include `cancelled` or `incident`.
- Code oddity (not fixed): the step labels (`Draft`, `Confirmed`, `Shipped`, `Delivered`), the banner lines and the defaults of `from` / `to` are printed or returned in English without `Lang::text`, unlike `title` and `subtitle`, so the object stays English when the page language is Indonesian unless the project translates them itself.

## Example

From the catalog demo (`site/resources/views/demos/delivery-track.blade.php`):

```blade
<x-dl::delivery-track stage="draft" title="Draft" subtitle="Truck waits at the warehouse" :times="['draft' => raw('07-Oct-2026')]" />

<x-dl::delivery-track stage="shipped" title="Shipped" subtitle="On the way (position by stage, no GPS)" to="Customer A"
    :times="['draft' => raw('05-Oct-2026'), 'shipped' => raw('07-Oct-2026')]" />

<x-dl::delivery-track stage="delivered" title="Delivered" subtitle="Proof of delivery received" to="Customer A"
    :times="['draft' => raw('05-Oct-2026'), 'shipped' => raw('07-Oct-2026'), 'delivered' => raw('07-Oct-2026')]" />

<x-dl::delivery-track stage="incident" title="Accident" subtitle="Stopped on the route, recorded in Proof of Delivery" to="Customer A"
    :times="['draft' => raw('05-Oct-2026'), 'shipped' => raw('07-Oct-2026')]" />

<x-dl::delivery-track stage="cancelled" title="Cancelled" subtitle="Route stops at the warehouse" />
```

On a delivery order form, with the stage mapped by the project and a measured position:

```blade
<x-dl::delivery-track :stage="$do->trackStage()" :title="raw($do->number)" :to="raw($do->customerName)" :position="$do->progress" :times="$do->stepTimes()" />
```

## Related

- `x-dl::flow`: a process diagram of blocks (use it to explain a flow between documents, not one document's status).
- `x-dl::approval`: approval steps of a document.
- `x-dl::status-card`: a status card with badge, value and time.
- `x-dl::progress`, `x-dl::progress-ring`: simple progress indicators.
- `x-dl::icon-button`: the Replay button in the header.
- `x-dl::geo-clock`: the other object that uses position, for GPS attendance (not shipments).
- `x-dl::timer`: a running clock for operator work.
