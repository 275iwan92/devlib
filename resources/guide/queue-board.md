## Overview

`<x-dl::queue-board>` shows what is queued per production process and per machine: a Process dropdown, a Machine dropdown that depends on it, and a read-only table of the queue with status, work order, item, quantity, progress bar and percentage, start, end, optional "Suggested" text and the idle time before each job with its reason. The server renders every row once; the script only shows and hides rows according to the two dropdowns and updates the "n items" counter. It makes no server call.

Use it to answer "what is waiting on machine X?" inside a work-order form or a planning screen, where the data already comes from the page. Pick another object when:

- Planners must drag jobs, change durations or check impact: use `x-dl::gantt` (the queue board is its read-only list companion).
- The list needs sort, paging, export or row actions: use `x-dl::grid`.
- Cards move between stages: use `x-dl::kanban`.

## Props

Constructor of `Devlib\Components\QueueBoard` (7 props). Arrays and booleans need the `:prop="expr"` form; there is no `source` prop on this object.

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `processes` | `array` | `[]` | Process dropdown options as `[processKey => label]`. The constructor adds `'' => 'All processes'` as the first option unless the key `''` is already present. |
| `machines` | `array` | `[]` | `[processKey => [machineKey => label]]`: the machines that can run each process. One Machine dropdown is rendered per process key; only the one of the selected process is visible. The constructor adds the key `''` (the "All processes" case) containing the union of all machines, unless `''` is already present in `processes`. Every Machine dropdown starts with `'' => 'All machines'`. |
| `rows` | `array` | `[]` | The queue lines (shape below). All rows are rendered, hidden, and shown by the filter. |
| `process` | `?string` | `null` | Process key selected when the page opens. Use `''` (not `null`) for "All processes", see the notes. |
| `machine` | `string` | `''` | Machine key selected in the dropdown of `process` on load. `''` = All machines. Only used for the Machine dropdown whose key equals `process`. |
| `empty` | `string` | `'Nothing is queued on this machine.'` | Message shown (in a full-width row) when no row matches the current filter. Translated. |
| `suggested` | `bool` | `false` | Adds the "Suggested" column (reads `'suggested'` of each row). Meant to be on only while a sequence suggestion model is active; with `false` the column and the key are ignored. |

Common attributes: extra HTML attributes (`id`, `class`, `style`, `data-*`) are merged onto the root `div`; `can="module.action"` hides the whole object when the user lacks the ability.

### The `rows` array

Each row is an associative array. Keys read directly by the view (a missing one is an "undefined array key" error) are marked required.

| Key | Required | Meaning |
|---|---|---|
| `process` | yes | Process key; must equal a key of `processes` for the row to appear under that process. |
| `machine` | yes | Machine key; must equal a key inside `machines[process]` to be reachable with the Machine dropdown. |
| `status` | yes | `Running`, `Confirmed` or `Draft`. Shown as a pill (class `dl-queue__st--<status lower-cased>`) with the translated word. Other words are shown in a neutral pill. |
| `wo` | yes | Work order number (text, escaped). |
| `item` | yes | Item name (text). |
| `qty` | yes | Quantity, printed as given (format it first), right-aligned. |
| `progress` | yes | `0` to `100`, or `null`. A number draws a bar (`dl-queue__pct`) and `n%`; `null` prints an em dash (for example a subcontracted step without output data). Cast to int; not clamped. |
| `start` | yes | Start text (printed as given). |
| `end` | yes | End text (printed as given). |
| `idle` | no | Idle time before this job and why, as text (for example `2 h, waiting for plates`). Empty cell when missing. |
| `suggested` | no | Text of the Suggested column (only when `suggested` is true). |
| `mine` | no | `true` adds `is-mine` to the row (accent-coloured background): the user's own jobs. |

## Behavior

- On load the script reads the selected process from the Process dropdown and the machine from the visible Machine dropdown, then shows only the matching rows. A row is visible when (no process selected or its `process` equals the selection) and (no machine selected or its `machine` equals the selection).
- Changing the Process dropdown shows the Machine dropdown that belongs to it, hides the others, resets the machine to "All machines" and filters again. Changing a Machine dropdown filters again.
- The counter (`dl-queue__count`) shows `n item` / `n items`. When `n` is 0 the `empty` message row is shown.
- The table is the standard `dl-table`; headings: Status, Work Order, Item, Quantity, Progress, Start, End, (Suggested), Idle before. Rows keep the order in which you passed them: sort in PHP, the board cannot sort.
- No keyboard behaviour of its own: the dropdowns are `x-dl::field.select` (searchable custom select); the table is static text. Status is conveyed by text as well as colour.

### Translation

Headings, status words, "All processes", "All machines" and `empty` go through `Devlib\Lang::text()` (English in the view, translated by the dictionary). Row cell values (`wo`, `item`, `qty`, `start`, `end`, `idle`, `suggested`) and the labels in `processes` / `machines` are data and are printed as passed. Several of the English headings and the two "All ..." labels are not in the built-in DevLib dictionary (they need an entry in the application dictionary to be translated); the counter words `item` / `items` are written by the script and are never translated.

## Data and events

| Element | Meaning |
|---|---|
| `data-dl="x-dl::queue-board"`, `data-dl-class`, `data-dl-src` | Component identity (`data-dl-src` is `—`: there is no `source` prop). |
| `data-dl-queue` | Mount selector of `queue-board.js`. |
| `select name="queue_process"` | The Process dropdown (hidden `input` inside `x-dl::field.select`). |
| `span[data-q-mach="<processKey>"]` | Wrapper of one Machine dropdown; `hidden` unless it is the selected process. Its select is named `queue_machine_<index>` (index = position in `machines`). |
| `tr[data-q-process][data-q-machine]` | A queue row, `hidden` until the filter shows it. |
| `tr[data-q-empty]` | The "nothing queued" row (`colspan` 8, or 9 with `suggested`). |
| `[data-q-count]` | The counter. |
| CSS | `dl-queue`, `dl-queue__bar`, `dl-queue__count`, `dl-queue__pct` (bar track, inner `i` with the width), `dl-queue__st`, `dl-queue__st--running/confirmed/draft`, row class `is-mine`. |

No custom events (`dl:*`) are dispatched and there is no URL contract. The script instance is `el.dlComponent` (fields `proc`, `mach`, method `apply()`). To refresh data, reload the page or re-render the component.

## Rules and pitfalls

### Rules

- The board is read-only. Changes to a job's start, end or machine are made elsewhere (the form, `x-dl::gantt`) and the board is re-rendered.
- Give the server the filtering/sorting job: build `rows` already ordered the way the planner wants them (for example by start time within a machine).
- Do not put markup in row values; they are escaped.
- Lint: no queue-board rule. The screen rules L001 (raw `table`/`select`/... tags), L002 (inline `<script>`) and L003 (hex colours) apply to the view that uses it.

### Common mistakes

- Row `process` / `machine` values that are not keys of `processes` / `machines`: the row is rendered but cannot be reached with the dropdowns (only "All processes" shows it).
- Omitting `progress` for rows without data: pass `null`, because the key is read directly.
- Passing `process="all"` or similar for "all processes": the empty string `''` is the key of that option.
- Numeric process keys (for example database ids): PHP turns `['5' => ...]` array keys into integers, and the view compares `$pk !== $process` strictly against the `process` prop, so the Machine dropdown of that process is never shown on load. Use non-numeric keys (`'p5'`) or fix the types before passing them.
- Putting very long queues in it: every row is in the HTML (no paging); keep it to the queue of one plant/day or use `x-dl::grid`.

### Notes on inconsistencies found while writing this entry (not fixed)

- The header comment lists `mine` among the inputs; there is no such constructor prop: `mine` is a key of each row.
- With the default `process = null` the "All processes" Machine dropdown (key `''`) is hidden on load, because the view tests `'' !== null`; users cannot filter by machine across all processes until they pick a process and come back to "All processes" (the script then shows it). Passing `process=""` avoids this.
- The same strict comparison breaks numeric process keys (see Common mistakes).
- The counter words `item` / `items` are hard-coded in `queue-board.js` and not translated; the headings "Work Order", "Idle before", "Suggested", "All processes" and "All machines" and the default `empty` text are not in the built-in dictionary (searched `src/Lang.php`).
- `empty` is translated twice (once as a standard text prop, once in the view); harmless but redundant.
- Status colours (`dl-queue__st--running/confirmed/draft`) use fixed hex values in `devlib.css` rather than theme tokens, so they do not follow dark or other themes; a status other than the three has no colour of its own.
- Unlike most objects, `QueueBoard` has no `source` prop, so the catalog shows `—` as its source.

## Example

There is no catalog demo of this object yet; the example below is built from the documented row shape of the class (fictional data) and was not rendered or run while writing this entry.

```blade
@php
    $processes = ['print' => 'Printing', 'cut' => 'Die-cutting'];
    $machines = [
        'print' => ['pressA' => 'Press A', 'pressB' => 'Press B'],
        'cut'   => ['cutter1' => 'Die-cutter 1'],
    ];
    $rows = [
        ['process' => 'print', 'machine' => 'pressA', 'status' => 'Running',   'wo' => 'WO-0301', 'item' => 'Snack box 200g', 'qty' => '12,000', 'progress' => 40,   'start' => '21 Oct 08:00', 'end' => '21 Oct 14:30', 'idle' => '', 'mine' => true],
        ['process' => 'print', 'machine' => 'pressA', 'status' => 'Confirmed', 'wo' => 'WO-0305', 'item' => 'Tea sleeve',     'qty' => '30,000', 'progress' => 0,    'start' => '21 Oct 15:00', 'end' => '22 Oct 02:00', 'idle' => '30 min, plate change'],
        ['process' => 'print', 'machine' => 'pressB', 'status' => 'Draft',     'wo' => 'WO-0309', 'item' => 'Bottle label',   'qty' => '50,000', 'progress' => 0,    'start' => '22 Oct 06:00', 'end' => '22 Oct 14:00', 'idle' => '2 h, waiting for artwork'],
        ['process' => 'cut',   'machine' => 'cutter1', 'status' => 'Confirmed', 'wo' => 'WO-0307', 'item' => 'Pharma carton', 'qty' => '8,000',  'progress' => null, 'start' => '21 Oct 16:00', 'end' => '21 Oct 20:00'],
    ];
@endphp

<x-dl::queue-board :processes="$processes" :machines="$machines" :rows="$rows" process="" />
```

With `process=""` the page opens on "All processes" with all rows and the "All machines" dropdown visible; choose Printing and the Machine dropdown lists only Press A and Press B.

## Related

- `x-dl::gantt`: the interactive resource x hours board; the queue board is its read-only list for the same machines.
- `x-dl::suggestion-strip`: the machine-learning sequence suggestion shown above a Gantt, which is when the `suggested` column of this board makes sense.
- `x-dl::grid`: sortable, pageable, exportable lists.
- `x-dl::kanban`: stage-based cards.
- `x-dl::field.select`: the searchable dropdown used for Process and Machine.
- `x-dl::progress`: a standalone progress bar (the queue board draws its own small bar).
- `skills/devlib/components.md`: generated prop list.
