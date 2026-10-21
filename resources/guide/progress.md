## Overview

`<x-dl::progress>` is the horizontal progress bar of DevLib: an 8 px track with a filled bar, and an optional line above it with a label on the left and the percentage (or your own text) on the right. The colour of the bar is a `tone`, fixed or chosen automatically from the value (`tone="auto"`).

Use it for a share of a whole that fits in a line or a table cell: data readiness, PO realisation, target vs actual, profile completeness. It is already used inside `x-dl::dashboard`, `x-dl::widget-board`, `x-dl::profile-banner`, `x-dl::suggestion-strip` and `x-dl::build-board`.

Pick another object when:

- the figure should be a big round indicator with a caption: `x-dl::progress-ring`;
- the value is a headline number with trend or limits: `x-dl::kpi`, `x-dl::gauge`, `x-dl::stat-box`;
- the wait has no measurable progress: `x-dl::spinner`;
- the figure belongs in a grid column: use the grid column type `progress`, not this tag.

### Generated markup

```html
<div class="dl-progress dl-progress--success" data-dl="x-dl::progress" data-dl-class="Progress ← DevlibComponent" data-dl-src="—">
  <div class="dl-progress__label"><span>Kesiapan data</span><b>92%</b></div>   <!-- only with label -->
  <div class="dl-progress__track"><div class="dl-progress__bar" style="width:92%"></div></div>
</div>
```

## Props

Constructor of `Devlib\Components\Progress` (4 props; nothing is inherited from `DevlibComponent`).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `value` | `float`, `0` | Percent done, 0 to 100. Bar width is `max(0, min(100, value))` %, so values below 0 or above 100 are clamped. Pass a number (`:value="64"`) or a numeric string (`value="64"`); `null` and non-numeric text raise a PHP `TypeError`. Also drives the percent text and `tone="auto"`. |
| `tone` | `string`, `'accent'` | Bar colour, class `dl-progress--<tone>`. Styled: `accent` (default, theme accent), `success` (green), `warn` (amber), `danger` (red), `violet`, `teal`. `auto` picks from `value`: `>= 80` `success`, `>= 50` `warn`, otherwise `danger` (uses the unclamped, unrounded value). Any other string (`info`, `neutral`, `primary`...) has no rule and looks like `accent`. Not translated. |
| `label` | `?string`, `null` | Text of the line above the bar (left side). Without a label the whole line is omitted: no percent, no `text`, only the bar is drawn. Translated through the dictionary (`label` is a standard text prop); wrap data values in `raw()` (for example "Built, waiting for user QC · 12" in `x-dl::build-board`). |
| `text` | `?string`, `null` | Right-side text of the label line, replacing the percent (for example a count `46 / 50`). Needs a `label` to be visible. The bar still follows `value`. Translated like `label` (`text` is a standard text prop), so wrap data in `raw()`. `null` prints `Locale::percent($value, 0)`. |

Extra HTML attributes (`id`, `class`, `style`, `data-*`) are merged onto the root `div`; `class` is appended to the generated classes. `can="module.action"` is supported by the base class `DevlibComponent`: without the ability nothing is rendered.

## Behavior

- Look: track `--dl-chip` colour, 8 px high, fully rounded; bar uses the tone colour; the label line is 12 px (scaled by the user font scale) in the ink colour, the right value in bold. All colours come from theme variables, so dark mode and themes are followed.
- Animation: the bar has `transition: width .4s`. It animates only when a script changes `style.width` after load; the server-rendered bar appears at its final width without animation. No `prefers-reduced-motion` rule exists for it.
- Percent text: `Locale::percent($value, 0)`, that is the number with 0 decimals in the page language format plus `%`. It shows the real value, not the clamped one: `value=120` draws a full bar and prints `120%`; `value=79.6` prints `80%` but is still `warn` under `tone="auto"` (threshold is on the raw value).
- Layout: block element filling its container width; inside a table cell or a flex row give the parent the width you want (`x-dl::dashboard` and `widget-board` put it in `dl-dash__rkpct` cells, `profile-banner` in `.dl-pbanner__prog`, with `margin:0` and `flex:1` rules).
- Language: `label` and `text` are translated; the number format follows the page language.
- Accessibility as implemented: plain `div`s only. There is no `role="progressbar"`, no `aria-valuenow` / `aria-valuemin` / `aria-valuemax`, and no `aria-label`. A screen reader reads only the label and percent text, and nothing when `label` is empty. Add `role` and `aria-*` through extra attributes if the screen needs it; the attributes go on the root, not on the bar.

## Data and events

- Input is only the four props; there is no slot.
- Nothing is posted and no `dl:*` event is emitted. There is no JS twin and `el.dlComponent` is not set.
- Hooks for your own script: `.dl-progress`, `.dl-progress__bar` (change `style.width`), `.dl-progress__label`, `.dl-progress__track`, tone classes `.dl-progress--<tone>`. `x-dl::kanban` builds the same bar in JavaScript (`resources/assets/js/components/kanban.js:224`, adds `is-done` at 100%, green) using the same classes.

## Rules and pitfalls

- Give it 0 to 100. A fraction (0.64) draws a bar at 0.64%, and nothing converts it. Compute the percent yourself (`$done / $total * 100`) and guard against division by zero (`$total ? ... : 0`), as `x-dl::dashboard` does.
- `tone="auto"` thresholds are 80 / 50 here but 85 / 60 in `x-dl::progress-ring`. Do not expect the same colour from the same number in both.
- The grid column type `progress` has its own colour rule (`resources/assets/js/components/grid.js:278`: 100 success, >= 60 accent, >= 30 warn, else danger), a third scheme; it does not use this component.
- `tone="auto"` means "higher is better". For a figure where low is good (defect rate, budget used) set the tone yourself from your own rule.
- `text` shows only with a `label`.
- A numeric value in `label` or `text` is data: wrap it in `raw()` so it is not translated and not added to the dictionary.
- The percent text is rounded to whole numbers; show decimals through `text` (`:text="Devlib\Locale::percent($v, 1)"`) if needed.
- Do not use `tone="info"` or `"neutral"` here (they exist for the ring and charts only).
- No lint rule covers this object.
- Code oddity (not fixed): with `label` set, the percent text is not clamped (`src/Components/Progress.php:36` uses `$value`, the bar at `:37` uses `max(0, min(100, $value))`), so `value=-5` prints `-5%` over an empty bar.

## Example

Copied in shape from the catalog (`avatar-progress`):

```blade
<x-dl::progress :value="92" tone="auto" label="Kesiapan data" />
<x-dl::progress :value="64" tone="auto" label="Realisasi PO" />
<x-dl::progress :value="31" tone="auto" label="Stok aman" />
```

Fixed tone and a custom right-hand text (the bar still follows `value`):

```blade
<x-dl::progress :value="92" tone="success" label="Plan Delivery" text="46 / 50" />
<x-dl::progress :value="38" label="Anggaran Terpakai" />
<x-dl::progress :value="$target ? min(100, $actual / $target * 100) : 0" tone="violet" />
```

Data in the label (not translated):

```blade
<x-dl::progress :value="$pct" tone="warn" :label="raw($warehouse->name.' · '.$count)" />
```

## Related

- `x-dl::progress-ring`: round version with a caption; other auto thresholds (85 / 60) and a wider tone list.
- `x-dl::gauge`, `x-dl::kpi`, `x-dl::stat-box`: headline figures with more context.
- `x-dl::chart`: tones `dl-chart--*` are shared with the ring.
- `x-dl::spinner`: when progress cannot be measured.
- `x-dl::dashboard`, `x-dl::widget-board`, `x-dl::profile-banner`: containers that already embed this bar.
- `x-dl::grid`: column type `progress` for a bar per row.
- `skills/devlib/components.md`: generated prop list.
