## Overview

`<x-dl::phone-link>` is a large link row for phone lists (a module list, a reminder): an icon, a name and an optional small badge; the whole row is the touch target (64 px high).

Pick another object when you need a figure (`x-dl::phone-tile`) or a button in a form (`x-dl::button`).

### Generated markup

```html
<a class="dl-plink dl-plink--info" data-dl="x-dl::phone-link" href="/count"><i class="fa-solid fa-clipboard-check"></i><span>Stock count</span><em>2</em></a>
```

Extra attributes (`class`, `id`, `data-*`) go on the root element; `can="module.action"` works as for every component.

## Props

Constructor of `Devlib\Components\PhoneLink` (5 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `label` | `string`, `''` | Name of the row (translated; wrap data values in `raw()`). |
| `icon` | `string`, `'fa-solid fa-circle'` | Font Awesome classes of the icon. |
| `url` | `string`, `'#'` | `href` of the row. |
| `badge` | `?string`, `null` | Small red pill at the right end (a count or a word). `null` = none. |
| `tone` | `string`, `''` | `info`, `warn` or `danger` colours the row (for reminders); empty = plain. |

## Behavior

- A plain link, no JavaScript. Rows stack with a 12 px gap.

## Data and events

- No events. Browser to server: navigation only.

## Rules and pitfalls

- Keep the label short; it does not wrap beyond two lines comfortably at 17 px.
- Related lint rules: none.

## Example

```blade
<x-dl::phone-link label="Receiving" icon="fa-solid fa-truck-ramp-box" :url="url('/receiving')" />
```

## Related

- `x-dl::phone-tile`, `x-dl::phone-shell`.
