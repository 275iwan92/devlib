## Overview

`<x-dl::progress-ring>` is a circular progress indicator: a ring that is filled clockwise from the top according to a percentage, with the rounded percentage printed in the centre, and an optional label (and smaller caption) under it. Its colour is a fixed `tone` or is chosen automatically from the value.

Use it for "how much of the goal is done" figures that deserve a visual weight of their own, usually several side by side in a `dl-gauges` row: deliveries sent, stock opname done, budget used. It is a catalog object of the `indicators` page.

Pick another object when:

- a thin bar fits better in a line, a table cell or a card: `x-dl::progress`;
- the figure has a scale with bands, a target mark or a min / max: `x-dl::gauge`;
- the figure is a headline number with trend and sparkline: `x-dl::kpi`;
- a target is compared with ranges: `x-dl::bullet`.

### Generated markup

```html
<div class="dl-ring" data-dl="x-dl::progress-ring" data-dl-class="ProgressRing ← DevlibComponent" data-dl-src="—" style="--s:96px">
  <svg viewBox="0 0 100 100">
    <circle cx="50" cy="50" r="40" class="dl-ring__track"/>
    <circle cx="50" cy="50" r="40" class="dl-ring__bar dl-chart--success" stroke-dasharray="231.22 251.33" transform="rotate(-90 50 50)"/>
    <text x="50" y="55" text-anchor="middle" class="dl-ring__val">92%</text>
  </svg>
  <span class="dl-ring__label">Plan Delivery<small>46 dari 50 terkirim</small></span>   <!-- only with label; small only with caption -->
</div>
```

The filled arc is `stroke-dasharray = "<filled> <circumference>"` on a circle of radius 40 (circumference 251.33), starting at 12 o'clock.

## Props

Constructor of `Devlib\Components\ProgressRing` (5 props; nothing is inherited from `DevlibComponent`).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `value` | `float`, `0` | Percent done. The arc uses the value clamped to 0 to 100. The centre text is `round(value)` followed by a literal `%` and is NOT clamped (`value=120` draws a full ring and prints `120%`). Also drives the automatic tone. Pass a number or a numeric string; `null` and non-numeric text raise a PHP `TypeError`. |
| `label` | `string`, `''` | Bold text under the ring. Empty string (default) prints no label block, and then `caption` is not shown either. Translated through the dictionary (`label` is a standard text prop); wrap data values in `raw()`. Use `''` not `null` to omit (the type is non-nullable). |
| `tone` | `?string`, `null` | Arc colour, class `dl-chart--<tone>`. `null` (default) = automatic: `>= 85` `success`, `>= 60` `warn`, otherwise `danger` (on the raw value). A string is used as-is. Defined tones: `accent`, `success`, `warn`, `danger`, `violet`, `info`, `neutral`, `teal`. The word `auto` is NOT special here (leave `tone` out instead). Not translated. |
| `size` | `int`, `96` | Width and height of the ring in px (CSS variable `--s` on the root). The centre text scales with it, because it lives inside the SVG viewBox. Does not include the label block under it. |
| `caption` | `?string`, `null` | Smaller muted line under the label (`<small>`). Shown only when `label` is not empty. Translated (`caption` is a standard text prop); wrap data in `raw()`. |

Extra HTML attributes (`id`, `class`, `data-*`) are merged onto the root `div`; `class` is appended to `dl-ring`. `can="module.action"` is supported by the base class `DevlibComponent`: without the ability nothing is rendered. Do not pass `style` (see pitfalls).

## Behavior

- Look: the track is a 10-unit stroke in the chip colour, the bar a 10-unit round-capped stroke in the tone colour, the centre value is 20-unit bold text in the strong ink colour. The root is `inline-flex`, column, centred, 4 px gap, so rings sit side by side inside a flex or grid row (`dl-gauges` on the catalog page) and wrap with it.
- Value text and arc are two separate calculations: the text is rounded to a whole number, the arc keeps the fraction (`92.4` draws 92.4% of the circle and prints `92%`).
- Tone thresholds apply to the raw value, so `84.6` prints `85%` but is `warn`.
- No animation or transition: the arc is drawn at its final length on load. A script that changes `stroke-dasharray` jumps; there is no CSS transition on the ring.
- Theme and dark mode: colours come from theme variables (`--dl-chip`, `--dl-ink-strong`, tone variables), so light / dark and themes are followed. The label font follows the user font scale; the SVG text scales with `size`, not with the font scale.
- Language: `label` and `caption` are translated. The percent sign is hard-coded and the number is not formatted by locale (whole numbers only, so there are no separators to localise).
- Accessibility as implemented: an SVG with no `role`, `aria-*` or `<title>`. Screen readers get the SVG text (`92%`) and the label span, with no statement that it is a progress value. Add `role="img"` and `aria-label` through extra attributes when the screen must be accessible.

## Data and events

- Input is only the five props; there is no slot.
- Nothing is posted and no `dl:*` event is emitted. There is no JS twin and `el.dlComponent` is not set.
- Hooks: `.dl-ring`, `.dl-ring__track`, `.dl-ring__bar`, `.dl-ring__val`, `.dl-ring__label`, tone class `dl-chart--<tone>` on the bar. The size is the CSS variable `--s` on the root.

## Rules and pitfalls

- Give a percent 0 to 100, computed by the screen (`$done / $total * 100`, guard the zero total). A fraction (`0.92`) draws 0.92%.
- Leave `tone` out for the standard red / amber / green, set it only when the colour has a meaning of its own (a budget ring in `accent`). Do not write `tone="auto"`: it produces class `dl-chart--auto`, which no CSS defines, so the arc is left without a colour variable.
- The automatic thresholds are 85 / 60 here and 80 / 50 in `x-dl::progress` (`tone="auto"`); they mean "higher is better". For "low is good" figures (reject rate) set the tone yourself.
- The label block is outside `size`: the object is taller than `size` when `label` is set; reserve room in the row.
- A data value inside `label` or `caption` goes in `raw()` so it is not translated and not added to the dictionary.
- Do not use a ring for many items in a table: use `x-dl::progress` in the cell.
- No lint rule covers this object.
- Code oddity (not fixed): `src/Components/ProgressRing.php:46` writes `style="--s:...px"` after the merged attributes. A caller-supplied `style` becomes a second `style` attribute on the same element, and the browser keeps the first one, so `--s` (the size) is lost.
- Code oddity (not fixed): `src/Components/ProgressRing.php:48` prints the unclamped `round($value)` while the arc (`dash()`, line 35) is clamped, so out-of-range values show a text that disagrees with the drawing.
- Code oddity (not fixed): the same "good colour" rule has different thresholds in neighbouring objects: ring 85 / 60 (`ProgressRing::autoTone`, line 40), progress bar 80 / 50 (`Progress.php:34`), grid `progress` column 100 / 60 / 30 (`resources/assets/js/components/grid.js:278`).

## Example

Copied in shape from the catalog (`indicators`):

```blade
<div class="dl-gauges">
    <x-dl::progress-ring label="Plan Delivery" caption="46 dari 50 terkirim" :value="92" />
    <x-dl::progress-ring label="Stock Opname" caption="Gudang Bahan Baku" :value="64" />
    <x-dl::progress-ring label="Anggaran Terpakai" caption="Q3 2026" :value="38" tone="accent" />
</div>
```

Value computed by the screen, smaller ring without label:

```blade
<x-dl::progress-ring :value="$total ? $done / $total * 100 : 0" :size="64" />
```

Data in the label, fixed tone:

```blade
<x-dl::progress-ring :label="raw($warehouse->name)" caption="Opname" :value="$pct" tone="violet" />
```

## Related

- `x-dl::progress`: the bar version for lines and table cells; its `tone="auto"` has different thresholds.
- `x-dl::gauge`: dial with bands, target and a min / max scale.
- `x-dl::kpi`, `x-dl::bullet`: headline number with trend, and target vs ranges.
- `x-dl::chart`: shares the `dl-chart--<tone>` colour classes.
- `x-dl::spinner`: when progress cannot be measured.
- `skills/devlib/components.md`: generated prop list.
