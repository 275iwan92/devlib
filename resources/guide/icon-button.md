## Overview

`<x-dl::icon-button>` is the square (30 x 30 px) icon-only button of DevLib: row actions in a grid, toolbars, calendar arrows, widget controls. The icon is given by **meaning** (`edit`, `delete`, `open`, ...; the same map as `x-dl::icon`, `Devlib\Icon::MAP`). It renders a `<button type="button">`, or an `<a>` when `href` is set, and it can ask for confirmation before the click is passed on (`confirm`).

Pick another object when:

- the action needs a visible text label: `x-dl::button` (with `preset` for the standard Save / New / Delete buttons);
- the icon is only decoration and not clickable: `x-dl::icon`;
- several actions share one button: `x-dl::menu-button` or `x-dl::split-button`.

### Generated markup

```html
<!-- default: button -->
<button type="button" class="dl-iconbtn dl-iconbtn--danger" data-dl="x-dl::icon-button icon=delete" data-dl-class="IconButton ← DevlibComponent" data-dl-src="—"
        title="Hapus" aria-label="Hapus" data-dl-confirm="Hapus data ini?"><i class="fa-solid fa-trash-can"></i></button>

<!-- with href: link, never has data-dl-confirm -->
<a href="/po/1" class="dl-iconbtn dl-iconbtn--default" data-dl="x-dl::icon-button icon=open" title="Buka" aria-label="Buka"><i class="fa-solid fa-up-right-from-square"></i></a>
```

`data-dl-confirm` is written only on the `<button>` branch and only when `confirm` is not empty.

## Props

Constructor of `Devlib\Components\IconButton` (5 props; nothing is inherited from `DevlibComponent`).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `icon` | `string`, `'edit'` | Icon meaning from `Devlib\Icon::MAP` (`edit`, `delete`, `open`, `print`, `more`, `prev`, `next`, `undo`, `refresh`, ...). A name that is not in the map is used as a raw Font Awesome class (`fa-xmark`, `fa-rotate-right`); the glyph is always `fa-solid`. Also shown in the developer tag (`x-dl::icon-button icon=<icon>`). Not translated. |
| `title` | `string`, `''` | Tooltip **and** accessible name: written to both `title` and `aria-label`. Translated through the dictionary (`title` is a standard text prop; the class belongs to the `buttons` dictionary group). Wrap data values in `raw()`. Always set it: the default `''` writes empty `title=""` and `aria-label=""`, which leaves an icon-only button without a name. |
| `variant` | `string`, `'default'` | Adds class `dl-iconbtn--<variant>`. Styled values: `danger` (red icon and border, red hover), `success` (green icon), `plain` (no border, no background; for toolbars and dense rows). `default` and any other string have no extra rule (accent-coloured icon, bordered). Not translated, not validated. |
| `href` | `?string`, `null` | When not empty the root becomes `<a href="...">` (navigation) instead of `<button>`. Escaped with `{{ }}`, not validated. With `href` the `confirm` prop is ignored (see Rules and pitfalls). |
| `confirm` | `?string`, `null` | Question asked before the click goes through. Written as `data-dl-confirm="<text>"` on the `<button>`; the global handler in `core.js` shows the library confirm modal. NOT translated by the component (it is not a standard text prop): write it in the final language. |

Extra HTML attributes (`id`, `class`, `disabled`, `data-*`, `onclick`, `data-dl-confirm-tone`...) are merged onto the root `<button>` / `<a>`; `class` is appended to the generated classes. `can="module.action"` is supported by the base class `DevlibComponent`: without the ability nothing is rendered. There is no `disabled` prop: pass the attribute (`disabled`, or `:disabled="$i === 0"` as `widget-board` does).

## Behavior

### Look

- Square 30 x 30 px, 1 px border, surface background, accent icon, theme radius; hover background `accent-soft` (`danger-soft` for `danger`). The font size follows the user font scale.
- Inside a grid action cell (`.dl-grid__act`) neighbouring buttons are spaced 4 px; in an entry grid (`.dl-grid--entry`) they shrink to 22 x 22 px, borderless and muted, turning accent on hover (the `success` and `danger` colours are kept).
- `:disabled` (button only): opacity .45, `not-allowed` cursor, no pointer events. An `<a>` has no disabled state.
- Dark mode and density come from the theme variables; the component has no theme code of its own.

### Click and confirmation

- A `<button type="button">` never submits a form by itself. Attach behaviour yourself: `onclick`, a screen-script `data-*` hook, or `data-dl-open="<modal id>"` for the library modals.
- With `confirm`: a capture-phase listener in `resources/assets/js/core.js` (around line 381) stops the click, opens the confirm modal, and on OK sets a temporary flag and calls `click()` again, so your `onclick` / hooks run only after the user agrees. Cancel: nothing happens.
- The modal tone is `danger` when the button is, or sits inside, a `.dl-iconbtn--danger` / `.dl-btn--danger`; otherwise `warn`. Override with the extra attribute `data-dl-confirm-tone="info"` (values used in the repo: `info`, `warn`).

### Language and permissions

Only `title` is translated. Icons are language-neutral. With `can` the whole button is removed when the ability is missing (no disabled placeholder).

### Accessibility as implemented

- `aria-label` is always written (same text as `title`); the inner `<i>` has no `aria-hidden` (it has no text, so it adds nothing for a screen reader).
- It is a real `<button>` / `<a>`, so it is in the Tab order and works with Enter and Space (button) or Enter (link).

## Data and events

- Input: only the props above. There is no slot: content between the tags is dropped because the view prints no `$slot`.
- Nothing is posted (`type="button"`, no `name` / `value`).
- No `dl:*` event. The confirm flow re-dispatches a normal `click`. There is no JS twin and `el.dlComponent` is not set.
- Hooks: `.dl-iconbtn`, `.dl-iconbtn--<variant>`, `[data-dl-confirm]`, `[data-dl-confirm-tone]`. Components that embed icon buttons mark them with their own attributes, for example `data-c-prev` / `data-c-next` (calendar), `data-s-undo` (sheet), `data-wb-act="left|right|narrower|wider|remove"` (widget board), `data-dl-track-replay` (delivery track).

## Rules and pitfalls

- Always give `title`: it is the only name of the button.
- Name the icon by meaning, not by Font Awesome class (project rule); a raw `fa-*` is the exception for glyphs that are not in `Devlib\Icon::MAP`.
- Use `confirm` for destructive or irreversible clicks (delete a row, toggle active) and pair it with `variant="danger"` so the modal tone and the colour match.
- Do not combine `href` and `confirm`: the anchor branch drops `confirm`. If a link really needs a question, pass `data-dl-confirm="..."` as an extra attribute (the global handler works on any element, then re-clicks the link) or use a `<button>` with `onclick`.
- `confirm` is not run through the dictionary and `title` is; keep both in the same language.
- `variant="primary"` writes `dl-iconbtn--primary`, which has no style. Use only `default`, `danger`, `success`, `plain`.
- For a text button use `x-dl::button`; do not widen an icon button with CSS.
- No lint rule covers this object.
- Code oddity (not fixed): `src/Components/IconButton.php:35` (the `href` branch) never writes `data-dl-confirm`, so `confirm` is silently ignored on a link.
- Code oddity (not fixed): `confirm` is missing from `DevlibComponent::TEXT_PROPS` (`src/Components/DevlibComponent.php:80`), so it is the one visible text of this button that is not translated.
- Note: JS-built screens write the same button by hand (`resources/assets/js/components/grid.js:297-299`, `lines.js:163`, `member-list.js:73`) with `class="dl-iconbtn ..."`, outside this component. Keep the class contract (`dl-iconbtn`, `--danger`, `--success`, `--plain`) stable.

## Example

Copied in shape from the catalog (`icon-button`, `confirm`):

```blade
<x-dl::icon-button icon="open" title="Buka (klik 2x baris)" />
<x-dl::icon-button icon="edit" title="Edit" />
<x-dl::icon-button icon="confirm" title="Confirm" variant="success" />
<x-dl::icon-button icon="delete" title="Hapus" variant="danger" confirm="Hapus data ini?" />
<x-dl::icon-button icon="print" title="Cetak" variant="plain" />
<x-dl::icon-button icon="more" title="Aksi lain" variant="plain" />
```

Delete with a confirmation; the handler runs only after OK:

```blade
<x-dl::icon-button icon="delete" title="Hapus baris" variant="danger" confirm="Hapus baris ini?" onclick="Devlib.toast('Baris dihapus.')" />
```

Link, disabled state and permission:

```blade
<x-dl::icon-button icon="open" title="Open PO" href="{{ url('/po/1') }}" />
<x-dl::icon-button icon="prev" title="Move earlier" :disabled="$i === 0" />
<x-dl::icon-button icon="edit" title="Edit" can="po.edit" />
```

## Related

- `x-dl::icon`: the non-clickable glyph; its entry has the full meaning table.
- `x-dl::button`: labelled button and the standard presets (Save, New, Delete...).
- `x-dl::menu-button`, `x-dl::split-button`: one button that opens several actions.
- `x-dl::grid`: its action column (`.dl-grid__act`) places icon buttons.
- `x-dl::modal`: `data-dl-open` on an icon button opens a modal.
- `skills/devlib/components.md`: generated prop list.
