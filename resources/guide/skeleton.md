## Overview

`<x-dl::skeleton>` is a loading placeholder: grey bars with a moving light band (shimmer) that have the rough shape of the content that is on its way (text lines, a table, a card, a form). It is static markup with no script and no data: it shows until YOUR code replaces it with the real content.

Use it where content takes noticeable time to appear and the layout would otherwise jump: a card or grid filled after a fetch, a section loaded lazily.

Pick another object when:

- an action is running (saving, posting) rather than content loading: `x-dl::spinner` (use `overlay` to cover a card or grid while it works);
- there is nothing to show and the user needs a next step: `x-dl::empty-state`;
- the result is an error or a warning: `x-dl::alert`;
- progress is measurable: `x-dl::progress`.

Generated markup (root also has `data-dl`, `data-dl-class`, `data-dl-src`; extra attributes and `class` go on the root; `can="module.action"` removes it when the ability is missing). The root is always:

```html
<div class="dl-skel dl-skel--<type>" aria-busy="true" aria-label="Loading...">
  ...bars, depending on type...
</div>
```

Contents per `type`:

| `type` | Inside the root |
|---|---|
| `text` (default) | `lines` x `<span class="dl-skel__bar" style="width:100%">`; the last one is `60%` wide. |
| `table` | `rows + 1` x `<div class="dl-skel__row">` (the first is the header row, bars with `is-head`), each with `cols` x `<span class="dl-skel__bar">`. |
| `card` | `<div class="dl-skel__card">`: a round `dl-skel__circle` (44px, avatar) and three bars of width 70%, 90%, 50%. |
| `form` | `lines` x `<div class="dl-skel__field">`: a label bar (30% wide) and an input-height bar (`is-input`, 30px). |

## Props

Constructor of `Devlib\Components\Skeleton` (4 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `type` | `string`, `'text'` | Shape: `text`, `table`, `card` or `form`. Written into the class `dl-skel--<type>` (the modifier classes have no style of their own; the layout comes from the inner elements). Any other value falls back to the `text` layout while keeping its own modifier class. |
| `lines` | `int`, `3` | Number of bars for `text`, number of label+input pairs for `form`. Ignored by `table` and `card`. `0` renders an empty box. Bind as an integer: `:lines="2"`. |
| `rows` | `int`, `5` | Number of BODY rows for `table` (one extra header row is always added, so the table has `rows + 1` rows). Ignored by the other types. Use `:rows="4"`. |
| `cols` | `int`, `4` | Number of bars (columns) per row for `table`. Rows are a grid with `repeat(auto-fit, minmax(60px, 1fr))` and a 14px gap, so with many columns on a narrow parent they wrap into more lines. Ignored by the other types. |

The widths of the bars are fixed in the view (see the table above); they cannot be configured.

## Behavior

- The shimmer is a CSS animation (`@keyframes dl-shimmer`, 1.3s, linear, infinite) on `.dl-skel__bar` and `.dl-skel__circle`; colors come from the theme tokens (`--dl-chip`, `--dl-border-soft`), so it follows light/dark themes. Bars are 12px high (head bars 14px, `is-input` 30px); these are fixed pixel sizes, so the skeleton does not grow with the user's font-scale setting.
- Nothing removes the skeleton. Typical patterns: render it in a `@if ($loading)` / `@else` on the server (for example a lazy include), or put it in the container and replace the container's content from your own script when the data arrives (`el.replaceWith(...)`, `innerHTML`).
- It takes the width of its parent (`display:flex; flex-direction:column; gap:8px`). Wrap it in a `x-dl::card` with the same title as the real content so the page does not shift.
- Accessibility as implemented: `aria-busy="true"` and `aria-label` with the text `Loading...` (translated through the dictionary, for example `Memuat...`). The `div` has no role, so many screen readers do not announce the label; if announcement is needed, put the skeleton inside a live region of your own.
- Language: only the `aria-label` text exists. It is translated at render.
- Permissions: `can="module.action"` hides it.
- Dictionary group: `messages` (with `x-dl::empty-state`, `x-dl::spinner`, `x-dl::alert`).

## Data and events

Nothing is loaded, posted or emitted. No `dl:*` events, no JS class, no `dlComponent`. The only hooks are the classes `dl-skel`, `dl-skel__bar`, `dl-skel__row`, `dl-skel__card`, `dl-skel__circle`, `dl-skel__field`, the `is-head` / `is-input` modifiers and `aria-busy`.

## Rules and pitfalls

- Match the skeleton to the real content: `table` with `:cols` equal to the real number of columns and `:rows` close to a normal page; `form` with `:lines` equal to the number of fields. A skeleton that is much shorter than the content makes the page jump when it is replaced.
- `rows` counts body rows only; the header row is extra (`:rows="4"` shows 5 rows).
- Bind `lines`, `rows` and `cols` as numbers (`:rows="4"`).
- Do not leave a skeleton on screen when loading fails: replace it with an error (`x-dl::alert`) or an empty state. It never times out by itself.
- Do not use it for short actions (button clicks, saves); use the `x-dl::spinner`.
- The animation does not stop for users who prefer reduced motion.
- This repository only uses the object on its catalog demo page; the replacement logic always belongs to the screen.
- Code oddity (not fixed): `resources/assets/devlib.css:923,928` run an infinite shimmer animation with no `prefers-reduced-motion` override, unlike other animated objects in the same file (for example `devlib.css:2630`, `3488`).

## Example

Catalog demo `skeleton` (fictional data):

```blade
<x-dl::card title="Tabel sedang dimuat"><x-dl::skeleton type="table" :rows="4" :cols="5" /></x-dl::card>
<x-dl::form.row>
    <x-dl::card title="Kartu"><x-dl::skeleton type="card" /></x-dl::card>
    <x-dl::card title="Form"><x-dl::skeleton type="form" :lines="2" /></x-dl::card>
</x-dl::form.row>
```

Text placeholder, and the skeleton replaced by the real content on the server side:

```blade
<x-dl::skeleton :lines="4" />

@if ($loading)
    <x-dl::skeleton type="table" :rows="6" :cols="4" />
@else
    <x-dl::grid id="orders" :rows="$orders" row-key="no" :columns="$columns" />
@endif
```

## Related

- `x-dl::spinner`: "working" indicator, with `overlay` to cover a card or grid.
- `x-dl::empty-state`: what to show when loading finished and there is nothing.
- `x-dl::alert`: error or warning when loading failed.
- `x-dl::card`, `x-dl::form.row`: the usual containers around a skeleton.
- `x-dl::grid`, `x-dl::form`: the real content that the table and form skeletons stand in for.
- `skills/devlib/components.md`: generated prop list.
