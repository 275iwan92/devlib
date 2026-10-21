## Overview

`<x-dl::gantt>` is the resource x time board of DevLib: one lane per resource (machine, crew, person), blocks for the tasks planned on it, a time axis in hours, and a toolbar with filters, a Simulation | Live switch and a Submit button. Planners drag a block to change its start (or drop it on another resource), drag its right edge to change the duration, click it for a short detail card and double-click it to choose another resource or process. The screen only declares data; the behaviour is in `gantt.js`.

It has three working styles that are chosen by props:

1. **Simple board** (no URLs): changes are made on the in-page copy and announced with `dl:gantt`; **Submit** asks for confirmation and announces `dl:gantt-submit` with the changed tasks. Nothing is sent to the server by the component.
2. **Scheduling board with server checks** (`impact-url`, optional `move-url`, `lock-until`, `origin-date`, `now-at`): every drop is first judged by the server (the "impact" panel), and an accepted move can be posted to the server together with a reason. This is the production scheduling board.
3. **Linked board** (`linked`): the toolbar becomes Process then Machine dropdowns (only machines able to run the process), and the time axis is fitted automatically to the bars shown.

Use it for production or crew scheduling where resource and hours matter. Pick another object when:

- You only need the read-only queue per machine: `x-dl::queue-board`.
- Items go through stages, not hours: `x-dl::kanban`.
- Items are dated appointments: `x-dl::calendar`; whole-year holidays: `x-dl::year-calendar`.
- You need a list with sort, paging and export: `x-dl::grid`.

### Layout

Root `<div class="dl-gantt" data-dl-gantt data-config="..." data-mode="sim|live" data-extras="1|0">`. From top to bottom: toolbar (`dl-gantt__bar`), the four number cards (`dl-gantt__stats`), the colour legend (`dl-gantt__legend`), the active filter chips (`dl-grid__chips`), the board (`dl-gantt__board`, horizontally scrollable) and a help line. The resource column is 190px wide (120px under 640px viewport width).

## Props

Constructor of `Devlib\Components\Gantt` (27 props; nothing is inherited from a field). Blade names are kebab-case: `pxPerHour` is `px-per-hour`, `idleHours` is `idle-hours`, `impactUrl` is `impact-url`, and so on. Arrays, numbers and booleans need `:prop="expr"`.

### Data

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `rows` | `array` | `[]` | The resources, top to bottom (shape below). |
| `tasks` | `array` | `[]` | The blocks (shape below). |
| `categories` | `array` | `[]` | `[processKey => ['label' => .., 'tone' => ..]]`: colour of blocks (by the task's `process`), the legend, and the options of the Process dropdown/filter. Sent to the script as a JSON object. |
| `unscheduled` | `int` | `0` | The number shown on the "Not Scheduled" card (tasks the server could not place). Only a number; the board does not list them. |

### Time axis

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `start` | `int` | `8` | First hour of the axis (hour offset: `8` = 08:00). |
| `end` | `int` | `20` | Last hour of the axis. With `origin-date` the hours may exceed 24 (`6` to `54` = 06:00 of day 0 to 06:00 of day 2). Blocks cannot be dragged outside `start`..`end`; a block already outside is simply drawn outside the board (not clipped). |
| `px-per-hour` | `int` | `70` | Width of one hour in pixels. Hour labels are thinned so they are at least 36px apart. Overridden in `linked` mode (axis fit). |
| `snap` | `int` | `15` | Minutes: drag and resize snap to this grid; the minimum duration is one snap. |
| `origin-date` | `?string` | `null` | `'YYYY-MM-DD'` = the date that hour 0 means. Turns on the multi-day axis: day strips with weekday names, vertical day separators, `Thu 08:30` style time labels, hour tick labels modulo 24, and the "now" line is computed against that date. `null` = a single-day axis with plain `HH:MM`. |
| `lock-until` | `int\|float\|string\|null` | `null` | End of the lock window, as an hour offset (`16`, `16.5`) or `'HH:MM'` (`'16:30'`). Tasks that start before it cannot be dragged or resized, a shaded area and a vertical "Locked until ..." line mark the window, and no task can be dropped earlier than it. Exceptions: a task with `'movable' => true` or `'state' => 'hot'`. `null`/`''` = no lock. |
| `now-at` | `?float` | `null` | Position of the red "now" line as an hour offset sent by the server (use this when the board runs in a simulated or fixed time). `null` = the browser's current time converted to the application time zone (`data-dl-tz`, default `Asia/Jakarta`); with `origin-date` the day difference is added. The line is drawn only when it falls inside the axis. |
| `idle-hours` | `float` | `2` | Threshold for the "Resource Idle > n hours" card: a resource counts when it has a gap between tasks of at least this many hours. (Gaps of one hour or more are always drawn as a dotted "idle" strip.) |

### Mode and toolbar

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `simulation` | `bool` | `true` | Starting mode. `true` = Simulation (blocks can be edited); `false` = Live (view only: no drag, no double-click, click opens the detail card; Submit is disabled). |
| `mode-switch` | `bool` | `true` | `false` hides the Simulation / Live switch (the board stays in the mode given by `simulation`). |
| `submit-label` | `?string` | `'Submit to Master Schedule'` | Text of the Submit button (preset `submit`). `null` or `''` removes the button. The button is disabled until at least one task has changed, and in Live mode. Not run through the dictionary: pass a translated text if needed. |
| `resource-label` | `string` | `'Machine / Crew'` | Heading of the resource column. Translated. |
| `extras` | `bool` | `true` | `false` hides the four number cards, the colour legend and the filter chips; the toolbar dropdowns remain the only filter. |
| `draft-label` | `?string` | `null` | When not `null` an amber badge (`dl-gantt__draft`) is shown in the toolbar with this text, for example `DRAFT 0 change`. Update it with `el.dlGantt.setDraft(text)` or by returning `draft` from `move-url`. Not translated. |
| `bulk-label` | `?string` | `null` | When set, a toolbar button with this text (icon `fa-check-double`) that applies all Draft bars on the process/machine on show (see Bulk). |
| `bulk-url` | `?string` | `null` | URL the bulk button posts to. While empty the bulk button is rendered disabled (for example until a Process has been chosen on the page); the URL is read once at render, so enable it by re-rendering. |
| `linked` | `?array` | `null` | Turns on the linked mode (see below). |
| `source` | `?string` | `null` | Free text naming the data source for the developer catalog / Manifest; default `jadwal resource`. No runtime effect. |

### Server checks and texts

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `impact-url` | `?string` | `null` | Endpoint that judges a move before it is accepted (contract below). `null` = every change is committed at once, without a panel. |
| `move-url` | `?string` | `null` | Endpoint that receives the accepted move (contract below). Requires `impact-url` to be reached (the accept button only exists in the impact panel). `null` = the accepted move is only applied in the browser. |
| `reason-required` | `bool` | `false` | Shows a free-text reason field (max 190 characters) in the impact panel. It is mandatory only when `move-url` is set: the accept button then refuses an empty reason and the text is sent to `move-url`. Without `move-url` the field is shown but ignored. |
| `move-label` | `?string` | `null` | Text of the accept button in the impact panel; default is "Move into Draft". Translated. |
| `empty-text` | `?string` | `null` | Shown instead of "No resource matches the filter" when the board has no task at all. Translated. |

Slot: `<x-slot:actions>` places extra toolbar content (for example a "Hot job" button) just before the Submit button.

Common attributes: extra HTML attributes (`id`, `class`, `style`, `data-*`) are merged onto the root; `can="module.action"` hides the whole board when the user lacks the ability.

### The `rows` array

| Key | Meaning |
|---|---|
| `key` | Required. Unique resource key (string); referenced by `tasks[].row`, `eligible`, `linked.machines`. |
| `label` | Required. Name shown in the resource column. |
| `section` | Optional. Rows with the same consecutive `section` text are grouped under a separator line (for example `PRINTING`, `SUBCON`). In the Resource editor the first word is appended in brackets. |
| `down` | Optional. Downtime windows `[['from' => 34, 'to' => 38, 'label' => 'Maintenance']]` (hours or `'HH:MM'`); drawn as hatched areas on the lane. They are informational: the script does not stop a drop there (the server's impact check should). |
| `load` | Optional. `0` to `100`: a small load bar and percentage under the name (amber from 75, red from 90). |
| `bottleneck` | Optional. `true` adds a "Bottleneck" tag. |
| `ghostOf` | Optional. In `linked` mode, a row whose `ghostOf` equals the machine chosen in the dropdown is shown as well (a context lane of that machine). |

A `sub` key (a sub-title such as the crew) appears in the demos but is not displayed by the script.

### The `tasks` array

| Key | Meaning |
|---|---|
| `id` | Required. Unique id (int or string), sent to the server and in events. |
| `row` | Required. Key of the resource the task is on. |
| `label` | Required. Name shown on the block (for example the work order number); also the "Code" in the editor. |
| `process` | Key of `categories`: block colour, legend, process filter. An unknown key shows the key itself as the label and an `accent` colour. |
| `start`, `end` | `'HH:MM'` or a number of hours; may exceed 24 with `origin-date` (`'26:00'`). If `end` is missing, `duration` (hours, default 1) is used. |
| `progress` | `0` to `100`: the thin progress bar at the bottom of the block (and the percentage in the detail card). |
| `status` | Free text shown as "Status" in the detail card (default: Scheduled, Hot Job, QC Hold or Breakdown according to `state`). |
| `state` | `hot` (flame, red ring, may move inside the lock window), `qchold` (always locked; dashed border), `breakdown` (red colour regardless of `process`). |
| `risk` | `true` counts the task on the "At Risk of Delay" card and in its highlight. |
| `muted` | `true` greys the block (context only). |
| `locked` | `true` makes the block non-draggable (lock icon). |
| `movable` | `true` exempts the task from `lock-until` and tags wide blocks `DRAFT`. |
| `live` | `true` tags wide blocks `LIVE`, adds `is-live` styling and excludes the task from the bulk action. |
| `setup` | Minutes of changeover at the start of the block (hatched part, `S 45` label when wide enough). |
| `gates` | Three readiness lights in the order material, tooling, artwork; each `ok`, `wait` or `block`. `block` makes the border red and `wait` amber; tooltips name each gate. |
| `forecastEnd` | Hour (or `'HH:MM'`) the server expects the task to really finish; if later than `end`, an "over" tail (minutes or hours) is drawn after the block. |
| `eligible` | Array of row keys this task may run on. A drop on another row is refused ("This machine cannot run this process"). Missing = any row. |
| `short` | Short identity used on narrow blocks (under 150px) and as the work-order key in the bulk list. |
| `detail` | Tooltip text of the block (default `label`). |
| `clash`, `ghost`, `queued`, `mine` | Flags that add the CSS classes `is-clash`, `is-ghost`, `is-queued`, `is-mine` (visual markers decided by the server). |

Other keys are kept and returned in events but not used.

### The `categories` tones

`tone` becomes the class `dl-gantt--<tone>`; the stylesheet defines `accent`, `violet`, `warn`, `success` and `info` (and `breakdown`, used for `state = breakdown`). Another name (for example `danger` or `neutral`) has no colour definition and the block would be drawn without a background.

### The `linked` array

```php
['processes' => ['print' => 'Printing', 'cut' => 'Die-cutting'],       // processKey => label
 'machines'  => ['print' => ['sm74', 'cd102'], 'cut' => ['dc']],        // processKey => [row keys able to run it]
 'defaults'  => ['print' => 'sm74'],                                    // processKey => machine preselected when the process is chosen (optional)
 'process'   => 'print',                                                // process selected on load ('' = All processes)
 'machine'   => 'sm74']                                                 // machine filter on load (optional, see notes)
```

The constructor adds `'' => 'All processes'` to `processes` and `'' => [all row keys]` to `machines` unless `''` is already a process key. The toolbar then has a Process select and one Machine select per process (the one of the chosen process is visible, each starting with "All machines"). Choosing a process presets the machine to `defaults[process]` (or all). The board shows only the rows of the chosen process/machine, with ALL tasks of those machines (not only those of the process), and the time axis is recalculated at every draw to fit them: start = lower bound rounded to a 6-hour step (at least 2 hours margin before the first bar), end = upper bound plus 4 hours rounded up, minimum 24 hours, pixels per hour between 14 and 60 so the lanes fit the width. `start`, `end` and `px-per-hour` are therefore only used when no task is on the machines shown. In this mode the double-click editor lists only machines the task may run on and hides the process choice.

## Behavior

### Interacting with blocks (Simulation mode)

- **Drag** a block: moves it in time (snapped to `snap` minutes, kept inside the axis and after `lock-until`) and, vertically, to another resource lane. The target lane is found by geometry; while dragging, lanes the task may use are tinted green and the others red. A drop on a lane the task may not use is cancelled with a warning. Movement under 3px counts as a click.
- **Right edge** (`dl-gantt__rz`): changes the end time; minimum duration one snap.
- **Collisions**: on a drop (not on a resize) a block that lands on another block of the same lane slides to the first free place after it (never before the lock). Blocks that still overlap get the `is-conflict` marker and a toast.
- **Click**: opens the detail card (resource, process, time, setup, forecast, progress, status).
- **Double-click**: opens the editor with Resource (and Process, unless `linked`) selects; Save re-assigns the task.
- **Locked blocks** (QC Hold, `locked`, inside the lock window): cannot be started; a drag attempt shows "This task is locked" (or "QC Hold is locked until QA decides"); a click still opens the detail card.
- **Live mode**: no editing; click opens the detail card.
- Changed tasks get `is-changed` until Submit. Hot (`state = hot`) tasks and `movable` ones may be moved inside the lock window.

### Cards, legend, filters

- Number cards (buttons): Active Tasks (total; click resets the highlight), Not Scheduled (`unscheduled`), At Risk of Delay (tasks with `risk`), Resource Idle > `idle-hours` (resources with a big gap). The last two highlight matching blocks and dim the rest; click again to clear.
- Legend: one swatch per category, then fixed entries for Breakdown, QC Hold, Hot Job and the progress bar.
- Toolbar filters (simple board): Resource and Process selects. The Process filter shows only the resources that have a task of that process and only those tasks. Active filters appear as removable chips with a "reset" link (hidden when `extras` is false). In `linked` mode the Process/Machine selects are the filter; the chips are drawn for them too.

### The impact check (with `impact-url`)

1. The drop is kept in the browser (block drawn dashed "checking") and the board is locked against other edits.
2. The browser POSTs to `impact-url`. If the answer is not `ok`, the block returns to where it was and a warning toast shows `reason`.
3. If `ok`, the answer may correct the position (`row`, `start`, `end`), may list other tasks that would move (`moves`, drawn outlined at their new place with a ghost at the old place, and announced to tables on the page with `dl:plan-sync`), and `lines` are printed in the "Impact of this move" panel under the block.
4. The user presses the accept button (default "Move into Draft") or Cancel / Escape (the block and previews return to the old place). With `reason-required` and `move-url` the reason must be filled first.
5. With `move-url`, the browser POSTs the move; failure reverts, success updates the draft badge, optionally reloads the page, and then `dl:gantt` is emitted (`type` `move`, `resize` or `reassign`). Without `move-url`, the accept button only applies the move locally and emits `dl:gantt`.

Without `impact-url` every change is applied and `dl:gantt` is emitted immediately. Double-click re-assignments go through the same check.

### Submit

Submit asks "Send n simulation changes to the Master Schedule?"; on yes it emits `dl:gantt-submit`, shows a toast, clears the changed set and redraws. The component has no submit URL: the page's listener must post the tasks.

### Bulk (with `bulk-label`)

The button lists the work orders (task `short`, else `label`) that have a task on the machines shown (linked: the chosen process/machine; otherwise all rows), matching the Process filter, not `locked` and not `live`. With none it warns. Otherwise it asks for confirmation (count and list), POSTs to `bulk-url`, shows the per-work-order results in a dialog and reloads the page.

### Language, time zone, access

Most texts use `Lang` keys `gt_*`; see the notes for the ones that are fixed English. Day names use `id-ID` when the display language is Indonesian, otherwise `en-GB`. "Now" uses `data-dl-tz` (default `Asia/Jakarta`) unless `now-at` is given. The object belongs to the `grid` dictionary group. `can` hides the entire board.

### Keyboard and accessibility

Dragging uses pointer events only; blocks are not focusable and have no keyboard equivalent, except that Escape cancels a pending impact panel; the editor dialog and the panel buttons are ordinary form controls. The detail card has a labelled close button; the editor is a `role="dialog" aria-modal="true"` dialog. Status is shown by colour plus text and icons (lock, flame, magnifier), and the gate lights have tooltips, but there is no screen-reader model of the lanes. Plan for a read-only list (`x-dl::queue-board` or a grid) next to the board for users who cannot drag.

## Data and events

### data-config (JSON on `data-config`, produced by `config()`)

`rows`, `tasks`, `categories` (object), `start`, `end`, `px` (= `px-per-hour`), `snap`, `idle` (= `idle-hours`), `unscheduled`, `resourceLabel` (translated), `originDate`, `lockUntil`, `nowAt`, `impactUrl`, `moveUrl`, `reasonRequired`, `linked`, `emptyText` (translated or null), `moveLabel` (translated or null), `bulkUrl`. Not in the config but on the root: `data-mode` (`sim` / `live`, from `simulation`) and `data-extras` (`1` / `0`).

### Hooks

`data-g2-mode` (switch), `data-g2-bulk`, `data-g2-draft`, `data-g2-submit`, `data-g2-stats`, `data-g2-hi="all|none|risk|idle"` (cards), `data-g2-legend`, `data-g2-chips`, `data-g2-chip="res|proc|all"`, `data-g2-board`, `data-g2-row="<row key>"`, `data-g2-task="<task id>"`, `data-g2-rz` (resize handle), `data-g2-fc="<task id>"` (forecast tail), `data-g2-mach="<process key>"` (linked Machine select wrapper), `data-g2-modehelp`. Selects are named `gantt_resource` and `gantt_process` (simple board) or `gantt_process` and `gantt_machine_<index>` (linked). Block classes: `dl-gantt__blk dl-gantt--<tone>` plus state classes `is-hot`, `is-qchold`, `is-conflict`, `is-dim`, `is-match`, `is-changed`, `is-locked`, `has-gates`, `has-gate-wait`, `has-gate-block`, `is-clash`, `is-previewed`, `is-ghost`, `is-queued`, `is-mine`, `is-live`, `is-checking`, `is-pending`.

### Events (on the root, bubbling, not cancelable)

| Event | Detail |
|---|---|
| `dl:gantt` | `{type: 'move' \| 'resize' \| 'reassign', task: {...task, start: 'HH:MM', end: 'HH:MM'}}`. `task` is the task object with its current `row`, `process`, `start`, `end` (hours may be 24 or more with an origin date) plus the internal numeric fields `s`, `e`, `over`. Fired when a change is committed (immediately without `impact-url`; after acceptance with it). `reassign` = moved to another row or process (drag to another lane or editor). |
| `dl:gantt-submit` | `{tasks: [...]}`: the tasks changed since the last submit (same shape as above). |
| `dl:plan-sync` | `{id, cells}`: a preview moved the task `id` and the server sent `sheet` for it (`cells` = that object); `cells = null` = back to the page's original values. Meant for a table on the page that shows the plan (listen and update the row). |

### JS access

`el.dlComponent` (the `Gantt` instance) and `el.dlGantt = {tasks, rows, render(), changed (a Set of ids), setDraft(text)}`. `tasks` entries hold internal numeric fields (`s`, `e` in hours, `over`).

### URL contracts

All requests are `POST`, `Content-Type: application/json`, `Accept: application/json`, `credentials: same-origin`, header `X-CSRF-TOKEN` taken from `window.DL_CSRF` or `<meta name="csrf-token">`. The page therefore needs one of them (the Laravel layout normally has the meta tag).

**`impact-url` request** (body): `{"task": <id>, "row": "<row key>", "start": "HH:MM", "end": "HH:MM"}`. Times are the proposed ones after snapping and sliding; hours of 24 or more are written as is (`"26:30"`).

**`impact-url` response** (JSON): `ok` (bool; if absent, the HTTP status decides), `reason` (string, shown when `ok` is false; falls back to `message`, then the HTTP status text), `lines` (list of `{label, value, tone}` with `tone` `ok` (default, green), `warn` (amber) or `bad` (red)), and optionally `row`, `start`, `end` (the server corrects the position; `start`/`end` as `"HH:MM"`), `moves` (list of `{id, row, start, end, sheet?}`: other tasks that would move; locked tasks are ignored), `sheet` (object for the dragged task, forwarded in `dl:plan-sync`), `no_change` (true = nothing would change: the accept button is disabled and the reason field hidden). If the body is not JSON the HTTP status decides (a 2xx counts as ok, with no lines); a network error reverts the move with an error toast.

**`move-url` request**: the same four keys plus `"reason": "<text>"` when the field was filled. **Response**: `ok`, `reason`/`message` (shown on failure, the move is reverted), `draft` (string: replaces the draft badge text), `reload` (true: toast "replanned" and the page reloads after 600 ms, use when the server re-plans other tasks). A success without `reload` keeps the block where it is and emits `dl:gantt`.

**`bulk-url` request**: `{"process": "<process key or ''>", "machine": "<row key or ''>"}`. **Response**: `{"results": [{"wo": "<work order>", "ok": true, "message": "<text if not ok>"}]}`, or `{"error": "<text>"}` / a non-2xx status for a failure toast. After a normal answer the results are shown and the page reloads.

## Rules and pitfalls

### Rules

- Do the planning rules on the server. The script checks only eligibility (`eligible`), the lock window and overlaps; capacity, downtime windows, material, due dates and the actual re-plan belong in `impact-url` / `move-url`. Always answer `ok: false` with a clear `reason` for a refused move.
- Treat the browser copy as a proposal: with `move-url` the accepted result is what the server stores; use `reload: true` when its answer changes other tasks.
- Declare the board as data; do not draw lanes by hand. Behaviour for `dl:gantt-submit`, `dl:plan-sync` and toolbar extras belongs to project JS or the `actions` slot, not to an inline `<script>` (lint L002; also L001 raw tags, L003 hex colours). There is no Gantt-specific lint rule.
- Keep `id` and row `key` stable and unique; keep times as `HH:MM` text in the application time zone.
- Use tones that exist in the stylesheet (`accent`, `violet`, `warn`, `success`, `info`).
- A board in Live mode must not be edited: set `simulation` false (or switch) and keep the mutating endpoints behind the Draft flow.

### Common mistakes

- `end` smaller than `start` or forgetting that hours may exceed 24: without `origin-date` the labels show `26:00`; with it they show `Fri 02:00`.
- Giving `lock-until` as a clock time while the axis is multi-day: it is an hour offset from the origin (`16` = 16:00 of day 0), not a time of day.
- Expecting `impact-url` without a CSRF token source: the POST returns 419.
- Using `reason-required` without `move-url`: the reason is not enforced or sent.
- Setting `bulk-label` and expecting the button to work when `bulk-url` is empty: it is rendered disabled.
- Reading `task.s`/`e` from events: use the `start`/`end` strings.
- Passing `linked` without `machines` or without `process`: the constructor and the view read those keys directly (`process` may be `''`).
- Numeric linked keys (`'process' => '5'`, machine/process keys as integers): PHP converts array keys to integers and the view compares process keys strictly with the `process` string, so the Machine select of the chosen process stays hidden on load.
- Expecting `dl:gantt` on Submit: that is `dl:gantt-submit`.

### Performance

Every change redraws the whole board as an HTML string and several helpers loop over all tasks (conflict detection is quadratic per row). Fine for a few hundred blocks; for large plans send only the visible days/processes, or use `linked` mode which shows one process at a time. Document-level listeners (Escape, outside-click) are added per board and never removed.

### Security

Row/task text is escaped by the script. The component never writes to the server by itself, so `impact-url`, `move-url` and `bulk-url` must authorise the user and re-validate everything (ids, row, times, the reason); never trust the posted times as already legal. CSRF is the page's responsibility (see URL contracts).

### Notes on inconsistencies found while writing this entry (not fixed)

- The header comment documents the impact answer as `{ok, reason?, lines?, row?, start?, end?}`; the script also reads `moves`, `sheet` and `no_change`, and reads `draft` and `reload` from the move answer and `results` / `error` from the bulk answer. None of these are mentioned in the PHP comment.
- Row `sub` (the crew/sub-title used in the demos) is not read by `gantt.js`.
- `linked.machine` selects the filter on load (script), but the Machine select shows `linked.defaults[process]` (view); if they differ the dropdown and the board disagree.
- With `linked.process` = `null` or a numeric key the Machine select of the chosen process is hidden on load (`$pk !== $linked['process']` is a strict comparison); `''` (All processes) is the safe "none".
- `reason-required` is only enforced when `move-url` is set, but the reason field is drawn regardless.
- English or non-dictionary literals in the script and CSS: `Live` in the switch, `Resource` / `Progress` / `Status` in the detail card and editor, `Cancel` / `Save` in the editor, `idle` on gap strips, the legend entries `Breakdown` / `QC Hold` / `Hot Job`, the tags `LIVE` / `DRAFT`, the filter chip prefix `Resource:`, minutes `min` in the detail card; the conflict marker is the Indonesian word `bentrok` (CSS `content`). Buttons `submit-label`, `bulk-label`, and `draft-label` are not translated (the `Button` object keeps labels as given).
- `unscheduled` is a bare number; nothing in the board lists those tasks.
- Opening the editor with a task whose `process` is not a key of `categories` selects the first category and a Save with no other change would re-assign its process (linked mode hides that select but still reads it).
- In `linked` mode `start` / `end` / `px-per-hour` are ignored whenever a machine shown has at least one task, which is easy to miss when tuning the axis.
- Document-level listeners and nothing to remove them: removing a board from the page (partial re-render) leaves its Escape/outside-click handlers behind.

## Example

The production scheduling board from the catalog demo `gantt-production` (rows, tasks and categories come from the demo support class; shortened here and with fictional names), with the two JSON endpoints:

```blade
@php
    $categories = ['print' => ['label' => 'Printing', 'tone' => 'accent'], 'cut' => ['label' => 'Die-cut', 'tone' => 'warn'], 'fold' => ['label' => 'Folding', 'tone' => 'success']];
    $rows = [
        ['key' => 'sm74', 'label' => 'Press A', 'section' => 'PRINTING', 'load' => 86],
        ['key' => 'cd102', 'label' => 'Press B', 'section' => 'PRINTING', 'load' => 94, 'bottleneck' => true,
            'down' => [['from' => 34, 'to' => 38, 'label' => 'Maintenance']]],
        ['key' => 'dc', 'label' => 'Die-cutter', 'section' => 'FINISHING', 'load' => 62],
    ];
    $tasks = [
        ['id' => 301, 'row' => 'sm74', 'label' => 'WO-0301', 'process' => 'print', 'start' => '06:00', 'end' => '11:30', 'progress' => 100, 'setup' => 45, 'gates' => ['ok', 'ok', 'ok']],
        ['id' => 305, 'row' => 'sm74', 'label' => 'WO-0305', 'process' => 'print', 'start' => '18:30', 'end' => '26:00', 'progress' => 0, 'setup' => 30, 'gates' => ['ok', 'ok', 'wait'], 'forecastEnd' => 27.3, 'eligible' => ['sm74', 'cd102']],
        ['id' => 304, 'row' => 'cd102', 'label' => 'WO-0304', 'process' => 'print', 'start' => '14:00', 'end' => '22:00', 'progress' => 5, 'state' => 'hot', 'risk' => true, 'eligible' => ['sm74', 'cd102']],
        ['id' => 307, 'row' => 'dc', 'label' => 'WO-0307', 'process' => 'cut', 'start' => '16:00', 'end' => '20:00', 'progress' => 0, 'eligible' => ['dc']],
    ];
@endphp

<x-dl::gantt :unscheduled="2" :start="6" :end="54" :px-per-hour="46" origin-date="2026-10-08"
    :lock-until="16" :now-at="10.5"
    impact-url="{{ url('/demo/gantt/impact') }}" move-url="{{ url('/demo/gantt/move') }}" draft-label="DRAFT 0 change"
    :submit-label="null" resource-label="Machine"
    :categories="$categories" :rows="$rows" :tasks="$tasks" />
```

The matching routes of the demo site (`site/routes/web.php`) and its judge (`App\Support\GanttDemo::impact()`):

```php
Route::post('/demo/gantt/impact', fn (Request $r) => response()->json(GanttDemo::impact($r->all())));
Route::post('/demo/gantt/move', fn (Request $r) => response()->json(
    GanttDemo::impact($r->all()) + ['draft' => 'DRAFT '.$r->session()->increment('gantt_demo_draft').' change']
));

// GanttDemo::impact(): refuse a window where the machine is down, otherwise return impact lines
if ($row === 'cd102' && $start < 38 && $end > 34) {
    return ['ok' => false, 'reason' => 'CD102 is down for maintenance in that window.'];
}
return ['ok' => true, 'lines' => [
    ['label' => 'Changeover', 'value' => '+20 min', 'tone' => 'warn'],
    ['label' => 'Material ready', 'value' => 'Yes', 'tone' => 'ok'],
    ['label' => 'Due date', 'value' => $late ? 'At risk (+1 day)' : 'Unchanged', 'tone' => $late ? 'bad' : 'ok'],
]];
```

A linked board (Process then Machine) is declared with the `linked` prop; this variant is built from the class contract, it is not in a catalog demo and was not run:

```blade
<x-dl::gantt :rows="$rows" :tasks="$tasks" :categories="$categories" :simulation="false" :extras="false"
    :linked="[
        'processes' => ['print' => 'Printing', 'cut' => 'Die-cutting'],
        'machines'  => ['print' => ['sm74', 'cd102'], 'cut' => ['dc']],
        'defaults'  => ['print' => 'sm74'],
        'process'   => '',
    ]" />
```

(The simple board with Simulation | Live, number cards, an `actions` slot and Submit is the demo `gantt-scheduling`.) Everything in this entry was read from `Gantt.php`, `gantt.js`, `devlib.css`, `GanttDemo.php` and the routes; the board was not exercised in a browser.

## Related

- `x-dl::queue-board`: read-only queue per process and machine (its Process/Machine dropdowns mirror the linked board).
- `x-dl::suggestion-strip`: the machine-learning sequence suggestion shown above the board (Preview / Use / Dismiss).
- `x-dl::kanban`: stage-based cards.
- `x-dl::calendar`, `x-dl::year-calendar`: dated events and holidays.
- `x-dl::grid`: the list/master data (rows, work orders) that feed the board.
- `x-dl::button`, `x-dl::field.select`, `x-dl::modal`: the toolbar button, the dropdowns and dialogs the board uses or resembles.
- `skills/devlib/components.md`: generated prop list.
