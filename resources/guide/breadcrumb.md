## Overview

`<x-dl::breadcrumb>` is the location trail at the top of a screen: `Module > Group > Screen [> current]`, with chevron separators. Items can be plain text, normal links, or (with `menu`) clickable names that open the **Layer Menu** at that branch.

Use it at the top of a screen that sits in the menu tree. Do not use it for in-page steps (`x-dl::wizard`), for tabs (`x-dl::tabs`) or as a "go back" control (`x-dl::back-button`).

### Generated markup

```html
<nav class="dl-crumb" aria-label="breadcrumb" data-dl="x-dl::breadcrumb" data-dl-class="Breadcrumb" data-dl-src="—">
  <a href="/url">Module</a>
  <i class="fa-solid fa-chevron-right"></i>
  <a href="#" data-dl-crumb-menu='["Module","Group"]' data-leaf="Screen" [data-dl-crumb-url="/url"] title="Open menu Group">Group</a>
  <i class="fa-solid fa-chevron-right"></i>
  <a href="/screen" class="is-current" aria-current="page" title="Reload Screen">Screen</a>
  <i class="fa-solid fa-chevron-right"></i><span>Form PO-0001</span>   <!-- only with current -->
</nav>
```

Extra HTML attributes are merged onto the `nav` (`class` is appended). `can="module.action"` hides it when the ability is missing.

## Props

Constructor of `Devlib\Components\Breadcrumb` (3 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `items` | `array`, `[]` | The trail, in order. Two accepted shapes. (1) Map `[label => url\|null]`, for example `['Procurement' => '#', 'Purchase Order' => null]`. (2) List of rows `[[label, url\|null, openUrl\|null], ...]`, needed when two labels are the same (a group and a screen with the same name, which an array key cannot hold). `openUrl` is the third element (see `menu`). A missing url is `null`. Labels are escaped text and are NOT translated. Do not pass a plain list of strings: each string becomes both label and url (see oddity). |
| `menu` | `bool`, `false` | `true`: items without a url (Module, Group) become clickable links (`href="#"` with `data-dl-crumb-menu`) that open the Layer Menu on that branch. The last item is never a menu link unless `current` is set (then no item is "last"). `false`: items without a url are plain `<span>` text. |
| `current` | `?string`, `null` | Text of the current position after the last item, for example `Form PO-0001`. It is a plain `<span>` (not translated). When set, no item counts as the last one: the last item with a url is a normal link (back to the grid of the screen), and the last item without a url becomes a menu link under `menu`. When `null`, the last item is the current page. |

## Behavior

### How each item is drawn

For each row `[text, url, open]`; "last" means the final row while `current` is `null`:

| Case | Rendered |
|---|---|
| `url` set, last | `<a href class="is-current" aria-current="page" title="Reload <text>">`: link to the page itself (semibold). |
| `url` set, not last | `<a href>`: normal link. |
| no `url`, `menu` on, not last | `<a href="#" data-dl-crumb-menu=... data-leaf=... title="Open menu <text>">` plus `data-dl-crumb-url` when `open` is set. |
| no `url`, otherwise (menu off, or last item) | `<span>text</span>` (bold, ink colour). |

The chevron `<i>` goes between rows, and before `current`. `data-dl-crumb-menu` is the JSON list of labels from the first item up to this one (the menu path); `data-leaf` is the label of the last row, which the menu can highlight. The titles "Reload" and "Open menu" go through the dictionary; labels do not.

### Menu mode click (`core.js`)

On click of `[data-dl-crumb-menu]`:

1. The default action is cancelled.
2. The message `{dl: 'menu', path, leaf}` is sent to the Layer Shell: inside an iframe tab by `postMessage` to the parent shell (same origin only); on a page that holds the Layer Menu itself by calling `goTo(path, leaf)` on `[data-dl-layer-menu]`. The menu expands to that branch.
3. If the item has `data-dl-crumb-url` (the `openUrl` third element), the page itself then goes to that address (`location.href`).
4. If a guarded form (`data-dl-guard`) has unsaved changes and there is an `openUrl`, the dialog Save / Don't Save / Cancel comes first; the menu moves only after the user decides. Without `openUrl` the menu opens at once and the page is left as it is.

### Look

`.dl-crumb` is a wrapping flex row, 12.5px, muted colour, links in the accent colour with underline on hover, separators 9px. The current page (`is-current`) and spans are semibold.

### Language, accessibility

- `items` and `current` are never translated; write the labels as they should appear (menu names are data). Only the two tooltip prefixes are translated.
- The `nav` has the fixed `aria-label="breadcrumb"` (not translated). The last linked item has `aria-current="page"`. The chevron icons have no `aria-hidden`, but they are empty icon elements without text.
- Menu links are `href="#"` anchors: they are keyboard focusable and activate with Enter.

## Data and events

- No posted data. No `dl:*` event is emitted by the breadcrumb itself; it has no `dlComponent`. The behaviour is a global click handler in `core.js` keyed on `data-dl-crumb-menu`.
- The shell receives `{dl: 'menu', path: [labels...], leaf: label}` through `postMessage`; the Layer Shell calls `goTo(path, leaf)` on its Layer Menu.
- Hooks: `.dl-crumb`, `.is-current`, `data-dl-crumb-menu`, `data-dl-crumb-url`, `data-leaf`.

## Rules and pitfalls

- The menu path is built from the labels, so the labels must be equal to the menu entry names for the Layer Menu to find the branch.
- `menu` mode is for screens opened inside the Layer Shell. On a page with no Layer Menu and no parent shell the click only cancels the default; nothing opens (unless `openUrl` is set, which then navigates).
- If two items share a label, use the list-of-rows shape (an associative array would merge them).
- Give the last item (the screen itself) a url, usually `url()->current()`, so it is the "Reload" link; use `current` for a deeper position such as the form of one record.
- With `current` set, the last item becomes a normal link back to its grid. Pass its url for that to work.
- `openUrl` is for a group that has a landing page: the right-hand page follows the menu click.
- Do not translate labels by hand with `__()`; the trail is menu data. Wrap data values (a document number in `current`) in nothing: they are already not translated.
- Code oddity (not fixed): `src/Components/Breadcrumb.php:38` normalises a list with integer keys as `[$u, $u]`, so `:items="['Home', 'Orders']"` renders each text as an `<a href="Home">`/`<a href="Orders">` (label = url). Use the map or row shapes.
- Code oddity (not fixed): the `data-leaf` is the last item label (`Breadcrumb.php:40`), also when `current` is set, so the menu highlights the grid screen and not the `current` text.

## Example

Static trail (catalog demo `breadcrumb`):

```blade
<x-dl::breadcrumb :items="['Procurement' => '#', 'Transaksi' => '#', 'Purchase Order' => null]" />
```

Menu mode with a record position (shape of the demo `screen-nav`):

```blade
<x-dl::breadcrumb menu
    :items="['Menus, Accounts & Layout' => null, 'Screen Navigation (Grid ↔ Form)' => $grid]"
    :current="$row ? 'Form '.$row['code'] : null" />
```

Group and screen with the same name, and a group with a landing page (row shape):

```blade
<x-dl::breadcrumb menu :items="[
    ['Sales', null],
    ['Quotation', null, route('quotation.home')],
    ['Quotation', route('quotation.index')],
]" />
```

## Related

- `x-dl::layer-menu`, `x-dl::layer-shell`: the menu that the menu-mode items open and the shell that relays the message from a screen in a tab.
- `x-dl::back-button`: the way back to the previous screen (the breadcrumb is a location, not a back button).
- `x-dl::tabs`, `x-dl::wizard`: other in-page navigation.
- `x-dl::form`: its dirty guard (`data-dl-guard`) is consulted before a menu click that also changes the page.
