## Overview

`<x-dl::year-calendar>` shows one whole year as twelve small month grids, for holiday lists and working-day calendars. Dates you mark (`marks`) are painted in a colour per tone, non-working weekdays (`off-days`, by default Sunday) are written in red like a wall calendar, today is outlined, and every month header shows how many marked days it contains. An optional legend explains the tones. Dates can be links: a marked date opens its own `url`, and an unmarked date can open a "new" URL with the date filled in, which is how a holiday master grid usually creates a new entry.

It is rendered by the server (`YearCalendar::months()` and an inline Blade view); there is no JavaScript, no event and no state in the browser.

Use it when the year view is the point: public and company holidays, collective leave, shutdown days. Pick another object when:

- Users must create, edit or drag events with times: use `x-dl::calendar`.
- You need hours of the day or resources: use `x-dl::gantt`.
- You only need to pick a date in a form: use `x-dl::field.date`.
- The holidays are a list to filter and export: use `x-dl::grid` (a year calendar is usually shown next to or above that grid, not instead of it).

## Props

Constructor of `Devlib\Components\YearCalendar` (6 props). Blade names are kebab-case; arrays and numbers need the `:prop="expr"` form.

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `year` | `int` | none (required) | The year to draw, for example `2026`. There is no default: leaving it out is an error. |
| `marks` | `array` | `[]` | The marked dates, keyed by `'YYYY-MM-DD'` (shape below). Keys outside `year` are never drawn. |
| `legend` | `array` | `[]` | `[tone => label]`. When not empty a legend row is shown above the months: a colour swatch (class of that tone) and the label, translated through the dictionary. Empty = no legend row. |
| `off-days` | `array` | `[0]` | Weekdays written in red on every date cell, as integers with `0` = Sunday ... `6` = Saturday (the comparison is strict, so write `[0, 6]`, not `['0', '6']`). `[]` = none. |
| `new-url` | `?string` | `null` | Link template for dates: `{date}` is replaced by `YYYY-MM-DD`, for example `/holidays/create?date={date}` or `#new={date}`. When set, every date that has no mark URL becomes a link to it (see Behavior). `null` = such dates are plain text. |
| `source` | `?string` | `null` | Free text naming where the data comes from (developer catalog / Manifest); default `tanggal bertanda`. No visual effect. |

Common attributes: extra HTML attributes (`id`, `class`, `style`, `data-*`) go on the root `div`; `can="module.action"` hides the whole component when the user lacks the ability.

### The `marks` array

`marks` is `['2026-08-17' => ['label' => 'Independence Day', 'tone' => 'danger', 'url' => '/holidays/12'], ...]`:

| Key | Type | Meaning |
|---|---|---|
| `label` | `string` | Required. Text in the tooltip (`title`) after the formatted date: `17-Aug-2026 · Independence Day`. It is data, so it is not translated. Missing = PHP error (undefined array key). |
| `tone` | `string` | Colour of the day: `danger`, `warn`, `info`, `success`, `neutral`, or `off` (outlined red, transparent background). Default `neutral`. Written into the class `dl-ycal__d--<tone>`; any other name gets no colour. |
| `url` | `?string` | Link of the marked date. Optional. Used as is (no `{date}` replacement). |

## Behavior

### Rendering

- Twelve months in an auto-fill grid (cells at least 220px wide, so 4 / 3 / 2 / 1 columns depending on the width). Each month has its localized full name (`F`, in the display language from `Devlib\Locale`), the weekday initials (two letters, week starts on Monday, fixed), and the dates. The first date is placed on its ISO weekday with empty `span`s before it.
- The count badge (`dl-ycal__n`, red) after the month name is the number of keys in `marks` that start with that month (`YYYY-MM`); it is hidden when zero. It counts marks of every tone.
- A date cell gets: `dl-ycal__d`, `dl-ycal__d--<tone>` when marked, `is-off` when its weekday is in `off-days`, `is-today` when it is today (server date, outlined).
- Mark colours win over the red off-day text: a `danger` day on a Sunday is a red tile with white text; a `neutral` or `info` tile keeps its own text colour.

### Links

For each date the target is: the mark's `url` if it has one, otherwise `new-url` with `{date}` replaced, otherwise none.

- With a target the cell is an `<a href>` (hover shows an accent outline).
- Without a target it is a `<span>`.
- The tooltip (`title`) of every date is the formatted date, plus ` · label` for a marked date.

Note that when `new-url` is set, a marked date WITHOUT its own `url` also links to `new-url` (it opens "new" for a date that already has a mark); give marked dates their own `url` (the edit page) when that matters.

### Accessibility, language, access

- The year is a set of links and spans; there is no keyboard model beyond normal link focus, no ARIA grid, and the tone is conveyed by colour plus the tooltip only. Do not rely on colour alone: the legend and the count badge help, and the holiday list should also exist as a grid.
- Month names and weekday initials follow the display language. The legend labels go through `Lang::text()`. Mark labels are data (not translated).
- "Today" is the server date in the application time zone (`CarbonImmutable::today()`), not the browser's.
- `can="module.action"` works like on every component: without the ability nothing is rendered.

## Data and events

- Markup: root `<div class="dl-ycal" data-dl="x-dl::year-calendar" data-dl-class data-dl-src>`; optional `dl-ycal__legend` (spans with `<i class="dl-ycal__sw dl-ycal__d--<tone>">`); `dl-ycal__grid` of `dl-ycal__m` months, each with `dl-ycal__mh` (name `<b>` and `dl-ycal__n` count), then `dl-ycal__days` with the seven `dl-ycal__h` weekday headers followed by the date cells.
- No `data-*` hooks other than the identity attributes, no JavaScript file, no `el.dlXxx`, no `dl:*` events. The only interaction is following the links.
- Public methods (usable from the view or a subclass): `months()` (list of `['name', 'cells', 'count']`, each cell `['day', 'date', 'mark', 'today', 'off', 'url', 'title']` or `null`), `weekdays()`, `legendItems()`.

## Rules and pitfalls

### Rules

- Keep the data as `marks`; do not draw holiday tables by hand. Behaviour for the links (a modal, an inline form) belongs to the target page or to project JS, not to an inline `<script>` in the screen (lint L002; also L001 for raw tags and L003 for hex colours). There is no lint rule specific to this object.
- Put both the calendar and a grid of the same marks on a holiday master screen: the calendar is for overview, the grid for filtering and export.
- Use only the documented tones; add new tones in the CSS (`.dl-ycal__d--<tone>`) before using them.

### Common mistakes

- Forgetting `:` on `:year` (`year="2026"` also works because PHP converts the numeric string, but `:year="$year"` is the usual form), or omitting it.
- Writing `off-days` as `"[0,6]"` without the colon: it becomes a string and fails the type; use `:off-days="[0, 6]"`.
- A mark without `label`: the page fails with an undefined array key.
- Expecting `marks` keys in another date format (`17-08-2026`): only `YYYY-MM-DD` matches.
- Expecting the week to start on Sunday: it always starts on Monday; there is no prop.
- Using `legend` tones that no mark uses: the swatch still shows, so the legend should match the tones in `marks`.

### Performance

About 365 cells and one `Locale::date()` call per date are rendered on the server; trivial for one year. Several years on one page multiply that; give each its own `<x-dl::year-calendar>` only when needed.

### Notes on inconsistencies found while writing this entry (not fixed)

- Only the Sunday weekday header is coloured as an off day (`$i === 6 && in_array(0, $offDays)`); with `off-days="[0, 6]"` the date cells of Saturdays are red but the Saturday header `Sa` is not.
- The header comment says a marked date opens its mark's `url` and an empty date opens `newUrl`; in code a marked date without its own `url` also falls back to `new-url`.
- The header comment lists the tones `danger|warn|info|success|neutral`; the CSS also defines `off`, which works as a mark tone but is not in the comment.
- A mark counted in the month badge is any key beginning with `YYYY-MM`, even an impossible date such as `2026-02-30`, which is counted but never drawn.
- The legend swatch uses the date-cell classes (`dl-ycal__d--<tone>`) with no separate swatch class for the tone; a new tone must be added to the date-cell rules to get a swatch colour.
- `is-today` comes from the PHP default time zone (`CarbonImmutable::today()`), while the event calendar and the date fields compute "today" in the browser with `data-dl-tz`; if `app.timezone` and `devlib.timezone` differ they can disagree around midnight.

## Example

From the catalog demo `calendar-year` (fictional dates for the holiday names), Sunday off, with a legend and a "new" link:

```blade
<x-dl::year-calendar :year="2026" :off-days="[0]" new-url="/holidays/create?date={date}"
    :legend="['danger' => 'National holiday', 'warn' => 'Collective leave', 'info' => 'Company holiday']"
    :marks="[
        '2026-01-01' => ['label' => 'New Year', 'tone' => 'danger'],
        '2026-03-20' => ['label' => 'Collective leave', 'tone' => 'warn', 'url' => '/holidays/7/edit'],
        '2026-03-21' => ['label' => 'Spring festival', 'tone' => 'danger'],
        '2026-07-10' => ['label' => 'Company anniversary', 'tone' => 'info'],
        '2026-08-17' => ['label' => 'Independence Day', 'tone' => 'danger'],
    ]" />
```

The demo itself uses `new-url="#new={date}"` and more marks; the URLs above (`/holidays/...`) are an assumed project route. Written from `YearCalendar.php` and `devlib.css`; not exercised in a browser.

## Related

- `x-dl::calendar`: editable event calendar with Day / Week / Month views and drag and drop.
- `x-dl::gantt`: resource x hours scheduling board.
- `x-dl::grid`: the list/master screen that normally holds the same holiday records.
- `x-dl::field.date`, `x-dl::field.date-range`: single date or range inside a form.
- `skills/devlib/components.md`: generated prop list.
