## Overview

`<x-dl::layer-menu>` is the layered left navigation panel of an application: layer 1 shows the modules, a click on a branch slides the whole panel to the next layer (menu, sub, sub-sub, any depth), `Back` goes up one layer. Everything stays inside the same panel, with the same width. The bottom of the panel is the user row (avatar and name); clicking it slides the Profile panel up over the menu. Optionally a compact chat can live in the panel (swap with the menu, or dock to the right side of the screen).

It is the only main menu of DevLib applications. In a real application you do not place it yourself: `x-dl::layer-shell` builds it from the `modules` array, wires its events to MDI tabs, the Ctrl+K palette, drawers and the account actions. Use `x-dl::layer-menu` alone only for a catalog demo, or for a layout that is not a shell. Use `x-dl::side-menu` for a secondary accordion panel inside a screen.

Root markup (the component is an `<aside>`):

```html
<aside class="dl-lm [is-mini] [is-peek]" data-dl="x-dl::layer-menu" data-dl-layer-menu data-id="layer"
       data-active="" data-counts="1" data-fold-groups="0" data-root="Modules" data-dock=""
       data-tree="[...json...]" data-views="[...json...]" data-view="">
  <div class="dl-lm__head" [data-lm-home role="button" tabindex="0"]> logo badge + <b>title</b> + <small data-lm-tag> </div>
  <div class="dl-lm__bar"> views switch, swap, dock, search, mini buttons </div>
  <div class="dl-lm__modes">
    <div class="dl-lm__vp"><div class="dl-lm__track" data-lm-track>  one .dl-lm__pane per layer  </div></div>
    <div class="dl-lm__ov" data-lm-ov="chat">...</div>          <!-- only with the chat slot -->
    <div class="dl-lm__ov" data-lm-ov="profile">...</div>       <!-- only with the profile slot -->
  </div>
  <div data-lm-acts hidden>...account action buttons...</div>    <!-- only with user -->
  <div class="dl-lm__foot" data-lm-foot><button class="dl-lm__userbtn" data-lm-user>avatar, name, role, env-badge</button></div>
</aside>
```

Extra HTML attributes (`class`, `style`, `data-*`) and `can="module.action"` go on the root `aside` (x-dl::layer-shell adds `dl-shell__lm`). Without the ability the panel is not rendered.

## Props

Constructor of `Devlib\Components\LayerMenu`: 19 props. Use `:prop="expr"` for anything that is not a plain string; booleans can be bare attributes (`search`, `home`).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `items` | `array`, `[]` | The tree of layer 1 (see "Item shape"). Ignored when `views` is not empty. Serialized into `data-tree` (when `views` is set, `data-tree` is `[]`). |
| `title` | `?string`, `null` | Application name in the head. `null` = `Brand::identity()['name']` (container binding `devlib.brand`, else `config('devlib.brand.name')`, else `DEVLIB`). A text prop: translated through the dictionary, so wrap a data value in `raw()`. Also the `alt` of the logo. |
| `logo` | `?string`, `null` | URL of the APPLICATION logo image; `null` = the gradient badge (`dl-logo-badge`). The company logo is not shown here (it is for print headers). Unlike `title`, no default is read from `Brand` in this component. |
| `tagline` | `?string`, `null` | Small line under the name (`data-lm-tag`). Not translated. It is replaced by the word `Chat` or `Profile` while one of those panels is open. No default from `Brand` here. |
| `id` | `string`, `'layer'` | `data-id`; the namespace of the browser storage (`dl-lm-<id>`, `-mini`, `-fold`). Use a unique id per menu. |
| `active` | `?string`, `null` | Key of the leaf to highlight and open on load (`data-active`). Wins over the position saved in the browser. |
| `user` | `?string`, `null` | Display name. Non-empty = the user row, the Profile panel host and the action list exist. Empty = no foot row at all (so no Profile). Used for the avatar initials (first letters of the first two words, upper case). Not translated. |
| `role` | `?string`, `null` | Role text: small line in the user row and, after `login`, in the Profile header. |
| `login` | `?string`, `null` | Second identity text in the Profile header (the shell passes the e-mail). The header small line is `login · role`, empty parts are skipped. |
| `counts` | `bool`, `true` | Shows the number of screens under each branch (`dl-lm__n`) and, on foldable captions, the group total. `data-counts`. |
| `root-caption` | `string`, `'Modules'` | Caption on layer 1 when there are no `views` (with `views` the label of the current view is shown). Translated through the dictionary at render time (`data-root`). |
| `dock` | `?string`, `null` | CSS selector of the container on the right side of the layout where the chat can be docked. `null` = the chat docks into a fixed `aside.dl-lm-dock.is-fixed` that the script appends to `body`. Only used when the `chat` slot exists. |
| `actions` | `array`, `[]` | Account action rows of the Profile panel: `[['key', 'label', 'icon', 'tone'?, 'confirm'?, 'sep'?]]` (see "Profile panel"). Only rendered when `user` is set. |
| `views` | `array`, `[]` | Several menu roots swapped by small icons in the head (for example Operational and Management Reports): `[['key', 'label', 'icon', 'items' => [ITEM...]]]`. The icons appear only when there are 2 or more views. When not empty, `items` is ignored. |
| `initial-view` | `?string`, `null` | `key` of the view shown first (`data-view`). Otherwise: the view that contains the active screen, then the view saved in the browser, then the first. Named `initial-view` because `view` is a reserved Laravel name. |
| `search` | `bool`, `false` | Shows the magnifier button in the head. A click fires `dl:layer-menu-action` with key `palette`; the menu itself has no search box (the shell opens its Ctrl+K palette). |
| `collapsible` | `bool`, `true` | Shows the minimize button. The mini state (one icon column) is stored per browser. See "Mini mode". |
| `home` | `bool`, `false` | Makes logo and name a button (`data-lm-home`, focusable, Enter/Space) that fires `dl:layer-menu-action` with key `home`. In the shell this closes all tabs and shows the opening screen. |
| `fold-groups` | `string`, `''` | Layer-1 group captions (`group`) become fold buttons. `''` = plain captions. `'1'` (any other non-empty value except `accordion`) = every caption folds on its own. `'accordion'` = one group open at a time (first load: the group of the active screen, else the first). Written as `data-fold-groups` (`0` when empty). |

### Slots

| Slot | Content |
|---|---|
| `chat` | One `<x-dl::chat compact ...>`. Enables the swap button, the dock button and the unread badges. Rendered inside `[data-lm-chathost]`; the SAME element moves between the left panel and the dock. |
| `profile` | Profile pages: `<section data-lm-page="key" data-label="Text" data-icon="fa-...">content</section>` one per page. A section whose key equals an action key replaces that action row with a page row. Optional `data-lm-children="a,b"` makes the section a parent page that lists the pages `a` and `b` (they are hidden from the main list). Needed for the Profile panel to exist (without the slot there is no Profile overlay). |

### Item shape

```php
['label' => 'Sales', 'icon' => 'fa-cart-shopping', 'group' => 'Operations', 'items' => [   // branch: non-empty items
    ['key' => 'co', 'label' => 'Customer Order', 'icon' => 'fa-clipboard-list', 'url' => '/co', 'badge' => '4', 'tone' => 'warn'],   // leaf
]],
['label' => 'Security', 'icon' => 'fa-shield', 'key' => 'sec', 'pin' => true],    // layer 1 only: stuck at the bottom of the panel
```

| Key | Meaning |
|---|---|
| `label` | Row text and tooltip. Required. Not translated (data). |
| `icon` | Icon meaning from `Devlib\Icon::MAP` or a Font Awesome class; `fa()` adds `fa-solid` when there is no space. Default `fa-circle-dot`. A branch also uses it in its layer title. |
| `key` | Identity of a leaf (selected state, event `key`, saved position). Default = `label`. |
| `url` | `href` of a leaf; empty or `#` = no navigation (the click is prevented). |
| `badge`, `tone` | Pill after the label; `tone` becomes `is-<tone>` (`warn`, `success`, `danger`, default `accent`). A badge that starts with `icon:` (for example `icon:fa-solid fa-circle-check`) shows only that icon. |
| `group` | Caption of the group in that layer: consecutive entries with the same `group` sit under one caption. |
| `pin` | Layer 1 only: the row sticks to the bottom of the panel near the user row. |
| `items` | Non-empty = branch; no `items` = a final screen. Nesting is free. |

## Behavior

### Layers

- Layer 1 lists the root items; a branch row shows a chevron and (with `counts`) the number of final screens below it. Click = the track slides left (`translateX(-100% * depth)`), a `Back` button and the branch title (icon and name) head the new layer, and the first `Back` button receives the focus. Panes that are not current are `inert`.
- Back: the `Back` button, the key `Escape` or `ArrowLeft` while the focus is inside the track and the menu is shown. There is no breadcrumb in the panel; breadcrumbs belong to screens.
- Clicking a final screen: marks it `is-active`, stores it, closes the floating (peek) menu in mini mode, fires `dl:layer-menu` (cancelable) and, when the leaf has no `url`, shows a preview toast (`Open: A › B (preview)`).
- The position (opened path labels, selected key, current view) is saved in `localStorage` `dl-lm-<id>` and restored on load; `active` and the view that contains it take priority.

### Group folding (`fold-groups`)

Only on layer 1. A caption becomes `<button class="dl-lm__cap dl-lm__cap--fold" data-lm-fold aria-expanded>`; its children sit in a wrapper that rolls up (`is-folded`, `inert`). In `accordion` mode opening one closes the others; the open group name is saved in `dl-lm-<id>-fold` as `{open}`, otherwise the list of folded captions. Mini mode always shows everything. Pinned rows are outside the folding.

### Views

With two or more `views`, small icon buttons (`data-lm-view`, `aria-pressed`) sit in the head. Switching resets the path to layer 1, returns from Chat/Profile to the menu and fires `dl:layer-menu-view`.

### Mini mode and peek

The minimize button toggles `is-mini` (stored as `dl-lm-<id>-mini`: `1` / `0`) and fires `dl:layer-menu-mini {mini}`. In mini mode the panel is one icon column. Clicking a branch, `Back` or the avatar opens the full menu floating over the screen (`is-peek`, the screen does not move). It closes when a final screen is chosen, when the user clicks outside the panel, when the window loses focus, or with `Escape` on the menu. While Chat or Profile is shown the floating menu cannot be closed.

### Profile panel

Available when `user` is set and the `profile` slot exists.

- Open: click the avatar and name row at the bottom (`data-lm-user`, `aria-expanded`); click it again to close. In mini mode the first click opens the floating menu directly on Profile.
- The panel slides up over the menu with the same width. Top-right of the panel: the X (`data-lm-close`, `aria-label` Close) closes it and returns to the menu (or to the chat when a left chat is open).
- Header (`dl-lm__me`): a large avatar with initials, the name, and a small line `login · role`.
- List (`data-lm-plist`): built in the order of `actions`. A row whose key has a page in the `profile` slot becomes a page row (chevron); other rows stay action buttons (`data-lm-act`). `sep => true` draws a separator line before the row; `tone => 'danger'` gives the red style (`is-danger`). Pages from the slot that are not an action key and have no parent (for example Setup) are inserted right after the `help` row, or at the end when there is no `help` action.
- Opening a page moves the `<section>` (it is moved, not copied, so form input is kept) into a second pane with a `Back` button and the page title; `Back` goes to the list, or to the page from which it was opened (stack). Fires `dl:layer-menu-page {key}`.
- Action click: a page key opens that page; `chat` fires `dl:layer-menu-action` and then opens the left chat (only if the `chat` slot exists; the row is not listed when there is no chat at all); other keys with a `confirm` text first ask in a confirm dialog (title = the label, tone `danger` for danger rows), then fire `dl:layer-menu-action {key}`. If nobody cancels that event, a toast `<label> (preview)` appears.
- Footer (the user row at the bottom of the panel) shows avatar, name, role and the line produced by `<x-dl::env-badge part="where" />`; the title line next to the application name uses `<x-dl::env-badge part="title" variant="title" />`. Both are empty unless the application binds `devlib.env_where` / `devlib.env_title` in the container.

### Chat in the panel (optional)

With the `chat` slot: opening Chat shows it over the menu; the swap button toggles menu and chat; the dock button moves the chat element to the right-hand dock (`dock` selector or a fixed aside) where its own bar offers "move back" and close; the close X sits in the chat title bar. Unread counts come from `dl:chat-unread` of the chat and appear as badges on the avatar, the swap button and the Chat row.

### Language and text

Translated at render or run time: `title`, `root-caption`, tooltips and `aria-label`s of the head buttons ("Choose a menu", "Search (Ctrl K)", "Minimize menu" / "Expand menu", "Profile", "Close", "Swap menu / chat"), the home hint ("Opening screen (closes all tabs)") and the chat/dock button titles. Not translated: item `label`, `badge`, `group`, `tagline`, `user`, `role`, `login`, action `label` (translate them where you build the array; `LayerShell::actions()` already does) and the word `Back` (hard-coded in the script).

### Accessibility as implemented

Buttons have `title` and mostly `aria-label`; fold captions have `aria-expanded`; view buttons and the mini button have `aria-pressed`; the user row has `aria-expanded`; non-current panes are `inert`; the home head has `role="button"` and `tabindex="0"`. Keyboard: Tab, Enter, `Escape` / `ArrowLeft` (back). There is no `role="menu"` / `role="tree"` and the active leaf has no `aria-current`.

## Data and events

### Events (all bubble, from the root `aside`)

| Event | Detail | Notes |
|---|---|---|
| `dl:layer-menu` | `{key, label, url, path}` (`path` = array of labels from layer 1 to the leaf) | Final screen clicked. Cancelable: `preventDefault()` also stops the link. |
| `dl:layer-menu-action` | `{key}` | `palette` (magnifier), `home` (logo), `chat` (Chat row), or the key of an account action. Cancelable. |
| `dl:layer-menu-mini` | `{mini}` | Mini state changed by the user or restored. |
| `dl:layer-menu-view` | `{key}` | A view was selected. |
| `dl:layer-menu-panel` | `{chat, dock, show}` | `show` is `menu`, `chat` or `profile`; fired by every panel change. |
| `dl:layer-menu-page` | `{key}` | A Profile page opened. |

### JS API (`el.dlComponent`, class `LayerMenu`)

`goTo(labels, leaf?)` opens the menu on the branch with those labels (falls back to searching the leaf label); `back()`; `setView(index)`; `setMini(on)`; `show('menu' | 'chat' | 'profile')`; `openProfile(pageKey?)`; `toggleProfile()`; `openPage(key)` / `closePage()`; `openChat()` / `closeChat()` / `setDock(on)`; `chatVisible()`; `draw()`. Properties: `path`, `sel`, `views`, `view`, `root`, `st` (`{chat, dock, show}`), `mini`, `peek`, `unread`.

### Server helpers

`tree()` converts the item array (icons to Font Awesome classes, default key = label for leaves) and keeps only `label`, `icon`, `key`, `url`, `badge`, `tone`, `group`, `pin` and `items`. `viewData()` does the same for `views`. `initials()` builds the avatar letters. `fa($icon)` maps an icon meaning to a class.

### Browser storage

| Key | Content |
|---|---|
| `dl-lm-<id>` | `{path: [labels], sel, view}` |
| `dl-lm-<id>-mini` | `1` / `0` |
| `dl-lm-<id>-fold` | `{open}` (accordion) or array of folded captions |

`Devlib.resetWorkspace()` removes every `dl-lm-*` key except the `-mini` ones (fresh login, Sign out): the menu returns to layer 1.

## Rules and pitfalls

- Do not build the application frame from `x-dl::layer-menu`; use `x-dl::layer-shell`, which gives the data, tabs, palette, drawers and the standard Profile panel. This is the only main menu.
- No `user` = no user row, no Profile, no actions, even if `actions` and the `profile` slot are given. No `profile` slot = a user row that shows nothing when clicked.
- Leaves need a stable `key`; with the default (the label) a rename changes the saved selection.
- `items` is ignored as soon as `views` is not empty. A single view still works but shows no switch icons.
- `fold-groups` only works for items that have a `group` on layer 1; with the value `1` there is no accordion.
- Item labels, badges and groups are data and are not translated; JSON is placed in `data-tree`, so do not put secrets in the array.
- The magnifier and the logo only emit events. Without a listener (outside `x-dl::layer-shell`) nothing happens.
- Only one chat per menu. The chat element is moved, never cloned; keep a single `x-dl::chat` in the slot.
- The mini state survives logout on purpose; everything else is cleared by `Devlib.resetWorkspace()`.
- Code oddity (not fixed): `src/Components/LayerMenu.php:7` says "18 prop" but the constructor has 19.
- Code oddity (not fixed): `LayerMenu::tree()` (`src/Components/LayerMenu.php:82`) does not copy `badgeTitle`, although `x-dl::layer-shell` sets it and the script reads `n.badgeTitle` for `icon:` badges (`resources/assets/js/components/layer-menu.js:179`); the tooltip of icon-only badges is therefore always empty.
- Code oddity (not fixed): the class comment (`src/Components/LayerMenu.php:36`) still describes "user row + gear" as the entry to Chat and Profile; the gear was removed and the user row is the only door to Profile (the view comment at `LayerMenu.php:154` says so).
- Code oddity (not fixed): the word `Back` is hard-coded in English in the script (`resources/assets/js/components/layer-menu.js:5`) and is not translated; the same applies to the title `Chat` of the dock bar (`layer-menu.js:344`).

## Example

Taken in shape from the catalog demo (`site/resources/views/demos/layer-menu.blade.php`), fictional data. The page area and `#demo-lm-dock` belong to the page layout.

```blade
@php
    $tree = [
        ['label' => 'Sales', 'icon' => 'fa-cart-shopping', 'items' => [
            ['label' => 'Quotation', 'icon' => 'fa-file-invoice', 'group' => 'Transactions', 'items' => [
                ['key' => 'quo.entry', 'label' => 'Entry', 'icon' => 'fa-file-circle-plus'],
                ['key' => 'quo.list', 'label' => 'Worklist', 'icon' => 'fa-list', 'badge' => '3', 'tone' => 'warn'],
            ]],
            ['key' => 'r-sales', 'label' => 'Sales Summary', 'icon' => 'fa-chart-column', 'group' => 'Reports'],
        ]],
        ['label' => 'Inventory', 'icon' => 'fa-boxes-stacked', 'items' => [
            ['key' => 'r-card', 'label' => 'Stock Card', 'icon' => 'fa-rectangle-list'],
        ]],
    ];
    $actions = [
        ['key' => 'theme', 'label' => 'Theme', 'icon' => 'fa-palette'],
        ['key' => 'logout', 'label' => 'Log out', 'icon' => 'logout', 'tone' => 'danger', 'confirm' => 'Sign out of the application?'],
    ];
@endphp
<div class="dl-lm-demo">
    <x-dl::layer-menu id="demo-layer" search :items="$tree" user="Budi Santoso" role="Administrator" login="budi@example.test" dock="#demo-lm-dock" :actions="$actions">
        <x-slot:chat><x-dl::chat compact :me="$chat['me']" :people="$chat['people']" :convos="$chat['convos']" /></x-slot:chat>
        <x-slot:profile>
            <section data-lm-page="theme" data-label="Theme" data-icon="fa-palette"><x-dl::theme-picker /></section>
        </x-slot:profile>
    </x-dl::layer-menu>
    <aside id="demo-lm-dock" class="dl-lm-dock"></aside>
</div>
```

Listening in a custom layout:

```js
document.addEventListener('dl:layer-menu', e => { e.preventDefault(); openScreen(e.detail.key); });
document.addEventListener('dl:layer-menu-action', e => { if (e.detail.key === 'palette') { e.preventDefault(); openPalette(); } });
```

## Related

- `x-dl::layer-shell`: the application frame that builds this menu from `modules` and wires tabs, palette, drawers and the Profile panel.
- `x-dl::side-menu`: a secondary accordion panel (not the main menu).
- `x-dl::chat`: the compact chat placed in the `chat` slot.
- `x-dl::env-badge`: the host/client line in the user row and the environment suffix in the title.
- `x-dl::theme-picker`, `x-dl::lang-switch`: the pages used inside the Setup page of the Profile panel.
- `x-dl::side-screen`: the wide window opened by the Edit profile and Help & support actions of the shell.
- `skills/devlib/components.md`, `skills/devlib/object.md`: generated prop list and object rules.
