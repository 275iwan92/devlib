## Overview

`<x-dl::build-board>` is the "Build Progress" board of a project: how many objects, modules, screens, forms, grids, reports and engines have been built, which modules are fully done, partly done or not started, how many screens still wait for the user's QC and how many the user has locked after QC, plus the foundation numbers (models, migrations, tests, dictionary), the list of built features, the open work, and optionally a commits-per-day chart and a per-agent summary. It is a read-only dashboard made of `x-dl::stat-box`, `x-dl::card`, `x-dl::progress` and `x-dl::badge`; it has no JavaScript and posts nothing.

Use it on the home or summary screen of a project to give the owner a single view of progress. Do NOT use it for per-record data (use `x-dl::grid`), for KPI tiles of business data (`x-dl::kpi-row`, `x-dl::dashboard`), or as the reference of engines (`x-dl::engine-catalog`, which this board links to).

All numbers are DATA given in the props. The names of modules, engines and features are the application's own and are not translated; only the headings of the board are. In the DevLib site the data comes from the file `resources/data/build.json`, written by `php artisan build:catalog` from the DevLib catalog itself (see Data and events).

### Generated markup

```html
<div class="dl-bb" data-dl="x-dl::build-board" data-dl-build-board>
  <header class="dl-bb__head"> "Build Progress", "Snapshot <date> · <database> · N commits since <date>", [source] </header>
  <div class="dl-bb__tiles"> 8 x x-dl::stat-box </div>
  <div class="dl-bb__cols">
    [card "When" (commits per day) + card "Work by agent"]  <!-- only when build.agents is not empty -->
    card "Modules" (table.dl-bb__table, legend, total row)
    <div class="dl-bb__side"> card "From built to approved" (3 x x-dl::progress), card "DevLib library", card "Foundation" </div>
    card "Engines · N" (mode badges, groups, link)
    <div class="dl-bb__side"> card "Features built", card "Not yet" </div>
  </div>
</div>
```

Extra HTML attributes (`class`, `id`) and `can="module.action"` go on the root `div`; without the ability nothing is rendered.

## Props

Constructor of `Devlib\Components\BuildBoard` (4 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `build` | `array`, `[]` | The progress data (shape below). Everything on the board except the "DevLib library" card comes from here. With `[]` the board CANNOT render: the percentage helper receives `null` and raises a `TypeError` (see "Shape of `build`" for the required numbers). Check for an empty array in the page and show `x-dl::empty-state` instead (the catalog demo does). |
| `library` | `array`, `[]` | Numbers of the component library itself: `['groups' => [labels], 'pages' => n, 'approved' => n, 'demos' => n, 'packages' => [names]]`. When the array is empty the card "DevLib library" is not drawn. The first tile ("DevLib objects") uses `build.totals.objects` for the value and `count(library.groups)` for its sub-line, so without `library` that sub-line says `0 catalog groups`. |
| `engines-url` | `?string`, `null` | Address of the engine catalog screen. When set, the "Engines" card ends with the link "Open Engine Catalog" (`<a class="dl-bb__link">`); when `null` there is no link. Plain text value, not translated. |
| `source` | `?string`, `null` | Text of the small database-icon line at the top right and of `data-dl-src` for the developer catalog; default `build catalog`. Pass the origin of the data, for example `resources/data/build.json`. |

### Shape of `build`

| Key | Used for |
|---|---|
| `generated` | Date-time of the snapshot ("Snapshot ..."); `-` when missing. |
| `database` | Name printed after the snapshot date. |
| `totals` | Associative array of numbers (below). A missing key prints `-`, EXCEPT `screens`, `v2`, `v1`, `waiting_qc`, `locked` and `not_built`, which feed the percentage helper (`pct()`, typed int or float) and must be present as numbers, otherwise a `TypeError` stops the page. |
| `modules` | List of module rows (below); the "Modules" card. |
| `engines` | `[['group' => 'Pricing', 'items' => [['name', 'purpose', 'mode']]]]`; the "Engines" card and the mode badges. `mode` is one of `suggest`, `auto`, `calc`, `flow`. |
| `features` | `[[icon, text]]`; chips in "Features built". The icon is a Font Awesome class (`fa-language`) or a DevLib icon name resolved by `Devlib\Icon::fa()`. |
| `notes` | List of texts appended to "Not yet". |
| `agents` | Optional. List of `['key', 'name', 'last' => date or null, 'commits', 'days', 'screens', 'objects', 'engines', 'tests']` (one per contributor). With it the board adds the cards "When" and "Work by agent". |
| `days` | Optional. For the chart: `[['date' => 'Y-m-d', '<agent key>' => commits, 'other' => commits]]`, one per day. |
| `untracked` | Optional. Commits made before agent tracking started (the grey share). |

`totals` keys read by the board:

| Key | Where it appears |
|---|---|
| `objects` | tile "DevLib objects"; "Objects" in the library card |
| `modules`, `modules_done` | tile "Modules" and its sub-line "N fully on v2" |
| `screens`, `v2`, `v1`, `not_built` | tile "Screens in menu" (sub-line "N v2 · N not yet" = `v1 + not_built`); total row of the Modules table; the percentage base of the funnel |
| `forms`, `reports` | tiles "Forms" and "Reports" (sub-line "menu items"); total row |
| `grid_classes`, `lookups`, `report_grids` | tile "Grids" and its sub-line "N lookup · N report" |
| `grids` | the Grid column of the total row of the Modules table |
| `engines`, `engine_groups` | tile "Engines" (sub-line "N business areas") and the title of the Engines card |
| `tables`, `databases` | tile "Tables" (sub-line "N databases") |
| `waiting_qc`, `locked` | funnel "From built to approved"; total row; the QC line in "Not yet" |
| `models`, `migrations`, `tests`, `dictionary`, `languages` | card "Foundation" |
| `commits`, `since` | header: "N commits since <date>" |

Module row keys: `label`, `total` (screens of the module), `v2` (screens built on the current screens), `v1` (screens whose menu item still opens the earlier screen), `not_built`, `waiting_qc`, `locked`, `form`, `grid`, `report`. The board reads `label`, `total`, `v2`, `v1`, `not_built`, `form`, `grid`, `report` and `locked` directly: a missing key raises an "undefined array key" error.

## Behavior

### Tiles

Eight stat boxes in an auto-fit grid (minimum 150 px): DevLib objects, Modules (success tone), Screens in menu, Forms, Grids, Reports, Engines (warn tone), Tables (neutral tone). Numbers are formatted with the application locale and no decimals (`Locale::number`); a value that is `null` or absent prints `-`.

### Modules card

- One row per module: name, a two-colour progress bar (accent = `v2`, amber = `v1`, remainder grey = not built), `v2 / total`, counts of forms, grids, reports, locked screens, and a state badge.
- State badge (`BuildBoard::state()`): `Built` (green) when `total > 0` and `v2 === total` (strict comparison); `Partial` (amber) when `v2 > 0`; otherwise `Not started` (neutral). Locking after QC does not change the state.
- The footer row sums the whole project from `totals` (not from the rows) and shows the percentage of `v2` over `screens`.
- A note explains: Built means the screen exists and reads / writes the database; it is finished only after the user locks it after QC.

### Funnel, library, foundation

- "From built to approved": three progress bars as a percentage of `totals.screens` (rounded to 0.1): built and waiting for QC (`waiting_qc`), locked by the user after QC (`locked`, success tone), not built (`not_built`, warn tone). They are independent bars and need not add up to 100.
- "DevLib library": objects, approved pages over all pages, live demos, side packages, the group names as chips and the package names as a note.
- "Foundation": models, migrations, test files, dictionary texts with the number of languages.

### Engines card

Title `Engines · N` with one badge per mode and its count (`N Suggests, officer confirms`, `N Calculation`, ...; labels and tones from `EngineCatalog::MODES`, most frequent first). Below, one row per group with its engine names (the tooltip of a name is its `purpose`), and the link to the catalog when `engines-url` is set.

### Not yet

The open work is computed: for every module `N screens not built` (when `not_built > 0`) and `N screens still open the old v1 screen` (when `v1 > 0`), then all texts of `build.notes`, then (when `waiting_qc > 0`) the line `User QC: N screens wait for review`.

### Agents and time (optional)

When `build.agents` is not empty:

- "When": a bar chart of commits per day for the days in `build.days`. Each bar is stacked per agent key and `other` (work before tracking, grey); the height is relative to the busiest day (120 px). The title of a bar is `date: total`. The legend lists the agents.
- "Work by agent": a card per agent with commits, active days, items touched (`screens + objects + engines`), the mix of screens / objects / engines / tests, and the last active date; a share bar of commits (tracked agents against `untracked`).
- The colours of agents come from CSS classes `is-<key>` (see the code oddity below).

### Language and accessibility

Headings, legends, notes and state labels go through the dictionary; module, engine and feature names are data and are not translated. The board is made of ordinary blocks: the module table is a real `<table>` with `<thead>`, `<tbody>`, `<tfoot>`; the progress bars are plain `<i>` elements without `role="progressbar"`, so the percentage is visible only as the numbers beside them; chart bars have only a `title`. There is no keyboard interaction other than the optional link.

## Data and events

- No events, no `dl:*` messages, no JavaScript class (`data-dl-js` is not set). Nothing is requested at run time.
- In the DevLib site the file `resources/data/build.json` is created by `php artisan build:catalog` (`site/app/Console/Commands/BuildCatalog.php`). It builds one module per catalog group (pages that are rules documents are skipped), takes `locked` from the approved-page list, `waiting_qc` as the rest, counts demos as screens, takes engines from the registered `EngineRegistry`, and reads the commit count and first commit date from git. `App\Support\Catalog::build()` reads the file back (empty array when the file is missing) and `Catalog::stats()` gives the `library` array.
- A project can feed the board from its own registry or database in the same shape; the component never reads a database.
- JSON written by that command contains extra keys (`items`) that the board ignores.

## Rules and pitfalls

- Compute the data on the server and pass plain arrays. Do not put private information into `build`, `library` or `notes`: everything is printed on the page.
- Make sure `v2 + v1 + not_built` equals `total` for each module and the same for `totals`; the board does not check it, and the bars are drawn from these numbers as given.
- Counters must be integers. `state()` compares `v2` and `total` with `===`, so numeric strings from a database never reach `Built`; cast them (`(int)`).
- Provide every key of the module rows and of the rows you use in `agents`, otherwise rendering stops with an "undefined array key" error.
- Always give `totals.screens`, `v2`, `v1`, `waiting_qc`, `locked` and `not_built` as numbers (even `0`); a missing one is `null` and `BuildBoard::pct()` (typed `int|float`) throws a `TypeError`.
- Pass `totals.since` whenever `totals.commits` is set. `Locale::date(null)` prints today's date, so a missing `since` shows the wrong "commits since" date.
- `library` is optional; leave it out on projects that are not a component library, and the card disappears.
- Put the board where the owner looks first (home or summary). It is a dashboard, not an audit log: the numbers are a snapshot taken when the data was generated (`generated`).
- Naming: keep module labels short; the table does not wrap them (`white-space: nowrap`) and scrolls horizontally inside the card on narrow screens.
- Code oddity (not fixed): the "Grids" tile reads `totals.grid_classes` (`src/Components/BuildBoard.php:128`) but the total row of the Modules table reads `totals.grids` (line 180). The command `build:catalog` (`site/app/Console/Commands/BuildCatalog.php:48-56`) writes `grids` and does not write `grid_classes`, `lookups`, `report_grids`, `tables`, `databases`, `models`, `dictionary` or `languages`, so in the DevLib site those tiles and the Foundation figures show `-`.
- Code oddity (not fixed): the colours of the agent chart and cards exist only for three fixed keys in `resources/assets/devlib.css` (lines 3411 and 3425: two named agent keys and `other`), and the list of agents in `site/app/Support/Summary.php` is fixed the same way. Any other agent key renders without a colour. Use `other` for "before tracking" and keep the keys to those the CSS knows, or extend the CSS in your theme.
- Code oddity (not fixed): the "Not yet" texts hard-code the words "old v1 screen" (`BuildBoard.php:105`) and the tile sub-line hard-codes "v2" (line 126); the board assumes exactly two screen generations (`v2` current, `v1` earlier).
- Code oddity (not fixed): the funnel (`BuildBoard.php:189-191`) and the Modules total row take their percentage from `totals.screens`, while the module rows use each module's own `total`; if the two sets of numbers are not kept consistent the percentages disagree.

## Example

From the catalog demo `site/resources/views/demos/build.blade.php`:

```blade
@php($build = \App\Support\Catalog::build())
@if ($build)
    <x-dl::build-board :build="$build" :library="\App\Support\Catalog::stats()" :engines-url="route('lib', ['slug' => 'p9-engines'])" source="resources/data/build.json" />
@else
    <x-dl::empty-state icon="fa-chart-simple" title="No build catalog yet" message="Run php artisan build:catalog to create resources/data/build.json." />
@endif
```

A small board with fictional data (no agents, no library):

```blade
@php
    $build = [
        'generated' => '2026-10-21 09:30', 'database' => 'sample_db',
        'totals' => ['objects' => 84, 'modules' => 2, 'modules_done' => 1, 'screens' => 30, 'v2' => 24, 'v1' => 4, 'not_built' => 2,
            'waiting_qc' => 10, 'locked' => 14, 'forms' => 6, 'grid_classes' => 9, 'lookups' => 3, 'report_grids' => 2, 'grids' => 9, 'reports' => 5,
            'engines' => 1, 'engine_groups' => 1, 'tables' => 40, 'databases' => 1, 'models' => 20, 'migrations' => 35, 'tests' => 12,
            'dictionary' => 800, 'languages' => 2],
        'modules' => [
            ['label' => 'Sales', 'total' => 12, 'v2' => 12, 'v1' => 0, 'not_built' => 0, 'waiting_qc' => 4, 'locked' => 8, 'form' => 3, 'grid' => 4, 'report' => 2],
            ['label' => 'Inventory', 'total' => 18, 'v2' => 12, 'v1' => 4, 'not_built' => 2, 'waiting_qc' => 6, 'locked' => 6, 'form' => 3, 'grid' => 5, 'report' => 3],
        ],
        'engines' => [['group' => 'Pricing', 'items' => [['name' => 'Volume Discount', 'purpose' => 'Quantity discount of an order line.', 'mode' => 'calc']]]],
        'features' => [['fa-language', 'Multi-language + dictionary']],
        'notes' => ['Import of opening balances is still manual.'],
    ];
@endphp
<x-dl::build-board :build="$build" source="Sample data" />
```

## Related

- `x-dl::engine-catalog`: the full engine reference; the board lists engine names and mode counts and links to it (`engines-url`). The mode labels come from `EngineCatalog::MODES`.
- `x-dl::stat-box`, `x-dl::card`, `x-dl::progress`, `x-dl::badge`: the objects the board is built from.
- `x-dl::dashboard`: the home dashboard that can host the board in its default slot (as the DevLib home does).
- `x-dl::qc-bar`: the floating review bar used during acceptance; the board's QC numbers (`waiting_qc`, `locked`) describe the same review process, but in the DevLib site they are computed from the approved-page list, not read from the bar.
- `skills/devlib/object.md`: how objects are built and approved.
