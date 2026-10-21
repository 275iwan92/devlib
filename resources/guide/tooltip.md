## Overview

`<x-dl::tooltip>` wraps any content (a button, a badge, an icon) and shows a small dark bubble with a short explanation while the mouse pointer is over it. It is pure CSS: no script, no event, no data.

Use it to explain an icon-only control, a shortcut, or a term, in one short line. Pick another object when the need is different:

- The `tooltip` prop of a field (`x-dl::field.*`) already draws the (i) bubble next to the label; do not wrap fields in this object.
- `x-dl::hint` or the field `hint` prop for help that must always be visible.
- `x-dl::alert` for information the user must not miss.
- `x-dl::modal` or a popover for long or interactive content (a tooltip cannot be clicked or selected).

Generated markup:

```html
<span class="dl-tooltip dl-tooltip--top" data-dl="x-dl::tooltip" data-dl-class="Tooltip" data-dl-src="—">
  {slot content}<span class="dl-tooltip__text">Tooltip text</span>
</span>
```

## Props

Constructor of `Devlib\Components\Tooltip` (2 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `text` | `string`, `''` | The bubble text. Escaped. Translated through the dictionary (`text` is in `TEXT_PROPS`; the object belongs to the `messages` dictionary group); wrap data values in `raw()`. Written on one line (`white-space:nowrap`), so keep it short. An empty text still renders an empty (invisible-looking) bubble. |
| `position` | `string`, `'top'` | Where the bubble appears: `top` (above, the default) or `bottom` (below). The value becomes the class `dl-tooltip--<position>`; only `bottom` has a rule. Any other value (`left`, `right`, `auto`, a typo) is written but has no CSS, so the bubble shows above, exactly like `top`. |

The wrapped content is the slot (not translated, not escaped: it is Blade you write). Extra HTML attributes (`id`, `class`, `style`, `data-*`) are merged onto the root `span`. The attribute `can="module.action"` is supported (base class `DevlibComponent`): without the ability nothing is rendered, including the wrapped content.

## Behavior

- **Trigger:** `:hover` on the root `.dl-tooltip`. The bubble goes from `visibility:hidden; opacity:0` to visible with a 0.15s opacity fade. There is no delay and no touch or long-press behavior.
- **Keyboard:** none. The bubble is not shown on focus (there is no `:focus-within` rule), so keyboard users never see it.
- **Position and size:** the root is `position:relative; display:inline-flex`. The bubble is absolute, centered horizontally (`left:50%` with `translateX(-50%)`), 6px above (`top`) or below (`bottom`) the content, `z-index:100`, font 11px times the user font scale, padding `4px 8px`, small radius, one line, no wrapping.
- **Look:** dark background (token `--dl-f-ink`) with white text (`--dl-white`); no per-theme rule exists in `devlib.css`, so it follows whatever those tokens are in the active theme.
- **Pointer:** the bubble has `pointer-events:none`, so moving the pointer onto it does not keep it open and the text cannot be selected.
- **Clipping:** the bubble is part of the layout box of its parent; an ancestor with `overflow:hidden` or `overflow:auto` (scrolling table cells, modal bodies, the edges of a card) clips it, and it may run outside the viewport near the screen edges (no collision detection).
- **Accessibility as implemented:** no `role="tooltip"`, no `aria-describedby`. The hidden bubble uses `visibility:hidden`, so it is not read by screen readers while closed, and it is never opened by keyboard. Do not put information only in a tooltip; give icon-only buttons an `aria-label` or a visible label as well.
- **Permissions:** `can="module.action"` hides the whole thing, wrapped content included.

## Data and events

- Takes a short string and a slot. No arrays.
- No JS class, so `el.dlComponent` is not set; no `dl:*` event; nothing is posted.
- Markup hooks: `.dl-tooltip`, `.dl-tooltip--top|bottom`, `.dl-tooltip__text`. A script can change the text with `root.querySelector('.dl-tooltip__text').textContent = '...'`.

## Rules and pitfalls

- Wrap ONE element (or a few inline elements). The root is `inline-flex`, so block content inside is laid out as flex items.
- Only `top` and `bottom` work. Do not write `left` or `right`.
- Keep texts short and in one line; for more, use `x-dl::hint` or a modal.
- Use `raw()` for texts that carry data (a document number); plain English texts are translated automatically.
- Do not use it as the only way to learn what a control does (keyboard and touch users cannot open it).
- Do not use it for the (i) bubble of a field: use the field's `tooltip` prop.
- No lint rule exists for this object (`lint()` returns no findings).
- The demo text `Save dokumen (Ctrl+S)` only describes a shortcut; the object never binds a key. Wire shortcuts separately.
- Code oddity (not fixed): `src/Components/Tooltip.php` accepts any `position` string without validation, and `resources/assets/devlib.css:682` styles only `bottom`; an unsupported value fails silently.

## Example

```blade
{{-- Shape of the catalog demo --}}
<x-dl::tooltip text="Save the document (Ctrl+S)"><x-dl::button icon="save" label="Save" /></x-dl::tooltip>
<x-dl::tooltip text="Explanation below" position="bottom"><x-dl::badge label="Hover me" /></x-dl::tooltip>

{{-- On an info icon next to a checkbox label --}}
<label class="dl-choice">
    <input type="checkbox" name="direction" checked> Direction
    <x-dl::tooltip text="Paper grain direction, parallel to the sheet length."><i class="fa-regular fa-circle-question"></i></x-dl::tooltip>
</label>

{{-- Data value: not translated --}}
<x-dl::tooltip :text="raw('Created by '.$user->name)"><x-dl::badge label="Draft" /></x-dl::tooltip>
```

## Related

- `x-dl::field.text` and the other `x-dl::field.*`: the `tooltip` prop gives the (i) bubble next to the label.
- `x-dl::hint`: help text that is always visible.
- `x-dl::alert`: important messages the user must see.
- `x-dl::button`, `x-dl::badge`: the usual wrapped content.
- `x-dl::modal`: for long or interactive explanations.
- `skills/devlib/components.md` (generated prop list), `skills/devlib/object.md` (translated props, `can`).
