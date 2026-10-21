## Overview

`<x-dl::widget-board>` is a board of widgets on a 12-column grid: the opening screen that its owner can arrange. The application owns a catalog of widgets (KPI tiles, charts, target bars, rankings, lists, quotes, long text); the user chooses which ones appear, in which order and how wide. The board only proposes changes: every action (move, resize, remove, add, reset, change period, change dashboard) is posted to one address, the server stores the arrangement and answers with the freshly rendered board, and the screen swaps itself for that HTML. What is on screen is always what the server stored.

Use it when:

- the user may shape the opening screen (Customize, Choose Widgets, Reset to Template);
- the numbers depend on a period (Daily .. Yearly) or the page groups several dashboards (tabs) of which the user owns one.

Pick another object when:

- the arrangement is fixed and per job position: `x-dl::dashboard` without `widgets` (fixed blocks); the same `x-dl::dashboard` also embeds this board when its `board` has the key `widgets`;
- you only need a row of KPI tiles above a report: `x-dl::kpi-row` with `x-dl::kpi`;
- you need one chart or one card: `x-dl::chart`, `x-dl::card`;
- you need a work board with stages and cards: `x-dl::kanban`.

The board reads no table or model: data enters through `widgets` (what is on the board now) and `catalog` (what could be on it). It stores nothing by itself; without `action-url` it is a static display.

Generated markup (simplified):

```html
<div class="dl-wb [is-edit]" data-dl="x-dl::widget-board" data-dl-class="WidgetBoard ← DevlibComponent" data-dl-src="katalog widget + susunan papan per user"
     data-dl-wboard data-config='{"action":"/board","csrf":"..."}'>
  <div class="dl-wb__tabs" role="tablist"> <button class="dl-wb__tab is-active" data-wb-dash="overall">...</button> ... </div>   <!-- only with dashboards -->
  <div class="dl-wb__bar">
    <h2>My Board</h2>
    <div class="dl-dash__seg dl-wb__seg"> <button class="is-active" data-wb-period="monthly">Monthly</button> ... </div>   <!-- only with periods -->
    <div class="dl-wb__tools"> Choose Widgets | Reset to Template | Done | Customize </div>                                    <!-- only when editable -->
  </div>
  <div class="dl-wb__grid">
    <div class="dl-wb__item dl-wb__item--kpi" data-size="3" data-key="revenue" data-dl="widget revenue" data-dl-src="...">
      <div class="dl-wb__edit"> title + icon buttons data-wb-act="left|right|narrower|wider|remove" </div>
      ...the widget (x-dl::kpi, x-dl::chart or x-dl::card)...
    </div>
    <button class="dl-wb__addtile" data-dl-open="wboard-add">Add widget</button>
  </div>
  <div class="dl-mov" data-dl-modal-id="wboard-add"> Choose Widgets: search + checkbox per catalog item, grouped </div>
</div>
```

Extra HTML attributes (`id`, `class`, `style`, `data-*`) go on the root `div`. `can="module.action"` is read by the base class: without the ability nothing is rendered.

## Props

Constructor of `Devlib\Components\WidgetBoard` (12 props). Blade names are kebab-case; use `:prop="expr"` for arrays and booleans.

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `widgets` | `array` | `[]` | The widgets on the board now, in display order (the server's stored arrangement). Shape in "Data and events". An empty array shows the empty state ("No widgets on this board" with a hint to Choose Widgets). |
| `catalog` | `array` | `[]` | Every widget this user may install: `[['key', 'title', 'description', 'group', 'icon', 'type', 'installed' => bool], ...]`. Drives the "Choose Widgets" window (checkbox per item, grouped by `group`, default group `General`). `key`, `title` and `installed` are required in each item; `description`, `group`, `icon` and `type` are optional. Only rendered when the board is editable. Empty = "No widget is available for your account". |
| `action-url` | `?string` | `null` | Address that receives every action as a JSON `POST` and answers with `{html}`; the same address is called with `GET` to reload the board (contract in "Data and events"). Without it the board is static: no Customize, no tabs, no period buttons. |
| `editable` | `bool` | `true` | Lets the user arrange the board. The board is editable only when `editable` is `true` and `action-url` is set; otherwise there are no edit buttons, no add tile, no Choose Widgets window. Period buttons and dashboard tabs still work (they need only `action-url`). |
| `editing` | `bool` | `false` | Starts the board in edit mode (adds `is-edit` on the first render, only when the board is editable). After the first action the screen keeps its own current mode when it swaps in the server HTML, so this prop only matters for the initial load. |
| `title` | `?string` | `null` | Heading in the bar. `null` shows `My Board`. Translated through the dictionary (wrap a data value in `raw()`). |
| `id` | `string` | `'wboard'` | Base id of the Choose Widgets window: `<id>-add`. Must be unique on the page when there are two boards (`x-dl::dashboard` passes `dash-<profile>-board`). |
| `source` | `?string` | `null` | Free text naming where the data comes from, shown by the developer catalog (`data-dl-src`); default `katalog widget + susunan papan per user`. No runtime effect. |
| `periods` | `array` | `[]` | Period buttons in the bar as `[key => label]` (for example `['daily' => 'Daily', 'monthly' => 'Monthly']`). A click sends the action `period` with the key. The labels are translated. Empty (or no `action-url`) = no period buttons; the numbers of each widget must then not depend on a period. |
| `period` | `?string` | `null` | The key of `periods` that is active now (gets `is-active`). The server calculates every widget's numbers for this period. |
| `dashboards` | `array` | `[]` | Tab row above the board: `[['code', 'title', 'icon'], ...]`. Shown only with more than one entry and an `action-url`. A click sends the action `dashboard` with the `code`. `icon` is a Font Awesome class: a value with a space (`fa-regular fa-star`) is used as is, any other value is prefixed with `fa-solid` (default `fa-gauge-high`). `title` is translated. |
| `dashboard` | `?string` | `null` | The `code` of `dashboards` that is active now. |

## Behavior

### Edit mode (Customize)

- **Customize** toggles edit mode in the browser (class `is-edit`); it sends nothing. **Done** leaves it. The mode survives the swaps that follow an action.
- Long press: pressing a widget for 600 ms (not on a button, link or input; any movement or release cancels it) enters edit mode, like a phone home screen.
- In edit mode every widget gets an edit bar with its title and five icon buttons: Move earlier (disabled on the first widget), Move later (disabled on the last), Narrower (disabled at size 3), Wider (disabled at size 12) and Remove from board. Widgets become draggable: dropping a widget on another widget moves it to that widget's place; dropping it on the dashed "Add widget" tile moves it to the end.
- **Reset to Template** (visible only in edit mode) asks for confirmation ("Put the board back to the template of your position? Your own arrangement is removed.") and sends `reset`.
- The width is a span of the 12-column grid: sizes `3, 4, 6, 8, 12` (`WidgetBoard::SIZES`). A missing or other `size` renders as 4. Narrower/Wider step through that list. Below 1100 px sizes 3 and 4 become half width and 6, 8, 12 full width; below 760 px everything is full width.

### Choose Widgets

- Buttons **Choose Widgets** (bar) and the **Add widget** tile open a modal (`<id>-add`, size `xl`) listing the catalog by `group`, each heading showing `installed / total`.
- Each item is a checkbox row (icon, title, description, a small type label KPI / Chart / Target / Ranking / List / Text). Ticking sends `add`, unticking sends `remove`; the change is saved at once and the board is redrawn behind the window, which stays open with its scroll position and search text.
- The search box filters the rows by title and description (case-insensitive, matched against both the translated and the original English title); groups without a visible row are hidden.

### Period and dashboards

- Period buttons (`periods`) and dashboard tabs (`dashboards`) work in view mode too. The active period and dashboard are the server's state: a click is posted, the server stores the choice and renders the board with the new `period` / `dashboard`, calculating the widgets for it. Nothing is remembered in the browser (unlike the fixed blocks of `x-dl::dashboard`, which remember the period in `localStorage`).
- The active dashboard decides which `widgets` the server sends (typically one owned "Overall" dashboard that the user arranges, and template dashboards per department).

### Widget types and rendering

| `type` | Rendered with | Notes |
|---|---|---|
| `kpi` | `x-dl::kpi` | `title` becomes the label. |
| `chart` | `x-dl::chart` | If the sum of the absolute values of all series is 0, a card "No data in this period" replaces the chart. |
| `progress` | `x-dl::card` with achievement rows | One row per target with a progress bar. |
| `ranking` | `x-dl::card` with a table | People or units against their targets. |
| `list` | `x-dl::card` with rows | Also the fallback for an unknown `type`. |
| `text` | `x-dl::card` | Long text, line breaks kept (`white-space: pre-line`), scrolls after 320 px. |
| `quote` | quote block | Text, author and "day n of N". |

Achievement colors (progress and ranking): `actual / target * 100` (or `target / actual * 100` when `good_when` is `down`); 100% or more is `success`, 85% to under 100% is `warn`, below 85% is `danger`. A row without a target shows a neutral "No target" badge and an empty bar. The bar never exceeds 100%.

### Busy state and failures

While a request is running the board has `is-busy` (dimmed, no pointer events) and further clicks are ignored. A failed request (non-2xx, network error) shows a danger toast with the `message` of the JSON answer, or the standard text "The board could not be updated. Reload the page and try again." (`wb_failed`).

### Language and translation

- Translated through the dictionary: `title` (bar), `periods` labels, `dashboards[].title`, widget `title` (in the edit bar and inside the widget), catalog `title`, `description` and `group`, the interface texts of the board, and in `progress` rows `label` and `sub`, in `ranking` `name_label`.
- Also translated, by the card, chart and KPI that draw the widget: `caption` (card description, chart subtitle, KPI hint).
- Not translated (data): `list` rows (`title`, `sub`, `value`), `ranking` rows (`name`, `sub`), `text`, `quote`, the KPI `note`. Wrap data values in `raw()` where they are passed to translated props.
- The type labels in the picker (`KPI`, `Chart`, `Target`, `Ranking`, `List`, `Text`) and the default button label `Open` are fixed English.

### Access and accessibility

- `can="module.action"` hides the whole board. The server must also decide which `catalog` items a user may see and reject `add` of anything else.
- The edit buttons are real buttons with titles. Drag and drop is mouse/touch only; the keyboard path is the edit bar buttons (move earlier/later, narrower/wider, remove). The tab row and the period row have `role="tablist"` but their buttons are not marked `role="tab"` and carry no `aria-selected`; the active state is visual (`is-active`).

## Data and events

### `widgets` item

Common keys of every item:

| Key | Meaning |
|---|---|
| `key` | Required. Unique widget id (also the catalog key); written to `data-key`. |
| `type` | Required. `kpi`, `chart`, `progress`, `ranking`, `list`, `quote` or `text` (see above). |
| `title` | Required. Translated; label of a KPI, title of a chart or card, and the name in the edit bar. |
| `caption` | Optional subtitle (chart `subtitle`, card `description`; for a KPI it is the `hint` when `data.hint` is missing). |
| `icon` | Optional Font Awesome class of the card or KPI (defaults: KPI `fa-chart-line`, chart card `fa-chart-column`, others `fa-square-poll-vertical`). |
| `tone` | Optional KPI tone (default `accent`). |
| `size` | Optional width: `3`, `4` (default), `6`, `8` or `12`. |
| `source` | Optional text naming where the numbers come from (`data-dl-src`; passed to the chart). |
| `open` | Optional button that opens the source screen as a work tab: `['url', 'code' (default: the widget key), 'label' (default `Open`), 'icon' (default `fa-solid fa-chart-pie`), 'title' (tab title, default the widget title)]`. Shown in the card header of `progress`, `ranking`, `list` and `text` widgets only; ignored for `kpi`, `chart` and `quote`. The tab needs the application shell (`x-dl::layer-shell`), which handles `data-dl-tab-url`. |
| `data` | Array that depends on `type`: |

`data` by type:

- `kpi`: `value`, `previous`, `target`, `format` (`number` default, `money`, `percent`), `good_when` (`up` default or `down`), `spark` (list of numbers), `hint`, `note`. See `x-dl::kpi`.
- `chart`: `chart` (chart type, default `bar`; `bar`, `hbar`, `stacked`, `line`, `area`, `combo`, `pie`, `donut`, `funnel`, `heatmap`, `radar`), `labels`, `series` (`[['name', 'data' => [..], 'type' => 'bar'|'line' (combo), 'tone']]`), `format` (`number`, `money`, `percent`), `height` (default `300px`), `legend` (default `true`).
- `progress`: `rows` = `[['label', 'sub', 'actual', 'target', 'format' (number|money|percent), 'good_when', 'icon', 'tone']]`. Money is shown compact (`IDR 1.9 M`).
- `ranking`: `name_label` (first column title, default `Name`), `format` (default `money`, for the whole table), `rows` = `[['name', 'sub', 'target', 'actual']]` (a row may also carry `good_when`). Sort the rows on the server; the board does not.
- `list`: `rows` = `[['title', 'sub', 'value', 'tone']]` (`value` is shown as a badge of `tone`, default `neutral`, and is not translated), `empty` (text when no rows, default `Nothing to show`).
- `quote`: `text`, `author`, `day` (day number, shown big), `days` (default 365).
- `text`: `text` (long text, escaped, line breaks kept), `empty` (text when blank).

### `catalog` item

`['key' => 'revenue', 'title' => 'Company Revenue', 'description' => 'One number with trend and target.', 'group' => 'Sales', 'icon' => 'fa-sack-dollar', 'type' => 'kpi', 'installed' => true]`. `installed` must be a boolean: it ticks the checkbox and feeds the `n / total` counter. `type` only adds the small type label in the picker.

### Request contract (`action-url`)

Every action is sent as:

```
POST <action-url>
Content-Type: application/json
Accept: application/json
X-CSRF-TOKEN: <token from data-config, or window.DL_CSRF>
credentials: same-origin

{"action": "<name>", "key": "<widget key | period key | dashboard code | null>"}
```

| `action` | `key` | Extra | Sent by |
|---|---|---|---|
| `period` | period key | | period button (not when already active) |
| `dashboard` | dashboard `code` | | dashboard tab (not when already active) |
| `add` | catalog key | | ticking an item in Choose Widgets |
| `remove` | widget key | | unticking an item, or the remove button in the edit bar |
| `left`, `right` | widget key | | Move earlier / Move later |
| `narrower`, `wider` | widget key | | Narrower / Wider (one step in `3, 4, 6, 8, 12`) |
| `move` | dragged widget key | `"to"`: key of the widget it was dropped on, or `null` when dropped on the add tile (= end) | drag and drop |
| `reset` | `null` (see the notes below) | | Reset to Template |

The same address is also called with `GET` and no body to reload the board (see `dl:refresh`).

Answer: `200` with JSON `{"html": "<the full board HTML>"}`. The HTML must contain the board root (`data-dl-wboard`), that is the same markup the page renders, built again from the stored state. The script finds that element, swaps it in, keeps the edit mode and an open Choose Widgets window, and mounts the new scripts. If the HTML has no board root nothing changes (silently). Any non-2xx status is a failure: JSON `{"message": "..."}` is shown in the toast.

The server decides everything: validate that the widget is in the user's catalog, clamp sizes, ignore unknown actions, store the arrangement per user (in an application: catalog table plus per-user arrangement table) and render. The screen never keeps a layout the server did not confirm.

### Events

- The board listens on `document` for `dl:refresh` with `detail === 'board'` and then reloads itself with a `GET` to `action-url` (for example after the assistant installed a widget: the assistant drawer of `x-dl::layer-shell` dispatches `new CustomEvent('dl:refresh', {detail: 'board'})` when a reply carries `refresh: 'board'`). Every board on the page reacts.
- The board dispatches no event of its own and has no public JS API. `el.dlComponent` is the `WidgetBoard` instance (methods `send(body)`, `swap(html)`, `draggable()`); after a swap the element and the instance are new.

## Rules and pitfalls

- Give each board its own `id` when a page has more than one; the Choose Widgets window id is `<id>-add`.
- The numbers belong to the server: the widgets you send must already be calculated for the active `period`. The board does not convert periods.
- Keep the answer fast and idempotent: an action is retried only by the user, and clicks during a request are dropped.
- Return the same fragment on `GET` as on `POST`; it is also used for `dl:refresh`.
- Always send `installed` as a boolean and never put a widget in `widgets` that is not in the user's `catalog`.
- A widget without `key`, `type` or `title` (or a catalog item without `key`, `title` or `installed`) raises an undefined-key error while rendering. An unknown `type` is drawn as a `list`.
- Format money and numbers in `data` as numbers, not strings: the board and KPI/chart format them (`IDR 1.9 M`).
- Do not hide the edit bar buttons with CSS: they are the keyboard alternative to drag and drop.
- Reset only the arrangement. Do not delete the user's data when handling `reset`.
- Lint: no board-specific rule. The general rules L001 (raw HTML tags), L002 (inline `<script>`) and L003 (hex colors) apply to the Blade screen that holds the board.

### Notes on inconsistencies found while writing this entry (not fixed)

- The header comment of `src/Components/WidgetBoard.php` says the body is `{action, key}`; the `move` action also sends `to` (`resources/assets/js/components/widget-board.js:42`), and the demo `BoardDemo::apply()` reads it with `request('to')`.
- `widget-board.js:18` still handles clicks on `[data-wb-add]` (action `add`), but no markup produced by the view has that attribute since the picker became checkboxes (`data-wb-toggle`); the handler is dead code.
- `reset` is sent with `key = act.closest('[data-key]')?.dataset.key` (`widget-board.js:21`). The Reset button is not inside a widget, so inside `x-dl::dashboard` the nearest ancestor with `data-key` is the dashboard root and the key sent is `dash-<profile>`. A server that ignores `key` for `reset` (as `BoardDemo` does) is not affected.
- The `open` array also reads a `title` key (used for the tab title) that the header comment does not list, and `open` is silently ignored for `kpi`, `chart` and `quote` widgets.
- The header comment omits `data.note` for KPI widgets, `data.legend` for charts, `good_when` on ranking rows, and the catalog keys `type` (picker label) and the required `installed`.
- `editing` is read only by the first render: later swaps copy the current browser mode (`widget-board.js:78`), so a server that returns `editing: true` on every answer does not force edit mode.
- `role="tablist"` is set on the dashboard tab row and period row without `role="tab"` / `aria-selected` on the buttons.
- The `dl:refresh` listener is added on `document` and removed only when the board swaps itself; a board removed from the page by other code keeps a listener until the next event (`widget-board.js:58` and `:87`; it checks `document.contains` before acting).

## Example

Adapted from the catalog demo `widget-board` (fictional data; the demo keeps the arrangement in the session):

```blade
@php($demo = \App\Support\BoardDemo::props())
<x-dl::widget-board
    :widgets="$demo['widgets']"
    :catalog="$demo['catalog']"
    :periods="$demo['periods']" :period="$demo['period']"
    :action-url="route('board.demo')"
    title="Company KPI"
    source="App\Support\BoardDemo (sample, arrangement in session)" />
```

What `widgets` and `catalog` look like (shortened from `BoardDemo`):

```php
$widgets = [
    ['key' => 'revenue', 'title' => 'Company Revenue', 'type' => 'kpi', 'size' => 3, 'icon' => 'fa-building', 'tone' => 'accent',
     'data' => ['value' => 1_720_000_000, 'previous' => 1_540_000_000, 'target' => 2_500_000_000, 'format' => 'money', 'spark' => [1.2e9, 1.4e9, 1.7e9]]],
    ['key' => 'trend', 'title' => 'Revenue: Target vs Actual', 'caption' => 'Bars = actual, line = target', 'type' => 'chart', 'size' => 8,
     'data' => ['chart' => 'combo', 'format' => 'money', 'height' => '320px', 'labels' => ['Jul', 'Aug', 'Sep'],
                'series' => [['name' => 'Actual', 'data' => [1.2e9, 1.4e9, 1.7e9], 'type' => 'bar', 'tone' => 'accent'],
                             ['name' => 'Target', 'data' => [1.5e9, 1.5e9, 1.6e9], 'type' => 'line', 'tone' => 'danger']]]],
    ['key' => 'targets', 'title' => 'Targets by Area', 'type' => 'progress', 'size' => 4, 'icon' => 'fa-bullseye',
     'data' => ['rows' => [['label' => 'Sales', 'sub' => 'Confirmed orders', 'actual' => 1.72e9, 'target' => 2.5e9, 'format' => 'money', 'icon' => 'fa-sack-dollar', 'tone' => 'accent']]]],
    ['key' => 'jobdesc', 'title' => 'My Job Description', 'type' => 'text', 'size' => 6,
     'data' => ['text' => "1. Lead the daily operation.\n2. Approve purchase orders.", 'empty' => 'Nothing to show']],
];
$catalog = [
    ['key' => 'revenue', 'title' => 'Company Revenue', 'description' => 'One number with trend and target.', 'group' => 'Sales', 'icon' => 'fa-sack-dollar', 'type' => 'kpi', 'installed' => true],
    ['key' => 'trend', 'title' => 'Revenue: Target vs Actual', 'description' => 'Bars = actual, line = target, per month.', 'group' => 'Sales', 'icon' => 'fa-chart-column', 'type' => 'chart', 'installed' => true],
];
```

Server side (sketch; the demo route does exactly this with `BoardDemo`):

```php
Route::match(['get', 'post'], '/board', function (Request $r) {
    if ($r->isMethod('post')) {
        BoardService::apply($r->user(), $r->input('action'), $r->input('key'), $r->input('to')); // validate, store per user
    }

    return ['html' => view('board.fragment', BoardService::props($r->user()))->render()]; // the same <x-dl::widget-board ...> markup
});
```

Read-only board with period buttons and dashboard tabs:

```blade
<x-dl::widget-board :widgets="$widgets" :periods="['weekly' => 'Weekly', 'monthly' => 'Monthly']" period="monthly"
    :dashboards="[['code' => 'sales', 'title' => 'Sales', 'icon' => 'fa-bag-shopping'], ['code' => 'finance', 'title' => 'Finance', 'icon' => 'fa-coins']]" dashboard="sales"
    :editable="false" action-url="/board" />
```

## Related

- `x-dl::dashboard`: the opening screen that embeds this board when its `board` carries `widgets` (it passes `board_url`, `board_editable`, `board_source`, `board_periods`, `board_period`, `board_dashboards`, `board_dashboard`); without them it draws fixed blocks.
- `x-dl::kpi`, `x-dl::chart`, `x-dl::card`, `x-dl::progress`, `x-dl::badge`, `x-dl::avatar`, `x-dl::empty-state`: the objects the widgets are drawn with.
- `x-dl::modal`: the Choose Widgets window.
- `x-dl::layer-shell`: opens the `open` button as a work tab and dispatches `dl:refresh` from the assistant drawer.
- `x-dl::kpi-row`: a plain row of KPI tiles when no arrangement is needed.
- `x-dl::kanban`: a board of cards moved by people, not of widgets arranged by the owner.
