## Overview

`<x-dl::phone-bar>` is a row of large buttons placed in the flow of the content of `x-dl::phone-shell`, for step screens (Previous / Next). It is NOT stuck to the bottom of the screen (nothing is: a bottom bar can collide with the Android navigation buttons); form buttons live in the actions row of the floating icon (state B).

### Generated markup

```html
<div class="dl-pbar" data-dl="x-dl::phone-bar" style="grid-template-columns:1fr 1.5fr">…buttons…</div>
```

Extra attributes (`class`, `id`, `data-*`) go on the root element; `can="module.action"` works as for every component.

## Props

Constructor of `Devlib\Components\PhoneBar` (1 prop).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `columns` | `string`, `''` | CSS `grid-template-columns` value, for example `1fr 1.5fr` to make the second button wider. Empty = equal columns, one per button. |

## Behavior

- A grid row with a 12 px top margin; buttons are at least 52 px high and do not wrap. It scrolls with the content.

## Data and events

- No events. The buttons inside are yours (`x-dl::button`, or buttons with your own `data-*` hooks).

## Rules and pitfalls

- Do not put more than three buttons; at 320 px each gets about 100 px.
- It is not a navigation bar; the main menus live in the Menu panel of `x-dl::phone-shell`.
- Related lint rules: none.

## Example

```blade
<x-dl::phone-bar columns="1fr 1.5fr">
    <x-dl::button variant="secondary" label="Back" />
    <x-dl::button label="Next" />
</x-dl::phone-bar>
```

## Related

- `x-dl::phone-shell`: the frame; `x-dl::button`.
