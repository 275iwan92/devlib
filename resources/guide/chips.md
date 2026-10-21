## Overview

`<x-dl::chips>` is a tiny layout wrapper: a flex row that holds small items (normally `x-dl::badge`, or short text) and wraps to the next line when the width runs out. It has no data, no look of its own besides spacing, and no script.

Use it for a group of badges / tags / small status labels such as process tags, a kind label plus its special-process badges. Do NOT use it:

- for a single badge: put `x-dl::badge` directly.
- for toolbar buttons: `x-dl::action-bar`.
- for form field rows or columns: `x-dl::form.row` / `x-dl::cols`.
- for removable or selectable tags that the user edits: `x-dl::field.tags`.
- for the grid's own filter chips (`.dl-grid__chips`, built by the grid).

Generated markup (`as="div"`, default):

```html
<div class="dl-chips" data-dl="x-dl::chips" data-dl-class="Chips ← DevlibComponent" data-dl-src="—">
  <!-- slot content -->
</div>
```

With `as="span"` the same markup is a `<span class="dl-chips" ...>`. Extra HTML attributes (`class`, `id`, `style`, `data-*`) and `can="module.action"` go on the root; without the ability nothing is rendered.

## Props

Constructor of `Devlib\Components\Chips`: 1 prop, none inherited.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `as` | `string`, `'div'` | Root element. `span` renders a `<span>`; any other value (including a typo) renders a `<div>`. The element name is the only effect; the CSS class is the same. Use `span` when the root must be valid inside a `<p>` or another phrasing-content parent (a `<div>` there is invalid HTML). |

The content comes from the default slot. Nothing in the slot is translated or changed.

## Behavior

- CSS (`resources/assets/devlib.css`, `.dl-chips`): `display: flex; flex-wrap: wrap; gap: 6px; align-items: center`. So items sit in one row with 6 px gaps, wrap to new lines, and are centred vertically on each line (not horizontally). The gap is the same horizontally and vertically.
- The wrapper does not style children. A badge keeps its own tone, size and `nowrap`; plain text inside becomes a flex item (anonymous box).
- Theme, language, font scale: nothing of its own; children carry them.
- Accessibility as implemented: no `role`, no `aria-*`, no keyboard behavior. It is purely visual grouping; if the group needs a name for screen readers, add `role="group"` and `aria-label` as extra attributes (they are passed to the root).
- Permissions: `can="module.action"` hides the whole row.

## Data and events

- No data props, no arrays, no posting, no `dl:*` events, no JavaScript. `el.dlComponent` is not defined.
- The only markup hooks are the class `dl-chips` and `data-dl="x-dl::chips"`. Do not confuse it with `.dl-grid__chips` (the grid's filter chips) or the `chips` key of the assistant shell panel (`skills/devlib/assistant.md`), which are different things.

## Rules and pitfalls

- Chips are for static display. Interactive tag input belongs to `x-dl::field.tags`.
- Put only inline-sized small elements in it; large blocks work (they are flex items) but then use `x-dl::cols` or a grid layout.
- Data text inside the slot (names, codes) that goes through a component prop with a translated text must be wrapped in `raw()`; the slot itself is written by you and is not touched.
- `as` is not validated: `as="p"` silently becomes a `div`.
- Code oddity (not fixed): the REMARK (`src/Components/Chips.php:8-9`) says `span` is "inline di dalam kalimat" and the row is "rata tengah", but `.dl-chips` is `display:flex` for both elements (`resources/assets/devlib.css:3796`), so the `span` is a block-level box (it breaks the line, it is not inline in a sentence), and `align-items:center` centres vertically only; horizontal alignment is left (start).
- Code oddity (not fixed): the REMARK names `screens/inquiry-recalc` and `inquiry-revise` as users; they are project screens outside this repository, and no catalog demo or component view in DevLib uses `x-dl::chips` (verified with `git grep`).

## Example

No catalog demo uses the tag; these shapes follow the view and the REMARK (badge kind plus special-process tags):

```blade
<x-dl::chips>
    <x-dl::badge label="Revision" tone="accent" />
    <x-dl::badge label="Special size" tone="info" />
    <x-dl::badge label="Rush" tone="warn" icon="warning" />
    <x-dl::badge label="Needs approval" tone="violet" />
</x-dl::chips>
```

Driven by data, and an inline use inside a paragraph:

```blade
<x-dl::chips class="my-2">
    @foreach ($job->processes as $p)
        <x-dl::badge :label="raw($p->name)" :tone="$p->done ? 'success' : 'neutral'" />
    @endforeach
</x-dl::chips>

<p>Status: <x-dl::chips as="span"><x-dl::badge label="Draft" /></x-dl::chips></p>
```

## Related

- `x-dl::badge`: the usual content of a chips row.
- `x-dl::field.tags`: editable tag input (different job).
- `x-dl::action-bar`: row of action buttons.
- `x-dl::cols`, `x-dl::form.row`: column layouts for larger blocks.
- `skills/devlib/components.md` (generated prop list).
