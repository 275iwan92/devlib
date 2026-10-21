## Overview

`<x-dl::flow>` draws a process as a row (or column) of numbered blocks joined by arrows, in the style of an approval pipeline. Each block has an icon, a label, a small caption line (the module or the actor), a status badge (Done, Review, To Do, Locked) and, optionally, a short list of branches under it. A block either opens a screen (`url`) or tells the page it was clicked (`dl:flow` with its `key`). A legend of the four statuses is printed under the diagram.

Use it to explain a flow between modules or documents, to show where a value comes from and where it is stored, or to show the life cycle of a document and who acts on it. The catalog has three examples (part "Flow Diagrams"): System Flow (how modules hand a document to each other), Data Flow (input, validation, store, engine, report) and Work Flow (document life cycle with the cancel branch).

Pick another object when:

- the cards are work items that people move between stages: `x-dl::kanban`;
- the steps are the pages of one task the user fills in: `x-dl::wizard`;
- the diagram needs free positioning, loops or many-to-many links: this object is a single ordered line with one level of branches, not a graph (draw an SVG as the project help rules describe in `skills/devlib/help.md`);
- you only need a status label: `x-dl::badge`.

Not to be confused with `App\Support\FlowDemo` and `FlowPrintDoc` in the catalog site: those belong to the Grid -> Form -> back screen flow (`p5-flow-*` pages, rule file `skills/devlib/grid-form-flow.md`) and have nothing to do with this object. `x-dl::flow` has no server contract.

Generated markup:

```html
<div class="dl-flow [is-vertical]" data-dl="x-dl::flow" data-dl-class="Flow ← DevlibComponent" data-dl-src="—" data-dl-flow>
  <div class="dl-flow__title">Title</div>                                        <!-- only when title is not '' -->
  <ol class="dl-flow__track">
    <li class="dl-flow__item">
      <a class="dl-flow__step is-done" href="/url"  title="note">                 <!-- with url -->
      <a class="dl-flow__step is-review" href="#" data-flow-key="eng" title="note">  <!-- without url -->
        <span class="dl-flow__no">1</span>
        <span class="dl-flow__ico"><i class="fa-solid fa-file-signature"></i></span>
        <span class="dl-flow__txt"><b>Label</b><small>module</small></span>
        <span class="dl-badge ...">Done</span>
      </a>
      <ul class="dl-flow__branch"><li><a class="dl-flow__sub is-review" href="#"><i class="fa-solid ..."></i> Branch label</a></li></ul>   <!-- only with branch -->
    </li>
    ...
  </ol>
  <div class="dl-flow__legend"> Done | Review | To Do | Locked badges </div>
</div>
```

Extra HTML attributes (`id`, `class`, `style`, `data-*`) go on the root `div`. `can="module.action"` is read by the base class: without the ability nothing is rendered.

## Props

Constructor of `Devlib\Components\Flow` (4 props). Use `:prop="expr"` for arrays and booleans (`:steps="[...]"`, `vertical`).

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `steps` | `array` | `[]` | The blocks, in order. Pass a list (numeric keys from 0): the block number is the array index plus 1, and a non-numeric key raises an error. Item shape in "Data and events". Empty = only the title and the legend. |
| `title` | `string` | `''` | Heading above the diagram (`dl-flow__title`). An empty string draws no heading. Translated through the dictionary (wrap a data value in `raw()`). Must be a string, never `null`. When the page has a guide URL configured, the heading also gets the info link to this object's Technical Guide entry. |
| `vertical` | `bool` | `false` | Stacks the blocks in one column (arrows point down, each block up to 420 px wide) instead of the wrapping row. |
| `source` | `?string` | `null` | Free text naming where the diagram content comes from (the spec, the rule file), shown by the developer catalog (`data-dl-src`); default `—`. No visual or runtime effect. |

## Behavior

### Layout

- Horizontal (default): the track is a wrapping flex row; each block is 190 to 240 px wide and grows to share the line. A chevron connector is drawn after every block except the last. When the row wraps, the connector at the end of a line still points right; the next block starts on the next line (a limit of the CSS-only connectors, so keep a diagram to about five or six blocks per line or use `vertical`).
- Vertical: one column, connectors point down.
- The block is a grid: number, icon with label and caption, then the status badge. A hover lifts the block slightly. The left border color follows the status: `done` green, `review` accent, `locked` amber, `todo` grey.
- Branches (`branch`) are a dashed-line list under their block: one level only, no nesting.
- The legend under the diagram always lists the four statuses; it cannot be hidden.

### Statuses

`Flow::STATUS` maps the status key to the badge label and tone:

| `status` | Badge label | Tone | Typical meaning |
|---|---|---|---|
| `done` | Done | success | step finished / data stored |
| `review` | Review | info | in progress or waiting for a decision |
| `todo` | To Do | neutral | not started (also the default) |
| `locked` | Locked | warn | blocked until something else happens |

A missing status is `todo`. An unknown value shows the `To Do` badge, but the root class is written as `is-<value>` and has no style, so the left border stays grey.

### Clicking

- A block with `url` is a normal link (`href`), so it opens its screen like any link (the application shell may open it as a tab). No event is fired.
- A block without `url` has `href="#"` and `data-flow-key="<key>"` (the `key` of the step, or its index when `key` is missing). The click is cancelled (`preventDefault`) and the root fires the event `dl:flow` with `detail: {key}`. Listen to it to open a panel, scroll to a section or load details.
- A branch is a plain link: with `url` it navigates, without `url` it is `href="#"` with no handler and no event (the page jumps to the top). Give branches a `url` or do not use them as buttons.
- `note` becomes the `title` tooltip of the block (mouse hover only; not shown on touch or to keyboard users; branches have no tooltip).

### Language and translation

- Translated: `title`. The interface text is nothing else.
- Not translated (data): `label`, `module`, `note`, branch labels. Write them in the language of the audience.
- The legend and status badges are fixed English (`Done`, `Review`, `To Do`, `Locked`); the badge object does not translate status terms.

### Access and accessibility

- `can="module.action"` hides the whole diagram.
- The structure is an ordered list of links, so screen readers hear the number of items; each link's text contains the number, label, caption and status badge text, so the status is not conveyed by color alone. The connectors are decorative (CSS pseudo-elements). Blocks are focusable links (default browser focus ring).

## Data and events

### `steps` item

`['key' => 'rsv', 'label' => 'Stock Reservation', 'module' => 'Inventory', 'icon' => 'fa-boxes-stacked', 'status' => 'done', 'note' => 'Reserves stock per item and batch', 'url' => '/reservations', 'branch' => [...]]`

| Key | Meaning |
|---|---|
| `label` | Required. Bold text of the block (escaped). |
| `key` | Identifier sent in `dl:flow`. Default: the array index of the step. Only used when there is no `url`. |
| `module` | Small caption under the label (module, actor, rule). Omitted when empty. |
| `icon` | Font Awesome class. The view always writes `fa-solid` in front, so only solid icons work. Short aliases from `Devlib\Icon` (such as `file`, `money`, `settings`) are mapped; default `file` (a file icon). |
| `status` | `done`, `review`, `todo` (default) or `locked`. |
| `note` | Tooltip text. |
| `url` | Address of the screen the block opens. When set, the block is a link and no event is fired. |
| `branch` | List of branch items `['label' => ..., 'icon' => ..., 'url' => ..., 'status' => ...]`; only `label` is required, `icon` defaults to `file`, `url` to `#`. `status` is written as the class `is-<status>` on the branch link but the CSS has no style for it, so it has no visual effect. |

### Event `dl:flow`

Dispatched on the root `.dl-flow` element, bubbles, not cancelable, `detail: {key}` where `key` is the `data-flow-key` of the clicked block (a string).

```js
document.querySelector('[data-dl-flow]').addEventListener('dl:flow', e => {
  console.log('step clicked', e.detail.key);
});
```

### JS

`el.dlComponent` is the `Flow` instance. The only method is `open(key)`, which fires the event; override it in a subclass (`class MyFlow extends Devlib.classes.Flow { open(key) { ... } }` plus `data-dl-js` or `Devlib.define('Flow', MyFlow)`) to open a panel directly. There is no `el.dlFlow`.

### Data source

All content comes from the screen. The component reads nothing, stores nothing and posts nothing.

## Rules and pitfalls

- A flow explains; it does not run the process. Keep the statuses as true as the data behind them; for the live state of one document use the `x-dl::form` status or a `x-dl::badge`.
- Use `url` for blocks that open a screen and `key` for blocks that the page handles itself. Do not set both expecting the event: with a `url` there is no event.
- Keep a block short: one label, one caption line. Put the explanation in `note` and in the text around the diagram.
- Pass `steps` as a list and always give `label`; both are read without a default.
- Pass `title` as a string (`title=""` or `:title="''"` to hide it). `null` is a type error.
- The status colors and badge labels are English by design; do not try to translate them per diagram.
- Do not depend on the tooltip (`note`) for essential information: it is invisible on touch screens.
- For more than one level of branching, or loops, draw an SVG (project help rule); do not nest by abusing `branch`.
- Lint: no flow-specific rule. The general rules L001 (raw HTML tags), L002 (inline `<script>`) and L003 (hex colors) apply to the Blade screen that holds the diagram.

### Notes on inconsistencies found while writing this entry (not fixed)

- The header REMARK of `src/Components/Flow.php` names only `side-screen` under "Dipakai oleh"; the catalog also uses the component in `data-flow`, `system-flow` and `work-flow` (`site/resources/views/demos`), which are listed on the pages `p10-data-flow`, `p10-system-flow` and `p10-work-flow` (not yet APPROVED).
- A branch item that has a `status` gets the class `dl-flow__sub is-<status>`, but `devlib.css` defines no `dl-flow__sub.is-*` rule, so the status of a branch is invisible. The demos `work-flow` (`locked`) and `data-flow` (`done`, `review`) set it as if it mattered.
- A branch without `url` is `href="#"` with no `data-flow-key` and no handler (`src/Components/Flow.php` view, branch loop), so clicking it only jumps to the top of the page, unlike a block without `url`, which fires `dl:flow`.
- The connector after the last block of a wrapped line still points right (`.dl-flow__item:not(:last-child)::after`), although the next block is on the next line.
- `$title !== ''` with `string $title`: a `null` title (for example from an optional variable) raises a `TypeError` instead of hiding the heading.
- A step array keyed by strings breaks `$i + 1` for the block number; the header comment does not say that `steps` must be a list.

## Example

Catalog demo `system-flow` (fictional data):

```blade
<x-dl::flow title="Customer Order to cash" source="fictional sample" :steps="[
    ['key' => 'co', 'label' => 'Customer Order', 'module' => 'Sales', 'icon' => 'fa-file-signature', 'status' => 'done', 'note' => 'Confirmed order starts the flow'],
    ['key' => 'rsv', 'label' => 'Stock Reservation', 'module' => 'Inventory', 'icon' => 'fa-boxes-stacked', 'status' => 'done', 'note' => 'Reserves stock per item and batch',
     'branch' => [['label' => 'Short stock: Purchase Request', 'icon' => 'fa-cart-shopping', 'status' => 'review']]],
    ['key' => 'do', 'label' => 'Delivery Order', 'module' => 'Logistics', 'icon' => 'fa-truck-fast', 'status' => 'review', 'note' => 'Prepared from the reservation'],
    ['key' => 'inv', 'label' => 'Customer Invoice', 'module' => 'Finance', 'icon' => 'fa-file-invoice-dollar', 'status' => 'todo', 'note' => 'Created when the delivery is closed'],
    ['key' => 'pay', 'label' => 'Payment', 'module' => 'Finance', 'icon' => 'fa-money-bill-wave', 'status' => 'locked', 'note' => 'Locked until the invoice is posted'],
]" />
```

Blocks that open screens, and a branch with an address (vertical layout):

```blade
<x-dl::flow vertical title="Purchasing" :steps="[
    ['label' => 'Purchase Request', 'module' => 'Purchasing', 'icon' => 'fa-cart-shopping', 'status' => 'done', 'url' => '/purchase-requests'],
    ['label' => 'Purchase Order', 'module' => 'Purchasing', 'icon' => 'fa-file-contract', 'status' => 'review', 'url' => '/purchase-orders',
     'branch' => [['label' => 'Price above limit: Director approval', 'icon' => 'fa-stamp', 'url' => '/approvals']]],
    ['label' => 'Goods Receipt', 'module' => 'Warehouse', 'icon' => 'fa-dolly', 'status' => 'todo', 'url' => '/goods-receipts'],
]" />
```

Listening to blocks without `url` (the page decides what to show):

```blade
<x-dl::flow id="process-flow" title="Production" :steps="[
    ['key' => 'plan', 'label' => 'Planning', 'icon' => 'fa-calendar-days', 'status' => 'done'],
    ['key' => 'run', 'label' => 'Running', 'icon' => 'fa-gears', 'status' => 'review'],
]" />
<script>
    document.getElementById('process-flow').addEventListener('dl:flow', e => document.getElementById('detail').textContent = e.detail.key);
</script>
```

(The inline `<script>` above only shows the event; in a project screen move such behavior into a DevLib JS component, because lint rule L002 flags inline scripts.)

## Related

- `x-dl::badge`: the status badge used in each block and in the legend.
- `x-dl::card`: the usual container when a diagram sits next to text (as in the Side Screen demo).
- `x-dl::side-screen`: the floating window whose Help & support layout shows an "Application's Flow" section made of this object.
- `x-dl::kanban`: work items moved by people between stages.
- `x-dl::wizard`: a guided multi-page task, with real steps the user fills in.
- `x-dl::form`: shows the status of one live document with its Save / Confirm cycle.
- `skills/devlib/help.md`: the project rule for help documents and flow drawings (SVG).
