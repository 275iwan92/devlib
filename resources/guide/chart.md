## Overview

`<x-dl::chart>` draws a chart as inline SVG without any external library. One data format serves eleven chart types: the screen passes `labels` and `series`, picks a `type`, and the script `chart.js` draws the SVG, a hover tooltip and a clickable legend. Colours come from theme tokens, so charts follow light and dark themes. The chart is redrawn when its container changes width.

Types (`type`): `bar`, `hbar`, `stacked`, `line`, `area`, `combo`, `pie`, `donut`, `funnel`, `heatmap`, `radar`.

Use it for trends, comparisons, composition and rankings on dashboards and report screens. Do NOT use it for:

| Need | Better object |
|---|---|
| A single number with trend or target | `x-dl::kpi-row`, `x-dl::gauge`, `x-dl::bullet` |
| Exact figures to read, sort or export | `x-dl::grid`, `x-dl::table` |
| Statement lines (balance sheet, profit and loss) | `x-dl::statement` |
| Paper output | `x-dl::report` (the chart is on screen only) |
| Charts with negative values, two value axes, time axes or zoom | not supported by this object |

### Markup produced

```html
<figure class="dl-chart" data-dl="x-dl::chart type=bar" data-dl-class="Chart ← DevlibComponent" data-dl-src="data laporan"
        data-dl-chart data-type="bar" data-format="money" data-labels='["Jan","Feb"]' data-series='[{"name":"Sales","data":[1,2]}]' style="--dl-ch-h:280px">
  <figcaption class="dl-chart__head"><b>Title</b><small>Subtitle</small></figcaption>   <!-- only with title -->
  <div class="dl-chart__plot" data-ch-plot></div>                                     <!-- the script writes the <svg> here -->
  <div class="dl-chart__legend" data-ch-legend></div>                                 <!-- only with legend -->
  <div class="dl-chart__tip" data-ch-tip hidden></div>
</figure>
```

Extra HTML attributes (`id`, `class`, `style`, `data-*`) and `can="module.action"` go on the root `figure`; without the ability nothing is rendered. The tag shown in the catalog is `x-dl::chart type=<type>`.

## Props

Constructor of `Devlib\Components\Chart` (9 props). Use `:prop="expr"` for arrays and booleans (`:labels="[...]"`, `:legend="false"`).

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `type` | `string` | `'bar'` | One of the eleven types above. Not validated: an unknown value falls through to the cartesian drawer, which draws axes and x labels but no bars or lines. |
| `labels` | `array` | `[]` | Category labels as a list of strings: x axis of `bar`, `stacked`, `line`, `area`, `combo`; rows of `hbar`; slices of `pie`, `donut`, `funnel`; columns of `heatmap`; axes of `radar`. Not translated (data). |
| `series` | `array` | `[]` | List of series, see [Series shape](#series-shape). |
| `title` | `?string` | `null` | Bold title above the chart (`figcaption`). Translated through the dictionary (standard text prop). Without `title` there is no caption block (and no `subtitle`). It is also the `aria-label` of the SVG; without it the label is the dictionary word `Chart`. |
| `subtitle` | `?string` | `null` | Small grey line under the title. Translated. Shown only together with `title`. |
| `format` | `string` | `'number'` | How values are written on axes, labels and tooltips: `number`, `money` (prefix `IDR ` is hard-coded in the script), `percent` (suffix `%`). Any other string behaves like `number`. All three use the compact notation described below. |
| `height` | `string` | `'280px'` | Any CSS length for the plot area, written as `--dl-ch-h` on the root (`height="150px"`, `height="20rem"`). The SVG is drawn in the real pixel size of the plot (minimum 280 x 180 units), so text keeps its size when the chart is resized. |
| `legend` | `bool` | `true` | `false` removes the legend container. Pie, donut, funnel and heatmap never fill the legend container anyway (pie and donut draw their own legend inside the SVG). |
| `source` | `?string` | `null` | Free text naming where the data comes from, shown in the catalog / Manifest (`data-dl-src`). Default `data laporan`. No visual or runtime effect. |

### Series shape

`series` is a list of arrays:

| Key | Meaning |
|---|---|
| `name` | Series name: legend text, tooltip, and for `heatmap` the row label. Not translated. |
| `data` | List of numbers, one per label (same order as `labels`). A missing or non-numeric value is read as 0. |
| `tone` | Colour: `accent`, `success`, `warn`, `danger`, `violet`, `info`, `neutral`, `teal`. Default: the tones in this order by series index (`accent`, `success`, `warn`, `danger`, `violet`, `info`, `neutral`, `teal`, then again). Another value produces the class `dl-chart--<tone>` without a defined colour. |
| `type` | `combo` only: `bar` (default) or `line` per series. Any other value draws nothing for that series. |

What each type reads:

| Type | Series used | Notes |
|---|---|---|
| `bar` | all series | Grouped bars per label (bar width max 46 units). |
| `stacked` | all series | Series stacked per label; axis maximum is the largest stack. |
| `hbar` | all series | Horizontal grouped bars; label area is fixed at 120 units; the value is printed at the end of each bar. |
| `line`, `area` | all series | Polyline with dots; `area` adds a light fill under each line. |
| `combo` | all series, each `bar` or `line` | Bars and lines share ONE axis. |
| `pie`, `donut` | `series[0]` only | Slice colours follow the label index (the series `tone` is not used). `donut` shows the total in the centre. Slices of 5% or more get a percentage. |
| `funnel` | `series[0]` only | One trapezoid per label; the right column shows the percent of the first stage; the tooltip shows the percent of the previous stage. |
| `heatmap` | all series = ROWS | `name` is the row label, `labels` the columns; one accent colour, intensity from the minimum to the maximum value; ignores `tone`. |
| `radar` | all series | One polygon per series; `labels` are the axes (4 grid rings). |

## Behavior

### Drawing and resizing

On init the script reads `data-type`, `data-format`, `data-labels`, `data-series`, assigns default tones and draws. A `ResizeObserver` on the plot redraws when its width changes by more than 4 px; height changes alone do not trigger a redraw. Axis maxima are rounded up to a "nice" number (1, 2, 2.5, 5, 10 times a power of ten), with 4 grid intervals (2 for `hbar` when the width is under 520). If the plot is hidden at init (width 0) the script falls back to a 640 x 300 drawing area until the next resize.

### Number format

`chNum` shortens values: in Indonesian `rb` (thousand, from 10,000), `jt` (million), `M` (billion), `T` (trillion), written with a space (`1,2 jt`); in English `K`, `M`, `B`, `T`. Scaled values show up to 1 decimal, small ones up to 2. `money` prefixes `IDR `, `percent` appends `%`. A non-number is shown as `–`. The separators follow the display language.

### Legend, tooltip and interaction

- Cartesian types, `hbar`, `radar`: the legend lists the series as buttons; a click hides or shows that series and redraws (struck-through when off). The last visible series cannot be hidden.
- `pie`, `donut`: the in-SVG legend rows (`label · value`) toggle single slices; hidden slices are removed from the total and the percentages are recomputed.
- Hover on a bar, dot, slice, funnel step or heat cell shows a tooltip (`label`, series name, formatted value, plus percentage for pie / donut) that follows the mouse inside the figure.
- There is no click callback or drill-down, no zoom, no keyboard access to the tooltip. Legend entries of the cartesian types are native `button`s, so they are focusable; the pie legend rows are `g` elements and are not.
- The SVG has `role="img"` and an `aria-label` (the title); the values are not available to assistive technology except through the tooltip.

### Language and access

`title` and `subtitle` are translated; `labels`, `series[].name` and values are data. The script words come from the dictionary keys `cht_chart` (`Chart`), `cht_first` (`first stage`) and `cht_of_prev` (`{p}% of the previous stage`). The centre word of a donut is the hard-coded English `Total`. `can="module.action"` hides the chart without the ability. Dark mode follows the tokens `--dl-accent`, `--dl-green`, `--dl-amber-2`, `--dl-danger`, `--dl-violet`, `--dl-muted`, `--dl-ch-teal`.

## Data and events

### What the script reads

All data travels in the root attributes, JSON encoded: `data-type`, `data-format`, `data-labels`, `data-series`; the height in the inline style `--dl-ch-h`. Nothing is posted and no request is made. To change data, replace the attributes and re-mount, or edit `el.dlChart.series` / `labels` and call `draw()`.

### Events and JS API

- No `dl:*` event is emitted.
- `el.dlComponent` is the `Chart` instance (methods are auto-bound).
- `el.dlChart` is `{series, labels, draw}`: `series` items carry the resolved `tone` and `on` (visible) flag; `draw()` redraws the SVG and legend from the current state.

### Markup hooks

`data-dl-chart` (root), `data-ch-plot`, `data-ch-legend`, `data-ch-tip`, `data-ch-series="<index>"` (legend buttons), `data-ch-slice="<index>"` (pie legend rows), `data-tip="label|series|value[|percent]"` (on drawn marks). CSS classes: `dl-chart`, `dl-chart__head`, `dl-chart__plot`, `dl-chart__legend`, `dl-chart__li`, `dl-chart__tip`, `dl-chart__bar`, `dl-chart__line`, `dl-chart__area`, `dl-chart__dot`, `dl-chart__slice`, `dl-chart__heat`, `dl-chart__grid`, `dl-chart__ax`, and the tone classes `dl-chart--<tone>` (which set `--c`). `.dl-charts` is a ready-made auto-fill grid (min 340 px per column) for several charts side by side.

### Where it is embedded

`x-dl::dashboard` (trend as `combo`, weekly `bar`, pipeline `funnel`) and `x-dl::widget-board` (type taken from the widget data) render `x-dl::chart` internally.

## Rules and pitfalls

- Use `type` values exactly as listed; a wrong spelling draws an empty frame with axes.
- Values must be 0 or more: the axis starts at 0, negative values draw nothing for bars and the maximum ignores them.
- `combo` has a single axis. A percentage series next to a money series is crushed; scale it or use two charts.
- `pie`, `donut` and `funnel` use `series[0]` only; extra series are ignored, and their colours cannot be set per slice (they follow the label index and repeat after 8).
- Pie and donut list one legend row per label at 24 units each; many slices (about 9 or more at the default height) run below the drawing area.
- A `|` character in a label or series name breaks the tooltip (the mark's `data-tip` is split on `|`).
- Long x labels are not rotated or truncated; many categories overlap. `hbar` labels are right-aligned in a 120-unit gutter and are not truncated.
- `format="money"` always says `IDR`; there is no currency prop.
- Colour is never the only carrier: keep the series names meaningful because the tooltip and legend are the only text.
- Do not paint your own colours in the page (lint `L003`); use `tone`. Do not add inline `<script>` (`L002`).
- Lint: no rule is specific to this object. The catalog page `p6-charts` (Charts) is not in `Catalog::APPROVED`.

### Notes on inconsistencies found while writing this entry (not fixed)

- The donut centre word `Total` is hard-coded English in `chart.js`, while the other script words use dictionary keys.
- `format="money"` hard-codes the prefix `IDR ` in `core.js` (`chNum`); `Devlib\Locale` and the `currency` concept are not used.
- The header comment of `Chart.php` lists the compact notation as `1,2 jt / 3,4 M`; the English page language uses `K`, `M`, `B`, `T` instead.
- The `area` type with an empty `labels` list throws in the script (`pts[0][0]` of an empty array); `line` with empty labels just draws axes.
- `Chart` is not in `DevlibComponent::GROUP_OF`, so its translated `title` / `subtitle` go to the general dictionary group.
- The comment in `Chart.php` documents `tone` values `accent|success|...`; the full list is only in `core.js` (`CH_TONES`).

## Example

Adapted from the catalog demos `chart-bars`, `chart-lines` and `chart-pies` (fictional figures).

```blade
<div class="dl-charts">
    <x-dl::chart type="bar" title="Sales vs target" subtitle="Per month 2026" format="money"
        :labels="['Jan', 'Feb', 'Mar', 'Apr']"
        :series="[
            ['name' => 'Sales', 'data' => [1.82e9, 2.05e9, 1.96e9, 2.31e9]],
            ['name' => 'Target', 'data' => [2e9, 2e9, 2.1e9, 2.2e9], 'tone' => 'neutral'],
        ]" />

    <x-dl::chart type="combo" title="Orders and margin" subtitle="Bars = orders, line = margin %"
        :labels="['Jan', 'Feb', 'Mar', 'Apr']"
        :series="[
            ['name' => 'Orders', 'type' => 'bar', 'data' => [48, 52, 50, 61]],
            ['name' => 'Margin %', 'type' => 'line', 'data' => [22, 24, 21, 26], 'tone' => 'warn'],
        ]" />

    <x-dl::chart type="donut" title="Order status"
        :labels="['Done', 'In production', 'Waiting material', 'Cancelled']"
        :series="[['name' => 'Orders', 'data' => [184, 46, 12, 5]]]" />

    <x-dl::chart type="heatmap" title="Busy hours" subtitle="Average utilisation % per day and shift" format="percent"
        :labels="['Mon', 'Tue', 'Wed']"
        :series="[['name' => 'Shift 1', 'data' => [82, 88, 79]], ['name' => 'Shift 2', 'data' => [64, 71, 58]]]" />
</div>
```

Compact chart without legend, as used inside a dashboard card:

```blade
<x-dl::chart type="bar" title="Orders this week" height="150px" :legend="false"
    :labels="['Mon', 'Tue', 'Wed', 'Thu', 'Fri']" :series="[['name' => 'Orders', 'data' => [12, 18, 9, 22, 15]]]" />
```

## Related

- `x-dl::dashboard`, `x-dl::widget-board`: screens that embed charts (the widget board chooses the chart `type` per widget).
- `x-dl::kpi-row`, `x-dl::gauge`, `x-dl::bullet`: single-number indicators next to a chart.
- `x-dl::grid`, `x-dl::table`: the exact figures behind a chart.
- `x-dl::pivot`: user-arranged cross-tab of the same data.
- `x-dl::statement`: statement lines in a table.
- `x-dl::report`: printable report; charts do not print through it.
- `x-dl::card`: a box to put a chart in.
