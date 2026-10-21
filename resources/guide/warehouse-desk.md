## Overview

`<x-dl::warehouse-desk>` is the complete Warehouse Map screen as one object: a building picker with shortcut buttons, the map (`x-dl::wh-map`, bin panel on the left), a Capacity per Floor card, a one-line summary, and a Generate Bins pop-up. It has three states: no building yet (empty state with a Buildings button), a building without a size (empty state asking for length and width), and the full screen. The object does not read a database: the project gives ONE ready-made array, `desk`, that it builds from its own data (building, floors, areas, bins, capacities, URLs). Every number on the screen is computed by the project.

Use it when you want the standard Warehouse Map form (one screen: pick a building, see the plan, see capacity per floor, generate bins) and your project can supply the `desk` array.

Do NOT use it when:

- you only need the plan without the picker, capacity card or pop-up: use `x-dl::wh-map` directly;
- you need a list of bins or stock: use `x-dl::grid`;
- the screen must work in a different way (other fields, other cards): build your own screen with `x-dl::wh-map`, `x-dl::grid`, `x-dl::card` and the other objects.

### Where the data source goes (`source`)

The object has NO `source` prop of its own: its only constructor prop is `desk`. The data-source names (shown by the developer catalog / Manifest overlay on each part, `data-dl-src`) are given in a neutral way: the class names no table or column of any project. A project that wants them subclasses the object and overrides `sources()` (protected, returns `['building' => [...], 'map' => [...], 'capacity' => [...], 'zone' => [...]]`, each a list of source names) and, if needed, `src(string ...$tables)` (joins the names with ` + ` by default). The view passes the result to the `source` prop of the parts it contains: `x-dl::field.select` (building, storage area), `x-dl::wh-map` and `x-dl::grid` (capacity). Without an override `sources()` is empty, `srcFor()` returns `null` and the parts keep their own default source text (for example `denah gudang + isi bin` for the map). The release checklist in `docs/versioning.md` states this as "source data in prop `source`": that is the `source` prop of the contained parts, fed by `srcFor()`, not a prop of this object (see the oddity below).

### Markup produced

```html
<div class="dl-flat" data-dl="x-dl::warehouse-desk" data-dl-class="WarehouseDesk ← DevlibComponent" data-dl-src="—">     <!-- display:contents -->
  <!-- no building: x-dl::empty-state + Buildings button -->
  <form method="get" action="<home>">                                                           <!-- x-dl::action-form -->
    <x-dl::action-bar>  Building select (wh) · Pick allocation select (pick, optional) · Layer checkbox (putaway) · Show · shortcut buttons · Generate Bins </x-dl::action-bar>
  </form>
  <!-- building without size: x-dl::empty-state -->
  <div class="dl-whmap" id="wh-map-<wh>"> ... </div>                                            <!-- x-dl::wh-map -->
  <!-- x-dl::card "Capacity per Floor" with x-dl::grid id="wh-capacity-<wh>" (mode report) and an optional warn alert -->
  <!-- x-dl::alert tone=info (summary) and, when set, tone=warn (unassigned) -->
  <!-- x-dl::modal id="wh-generate" with the Generate Bins form -->
</div>
```

The root carries `dl-flat` (`display:contents`), so the parts lay out as direct children of the page area. Ids used: `wh-map-<wh>` (the map), `wh-capacity-<wh>` (the capacity grid; its table is remembered by id in the browser), `wh-generate` (the modal), `wh-generate-form` (its form).

## Props

Constructor of `Devlib\Components\WarehouseDesk` (1 prop). Blade name is `desk`; bind it with `:desk="$desk"`.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `desk` | `array`, `[]` | The whole screen as one ready-made array built by the project (see the key list below). An empty array is NOT usable: the view reads `wh`, `links`, `options`, `generate`, `home` and the URL keys directly. |

Common attribute: `can="module.action"`; without the ability nothing is rendered. Extra HTML attributes are merged onto the root.

### Keys of `desk`

Required unless marked optional. The view indexes them directly, so a missing required key raises an error.

| Key | Meaning |
|---|---|
| `wh` | Id of the chosen building. A falsy value (`null`, `0`) = the "No warehouse yet" empty state (only `buildingsUrl` is used then). |
| `home` | Address of this screen: the building form submits to it with GET (`wh`, `pick`, `putaway`). |
| `title` | Title of the map and of the "no size" empty state. Wrap in `raw()` if it holds data (the view already wraps it). |
| `options` | `id => label` of the buildings in the `Building` dropdown. |
| `buildingsUrl`, `zonesUrl` | Targets of the `Buildings` and `Stacking Height per Area` buttons. |
| `links` | List of `[icon, label, url]` for the shortcut buttons (for example Buildings, Storage Areas, Bins, Stack Monitor). May be `[]`. |
| `pickOptions`, `pick` | Optional. `pickOptions` = `id => label` of open pick lists; when not empty the `Pick allocation` dropdown appears (`None` = the plain map), `pick` is the chosen id. |
| `putaway` | Optional bool: state of the `Layer: Putaway` checkbox (always shown). |
| `generate` | Array: `show` (bool, shows the `Generate Bins` button), `action` (POST address of the pop-up form), `zones` (`id => label` of the storage areas offered in the pop-up). |
| `map` | `null` (or missing) = the building has no size yet. Otherwise the array below. |

Keys of `map` (all required when `map` is not `null`, except `layer`): `width`, `height`, `outline`, `floors`, `bins`, `floor`, `selected`, `saveUrl`, `restackUrl`, `actions` (these go straight to the props of the same name of `x-dl::wh-map`, see its entry for their format), `layer` (optional, default `[]`), `capacity` (rows of the capacity grid), `missingHeight` (bool), `summary` (text of the info alert), `unassigned` (text of a warning alert, or `null`).

## Behavior

### States

1. No building: empty state `No warehouse yet` with a `Buildings` button. Nothing else is shown (the modal is not rendered either).
2. Building without size (`map` is `null`): the top bar is shown, then the empty state `This building has no size yet...` with a `Buildings` button.
3. Full: top bar, map, Capacity per Floor card, info alert, optional warning, Generate Bins modal.

### Top bar

- A GET form to `home`: `Building` dropdown (`wh`), optional `Pick allocation` dropdown (`pick`), `Layer` checkbox with the text `Putaway` (`putaway`), and the `Show` button. Changing the building does nothing until `Show` is pressed (there is no auto-submit). The project reads `wh`, `pick`, `putaway` and builds the next `desk`.
- Shortcut buttons come from `links`; `Generate Bins` (only when `generate.show`) opens the pop-up.

### Map

The plan is exactly `x-dl::wh-map` (search, legend filters, floor tabs, bin panel, QR, stack, actions such as Transfer / Label / Block / Unblock, optional Edit Layout when `saveUrl` is set). The layer (pick allocation or putaway tasks) is drawn when `map.layer` has content. Behaviour, keyboard and accessibility are those of `x-dl::wh-map`.

### Capacity per Floor

An `x-dl::card` with two buttons (`Building Size & Height` and `Stacking Height per Area`) and a report-mode `x-dl::grid` (10 rows per page, search box, sums on the columns marked below). The columns are fixed by `capacityColumns()`; each row of `map.capacity` is one floor and must use these keys:

| Column (label) | Key | Type |
|---|---|---|
| Floor | `floor` | text |
| Floor Area (m²) | `area` | number, summed |
| Clear Height (m) | `height` | number |
| Room Volume (m³) | `room` | number, summed |
| Storage Areas (m²) | `storage` | number, summed |
| Storage % of Floor | `storage_pct` | number |
| Storage Volume (m³) | `cap_m3` | number, summed |
| In Use (m³) | `used_m3` | number, summed |
| Volume Used % | `pct_m3` | progress bar |
| Capacity (kg) | `cap_kg` | number, summed |
| In Use (kg) | `used_kg` | number, summed |
| Weight Used % | `pct_kg` | progress bar |
| Bins | `bins` | number, summed |

With `map.missingHeight` a warning alert explains that a floor without clear height has no volume.

### Generate Bins pop-up

A modal (`wh-generate`) with a POST form (CSRF token included) to `generate.action`: `zone_id` (required, from `generate.zones`), `rows_from`, `rows_to` (default 1 to 4), `cols_from`, `cols_to` (default 1 to 4), `levels` (default 1), `capacity_kg`, `capacity_pallet`. The hints tell the rules: a floor area uses lanes and slots, a rack area uses racks, columns and levels, an open area makes one bin and ignores the numbers; bin codes are made from the building, the area and the position (for example `A10-PPR-L03-05`); existing bins are skipped, so it can be run again to add lanes. The footer has `Cancel` and `Generate` (the submit button is outside the form and linked by `form="wh-generate-form"`). The form has NO building field: the building must be part of `generate.action` (or known to the server). The server creates the bins; the object does only the form.

### Screen text, language and rights

- All fixed texts are English and translated through the dictionary (labels, buttons, hints, empty states, alerts, modal, grid column labels). `title`, `summary` and `unassigned` are passed through `raw()` by the view: they are DATA and are not translated, so the project formats them (and numbers in them) for the user's language.
- Access: `can="module.action"` on the tag hides the whole screen. Protect `generate.action`, the map action URLs and `saveUrl` on the server too.

### Accessibility as implemented

Comes from the parts: real labels on the dropdowns and checkbox, native buttons, a modal; for the map see `x-dl::wh-map`. The alerts have no `role="alert"`.

## Data and events

### What the server receives

| From | Method and fields |
|---|---|
| Top bar | GET `home?wh=<id>&pick=<id>&putaway=<value>` (`pick` is sent only when the dropdown exists). |
| Generate Bins | POST `generate.action` with `_token`, `zone_id`, `rows_from`, `rows_to`, `cols_from`, `cols_to`, `levels`, `capacity_kg`, `capacity_pallet`. |
| Map | Calls of `x-dl::wh-map`: JSON POST to `saveUrl`, `restackUrl` and POST actions. |

### Events

No event of its own. The map emits `dl:whmap-select` (`{id, code}`, bubbles) when a bin panel opens; the modal and the grid use the normal DevLib behaviour.

### Markup hooks and JS

There is no JS class for the desk itself (no `resources/assets/js/components/warehouse-desk.js`). The behaviour comes from the contained objects: `WhMap`, `Grid`, `Modal` (`data-dl-open="wh-generate"`, `data-dl-close-modal`), `Select`.

### Subclassing

```php
use Devlib\Components\WarehouseDesk;

// A project keeps its own tag and data-source names (fictional table names here).
class StockWarehouseDesk extends WarehouseDesk
{
    public function tag(): string { return 'x-app::warehouse-desk'; }

    protected function sources(): array
    {
        return ['building' => ['warehouses'], 'map' => ['bins', 'stock_lots'], 'capacity' => ['bins'], 'zone' => ['storage_areas']];
    }
}
```

`src()` can be overridden as well (for example to turn table names into data-dictionary captions).

## Rules and pitfalls

- Build `desk` in one data class of the project, not in the view. The view does no query and no calculation.
- All required keys must be present, even when empty (`links` => `[]`). `generate` is read as `$g['show']` and must be an array even if `show` is false.
- `desk.map` must be `null` (not `[]`) when the building has no size; an empty array would try to draw.
- `wh-map` ids are `wh-map-<wh>`: one building at a time per page. The bin data of the chosen building is serialised into the page, so keep one building per request.
- `title`, `summary`, `unassigned` are shown as given. Do not put untranslated text of the wrong language there.
- The `Layer: Putaway` checkbox is always shown, also when the project has no putaway layer; ignore `putaway` on the server in that case, or use `x-dl::wh-map` and build your own bar.
- The capacity columns and their keys are fixed by the object; extra keys in `map.capacity` rows (for example `id`, `areas`) are ignored.
- Keep the project's table names out of the shared class; use the `sources()` override in the project.
- Code oddity (not fixed): `docs/versioning.md` (checklist for 1.0.0) says the data source of `warehouse-desk` is given "in prop `source`", but `WarehouseDesk::__construct` has only `desk`. The source names are supplied through the protected `sources()` / `src()` methods of a subclass and reach the `source` prop of the contained parts. The class itself is already neutral (`sources()` returns `[]`), so the wording of the checklist item does not match the code.
- Code oddity (not fixed): the view writes `$g = $d['generate'] ?? null` as if `generate` were optional, then reads `$g['show']`, `$g['action']` and `$g['zones']` without a guard; a missing `generate` key raises an error when a building is chosen. The same goes for `links`, `options`, `home`, `buildingsUrl`, `zonesUrl` and every `map` key.
- Code oddity (not fixed): the sample data `site/resources/data/warehouse-desk.json` holds `qr` values that are full addresses on the host of another project, so the QR in the demo points outside DevLib. Sample data should use neutral values.

## Example

The catalog demo (`site/resources/views/demos/warehouse-desk.blade.php`) feeds a sample building (30 x 20 m, 2 floors, 145 bins) from a JSON file:

```blade
<x-dl::warehouse-desk :desk="json_decode(file_get_contents(resource_path('data/warehouse-desk.json')), true)" />
```

In a project, from its own data class:

```blade
<x-dl::warehouse-desk :desk="Warehouse::desk($home)" can="inventory.view" />
```

Shape of `desk` (fictional values):

```php
[
    'wh' => 16, 'home' => route('warehouse.map'), 'title' => 'A10 · Warehouse A10',
    'options' => [16 => 'A10 · Warehouse A10', 17 => 'A5 · Warehouse A5'],
    'buildingsUrl' => route('buildings.index'), 'zonesUrl' => route('zones.index'),
    'links' => [['fa-building', 'Buildings', route('buildings.index')], ['fa-table-cells', 'Bins', route('bins.index')]],
    'generate' => ['show' => true, 'action' => route('bins.generate', 16), 'zones' => [54 => 'PPR · Plano Paper · Floor 1 · FLOOR', 56 => 'INK · Ink & Chemicals · Floor 1 · RACK']],
    'map' => [
        'width' => 30, 'height' => 20, 'outline' => [[0, 0], [20, 0], [20, 8], [30, 8], [30, 20], [0, 20]],
        'floors' => $floors, 'bins' => $bins, 'floor' => null, 'selected' => null,
        'saveUrl' => route('zones.layout'), 'restackUrl' => '/stack/{id}/{dir}',
        'actions' => [['label' => 'Transfer', 'icon' => 'fa-right-left', 'tone' => 'primary', 'when' => 'stock', 'url' => '/transfer?from={code}']],
        'capacity' => [['floor' => 'Floor 1', 'area' => 600, 'height' => 7, 'room' => 4200, 'storage' => 472.5, 'storage_pct' => 78.8, 'cap_m3' => 1176.4,
                        'used_m3' => 27.42, 'pct_m3' => 2.3, 'cap_kg' => 173400, 'used_kg' => 21781, 'pct_kg' => 12.6, 'bins' => 99]],
        'missingHeight' => false, 'summary' => 'Floor area 600 m2 on 1 floor, 99 bins, 21,781 of 173,400 kg in use.', 'unassigned' => null,
    ],
]
```

## Related

- `x-dl::wh-map`: the plan inside this screen; its entry describes bins, floors, actions, layer and Edit Layout.
- `x-dl::grid`: the capacity table (report mode).
- `x-dl::card`, `x-dl::alert`, `x-dl::empty-state`, `x-dl::modal`, `x-dl::action-form`, `x-dl::action-bar`, `x-dl::button`: the parts the screen is made of.
- `x-dl::field.select`, `x-dl::field.checkbox`, `x-dl::field.number`: the fields in the top bar and in the Generate Bins form.
- `x-dl::label`, `x-dl::label-sheet`, `x-dl::scan`: the label and scan screens that go with warehouse bins.
- `docs/versioning.md`: the 1.0.0 checklist that mentions the data source of this object.
