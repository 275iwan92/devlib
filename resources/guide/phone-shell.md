## Overview

`<x-dl::phone-shell>` is the application frame for a smartphone held upright. There is NO permanent bar: the only permanent control is ONE hamburger icon (≡) that floats at the top left (fixed, 46 px, slightly transparent until touched, soft shadow). The screen title and the back button sit in a row at the top of the page content (the icon is given room at the left), so the screen can use all of its height, and nothing is stuck to the bottom (a bottom bar can collide with the Android navigation buttons). It is the phone sibling of `x-dl::layer-shell` (desktop). Both are variants of the DEVICE, chosen by `Devlib\Device` and the user's cookie `ui`; neither is an old or new version of the other. (The earlier bottom tab bar is history: tag `history/phone-shell-bottom-tabs-v1`.)

The icon rotates through states on the same button:

- **A**: icon only (the start state of every screen).
- **B**: the ACTIONS of the screen, in a row at the top. Only on a screen that has some: a form (`x-dl::form` panel buttons: Save as the main button, Undo, Back, ...) or a grid (`x-dl::grid` toolbar: search, + New, Filters, Columns, Export). Buttons that do not fit wrap to the next line.
- **C**: the five MAIN MENUS as a row at the top (Home, Stock, ... and a fifth button "Menu" that opens the list of all modules, a "Desktop view" button and Sign out).
- Then back to A. A screen without actions goes A, C, A.

The icon changes shape with the state (≡, save, menu), `aria-label` and `aria-expanded` name the state, and a visually hidden `aria-live` element announces it. B and C close by themselves: after choosing a menu, a tap outside, scrolling the content, or about 6 seconds without touch. The actions row of a FORM does not close by itself while the form is dirty (unsaved), and a small orange dot on the icon shows an unsaved form in every state; choosing a menu while dirty asks for confirmation first.

The content is the slot. A screen that has no phone version yet is shown by its existing desktop page inside the frame: pass its address in `frame` and the page opens in an `iframe` below the title row. The shell marks that page (body class `dl-phone-shelled`), moves its form buttons / grid toolbar to the B row, and watches its form state.

Touch targets are at least 44 px, input text is 16 px (phone browsers do not zoom the page when focusing a 16 px field), and the edges use `env(safe-area-inset-*)`.

Pick another object when you need:

- The desktop frame with the left menu and work tabs: `x-dl::layer-shell`.
- Only a choice of Phone view / Desktop view: `x-dl::device-pick`.

### Generated markup

```html
<div class="dl-phone" data-dl="x-dl::phone-shell" data-dl-phone>
  <button class="dl-phone__burger" data-dl-phone-menu aria-label="Menu" aria-expanded="false">≡</button>   <!-- floating, the only permanent control -->
  <header class="dl-phone__head"><a class="dl-phone__back" href="/">‹</a><h1 class="dl-phone__title">Count Stock</h1></header>   <!-- inside <main> when the content is the slot -->
  <main class="dl-phone__main">…slot, or <iframe class="dl-phone__frame"> with `frame`…</main>
  <div class="dl-phone__drop" data-dl-phone-drop hidden>…state C: <nav class="dl-phone__quick"> main menus + "Menu", the module list, a veil…</div>
</div>
```

The slot named `actions` (`<x-slot:actions>`) puts buttons at the right end of the top bar. Extra attributes go on the root `div`.

## Props

Constructor of `Devlib\Components\PhoneShell` (12 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `title` | `string`, `''` | Screen title in the row at the top of the content (translated). Empty = the brand name. |
| `back` | `?string`, `null` | Address of the back button in the title row of a child screen. `null` = a main screen, no back button. The button is always a link to this address (the shell does not use browser history). |
| `tabs` | `array`, `[]` | The MAIN MENUS of state C: `[['key', 'label', 'icon', 'url'], …]` (for example Home, Stock, Transactions, Count). Use four: the fifth button "Menu" (module list) is added by the shell. They are drawn only in the row of state C. |
| `active` | `string`, `''` | `key` of the main menu that is marked in the row. Empty = none. |
| `modules` | `array`, `[]` | The module list content, same shape as `x-dl::layer-shell`: `[['code', 'label', 'icon', 'children' => [ITEM, …]], …]`, `ITEM = ['code', 'label', 'url', 'icon'?, 'badge'?]`. A child with `items` (a submenu) is flattened one level. Pass only what the user may open (the project filters by role and group); the shell draws everything it receives. |
| `user` | `array`, `['name' => '', 'role' => '']` | Name and role shown at the top of the module list. |
| `brand` | `?string`, `null` | Title when `title` is empty. `null` = `Brand::identity()['name']`. |
| `logout-action` | `string`, `''` | POST address of the Sign out button at the bottom of the module list. Empty = no button. |
| `device-action` | `?string`, `null` | POST address that saves `ui=desktop`: drawn as a "Desktop view" button in the module list. `null` = no such button. |
| `links` | `array`, `[]` | Extra links at the end of the module list: `[['label', 'icon', 'url'], …]` (for example Help). |
| `frame` | `?string`, `null` | Address of a desktop screen shown in an `iframe` as the content (replaces the slot). The desktop page keeps its own behaviour inside; wide tables scroll inside the frame. |
| `contained` | `bool`, `false` | `false` = the shell is as tall as the screen (`100dvh`) and the icon is `position: fixed`. `true` = as tall as its parent and the icon is positioned inside it (catalog demo). |

## Behavior

### States and focus

- Tapping the icon calls `next()`: A to B (only when `hasActions()`, i.e. the page has `.dl-form__acts` or `.dl-grid__toolbar`), B to C, C to A; without actions A to C. A new screen (or the frame finishing a load) always starts at A.
- Focus: entering B focuses the first enabled action button, C the first main menu, A returns focus to the icon. Escape and the dimmed area (state C) go back to A. In C the fifth button toggles the module list (`aria-expanded`).
- Auto-hide: scrolling the content (ignored for 0.5 s after opening), a tap outside the row, a choice, or 6 s of no touch/key inside the shell closes B and C. It waits while focus is inside the row (typing in the grid search), and B is never closed by itself while a form is dirty (`form.is-dirty`).
- Motion: the rows slide in for 0.18 s; with `prefers-reduced-motion: reduce` there is no animation.

### Layout

- Flex column: content only (the only scrolling area, `overflow-y:auto`; its bottom padding includes `env(safe-area-inset-bottom)`). With a slot the title row is the first element of the scrolling content; with `frame` the title row is a fixed row above the iframe. Nothing is stuck to the bottom.
- Wide tables must not widen the page: wrap them in an element with the class `dl-phone__scroll` (scrolls sideways inside). A table in the frame scrolls inside its own page.

### Device choice

`Devlib\Device::fromRequest` gives `hp` or `desktop` (cookie first, then `Sec-CH-UA-Mobile`, then `User-Agent`). The project decides which view/shell to render from it. The shell itself never redirects.

## Data and events

- Server to browser: only markup. Browser to server: links and the two forms in the panel (`device-action`, `logout-action`).
- `el.dlComponent` is the `PhoneShell` instance (`toggle(open)` opens or closes the panel). No `dl:*` events.
- Hooks: `[data-dl-phone]` (attribute `data-state` = A, B or C), `[data-dl-phone-menu]` (the icon), `[data-dl-phone-drop]`, `[data-dl-phone-more]`, `[data-dl-phone-list]`, `[data-dl-phone-close]`; JS class `PhoneShell`. Classes on the framed page: `dl-phone-shelled`, `dl-phone-acts` (state B).

## Rules and pitfalls

- One shell per page; it takes the whole screen, so do not put it inside another frame (except `contained` in a demo).
- Keep `tabs` to four entries; at 360 px five equal columns is the limit.
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
- `x-dl::device-pick`: the Phone / Desktop choice radios (the module list itself offers only a "Desktop view" button).
- `Devlib\Device`: detection and the cookie `ui`.
- `skills/devlib/components.md`: generated prop list.
