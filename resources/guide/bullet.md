## Overview

`<x-dl::bullet>` is a bullet chart (after Stephen Few): one horizontal row that compares an actual value with a target against up to three qualitative ranges (poor / fair / good). Each row has a title on the left, the track in the middle (grey range bands, a thin value bar, a red target tick) and the numbers on the right (`value`, then `target ... · NN%`).

Use it for KPI rows where "actual versus target with bands" matters (sales, output, margin). Do NOT use it:

- for a single percentage: `x-dl::progress` (bar) or `x-dl::progress-ring`.
- for a single meter with coloured bands and a needle: `x-dl::gauge`.
- for a KPI card with trend and sparkline: `x-dl::kpi`.
- for time series or many categories: `x-dl::chart`.

Generated markup (illustrative numbers for `value` 7.4e9, `target` 8e9, `ranges` [5e9, 7e9, 9e9], format money, English):

```html
<div class="dl-bullet" data-dl="x-dl::bullet" data-dl-class="Bullet ← DevlibComponent" data-dl-src="—">
  <div class="dl-bullet__label"><b>Sales Q3</b><small>vs target</small></div>      <!-- <small> only when sub is set -->
  <div class="dl-bullet__track" title="Value IDR 7.4B · target IDR 8B">
    <span class="dl-bullet__range is-r3" style="width:95.24%"></span>             <!-- one per range, widest first -->
    <span class="dl-bullet__range is-r2" style="width:74.07%"></span>
    <span class="dl-bullet__range is-r1" style="width:52.91%"></span>
    <span class="dl-bullet__bar is-good" style="width:78.31%"></span>              <!-- is-good when value >= target, else is-bad -->
    <span class="dl-bullet__target" style="left:84.66%"></span>
  </div>
  <div class="dl-bullet__nums"><b>IDR 7.4B</b><small>target IDR 8B · 93%</small></div>
</div>
```

Extra HTML attributes (`class`, `id`, `style`, `data-*`) and `can="module.action"` go on the root `div`; without the ability nothing is rendered.

## Props

Constructor of `Devlib\Components\Bullet`: 7 props, none inherited. Non-string values must be bound with `:` (`:value="620"`, `:ranges="[400, 550, 700]"`).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `label` | `string`, `''` | Bold title at the left (right-aligned on wide screens). Translated through the dictionary (standard `label` text prop); wrap data values in `raw()`. |
| `value` | `float`, `0` | The actual value: the thin bar. `bar` is green (`is-good`) when `value >= target`, otherwise dark ink (`is-bad`). Must be numeric (`null` raises a `TypeError`). |
| `target` | `float`, `0` | The target: the red 3 px tick, and the denominator of the percentage in the numbers (`value / target * 100`, shown as `0%` when `target` is 0). With `target` 0 the bar is always `is-good`. |
| `ranges` | `array`, `[]` | Upper limits of the qualitative bands, in ASCENDING order, e.g. `[400, 550, 700]` (poor up to 400, fair up to 550, good up to 700). One grey band per entry, drawn widest first so the smaller ones sit on top (`is-r1` = darkest = first/lowest, `is-r2`, `is-r3` = lightest). Only `is-r1..is-r3` have a style, so use at most three entries. Empty = no bands, just the plain track. Unsorted values give wrong stacking. |
| `max` | `float`, `0` | Scale end (100% of the track). `0` (default) = automatic: `max(value, target, every range, 1) * 1.05`. Set it for a fixed scale (`:max="100"` for percentages). Values above `max` are clamped to 100%, negatives to 0%. |
| `format` | `string`, `'number'` | How numbers are written: `money` prefixes `IDR ` (hard-coded), `percent` appends `%`, anything else (including `number`) plain. All use the compact style of `Slider::compact`: language-aware abbreviations (`en`: K / M / B / T; `id`: rb / jt / M / T) with one decimal, else the plain number with the locale separators. |
| `sub` | `?string`, `null` | Small grey line under the title (unit or comparison, e.g. `vs target`, `%`). Not translated by the dictionary (not a standard text prop). Falsy (`null`, `''`, `'0'`) hides it. |

## Behavior

- Layout: a 3-column grid `170px 1fr 150px` (title, track, numbers) with 12 px gap and 6 px vertical padding; at widths up to 640 px it becomes one column and the title aligns left. The track is 26 px high with a 4 px radius and `overflow: hidden`.
- Scale: every position is `round(clamp(v / top * 100, 0, 100), 2)` per cent of the track, where `top` is `max` or the automatic value described above. So the target tick, bar and bands are comparable across rows only when rows share the same `max`.
- Colour: bands use `--dl-disabled` mixes at 34% / 22% / 12%; bar `--dl-green` (good) or `--dl-ink-strong` (below target); target `--dl-danger`. All are theme tokens; the bands become lighter from `is-r1` to `is-r3`.
- Numbers: left `<b>` = value, right `<small>` = `target <target> · <pct>%` (percentage with 0 decimals in the page locale, e.g. `93%`; may exceed 100%). The word `target` is English text hard-coded in the view.
- Tooltip: the track has a native `title` (`Value <value> · target <target>`, where `Value` is translated).
- Accessibility as implemented: no `role`, no `aria-*`, no hidden text; the bars are empty spans, so the information for assistive technology is only the visible numbers plus the `title`. Colour alone (green / ink) marks reached or not; the percentage text carries the same meaning.
- No states, no keyboard, no animation, no JavaScript. Permissions: `can="module.action"`.

## Data and events

- Data in: scalars and one flat array of numbers (`ranges`). No slot, no model. Nothing is posted; no `dl:*` events; `el.dlComponent` is not defined.
- Markup hooks: `dl-bullet`, `dl-bullet__label`, `dl-bullet__track`, `dl-bullet__range.is-r1..3`, `dl-bullet__bar.is-good|is-bad`, `dl-bullet__target`, `dl-bullet__nums`. Widths and the target position are inline `style` percentages, so a project script can update them without a re-render.
- Public PHP methods used by the view: `top()` (scale end), `pct($v)` (0-100 position), `show($v)` (formatted number).

## Rules and pitfalls

- Give `ranges` in ascending order and no more than three entries; pass `max` when several rows must share one scale.
- `format="money"` always writes `IDR`; for another currency write the unit in `sub` and use `format="number"`.
- `value` and `target` are plain numbers; do not pass localized strings (`"1.250,5"`). Convert to float on the server first.
- `label` is translated; data names must be wrapped in `raw()`. `sub` is not translated.
- Lower-is-better metrics are not supported: the bar is green only when `value >= target` (for a reject rate, invert or use `x-dl::kpi` with `good-when="down"`).
- Code oddity (not fixed): `src/Components/Bullet.php:55` and `:60` hard-code the word `target` in English (title and numbers), while `Value` in the same title goes through `Lang::text()`; in an Indonesian page the numbers read `target 8 M`.
- Code oddity (not fixed): `src/Components/Bullet.php:47` hard-codes `IDR ` for `format="money"`; the REMARK and `skills/devlib/components.md:91` do not mention it.

## Example

Shapes copied from the catalog demo `indicators`:

```blade
<x-dl::bullet label="Penjualan Q3" sub="vs target" format="money" :value="7.4e9" :target="8e9" :ranges="[5e9, 7e9, 9e9]" />
<x-dl::bullet label="Produksi (ribu lbr)" sub="vs rencana" :value="620" :target="600" :ranges="[400, 550, 700]" />
<x-dl::bullet label="Margin Kotor" sub="%" format="percent" :value="24" :target="27" :ranges="[15, 22, 32]" />
```

Fixed scale shared by several rows, and a data-driven label:

```blade
@foreach ($branches as $b)
    <x-dl::bullet :label="raw($b->name)" format="percent" :value="$b->fill_rate" :target="90" :ranges="[60, 80, 100]" :max="100" />
@endforeach
```

## Related

- `x-dl::progress`, `x-dl::progress-ring`: a single percentage.
- `x-dl::gauge`: one meter with coloured bands and a target mark.
- `x-dl::kpi`: card with trend, sparkline and `good-when`.
- `x-dl::chart`: real charts.
- `x-dl::field.slider`: provides the compact number format (`Slider::compact`) used here.
- `skills/devlib/components.md` (generated prop list).
