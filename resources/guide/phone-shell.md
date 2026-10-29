## Overview

`<x-dl::phone-shell>` is the application frame for a smartphone held upright. There is NO permanent bar: the only permanent control is ONE hamburger button that floats at the top left (a rounded square, 46 px, radius 13 px, opacity 0.9, `position: fixed` with `env(safe-area-inset-top/left)`). The screen title and the back button sit in a row at the top of the page content (the button is given room at the left), so the screen can use all of its height, and nothing is stuck to the bottom (a bottom bar can collide with the Android navigation buttons). It is the phone sibling of `x-dl::layer-shell` (desktop). Both are variants of the DEVICE, chosen by `Devlib\Device` and the user's cookie `ui`; neither is an old or new version of the other. (The earlier bottom tab bar is history: tag `history/phone-shell-bottom-tabs-v1`.)

Tapping the button makes it DISAPPEAR and a full-width BAR slides in at the top (62 px, surface background, thin bottom line), five equal items with icon and label, the item "Menu" (≡) always at the right end, separated by a thin line:

- **State A**: only the floating button (the start state of every screen).
- **State B** (only on a screen that has actions): the bar of the ACTIONS of the screen. Form: Save (the main button, accent colour), Undo, Back, New ...; grid: Search, + New (main), Filter, Export; report / print page: Print (main), Paper, Group, Ink saver; any other screen: its own buttons marked `data-phone-act`. Actions beyond four, or rarely used (Deactivate ...), go into the section "More actions" at the top of the module list. Tapping a button runs the real button of the page (`click()`) and the bar hides, except Menu. **Filter, Export, Paper and Group open a PANEL from the top, under the bar** (the bar stays, the content is dimmed): Filter = the view choice (Table / Cards, only for a grid that declares `cards`), one select per grid filter, "Set columns" (the column check list; there is no bar button for columns) and Apply; Export = the list of formats; Paper = A4 portrait / A4 landscape / A5 landscape (plus the price and VAT choices when the report has them); Group = the report groups. The panel closes with a tap on the dimmed area, Escape, or its action; the bottom of the screen has no control. Ink saver is a switch in the bar (accent colour when on).
- **State C**: the bar of the FIVE MAIN MENUS (Home, Stock, Transactions, Count and Menu). Tapping a main menu goes to that screen. Tapping Menu opens the LIST of all modules under the bar (dimmed background, "More actions", Desktop view and Sign out in it); tapping Menu again or outside closes the list.
- A screen without actions goes A, C, A. In B the item Menu goes to C.

The bar and the list hide by themselves (a choice, a tap outside, scrolling the content, or about 6 seconds without touch); the button comes back. The actions bar of a FORM does not hide by itself while the form is dirty (unsaved), and a small yellow dot on the button (and on the item Menu) shows an unsaved form; choosing a menu while dirty asks for confirmation first. `aria-label` and `aria-expanded` name the state, a visually hidden `aria-live` element announces it, Escape closes, and `prefers-reduced-motion` removes the slide.

The content is the slot. A screen that has no phone version yet is shown by its existing desktop page inside the frame: pass its address in `frame` and the page opens in an `iframe` below the title row. The shell marks that page (body class `dl-phone-shelled`), hides its form buttons / grid toolbar / print bar, shows their actions in the bar of state B, and watches its form state. No swipe gesture from the top edge is used (Chrome Android uses it for pull-to-refresh); the button is a tap.

Touch targets are at least 44 px, input text is 16 px (phone browsers do not zoom the page when focusing a 16 px field), and the edges use `env(safe-area-inset-*)`.

Pick another object when you need:

- The desktop frame with the left menu and work tabs: `x-dl::layer-shell`.
- Only a choice of Phone view / Desktop view: `x-dl::device-pick`.

### Generated markup

```html
<div class="dl-phone" data-dl="x-dl::phone-shell" data-dl-phone data-state="A">
  <button class="dl-phone__burger" data-dl-phone-menu aria-label="Menu" aria-expanded="false">≡</button>   <!-- floating, the only permanent control -->
  <header class="dl-phone__head"><a class="dl-phone__back" href="/">‹</a><h1 class="dl-phone__title">Count Stock</h1></header>   <!-- inside <main> when the content is the slot -->
  <div class="dl-phone__bar" data-dl-phone-bar hidden>
    <div class="dl-phone__items" data-dl-phone-acts hidden>…buttons built from the page actions + Menu…</div>       <!-- state B -->
    <nav class="dl-phone__items" data-dl-phone-main hidden><a class="dl-phone__q">…</a>… <button class="dl-phone__q dl-phone__q--menu">Menu</button></nav>   <!-- state C -->
  </div>
  <div class="dl-phone__drop" data-dl-phone-drop hidden>…list of all modules + veil…</div>
  <main class="dl-phone__main">…slot, or <iframe class="dl-phone__frame"> with `frame`…</main>
</div>
```

The slot named `actions` (`<x-slot:actions>`) puts buttons at the right end of the top bar. Extra attributes go on the root `div`.

## Props

Constructor of `Devlib\Components\PhoneShell` (15 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `title` | `string`, `''` | Screen title in the row at the top of the content (translated). Empty = the brand name. |
| `back` | `?string`, `null` | Address of the back button in the title row of a child screen. `null` = a main screen, no back button. The button is always a link to this address (the shell does not use browser history). |
| `tabs` | `array`, `[]` | The MAIN MENUS of the bar of state C: `[['key', 'label', 'icon', 'url'], …]` (for example Home, Stock, Transactions, Count). Use four: the fifth item "Menu" (module list) is added by the shell, always at the right end. |
| `active` | `string`, `''` | `key` of the main menu that is marked in the row. Empty = none. |
| `modules` | `array`, `[]` | The module list content, same shape as `x-dl::layer-shell`: `[['code', 'label', 'icon', 'children' => [ITEM, …]], …]`, `ITEM = ['code', 'label', 'url', 'icon'?, 'badge'?]`. A child with `items` (a submenu) is flattened one level. Pass only what the user may open (the project filters by role and group); the shell draws everything it receives. |
| `user` | `array`, `['name' => '', 'role' => '']` | Name and role shown at the top of the module list. When the application binds `devlib.net` (see `x-dl::net-line`) the small line `client IP → server IP` with the connection status is drawn under them. |
| `brand` | `?string`, `null` | Title when `title` is empty. `null` = `Brand::identity()['name']`. |
| `logout-action` | `string`, `''` | POST address of the Sign out button at the bottom of the module list. Empty = no button. |
| `device-action` | `?string`, `null` | POST address that saves `ui=desktop`: drawn as a "Desktop view" button in the module list. `null` = no such button. |
| `links` | `array`, `[]` | Extra links at the end of the module list: `[['label', 'icon', 'url'], …]` (for example Help). |
| `frame` | `?string`, `null` | Address of a desktop screen shown in an `iframe` as the content (replaces the slot). The desktop page keeps its own behaviour inside; wide tables scroll inside the frame. |
| `contained` | `bool`, `false` | `false` = the shell is as tall as the screen (`100dvh`) and the button is `position: fixed`. `true` = as tall as its parent and the button is positioned inside it (catalog demo). |
| `auto-return` | `bool`, `false` | `true` = when this phone screen is not the user's own choice (the phone view was chosen automatically: cookie `ui_auto`, set by `x-dl::phone-offer auto`; or the page was opened directly while `Device::view` says desktop) and the screen is no longer phone-sized, the Menu sheet form posts `ui=desktop` (+ `auto=1`) by itself through `device-action`, so a widened window returns to the desktop view (at most once per 10 seconds). Needs `device-action`. A choice the user made (cookie `ui=phone` without `ui_auto`) and a real phone (guessed `phone`) are never touched. |
| `install-url` | `?string`, `null` | Address of the PWA install page (for example `/pwa/?pasang`, which opens the install screen directly instead of the login screen). Set = the Menu sheet gets a group "App" / "Aplikasi" with a row "Install app" / "Pasang aplikasi" (icon `fa-mobile-screen`) that opens it, after `links` and before Desktop view. `null` (default) = no row, nothing changes. The install dialog (Android) and the iPhone instructions live on that page. |
| `logout-top` | `bool`, `false` | `true` = Sign out is an icon button (power symbol, danger outline, 44 px) at the right of the name row at the very top of the Menu sheet, and the "Sign out" button at the bottom of the sheet is not drawn. The first tap asks "Sign out of the application?" (`data-dl-confirm`), so a stray tap does not log the user out. Needs `logout-action`. `false` (default) = the button stays at the bottom. |

## Behavior

### States and focus

- Tapping the button calls `next()`: A to B (only when the page has actions), B to C (item Menu), C to A; without actions A to C. A new screen (or the frame finishing a load) always starts at A. The button is hidden in B and C.
- The actions are read from the page by `items()`: a form (`.dl-form__acts .dl-btn`), a screen's own buttons (`[data-phone-act]`, value `primary` = the accent item), a grid (`[data-g-search]`, `[data-g-new]`, the filter fields, `[data-g-col]`, `[data-g-expmenu]`; "Search" shows the grid search field at the top of the page and focuses it), a report / print page (`[data-p-print]`, `[data-p-paper]`, `[data-p-group]`, `[data-p-bw]`). The form actions are ordered Save, Undo, Back, New, then the rest. The page elements stay in the page (hidden / moved off screen) and the panel mirrors their state and clicks them, so the page's own behaviour keeps working. A report sheet is as wide as the paper; the shell shrinks it to the screen width (CSS variable `--dl-phone-fit`, screen only; printing is not scaled).
- Focus: entering B or C focuses the first enabled item; A returns focus to the button. Escape goes back to A.
- Auto-hide: scrolling the content (ignored for 0.5 s after opening), a tap outside, a choice, or 6 s without touch closes B and C; it waits while text is being typed in the page toolbar, and B is never closed by itself while a form is dirty (`form.is-dirty`).
- Motion: the bar slides in for 0.2 s; with `prefers-reduced-motion: reduce` there is no animation.

### Layout

- Flex column: content only (the only scrolling area, `overflow-y:auto`; its bottom padding includes `env(safe-area-inset-bottom)`). With a slot the title row is the first element of the scrolling content; with `frame` the title row is a fixed row above the iframe. Nothing is stuck to the bottom.
- Wide tables must not widen the page: wrap them in an element with the class `dl-phone__scroll` (scrolls sideways inside). A table in the frame scrolls inside its own page.

### Device choice

`Devlib\Device::view` gives `phone` or `desktop` (`Device::os` gives `android`, `ios` or `other`) (cookie first, then `Sec-CH-UA-Mobile`, then `User-Agent`). The project decides which view/shell to render from it. The shell itself never redirects.

## Data and events

- Server to browser: only markup. Browser to server: links and the two forms in the panel (`device-action`, `logout-action`).
- `el.dlComponent` is the `PhoneShell` instance (`toggle(open)` opens or closes the panel). No `dl:*` events.
- Hooks: `[data-dl-phone]` (attribute `data-state` = A, B or C), `[data-dl-phone-menu]` (the button), `[data-dl-phone-bar]`, `[data-dl-phone-acts]`, `[data-dl-phone-main]`, `[data-dl-phone-drop]`, `[data-dl-phone-more]`, `[data-dl-phone-list]`, `[data-dl-phone-close]`; JS class `PhoneShell`. Classes on the framed page: `dl-phone-shelled`, `dl-phone-search`, `dl-phone-print`.

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
