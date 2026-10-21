## Overview

`<x-dl::menu-button>` is one button that opens a list of choices below it, for example `Print ▾` or `Email ▾` when a form has more than one printable document. Each list item either opens an address (`href`) or announces an action to the screen (`act`) through the event `dl:menu`. The list uses the same look as the `x-dl::doc-share` list (CSS classes `dl-docshare*`).

Use it when the choices are separate actions of equal weight and none is the "default". Use `x-dl::split-button` when one action is the usual one and the arrow only offers alternatives (the main part submits the form). Use `x-dl::button` for a single action and `x-dl::mega-menu` or `x-dl::ribbon` for application-level menus.

Generated markup:

```html
<span class="dl-docshare dl-menubtn" data-dl-menubtn>
  <span class="dl-docshare__menu">
    <button class="dl-btn ..." data-mb-toggle> label / preset text <i class="fa-solid fa-caret-down dl-docshare__caret"></i></button>
    <span class="dl-docshare__list" role="menu">
      <button type="button" role="menuitem" data-mb-item data-mb-act="print-mc" data-href="" [data-blank] [title] [disabled]>
        <i class="fa-solid fa-id-card"></i> Master Card <small>A4 landscape</small>
      </button>
    </span>
  </span>
</span>
```

Extra attributes (`class`, `id`, `data-*`) are merged on the root `span`. The toggle is a real `x-dl::button` (so presets, variant and size behave as on that component).

## Props

Constructor of `Devlib\Components\MenuButton` (6 props, all own). Use `:items="[...]"` for the array.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `items` | `array`, `[]` | The list entries (see below). An empty list gives a button with an empty list. |
| `preset` | `?string`, `null` | A fixed `x-dl::button` preset (`print`, `email`, `save`, ...). When set, the button label, icon and variant come from the preset and `label`, `icon` and `variant` are ignored; only `size` is passed on. |
| `label` | `string`, `''` | Button text when no `preset`. Translated like any text prop. |
| `icon` | `?string`, `null` | Icon of the button when no `preset`: a key from the DevLib icon map or a Font Awesome class starting with `fa-` (handled by `x-dl::button`). |
| `variant` | `string`, `'secondary'` | Button style when no `preset` (`primary`, `secondary`, `success`, `danger`, `ghost`, `link`; see `x-dl::button`). |
| `size` | `string`, `'md'` | Button size passed to `x-dl::button` (`sm`, `md`, `lg`); applies with and without `preset`. |

### Item keys

| Key | Meaning |
|---|---|
| `label` | Text of the item (empty text if missing). Not translated by the component: write the final text. |
| `icon` | Icon key or `fa-*` class, resolved with `Icon::fa()`; default `fa-file-lines`. |
| `sub` | Small second line under the label (`<small>`). |
| `href` | Address. The item navigates there (`location.href`). Takes priority over `act`. |
| `blank` | `true` together with `href`: opens in a new tab (`window.open(href, '_blank', 'noopener')`). |
| `act` | Action name. Without `href`, the click fires `dl:menu` with `detail.act`. |
| `disabled` | `true` renders the item `disabled` (grayed, not clickable). |
| `title` | Tooltip text, for example the reason why the item is disabled. |

## Behavior

- Click the button: toggles `is-open` on the root (the list shows below the button, right-aligned). The click does not bubble out of the toggle.
- Click outside the root: closes the list (every instance adds one listener on `document`).
- Click an item: closes the list first. With a non-empty `href` it navigates (same tab, or new tab with `blank`); otherwise it dispatches `dl:menu`. An item with neither `href` nor `act` still closes the list and dispatches `dl:menu` with `act: ''`.
- A disabled item cannot be clicked (native `disabled`).
- Keyboard: the toggle and the items are native buttons (Tab, Enter, Space work). There is no arrow-key navigation, no Esc handling and no `aria-expanded`/`aria-haspopup` on the toggle; the list has `role="menu"` and items `role="menuitem"` but focus is not moved into the list.
- Text on screen: `label` and preset labels follow the page language; item `label`, `sub` and `title` are written as given.

## Data and events

- The component posts nothing: toggle and items are `type="button"` (the toggle is the button type of `x-dl::button`, so check it is not a submit button inside a form; `x-dl::button` default is `button`).
- Event `dl:menu`: `CustomEvent`, `bubbles: true`, NOT cancelable, dispatched on the root, `detail = { act }`. The screen listens on the root or any ancestor and runs the action:

```js
document.addEventListener('dl:menu', e => { if (e.detail.act === 'print-mc') window.print(); });
```

  `x-dl::mega-menu` dispatches an event with the same name (`detail = { key, label }`); when both are on one page, test `e.detail.act` or `e.detail.key` to tell them apart.
- Hooks: `data-dl-menubtn` (root), `data-mb-toggle`, `data-mb-item`, `data-mb-act`, `data-href`, `data-blank`. JS class `MenuButton` (`resources/assets/js/components/menu-button.js`), `el.dlComponent` on the root.

## Rules and pitfalls

- Do not use it for one action; use `x-dl::button`. Do not use it to submit a form with a chosen action; use `x-dl::split-button`.
- Item texts are not in the dictionary translation of props (`items` is not a translated list): provide English or translated text yourself; wrap data values in `raw()` if they come from the database.
- `preset` wins over `label`, `icon`, `variant`; do not set both.
- `href` wins over `act`. A item with `href="#"` navigates to `#` (it does not fire `dl:menu`).
- `dl:menu` is not cancelable and there is no default preview toast: if nothing listens, the click only closes the list.
- Items are rendered on the server only; to change the list at run time re-render the component.
- Do not nest it in a container with `overflow: hidden`: the list is absolutely positioned and would be clipped.
- Code oddity (not fixed): the file header (`src/Components/MenuButton.php`, REMARK) lists "Dipakai oleh: button" and the list reuses `dl-docshare` CSS; the root class `dl-docshare` also makes the CSS of `x-dl::doc-share` apply to it, so changes to that component can change this one.

## Example

From the catalog demo (`site/resources/views/demos/button.blade.php`):

```blade
<x-dl::menu-button preset="print" :items="[
    ['label' => 'Master Card', 'icon' => 'fa-id-card', 'sub' => 'A4 landscape', 'act' => 'print-mc'],
    ['label' => 'ACC Digital', 'icon' => 'fa-stamp', 'sub' => 'Proof sheet', 'act' => 'print-acc'],
]" />

<x-dl::menu-button preset="email" :items="[
    ['label' => 'Master Card', 'icon' => 'fa-id-card', 'act' => 'email-mc'],
    ['label' => 'ACC Digital', 'icon' => 'fa-stamp', 'disabled' => true, 'title' => 'No document yet'],
]" />

<x-dl::menu-button label="More" icon="fa-ellipsis" :items="[['label' => 'Open DevLib', 'icon' => 'link', 'href' => '#']]" />
```

## Related

- `x-dl::button`: the toggle; presets and variants are defined there.
- `x-dl::split-button`: main action plus arrow, posts the chosen action as `_action`.
- `x-dl::doc-share`: the document-sharing list whose CSS this button reuses.
- `x-dl::action-bar`: toolbar that often holds these buttons.
- `x-dl::mega-menu`, `x-dl::ribbon`: application menus that fire similar events.
