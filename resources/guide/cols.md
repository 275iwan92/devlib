## Overview

`<x-dl::cols>` puts two or more blocks (cards, blocks, images, fields) side by side and lets them stack by themselves on narrow screens. It is a CSS grid with `repeat(auto-fit, minmax(320px, 1fr))` and a 12px gap: as many equal columns as fit at 320px minimum each, the rest wraps to the next row. There is no breakpoint and no column count to set.

Pick another object when:

- the layout is a wide main area plus a narrow side panel with a fixed width: `x-dl::split`;
- the columns are fields of one form row (2 to 4 fields in a line, with form spacing): `x-dl::form.row` / `x-dl::form.col`;
- a dashboard grid with 12 columns, sizes and edit mode: `x-dl::widget-board`;
- you only need one neutral wrapper: `x-dl::box`.

Generated markup:

```html
<div class="dl-cols" data-dl="x-dl::cols" data-dl-class="Cols ← DevlibComponent" data-dl-src="—"> ...children... </div>
```

Extra attributes (`id`, `class`, `style`, `data-*`) are merged onto the `div`; a `class` you pass is appended after `dl-cols`. `can="module.action"` is accepted: without the ability nothing is rendered.

## Props

`Devlib\Components\Cols` has no constructor and therefore no props (the Manifest lists none). There is no `cols` / `gap` / `min` prop: the number of columns follows from the width. To change the gap or the minimum width, override the rule in the project CSS (`.dl-cols { gap: ...; grid-template-columns: ... }`) or pass a `style` attribute (use theme tokens, not hex colors).

The only input is the default slot: each direct child becomes one grid cell.

## Behavior

- Number of columns = `floor((container width + 12px) / (320px + 12px))`, never less than 1, and never more than the number of children (`auto-fit` collapses empty tracks, so two cards in a very wide container are two wide columns, not two narrow ones plus empty space). Examples with equal children: container 640px or less is 1 column; about 652px and up fits 2; about 984px and up fits 3.
- Cells use the default grid alignment (`stretch`): children in the same row get the same height. A child that should not stretch needs `align-self: start`.
- Wrapped children flow into new rows in source order. There is no way to span two columns by prop; give the child `style="grid-column: 1 / -1"` when one block (for example a notes card) must take the whole row, or end the `x-dl::cols` and place that block after it (the catalog's object story page does this: three cards in `x-dl::cols`, then the last one outside).
- The grid works on the container width, not on the viewport: it also stacks inside a narrow modal, a side panel or one cell of `x-dl::split`.
- No JS, no state, no events (`el.dlComponent` is not set).
- Text: none. Permissions: `can` only.
- Accessibility: purely visual layout, DOM order is reading order.

## Data and events

- Server: nothing is posted; fields inside the children post as part of their form.
- Events: none.
- Markup hooks: `.dl-cols` only. Children are not wrapped or modified.

## Rules and pitfalls

- Children should be self-contained blocks (`x-dl::card`, `x-dl::block`, a field, an image). Do not use `x-dl::cols` to align label/value pairs: use `x-dl::form.row` or `x-dl::strip-kv`.
- The 320px minimum is fixed. A container narrower than 320px (very small phones inside padded pages, or a narrow side panel) makes the single column overflow horizontally, because `minmax(320px, 1fr)` has no lower escape.
- Grid items have `min-width: auto`, so an unbreakable wide child (a long code, a wide table that does not scroll itself) can push its column wider than the container. Give such a child `min-width: 0` and its own overflow handling.
- Do not nest several levels of `x-dl::cols` to get unequal widths; use `x-dl::split` for a main + side layout.
- Each direct child is a cell, so wrap multi-element groups in one element (`x-dl::box` or `x-dl::card`), otherwise each element becomes its own cell.
- No lint rule targets `x-dl::cols`. General `devlib:lint` rules L001 to L003 apply to the surrounding view.
- Code oddity (not fixed): `src/Components/Cols.php:5` says the blocks "otomatis bertumpuk di layar sempit", but the stacking depends only on the 320px minimum column width in `resources/assets/devlib.css:3797`; there is no media query, so below 320px of container width the column overflows instead of shrinking.

## Example

From the catalog demo `site/resources/views/demos/oo-story.blade.php` (three equal cards side by side, wrapping on narrow containers):

```blade
<x-dl::cols>
    @foreach (array_slice($story, 0, -1, true) as $heading => $paragraphs)
        <x-dl::card :title="$heading">
            @foreach ($paragraphs as $text)
                <p class="dl-note">{{ $text }}</p>
            @endforeach
        </x-dl::card>
    @endforeach
</x-dl::cols>
```

Static form of the same idea, with a block that spans the whole row (fictional data):

```blade
<x-dl::cols>
    <x-dl::card title="Delivery">Warehouse A, dock 3</x-dl::card>
    <x-dl::card title="Billing">Net 30 days</x-dl::card>
    <x-dl::card title="Notes" style="grid-column: 1 / -1">Handle with care.</x-dl::card>
</x-dl::cols>
```

## Related

- `x-dl::split`: main content plus a fixed-width side panel (breakpoint 900px).
- `x-dl::card`, `x-dl::block`: the usual children.
- `x-dl::form.row`, `x-dl::form.col`: columns of fields inside a form.
- `x-dl::widget-board`: 12-column dashboard grid with sizes.
- `x-dl::box`: neutral wrapper for a group that must be a single cell.
