## Overview

`<x-dl::blueprint>` is a dark CAD-style drawing canvas: an SVG with a minor and a major grid, wheel zoom, drag pan, a crosshair that follows the pointer, a coordinate HUD, zoom buttons (`+`, `-`, `Fit`) and optional dimension lines with automatic length text. The units of the drawing are "world units" (millimetres by default). The slot is the drawing itself, written as plain SVG elements.

Use it to show a technical drawing that the user must inspect and measure with the mouse (a layout, a die-cut, a floor plan). It is display only: it is not an editor, posts nothing, and reads no data by itself.

Do NOT use it for charts (use the chart objects), for a printed drawing (use the print objects), or for a diagram that needs no zoom (plain SVG in a `x-dl::box`).

There is no catalog demo for this object; the example below is written from the view contract.

### Markup

```html
<div class="dl-bp" data-dl-blueprint data-w="400" data-h="300" data-unit="mm" style="height:420px" data-dl="x-dl::blueprint" data-dl-js="Blueprint" ...>
  <svg viewBox="-20 -20 440 340" preserveAspectRatio="xMidYMid meet" data-dl-bp-svg>
    <defs> pattern minor, pattern major, arrow marker (ids bp<6 hex>m / j / a) </defs>
    <g data-layer="grid"> two 4000 x 4000 rects filled with the patterns </g>
    <g data-layer="cut"> ...slot... </g>
    <g data-layer="dim"> line + text per entry of dims </g>
    <g data-dl-bp-cross class="dl-bp__cross" hidden> horizontal (data-h) and vertical (data-v) line </g>
  </svg>
  <div class="dl-bp__hud" data-dl-bp-hud>X 0.0 Y 0.0 · 100%</div>
  <div class="dl-bp__tools"><button data-dl-bp-zin>+</button><button data-dl-bp-zout>-</button><button data-dl-bp-fit>Fit</button></div>
</div>
```

Extra HTML attributes (`id`, `class`, `style`, `data-*`) are merged into the root `div`; `can="module.action"` hides the object without the ability.

## Props

Constructor of `Devlib\Components\Blueprint` (7 props). Use `:prop="expr"` for numbers and arrays.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `width` | `float`, `400` | Width of the drawing area in world units. Written to `data-w`, and the SVG `viewBox` is `-20 -20 (width + 40) (height + 40)`, so the area has a 20-unit margin on every side. Must be greater than 0. |
| `height` | `float`, `300` | Height of the drawing area in world units (`data-h`). Not the CSS height: for that see `height-css`. |
| `minor` | `float`, `10` | Spacing of the minor grid lines in world units. Must be greater than 0 (0 gives an invalid grid pattern). Drawn with `.dl-bp__minor`. |
| `major` | `float`, `50` | Spacing of the major grid lines, drawn over the minor ones with `.dl-bp__major`. Normally a multiple of `minor`. |
| `unit` | `string`, `'mm'` | Unit text. Written to `data-unit`; appended to the computed length of every dimension line and shown in the HUD while the pointer moves. It does not scale anything. Not translated. |
| `dims` | `array`, `[]` | Dimension lines: a list of `[x1, y1, x2, y2]` or `[x1, y1, x2, y2, 'text']`. Each entry draws a line with an arrow at both ends (`.dl-bp__dim`) and a label in the middle (`.dl-bp__label`, centred on the line, 2 units above its middle). Without the fifth element the text is the line length `hypot(x2-x1, y2-y1)`, formatted by `Locale::decimal` with at most 1 decimal and followed by a space and `unit`. A fifth element is shown as given (no unit added, not translated). Coordinates are world units, the same as the slot. |
| `height-css` | `string`, `'420px'` | CSS height of the whole component, written to the root `style="height: ..."`. Any CSS length (`70vh`, `500px`). The width is always the width of the parent. |

The default slot is the drawing: SVG elements in world coordinates (origin at the top-left of the area, Y grows downwards).

## Behavior

### Layers

Drawing order, bottom to top: `grid` (two rects covering -2000 to +2000 in both axes, filled with the patterns), `cut` (the slot), `dim` (the dimension lines), and the crosshair. Dimension lines are therefore drawn over the slot.

Classes the slot should use (all set `vector-effect: non-scaling-stroke`, so the stroke keeps its pixel width when zoomed):

| Class | Look | Typical use |
|---|---|---|
| `dl-bp__cut` | solid blue line, no fill | outline / cut line |
| `dl-bp__crease` | green dashed line | fold / crease |
| `dl-bp__perf` | orange dotted line | perforation |

Colours come from the `--bp-*` custom properties declared on `.dl-bp` (background, minor, major, line, dimension, crosshair); use these classes instead of colours in the SVG.

### Zoom, pan, crosshair, HUD

Script `Blueprint` (`resources/assets/js/components/blueprint.js`, selector `[data-dl-blueprint]`, bound through `data-dl-js="Blueprint"`). The view is the SVG `viewBox`; zoom and pan only rewrite that attribute.

- Wheel over the canvas: zoom around the pointer, factor 0.8 (in) or 1.25 (out) per step. The wheel handler calls `preventDefault`, so the page does not scroll while the pointer is over the canvas.
- Buttons: `+` zooms in and `-` zooms out around the centre of the current view; `Fit` restores the initial view (the `-20 -20 w+40 h+40` box).
- Drag with the primary pointer (mouse, pen or one finger; the root has `touch-action: none`) pans. There is no pinch gesture and no zoom limit: the user can zoom far out or in, and can pan the drawing out of sight (use `Fit`).
- The crosshair (two lines in the crosshair colour) follows the pointer while it is over the canvas, and is hidden on `pointerleave`.
- The HUD (bottom left) is rewritten on every pointer move as `X <x> Y <y> <unit> · <percent>%`, where x and y are world coordinates with one decimal (always with a dot, not locale-formatted) and percent is the initial view width divided by the current view width (100% = fit). Before the first pointer move it shows the static text `X 0.0 Y 0.0 · 100%` (no unit).
- The tool buttons show the fixed texts `+`, `-` and `Fit`.

### Size and responsiveness

The component is as wide as its parent and `height-css` high. The SVG uses `preserveAspectRatio="xMidYMid meet"`, so the whole drawing area is shown centred and keeps its proportions in any box.

### Locale and language

Only the automatic dimension text is locale-aware (decimal separator of the page language, at most 1 decimal). The HUD uses a dot. No text of the object goes through the dictionary.

### Accessibility as implemented

- The tool controls are native `<button type="button">` elements (Tab, Enter, Space) but have no `aria-label` (their text is `+`, `-`, `Fit`).
- The drawing is mouse/pointer driven: the SVG is not focusable, has no `role` or `aria-label`, and the HUD is not a live region. There is no keyboard zoom or pan other than the three buttons. Give the drawing a text alternative next to it if screen-reader users need the content.

## Data and events

- No data in or out. Nothing is posted (no input, no `name`).
- No `dl:*` custom event. Events are native `wheel`, `pointerdown`, `pointermove`, `pointerup`, `pointerleave` on the SVG and `click` on the three buttons.
- JS API: `el.dlComponent` on the root is the `Blueprint` instance: `vb` (current viewBox as `[x, y, w, h]`), `home` (the fit viewBox), `unit`, `apply()` (writes `vb` to the SVG), `zoomAt(event|null, factor)` (null = centre), `world(event)` (pointer to world point). To change the behaviour use `data-dl-js="MyBlueprint"` with `Devlib.register(class MyBlueprint extends Devlib.classes.Blueprint {...})`.

| Hook | Where |
|---|---|
| `data-dl-blueprint`, `data-w`, `data-h`, `data-unit` | root and its configuration. |
| `data-dl-bp-svg` | the SVG. |
| `data-layer` | the three groups, with values `grid`, `cut` (the slot) and `dim`. |
| `data-dl-bp-cross` (with `data-h`, `data-v` lines) | crosshair. |
| `data-dl-bp-hud` | HUD text. |
| `data-dl-bp-zin`, `data-dl-bp-zout`, `data-dl-bp-fit` | tool buttons. |

## Rules and pitfalls

- Keep the drawing inside `0..width` and `0..height`, or enlarge `width`/`height`. The grid and the crosshair lines only exist between -2000 and +2000 world units, so a drawing larger than that has no grid beyond it.
- Keep `minor` and `major` greater than 0 and `major` a multiple of `minor`, otherwise the two grids drift apart visually.
- Dimension text size is fixed in world units (`font: 4px monospace` in `.dl-bp__label`), so it scales with the zoom and is tiny on a very large drawing (thousands of units) and huge on a very small one. Pick `unit`, `width` and `height` so that the drawing is of the order of hundreds of units, or override the label size in the project theme.
- The `dims` coordinates are not clipped to the drawing area; a dimension line placed outside `0..width` or `0..height` is only visible when it is inside the 20-unit margin or after the user pans or zooms out.
- Use the `dl-bp__*` classes in the slot; do not write hex colours (lint L003) or `<script>` (L002) in the screen. Other raw HTML tags flagged by L001 (`table`, `button`, `input`, `select`, `textarea`, `form`) are not needed inside the slot. The object has no own lint rule.
- A custom label in `dims` replaces the number and the unit; write the unit yourself (`'Height 160 mm'`). It is not translated.
- Several blueprints on one page work: each gets its own pattern ids (`bp` plus 6 hex characters, suffix `m`, `j`, `a`).
- Code oddity (not fixed): the class header of `src/Components/Blueprint.php` and the comment at `resources/assets/devlib.css:3454` refer to a guide called `devlib-svg-cad`, which does not exist in this repository (`skills/devlib` has no such file). This entry is the only written guide.

## Example

Written from the view contract (no catalog demo). A flat box layout 260 x 160 mm with a fold line and a perforation, and two dimension lines (the first uses the automatic text, the second a custom text).

```blade
<x-dl::blueprint :width="260" :height="160" :minor="10" :major="50" unit="mm" height-css="420px"
    :dims="[[0, 172, 260, 172], [272, 0, 272, 160, 'Height 160 mm']]">
    <rect class="dl-bp__cut" x="0" y="0" width="260" height="160" />
    <line class="dl-bp__crease" x1="130" y1="0" x2="130" y2="160" />
    <line class="dl-bp__perf" x1="0" y1="80" x2="260" y2="80" />
</x-dl::blueprint>
```

## Related

- `x-dl::device-frame`: the other preview-type object (screen preview at three widths).
- `x-dl::box`: a plain wrapper if the SVG needs no zoom.
- `skills/devlib/components.md`: generated prop list.
