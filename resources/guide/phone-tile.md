## Overview

`<x-dl::phone-tile>` is a summary card for a phone home screen: an icon, a big number and a name, the whole card being one link (touch target at least 120 px high). Put them in `x-dl::phone-tiles` (two columns).

Pick another object when you need a full-width row with a name only (`x-dl::phone-link`) or a figure inside a desktop screen (`x-dl::stat-box`, `x-dl::kpi`).

### Generated markup

```html
<a class="dl-ptile dl-ptile--danger" data-dl="x-dl::phone-tile" href="/items"><i class="fa-solid fa-box"></i><strong>4</strong><span>Low stock</span></a>
```

Extra attributes (`class`, `id`, `data-*`) go on the root element; `can="module.action"` works as for every component.

## Props

Constructor of `Devlib\Components\PhoneTile` (5 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `label` | `string`, `''` | Name under the number (translated through the dictionary; wrap data values in `raw()`). |
| `value` | `string|int|float|null`, `null` | The big figure. `null` = no figure (icon and name only). Print it already formatted (the card does not format). |
| `icon` | `string`, `'fa-solid fa-circle'` | Font Awesome classes of the icon. |
| `url` | `string`, `'#'` | `href` of the card. |
| `tone` | `string`, `''` | `info`, `warn` or `danger` colours the card (class `dl-ptile--<tone>`); empty = plain. Chosen on the server; it never changes by itself. |

## Behavior

- The card is an ordinary link: a tap navigates; there is no JavaScript.
- The figure is 30 px (scaled by the font-scale setting), the name 14 px.

## Data and events

- No events, no `el.dlComponent`. Browser to server: only the navigation to `url`.

## Rules and pitfalls

- Use at most six to eight tiles per screen; each is a navigation target, not a status light.
- Tone is colour plus background only; say the state in the label too (for accessibility).
- Related lint rules: none.

## Example

```blade
<x-dl::phone-tiles>
    <x-dl::phone-tile label="Active items" :value="27" icon="fa-solid fa-boxes-stacked" :url="url('/items')" />
    <x-dl::phone-tile label="Low stock" :value="4" icon="fa-solid fa-triangle-exclamation" tone="danger" :url="url('/low')" />
</x-dl::phone-tiles>
```

## Related

- `x-dl::phone-tiles`: the two-column wrapper.
- `x-dl::phone-link`: full-width link row.
- `x-dl::phone-shell`: the frame these live in.
