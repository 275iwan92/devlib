## Overview

`<x-dl::kpi>` is the dashboard KPI card: an icon and label, the main value, a change badge against the previous period (arrow plus percent, colored by whether the direction is good), an optional sparkline, and a progress bar toward a target (or a free hint line). A colored top bar follows the `tone`. It is static, server-rendered HTML with an inline SVG sparkline: no JavaScript, no events.

Use it for numbers a manager reads on a dashboard or report header (sales, reject rate, receivables). Pick another object when:

- you need a clickable count that filters a list: `x-dl::stat-box` (or `x-dl::status-card` for a thing with a status);
- the value must be judged on a dial with zones: `x-dl::gauge`;
- you only need a verdict lamp: `x-dl::traffic-light`;
- you need a full chart: the chart objects (`x-dl::chart`, `x-dl::progress-ring`, `x-dl::bullet`);
- you build a whole dashboard: `x-dl::dashboard` / `x-dl::widget-board` already create `x-dl::kpi` cards from their data array.

### Generated markup

```html
<div class="dl-kpi dl-kpi--accent" data-dl="x-dl::kpi" data-dl-class="Kpi ← DevlibComponent" data-dl-src="—">
  <div class="dl-kpi__top">
    <span class="dl-kpi__icon"><i class="fa-solid fa-money-bill-wave"></i></span><span class="dl-kpi__label">Sales this month</span>
    <span class="dl-kpi__delta is-good" title="vs last period: IDR 2.7B">▲ 8.6%</span>      <!-- only with a usable previous -->
  </div>
  <div class="dl-kpi__row">
    <b class="dl-kpi__value">IDR 2.9B</b>
    <svg class="dl-kpi__spark [is-bad]" viewBox="0 0 120 36" preserveAspectRatio="none"><polyline points="0,30 17.1,..."/></svg>  <!-- only with 2+ spark points -->
  </div>
  <div class="dl-kpi__target"><span style="width:96.7%"></span></div><small class="dl-kpi__hint">97% of target IDR 3B</small>   <!-- with target -->
  <small class="dl-kpi__hint">hint text</small>                                                                  <!-- hint, only without target -->
  <small class="dl-kpi__hint dl-kpi__note">note text</small>                                                     <!-- only with note -->
</div>
```

Put several cards in `<div class="dl-kpis">` (CSS grid, `auto-fill`, columns of at least 210px, gap 10px) or in `<x-dl::kpi-row>` (flex wrapper `.dl-grid__stats`).

## Props

Constructor of `Devlib\Components\Kpi` (11 props, none inherited). Blade names are kebab-case (`good-when`); use `:prop="expr"` for anything that is not a plain string (`:value="2.9e9"`, `:spark="[1,2,3]"`).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `label` | `string`, `''` | Caption next to the icon. Translated through the dictionary (wrap database text in `raw()`). |
| `value` | `float\|int\|string`, `0` | The main value. A number (or numeric string) is formatted by `format`; any other string (`5y 4m`, `Verbal 6/6`) is printed as is and disables the change badge. See the number pitfall below. |
| `format` | `string`, `'number'` | `number` (default), `money` or `percent`. `number`: abbreviated (see Behavior). `money`: `IDR ` + abbreviated number; the prefix `IDR` is fixed. `percent`: value with at most 1 decimal and `%`, not abbreviated. Any other string behaves as `number`. Applies to `value`, `target` and the `previous` shown in the badge title. |
| `previous` | `?float`, `null` | Value of the previous period. With a numeric `value` and `previous` other than `0`, shows the change badge `▲ n%` / `▼ n%`. `null` or `0` = no badge (no division by zero). |
| `good-when` | `string`, `'up'` | Which direction is good: `up` (default; sales) or `down` (reject rate, receivables, cost). Only the value `down` switches; anything else means `up`. It colors the badge (green `is-good`, red `is-bad`) and the sparkline; the arrow always shows the real direction. |
| `spark` | `array`, `[]` | Numbers for the sparkline (oldest first), drawn as a polyline scaled between its own minimum and maximum. Fewer than 2 points = no sparkline. Non-numeric entries count as `0`. A flat series is drawn as a middle line. The line is green, or red (`is-bad`) when a badge exists and the change is bad. |
| `target` | `?float`, `null` | Goal. When set and not `0`, draws a progress bar of `value / target` (clamped 0..100%) and the text `<n>% of target <target>` (translated words). Replaces `hint`. `null` or `0` = no bar. |
| `hint` | `?string`, `null` | Small line under the value. Translated. Shown only when `target` is not set (the target text wins). |
| `icon` | `string`, `'fa-chart-line'` | Icon in the rounded square: a DevLib meaning name (`money`, `user`, `time`, `warning`, ... from `Devlib\Icon::MAP`) or a Font Awesome class (`fa-truck-fast`). The view adds `fa-solid`, so only solid icons work. |
| `tone` | `string`, `'accent'` | Color of the top bar, icon tint and target bar: `accent`, `success`, `warn`, `danger`, `violet`, `teal`. Written as `dl-kpi--<tone>`. Other values (`info`, `neutral`) have no rule: the variable is undefined and the bar, tint and target bar are not colored. It does not affect the badge or sparkline colors. |
| `note` | `?string`, `null` | Extra small line at the very bottom (a breakdown such as `Confirmed 12 · Draft 3`), always shown when set, in addition to `target` or `hint`. Not translated. |

Every DevLib component also accepts `can="module.action"` (without the ability nothing is rendered) and extra HTML attributes (`id`, `class`, `style`, `data-*`), merged on the root `div.dl-kpi`.

## Behavior

### Number formatting (`format`)

- `number`/`money` first abbreviate with `Slider::compact`: from 1,000 up as `1.3K`, `2.9M`, `3B`, `1T` (English) or `1,3 rb`, `2,9 jt`, `3 M`, `1 T` (Indonesian, by display language); below 1,000 as `Locale::number` (integer when whole, otherwise 1 decimal: `12.5`). One decimal at most, trailing zero dropped. So `1284` prints `1.3K`: the exact value is not shown.
- `percent` prints `Locale::decimal(v, 1)` + `%` (`1.4%`, `1,4%` in Indonesian).
- The target text is `Locale::number(pct)` (no decimals) + translated `of target` + the formatted target.
- The badge percent is `Locale::number(abs(change), 1)` + `%` (always exactly one decimal, `8.0%`); the badge `title` is the translated `vs last period` plus the formatted `previous`.

### Change badge

`change = (value - previous) / abs(previous) * 100`. The arrow is `▲` when `change >= 0` and `▼` when below; zero change shows `▲ 0%` in the good color. `good-when="down"` makes a negative change green.

### Layout, theme, language

- Card: white surface, 3px top bar in the tone color; the value is 24px tabular figures, `white-space: nowrap`; the sparkline (110 x 34) sits right of the value and shrinks when narrow.
- Colors and fonts are CSS variables and respect the font scale setting, so light, dark and density settings follow the site.
- Language: translated are `label`, `hint`, `vs last period` and `of target`. Not translated: `note`, `value` text, the `IDR` prefix.

### Accessibility as implemented

No `aria-*`, no `role`. The badge arrow and the red / green color carry the direction (the arrow is text, so direction is readable; whether it is good is only color). The sparkline `svg` is not hidden from assistive technology and has no title or description. The card is not focusable.

## Data and events

- Input is only props; there is no slot (`source()` is `—`). `x-dl::dashboard` feeds it from `kpis` rows (keys `label`, `value`, `previous`, `target`, `format`, `good_when`, `icon`, `tone`, `spark`, `hint`) and `x-dl::widget-board` from widget data (plus `note`).
- Nothing is posted; no `dl:*` event, no JS class (`jsClass()` is `null`).
- Hooks: `.dl-kpi`, `.dl-kpi--<tone>`, `.dl-kpi__value`, `.dl-kpi__delta.is-good|is-bad`, `.dl-kpi__spark`, `.dl-kpi__target > span`, `.dl-kpi__hint`, `.dl-kpi__note`.

## Rules and pitfalls

- A whole number of 1,000 or more is abbreviated. When the exact figure must show, pass it as a text that is not numeric (for example `"1,284 PO"`) or use `x-dl::stat-box`, which prints the string as given.
- A numeric string is parsed by PHP, not by the display locale: `"1.284"` is read as `1.284` (printed `1.3`). Pass real numbers (`:value="1284"`), never locale-formatted strings.
- `money` always prefixes `IDR`; there is no currency prop. Format other currencies yourself and pass the text.
- Set `good-when="down"` for every metric where lower is better, or the badge will show a good (green) increase when cost rises.
- `previous` equal to `0` or `null` hides the badge, so a first period shows no change.
- `hint` disappears as soon as `target` is set; use `note` for text that must stay.
- Wrap database text in `label`, `hint` with `raw()`; `note` is never translated.
- Give a `tone` from the list; `info`/`neutral` show an unstyled card.
- No lint rule targets this object.
- Code oddity (not fixed): `Kpi` formats numbers with `Devlib\Components\Field\Slider::compact` (`src/Components/Kpi.php:47`), a field class, instead of a shared formatter; changing the slider helper changes every KPI.
- Code oddity (not fixed): `money` hard-codes `IDR ` in `show()` (`src/Components/Kpi.php:50`), while `Devlib\Locale::money()` takes a currency.
- Code oddity (not fixed): the class header summary (`src/Components/Kpi.php:4`) ends with a trailing comma (`...sesuai arah yang BAIK),.`), which is also what the generated `skills/devlib/components.md` shows.

## Example

Copied in shape from the catalog demo `indicators` (fictional data).

```blade
<div class="dl-kpis">
    <x-dl::kpi label="Sales this month" :value="2.9e9" :previous="2.67e9" format="money" :spark="[1.8, 2.05, 1.96, 2.31, 2.44, 2.12, 2.67, 2.9]" :target="3e9" icon="money" tone="accent" />
    <x-dl::kpi label="Reject rate" :value="1.4" :previous="1.9" format="percent" good-when="down" :spark="[2.4, 2.8, 2.1, 1.9, 1.6, 2, 1.9, 1.4]" icon="fa-triangle-exclamation" tone="success" hint="Lower is better" />
    <x-dl::kpi label="Overdue receivables" :value="845e6" :previous="620e6" format="money" good-when="down" icon="fa-file-invoice-dollar" tone="danger" hint="Up 36% vs last month" />
    <x-dl::kpi label="On-time orders" :value="93" :previous="89" format="percent" :target="95" icon="fa-truck-fast" tone="violet" />
</div>
```

Text value, extra note, and the KPI row wrapper used by a profile or report header:

```blade
<x-dl::kpi-row>
    <x-dl::kpi label="Tenure" value="5y 4m" icon="fa-hourglass-half" tone="success" />
    <x-dl::kpi label="Open orders" :value="$open" icon="fa-boxes-stacked" :note="'Confirmed '.$confirmed.' · Draft '.$draft" />
    <x-dl::kpi can="finance.view" label="Cash balance" :value="$cash" format="money" :previous="$cashLastMonth" />
</x-dl::kpi-row>
```

## Related

- `x-dl::kpi-row`: flex wrapper (`.dl-grid__stats`) for a row of KPI cards on top of a report or profile header.
- `x-dl::dashboard`, `x-dl::widget-board`: build `x-dl::kpi` cards from a data array (including the same props).
- `x-dl::stat-box`: plain or clickable count tile, string printed as is.
- `x-dl::gauge`: dial with colored zones for one value.
- `x-dl::traffic-light`: red / amber / green verdict with a sentence.
- `x-dl::status-card`: status of a machine, document or service.
- `skills/devlib/components.md`: generated prop list of every object.
