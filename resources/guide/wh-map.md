## Overview

`<x-dl::wh-map>` draws a warehouse as a plan: one building outline (a rectangle, an L shape or any polygon, in METRES), one drawing per floor, coloured storage areas (zones) on each floor, and the bins as small boxes inside the areas. Clicking a bin opens a panel on the left with what it holds: status, capacity bars, a QR label, the stack seen from the side, the items, and action buttons. Search, a status legend that filters, floor tabs and an optional Edit Layout mode (drag or resize an area and save) are built in.

The object only DRAWS what it is given. There is no database or model behind it: the screen (the project) computes the positions of areas and cells, the content of every bin, and the statuses, and hands them over as plain arrays. All bins are serialised into the page (`data-config`) and the panel is filled in the browser. Server round trips happen only for the optional URLs: `save-url` (move/resize an area), `restack-url` (swap two stacked units) and POST buttons in `actions`.

Colour language of a bin box: empty = white, in use = blue, full = amber, over capacity = orange, blocked = hatched grey, DEAD STOCK = red (red wins over the fill colour). Small dots in a box: red = dead stock, yellow = expired or expires soon, orange = buried (the unit that must be used first is under others), violet = remnant. A small number at the bottom right = number of stacked units, `×n` at the top right = a rack cell with n bins (levels).

Use it when:

- people must SEE where stock is: find a bin, see how full it is, see dead stock and expiry on the plan, open the bin and act on it (transfer, label, block);
- the layout is stored by the project and needs to be adjusted visually (Edit Layout).

Do NOT use it when:

- you need the whole screen with a building picker, capacity per floor and a Generate Bins pop-up: use `x-dl::warehouse-desk`, which already contains this map;
- you only need a list of bins or stock: use `x-dl::grid`;
- you need a free drawing/diagram: use `x-dl::flow` or `x-dl::blueprint`;
- you need labels to stick on bins: use `x-dl::label` and `x-dl::label-sheet`.

### Markup produced

```html
<div class="dl-whmap" data-dl="x-dl::wh-map id=demo-whmap" data-dl-class="WhMap ← DevlibComponent" data-dl-src="denah gudang + isi bin"
     data-dl-whmap data-config='{"id":..,"bins":{..},"actions":[..],"selected":..,"floor":1,"saveUrl":..,"restackUrl":..,"csrf":..,"scale":10,"status":{..},"empty":"..","text":{..}}'>
  <div class="dl-whmap__bar">
    <b class="dl-whmap__title">Title</b>                              <!-- only with title -->
    <div class="dl-whmap__floors" role="tablist"><button data-wm-floor="1" class="is-active">Floor 1</button>...</div>  <!-- only with 2+ floors -->
    <label class="dl-whmap__search"><input type="search" data-wm-search></label>
    <span class="dl-whmap__found" data-wm-found></span>
    <span class="dl-whmap__spacer"></span>
    (slot "tools")  <button data-wm-edit>Edit Layout</button>          <!-- only with save-url -->
  </div>
  <div class="dl-whmap__legend"> <button data-wm-only="EMPTY|IN_USE|FULL|OVER|BLOCKED|DEAD|EXPIRY|REMNANT|BURIED"> swatch label <b>count</b> </button>... </div>
  <div class="dl-whmap__body">
    <div class="dl-whmap__canvas">
      <svg class="dl-whmap__svg" data-wm-svg="1" viewBox="..." role="img" aria-label="Title Floor 1">      <!-- one per floor -->
        <polygon class="dl-whmap__floor"/> grid <polygon class="dl-whmap__wall"/> two dimension labels (m)
        <g class="dl-whmap__zone" data-wm-zone="id" data-tone="accent" data-x data-y data-w data-h transform="translate(...)">
          <rect class="dl-whmap__zrect"/> <text class="dl-whmap__zname">CODE · Name</text> <text class="dl-whmap__znote"/>
          <g class="dl-whmap__cell dl-whmap__cell--in_use [is-dead]" data-wm-cell="binId[,binId]" data-status data-dead data-expiry data-remnant data-buried data-find tabindex="0"> rect, label, dots, counts </g>
          <rect class="dl-whmap__grip" data-wm-grip/>                   <!-- only with save-url -->
        </g>
        <g class="dl-whmap__layer" data-wm-layer> route, marks, end arrow </g>   <!-- only with layer -->
      </svg>
    </div>
    <aside class="dl-whmap__panel" data-wm-panel> empty text, or layer summary, or the bin/area panel </aside>
  </div>
</div>
```

Scale: 1 metre = 10 drawing units (`WhMap::SCALE`), and 1.5 m of empty margin is left around the building for the two dimension labels. The panel is the FIRST column (CSS `order:-1`, width `clamp(260px, 32%, 350px)`), the plan the second; when the component is narrower than 640px (container query) the panel moves above the plan and stops being sticky.

## Props

Constructor of `Devlib\Components\WhMap` (16 props). Blade names are kebab-case; use `:prop="expr"` for numbers, arrays and `null`. Text props `title`, `search` and `empty` are translated through the dictionary (wrap data in `raw()`).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `id` | ``int`, `string` or `null`; default `null``, `'whmap'` | Identity of the map. Used in the tag shown by the developer catalog and to build unique SVG pattern ids (`dl-whmap-hatch-<id>-<floor>`, `dl-whmap-grid-<id>-<floor>`). Must be unique per page when two maps are shown. |
| `title` | `?`int`, `string` or `null`; default `null``, `null` | Bold title in the top bar and the start of each SVG `aria-label` (`<title> <floor label>`). `null` = no title. Translated. |
| `width` | `float`, `0` | Building length in metres (the top dimension label and the width of the drawing area). `0` = take the largest x of `outline`. With neither `outline` nor `width`, the plan has zero size. |
| `height` | `float`, `0` | Building width in metres (the left dimension label). `0` = take the largest y of `outline`. |
| `outline` | `array`, `[]` | Building outline: list of `[x, y]` points in metres, clockwise from top-left, for example an L shape `[[0,0],[20,0],[20,8],[30,8],[30,20],[0,20]]`. Empty = a rectangle `width` x `height`. Not validated. The drawing area is `width` x `height`, so an outline that goes outside it is cut off. The same outline is used for every floor. |
| `floors` | `array`, `[]` | The floors, see [floors](#floors-zones-and-cells). One `<svg>` is rendered per floor; with two or more floors a tab bar is shown. An empty list draws no plan at all (bar, legend and panel only). |
| `bins` | `array`, `[]` | `id => bin` map, see [bins](#bins). Ids may be int or `int`, `string` or `null`; default `null` (but no comma); the key is what `cells[].bins`, `selected` and `{id}` refer to. Bins not referenced by any cell are not drawn, but still count in the legend. |
| `actions` | `array`, `[]` | Buttons shown at the bottom of a bin panel, see [actions](#actions). |
| `floor` | `?int`, `null` | The floor shown at first (matches `floors[].no`). `null` = the `no` of the first floor, or `1`. A floor that does not exist shows no plan. |
| `selected` | `int`, `string` or `null`; default `null` | Bin id opened when the page loads: the plan switches to its floor, the box is highlighted and the panel is filled (and `dl:whmap-select` is emitted). Ignored when the id is not a key of `bins` or is not placed in a cell. |
| `search` | ``int`, `string` or `null`; default `null``, `'Search bin, item, lot...'` | Placeholder of the search box. Translated. |
| `empty` | ``int`, `string` or `null`; default `null``, `'Click a bin on the map to see what is inside.'` | Text of the panel before any bin is chosen (replaced by the layer summary when `layer` has rows or notes). Translated. |
| `save-url` | `?`int`, `string` or `null`; default `null``, `null` | Turns on Edit Layout. Adds the `Edit Layout` button and a resize grip on each area. Moving or resizing an area POSTs JSON `{zone, x, y, w, h}` (metres, snapped to 0.5) to this address with the CSRF token. `null` = read-only map. |
| `restack-url` | `?`int`, `string` or `null`; default `null``, `null` | Address (POST) used by the up/down buttons of a stacked unit, with `{id}` = unit id and `{dir}` = `up` or `down`. `null` = no swap buttons. |
| `source` | `?`int`, `string` or `null`; default `null``, `null` | Free text naming where the data comes from, written in `data-dl-src` (default `denah gudang + isi bin`). No runtime effect. |
| `layer` | `array`, `[]` | Optional overlay on the plan, see [layer](#layer). `[]` = the plain map. |

Common attribute: `can="module.action"`; without the ability nothing is rendered. Extra HTML attributes are merged onto the root `div.dl-whmap`.

Slot: `<x-slot:tools>` puts extra markup in the top bar, after the spacer and before the Edit Layout button (for example an export button).

### Floors, zones and cells

```php
$floors = [[
    'no' => 1, 'label' => 'Floor 1',                       // label optional (default "Floor <no>")
    'zones' => [[
        'id' => 1, 'code' => 'PPR', 'name' => 'Plano Paper',
        'tone' => 'accent',                                // accent|violet|amber|lime|green|danger|pink|orange|neutral (default accent)
        'x' => 0.5, 'y' => 0.5, 'w' => 13.5, 'h' => 10.5,  // metres on the plan
        'note' => 'optional small text at the bottom',
        'cells' => [[
            'x' => 0.9, 'y' => 1.5, 'w' => 1.9, 'h' => 1.6, // ABSOLUTE metres on the plan (not relative to the zone)
            'label' => 'L01-01',
            'bins' => ['L-PPR-L01-01'],                    // one bin, or several for a rack cell (levels)
        ]],
    ]],
]];
```

- Required keys: floor `no`; zone `id`, `code`, `name`, `x`, `y`, `w`, `h`; cell `x`, `y`, `w`, `h`, `label`. The view reads them directly, so a missing one raises an error. `tone`, `note`, `cells` and `label` of a floor are optional.
- A cell is one position on the plan; a multi-level rack cell lists several bins and the panel shows a level switch.
- Cell colour = the "most important" status of its bins: OVER, then FULL, then IN_USE; BLOCKED only when ALL its bins are blocked; otherwise empty. A cell with any dead bin is red.
- The label font size is computed from the cell size (between 1.6 and 3.2 units).

### Bins

Each entry of `bins` is an array. Every key is optional unless noted.

| Key | Meaning |
|---|---|
| `code` | Text shown in the panel header and used by search and by `{code}` in actions. |
| `title` | Small text under the code in the panel header (for example the area name). |
| `status` | One of `EMPTY`, `IN_USE`, `FULL`, `OVER`, `BLOCKED` (default `EMPTY`). Any other value is not supported (see the oddities). |
| `dead` | `true` = dead stock: red box, panel badge `Dead stock`, red bar. |
| `capacity`, `pct` | One capacity line (text, and a bar from `pct` 0..100; over 100 = orange bar). Used only when `caps` is empty. |
| `caps` | List of `['label', 'value', 'pct' (optional), 'note' (optional)]`: several capacity lines (weight, volume, floor area). Replaces `capacity` + `pct`. |
| `capacityLabel` | Extra line beside the QR, for example `Max 3,000 kg`. (Used by the script; not in the class header.) |
| `note` | Paragraph in the panel, for example the reason a bin is blocked. |
| `qr` | Content of the QR shown in the panel (drawn by `data-dl-qrcode`). No `qr` = no QR block. |
| `level` | Label of this bin in the level switch of a rack cell. |
| `expiry` | `true` = yellow dot and the legend filter `Expired / expires soon`. |
| `remnant`, `buried` | `true` = violet / orange dot and a legend filter each. |
| `count` | Number of stacked units, printed in the box. |
| `stack` | Units seen from the side, listed from the TOP down: `['id', 'no' (1 = bottom), 'kind' (pallet or pack), 'name', 'sub', 'tone' (danger or null), 'up' (bool), 'down' (bool), 'tags' => [['label', 'tone']]]`. A pallet is drawn with a wooden base. `up` / `down` show the swap buttons (only when `restack-url` is set). |
| `items` | Table in the panel: `['code', 'name', 'lot' (optional), 'qty' (text), 'idle' (text), 'dead' (bool), 'tags' => [['label', 'tone']]]`. `lot` and `code` are also searched. |

Tag tones: `danger`, `warn`, `info`, `violet`, `orange` or none.

### Actions

```php
$actions = [
    ['label' => 'Transfer', 'icon' => 'fa-right-left', 'tone' => 'primary', 'when' => 'stock', 'url' => '/inventory/transfer?from={code}'],
    ['label' => 'Block', 'icon' => 'fa-ban', 'tone' => 'danger', 'when' => 'usable', 'url' => '/bins/{id}/block', 'post' => true, 'confirm' => 'Block this bin?', 'reason' => true],
    ['label' => 'Unblock', 'when' => 'blocked', 'url' => '/bins/{id}/unblock', 'post' => true],
];
```

- `label` (text), `icon` (Font Awesome name, solid), `tone` (`primary` / `secondary` / `danger`, default `secondary`), `url`, `blank` (open a link in a new tab).
- `when`: `stock` = only bins that have `items`; `usable` = status is not `BLOCKED`; `blocked` = status is `BLOCKED`; omit = always. (`stock` looks at `items`, not at `stack`.)
- `{id}` (the bin key) and `{code}` (the bin `code`) are URL-encoded and replaced in `url` (first occurrence only).
- Without `post` the button is a link. With `post: true` it POSTs JSON `{reason}` to the url; `confirm` shows a confirm box first, and `reason: true` also asks for a reason text, sent as `reason`. If the answer JSON has `redirect` the browser goes there, otherwise the page reloads. A failure shows the server `message` (or `Could not save the layout.`) as a toast.

### Layer

An optional overlay for work in progress (for example the allocation of a pick list, or putaway tasks). Everything is drawn by the server-side view; the layer never changes bin data.

```php
$layer = [
    'marks' => [['bin' => 12, 'text' => '1', 'tone' => 'pick'], ['bin' => 40, 'text' => 'P', 'tone' => 'put']], // numbered circle at the cell centre (put = dashed)
    'route' => [12, 15, 40],        // dashed line through these bins in order (bins on other floors are skipped on this floor)
    'end'   => 90,                  // destination bin, marked with an arrow
    'title' => 'Pick list PL-001',  // panel content before a bin is chosen
    'rows'  => [['no' => '1', 'bin' => 'A-01', 'qty' => '120']],
    'notes' => ['Take the older lot first.'],
];
```

The panel shows `title`, a table of `rows` (columns `#`, `Bin`, `Qty` from keys `no`, `bin`, `qty`) and the `notes`, until the user chooses a bin or an area.

## Behavior

### Choosing

- Click (or Enter / Space on the focused box) on a cell opens the panel of its first bin; for a rack cell the level buttons switch bins. The cell gets `is-sel`.
- Click on an AREA (outside a box) opens an area summary: how many bins are in use out of all, and for each used bin its stack or items with a button that jumps to that bin.
- Floor tabs: only one floor's `<svg>` is visible at a time; a search result that exists only on another floor switches the floor automatically.

### Search and filter

- The search box matches (case-insensitive, sub`int`, `string` or `null`; default `null`) the bin code, and the code, name and lot of the items. Matching boxes get `is-hit`, the others are dimmed (`is-dim`); the counter beside the box says `n bins match` or `No bin matches the search.`.
- A legend button toggles a filter: a status shows boxes with that status (dead boxes are excluded from status filters), `Dead stock`, `Expired / expires soon`, `Remnant` and `Buried` show boxes with the flag. Search and legend filter work together. The three flag buttons appear only when at least one bin has that flag; `Dead stock` and the five statuses always appear.
- The numbers in the legend count BINS (not boxes).

### Edit Layout (only with `save-url`)

- The button toggles `is-edit`: clicks on boxes are disabled; an area can be dragged by its body, or resized by the corner grip (minimum 1 m). Positions snap to 0.5 m and cannot go below 0.
- When the pointer is released a changed area is POSTed to `save-url`. On success a toast `Layout saved.` shows; after a RESIZE the page reloads (and Edit Layout is switched on again) because the server recomputes where the bins sit inside the area. On failure the page reloads with the toast `Could not save the layout.` (or the server message).
- The server must keep cell positions consistent with the saved area; the object does not move the cells in the data.

### Panel content (bin)

Header (code, title, status badge), level buttons (rack cell), capacity lines with bars, note, stack view (top unit first, with swap buttons), QR with code, items table (`Item`, `Qty`, `Idle`, dead items get a `Dead stock` badge), action buttons. A bin without items shows `No stock in this bin.`.

### Statuses

`EMPTY`, `IN_USE`, `FULL`, `OVER`, `BLOCKED` (class `dl-whmap__cell--<status lower case>`), plus the flags dead, expiry, remnant, buried. There is no state change in the object; the project changes bin data and re-renders.

### Screen text, language and rights

- Translated at render time: `title`, `search`, `empty`, status names, legend labels, panel labels (`Bin`, `Capacity`, `Item`, `Qty`, `Idle`, `Level`, `Label`, `Area`, ...), messages, `Edit Layout`, `Floor`. These texts are copied into `data-config`, so they follow the language at load time.
- Data (bin codes, item names, tags, notes, action labels, zone names, `capacity` text) is NOT translated. Write the number format in the project's locale before passing it; the object does not format `capacity`, `qty` or `caps` text. Only the two dimension labels are formatted by `Locale::decimal`.
- Access: `can="module.action"` on the tag. The actions themselves must be protected on the server; hiding a button is not security.

### Accessibility as implemented

- Each `<svg>` has `role="img"` and an `aria-label` (title + floor label), so a screen reader gets one label per floor, not the bins.
- Boxes have `tabindex="0"` and work with Enter / Space, but have no `role` or `aria-label`; the panel is not an `aria-live` region. Areas are not focusable. Edit Layout is pointer-only.
- The floor bar has `role="tablist"` but its buttons have no `role="tab"` or `aria-selected`.
- Colour is not the only signal for some states (hatch for blocked, number badges), but full / over / in use rely on colour alone.

## Data and events

### What the server receives

The object posts nothing by itself (no form inputs). The server is called only by:

| Call | Body |
|---|---|
| `save-url` | JSON `{zone, x, y, w, h}` (zone id as in `zones[].id`, metres), headers `Accept: application/json`, `Content-Type: application/json`, `X-CSRF-TOKEN`. |
| `restack-url` (button of a stacked unit) | JSON `{reason: ''}` to the url with `{id}` and `{dir}` filled. |
| `actions` with `post` | JSON `{reason}` to the action url. |

The CSRF token is put in the page only when `save-url`, `actions` or `restack-url` is used. Answer with JSON (a failed call: a non-2xx status and `{message}`; a success: `{}` or `{redirect}`).

### Event

`dl:whmap-select` is dispatched on the root when a bin panel is opened (a click, a level button, or `selected` at load); it bubbles.

```js
document.addEventListener('dl:whmap-select', e => {
  // e.detail = { id: 'L-PPR-L03-01', code: 'L-PPR-L03-01' }   (id is a `int`, `string` or `null`; default `null`)
});
```

It is not emitted for the area summary.

### Markup hooks and JS

| Hook | Where |
|---|---|
| `data-dl-whmap`, `data-config` | root and configuration (the class `WhMap` in `resources/assets/js/components/wh-map.js` mounts on it). |
| `data-wm-svg="<floor no>"`, `data-wm-floor="<no>"` | plan and tab of a floor. |
| `data-wm-zone`, `data-wm-cell`, `data-wm-grip` | area, bin box (comma-separated bin ids), resize grip. |
| `data-wm-search`, `data-wm-found`, `data-wm-only` | search box, match counter, legend buttons. |
| `data-wm-panel`, `data-wm-level`, `data-wm-goto`, `data-wm-post`, `data-wm-edit` | panel, level button, jump button, POST button, Edit Layout button. |
| `data-find` (on a cell) | lower-case search text built by PHP. |

`el.dlComponent` is the `WhMap` instance (methods `pick(cell, id)`, `show(id)`, `zone(el)`, `floor(no)`, `filter()`). Override with `data-dl-js` + `Devlib.register(class MyMap extends Devlib.classes.WhMap {...})`.

## Rules and pitfalls

- Everything in metres, with `cells` in ABSOLUTE plan coordinates (the view subtracts the zone origin). Keep cells inside their zone and zones inside `outline`.
- The positions of zones and cells and the content of bins belong to the project. Do not build the plan by hand in HTML; use this object (rule `L001` flags raw HTML in screens).
- Do not use a comma in a bin id (the cell attribute `data-wm-cell` joins ids with commas).
- `status` must be one of the five values; compute `dead`, `expiry`, `remnant`, `buried` on the server.
- Bin data is serialised into the page: for a very large warehouse (thousands of bins) the page gets heavy. Load one building at a time (this is what `x-dl::warehouse-desk` does).
- `{id}` / `{code}` / `{dir}` are replaced once per `int`, `string` or `null`; default `null`; do not repeat a placeholder in a url.
- `capacity`, `qty`, `caps` values and tag texts are shown as given: format numbers with the locale before you pass them.
- A layer is just a drawing: it does not select, filter or change bins.
- Edit Layout is for an authorised planner: protect `save-url` on the server.
- Code oddity (not fixed): `WhMap::layerPoint()` casts bin ids with `(int)` (`in_array((int) $binId, array_map('intval', $c['bins']), true)`). With `int`, `string` or `null`; default `null` bin ids such as the demo codes (`L-RCV`), every non-numeric id becomes `0` and matches the first cell that has a non-numeric bin, so layer marks, route and end arrow land on the wrong cell. Layers only work with numeric bin ids.
- Code oddity (not fixed): `WhMap::counts()` does `$c[$b['status'] ?? 'EMPTY']++` over an array of the five statuses; a bin whose `status` is any other `int`, `string` or `null`; default `null` reads an undefined array key (a warning, which Laravel turns into an exception) when the view renders.
- Code oddity (not fixed): the legend counts and the legend filter disagree for dead bins. `counts()` counts a dead bin once under its `status` AND once under `Dead stock`, but the status filters in `wh-map.js` (`filter()`) skip dead boxes, so a status button can show a bigger number than the boxes it highlights.
- Code oddity (not fixed): `data-find` is built with PHP `strtolower` (ASCII only from PHP 8.2) while the typed search uses JavaScript `toLowerCase()`, so a bin code or item name with an upper-case accented letter is not found by typing the same letter.
- Code oddity (not fixed): Edit Layout clamps a dragged area at `0` only (`Math.max(0, snap(...))`); the comment says it stays inside the canvas, but there is no upper limit, so an area can be saved outside the building.
- Code oddity (not fixed): the class header lists layer `rows` as `no, bin, sub, qty`, but the view renders only the columns `#`, `Bin`, `Qty`; `sub` is never shown.
- Code oddity (not fixed): the class header lists zone tones `accent|violet|amber|lime|green|danger|pink|neutral`; the CSS also supports `orange`. The header also omits the bin key `capacityLabel` and the item key `lot`, both used by the script.

## Example

Small self-contained example (fictional data; the full catalog demo is `site/resources/views/demos/wh-map.blade.php` with the sample data in `site/app/Support/DemoData.php`).

```blade
@php
    $bins = [
        101 => ['code' => 'A-01-01', 'title' => 'Plano Paper', 'status' => 'IN_USE', 'pct' => 69, 'capacity' => '2,070 / 3,000 kg', 'qr' => 'LOC:A-01-01',
                'items' => [['code' => 'IV-300', 'name' => 'Ivory 300 gsm 79 x 109', 'qty' => '8,000 sheets', 'idle' => '12 days', 'dead' => false]]],
        102 => ['code' => 'A-01-02', 'title' => 'Plano Paper', 'status' => 'FULL', 'pct' => 95, 'capacity' => '2,850 / 3,000 kg', 'qr' => 'LOC:A-01-02',
                'items' => [['code' => 'KR-140', 'name' => 'White Kraft 140 gsm', 'qty' => '15,000 sheets', 'idle' => '275 days', 'dead' => true]], 'dead' => true],
        103 => ['code' => 'A-01-03', 'title' => 'Plano Paper', 'status' => 'EMPTY'],
    ];
    $floors = [['no' => 1, 'label' => 'Floor 1', 'zones' => [
        ['id' => 1, 'code' => 'PPR', 'name' => 'Plano Paper', 'tone' => 'accent', 'x' => 0.5, 'y' => 0.5, 'w' => 8, 'h' => 4, 'cells' => [
            ['x' => 1, 'y' => 1.5, 'w' => 1.9, 'h' => 1.6, 'label' => 'L01-01', 'bins' => [101]],
            ['x' => 3, 'y' => 1.5, 'w' => 1.9, 'h' => 1.6, 'label' => 'L01-02', 'bins' => [102]],
            ['x' => 5, 'y' => 1.5, 'w' => 1.9, 'h' => 1.6, 'label' => 'L01-03', 'bins' => [103]],
        ]],
    ]]];
@endphp
<x-dl::wh-map id="demo-whmap" title="Building A · 12 x 8 m" :width="12" :height="8" :floors="$floors" :bins="$bins" selected="101"
    :actions="[['label' => 'Transfer', 'icon' => 'fa-right-left', 'tone' => 'primary', 'when' => 'stock', 'url' => '/transfer?from={code}'], ['label' => 'Label', 'icon' => 'fa-qrcode', 'url' => '/label/{code}']]" />
```

L-shaped building with two floors, restack buttons and Edit Layout (the shape used by the demo):

```blade
<x-dl::wh-map id="demo-whmap" title="Building L · L shape 30 x 20 m" :width="30" :height="20"
    :outline="[[0, 0], [20, 0], [20, 8], [30, 8], [30, 20], [0, 20]]" :floors="$floors" :bins="$bins" selected="L-PPR-L03-01"
    restack-url="/stack/{id}/{dir}" save-url="/zones/layout" />
```

## Related

- `x-dl::warehouse-desk`: the complete Warehouse Map screen that wraps this map (building picker, capacity per floor, summary, Generate Bins).
- `x-dl::qr`: the QR drawn in the bin panel (`data-dl-qrcode`).
- `x-dl::label`, `x-dl::label-sheet`, `x-dl::label-guide`: stick-on labels for bins and items.
- `x-dl::scan`: scan a bin or item code on the floor.
- `x-dl::grid`: the list view of the same bins and stock.
- `x-dl::table`: used for the layer summary rows.
- `skills/devlib/components.md`: generated prop list.
