## Overview

`<x-dl::engine-catalog>` is the reference list of the ENGINES of an application: the calculation and business-rule classes that run behind the screens. For every engine it shows the purpose, how to call it (the real method signature), the input parameters, the outputs, the steps, the tables it reads and writes, its settings, the screens that use it, its tests, the documents it prints, and its source file. A search box (over the whole text of all cards), group chips with counts and an "Expand all" button make a long list easy to scan. The names and explanations are DATA of the application; the object translates only its own headings.

Use it on a "Technical / Engines" screen or in the developer help of an application. Do NOT use it to run an engine (it only documents), for a list of screens (`x-dl::help-map`), for module progress numbers (`x-dl::build-board`), or for a generic table (`x-dl::table`, `x-dl::grid`).

The list is almost always produced by the engine contract of DevLib, so that it can never differ from the code: `Devlib\Engine\EngineRegistry::catalog()` reads each class by reflection (`Devlib\Engine\EngineInspector`) and merges the hand-written explanation that an engine returns from `details()` (interface `Devlib\Engine\Described`). Only the explanation is written by hand.

### Generated markup

```html
<div class="dl-eng" data-dl="x-dl::engine-catalog" data-dl-engine-catalog>
  <div class="dl-eng__bar">
    <input type="search" class="dl-input dl-eng__q" data-eng-q>
    <span class="dl-eng__count" data-eng-count>12</span>
    <span class="dl-eng__chips" role="group">
      <button class="dl-eng__chip is-active" data-eng-group="">All</button>
      <button class="dl-eng__chip" data-eng-group="Pricing">Pricing <em>2</em></button> ...
    </span>
    <button ... data-eng-all>Expand all</button>
  </div>
  <section class="dl-eng__group" data-eng-section="Pricing">
    <h3 class="dl-eng__gt">Pricing</h3>
    <details class="dl-eng__item" id="engine-volume-discount" data-eng-item data-group="Pricing" [open]>
      <summary class="dl-eng__head"> name + purpose, mode badge, chevron </summary>
      <div class="dl-eng__body">
        [alert: restricted] "How to call" (dl-eng__call code) | Input / Output tables (dl-eng__io) | "How it works" (ol.dl-eng__steps) | dl.dl-eng__meta (Reads, Writes, Settings, Used by, Tests, Documents, Source)
      </div>
    </details>
  </section>
  <p class="dl-eng__none" data-eng-none hidden>No engine matches.</p>
</div>
```

Extra HTML attributes and `can="module.action"` go on the root `div`; without the ability nothing is rendered. Each card has the anchor `id="engine-<key>"`, so `#engine-volume-discount` in the URL opens and scrolls to that engine.

## Props

Constructor of `Devlib\Components\EngineCatalog` (3 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `engines` | `array`, `[]` | The list of engine rows (shape below). Normally `app(\Devlib\Engine\EngineRegistry::class)->catalog(base_path())`. Rows are grouped by their `group` in order of first appearance (a row without `group` goes into `Other`); one chip and one section per group. The counter at the top starts as `count($engines)`. With an empty array the screen shows only the bar and no cards; show `x-dl::empty-state` yourself in that case (the catalog demo does). |
| `open` | `bool`, `false` | When true every card is rendered with the `open` attribute (expanded). Default collapsed. The search box also opens the cards that match, and "Expand all" toggles at run time; `open` only sets the initial state. |
| `source` | `?string`, `null` | Text for the developer catalog / Manifest (`data-dl-src`); default `daftar engine`. Pass where the list comes from, for example `Devlib\Engine\EngineRegistry::catalog()`. No effect on the screen. |

### Shape of one engine row

| Key | Required | Meaning |
|---|---|---|
| `key` | yes | Unique id of the engine; becomes the anchor `engine-<key>`. Use only letters, digits, `_` and `-` so the deep link works. |
| `name` | yes | Title of the card. |
| `group` | no | Business area; section, chip and filter value. Default `Other`. |
| `mode` | no | `suggest`, `auto`, `calc` or `flow`; shows the badge (see Behavior). Any other value is printed as is with a neutral badge; empty prints an empty badge. |
| `purpose` | no | One sentence under the name (also used as tooltip elsewhere). |
| `class` | no | PHP class name, printed in the Source row. `null` for engines that are not PHP classes (for example JavaScript). |
| `file`, `lines` | no | Source path (relative to `catalog($basePath)`) and line count; `lines` is printed as a localized number followed by "lines". |
| `entries` | no | "How to call": list of `['method', 'static' => bool, 'params' => [['name', 'type', 'default']], 'returns', 'note'?, 'literal'?]`. The signature is built as `static method(Type $name = default, ...): Returns`; the type `mixed` is left out and so is a `mixed` return. With `'literal' => true` the `method` text is printed as the call, exactly as written (use it for a non-PHP engine). `note` is a small line under the signature. |
| `inputs`, `outputs` | no | Lists of `[name, meaning]` pairs for the Input / Output tables ("No input." / "No output." when empty). Both elements are required in each pair. |
| `steps` | no | Ordered list of texts for "How it works". |
| `reads`, `writes`, `settings`, `used`, `tests`, `docs` | no | Lists of texts shown as chips in the meta list under the labels Reads, Writes, Settings, Used by, Tests, Documents. A key that is empty or missing is skipped. |
| `restricted` | no | A warning text shown in an alert at the top of the card (for example "only administrators may run this"). Not translated. |

## Behavior

### Bar: search, groups, expand

- Search: every word typed (split on spaces, case-insensitive) must appear somewhere in the TEXT CONTENT of the card, which includes name, purpose, signatures, parameters, tables, steps and meta, even those of a collapsed card. Cards that match while a query is present are opened automatically. Clearing the query does not close them again.
- Group chips: one per group with the number of engines, plus "All". The chosen group and the search are combined (AND). A section with no visible card is hidden.
- The count badge shows the number of cards that pass the current search and group. When none passes, "No engine matches." appears.
- "Expand all": if at least one visible card is closed it opens all visible cards; otherwise it closes them all. The button label does not change.
- Deep link: if the URL hash is `#engine-<key>` when the page loads, that card is opened and scrolled into view. It is read once at start (later hash changes are ignored).

### Mode badge

| `mode` | Label (always English) | Tone | Meaning for the engine |
|---|---|---|---|
| `suggest` | Suggests, officer confirms | info | returns a DRAFT; the officer decides |
| `auto` | Runs automatically | success | runs in the background, result applies at once |
| `calc` | Calculation | accent | pure calculation, does not write to the database |
| `flow` | Document flow | violet | status flow, reversals, forwarding to other documents |

The labels are exposed as the constant `EngineCatalog::MODES` (`mode => [label, tone]`); `x-dl::build-board` reuses it for its engine badges.

### Card body

- "How to call": one `<code>` line per entry, built by `EngineCatalog::signature()`. A literal entry is printed as given.
- Input and Output are two `x-dl::table` blocks side by side (stack on narrow screens). Parameter names are bold.
- "Source": the class (or file) in code font, then the path and the line count when both exist.
- The card is a native `<details>`/`<summary>`: click, Enter or Space toggles it; the summary has a visible focus ring.

### Language

Headings and fixed texts (How to call, Input, Output, How it works, Reads, Writes, Settings, Used by, Tests, Documents, Source, lines, All, Expand all, the search placeholder, No engine matches) go through the dictionary. Everything in `engines` (names, purposes, parameter notes, steps, meta, `restricted`) is data and is printed as given, so write it in the language you want users to read. Mode labels stay English.

### Accessibility as implemented

The search input and the group chips have an `aria-label` / `role="group"`; the active chip is marked only by the class `is-active` (no `aria-pressed`). The counter is not a live region, so changes in the number of matches are not announced.

## Data and events

### What the object reads

Nothing from the server at run time: all data arrives in `engines`. With the DevLib engine contract the rows are made by `Devlib\Engine\EngineRegistry`:

- `register(Engine ...$engines)` adds engines (a second engine with the same `key()` throws `EngineException`).
- `catalog(?string $basePath = null)` returns, for each engine, `key`, `name`, `group`, `mode` (`Mode::value`), `purpose`, then `class`, `file` (relative to `$basePath`), `lines` and `entries` (only the method `run`) from `EngineInspector::describe()`, then everything `details()` returns when the engine implements `Described`.
- `Described::details()` returns the optional keys `inputs`, `outputs`, `steps`, `reads`, `writes`, `settings`, `used`, `tests`, `docs` (and `restricted`). Keys already set by the registry cannot be overridden by `details()` (see pitfalls).
- `EngineInspector::file($path, $basePath)` builds the row part for an engine that is not a PHP class (`class` null, empty `entries`).

An engine implements `Devlib\Engine\Engine` (`key`, `name`, `group`, `mode`, `purpose`, `run(array $input): Result`) with the enum `Mode` (`Suggest`, `Auto`, `Calc`, `Flow`). Rules of the contract (`skills/devlib/engine.md`): a Suggest engine returns a draft only (the registry throws if it returns `applied`); officer decisions are never overwritten; every decision has a reason; failure is a named `EngineException`, never `false` or `null`.

### Events and JS

- No custom `dl:*` event. The script `resources/assets/js/components/engine-catalog.js` class `EngineCatalog` (selector `[data-dl-engine-catalog]`) works on the DOM only; `el.dlComponent.apply()` re-runs the filter after you change the query or the group by script.
- Hooks: `data-dl-engine-catalog`, `data-eng-q`, `data-eng-count`, `data-eng-group`, `data-eng-all`, `data-eng-section`, `data-eng-item` (+ `data-group`), `data-eng-none`; classes `dl-eng`, `dl-eng__item`, `dl-eng__head`, `dl-eng__body`, `dl-eng__call`, `dl-eng__io`, `dl-eng__steps`, `dl-eng__meta`.
- Nothing is posted.

## Rules and pitfalls

- Write the explanation inside the engine (`Described::details()`), not in a separate file, so the text lives with the code and never goes stale. Only the explanation is hand-written; signatures, class, file and line count come from the code.
- `EngineRegistry::catalog()` merges with the array `+` operator, so the keys it already filled (`key`, `name`, `group`, `mode`, `purpose`, `class`, `file`, `lines`, `entries`) win over the same keys in `details()`. To change how an engine is called, change the code, not `details()`.
- Every pair in `inputs` and `outputs` needs two elements (name and meaning); a pair with only one element raises an "undefined array key" error when the page renders.
- `mode` must be one of `suggest`, `auto`, `calc`, `flow` (the enum values); the badge text is English whatever the language of the page.
- Use plain keys for `key` (letters, digits, `_`, `-`): the deep-link script accepts only `#[\w-]+`.
- Do not put secrets, passwords or real customer data in `settings`, `reads` or `notes`: the catalog is for developers, but every user who can open the screen can read it. Consider `can="..."` on the tag.
- Paths in `file` come from `catalog($basePath)`: pass `base_path()` so they are project-relative; without it the absolute server path is shown.
- The search matches closed cards as well, because it reads the text of the whole card. Very large lists are filtered in the browser; this is fine for hundreds of engines, not for thousands.
- Assistant link: only `Calc` and `Suggest` engines can become tools of the AI Assistant (`Devlib\Assistant\EngineTool`); the catalog is where users find which engines exist. Engines that touch salary, payroll, costing formulas or personal data must not be exposed to the Assistant (see `x-dl::chat`, "Assistant data policy").
- Code oddity (not fixed): the header comment of `src/Components/EngineCatalog.php` (lines 18-23) lists the row keys but omits `literal`, which `signature()` reads at line 70; there is also a stray comment `// run(float $planoW, float $gap = 3): array` above `signature()` (line 66).
- Code oddity (not fixed): the "Expand all" button keeps its label after it has closed everything (`resources/assets/js/components/engine-catalog.js:26-29`), and cards opened by a search stay open after the query is cleared (line 42).
- Code oddity (not fixed): the deep link is read only once in `init()` (`engine-catalog.js:31-32`); a later `hashchange` (clicking another `#engine-...` link on the same page) does not open the target.

## Example

From the catalog demo `site/resources/views/demos/engines.blade.php` (list taken from the registered engines):

```blade
@php
    $engines = app(\Devlib\Engine\EngineRegistry::class)->catalog(base_path());
@endphp
@if ($engines)
    <x-dl::engine-catalog :engines="$engines" source="Devlib\Engine\EngineRegistry::catalog()" />
@else
    <x-dl::empty-state icon="fa-gears" title="No engines registered" message="Register engines with Devlib\Engine\EngineRegistry::register() (see skills/devlib/engine.md)." />
@endif
```

An engine that explains itself (shape of the sample `site/app/Engines/VolumeDiscountEngine.php`):

```php
class VolumeDiscountEngine implements \Devlib\Engine\Described, \Devlib\Engine\Engine
{
    public function key(): string { return 'volume-discount'; }
    public function name(): string { return 'Volume Discount'; }
    public function group(): string { return 'Pricing'; }
    public function mode(): \Devlib\Engine\Mode { return \Devlib\Engine\Mode::Calc; }
    public function purpose(): string { return 'Gives the quantity discount of an order line and the line total from the unit price.'; }
    public function run(array $input): \Devlib\Engine\Result { /* ... */ }

    public function details(): array
    {
        return [
            'inputs' => [['qty', 'Ordered quantity'], ['unit_price', 'Price of one unit before discount']],
            'outputs' => [['discount_rate', 'Discount of the highest tier reached'], ['total', 'Line total']],
            'steps' => ['Take the highest tier whose threshold the quantity reaches.', 'Apply its rate to the unit price.', 'Multiply by the quantity.'],
            'settings' => ['TIERS: 100 = 5%, 500 = 10%, 1000 = 15%'],
        ];
    }
}
```

A hand-made row (engine that is not a PHP class, expanded by default), with fictional data:

```blade
<x-dl::engine-catalog open :engines="[[
    'key' => 'label-preview', 'name' => 'Label Preview', 'group' => 'Printing', 'mode' => 'calc',
    'purpose' => 'Lays out label text in the browser before printing.',
    'file' => 'resources/js/label-preview.js', 'lines' => 120,
    'entries' => [['method' => 'labelPreview(text, widthMm)', 'literal' => true, 'note' => 'Returns the wrapped lines.']],
    'inputs' => [['text', 'Label text'], ['widthMm', 'Label width in millimetres']],
    'outputs' => [['lines', 'Wrapped lines']],
    'steps' => ['Measure the text.', 'Wrap at the label width.'],
    'used' => ['Label designer'],
]]" source="Hand-written list" />
```

## Related

- `x-dl::build-board`: shows how many engines exist per group and mode and links to this catalog (prop `engines-url`).
- `x-dl::chat`: the AI Assistant can use `Calc` and `Suggest` engines as tools (`EngineTool`) and never touches salary, payroll, costing formulas or personal data.
- `x-dl::table`, `x-dl::badge`, `x-dl::alert`, `x-dl::button`: the objects the catalog is built from.
- `x-dl::help-map`: the list of screens and their guides (user help), as opposed to this developer reference.
- `skills/devlib/engine.md`: the engine contract (modes, `Result`, `EngineRegistry`, cancel and adjustment); tests `site/tests/Feature/EngineContractTest.php` and `SampleEnginesTest.php`.
