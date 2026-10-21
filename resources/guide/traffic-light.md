## Overview

`<x-dl::traffic-light>` is a small three-lamp signal (red, amber, green) with a bold label and an optional note next to it. It shows a verdict for one scorecard line ("Cash flow: green", "Paper stock: amber"). Exactly one lamp is lit, chosen by `status`; the other two stay dark. It is static: no JavaScript, no events.

Use it in KPI rows and scorecards where a person must see good / watch / bad at a glance, with a sentence that says why. Pick another object when:

- the status is a document or record state (Draft, Confirmed): `x-dl::badge`;
- the status belongs to a machine, service or document card with an icon, value and time: `x-dl::status-card`;
- the number itself matters, not only the verdict: `x-dl::kpi` or `x-dl::gauge`;
- it must be clickable as a filter: `x-dl::stat-box` or `x-dl::status-card` with `filter`.

### Generated markup

```html
<div class="dl-light is-amber" data-dl="x-dl::traffic-light status=amber" data-dl-class="TrafficLight ← DevlibComponent" data-dl-src="—">
  <span class="dl-light__box"><i class="is-red"></i><i class="is-amber"></i><i class="is-green"></i></span>
  <span class="dl-light__txt"><b>Paper stock</b><small>Below minimum in 3 days</small></span>  <!-- <small> only with note -->
</div>
```

The lamps are always rendered in the order red, amber, green. CSS lights the lamp whose class equals the root class (`.dl-light.is-amber .is-amber`).

## Props

Constructor of `Devlib\Components\TrafficLight` (3 props, none inherited). Blade names are kebab-case.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `status` | `string`, `'green'` | Which lamp is lit: `green`, `amber` or `red` (lower case, exact). Written as the root class `is-<status>`. Any other value (`yellow`, `Red`, `ok`, empty) is accepted without error but no lamp matches, so all three lamps stay dark. Not translated. |
| `label` | `string`, `''` | Bold text next to the lamps (the subject of the verdict). Translated through the dictionary; wrap database values in `raw()`. An empty label still renders an empty `<b>`. |
| `note` | `?string`, `null` | Small grey line under the label (the reason). Not translated (it is not a standard text prop), so write it in the page language or build it from translated data; `null` or `''` removes the line. |

Every DevLib component also accepts `can="module.action"` (without the ability nothing is rendered) and extra HTML attributes (`id`, `class`, `style`, `data-*`), merged on the root `div.dl-light`; `class` is appended to `dl-light is-<status>`.

## Behavior

- Layout: `inline-flex`, so the object is as wide as its content; put several in a `<div class="dl-row" style="gap:28px">` (or any flex/grid container) for a scorecard line.
- Lamp: dark housing (`--dl-navy`) with three 14px circles; the lit one gets the red / amber / green token color and a glow. Colors are CSS variables, so themes follow the site theme.
- The verdict is decided by the caller. The object does no thresholding; compute `status` on the server (for example `$stockDays < 3 ? 'red' : ($stockDays < 7 ? 'amber' : 'green')`).
- No animation, no hover, no click, no keyboard.
- Language: only `label` follows the page language.

### Accessibility as implemented

The root has no `role`, no `aria-*` and no text for the lamp color; the lit lamp is only a colored `<i>`. A screen reader reads the label and the note but not "red / amber / green". Color blind users see the position of the lit lamp (left, middle, right) but nothing names it. If this matters, say the verdict in `note` ("Critical: breakdown since 08:10").

## Data and events

- Input is props only. There is no slot, no database access and `source()` is the default `—`.
- Nothing is posted, no `dl:*` event is emitted, no JS class (`jsClass()` is `null`).
- Markup hooks: `.dl-light`, `.dl-light.is-red|is-amber|is-green`, `.dl-light__box`, `.dl-light__txt`.

## Rules and pitfalls

- Spell `status` exactly `green`, `amber` or `red`. There is no validation: a typo gives a dark light with no error.
- Never show the verdict by lamp alone; always give a `label` and, for amber and red, a `note` with the reason.
- `note` is not translated; `label` is. Do not put raw database text in `label` without `raw()`.
- The tag name in the Manifest includes the status (`x-dl::traffic-light status=amber`); this is only the developer catalog, not the HTML.
- There is no lint rule for this object (`lint()` returns nothing).
- Code oddity (not fixed): `status` is not checked against the three allowed values (`src/Components/TrafficLight.php:19` and the view line 32), so an unknown value renders three dark lamps silently.
- Code oddity (not fixed): the class header (`src/Components/TrafficLight.php:15`) says "Dipakai di baris KPI / scorecard" but nothing in `x-dl::kpi` or `x-dl::kpi-row` uses it; the only user is the catalog demo `indicators`.

## Example

Copied in shape from the catalog demo `indicators` (fictional data).

```blade
<div class="dl-row" style="gap:28px">
    <x-dl::traffic-light status="green" label="Cash flow" note="Balance covers 2.4 months" />
    <x-dl::traffic-light status="amber" label="Ivory paper stock" note="Below minimum in 3 days" />
    <x-dl::traffic-light status="red" label="Die-cut machine 2" note="Breakdown since 08:10" />
</div>
```

Verdict computed on the server and shown only to users with the ability:

```blade
@php($status = $daysLeft < 3 ? 'red' : ($daysLeft < 7 ? 'amber' : 'green'))
<x-dl::traffic-light can="stock.view" :status="$status" :label="raw($item->name)" :note="$daysLeft.' days of stock left'" />
```

## Related

- `x-dl::kpi`: number card with change and target; place lights beside it for the verdict.
- `x-dl::gauge`: dial with colored zones when the value itself must be seen.
- `x-dl::status-card`: card with icon, status badge, value and time for machines, documents and services.
- `x-dl::badge`: record status word (Draft, Confirmed) rather than a scorecard verdict.
- `skills/devlib/components.md`: generated prop list of every object.
