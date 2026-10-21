## Overview

`<x-dl::kpi-row>` is a plain wrapper that lines up a set of number cards (normally `x-dl::kpi`, or `x-dl::stat-box`) in one wrapping row above a report or dashboard. It owns only the container: the gap and the wrapping. It takes no props and no data; everything that is shown comes from the cards you put in its slot.

Use it when you want one named object instead of a hand-written `<div class="dl-grid__stats">`. Pick another object when:

- you want an auto-filling grid of equal KPI cards with a minimum width: write `<div class="dl-kpis">` around the `x-dl::kpi` cards (that is what the `indicators` catalog demo does);
- the cards must work as filters of a grid or sheet: use the `stats` prop of `x-dl::grid` / `x-dl::sheet` (they render clickable `x-dl::stat-box` cards by themselves);
- you need one single card: use `x-dl::kpi` or `x-dl::stat-box` directly.

Generated markup (the slot content is inserted as is):

```html
<div class="dl-grid__stats" data-dl="x-dl::kpi-row" data-dl-class="KpiRow ← DevlibComponent" data-dl-src="—">
  <div class="dl-kpi dl-kpi--accent">...</div>
  <div class="dl-kpi dl-kpi--success">...</div>
</div>
```

`data-dl`, `data-dl-class` and `data-dl-src` are the developer-catalog "ID card" attributes (Technical Guide link); they have no runtime effect.

## Props

`KpiRow` has no constructor, so it has no props of its own and inherits none (the abstract `DevlibComponent` has no constructor props).

| Attribute | Meaning |
|---|---|
| (none) | There is nothing to configure. |
| `class`, `id`, `style`, `data-*` | Any extra HTML attribute is merged onto the root `div` (`$attributes->class('dl-grid__stats')`; your `class` is appended). |
| `can="module.action"` | Handled by `DevlibComponent::withAttributes()`: the attribute is removed from the HTML and, when `Access::can('module.action')` is false, the whole row (slot included) renders as an empty string. |

Nothing is translated by the row itself; the cards translate their own text.

## Behavior

- Layout comes from two CSS rules in `resources/assets/devlib.css`: `.dl-grid__stats{display:flex;gap:10px;flex-wrap:wrap}` (line 993) and `.dl-grid__stats .dl-stat{flex:1 1 150px}` (line 994). So `x-dl::stat-box` children (class `dl-stat`) share the width evenly and wrap from a 150px base.
- `x-dl::kpi` children (class `dl-kpi`) do NOT match the `.dl-stat` rule, so they are not stretched: each card is only as wide as its content and the row wraps by content width. See the code oddity below.
- Inside `x-dl::profile-banner` (slot `kpis`) the CSS turns the row into a grid: `.dl-pbanner__kpis .dl-grid__stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr))}` (line 3894). There the cards are equal width.
- No script, no state, no keyboard or ARIA behavior (no `role`, no `aria-*`). Colors and dark mode come from the cards.
- There is no empty state: with an empty slot you get an empty `div`.

## Data and events

- Input: only the slot. No arrays, no URLs, nothing is posted, no `dl:*` event is emitted.
- No JS twin, no `el.dlComponent`; the root has no `data-dl-*` behavior hook except the catalog attributes above.
- Because the row only wraps, a screen that needs the numbers computed does it in the data class and passes plain values to each card, for example `<x-dl::kpi :value="$kpi['total']" />`.

## Rules and pitfalls

- Put number cards in it, not forms or grids: the CSS is made for `dl-kpi` / `dl-stat` cards.
- For equal-width KPI cards outside a profile banner, give the row a layout yourself, for example `style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr))"` on the `x-dl::kpi-row`, or use the `dl-kpis` grid instead.
- Do not wrap the row in `x-dl::grid`: the grid prints its own stats row from its `stats` prop.
- Text inside the cards that comes from the database must be wrapped in `raw()` when it is put into a translated prop (for example a `label`); see the rule in `DevlibComponent`.
- Code oddity (not fixed): `.dl-grid__stats .dl-stat{flex:1 1 150px}` in `resources/assets/devlib.css:994` only targets `.dl-stat`, but the `KpiRow` header comment (`src/Components/KpiRow.php:8`) and its purpose line present the row as the wrapper of `x-dl::kpi` cards. `.dl-kpi` has no flex basis (`devlib.css:1892`), so outside a profile banner the KPI cards do not fill the row evenly.
- Code oddity (not fixed): `src/Components/KpiRow.php:9` lists the screens `acc-*`, `mgmt-executive`, `maintenance-dashboard`, `production-*`, `setup-data-origin` as users; they are inheriting-project screens and are not in this repository. The only usage in the catalog is `site/resources/views/demos/profile-banner.blade.php:9`.

## Example

Shape copied from the `profile-banner` demo (fictional person and numbers):

```blade
<x-dl::kpi-row>
    <x-dl::kpi label="Tenure" value="5y 4m" icon="fa-hourglass-half" tone="success" />
    <x-dl::kpi label="Leave days left" value="12" icon="fa-umbrella-beach" />
    <x-dl::kpi label="Attendance" value="0" icon="fa-fingerprint" />
    <x-dl::kpi label="Discipline" value="Verbal 6/6" icon="fa-gavel" tone="danger" />
</x-dl::kpi-row>
```

With stat boxes (these do stretch evenly) and permission:

```blade
<x-dl::kpi-row can="report.view" class="mb-3">
    <x-dl::stat-box label="Open orders" value="42" tone="accent" />
    <x-dl::stat-box label="Late" value="5" tone="danger" />
    <x-dl::stat-box label="Done today" value="17" tone="success" />
</x-dl::kpi-row>
```

Equal-width KPI cards without a profile banner:

```blade
<x-dl::kpi-row style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr))">
    <x-dl::kpi label="Sales this month" :value="2.9e9" :previous="2.67e9" format="money" :target="3e9" icon="money" />
    <x-dl::kpi label="Reject rate" :value="1.4" :previous="1.9" format="percent" good-when="down" tone="success" />
</x-dl::kpi-row>
```

## Related

- `x-dl::kpi`: the KPI card (label, value, change vs last period, sparkline, target bar); the normal child of this row.
- `x-dl::stat-box`: the plain or clickable (filter) number box; stretches evenly inside the row.
- `x-dl::profile-banner`: its `kpis` slot wraps the row in an equal-width grid.
- `x-dl::grid`, `x-dl::sheet`: print their own stats row from the `stats` prop; use that instead when the numbers filter the table.
- `skills/devlib/components.md`: generated prop list (this object: none).
