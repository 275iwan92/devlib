## Overview

`<x-dl::drawer>` is a panel that slides in from the right (or left) edge of the screen over a dimmed backdrop. It holds a header (icon, title, optional Maximize button, close button), a scrolling body (the default slot) and an optional footer (the `footer` slot). It is closed until a button opens it; all the open/close behavior comes from the shared layer script in `resources/assets/js/core.js` (the same one that drives `x-dl::modal`), plus a small script for the Maximize button.

Use it for a detail view, a quick edit or a side form that should not leave the current screen (supplier detail, filter panel, a short form). Pick another object when:

- the content is a short question or confirmation that needs a decision: `x-dl::modal` (centered dialog);
- the screen is a full-screen, multi-section account area (Help, Profile): `x-dl::side-screen`;
- the panel is navigation (menu tree): `x-dl::side-menu`;
- the content is a whole record with its own URL, buttons and status: `x-dl::form` on its own page.

The Chat, AI Assistant and Notifications panels of the Shell are NOT `x-dl::drawer`: they are built by the Shell (`resources/views/components/shell/overlays.blade.php`, class `dl-drawer`). They share the Maximize script only.

### Generated markup

```html
<div class="dl-sdrawer dl-sdrawer--right" data-dl="x-dl::drawer id=dd-detail" data-dl-class="Drawer ← DevlibComponent" data-dl-src="—"
     data-dl-drawer-id="dd-detail" style="--dl-drawer-w:390px">
  <div class="dl-sdrawer__backdrop" data-dl-close-modal></div>
  <aside class="dl-sdrawer__panel" role="dialog" aria-label="Title">
    <div class="dl-dr-head">
      <i class="fa-solid fa-... dl-dr-ico"></i><span class="dl-dr-title">Title</span>
      <button type="button" class="dl-dr-x dl-dr-max" data-dl-dr-max ...><i class="fa-solid fa-expand"></i></button>   <!-- only with maximize -->
      <button type="button" class="dl-dr-x" data-dl-close-modal aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="dl-sdrawer__body">default slot</div>
    <div class="dl-modal__foot">footer slot</div>                                                                    <!-- only with the footer slot -->
  </aside>
</div>
```

Opened state is the class `is-on` on the root. The `id` prop is written ONLY as `data-dl-drawer-id`; the element gets no HTML `id`, so `document.getElementById` and `#id` anchors do not find it.

## Props

Constructor of `Devlib\Components\Drawer` (6 props). Blade names are kebab-case; use `:prop="expr"` for anything that is not a plain string. The boolean can be a bare attribute (`maximize`).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `id` | `string`, `'drawer'` | Name used to open it: `data-dl-open="<id>"` on any element, or `Devlib.h.openLayer('<id>')`. Written to `data-dl-drawer-id`, and (with `maximize`) to the browser key `dl.drmax.<id>`. The first element in the page with the id wins, so every drawer (and modal) on a page needs its own id; the default `drawer` collides as soon as there are two. |
| `title` | `?string`, `null` | Header title and the `aria-label` of the panel. Translated through the dictionary (standard text prop); wrap data values (a supplier name) in `raw()`. `null` gives an empty title and an empty `aria-label`. |
| `side` | `string`, `'right'` | `right` or `left`: the edge the panel slides from. Only `left` has its own style (`dl-sdrawer--left`); any other string is written as `dl-sdrawer--<value>` and behaves like `right`. |
| `width` | `string`, `'390px'` | Panel width as any CSS length (`480px`, `40vw`, `30rem`), written to the inline custom property `--dl-drawer-w`. The panel is also capped at `92vw` (`max-width`). With `maximize` and Maximize on, the width becomes the full viewport (`100vw`) until Restore. |
| `icon` | `?string`, `null` | Icon before the title: a Font Awesome class (`fa-circle-info`) or a DevLib icon alias such as `info` or `new` (`Devlib\Icon::fa`). The view hard-codes `fa-solid`, so only solid-style icons work. `null` = no icon. |
| `maximize` | `bool`, `false` | Adds a Maximize/Restore button in the header (`data-dl-dr-max`, `title` "Maximize" / "Restore", translated). Maximize makes the panel `100vw` wide, Restore returns to `width`. The mode is remembered per drawer id in the browser (see Behavior). |

Also accepted, as on every DevLib object: `can="module.action"` (without the ability the whole drawer is not rendered) and any HTML attribute (`class`, `data-*`), merged into the root `div`. Slots: the default slot (body) and `footer`.

## Behavior

### Opening and closing

- Open: a click on any element with `data-dl-open="<id>"` (document-level delegation, so it also works for elements added later), or `Devlib.h.openLayer('<id>')`. The script adds `is-on` to the root and, after 50 ms, focuses the first `input`, `textarea`, `select` or `button` (not a close button) in DOM order. With `maximize` on, the Maximize button comes before the body, so it takes the focus unless the drawer has no such button.
- Close: any element inside with `data-dl-close-modal` (the X button, the backdrop, your Cancel/Close button), the `Esc` key, or a successful Save of an `x-dl::form` without `action` that sits inside the drawer. `Esc` closes one layer per press: the last open modal/drawer in DOM order, not necessarily the one on top.
- One element with both `data-dl-open="<other-id>"` and `data-dl-close-modal` closes the layer it is in and opens the other (a wizard-like step between layers).
- Closing is a CSS state: the panel slides out (`transform .22s`) and becomes `visibility:hidden`, so its fields cannot be focused while closed. The content stays in the DOM: typed values survive closing and re-opening unless you reset them.
- z-index: the drawer is at 150 (`.dl-sdrawer`), above `x-dl::side-screen` (120).

### Maximize (`maximize`)

The script `DrawerMax` (`resources/assets/js/components/drawer-max.js`) reads `localStorage['dl.drmax.<id>']` at start (`1` = maximized) and applies it, so a user who maximized once finds the drawer maximized the next time even before clicking. Each click toggles the class `is-max` on the panel, swaps the icon (`fa-expand` / `fa-compress`) and the title, writes the new mode to the browser and fires `dl:drawer:max`. If browser storage is blocked the mode is just not remembered.

### Accessibility as implemented

- The panel is an `aside` with `role="dialog"` and `aria-label` = title; the close button has an `aria-label`. The Maximize button has only a `title`.
- There is no `aria-modal`, no focus trap and no return of focus to the opener when it closes. Tab can leave the open drawer into the dimmed page behind it.
- The backdrop is a plain `div` (clickable with the mouse only); keyboard users close with `Esc` or a close button.

### Language and permissions

Screen text is English in code: `title`, the button titles ("Maximize", "Restore", "Close") go through the dictionary. Slot content is yours. `can="module.action"` removes the drawer; the server must still check the right for whatever the drawer does.

### Forms inside

An `x-dl::form` without `action` inside the drawer behaves as a preview: Save shows a toast, fires `dl:form-submit` and closes the drawer. With `action` it posts normally. The footer slot is rendered outside the body, so a footer button must belong to the form by `form="<form-id>"` or be a `type="button"` with script.

## Data and events

- The drawer posts nothing and has no data of its own. Forms inside post as usual.
- Events: `dl:drawer:max` (`detail: {max: true|false}`), bubbles from the Maximize button; it also fires once when the script starts. Opening and closing fire NO event: observe the class `is-on` (MutationObserver) or wrap your own open/close button.
- Hooks: `data-dl-drawer-id` (root), `data-dl-open`, `data-dl-close-modal`, `data-dl-dr-max`, classes `dl-sdrawer`, `dl-sdrawer--left|right`, `dl-sdrawer__backdrop`, `dl-sdrawer__panel`, `dl-sdrawer__body`, `dl-dr-head`, `dl-modal__foot`, state classes `is-on` (root) and `is-max` (panel).
- JS helpers (`Devlib.h`, from `core.js`): `layer(id)` returns the element, `openLayer(id)`, `closeLayer(el)`. `el.dlComponent` is NOT set on the drawer root (no JS class); the Maximize button carries a `DrawerMax` instance.
- Storage: `localStorage['dl.drmax.<id>']` = `'1' | '0'` (only for drawers with `maximize`).

## Rules and pitfalls

- Give every drawer and modal on a page a unique `id`.
- Put long forms or full records in a form page, not in a drawer; the drawer is for detail and quick edits (project rule: a record with status and buttons lives in `x-dl::form` with its own URL).
- Do not use a drawer for a decision that blocks the user (confirm/delete): use `Devlib.confirm` or `x-dl::modal`.
- Do not rely on the drawer being reset: input values stay while the page is open.
- Pass `width` with a unit; a bare number such as `480` is not a valid CSS length, so the `width` declaration is dropped and the panel falls back to an automatic (content) width.
- Do not give the drawer a `style` attribute (see the oddity on `--dl-drawer-w`).
- Because there is no focus trap, never put sensitive actions behind a drawer assuming the page behind is inert; the server must authorize every action.
- No lint rule applies to this object (the `F1xx` rules check `x-dl::form` only).
- Code oddity (not fixed): the REMARK header and the comment in `src/Components/Drawer.php:5` and `:15`, and the generated list `skills/devlib/components.md:187`, say the drawer opens with `Devlib.drawer.open(id)`. No such function exists in `resources/assets/js`; the real API is `data-dl-open` or `Devlib.h.openLayer(id)` (`core.js:395`).
- Code oddity (not fixed): `src/Components/Drawer.php:36` prints `{{ $attributes->... }}` and then a second `style="--dl-drawer-w:..."`. If the caller passes `style="..."` the element has two `style` attributes, browsers keep the first, `--dl-drawer-w` is never set and the panel falls back to an automatic (content) width. Same pattern as `src/Components/Form/Row.php:32`.
- Code oddity (not fixed): `Esc` handler `core.js:402` closes the LAST open layer in DOM order (`.slice(-1)`), not the topmost by z-index or by opening order; with two layers open the wrong one can close.

## Example

From the catalog demo `modal-drawer` (fictional supplier):

```blade
<x-dl::button variant="secondary" label="Open drawer" icon="info" data-dl-open="dd-detail" />

<x-dl::drawer maximize id="dd-detail" title="Supplier detail" icon="info">
    <x-dl::field.display label="Name" value="PT Contoh Kertas Jaya" />
    <x-dl::field.display label="Payment terms" value="Net 30" />
    <x-dl::field.display label="PO total this year" :value="248500000" format="money" />
    <x-slot:footer><x-dl::button preset="close" data-dl-close-modal /></x-slot:footer>
</x-dl::drawer>
```

The same drawer from the left edge and wider: add `side="left" width="480px"` to the tag.

## Related

- `x-dl::modal`: centered dialog; same open/close attributes (`data-dl-open`, `data-dl-close-modal`) and the same `footer` slot.
- `x-dl::side-screen`: full-screen window (can float) for account-style screens; opened by script, not by `data-dl-open`.
- `x-dl::side-menu`: side panel for navigation.
- `x-dl::form`: a form without `action` inside a drawer closes it after Save; `x-dl::form.row`, `x-dl::form.col` lay out the fields.
- `x-dl::button`: the usual opener (`data-dl-open`) and closer (`data-dl-close-modal`, `preset="close"`).
- `x-dl::field.display`: read-only value lines for detail drawers.
- `x-dl::layer-shell`: the Shell owns the Chat, AI Assistant and Notifications panels, which reuse the Maximize script.
