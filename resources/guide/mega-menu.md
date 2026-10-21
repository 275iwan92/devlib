## Overview

`<x-dl::mega-menu>` is a colored top bar in the style of ScriptCase. Each bar item is one of three kinds: a plain link, a normal dropdown (which may nest sub-menus), or a MEGA panel (a wide panel with several columns; each column has a heading with icon and a list of items). Hover or click opens a panel, `Esc` closes it, the left/right arrows move between bar items, and on a narrow bar (under 760 px) the items collapse behind a hamburger button and open as an accordion.

Use it for the top navigation of a self-contained screen or a demo that needs a wide, categorized menu. It is NOT the application menu: the only application menu of DevLib is the Layer Menu (`x-dl::layer-shell`, `x-dl::layer-menu`) in the left panel. For commands of a document (Home / Insert / Data tabs) use `x-dl::ribbon`; for a button with a short list use `x-dl::menu-button`.

Generated markup:

```html
<nav class="dl-mm dl-mm--green" data-dl-mega aria-label="Main menu">
  <span class="dl-mm__brand">BRAND</span>
  <button class="dl-mm__burger" data-mm-burger aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
  <ul class="dl-mm__bar" data-mm-bar>
    <li class="dl-mm__top is-link|is-drop|is-mega [is-current]">
      <a class="dl-mm__tl" href="..." | href="#" data-mm-key="key"> icon + label + <em class="dl-mm__badge"> </a>        <!-- link -->
      <button class="dl-mm__tl" data-mm-open aria-expanded="false"> ... <i class="dl-mm__caret"></i></button>            <!-- drop / mega -->
      <div class="dl-mm__panel [is-mega]" style="--cols: N"> ... </div>
    </li>
  </ul>
  <span class="dl-mm__end"> slot </span>
</nav>
```

Extra attributes (`class`, `id`, `data-*`) are merged on the `nav`.

## Props

Constructor of `Devlib\Components\MegaMenu` (4 props, all own).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `items` | `array`, `[]` | The bar items (see item structure below). |
| `brand` | `string`, `''` | Bold text at the left of the bar (`dl-mm__brand`). Empty = not rendered. Not translated. |
| `tone` | `string`, `'green'` | Bar color: adds `dl-mm--<tone>`. The CSS defines `navy`, `accent` and `violet`; `green` (or any other value) uses the base color `--dl-green`. |
| `active` | `?string`, `null` | The `key` (or, if no key, the `label`) of the top-level item to mark as current (`is-current`). Only top-level items are compared. `null` = none. |

The default slot is printed at the right end of the bar (`dl-mm__end`): user name, icons, language switch.

### Item structure

Top-level item (`items`):

| Key | Meaning |
|---|---|
| `label` | Text (required). Not translated by the component. |
| `icon` | Icon key of the DevLib icon map (`home`, `users`, `settings`, ...) or a Font Awesome class (`fa-gauge-high`), resolved with `Icon::fa()`. Optional. |
| `key` | Identifier sent in the event; default is `label`. Also used by `active`. |
| `url` | Address. The item is a real link (`href`) and fires NO event. Without `url` the item is `href="#"` with `data-mm-key`. |
| `badge` | Small counter text (`<em class="dl-mm__badge">`). Shown only on leaf entries (links, and items inside lists), not on a top item that has a dropdown or columns. |
| `items` | List of ITEMs: makes the entry a normal dropdown. |
| `columns` | List of `['label', 'icon', 'items' => [ITEM]]`: makes the entry a mega panel with that many columns (`--cols`). Takes priority over `items`. |

ITEM (inside `items` of a dropdown or a column): `label`, `icon`, `key`, `url`, `badge` as above, and `items` (list of ITEMs) for a nested sub-menu, shown to the right on hover or click (`has-sub`, a chevron is added). Nesting depth is not limited by the code.

## Behavior

- Kind of a top item: `columns` present = `mega`, else `items` present = `drop`, else `link`.
- Hover (desktop, only on devices with hover): entering a top item with a panel opens it and closes the others; leaving closes it after 180 ms. Click on the top button toggles it (this is what touch devices use).
- Click on a leaf item with `data-mm-key` (no `url`): `preventDefault`, marks the top item containing it as `is-current`, closes all panels and the mobile menu, then dispatches `dl:menu`. If nobody cancels the event, a preview toast `Open: <label> (preview)` is shown.
- Click on a sub-menu entry (`data-mm-sub`): toggles `is-open` on its `li` (works for touch and for the accordion).
- Outside click closes panels and the mobile menu (one `document` listener per instance).
- Keyboard: `Esc` (while focus is inside the bar) closes panels. `Left`/`Right` on a bar item (`.dl-mm__tl`) moves focus to the previous/next one (wraps around). `Down` on an item that has a panel opens it and focuses its first link or button. Enter/Space on buttons and links are native. Arrow keys inside the panel are not handled; use Tab.
- Narrow mode: a `ResizeObserver` toggles `is-narrow` on the `nav` when its own width is under 760 px (the width of the bar, not of the window). In narrow mode the bar is hidden, the hamburger shows, and `is-mobile` (toggled by the hamburger) shows the accordion; hover does not open panels.
- Text on screen: the `aria-label` ("Main menu") and the hamburger label ("Open menu") follow the page language. `brand`, item labels and badges are not translated; write final text.

## Data and events

- Posts nothing; items are links or `href="#"` anchors.
- Event `dl:menu`: `CustomEvent`, `bubbles: true`, CANCELABLE, dispatched on the `nav`, `detail = { key, label }` (`label` is the trimmed text of the clicked item, including the badge text if any). Only items without `url` fire it.

```js
document.addEventListener('dl:menu', e => {
  e.preventDefault();               // stops the preview toast
  if (e.detail.key === 'cust') location.href = '/customers';
});
```

  `x-dl::menu-button` dispatches an event with the same name (`detail = { act }`); check the detail you expect.
- Hooks: `data-dl-mega`, `data-mm-bar`, `data-mm-burger`, `data-mm-open`, `data-mm-sub`, `data-mm-key`. JS class `MegaMenu` (`resources/assets/js/components/mega-menu.js`), `el.dlComponent` on the `nav`; methods `open(li)`, `close(except)`, `tops()`, `narrow()`.

## Rules and pitfalls

- Not the application menu. Real navigation of an application goes in the Layer Menu; this bar is for a screen's own top navigation or demos.
- Give a `url` to real navigation items; items with only a `key` fire an event and, if no handler cancels it, show the preview toast instead of navigating. In production always call `preventDefault()` in your `dl:menu` handler and navigate yourself.
- `active` only matches the `key`/`label` of TOP-LEVEL items. After a click, the script moves `is-current` to the top item that contains the clicked leaf, but the server is not told; set `active` again when re-rendering.
- Keep keys unique; they are written into `data-mm-key` and sent in the event.
- Labels, brand and badges are not translated; wrap database values in `raw()` if they pass through a translated prop.
- A `columns` entry ignores `items` of the same top item. A column needs `items` (a column without `items` raises a PHP error).
- Width: `is-narrow` follows the width of the bar itself; inside a narrow container the hamburger shows even on a wide screen.
- The panels are absolutely positioned: do not put the bar in a container with `overflow: hidden`.
- Code oddity (not fixed): `resources/assets/js/components/mega-menu.js:49` (`open()`) sets `aria-expanded="true"`, but the hover-out handler (line 14) removes only the `is-open` class after 180 ms, so a panel closed by leaving with the mouse keeps `aria-expanded="true"` until another item is opened or `close()` runs.

## Example

Shortened from `site/resources/views/demos/mega-menu.blade.php` (fictional data):

```blade
<x-dl::mega-menu brand="CONTOH MAKMUR" active="home"
    :items="[
        ['key' => 'home', 'label' => 'Home', 'icon' => 'home'],
        ['label' => 'Mega Menu', 'icon' => 'fa-table-cells-large', 'columns' => [
            ['label' => 'Sales', 'icon' => 'fa-cart-shopping', 'items' => [
                ['key' => 'cust', 'label' => 'Customers', 'icon' => 'users'],
                ['key' => 'co', 'label' => 'Customer Orders', 'icon' => 'fa-bag-shopping', 'badge' => '4'],
            ]],
            ['label' => 'Finance', 'icon' => 'money', 'items' => [
                ['label' => 'Journals', 'icon' => 'fa-book', 'items' => [['key' => 'bkm', 'label' => 'Kas Masuk'], ['key' => 'bkk', 'label' => 'Kas Keluar']]],
            ]],
        ]],
        ['label' => 'Reports', 'icon' => 'fa-chart-pie', 'items' => [
            ['key' => 'r-sales', 'label' => 'Sales Report', 'icon' => 'fa-chart-column'],
            ['url' => '/reports/stock', 'label' => 'Stock Card', 'icon' => 'fa-clipboard-list'],
        ]],
        ['key' => 'help', 'label' => 'Help', 'icon' => 'help'],
    ]">
    <i class="fa-solid fa-circle-user"></i> Admin
</x-dl::mega-menu>
```

## Related

- `x-dl::layer-shell`, `x-dl::layer-menu`: the application menu (left panel); use these for real navigation.
- `x-dl::ribbon`: tabbed command bar for document screens.
- `x-dl::menu-button`: one button with a short list.
- `x-dl::side-menu`, `x-dl::breadcrumb`, `x-dl::tabs`: other navigation objects in the same dictionary group.
