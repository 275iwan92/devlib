## Overview

`<x-dl::phone-shell>` is the application frame for a smartphone held upright: a small top bar with ONE hamburger button (≡) at the top left, a back button next to it on child screens, and the screen title; a content area as wide as the screen; and a Menu panel that drops from the top over the content when the hamburger is tapped. There is no bar at the bottom of the screen (it can collide with the Android navigation buttons). It is the phone sibling of `x-dl::layer-shell` (desktop). Both are variants of the DEVICE, chosen by `Devlib\Device` and the user's cookie `ui`; neither is an old or new version of the other. (The earlier bottom tab bar is history: tag `history/phone-shell-bottom-tabs-v1`.)

The Menu panel shows the main menus (`tabs`) as big tiles at the top, then every module the user may open (`modules`), extra links, a "Desktop view" button and Sign out.

The content is the slot. A screen that has no phone version yet is shown by its existing desktop page inside the frame: pass its address in `frame` and the page opens in an `iframe` that fills the content area.

Touch targets are at least 44 px, input text is 16 px (phone browsers do not zoom the page when focusing a 16 px field), and the edges use `env(safe-area-inset-*)` so the bars stay clear of notches and gesture bars.

Pick another object when you need:

- The desktop frame with the left menu and work tabs: `x-dl::layer-shell`.
- Only a choice of Phone view / Desktop view: `x-dl::device-pick`.

### Generated markup

```html
<div class="dl-phone" data-dl="x-dl::phone-shell" data-dl-phone>
  <header class="dl-phone__top"><button class="dl-phone__burger" data-dl-phone-menu aria-label="Menu" aria-expanded="false">≡</button><a class="dl-phone__back" href="/">‹</a><h1 class="dl-phone__title">Count Stock</h1></header>
  <main class="dl-phone__main">…slot, or <iframe class="dl-phone__frame"> with `frame`…</main>
  <div class="dl-phone__sheet" data-dl-phone-sheet hidden>…panel: close X, user, main-menu tiles, module groups, links, Desktop view, sign out; veil below…</div>
</div>
```

The slot named `actions` (`<x-slot:actions>`) puts buttons at the right end of the top bar. Extra attributes go on the root `div`.

## Props

Constructor of `Devlib\Components\PhoneShell` (12 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `title` | `string`, `''` | Screen title in the top bar (translated). Empty = the brand name. |
| `back` | `?string`, `null` | Address of the back button, drawn right of the hamburger on a child screen. `null` = a main screen, no back button. The button is always a link to this address (the shell does not use browser history). |
| `tabs` | `array`, `[]` | The MAIN MENUS, shown as big tiles at the top of the Menu panel: `[['key', 'label', 'icon', 'url'], …]` (for example Home, Stock, Transactions, Count, Reports). Use about five. They are not drawn anywhere else: there is no bottom bar. |
| `active` | `string`, `''` | `key` of the main menu that is marked in the panel. Empty = none. |
| `modules` | `array`, `[]` | The Menu panel content, same shape as `x-dl::layer-shell`: `[['code', 'label', 'icon', 'children' => [ITEM, …]], …]`, `ITEM = ['code', 'label', 'url', 'icon'?, 'badge'?]`. A child with `items` (a submenu) is flattened one level. Pass only what the user may open (the project filters by role and group); the shell draws everything it receives. |
| `user` | `array`, `['name' => '', 'role' => '']` | Name and role shown at the top of the Menu panel. |
| `brand` | `?string`, `null` | Title when `title` is empty. `null` = `Brand::identity()['name']`. |
| `logout-action` | `string`, `''` | POST address of the Sign out button at the bottom of the panel. Empty = no button. |
| `device-action` | `?string`, `null` | POST address that saves `ui=desktop`: drawn as a "Desktop view" button in the Menu panel. `null` = no such button. |
| `links` | `array`, `[]` | Extra links at the end of the module list: `[['label', 'icon', 'url'], …]` (for example Help). |
| `frame` | `?string`, `null` | Address of a desktop screen shown in an `iframe` as the content (replaces the slot). The desktop page keeps its own behaviour inside; wide tables scroll inside the frame. |
| `contained` | `bool`, `false` | `false` = the shell is as tall as the screen (`100dvh`). `true` = as tall as its parent (catalog demo). |

## Behavior

### Menu panel

- The hamburger is the only opener. Tapping it drops the panel from the top, under the top bar, over the content; the icon turns into an X. Keyboard focus moves into the panel (`tabindex="-1"`, `aria-modal`), and returns to the hamburger when it closes.
- It closes with the X button inside the panel (44 px, `aria-label`), a tap on the dimmed area below the panel, the Escape key, or by choosing a screen (the screen loads, so the panel is gone). `aria-expanded` on the hamburger follows the state.
- Links in the panel are ordinary links (full page load). The panel is rendered closed (`hidden`), so without JavaScript the hamburger does nothing but nothing else breaks.

### Layout

- Flex column: top bar, content (the only scrolling area, `overflow-y:auto`; its bottom padding includes `env(safe-area-inset-bottom)`). Body scroll is not used, so the top bar never moves. There is no space reserved for a bottom bar; a sticky action bar (`x-dl::phone-bar`, the form buttons) sits at the very bottom of the content area.
- Wide tables must not widen the page: wrap them in an element with the class `dl-phone__scroll` (scrolls sideways inside). A table in the frame scrolls inside its own page.

### Device choice

`Devlib\Device::fromRequest` gives `hp` or `desktop` (cookie first, then `Sec-CH-UA-Mobile`, then `User-Agent`). The project decides which view/shell to render from it. The shell itself never redirects.

## Data and events

- Server to browser: only markup. Browser to server: links and the two forms in the panel (`device-action`, `logout-action`).
- `el.dlComponent` is the `PhoneShell` instance (`toggle(open)` opens or closes the panel). No `dl:*` events.
- Hooks: `[data-dl-phone]`, `[data-dl-phone-menu]` (the hamburger), `[data-dl-phone-sheet]`, `[data-dl-phone-panel]`, `[data-dl-phone-close]`; JS class `PhoneShell`.

## Rules and pitfalls

- One shell per page; it takes the whole screen, so do not put it inside another frame (except `contained` in a demo).
- Keep the main menus to about five; they are tiles in two columns.
- The shell does not know access rights. Filter `modules` and `tabs` on the server.
- Desktop pages shown in `frame` are not made for a phone; their wide parts may need scrolling. Give a screen a phone version when it is used often.
- Input text is forced to 16 px only inside the shell (`.dl-phone input`), not inside the iframe.
- Related lint rules: none.

## Example

```blade
<x-dl::phone-shell title="Home" active="home" :tabs="[
    ['key' => 'home', 'label' => 'Home', 'icon' => 'fa-solid fa-house', 'url' => url('/')],
    ['key' => 'stock', 'label' => 'Stock', 'icon' => 'fa-solid fa-boxes-stacked', 'url' => url('/stock')],
    ['key' => 'trx', 'label' => 'Transactions', 'icon' => 'fa-solid fa-right-left', 'url' => url('/trx')],
    ['key' => 'count', 'label' => 'Count', 'icon' => 'fa-solid fa-clipboard-check', 'url' => url('/count')],
]" :modules="$modules" :user="$user" :logout-action="route('logout')" :device-action="route('ui.set')">
    …screen content…
</x-dl::phone-shell>
```

A screen without a phone version, shown inside the frame:

```blade
<x-dl::phone-shell title="Items" :back="url('/')" :tabs="$tabs" active="" :modules="$modules" :frame="route('products.index')" />
```

## Related

- `x-dl::layer-shell`: the desktop frame.
- `x-dl::device-pick`: the Phone / Desktop choice radios (the Menu panel itself offers only a "Desktop view" button).
- `Devlib\Device`: detection and the cookie `ui`.
- `skills/devlib/components.md`: generated prop list.
