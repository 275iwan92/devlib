## Overview

`<x-dl::phone-bar>` is a bar of large buttons that sticks to the bottom of the content area of `x-dl::phone-shell` (above the tab bar) and stays visible while the content scrolls. Use it for step screens: Previous / Next, Save / Cancel.

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

- `position: sticky; bottom: -16px` with matching negative margins, so it sits flush at the bottom of the scrolling area inside the shell (the shell content has 16 px bottom padding). Buttons are at least 52 px high and do not wrap.
- It works only inside `x-dl::phone-shell` (the scrolling content area); elsewhere it just sticks to its scroll parent.

## Data and events

- No events. The buttons inside are yours (`x-dl::button`, or buttons with your own `data-*` hooks).

## Rules and pitfalls

- Do not put more than three buttons; at 320 px each gets about 100 px.
- It is not a navigation bar; the five-button bottom bar is the tab bar of `x-dl::phone-shell`.
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
