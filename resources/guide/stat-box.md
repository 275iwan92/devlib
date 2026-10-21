## Overview

`<x-dl::stat-box>` is a compact statistic tile: a small label, a large value and an optional small line under it, with a colored left border for the tone. In its plain form it is a `div` that only shows a number. With `filter` it becomes a `<button>` that works as an on/off filter: click to switch it on, click again to switch it off, one active tile per group. The tile only announces the click (`dl:stat`); something else must filter the data.

Use it for counts above a list ("Waiting approval 37") and for summary rows. Pick another object when:

- the figure needs a trend, change or target: `x-dl::kpi`;
- the tile describes a thing with a status (machine, document, service): `x-dl::status-card`;
- you only need status filters above a grid: pass `stats` to `x-dl::grid` (the grid draws its own filter bar and does the filtering);
- you need a dial or a verdict lamp: `x-dl::gauge`, `x-dl::traffic-light`.

### Generated markup

Plain:

```html
<div class="dl-stat dl-stat--warn" data-dl="x-dl::stat-box tone=warn" data-dl-class="StatBox ← DevlibComponent" data-dl-src="—">
  <span class="dl-stat__label">Waiting approval</span><span class="dl-stat__value">37</span><span class="dl-stat__sub">optional line</span>
</div>
```

With `filter` (and `active`):

```html
<button type="button" class="dl-stat dl-stat--warn is-filter is-active" data-dl="x-dl::stat-box tone=warn" data-dl-stat> ...same three spans... </button>
```

The `<span class="dl-stat__sub">` exists only when `sub` is not empty.

## Props

Constructor of `Devlib\Components\StatBox` (6 props, none inherited). Blade names are kebab-case; booleans can be bare attributes (`filter`) or `:filter="$expr"`.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `label` | `string`, `''` | Small caption above the value. Translated through the dictionary (wrap database text in `raw()`). |
| `value` | `string`, `'0'` | The big figure, printed as given. It is typed `string` and printed exactly as given (no thousand separators, no abbreviation), so pass a string and format it yourself (`Locale::number(1284)` gives `1.284` or `1,284`). Not translated, not formatted. Non-scalar values fail. |
| `tone` | `string`, `'accent'` | Color of the left border: `accent` (default), `success`, `warn`, `danger`, `neutral`, `violet`. Written as `dl-stat--<tone>`. `accent`, and any unknown value such as `info`, keep the default blue border (there is no rule for `info`). Not a text color. |
| `sub` | `?string`, `null` | Small grey line under the value (a note or breakdown). Not translated; use `Lang::raw()` for data, or translate it yourself. `null` or `''` removes the line. |
| `filter` | `bool`, `false` | `true` renders a `<button type="button">` with `is-filter` (pointer cursor, hover) and `data-dl-stat`, which the `Stat` script turns into an on/off toggle. `false` renders a plain `div`. |
| `active` | `bool`, `false` | Initial state only: adds `is-active` (soft background and ring). The script then changes it on every click. Only meaningful with `filter`, but it is drawn even without it. |

Every DevLib component also accepts `can="module.action"` (without the ability nothing is rendered) and extra HTML attributes (`id`, `class`, `style`, `data-*`, `title`, `data-where`), merged on the root element; `class` is appended to the generated classes.

## Behavior

- Layout: a column, `min-width: 150px`, left border 4px in the tone color. Inside `.dl-grid__stats` (flex, wraps; see `x-dl::kpi-row`) every tile grows from 150px. In a `dl-demo dl-demo--row` or any flex/grid container tiles sit side by side.
- Hover and active: only `is-filter` tiles react (hover background; `is-active` gives the accent-soft background and a ring).
- Toggle (script `Stat`, `resources/assets/js/components/stat.js`): a click removes `is-active` from every `[data-dl-stat]` in the same parent element, then sets it on the clicked tile if it was off (a second click leaves all off). The group is the tile's parent element: put tiles of one filter set in one container, and do not mix other `data-dl-stat` objects (for example `x-dl::status-card` with `filter`) into the same container unless they should form one group.
- Keyboard: a real `button`, so Tab, Enter and Space work. The non-filter `div` is not focusable.
- Theme: all colors are CSS variables (light and dark follow the site).
- Language: only `label` is translated.

### Accessibility as implemented

No `aria-*` anywhere. The filter state is only the class `is-active` (no `aria-pressed`), and the tone is only color. A screen reader reads label, value and sub in order. Add `title` or `aria-pressed` handling in the project if required.

## Data and events

- Props only; no slot, no data access (`source()` is `—`).
- A filter tile posts nothing. On click it dispatches a bubbling `dl:stat` with `detail.active` (`true` = just switched on, `false` = just switched off) on the tile itself; listen on a parent: `list.addEventListener('dl:stat', e => { const tile = e.target.closest('[data-dl-stat]'); ... })`. Put your own data on the tile with extra attributes (`data-status="waiting"`) and read it from `tile.dataset`.
- Built-in consumer: `x-dl::sheet` renders its own tiles from its `stats` prop with `filter` and `data-where='{"column":["value"]}'` (a JSON object); the sheet listens to `dl:stat`, reads `data-where` of the tile and filters its rows (`resources/assets/js/components/sheet.js:69`), and rewrites the tile counts. A standalone `data-where` is read by nothing else.
- Hooks: `.dl-stat`, `.dl-stat__label`, `.dl-stat__value`, `.dl-stat__sub`, `[data-dl-stat]`, `.is-filter`, `.is-active`. The root `el.dlComponent` is the `Stat` instance (only for filter tiles).

## Rules and pitfalls

- Only tiles that really filter something get `filter`. A static aggregate (a money total) stays a plain tile: the demo marks it "not clickable (aggregate)".
- `value` and `sub` are never translated or formatted. Format numbers on the server with `Locale::number()` / `Locale::money()`, and wrap data in `Lang::raw()` when put in `label`.
- `active` does not survive a page reload by itself: the server must render `:active` again from its own state, or the script must be driven by your listener.
- `tone` has no `info` rule on this object: it looks the same as `accent` (and so does an unknown or misspelled tone).
- A tile does not count or filter by itself: the page or the object that listens to `dl:stat` must do the work and must also update `value`.
- No lint rule targets this object.
- Code oddity (not fixed): the valid tones are not named in the class docblock (`src/Components/StatBox.php:15`) and are spread over two CSS places (`resources/assets/devlib.css:730` for `success`, `warn`, `danger`, `neutral` and `:2177` for `violet`).
- Code oddity (not fixed): `dl:stat` carries no value or name, only `detail.active`, so a standalone listener must identify the tile through its own `data-*` attribute.

## Example

Copied in shape from the catalog demo `stat-box` (fictional data).

```blade
<div class="dl-demo dl-demo--row">
    <x-dl::stat-box label="Total PO" value="1.284" tone="accent" filter />
    <x-dl::stat-box label="Waiting approval" value="37" tone="warn" filter />
    <x-dl::stat-box label="Approved / in process" value="1.198" tone="success" filter />
    <x-dl::stat-box label="Rejected / cancelled" value="49" tone="danger" filter :active="$status === 'rejected'" />
    <x-dl::stat-box label="Value this month" value="IDR 2,4 B" tone="neutral" sub="not clickable (aggregate)" />
</div>
```

Server-formatted values and a listener:

```blade
<div id="po-stats" class="dl-grid__stats">
    <x-dl::stat-box label="Open" :value="\Devlib\Locale::number($open)" tone="warn" filter data-status="open" />
    <x-dl::stat-box label="Closed" :value="\Devlib\Locale::number($closed)" tone="success" filter data-status="closed" />
</div>
<script>
    document.getElementById('po-stats').addEventListener('dl:stat', e => {
        const tile = e.target.closest('[data-dl-stat]');
        applyFilter(e.detail.active ? tile.dataset.status : null);   // your function
    });
</script>
```

## Related

- `x-dl::kpi`: card with change, sparkline and target for dashboard numbers; `x-dl::kpi-row` wraps a row of them in the same `.dl-grid__stats` container.
- `x-dl::status-card`: the same clickable-filter behavior (`filter`, `active`, `dl:stat`) for cards with icon and status badge.
- `x-dl::sheet`, `x-dl::grid`: lists that already build and consume stat tiles (`stats` prop).
- `x-dl::gauge`, `x-dl::traffic-light`: other scorecard indicators.
- `skills/devlib/components.md`: generated prop list of every object.
