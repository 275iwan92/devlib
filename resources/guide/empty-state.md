## Overview

`<x-dl::empty-state>` is the standard "nothing here" panel: a round icon, a bold title, an optional explanation and, in the slot, the action buttons that fix the situation (for example `New`). It is centered text on the page background, with no border of its own, so it is normally placed inside a card, a grid area or a widget.

Use it when a list, board, map or widget has no data: first use ("No Purchase Order yet" + a New button), an empty search result, a filtered-out dashboard widget, a missing prerequisite ("No warehouse yet" + a link to Buildings).

Pick another object when:

- the content is still loading: `x-dl::skeleton` (or `x-dl::spinner` for a running action);
- something went wrong or the user must be warned: `x-dl::alert`; a whole failed page: `x-dl::error-page`;
- you only need one line of text for an empty table: the `empty` prop of `x-dl::table`; `x-dl::grid` and `x-dl::gantt` already draw their own empty message (they build the same `dl-empty` markup in JavaScript), so do not add this tag inside them;
- the user must confirm or choose something: `x-dl::modal`.

Generated markup (root also has `data-dl`, `data-dl-class`, `data-dl-src`; extra attributes and `class` go on the root; `can="module.action"` removes it when the ability is missing):

```html
<div class="dl-empty" data-dl="x-dl::empty-state" ...>
  <span class="dl-empty__icon"><i class="fa-solid fa-file-lines"></i></span>
  <b class="dl-empty__title">No data yet</b>
  <p class="dl-empty__msg">message</p>                  <!-- only when message is not empty -->
  <div class="dl-empty__actions">slot</div>             <!-- only when the slot is not blank -->
</div>
```

## Props

Constructor of `Devlib\Components\EmptyState` (3 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `title` | `string`, `'No data yet'` | Bold headline (`<b class="dl-empty__title">`, 15px). Translated through the dictionary (the default text becomes `Belum ada data` in Indonesian). Wrap data values in `raw()`: `:title="Lang::raw($d['title'])"`. Say what is empty ("No warehouse yet"), not an error. |
| `message` | `?string`, `null` | Explanation under the title, max width 380px, centered. Printed only when truthy (`null`, `''` and `'0'` print nothing). Translated. Use it for the reason and the next step ("PO is created from approved PR allocation. Start by creating a new PO."). |
| `icon` | `string`, `'file'` | Icon inside the 56px accent circle. A semantic name from `Devlib\Icon::MAP` (`file` = `fa-file-lines`, `search`, `notification`, `user`, ...) or a Font Awesome class with its `fa-` prefix (`fa-warehouse`, `fa-chart-simple`). A name that is neither in the map nor prefixed (`warehouse`) is printed as is and shows no icon. The view hard-codes `fa-solid`, so only solid-style icons work. Never empty: the circle is always drawn. |

Slot: the action area. Put `x-dl::button` (or links) there; the buttons are laid out in a centered row with an 8px gap. A slot that is empty or only whitespace removes the whole actions `div`.

## Behavior

- Static markup, no JavaScript of its own. It takes the width of the parent, centers its content (`flex` column, `align-items:center`, `text-align:center`, `padding:28px 16px`, 6px gap) and uses the muted text color; the icon circle uses the accent and soft-accent tokens, so it follows the theme.
- Language: `title` and `message` are translated at render (dictionary group `messages`); button labels in the slot follow the button rules. `icon` is not text.
- Permissions: `can="module.action"` on the tag hides everything, which is the way to hide an empty state from users who may not see the list at all. A button inside the slot can carry its own `can`, so a user without create rights sees the explanation without the New button.
- Accessibility as implemented: plain `div`/`span`/`b`/`p`; the title is NOT a heading element, there is no `role="status"` and no `aria-live`. Screen readers read it as ordinary text. If it replaces content after an action (a filter), announce the change yourself.
- Not interactive itself; the only focusable elements are the buttons you put in the slot.

## Data and events

Nothing is loaded, posted or emitted. No `dl:*` events, no JS class, no `dlComponent`. To show or hide it dynamically, render both states and toggle with your own script (`hidden`), or render the tag server-side only when the list is empty (`@if ($rows->isEmpty())`). The CSS classes (`dl-empty`, `dl-empty__icon`, `dl-empty__title`, `dl-empty__msg`, `dl-empty__actions`) are also used by the markup that `grid.js`, `approval.js` and `gantt.js` build for their own empty states, so the look is the same everywhere.

## Rules and pitfalls

- Always give the next step: either the action button in the slot or a `message` that says what to do. A bare "No data" with no way out is the pattern this object exists to avoid.
- Screens write English; the dictionary translates `title` and `message`. Put data (names, dates, counts) inside `raw()`, otherwise each value becomes a dictionary row.
- Use the `fa-` prefix for any icon that is not a semantic name of `Devlib\Icon::MAP`; otherwise the circle is blank.
- The default `title` ("No data yet") is only a fallback; set a specific one ("No Purchase Order yet", "No results").
- Do not use it as an error screen; an empty result and a failure need different wording and tone (`x-dl::alert`, `x-dl::error-page`).
- Do not place it inside `x-dl::grid` to cover "no rows": the grid draws its own message.
- `message` is plain text (escaped). For formatted help or links, put your own markup in the slot.
- No lint rule (`devlib:lint`) checks this object.
- The title is a `<b>` (`src/Components/EmptyState.php:34`), not a heading element, so it is not part of the page outline.

## Example

Catalog demo `empty-state` (fictional data): first use with an action, and an empty search result.

```blade
<x-dl::card title="Daftar Purchase Order">
    <x-dl::empty-state icon="file" title="Belum ada Purchase Order" message="PO dibuat dari alokasi PR yang sudah disetujui. Mulai dengan membuat PO baru.">
        <x-dl::button preset="new" />
    </x-dl::empty-state>
</x-dl::card>
<x-dl::card title="Hasil pencarian"><x-dl::empty-state icon="search" title="Tidak ada yang cocok" message="Coba kata kunci lain atau lepas filter." /></x-dl::card>
```

As the package itself uses it (English text, `fa-` icon, a button to the place that fixes it; `warehouse-desk`):

```blade
<x-dl::empty-state title="No warehouse yet" message="Add the first building, then its storage areas and bins." icon="fa-warehouse">
    <x-dl::button label="Buildings" icon="fa-building" :href="$d['buildingsUrl']" />
</x-dl::empty-state>
```

Title and message depending on the user's rights (as `widget-board` does):

```blade
<x-dl::empty-state title="No widgets on this board" :message="$canEdit ? 'Click Choose Widgets and tick the widgets you want to see.' : 'This dashboard has no widget your account may see.'" icon="fa-table-cells-large" />
```

## Related

- `x-dl::skeleton`: placeholder while content loads (before the empty state is known).
- `x-dl::spinner`: a running action.
- `x-dl::alert`, `x-dl::error-page`: errors and warnings.
- `x-dl::button`: the actions in the slot (`preset="new"`).
- `x-dl::card`: the usual container.
- `x-dl::table` (`empty` prop), `x-dl::grid`: have their own empty message.
- `x-dl::notification-list`, `x-dl::warehouse-desk`, `x-dl::widget-board`: DevLib objects that use this tag.
- `skills/devlib/components.md`: generated prop list.
