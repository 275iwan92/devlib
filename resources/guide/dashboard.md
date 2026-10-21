## Overview

`<x-dl::dashboard>` is the opening screen shown after login (the "Beranda"). One component serves every job position: the screen passes one array, `board`, built for the profile of the user (sales, management, general ...), and the component draws, from top to bottom:

1. the greeting card (`x-dl::greeting-card`: hello with name and time of day, position, today's brief, Job Description and SOP buttons, clock, check-in badge, weather);
2. then one of three bodies:
   - the slot, when the caller puts content inside the tag (it replaces everything below the greeting);
   - the owner-arranged widget board (`x-dl::widget-board`), when `board` has the key `widgets`;
   - otherwise the fixed blocks: period bar, KPI tiles, target-vs-actual chart, activities, targets per area, pipeline funnel, ranking, focus table, today's agenda and the daily quote.

The mechanism is the same for every profile; only the content of `board` differs. The component reads no table or model: the application builds `board` (in the catalog demo, `App\Support\DashboardData`) from its own data.

Pick another object when:

- the user must arrange the widgets, change the period on the server or switch dashboards: pass `widgets` (this component then embeds `x-dl::widget-board`) or use `x-dl::widget-board` directly;
- you only need KPI tiles above a report: `x-dl::kpi-row` with `x-dl::kpi`;
- you only need the greeting block: `x-dl::greeting-card`;
- you need a screen of your own with several charts: build it with `x-dl::chart`, `x-dl::card` and `x-dl::kpi`.

Generated markup (the root; blocks are described under Behavior):

```html
<div class="dl-dash dl-dash--sales" data-dl="x-dl::dashboard profile=sales" data-dl-class="Dashboard ← DevlibComponent" data-dl-src="profil dashboard per jabatan"
     data-dl-dash data-period="monthly" data-key="dash-sales">
  <div data-dl="x-dl::greeting-card profile=sales"> ...hero, Job Description and SOP windows... </div>
  <!-- slot | widget board | fixed blocks -->
</div>
```

Extra HTML attributes (`id`, `class`, `style`, `data-*`) go on the root `div`. `can="module.action"` is read by the base class: without the ability nothing is rendered.

## Props

Constructor of `Devlib\Components\Dashboard` (2 props). Use `:board="expr"` for the array.

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `board` | `array` | `[]` | The whole content of the screen. Keys are listed in "Data and events". Every key is optional; a missing block is simply not drawn. `profile` (default `general`) becomes the root class `dl-dash--<profile>`, the id base `dash-<profile>` (modal ids `dash-<profile>-jd`, `dash-<profile>-sop`, board id `dash-<profile>-board`) and `data-key`. `period` (default: the first key of `periods`, else `monthly`) is the period panel that starts active. |
| `source` | `?string` | `null` | Free text naming where the data comes from, shown by the developer catalog (`data-dl-src`); default `profil dashboard per jabatan`. No runtime effect. |

The body slot (default slot) has no prop: any non-empty content between the tags is drawn instead of the widget board or the fixed blocks (the greeting stays).

## Behavior

### Three bodies, one precedence

1. Non-empty slot: the slot (for example the Build Progress board of the developer screen).
2. `array_key_exists('widgets', $board)`: an `x-dl::widget-board` receives `widgets`, `catalog`, `board_url` (as `action-url`), `board_editable` (default `true`), `kpi_title` (as `title`), `board_source`, `board_periods`, `board_period`, `board_dashboards`, `board_dashboard` and the id `dash-<profile>-board`. The fixed blocks are not drawn. Everything about that board (actions, period, tabs, Choose Widgets) is in the `x-dl::widget-board` entry.
3. Otherwise the fixed blocks below.

### Fixed blocks

- **Period bar.** Heading `kpi_title` (default `My KPIs`; a value you pass is shown as given) and one button per `periods` entry (labels printed as given, not translated). Clicking a button shows that period's panel and hides the others. The choice is remembered per browser in `localStorage` under `dl-dash-period-dash-<profile>` and restored on load if that panel exists. The server never learns the choice.
- **Per-period panel** (`data-dash-panel="<period>"`; the panel for the active period has `is-active`). Each block reads `board[<block>][<period>]`; a period missing from a block just leaves that block out of the panel.
  - KPI tiles: `kpis[period]`, one `x-dl::kpi` each.
  - Trend: `trend[period]`, an `x-dl::chart` of type `combo` (bars = actual, line = target, series names "Actual" translated and "Target", height 400 px) followed by an optional summary strip.
  - Activities: `activities` (shared) with `activities.data[period]`: a card with one box per activity type (total / target and a progress bar), a segment-by-type table with totals, and an optional small bar chart (`week`, 150 px, no legend).
  - Targets per area: `targets[period]`, a card with one achievement row each (percent badge and bar).
  - Pipeline: `pipeline[period]`, a funnel chart; Ranking: `ranking[period]`, a table with avatar, target, actual and a percent bar. Both share a half-width row.
- **Focus and agenda** (not per period): a "focus" table card (`focus`) and "Today's Agenda" (`agenda`), side by side, drawn when either exists.
- **Daily quote:** `quotes` drawn by `x-dl::quote order="day"` (line number = day of the year) with "n of 365" (the day number is filled by the script in the browser).

Achievement colors: `actual / target * 100` (inverted for `good_when: down` in the targets block); 100% or more is `success`, 85% to under 100% is `warn`, below 85% is `danger`. The bars never exceed 100%. Compact numbers (`Dashboard::fmt`): `money` is `IDR` plus a compact value (`IDR 1.9 M`), `percent` is `93%`, anything else is a localized number.

### Language and translation

- Translated: the fixed interface texts (`My KPIs`, `Today's Agenda`, `Actual`, `of target`, `of`, `Target group`, `Name`, `Reports to`, `Last updated`, `Effective from`), and the `title`, `caption`/`description` and labels that are handed to `x-dl::card`, `x-dl::chart` and `x-dl::kpi` (those objects translate their text props).
- Not translated: the period button labels, table headers and cells you pass (`focus.columns`, names, `stage`, `last`, `action`, agenda texts), `kpi_title` when you pass it, the Job Description and SOP HTML, the quotes. Write these in the language of the audience, or translate them where you build `board`.
- Dictionary group: none (the component is not in `DevlibComponent::GROUP_OF`).

### Access and accessibility

- `can="module.action"` hides the whole screen. Which profile a user gets is decided by the application when it builds `board` (the demo maps the position code through `config('devlib.dashboards')`).
- The period buttons sit in a `role="tablist"` container but are plain buttons with an `is-active` class (no `role="tab"`, no `aria-selected`).

## Data and events

### The `board` array

All keys are optional unless marked. `[period]` means an array keyed by the keys of `periods`.

| Key | Shape |
|---|---|
| `profile`, `profile_label` | `'sales'`, `'Dashboard Sales'`. `profile` defaults to `general`. `profile_label` is the small caption above the greeting (default `Dashboard`). |
| `user` | `['first', 'name', 'code', 'position', 'department', 'superior']`. The greeting uses `first`; the line under it shows `position`, `department` and `Reports to: superior`. |
| `brief` | `[[label, value], ...]` summary chips under the greeting. |
| `attendance` | `['in', 'status', 'tone', 'shift', 'start', 'end']`: the check-in badge. `null` or empty hides it. |
| `weather` | `['city', 'lat', 'lon']`: the weather widget. |
| `jobdesc` | `['title', 'updated', 'html']`: opens in a window from the "Job Description" button. `html` is printed unescaped. |
| `sops` | `[['code', 'title', 'revision', 'effective', 'html'], ...]`: SOP button with a count; one accordion item per SOP (the first opened). `html` is printed unescaped. |
| `periods` | `['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', ...]`: keys name the panels, values are the button labels. |
| `period` | Key of the panel that starts active. Default: first key of `periods`. |
| `kpi_title` | Heading of the period bar (or of the widget board). Default `My KPIs`. |
| `kpis` | `[period => [['label', 'value', 'previous', 'target', 'format', 'good_when', 'icon', 'tone', 'spark', 'hint'], ...]]`, passed to `x-dl::kpi` (`format` is `number`, `money` or `percent`; `good_when` is `up` or `down`; `tone` e.g. `accent`, `success`, `violet`, `teal`, `warn`, `danger`). |
| `trend` | `[period => ['title', 'caption', 'labels', 'actual', 'target', 'format', 'source', 'summary' => [[label, value, 'good'\|'warn' (optional)], ...]]]`. `actual` and `target` are number lists aligned with `labels`. |
| `activities` | `['title', 'caption', 'segment_label', 'types' => [['key', 'label', 'icon', 'tone'], ...], 'segments' => [['key', 'label', 'tone'], ...], 'data' => [period => ['target' => [type => n], 'matrix' => [segment => [type => n]], 'week' => ['title', 'labels', <type> => [n, ...]]]]]`. The box of a type shows `sum over segments / target[type]`; `week` is optional. |
| `targets` | `[period => ['title', 'caption', 'rows' => [['label', 'sub', 'actual', 'target', 'format', 'good_when', 'icon', 'tone'], ...]]]`. |
| `pipeline` | `[period => ['title', 'caption', 'labels', 'values', 'format', 'series', 'source']]`: a funnel; `series` is the series name (default `Value`). |
| `ranking` | `[period => ['title', 'caption', 'name_label', 'format', 'rows' => [['name', 'sub', 'target', 'actual'], ...]]]` (format default `money`). Sort the rows yourself. |
| `focus` | `['title', 'caption', 'columns' => [6 header texts], 'rows' => [['name', 'sub', 'segment', 'tone', 'step' (0 to 4), 'stage', 'counts' => [icon => n], 'last', 'last_tone', 'action', 'primary' (bool)], ...]]`. Each row has exactly six cells: person, segment badge, stage dots, counters, last-contact badge, action button. |
| `agenda` | `[['time', 'duration', 'title', 'note', 'tone', 'done'], ...]`. |
| `quotes` | `[['t' => text, 'a' => author], ...]`: line n of the list is day n of the year (366 lines in the demo). |
| `widgets`, `catalog`, `board_url`, `board_editable`, `board_source`, `board_periods`, `board_period`, `board_dashboards`, `board_dashboard` | Widget board mode (see above): the first key switches it on; the others map to the props of `x-dl::widget-board` (`widgets`, `catalog`, `action-url`, `editable`, `source`, `periods`, `period`, `dashboards`, `dashboard`). |

`Dashboard::totals($matrix, $types)` (public static) returns the total per activity type from the segment-by-type matrix; `Dashboard::fmt($value, $format)` formats a compact number. `Dashboard::period()` returns the starting period key.

### Events and JS

- No `dl:*` event. The script (`Dashboard`, selector `[data-dl-dash]`) toggles the period panels, stores the choice in `localStorage` and writes the day of the year into `[data-dash-doy]`.
- `el.dlComponent` is the `Dashboard` instance; `show(period)` switches the panel by code. Override with `data-dl-js` as for any component.
- The Job Description and SOP buttons open modals through the generic `data-dl-open` mechanism; the widget board talks to its own address (see its entry).

### Where the data comes from

All data is supplied by the screen. In an application: targets per user and period from a target table, actuals from the transaction tables, Job Description from the position master, SOP from the SOP documents linked to the position, quotes from a 366-line table. The demo builds fictional, deterministic numbers (`App\Support\DashboardData`, `DashboardDocs`).

## Rules and pitfalls

- One component for all positions: do not copy the Blade for a new job position; add a profile to your `board` builder and send the keys that profile needs.
- Every per-period block must be keyed by the same keys as `periods`. A key missing from `kpis` leaves that panel without tiles; a key missing from `periods` is never drawn.
- `jobdesc.html` and `sops[].html` are printed unescaped (`{!! !!}`). They must come from a trusted, sanitized source (the HR-maintained master), never from user input.
- `targets` rows and `ranking` rows divide by `target`: send `0` rather than leave it out (a missing `target` raises an undefined-key error here; the widget board is more tolerant).
- Pass `focus.columns` with six headers and `rows[].step`; both are required by the view although the header comment does not list them.
- The profile name becomes a CSS class (`dl-dash--<profile>`) in the same namespace as the tone classes (`dl-dash--accent`, `--success`, `--warn`, `--danger`, `--violet`, `--teal`): do not name a profile after a tone.
- With a slot or with `widgets`, the fixed blocks, the focus table, the agenda and the quote are not drawn at all; do not expect both.
- The period remembered in `localStorage` hides the `period` you pass after the first visit of that browser. For a server-controlled period use the widget board.
- Format data before you send it where the object prints it as given (`summary` values, `brief` values, `focus` cells); numbers passed to KPI, chart, targets and ranking are formatted by the objects.
- Lint: no dashboard-specific rule. The general rules L001 (raw HTML tags), L002 (inline `<script>`) and L003 (hex colors) apply to the Blade screen that holds the component.

### Notes on inconsistencies found while writing this entry (not fixed)

- The header comment of `src/Components/Dashboard.php` lists the widget board keys as `'widgets', 'catalog', 'board_url', 'board_editable', 'board_source'`, but the view also passes `board_periods`, `board_period`, `board_dashboards` and `board_dashboard` (`dashboard.blade.php:18-20`).
- The same comment lists `focus` rows without `step` and `primary`, and the focus block without `columns`; the view requires `columns` and `step` (`dashboard.blade.php:152` and `:158`) and reads `primary`. It also omits `activities.segment_label`, `kpis[].hint`, `trend[].source` and `pipeline[].source`.
- `tag()` returns `x-dl::dashboard profile=<profile>` (not the bare tag), so `data-dl` on the root contains the profile; the same holds for `x-dl::greeting-card`.
- `Dashboard::period()` is typed `string`; with `'periods' => []` and no `period` key `array_key_first([])` returns `null` and the call fails with a `TypeError`.
- In fixed-block mode the period labels, the `focus` headers and `kpi_title` (when passed) are not translated, while the widget board translates its period labels and title.
- The targets block of the fixed layout has no "No target" case: a row with `target` 0 shows `0%` and a danger badge, while the same row in a widget board shows "No target" (`dashboard.blade.php:99` against `widget-board.blade.php`).
- The fixed layout shows `Target` and `Total` as English literals in some headers (`dashboard.blade.php:77`, `:126`, `:86`) while `Actual` is translated.

## Example

Catalog demo `dashboard-management` (the profile for top management, fictional data built by `DashboardData`):

```blade
<x-dl::dashboard :board="\App\Support\DashboardData::forLogin('carrij')" source="App\Support\DashboardData (sample)" />
```

A minimal `board` that draws a greeting, one period panel with KPI tiles, a trend chart and the daily quote:

```php
$board = [
    'profile' => 'general',
    'profile_label' => 'Dashboard',
    'user' => ['first' => 'Sania', 'name' => 'Sania Putri', 'position' => 'Staff Human Capital', 'department' => 'HR', 'superior' => 'Rina Wulandari'],
    'brief' => [['Tasks due today', '3'], ['Meetings', '2']],
    'periods' => ['weekly' => 'Weekly', 'monthly' => 'Monthly'],
    'period' => 'monthly',
    'kpis' => [
        'weekly' => [['label' => 'Tasks Done', 'value' => 9, 'previous' => 8, 'target' => 10, 'icon' => 'fa-list-check', 'tone' => 'accent']],
        'monthly' => [['label' => 'On-time Attendance', 'value' => 97.5, 'previous' => 96, 'target' => 98, 'format' => 'percent', 'icon' => 'fa-fingerprint', 'tone' => 'success']],
    ],
    'trend' => [
        'monthly' => ['title' => 'Target vs Tasks Done', 'caption' => 'Last 4 months', 'labels' => ['Jun', 'Jul', 'Aug', 'Sep'],
                      'actual' => [34, 38, 41, 37], 'target' => [40, 40, 42, 42], 'format' => 'number',
                      'summary' => [['Done', '150'], ['Target', '164'], ['Ratio', '91.5%', 'warn']]],
    ],
    'quotes' => [['t' => 'A team is as strong as its cooperation.', 'a' => '']],
];
```

```blade
<x-dl::dashboard :board="$board" source="MyApp\Dashboard\BoardBuilder" />
```

Opening screen with a custom body in the slot (the greeting is kept):

```blade
<x-dl::dashboard :board="$board">
    <x-dl::build-board :build="$build" source="resources/data/build.json" />
</x-dl::dashboard>
```

Board mode (owner-arranged widgets): add `widgets` and the endpoint to the same array.

```php
$board += ['widgets' => $widgets, 'catalog' => $catalog, 'board_url' => route('board'), 'board_editable' => true,
           'board_periods' => ['weekly' => 'Weekly', 'monthly' => 'Monthly'], 'board_period' => 'monthly'];
```

## Related

- `x-dl::widget-board`: the owner-arranged board that this screen embeds when `board` has `widgets`.
- `x-dl::greeting-card`: the greeting block, usable alone; it reads the same `board` keys for the user, brief, attendance, weather, Job Description and SOP.
- `x-dl::kpi`, `x-dl::kpi-row`: the KPI tile and the wrapper row.
- `x-dl::chart`: the trend and funnel charts.
- `x-dl::card`, `x-dl::progress`, `x-dl::badge`, `x-dl::avatar`: the objects the blocks are drawn with.
- `x-dl::quote`: the daily quote line.
- `x-dl::layer-shell`: the application shell whose `dashboard` slot normally holds this screen.
- `x-dl::build-board`: an example of a body placed in the slot.
