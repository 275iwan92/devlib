## Overview

`<x-dl::phone-tiles>` is the two-column grid (12 px gap) that holds `x-dl::phone-tile` cards on a phone home screen.

### Generated markup

```html
<div class="dl-ptiles" data-dl="x-dl::phone-tiles">…tiles…</div>
```

Extra attributes (`class`, `id`, `data-*`) go on the root element; `can="module.action"` works as for every component.

## Props

Constructor of `Devlib\Components\PhoneTiles` (no props).

This object has no props; everything goes in the slot.

## Behavior

- Two equal columns that shrink with the screen width (`minmax(0, 1fr)`), so nothing overflows from 320 px up. An odd last tile takes one column.

## Data and events

- None: a layout wrapper.

## Rules and pitfalls

- Put only `x-dl::phone-tile` inside.
- Related lint rules: none.

## Example

```blade
<x-dl::phone-tiles>
    <x-dl::phone-tile label="Today" :value="0" />
</x-dl::phone-tiles>
```

## Related

- `x-dl::phone-tile`.
