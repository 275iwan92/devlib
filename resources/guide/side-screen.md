## Overview

`<x-dl::side-screen>` is a full-screen window for account-style screens that have many aspects (Help & support, Edit profile). It covers the whole viewport when opened; a Float button turns it into a movable, resizable window like a desktop OS window. Its content is either a page loaded into an iframe (`url`, loaded the first time it is opened) or the slot (normally an `x-dl::side-screen.layout`). A search box in the header filters the sections and cards of the layout inside.

It is the "wide" variant of the drawer family: the PHP class extends `Devlib\Components\Drawer`, but it renders its own markup and is opened by script, not by `data-dl-open` (see Rules and pitfalls).

Use it when the content needs a lot of room and several sections (a rail of sections on the left, a form or guide on the right). Pick another object when:

- the content is a detail or a short form next to the current screen: `x-dl::drawer`;
- it is a question or a confirmation: `x-dl::modal`;
- it is navigation: `x-dl::side-menu`;
- it is a record with its own URL and status buttons: `x-dl::form`.

The Shell (`x-dl::layer-shell`) renders one side screen per entry of its `drawers` prop (keys such as `help`, `edit`), each with `url` set, and opens it from the profile menu actions of the same key.

### Generated markup

```html
<section class="dl-ss [is-on] [is-float]" data-dl="x-dl::side-screen id=demo-help" data-dl-class="SideScreen ← Drawer ← DevlibComponent"
         data-dl-src="slot | layar <url>" data-dl-side-screen="demo-help" data-dl-ss-url="<url>" data-dl-ss-size="1140px 680px"
         role="dialog" aria-label="Title">
  <div class="dl-ss__head" data-dl-ss-drag>
    <span class="dl-ss__ico"><i class="fa-solid ..."></i></span>
    <div class="dl-ss__titles"><div class="dl-ss__title">Title</div><div class="dl-ss__sub">Subtitle</div></div>
    <label class="dl-ss__search"><i ...></i><input type="search" data-dl-ss-search ...><kbd>/</kbd></label>      <!-- only with search -->
    <span class="dl-ss__esc">Esc</span>
    <div class="dl-ss__win"><button data-dl-ss-float ...></button><button data-dl-close ...></button></div>
  </div>
  <div class="dl-ss__body"><iframe class="dl-ss__frame" name="dl-wide" title="Title"></iframe> | slot</div>
  <span class="dl-ss__rs dl-ss__rs--n|s|e|w|ne|nw|se|sw" data-dl-ss-rs="..."></span> ... (8 resize handles)
</section>
```

`data-dl-ss-url` exists only with `url`. `is-on` = open, `is-float` = floating mode.

## Props

Constructor of `Devlib\Components\SideScreen` (8 props). Blade names are kebab-case; use `:prop="expr"` for anything that is not a plain string. Booleans can be written as bare attributes. The parent props `side` and `maximize` of `x-dl::drawer` are not accepted here (the constructor always passes `side: 'right'` to the parent, and the view ignores it).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `id` | `string`, `'side'` | Name of the window. Written to `data-dl-side-screen`; the Shell finds a window by this id (the profile menu action key). Also the browser key of the saved window state, `dl.ss.<id>`. Give every side screen a unique id: the default `side` is shared by all that omit it. |
| `title` | `?string`, `null` | Header title, the `aria-label` of the section and the `title` of the iframe. Translated through the dictionary; wrap data values in `raw()`. |
| `subtitle` | `?string`, `null` | Small grey line under the title. Rendered only when not empty. Translated. |
| `icon` | `?string`, `null` | Icon in the rounded square before the title: a Font Awesome class (`fa-circle-question`) or a DevLib icon alias (`Devlib\Icon::fa`). The view hard-codes `fa-solid`. `null` = no icon square. |
| `url` | `?string`, `null` | Page shown in an iframe (`class="dl-ss__frame" name="dl-wide"`). Not translated. The iframe has no `src` until the first `open()`, which then sets it from `data-dl-ss-url`; later opens keep the loaded page (state is not reloaded). With `url` the slot is ignored. `null` = the slot is the content. Also changes `source()` shown by the catalog (`layar <url>` or `slot`). |
| `search` | `bool`, `true` | Shows the search box in the header (placeholder "Search in this panel…", key hint `/`). The text is passed to the layout inside (see Behavior). `false` removes the box. |
| `width` | `string`, `'1140px'` | Initial WIDTH when the window is floating, before the user resizes it. Not the width of the full-screen mode (that is always the viewport). Must start with a number in px: the script reads it with `parseFloat` after splitting on a space, so write `1140px`, not `80%` or `1140 px`. Minimum 640. |
| `height` | `string`, `'680px'` | Initial HEIGHT when floating, same rules as `width`. Minimum 400. |

`width` and `height` are written together to `data-dl-ss-size="<width> <height>"`. Once the user has floated, moved or resized the window, the saved values in the browser win over these two props (they are only the first-use size).

Also accepted, as on every DevLib object: `can="module.action"` (without the ability the window is not rendered) and any HTML attribute (`class`, `data-*`), merged into the root. Slot: the default slot (used only without `url`).

## Behavior

### Open and close

- Open from script: `document.querySelector('[data-dl-side-screen="<id>"]').dlComponent.open()`; also `close()`, `toggle()`, `isOpen()`. In the Shell, profile menu actions call these for you (opening one closes the other drawers; Help toggles). Opening adds `is-on` (display grid) and re-applies the saved geometry.
- Close: the X button (inside the Shell only, see oddity), the `Esc` key, or a page inside the iframe sending `parent.postMessage({dl: 'ss-close'}, '*')` (the iframe page may ask to close after Save; the script accepts it only from its own iframe window).
- `Esc` inside a layout page that runs in the iframe (`window.name === 'dl-wide'`) is turned into the same message, so `Esc` also works when the focus is inside the iframe.
- `/` (outside inputs) focuses the search box while the window is open.
- The window state persists while the page stays open; a closed window keeps its content in the DOM.

### Full mode and float mode

- Full (default): the window fills the viewport (`position:fixed; inset:0`, z-index 120, below `x-dl::drawer` at 150), grid rows `52px` header and the body.
- Float: toggled by the header button (icon `fa-window-restore` becomes `fa-expand`) or a double click on the header (not on input or button). The window becomes a rounded window of the saved size (initial `width` x `height`), centered the first time, always kept inside the viewport.
- Move: drag the header (only in float mode; not from input, button, link or `kbd`). Position snaps to the viewport edges within 14px.
- Resize: drag any of the 8 edge/corner handles (float only); minimum 640 x 400; a dashed outline and a size tag (`w × h`) show during the drag. While dragging, a transparent shield covers the body so the iframe does not swallow pointer events.
- The state `{mode, w, h, x, y}` is saved in `localStorage['dl.ss.<id>']` when the mode, position or size changes. A blocked storage just forgets it. On browser resize a floating window is clamped back into the viewport.

### Search

The header box sends its text to the content on every `input`: with `url`, as `postMessage({dl: 'ss-search', q})` to the iframe (the layout page inside, or any page that listens, such as the help map); without `url`, directly to each `[data-dl-ss-layout]` in the slot (`filter(q)`). The layout dims rail items whose name does not contain the text and hides the cards of the active section that do not (see `x-dl::side-screen.layout`).

### Language, text and accessibility

- Strings go through the dictionary (English in code): "Search in this panel…", "Float window", "Close". The literal `Esc` hint is not translated. `title`, `subtitle` are translated; `url`, `id` are not.
- The root is a `section` with `role="dialog"` and `aria-label`; there is no `aria-modal`, no focus trap and no focus return to the opener. The header buttons have `title` and `aria-label`; the search input has an `aria-label`. The iframe has a `title`.
- At viewport width 900px or less the search box and the `Esc` hint are hidden (CSS).
- Permissions: `can="module.action"` removes the window from the page. The page loaded in the iframe is a normal route and must check its own rights on the server.

## Data and events

- The component posts nothing and has no data. Forms inside the slot or the iframe page post as usual (in the iframe, the form's own page flow decides where the response goes).
- Custom events (bubble from the root `section`): `dl:side-screen:open` and `dl:side-screen:close`, both with `detail: {id}`. `dl:side-screen:section` comes from the layout, see `x-dl::side-screen.layout`.
- `postMessage` protocol (target origin `*`; both sides are your own pages): parent to iframe `{dl: 'ss-search', q}`; iframe to parent `{dl: 'ss-close'}`. The parent ignores messages from other windows; the iframe side does not check the sender of `ss-search`.
- JS: `el.dlComponent` is the `SideScreen` instance (`resources/assets/js/components/side-screen.js`) with `open()`, `close()`, `toggle()`, `isOpen()`, `toggleFloat()`, `search(q)`; properties `frame` (the iframe or null) and `state`.
- Hooks: `data-dl-side-screen`, `data-dl-ss-url`, `data-dl-ss-size`, `data-dl-ss-drag`, `data-dl-ss-search`, `data-dl-ss-float`, `data-dl-ss-rs`, `data-dl-close`, classes `dl-ss`, `is-on`, `is-float`, `is-moving`, `is-resizing`, `is-shield`.
- Title link: `guide-link.js` wraps `.dl-ss__title` with the link to this Technical Guide entry when the project configures a guide URL.

## Rules and pitfalls

- Always open it from script (`dlComponent.open()`) or from the Shell. `data-dl-open="<id>"` does NOT work: the shared layer script only looks for `data-dl-modal-id` and `data-dl-drawer-id`.
- Give each side screen a unique `id`; the same id shares the saved window state and confuses the Shell lookup.
- With `url`, the page inside runs in an iframe named `dl-wide`: build it with `x-dl::side-screen.layout` so section switching, search and `Esc` work, and do not put frame-busting scripts or `X-Frame-Options: DENY` on that route.
- The iframe loads once. To refresh its content after something changed, set `frame.src` yourself (the Shell does this for Help to pass the active topic).
- Content from the slot and from the iframe is rendered/loaded at page load or first open; keep heavy screens behind `url` so the first page load stays light.
- Keep `width` and `height` in px; they are only used when floating, and the user's saved size replaces them.
- Do not use it as a general dialog; it blocks the whole screen. Use `x-dl::modal` or `x-dl::drawer` for short tasks.
- No lint rule applies to this object (the `F1xx` rules check `x-dl::form` only).
- Code oddity (not fixed): the close button is `data-dl-close` (`src/Components/SideScreen.php:60`), and the only click handler for `[data-dl-close]` is bound by `x-dl::layer-shell` (`resources/assets/js/components/layer-shell.js:102`). Outside the Shell, for example in the catalog demo, the X button does nothing; the window closes with `Esc` or `dlComponent.close()`.
- Code oddity (not fixed): `/` is swallowed (`preventDefault`) whenever the window is open and focus is not in a field, even with `search="false"` or when the search box is hidden by CSS under 900px (`resources/assets/js/components/side-screen.js:24`).
- Code oddity (not fixed): `src/Components/SideScreen.php:16` calls the object a "Turunan Drawer" (Drawer descendant) that opens from the right, but at run time it shares nothing with the drawer: it renders its own markup, is not found by `data-dl-open`, does not accept the Drawer props `side` and `maximize`, and the `side: 'right'` it passes to the parent constructor (line 33) is never used by its view.

## Example

From the catalog demo `side-screen` (slot mode, fictional content):

```blade
<x-dl::button icon="fa-circle-question" label="Open Help & support"
    onclick="document.querySelector('[data-dl-side-screen=demo-help]').dlComponent.open()" />

<x-dl::side-screen id="demo-help" title="Help & support" subtitle="F1 · guides, document flows and tickets" icon="fa-circle-question">
    <x-dl::side-screen.layout :sections="['topics' => ['Help Topics', 'fa-life-ring', 148], 'tickets' => ['Tickets', 'fa-ticket', 2]]" active="topics"
        :hints="['topics' => 'Every screen has its help.', 'tickets' => 'Send a ticket with screenshots; the reply appears here.']">
        <x-slot:topics><x-dl::card title="Help Topics" icon="fa-life-ring">Grid Help Center (Part 3: Grid).</x-dl::card></x-slot:topics>
        <x-slot:tickets>
            <x-dl::card title="Send a Ticket" icon="fa-paper-plane">
                <x-dl::field.text name="description" label="Problem description" multiline />
            </x-dl::card>
            <x-dl::side-screen.foot hint="The developer team answers in Setup > Support & Ticket">
                <x-dl::button icon="fa-paper-plane" label="Send ticket" />
            </x-dl::side-screen.foot>
        </x-slot:tickets>
    </x-dl::side-screen.layout>
</x-dl::side-screen>
```

As the Shell uses it (iframe mode; one per entry of the `drawers` prop of `x-dl::layer-shell`):

```blade
<x-dl::side-screen id="help" title="Help & support" icon="fa-circle-question" url="/help" />
```

## Related

- `x-dl::side-screen.layout`: the content: vertical section rail, sticky section title, one slot per section.
- `x-dl::side-screen.foot`: the sticky action row at the bottom of a section.
- `x-dl::layer-shell`: the Shell that renders side screens from its `drawers` prop and opens them from the profile menu.
- `x-dl::drawer`: the narrow side panel (opened with `data-dl-open`); `x-dl::modal`: the centered dialog.
- `x-dl::card`, `x-dl::table`, `x-dl::flow`: typical section content.
- `x-dl::breadcrumb`: used by the layout `crumbs`.
- `skills/devlib/help.md`: the Help (UG and TG) that the Help window shows.
