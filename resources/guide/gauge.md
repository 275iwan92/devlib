## Overview

`<x-dl::gauge>` is a half-circle (180 degree) dial: a grey track, colored zones drawn from `bands`, an optional target tick, a needle pointing at `value`, and the value printed under the dial in the color of the zone it falls in. It is a static, server-rendered SVG with no JavaScript.

Use it for one number that must be judged against zones (OEE, capacity used, satisfaction score). Pick another object when:

- the number is a trend or a change against the last period: `x-dl::kpi` (delta, sparkline, target bar);
- the number is a plain progress percentage without zones: `x-dl::progress-ring`;
- you compare actual, target and ranges as a horizontal bar: `x-dl::bullet`;
- you only need a red / amber / green verdict with a sentence: `x-dl::traffic-light`;
- you need a clickable count: `x-dl::stat-box`.

### Generated markup

```html
<div class="dl-gauge" data-dl="x-dl::gauge" data-dl-class="Gauge ← DevlibComponent" data-dl-src="—">
  <svg viewBox="0 0 200 118" role="img" aria-label="OEE Press 78.5%">
    <path class="dl-gauge__track" d="M20,100 A80,80 0 0 1 180,100"/>
    <path class="dl-gauge__band dl-chart--danger" d="..."/>   <!-- one per band -->
    <line class="dl-gauge__target" .../>                       <!-- only with target -->
    <line class="dl-gauge__needle" .../><circle class="dl-gauge__hub" cx="100" cy="100" r="6"/>
    <text class="dl-gauge__mm" x="22" y="114">0</text><text class="dl-gauge__mm" x="178" y="114">100</text>
  </svg>
  <b class="dl-gauge__big is-warn">78.5%</b>
  <span class="dl-gauge__label">OEE Press<small> · target 85%</small></span>  <!-- only with label -->
</div>
```

The dial is centered at (100,100): track and bands use radius 80, the needle length 62, the target tick runs from radius 66 to 94. `min` is the left end, `max` the right end, the top is the middle.

## Props

Constructor of `Devlib\Components\Gauge` (7 props). The class has no parent constructor props. Blade names are kebab-case; use `:prop="expr"` for anything that is not a plain string.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `value` | `float`, `0` | The measured value. The needle position is clamped to `min`..`max` (a value of 120 on a 0..100 dial points at the right end), but the zone color and the printed number use the real value. A non-numeric string throws a `TypeError`. Not translated. |
| `min` | `float`, `0` | Value at the left end of the dial. Printed small under the left end. |
| `max` | `float`, `100` | Value at the right end of the dial. Printed small under the right end. `max` equal to `min` does not divide by zero (internal guard) but the dial is meaningless. |
| `bands` | `array`, `[[60,'danger'],[85,'warn'],[100,'success']]` | Zones as a list of `[upper limit, tone]` pairs, in ascending order of the limit. Each zone runs from the previous limit (the first from `min`) to its own limit (capped at `max`). Pairs must be positional 2-element arrays (they are destructured as `[$upto, $tone]`). A last limit below `max` leaves the rest of the track grey. `tone` is the CSS tone: `accent`, `success`, `warn`, `danger`, `info`, `violet`, `teal`, `neutral`. Pass `[]` for a plain dial with no zones (the number then uses tone `accent`). |
| `label` | `string`, `''` | Caption under the number. Translated (dictionary). Also the first part of the SVG `aria-label`. An empty label removes the caption line, and with it the visible `target` text. |
| `unit` | `string`, `'%'` | Text appended to the printed value, the target and the `aria-label` (`%`, `h`, `kg`). Pass `unit=""` for none. Not translated. |
| `target` | `?float`, `null` | Draws a dark tick across the track at this value (clamped to `min`..`max`) and, when `label` is set, adds ` · target <n><unit>` to the caption. `null` = nothing. A target of `0` is still drawn (the check is `!== null`). |

Every DevLib component also accepts `can="module.action"` (without the ability nothing is rendered) and extra HTML attributes (`id`, `class`, `style`, `data-*`), which are merged on the root `div.dl-gauge` (`class` is appended to `dl-gauge`).

## Behavior

### Zone color of the number

The number (`dl-gauge__big`) takes the tone of the first band whose limit is greater than or equal to the real `value` (boundary inclusive: with `[60,'danger']`, a value of exactly 60 is danger). A value above the last limit takes the tone of the last band; empty `bands` gives `accent`.

Text colors exist only for `success` (green), `warn` (amber), `danger` (red) and `accent`. Other tones (`info`, `violet`, `teal`, `neutral`) color the arc but leave the number in the default text color.

### Needle and target

Both are straight lines from fixed radii; the angle is linear in the value (`0` at `min`, 180 degrees at `max`). There is no animation or transition: a new value means a new page render.

### Sizes, theme, language

- Width is fixed at 220px (`.dl-gauge`), the SVG scales to it. There is no size prop; wrap in a container with your own CSS or `style` to change it.
- Colors come from CSS variables, so light and dark themes follow the site theme (the bands use the same `dl-chart--*` color tokens as the charts).
- The printed number uses the display locale (`Locale::decimal`, at most 1 decimal, no trailing zeros: `78,5` in Indonesian, `78.5` in English). The `aria-label` uses the plain PHP number (`78.5`) and is not locale-formatted.
- `label` is translated; `unit` is not.

### Accessibility as implemented

The `<svg>` has `role="img"` and `aria-label="<label> <value><unit>"`. The zones, target and min/max texts are not described. Color is the only carrier of the zone meaning, besides the printed number; if the verdict matters, show the sentence with `x-dl::traffic-light` or the `label`. There is no focus, keyboard or tooltip.

## Data and events

- Input is only props; there is no slot, no database access and `source()` is the default `—`.
- Nothing is posted, no `dl:*` event is emitted and no JavaScript class is attached (`jsClass()` is `null`). `el.dlComponent` is not used.
- Markup hooks for CSS or tests: `.dl-gauge`, `.dl-gauge__band`, `.dl-gauge__needle`, `.dl-gauge__target`, `.dl-gauge__big.is-<tone>`, `.dl-gauge__label`.
- Data for several gauges: loop in Blade and pass `:value` from the server. Put them in `<div class="dl-gauges">` (flex, wraps, gap 16px) for a row.

## Rules and pitfalls

- Keep `bands` ascending and ending at `max`. Unsorted limits draw wrong arcs and the tone lookup stops at the first match.
- For a "lower is better" metric put the green zone first: `:bands="[[70,'success'],[90,'warn'],[100,'danger']]"`.
- When the scale is not 0..100, set `min`, `max`, `unit` and the band limits together (for a 1..5 score: `:min="1" :max="5" unit="" :bands="[[3,'danger'],[4,'warn'],[5,'success']]"`).
- `value` is typed `float`: bind numbers with `:value`, never a locale-formatted string such as `"1.250,5"`.
- Data values inside `label` (a machine name from the database) must be wrapped in `raw()` so they are not translated or added to the dictionary.
- There is no lint rule for this object (`lint()` returns nothing).
- Code oddity (not fixed): the caption word `target` is hard-coded English in the view (`src/Components/Gauge.php:81`), not passed through the dictionary like `label` (compare `x-dl::kpi`, which translates `of target`).
- Code oddity (not fixed): the `aria-label` prints the raw number (`src/Components/Gauge.php:73`) while the visible number uses the locale (`:80`), so a screen reader reads `78.5` where an Indonesian page shows `78,5`.
- Code oddity (not fixed): the CSS class `.dl-gauge__val` (`resources/assets/devlib.css:1917`) is not used by the view any more; the number is `.dl-gauge__big`.

## Example

Copied in shape from the catalog demo `indicators` (fictional data).

```blade
<div class="dl-gauges">
    {{-- default bands (red under 60, amber to 85, green above) and a target tick --}}
    <x-dl::gauge label="OEE Press 1" :value="78.5" :target="85" />

    {{-- lower is better: green first --}}
    <x-dl::gauge label="Capacity used" :value="92" :bands="[[70, 'success'], [90, 'warn'], [100, 'danger']]" :target="85" />

    {{-- own scale 1..5, no unit --}}
    <x-dl::gauge label="Customer satisfaction" :value="4.3" :min="1" :max="5" unit="" :bands="[[3, 'danger'], [4, 'warn'], [5, 'success']]" />
</div>

{{-- permission-gated, data value protected from translation --}}
<x-dl::gauge can="report.view" :label="raw($machine->name)" :value="$machine->oee" :target="$machine->oee_target" />
```

## Related

- `x-dl::kpi`: card with change against the previous period, sparkline and target bar; use it for trends.
- `x-dl::progress-ring`: circular progress without zones.
- `x-dl::bullet`: horizontal actual / target / ranges.
- `x-dl::traffic-light`: red / amber / green verdict with a sentence.
- `x-dl::stat-box`, `x-dl::status-card`: plain or clickable counts and statuses.
- `x-dl::kpi-row`: wrapper row for KPI cards on top of a report.
- `skills/devlib/components.md`: generated prop list of every object.
