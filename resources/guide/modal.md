## Overview

`<x-dl::modal>` is the standard dialog window of DevLib: a fixed full-screen overlay that holds a centered card with an optional title bar, a scrollable body (default slot) and an optional footer (slot `footer`, normally the action buttons). It is closed by default and opened by a button that carries `data-dl-open="<id>"`, by `Devlib.open(id)`, by the grid New Record button (`new-modal` of `x-dl::grid`), or server-side with `open`.

Use it for a short task that interrupts the page and returns to it: a small form (new contact), a wizard step, a picker, a share/QR window, or a warning that must be seen (`tone`). Pick another object when:

- the content is a detail panel that should not block the page: `x-dl::drawer` (side panel, same open/close hooks);
- a yes/no question is enough: `Devlib.confirm({...})` or `data-dl-confirm="message"` on a button (library confirm box, no markup needed);
- a table of problems must pop up when the page loads: `x-dl::issue-modal` (a ready-made modal with a table);
- a short non-blocking message is enough: `Devlib.toast(...)`, `x-dl::alert` or `x-dl::announce`;
- the content is the page itself (a document form): `x-dl::form`.

Generated markup (root is always rendered, hidden by CSS until `is-on`):

```html
<div class="dl-mov [is-on]" data-dl="x-dl::modal id=<id>" data-dl-class="..." data-dl-src="..." data-dl-modal-id="<id>" role="dialog" aria-modal="true" aria-label="<title>">
  <div class="dl-modal dl-modal--<size> [dl-modal--tone dl-modal--<tone>]">
    <!-- only when title is not empty -->
    <div class="dl-modal__head"><h3>[<i class="fa-solid fa-..."></i> ]Title</h3><button type="button" class="dl-modal__x" data-dl-close-modal aria-label="Close">&times;</button></div>
    <div class="dl-modal__body"> ...default slot... </div>
    <!-- only when slot footer is given -->
    <div class="dl-modal__foot"> ...slot footer... </div>
  </div>
</div>
```

Extra HTML attributes (`class`, `style`, `data-*`) are merged on the root `div.dl-mov`. `can="module.action"` is read by the base class: without the ability nothing is rendered at all, so a `data-dl-open` button pointing at it silently does nothing.

## Props

Constructor of `Devlib\Components\Modal` (6 props). Blade names are kebab-case; use `:open="true"` for a non-string value.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `id` | `string`, `'modal'` | Written to `data-dl-modal-id` on the root. It is the key used by `data-dl-open="<id>"`, `Devlib.open(id)` and `Devlib.close(id)`. It is NOT written to the HTML `id` attribute. Several modals on one page need different ids: the lookup takes the first match in the document (`document.querySelector`), and the namespace is shared with `x-dl::drawer` (`data-dl-drawer-id`), so a modal and a drawer must not share an id either. The default `'modal'` is only safe for one modal per page. |
| `title` | `?string`, `null` | Heading text (`<h3>`) and also the `aria-label` of the root. Translated through the dictionary (standard text prop); wrap data values in `raw()`. When empty, the whole header is not rendered: no title, no icon, and no X button. Then the modal can be closed only by Esc, a click on the backdrop, or a footer/body element that has `data-dl-close-modal`. The `aria-label` is then `""`. |
| `size` | `string`, `'md'` | Adds `dl-modal--<size>`. Styled sizes: `sm` (max-width 400px), `md` (520px), `lg` (760px), `xl` (1000px). The card is always `width:100%` of the overlay minus 20px padding, so it shrinks on small screens. Any other string is written as a class without CSS and the card falls back to the base `max-width:430px` (narrower than `md`). |
| `open` | `bool`, `false` | `true` renders the root with `is-on` (visible when the page loads). Nothing else happens: no focus is moved and no event is fired. After the user closes it, only a script or a `data-dl-open` button can show it again; a reload shows it again. |
| `icon` | `?string`, `null` | Icon in front of the title, rendered as `<i class="fa-solid ...">` after `Devlib\Icon::fa()`. Accepts a standard meaning from the icon map (`user`, `qr`, `email`, `warning`, ...) or a Font Awesome class (`fa-triangle-exclamation`). Because the view hard-codes `fa-solid`, only solid-style icons work. Shown only when `title` is not empty. |
| `tone` | `?string`, `null` | Emphasis for a warning that must be seen: `danger`, `warn`, `info`, `success`. Adds `dl-modal--tone dl-modal--<tone>`: a 6px top border in the tone color and a tinted header with title and icon in that color (CSS variable `--dl-tone`). Any other non-empty string adds the two classes but has no tone color, so the accent color is used. `null` = plain header. The tone only colors the header, so it has no visual effect without `title`, except the top border. |

Slots: the default slot is the body (`dl-modal__body`, scrolls when taller than 90% of the viewport); `footer` (`<x-slot:footer>`) is the button row, right-aligned with a tinted background. A modal without `footer` has no footer element at all.

## Behavior

### Opening and closing (script `core.js`, one global listener for the whole page)

| Action | How |
|---|---|
| Open | Click on any element with `data-dl-open="<id>"` (the click is `preventDefault`ed), `Devlib.open('<id>')`, or the grid `new-modal`. All of them add `is-on` to the root. |
| Close | `[data-dl-close-modal]` inside the modal (the X in the header counts), Esc, a click on the dark backdrop itself (not on the card), `Devlib.close('<id>')`. All remove `is-on`. |
| Wizard step | One element with BOTH `data-dl-open="next"` and `data-dl-close-modal`: closes the modal it sits in, then opens the target. |
| Form inside | An `x-dl::form` without `action` that is saved inside a modal fires `dl:form-submit`, shows its preview toast and closes the nearest enclosing modal/drawer (`form.js:35`). |

- Esc closes ONE layer: the last `.is-on` modal or drawer in DOM order (not by z-index). Pressing it again closes the next one.
- When opened by `data-dl-open` or `Devlib.open`, after 50 ms the first `input, textarea, select` or `button` that is not a close button gets focus. Nothing is focused when none exists (for example a body with only text and a footer made of close buttons). A modal rendered with `open` gets no focus.
- The overlay is `position:fixed; inset:0; z-index:120` with `--dl-overlay` as backdrop. The card is a flex column with `max-height:90dvh`; only the body scrolls, so header and footer stay visible.
- The page behind is NOT locked: it can still scroll with the wheel, and there is no focus trap and no focus restore on close. Tab can move to controls behind the overlay.
- State is only the class `is-on`. Nothing is saved: a reload returns to the server-rendered state (`open`).

### Language and permissions

- `title` is translated; the X button `aria-label` uses the dictionary word `Close`. Slot content is NOT translated by DevLib: put final text there, or use DevLib components that translate their own props (`x-dl::button`, fields). Footer buttons should use the presets (`preset="save"`, `preset="cancel"`, `preset="close"`) so their labels follow the page language.
- The modal belongs to the dictionary group `messages`.
- `can="module.action"` hides the entire modal; enforce the same permission again on the server.

### Accessibility as implemented

- The root has `role="dialog"`, `aria-modal="true"` and `aria-label` (= title). There is no `aria-labelledby`, no focus trap, no return of focus to the opener, and the rest of the page is not marked inert.
- The X button is a real `<button>` with `aria-label`. Esc works from anywhere on the page.

## Data and events

- The modal itself submits nothing. Fields in its body post with the nearest ancestor `<form>`, or with their own `x-dl::form` placed inside the modal.
- Place the modal OUTSIDE other `<form>` elements when it has its own form (nested `<form>` is invalid HTML). If a modal is nested inside an `x-dl::form` on purpose, that form ignores the fields inside it for its "Required" check (`form.js:132`).
- No `dl:*` event is dispatched when a modal opens or closes. A project that needs one can listen to `click` on `[data-dl-open]` / `[data-dl-close-modal]` or observe the `is-on` class.

### Markup hooks and JS

| Hook | Where |
|---|---|
| `data-dl-modal-id="<id>"` | root; looked up by the open/close code and by `x-dl::grid` `new-modal`, `x-dl::doc-share`, `x-dl::photo-box`. |
| `.dl-mov`, `.is-on` | root overlay and its open state. |
| `data-dl-open="<id>"` | any trigger (button, link, menu item). |
| `data-dl-close-modal` | any closer; also the one used by the X button. |
| `.dl-modal`, `.dl-modal__head`, `.dl-modal__body`, `.dl-modal__foot`, `.dl-modal__x` | card parts. |

JS API (`window.Devlib`): `Devlib.open(id)`, `Devlib.close(id)`. Helpers in `Devlib.h`: `layer(id)` (the root element or `null`), `openLayer(id)`, `closeLayer(element)`. `Devlib.open` with an unknown id does nothing. The Modal has no `dlComponent` instance: its behavior is global delegation, so a modal added to the page later works without re-initialising.

## Rules and pitfalls

- Open it with `data-dl-open` on an `x-dl::button` (the attribute passes through to the `<button>`); do not write inline `onclick` or `<script>` for it (lint L002) and do not use a raw `<button>` in the footer (lint L001): use `x-dl::button`. Colors come from theme tokens (L003). There is no lint rule that is specific to the modal.
- Put a `data-dl-close-modal` button (Cancel or Close) in the footer, even when the header X exists, and always give `title` when the user has to close it with the mouse: without a title there is no X.
- Pass a unique `id` for every modal, including the ones generated inside loops or other components (`:id="$uid.'-mail'"`).
- Do not place the modal inside an element that is hidden (`display:none`, a closed tab pane, a collapsed section): the fixed overlay is hidden with its parent.
- `tone` is for warnings that must be noticed (shortage, locked period). Do not use it for ordinary dialogs; for a confirm question use `Devlib.confirm`.
- Use `raw()` for data in `title` (for example `'Customer · '.raw($no)`), so it is not translated or put in the dictionary.
- `open` is only for content the user must see on load. Closing is client-side only: nothing remembers that the user dismissed it.
- Validate again on the server: closing a modal or hiding a button does not protect an action.
- Code oddity (not fixed): the REMARK header of `src/Components/Modal.php:5` and the comment at line 15 (copied to `skills/devlib/components.md:577`) say the modal opens with `Devlib.modal.open(id)`; no such object exists. The real API is `Devlib.open(id)` / `Devlib.close(id)` (`resources/assets/js/core.js:873`).
- Code oddity (not fixed): `openLayer` (`resources/assets/js/core.js:395`) focuses the first `input,textarea,select,button...` in the modal, and `input` also matches `type="hidden"`. A modal whose first input is hidden (for example a CSRF or id field) therefore moves no focus.
- Code oddity (not fixed): `src/Components/Modal.php:36` writes `aria-label="{{ $title }}"` even when there is no title, giving `aria-label=""` on a `role="dialog"` element.

## Example

Taken from the catalog demo `modal-drawer` (fictional data).

```blade
<x-dl::button label="Buka modal" icon="new" data-dl-open="dm-kontak" />
<x-dl::button variant="danger" label="Modal tone danger" icon="fa-triangle-exclamation" data-dl-open="dm-tone" />

<x-dl::modal id="dm-kontak" title="Kontak Baru" icon="user">
    <x-dl::field.text name="nama" label="Nama" required />
    <x-dl::field.phone name="hp" label="No. HP" />
    <x-slot:footer>
        <x-dl::button preset="cancel" data-dl-close-modal />
        <x-dl::button preset="save" type="button" data-dl-close-modal onclick="Devlib.toast('Kontak tersimpan.', 'success')" />
    </x-slot:footer>
</x-dl::modal>

<x-dl::modal id="dm-tone" title="Material Shortage" icon="fa-triangle-exclamation" tone="danger">
    <p><b>3 materials are short.</b> Open Material Planning to create a Purchase Requisition.</p>
    <x-slot:footer><x-dl::button preset="close" data-dl-close-modal /></x-slot:footer>
</x-dl::modal>
```

Larger and visible on load (script API from the project JS file: `Devlib.open('pick-item')`, `Devlib.close('pick-item')`):

```blade
<x-dl::modal id="pick-item" title="Choose Item" size="lg" :open="true">
    <x-dl::field.text name="q" label="Search" />
    <x-slot:footer><x-dl::button preset="close" data-dl-close-modal /></x-slot:footer>
</x-dl::modal>
```

## Related

- `x-dl::issue-modal`: a modal that already contains a message and a problems table, open on load.
- `x-dl::drawer`: side panel with the same `data-dl-open` / `data-dl-close-modal` hooks.
- `x-dl::button`: openers and footer buttons (`preset="save|cancel|close"`).
- `x-dl::form`: a form without `action` inside a modal closes it after Save; `x-dl::grid` `new-modal` opens a modal as a wizard.
- `x-dl::doc-share`, `x-dl::photo-box`, `x-dl::greeting-card`, `x-dl::widget-board`, `x-dl::warehouse-desk`: library objects that build their own modals with this component.
- `Devlib.confirm`, `data-dl-confirm`, `Devlib.toast`: lighter alternatives for questions and notices.
