## Overview

`<x-dl::org-chart>` draws an organization structure as a top-down tree of position cards joined by lines. Each card is one position (`title`, optional `dept`) held by zero, one or several people; a card without people is shown dashed with the word "Vacant". In editable mode the user can drag a card (by its grip) onto another card to move it, with all its subordinates, under a new superior; the move can be undone, and Save only raises an event (`dl:orgchart-save`) for the host application to persist. Everyone can search people / positions, filter-highlight by department, fold and unfold branches, zoom, and download the current chart as a standalone HTML file.

The object is purely client-side: the tree is built in the browser from `nodes` (a JSON copy in `data-nodes`), there is no `save-url`, and nothing is written back to PHP. The host application listens to the events and stores the result.

Use it for a hierarchy of positions or units where holders and vacancies matter. Do NOT use it for a long flat list of employees (use `x-dl::grid`), for a card list without hierarchy (use `x-dl::member-list`), or for workflow steps and approval chains (use `x-dl::flow` or `x-dl::approval`).

## Props

Constructor of `Devlib\Components\OrgChart` (5 props). Blade names are kebab-case; pass arrays and booleans with `:prop="expr"`.

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `nodes` | `array` | `[]` | The positions, as a flat list. Each item: `['id' => scalar, 'parent' => id\|null, 'title' => string, 'dept' => ?string, 'people' => list]`. See "The nodes array" below. An empty list renders an empty canvas. Encoded with `json_encode` into `data-nodes`. Not translated. |
| `editable` | `bool` | `true` | `true` shows the Undo and Save buttons, the instruction tip, the drag grip on every card and makes every card a drop target. `false` removes all of these (no way to move anything); search, department highlight, fold, zoom and Download HTML still work. Written as `data-editable="1\|0"`. |
| `title` | `string` | `'Organization Structure'` | Title of the downloaded HTML file (`<title>` and `<h1>`), written to `data-title`. It is a standard text prop, so it is passed through the dictionary (`Lang::text`). It is NOT shown on screen above the chart: the screen title belongs to the page. |
| `source` | `?string` | `null` | Free text naming where the data comes from, for the developer catalog (`data-dl-src`). `null` = `'struktur jabatan'`. No visual or runtime effect. |
| `departments` | `array` | `[]` | List of department names (`['Finance', 'Warehouse']`). Non-empty adds a select "All departments" + one option per name to the toolbar. Choosing a department highlights (it does not hide or re-arrange) the cards whose `dept` equals the name exactly (strict string match). Empty = no select. Option texts are not translated. |

### The nodes array

```php
[
    'id'     => 7,                 // required, unique, scalar (int or string). Compared as a string.
    'parent' => 4,                 // id of the superior; null (or '') = a root card.
    'title'  => 'Shift Leader',    // the position; shown as the small accent line on the card.
    'dept'   => 'Production',      // optional; shown as a muted line and used by search / the department select.
    'people' => ['Alex Morgan', ['name' => 'Sam Lee', 'photo' => '/img/sam.jpg']],  // optional
]
```

| Key | Valid values | Notes |
|---|---|---|
| `id` | int or string | Required. Becomes `data-o-node` on the card. Two nodes with the same id break `byId` (the first one wins). |
| `parent` | an existing `id`, or `null` | Children are laid out in the order of the `nodes` list. Several roots are allowed and are drawn side by side. A node whose `parent` points to an id that does not exist, or that sits in a loop, is never rendered (the tree is built only by walking down from the roots). |
| `title` | string | Required in practice: it is the position line of the card, it is searched, and it is used in the "X is now under Y" toast. A missing title gives an empty line (and the search haystack then contains the word "undefined"). |
| `dept` | string or absent | Compared with `===` against the selected department. |
| `people` | list of `string` or `['name' => string, 'photo' => ?string]` | Empty or absent = vacant card (dashed border, italic "Vacant", an empty avatar with a user-plus icon). A plain string is the same as `['name' => string]`. The card shows up to 3 overlapping avatars and the first 2 names separated by a comma; with more than 2 holders the name line ends with `+N` (N = holders minus 2). An avatar shows the photo (`<img src>`) when `photo` is set, otherwise the initials (first letters of the first two words of the name, upper-case) on a colour whose hue is computed from the name. |

## Behavior

### Markup produced

```html
<div class="dl-org" data-dl="x-dl::org-chart" data-dl-class="OrgChart" data-dl-src="struktur jabatan"
     data-dl-org data-nodes="[...]" data-editable="1" data-title="Organization Structure">
  <div class="dl-org__bar">  <!-- toolbar -->
    Undo (data-o-undo), Save (data-o-save), Download HTML (data-o-html),
    zoom: [-] 100% [+] Fit (data-o-zoom="-1" | "1" | "0", value in data-o-zoomval),
    tip (editable only), department select (data-o-dept, only with departments), search (data-o-search)
  </div>
  <div class="dl-org__canvas" data-o-canvas><div class="dl-org__tree" data-o-tree>
    <ul><li><div class="dl-org__card [is-vacant] [is-hit]" data-o-node="7" data-o-drop>
        <span class="dl-org__grip" draggable="true" data-o-grip>...</span>
        <div class="dl-org__who"> avatars + <span class="dl-org__txt"><b>names</b><small>title</small><em>dept</em></span> </div>
        <button class="dl-org__fold" data-o-fold>-</button>
    </div><ul>...children...</ul></li></ul>
  </div></div>
</div>
```

The tree inside `data-o-tree` is generated by the script; it is rebuilt with `innerHTML` after every action (move, undo, save, fold, zoom, search). The lines are pure CSS (`li::before` / `li::after` borders in `devlib.css`), so they scale with the zoom. State classes on a card: `is-vacant` (no people), `is-hit` (matches search / department: amber ring), `is-drag` (being dragged), `is-target` (valid drop target: green ring) and `is-deny` (invalid drop target: red ring).

### Toolbar

| Control | What it does |
|---|---|
| Undo | Reverts the last move (one step per click, the whole session of moves since the last Save). Disabled when nothing can be undone. Fires no event. Editable only. |
| Save | Fires `dl:orgchart-save` with the whole structure, shows a toast "Structure saved (N changes)" and clears the undo history. No server call is made. Editable only. |
| Download HTML | Builds a standalone HTML page (current tree, linked stylesheets with absolute URLs, grip and fold buttons hidden) and downloads it as `struktur-organisasi.html`. |
| Zoom -, +, Fit | Scale from 40% to 160% in steps of 10%. Fit sets the scale so the whole tree fits the canvas width (never above 100%). The current value is printed between the buttons. |
| Department select | Highlights the cards of one department (see `departments`). |
| Search | Case-insensitive text search over position title, department and holder names. Matching cards are highlighted, closed branches leading to them are opened, and the first match is scrolled into view. It never hides cards. Typing and the department select combine with AND. |
| Fold button on a card | Shown only on cards that have children. Shows `-` (open) or `+N` (closed, N = direct children). Click toggles the branch. |

On load the canvas is scrolled horizontally to the middle so the top of the tree is visible.

### Moving a card (editable mode)

1. Drag the grip (dotted icon at the left of a card). The whole card becomes semi-transparent.
2. Hover another card: green ring = allowed; red ring and a "not allowed" cursor when the target is the card itself or one of its own subordinates.
3. Drop. The dragged card, with its whole subtree, becomes a child of the target card. The target branch is opened, a toast "X is now under Y" appears, and `dl:orgchart` is dispatched.

Dropping on the card's current superior does nothing. Dropping on a subordinate shows the danger toast "It cannot be moved under its own subordinate." and changes nothing. The order of siblings is not editable (children keep the order of `nodes`; a moved card becomes a child at its place in the original list order). A card cannot be dragged out to become a root: the only way back to a root is Undo.

### Keyboard and accessibility

Moving is mouse (HTML5 drag and drop) only; the grip is a draggable `span` without `tabindex` and there is no keyboard or touch alternative. Everything else uses native controls (toolbar `button`s, `select`, `input type="search"`), and the fold control is a real `button` with a title. The grip has a title ("Drag to a new superior"). Names in avatars have a `title` with the full name. The search and department controls have `aria-label`s.

### Language

Toolbar and tip texts use `Lang::text` (English source text, translated by the dictionary): "Drag the dotted icon on the left of a card onto another superior card to move its subordinates.", "Department", "All departments", "Search employee / position...", "Search employee". Script messages come from the DevLib dictionary keys `oc_undone`, `oc_saved`, `oc_drag`, `oc_vacant`, `oc_expand`, `oc_collapse`, `oc_own_child`, `oc_moved` (Indonesian and English). The button labels "Undo", "Download HTML" and "Fit" are button texts and are always English by project rule. Data (titles, names, departments) is never translated.

### Access

Only the generic `can="module.action"` attribute of every DevLib object: without the ability the whole chart is not rendered. There is no per-card permission; to make the chart read-only for some users, decide `editable` in the view from the same ability (for example `:editable="$user->can('org.edit')"`) and also check on the server when you persist.

## Data and events

### Inputs

Only the props above. The browser works on its own copy of `nodes` (`el.dlOrg.nodes`); a page reload shows the PHP data again, so persist on `dl:orgchart-save`.

### Events

Both events are `CustomEvent`s dispatched on the root `.dl-org` element, bubbling (listen on `document` or on the element).

| Event | When | `detail` |
|---|---|---|
| `dl:orgchart` | After every successful move (not on Undo, not on a refused drop). | `{id, from, to}`: `id` of the moved node, `from` its previous `parent` (as given in `nodes`: may be `null`), `to` the id of the new superior. |
| `dl:orgchart-save` | When Save is clicked (also when nothing changed). | `{nodes: [{id, parent}, ...]}`: every node, in the original order, with its current `parent` (`null` for roots). Only `id` and `parent` are sent. |

```js
document.addEventListener('dl:orgchart-save', e => {
    fetch('/org/structure', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': window.DL_CSRF},
        body: JSON.stringify(e.detail),   // {nodes: [{id, parent}, ...]}
    });
});
```

### JavaScript API

The script class is `OrgChart` (selector `[data-dl-org]`, file `resources/assets/js/components/org-chart.js`). The instance is `el.dlComponent`; the element also gets `el.dlOrg = {nodes, move, render}`:

- `el.dlOrg.nodes`: the live array of nodes (mutated by moves).
- `el.dlOrg.move(id, to)`: the same move as a drop (validation, toast, undo entry and the `dl:orgchart` event). Methods are auto-bound.
- `el.dlOrg.render()`: redraw the tree.

To change the behavior in a project, extend the class (`Devlib.register(class OrgChartX extends Devlib.classes.OrgChart {...})` plus `jsClass()` on a PHP subclass), as described for all objects in `core.js`.

## Rules and pitfalls

- Save does not save anything by itself. Without a listener for `dl:orgchart-save` the user sees "Structure saved" and the change is lost on reload.
- The save payload is the structure only (`id`, `parent`); it carries no title, department or people. The chart cannot add, edit or delete positions or holders; do that in a form or grid and re-render.
- Give every node a unique `id` and make sure each `parent` exists. A broken `parent` silently hides the node and its whole subtree.
- Keep `id` types consistent (all int or all string). Comparisons are done on strings, but see the first note below about the type of `parent` after a move.
- A chart with `editable` true must be backed by a server check: the browser enforces "not under its own subordinate" only on the screen.
- Department matching is exact: `'Finance'` and `'finance'` are different. Put the same spelling in `nodes[].dept` and `departments`.
- Search and the department select only highlight. For a "filter" use `x-dl::grid` over the same data.
- Very large trees are rebuilt with `innerHTML` on each action; there is no virtualization. All branches are open on load; there is no prop to start folded.
- The downloaded HTML is a snapshot of the tree as currently displayed (closed branches are not included, highlight classes are), and it links the same stylesheets by absolute URL, so it only renders correctly where those URLs are reachable.
- Do not put secrets or personal data beyond what the viewer may see in `people`; all of it is in `data-nodes` in the page source.

### Notes on inconsistencies found while writing this entry (not fixed)

- After a move, `parent` of the moved node is set from the card's `data-o-node` attribute, so it is a string (`"4"`) even when the original `id`/`parent` values were integers. `dl:orgchart` `to` and the `parent` in `dl:orgchart-save` therefore mix types (moved nodes: string; untouched nodes: original type). The host should cast (`(int)`) or compare as strings. `from` keeps the original type. (`org-chart.js`, `move()`.)
- `dl:orgchart` is not fired on Undo, and Undo restores the old parent silently, so a host that persists on each `dl:orgchart` will not see the reversal. Persist on `dl:orgchart-save` instead.
- Save clears the undo history, so a saved move cannot be undone from the screen.
- The downloaded file name (`struktur-organisasi.html`) and its `lang="id"` are hard-coded Indonesian, regardless of the page language; the `title` prop is used only inside the file.
- The `title` prop has no on-screen use, although the name suggests a heading.
- Moving by keyboard or touch is not possible (see Behavior); the class comment describes only mouse dragging.

## Example

Runnable with fictional data (adapted from the catalog demo `site/resources/views/demos/org-chart.blade.php`):

```blade
<x-dl::org-chart
    :nodes="[
        ['id' => 1, 'parent' => null, 'title' => 'Managing Director', 'dept' => 'Board', 'people' => ['Alex Morgan']],
        ['id' => 2, 'parent' => 1, 'title' => 'Operations Manager', 'dept' => 'Operations', 'people' => ['Sam Lee']],
        ['id' => 3, 'parent' => 1, 'title' => 'Finance Manager', 'dept' => 'Finance', 'people' => ['Robin Hale']],
        ['id' => 4, 'parent' => 2, 'title' => 'Warehouse Head', 'dept' => 'Warehouse', 'people' => []],
        ['id' => 5, 'parent' => 2, 'title' => 'Shift Leader', 'dept' => 'Operations', 'people' => ['Kim Doe', ['name' => 'Jo Park', 'photo' => '/img/jo-park.jpg']]],
        ['id' => 6, 'parent' => 3, 'title' => 'Accountant', 'dept' => 'Finance', 'people' => ['Lee Chan', 'Max Ray', 'Eli Fox']],
    ]"
    :departments="['Board', 'Finance', 'Operations', 'Warehouse']"
    title="Organization Structure"
    :editable="true" />

<script>
    document.addEventListener('dl:orgchart', e => console.log('moved', e.detail));            // {id, from, to}
    document.addEventListener('dl:orgchart-save', e => console.log('save', e.detail.nodes));   // [{id, parent}, ...]
</script>
```

## Related

- `x-dl::grid`: tabular list, search and filters over the same positions or employees.
- `x-dl::member-list`: people as a list/cards without hierarchy.
- `x-dl::button`, `x-dl::icon-button`: the toolbar controls it renders.
- `x-dl::approval`, `x-dl::flow`: approval chains and step flows (a path, not an organization).
- `x-dl::permission-matrix`: access rights per group, usually maintained on the same Setup screens.
