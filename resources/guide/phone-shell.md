## Overview

`<x-dl::phone-shell>` is the application frame for a smartphone held upright: a small top bar (screen title, back button), a full-width content area, five buttons in a bar at the BOTTOM (four links you define, plus Menu) and a Menu sheet that slides up from the bottom listing every module the user may open. It is the phone sibling of `x-dl::layer-shell` (desktop). Both are variants of the DEVICE, chosen by `Devlib\Device` and the user's cookie `ui`; neither is an old or new version of the other.

The content is the slot. A screen that has no phone version yet is shown by its existing desktop page inside the frame: pass its address in `frame` and the page opens in an `iframe` that fills the content area.

Touch targets are at least 44 px, input text is 16 px (phone browsers do not zoom the page when focusing a 16 px field), and the edges use `env(safe-area-inset-*)` so the bars stay clear of notches and gesture bars.

Pick another object when you need:

- The desktop frame with the left menu and work tabs: `x-dl::layer-shell`.
- Only a choice of Phone view / Desktop view: `x-dl::device-pick`.

### Generated markup

```html
<div class="dl-phone" data-dl="x-dl::phone-shell" data-dl-phone>
  <header class="dl-phone__top"><a class="dl-phone__back" href="/">‹</a><h1 class="dl-phone__title">Count Stock</h1></header>
  <main class="dl-phone__main">…slot, or <iframe class="dl-phone__frame"> with `frame`…</main>
  <div class="dl-phone__sheet" data-dl-phone-sheet hidden>…veil, panel: user, module groups, links, device form, sign out…</div>
  <nav class="dl-phone__tabs"><a class="dl-phone__tab is-active">…</a>… <button class="dl-phone__tab" data-dl-phone-menu>Menu</button></nav>
</div>
```

The slot named `actions` (`<x-slot:actions>`) puts buttons at the right end of the top bar. Extra attributes go on the root `div`.

## Props

Constructor of `Devlib\Components\PhoneShell` (12 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `title` | `string`, `''` | Screen title in the top bar (translated). Empty = the brand name. |
| `back` | `?string`, `null` | Address of the back button at the left of the top bar. `null` = a main screen, no button. The button is always a link to this address (the shell does not use browser history). |
| `tabs` | `array`, `[]` | The link tabs, left to right: `[['key', 'label', 'icon', 'url'], …]`. Use four; the Menu tab is always added as the fifth. Labels are translated. |
| `active` | `string`, `''` | `key` of the tab that is lit. `'menu'` lights the Menu tab. |
| `modules` | `array`, `[]` | The Menu sheet content, same shape as `x-dl::layer-shell`: `[['code', 'label', 'icon', 'children' => [ITEM, …]], …]`, `ITEM = ['code', 'label', 'url', 'icon'?, 'badge'?]`. A child with `items` (a submenu) is flattened one level. Pass only what the user may open (the project filters by role and group); the shell draws everything it receives. |
| `user` | `array`, `['name' => '', 'role' => '']` | Name and role shown at the top of the Menu sheet. |
| `brand` | `?string`, `null` | Title when `title` is empty. `null` = `Brand::identity()['name']`. |
| `logout-action` | `string`, `''` | POST address of the Sign out button at the bottom of the sheet. Empty = no button. |
| `device-action` | `?string`, `null` | POST address that saves the chosen view (field `ui`, see `x-dl::device-pick`). `null` = the sheet has no Display choice. |
| `links` | `array`, `[]` | Extra links at the end of the module list: `[['label', 'icon', 'url'], …]` (for example Help). |
| `frame` | `?string`, `null` | Address of a desktop screen shown in an `iframe` as the content (replaces the slot). The desktop page keeps its own behaviour inside; wide tables scroll inside the frame. |
| `contained` | `bool`, `false` | `false` = the shell is as tall as the screen (`100dvh`). `true` = as tall as its parent (catalog demo). |

## Behavior

### Menu sheet

- The Menu tab (a button) opens the sheet from the bottom, above the tab bar; the tab stays visible and closes the sheet again. A tap on the dimmed area or the Escape key also closes it. `aria-expanded` follows the state.
- Links in the sheet and in the tabs are ordinary links (full page load). The sheet is rendered closed (`hidden`), so without JavaScript the Menu tab does nothing but nothing else breaks.

### Layout

- Flex column: top bar, content (the only scrolling area, `overflow-y:auto`), tab bar. Body scroll is not used, so the bars never move.
- Wide tables must not widen the page: wrap them in an element with the class `dl-phone__scroll` (scrolls sideways inside). A table in the frame scrolls inside its own page.

### Device choice

`Devlib\Device::fromRequest` gives `hp` or `desktop` (cookie first, then `Sec-CH-UA-Mobile`, then `User-Agent`). The project decides which view/shell to render from it. The shell itself never redirects.

## Data and events

- Server to browser: only markup. Browser to server: links and the two forms in the sheet (`device-action`, `logout-action`).
- `el.dlComponent` is the `PhoneShell` instance (`toggle(open)` opens or closes the sheet). No `dl:*` events.
- Hooks: `[data-dl-phone]`, `[data-dl-phone-menu]`, `[data-dl-phone-sheet]`, `[data-dl-phone-close]`; JS class `PhoneShell`.

## Rules and pitfalls

- One shell per page; it takes the whole screen, so do not put it inside another frame (except `contained` in a demo).
- Keep the tab list to four links. A sixth cell would not fit 360 px wide.
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
<x-dl::phone-shell title="Items" :back="url('/')" :tabs="$tabs" active="menu" :modules="$modules" :frame="route('products.index')" />
```

## Related

- `x-dl::layer-shell`: the desktop frame.
- `x-dl::device-pick`: the Phone / Desktop choice (also drawn inside the Menu sheet).
- `Devlib\Device`: detection and the cookie `ui`.
- `skills/devlib/components.md`: generated prop list.
