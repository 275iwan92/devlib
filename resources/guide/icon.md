## Overview

`<x-dl::icon>` draws one standard icon. The screen names the **meaning** of the icon (`save`, `delete`, `print`, ...), never the Font Awesome class; the meaning-to-class table lives in one place, `Devlib\Icon::MAP` (`src/Icon.php`), so changing the glyph there changes every button and screen that uses it. The component is a single `<i>` element, Font Awesome 6 solid.

Use it for a decorative or labelled glyph inside text, a menu row or a card heading. Pick another object when you need:

- a clickable icon: `x-dl::icon-button` (square button or link) or `x-dl::button` with `icon` / `preset`;
- a busy indicator: `x-dl::spinner`;
- a coloured status word: `x-dl::badge`.

### Generated markup

```html
<!-- without label: decorative -->
<i class="fa-solid fa-floppy-disk dl-icon" data-dl="x-dl::icon name=save" data-dl-class="Icon ← DevlibComponent" data-dl-src="—" aria-hidden="true"></i>

<!-- with label: tooltip + accessible name -->
<i class="fa-solid fa-trash-can dl-icon" data-dl="x-dl::icon name=delete" data-dl-class="Icon ← DevlibComponent" data-dl-src="—" title="Delete" aria-label="Delete"></i>
```

`dl-icon` is only a marker class: `devlib.css` has no rule for `.dl-icon`, so size and colour come from the surrounding text (`font-size`, `color`). The root also carries `data-dl`, `data-dl-class` and `data-dl-src` (developer catalog / Technical Guide link).

## Props

Constructor of `Devlib\Components\Icon` (2 props; nothing is inherited from `DevlibComponent`).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `name` | `string`, `'info'` | The icon **meaning**. Looked up in `Devlib\Icon::MAP`; the result is written as the Font Awesome class next to `fa-solid`. A name that is not in the map is used as-is, so a raw class such as `fa-gear` or `fa-fire` also works (the `fa-` prefix is required; a bare `fire` draws nothing). Because `fa-solid` is hard-coded, only solid icons work; brand icons do not. Not translated. |
| `label` | `?string`, `null` | Accessible text. `null` (default): the icon is decorative and gets `aria-hidden="true"`. A string: written to both `title` (hover tooltip) and `aria-label`, and `aria-hidden` is not written. Translated through the dictionary (`label` is a standard text prop); wrap data values in `raw()`. |

Extra HTML attributes (`class`, `style`, `id`, `data-*`, `onclick`...) are merged onto the `<i>`; `class` is appended to the generated classes. `can="module.action"` is supported by the base class `DevlibComponent`: without the ability nothing is rendered.

### Standard meanings (`Devlib\Icon::MAP`)

| Group | Names |
|---|---|
| Document actions | `new`, `edit`, `open`, `view`, `save`, `delete`, `cancel`, `close`, `back`, `confirm`, `approve`, `reject`, `hold`, `submit`, `copy`, `undo`, `eraser`, `signature`, `card`, `print`, `pdf`, `image`, `link`, `qr`, `mail`, `ink-saver`, `download`, `upload`, `export`, `import`, `refresh`, `lock`, `unlock`, `activate`, `deactivate` |
| Grid and navigation | `search`, `filter`, `sort`, `columns`, `group`, `density`, `expand-row`, `more`, `expand`, `collapse`, `next`, `prev`, `menu`, `home` |
| Status and info | `info`, `warning`, `error`, `success`, `help`, `support`, `notification`, `chat`, `ai`, `code` |
| Data and modules | `user`, `users`, `password`, `email`, `phone`, `calendar`, `time`, `money`, `file`, `attachment`, `settings`, `logout` |

The catalog page `basic-icons` renders the whole map live.

## Behavior

- Static: no state, no script, no animation, no theme rule of its own. It inherits `color` and `font-size` from its parent, so it follows the theme and the font scale of the element it sits in.
- Language: only `label` is translated. The glyph itself is language-neutral.
- Accessibility as implemented: a decorative icon is hidden from screen readers (`aria-hidden="true"`); a labelled icon gets `title` + `aria-label` but no `role`, so an `<i>` with only `aria-label` is not guaranteed to be announced. If the meaning matters (a status), print the text next to the icon instead of relying on `label` alone.
- Not interactive: the `<i>` is not focusable and has no keyboard behaviour. Use `x-dl::icon-button` for anything clickable.

## Data and events

- Input is only the two props. There is no slot: content between the tags is ignored because the view never prints `$slot`.
- Nothing is posted and no `dl:*` event is emitted. There is no JS twin and `el.dlComponent` is not set.
- Hooks: the marker class `.dl-icon` and `[data-dl="x-dl::icon name=..."]`.

## Rules and pitfalls

- Name the **meaning**, not the Font Awesome class (project rule, header of `Devlib\Icon`). Use a raw `fa-*` class only for a glyph with no standard meaning yet; if several screens need it, add it to `Devlib\Icon::MAP` so one edit changes them all.
- A typo in `name` fails silently: `name="sav"` writes the class `sav`, which draws nothing. Check the glyph on the `basic-icons` catalog page.
- `mail` is a paper plane (same glyph as `submit`); the envelope is `email`. `confirm`, `success` and `activate` share one glyph (`fa-circle-check`); `expand-row` and `next` share `fa-chevron-right`.
- Do not rely on `label` for important information (the tooltip shows on hover only).
- Do not put content between the tags: it is dropped.
- No lint rule covers this object.
- Note (not a bug): some components bypass the meaning map with raw names, for example `fa-rotate-right` in `src/Components/Sheet.php:118` and `fa-compress` / `fa-expand` in `resources/views/components/widget-board.blade.php:59-60`, because the map has no entry for those glyphs. It works through the pass-through but those icons are not changeable in one place.

## Example

Copied in shape from the catalog (`basic-icons`):

```blade
<x-dl::icon name="save" />
<x-dl::icon name="delete" label="Delete" />
<x-dl::icon name="fa-fire" style="color:#c62828" />

@foreach (\Devlib\Icon::MAP as $name => $fa)
    <div><x-dl::icon :name="$name" /> {{ $name }}</div>
@endforeach
```

An icon inside a heading; its size follows the text:

```blade
<h2 style="font-size:20px"><x-dl::icon name="calendar" /> Delivery schedule</h2>
```

Shown only to users who may print:

```blade
<x-dl::icon name="print" can="report.print" label="Print" />
```

## Related

- `x-dl::icon-button`: the clickable square version (uses the same meaning map).
- `x-dl::button`: labelled button; its `preset` / `icon` also resolve through `Devlib\Icon`.
- `x-dl::spinner`: animated busy indicator (instead of a spinning icon).
- `x-dl::badge`: coloured status word.
- `skills/devlib/components.md`: generated prop list.
