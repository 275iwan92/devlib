## Overview

`<x-dl::side-menu>` is a standalone side panel menu, on the left or on the right of the screen. In wide mode it is a nested accordion (group, sub-group, item) with a search box, an active-item highlight and number badges. In collapsed ("mini") mode it becomes a 60 px icon rail and a flyout shows the sub-menu of the icon under the mouse. Stars on items build a Favourites group; every opened item goes to a Recently opened group. The mode and both lists are remembered per browser.

Use it for a secondary or shortcut panel inside a screen (the catalog demo shows a left panel and a right "shortcuts" panel). It is NOT the main application menu: the main menu of an application is `x-dl::layer-menu`, normally through `x-dl::layer-shell`, which also adds MDI tabs, the Ctrl+K palette, drawers and the profile panel. `x-dl::side-menu` knows nothing about tabs, users or logout.

Root markup (the component is an `<aside>`):

```html
<aside class="dl-sm dl-sm--left [is-mini]" data-dl="x-dl::side-menu side=left" data-dl-side-menu data-id="side">
  <div class="dl-sm__head"><b class="dl-sm__title">Menu</b><button class="dl-sm__mini" data-sm-mini>...</button></div>
  <div class="dl-sm__search"><input type="search" data-sm-q></div>            <!-- only with search -->
  <div class="dl-sm__scroll">
    <div class="dl-sm__extra" data-sm-fav hidden>...<ul data-sm-favlist></ul></div>   <!-- only with favorites -->
    <div class="dl-sm__extra" data-sm-recent hidden>...<ul data-sm-recentlist></ul></div>
    <nav data-sm-tree><ul class="dl-sm__ul" data-depth="0">...</ul></nav>
  </div>
  <div class="dl-sm__fly" data-sm-fly hidden></div>                            <!-- flyout of the mini rail -->
</aside>
```

Extra HTML attributes (`class`, `style`, `data-*`) and `can="module.action"` go on this root `aside`. Without the ability the panel is not rendered.

## Props

Constructor of `Devlib\Components\SideMenu`: 8 props. Use `:prop="expr"` for anything that is not a plain string (`:items`, `:search="false"`, `:collapsed="true"`).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `items` | `array`, `[]` | The tree (see "Item shape" below). Any depth. An empty array renders an empty panel. |
| `side` | `string`, `'left'` | `left` or `right`. Adds `dl-sm--left` / `dl-sm--right`; on the right the header is mirrored, the collapse arrows point right and the flyout opens to the left. Any other string only produces a class without style, and the arrows then point left. |
| `title` | `string`, `'Menu'` | Text of the header (`dl-sm__title`). Translated through the dictionary (it is a standard text prop). Wrap data in `raw()`. |
| `id` | `string`, `'side'` | Written to `data-id`. It is the key of the browser storage (`dl-sm-<id>-fav`, `-recent`, `-mini`). Give every side menu on one site its own `id`, otherwise two panels share favourites and the mini state. |
| `collapsed` | `bool`, `false` | Adds `is-mini` on the first render (icon rail). A value saved by the user in the browser (`-mini`) wins over this prop as soon as the user has clicked the collapse button once. |
| `search` | `bool`, `true` | Shows the search input (`data-sm-q`). Without it the search script is simply inactive. |
| `favorites` | `bool`, `true` | Shows a star button after every leaf and renders the two extra groups Favourites and Recently opened. With `false` there are no stars and no extra groups, but the clicked keys are still stored in `-recent`. |
| `active` | `?string`, `null` | The `key` (or the `label` when the item has no key) of the leaf that is highlighted and whose parents are opened on load. `null` = none. |

### Item shape

```php
['label' => 'Sales', 'icon' => 'fa-cart-shopping', 'items' => [          // branch: has a non-empty items
    ['key' => 'co', 'label' => 'Customer Order', 'url' => '/co', 'badge' => '4', 'tone' => 'warn'],   // leaf
]],
['caption' => 'Reports'],                                                   // caption row, not clickable
```

| Key | Used for |
|---|---|
| `label` | Text of the row, also the title tooltip. Required for branches and leaves. Not translated (data). |
| `key` | Identity of a leaf: `data-sm-key`, the match with `active`, the storage key of favourites and recent, and the `key` in the event. Default = `label`. A branch never uses its key. |
| `icon` | Icon meaning (`settings`, `notification`, see `Devlib\Icon::MAP`) or a Font Awesome class name without the style (`fa-cart-shopping`); the view always writes `fa-solid`. At depth 0 an item without `icon` gets `fa-circle`; at depth 1 and deeper an item without `icon` shows a small dot instead of an icon. |
| `url` | `href` of a leaf. Default `#`. |
| `badge`, `tone` | A small pill after the label. `tone` becomes `is-<tone>`; default `accent`. Typical values used by the demo: `warn`, `danger`. Badge on a branch is allowed. |
| `items` | Makes the entry a branch (only when non-empty; an empty `items` array makes it a leaf). |
| `caption` | A row with only this key is a section caption (`dl-sm__cap`). It is hidden while searching. |

## Behavior

### Wide mode

- A branch is a button (`data-sm-toggle`, `aria-expanded`); click opens or closes it (class `is-open`). Several branches can be open at the same time.
- Opening the page with `active` set (or after clicking a leaf) opens all parent branches of the active leaf.
- A click on a leaf marks it `is-active`, opens its parents, moves its key to the front of Recently opened (maximum 5, newest first), and fires the event `dl:side-menu`. Leaves with `url="#"` show a preview toast (`Open: <label> (preview)`) unless a listener cancels the event; real `href` links navigate by themselves.
- Star button (`data-sm-star`): toggles Favourites, shows a toast ("<label> added to Favourites" / "removed from Favourites") and redraws both extra groups. The Favourites group is placed above Recently opened; each group is hidden while empty or when none of its keys exists in this menu.

### Search

Typing in `data-sm-q` (case-insensitive, `includes`) hides captions and every leaf whose label does not contain the text, marks the hits (`is-hit`) and opens their parents. When a branch label matches, all rows under it are shown. Branches without a visible leaf are hidden. Clearing the text restores everything (the open/closed state of branches stays as it became). Only labels are searched, not keys or badges.

### Mini rail (collapse button)

- The header button toggles `is-mini` on the root, hides the flyout and saves `-mini` (`true` / `false`) in `localStorage`.
- In mini mode only icons are visible. Hovering a depth-0 branch (or clicking it) shows the flyout `data-sm-fly` next to the rail: a copy of its sub-menu with a title row; stars are removed from the copy. Leaving the panel hides the flyout after 200 ms; clicking a leaf in the flyout also hides it. Clicking the same branch icon again closes it.
- A branch that contains the active leaf is highlighted on the rail.

### Language

Translated: `title`, the search placeholder and label, the collapse button texts, the group names Favourites and Recently opened, the star label, and the toasts. Not translated: item `label`, `caption` and `badge` (they are data; translate them before passing the array).

### Accessibility as implemented

Branch buttons carry `aria-expanded`; the collapse button has `aria-label`; the search input has `aria-label`; the star button has an `aria-label`. There is no `role="tree"` / `role="menu"`, no arrow-key navigation and no `aria-current` on the active leaf. The mini flyout opens on mouse hover or click; it has no keyboard-only path other than clicking a focused icon button.

## Data and events

### Event `dl:side-menu`

Dispatched on the root `aside` when a leaf (including a row of the Favourites / Recently groups) is clicked. It bubbles and is cancelable.

```js
document.addEventListener('dl:side-menu', e => {
  const {key, label} = e.detail;           // key = data-sm-key
  e.preventDefault();                      // stops only the preview toast (see the oddity below)
});
```

### Browser storage

| Key | Content |
|---|---|
| `dl-sm-<id>-fav` | array of favourite keys |
| `dl-sm-<id>-recent` | array of up to 5 recent keys |
| `dl-sm-<id>-mini` | `true` / `false`; absent until the user clicks the collapse button |

All storage access is wrapped in try/catch; the menu works without it. `Devlib.resetWorkspace()` (called by the Sign out action of `x-dl::layer-shell` and by a fresh login) removes every `dl-sm-*` key except the `-mini` ones, so favourites and recent items are cleared at that moment.

### Markup hooks and JS

| Hook | Where |
|---|---|
| `data-dl-side-menu`, `data-id` | root. |
| `data-sm-tree`, `data-sm-node`, `data-sm-toggle` | tree container, branch `li`, branch button. |
| `data-sm-leaf="<key>"`, `data-sm-key="<key>"` | leaf `li`, leaf link. |
| `data-sm-star="<key>"` | star button. |
| `data-sm-q`, `data-sm-mini`, `data-sm-fly` | search input, collapse button, flyout. |
| `data-sm-fav`, `data-sm-favlist`, `data-sm-recent`, `data-sm-recentlist` | extra groups. |

`el.dlComponent` on the `aside` is the `SideMenu` instance (properties `fav`, `recent`, `tree`, `fly`; methods `mini()`, `leaf(key)`, `labelOf(key)`, `openPath(li)`, `extras()`, `showFly(node)`, `hideFly()`). The tag names the side in `data-dl` (`x-dl::side-menu side=left`).

## Rules and pitfalls

- This is not the application menu. For a menu that opens screens as MDI tabs use `x-dl::layer-shell`; for a layered left panel use `x-dl::layer-menu`.
- Give each panel a unique `id`; the id is the storage namespace.
- A leaf is recognised by being an entry without a non-empty `items`; a branch needs at least one child to behave as a branch.
- Use stable `key` values. Favourites and Recently opened are stored by key; renaming a key silently drops the old favourite (keys that no longer exist are ignored and the group hides itself when none is left).
- With `favorites` false the stars are gone; users cannot create favourites. The `-recent` list is still written.
- `collapsed` is only the first-visit default. After the user toggles the rail, the saved value wins.
- Labels are not translated and are HTML-escaped. Do not put markup in `label`.
- Do not use the flyout as a navigation of its own: it is a copy of the sub-tree; a `dl:side-menu` listener must read the `key`, not rely on DOM position.
- Code oddity (not fixed): the event `dl:side-menu` is `cancelable` (`resources/assets/js/components/side-menu.js:42`) but the script never calls `preventDefault()` on the click when the event is cancelled, so cancelling does not stop a real `href` navigation; it only suppresses the preview toast for `#` links. (`dl:layer-menu` does stop the click.)
- Code oddity (not fixed): `SideMenu::tag()` returns `x-dl::side-menu side=left` including the prop (`src/Components/SideMenu.php:37`), so `data-dl` on the root carries that text rather than the plain tag.

## Example

From the catalog demo (`site/resources/views/demos/side-menu.blade.php`), fictional data:

```blade
@php
    $tree = [
        ['caption' => 'Transactions'],
        ['label' => 'Sales', 'icon' => 'fa-cart-shopping', 'items' => [
            ['key' => 'quo', 'label' => 'Quotation'],
            ['key' => 'co', 'label' => 'Customer Order', 'badge' => '4'],
            ['label' => 'Delivery', 'items' => [
                ['key' => 'do', 'label' => 'Delivery Order'],
                ['key' => 'dn', 'label' => 'Delivery Notes'],
            ]],
        ]],
        ['label' => 'Procurement', 'icon' => 'fa-truck', 'items' => [
            ['key' => 'pr', 'label' => 'Purchase Requisition', 'badge' => '2', 'tone' => 'warn'],
            ['key' => 'po', 'label' => 'Purchase Order'],
        ]],
        ['caption' => 'Reports'],
        ['key' => 'settings', 'label' => 'Settings', 'icon' => 'settings'],
    ];
    $shortcuts = [
        ['caption' => 'Shortcuts'],
        ['key' => 'r-approve', 'label' => 'Waiting for Approval', 'icon' => 'approve', 'badge' => '5', 'tone' => 'danger'],
        ['key' => 'r-notif', 'label' => 'Notifications', 'icon' => 'notification', 'badge' => '3'],
    ];
@endphp
<x-dl::side-menu id="demo-left" title="SAMPLE CO" :items="$tree" active="co" />
<x-dl::side-menu id="demo-right" side="right" title="Right Panel" :items="$shortcuts" :search="false" />
```

## Related

- `x-dl::layer-menu`: the layered main menu panel (module, menu, sub, sub-sub in one panel) that replaces an accordion for applications.
- `x-dl::layer-shell`: the application frame that owns a `x-dl::layer-menu`, tabs, drawers and the profile panel.
- `x-dl::breadcrumb`, `x-dl::tabs`, `x-dl::accordion`: other navigation and disclosure objects.
- `skills/devlib/components.md`: generated prop list; `skills/devlib/object.md`: rules for building objects.
