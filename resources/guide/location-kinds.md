## Overview

`<x-dl::location-kinds>` shows storage locations grouped by kind (cabinet, closed warehouse, open warehouse, wide area) as expandable tiles. Each tile has an icon, a name and a count; opening a tile shows its locations as cards, and the active location can open a floor plan (the slot) inside the same panel. It needs no JavaScript: it is built on `<details>`, so keyboard and touch work as for any disclosure.

Use it as the location picker of a warehouse screen, when:

- the locations fall into a few kinds and the user first chooses the kind, then the place;
- the chosen place has a drawing (floor plan, shelf map) to show next to the list.

Pick another object when:

- the data is a long table of locations to sort, filter or edit: `x-dl::grid`;
- you need a plain open/close panel for text: `x-dl::accordion`;
- you need the whole desk (map, stock, actions) and not only the picker: `x-dl::warehouse-desk`, which uses this object.

The object reads no table or model: data enters only through `kinds`. It does not save or navigate by itself; every location card is a link (`href`), so choosing a place is a normal page visit and the server marks the active one.

Generated markup:

```html
<div class="dl-lk" data-dl="x-dl::location-kinds" style="--dl-lk-n:2">
  <details name="dl-lk" class="dl-lk__k dl-lk__k--violet" open>
    <summary class="dl-lk__sum"><span class="dl-lk__ico"><i class="fa-solid fa-box"></i></span><span class="dl-lk__name">Cabinet</span><span class="dl-lk__count">3</span><i class="fa-solid fa-chevron-up dl-lk__chev"></i></summary>
    <div class="dl-lk__body">
      <a href="/warehouse/a1" class="dl-lk__card is-active"><span class="dl-lk__title">A1</span><span class="dl-lk__sub">Sheet paper</span></a>
      <details class="dl-lk__map" open><summary class="dl-lk__mapsum">Floor plan · A1</summary><div class="dl-lk__mapbody">{{ slot }}</div></details>
    </div>
  </details>
</div>
```

With an empty `kinds` the root is not drawn: only the slot is output. Extra attributes (`id`, `class`, `style`, `data-*`) go on the root `div`. `can="module.action"` hides the whole object without the ability.

## Props

Constructor of `Devlib\Components\LocationKinds` (2 props). Use `:kinds="$kinds"` (array expression).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `kinds` | `array`, `[]` | The tiles, in order. Each item: `key` (string id of the kind, for your own use), `label` (tile name; translated through the dictionary), `icon` (Font Awesome class or an `Icon` meaning; default `fa-box`), `tone` (`violet`, `teal`, `amber`, `coral` or `neutral`; default `neutral`; colours the tile), `open` (`true` = the tile starts open), `items` (the locations, below). The number of tiles is written to the CSS variable `--dl-lk-n`. An empty array draws no tiles and outputs the slot only. |
| `empty` | `string`, `'No location yet'` | Text inside an open tile that has no `items`; translated through the dictionary. |

Each `items` entry: `id`, `title` (required text of the card; translated), `sub` (optional small line; translated), `href` (required address of the card link), `active` (`true` = the current location, highlighted), `map` (`true` on the active location = show the slot as an expandable "Floor plan" section under that card).

## Behavior

- Wide screen: the tiles stand in a column on the left and the open kind fills the panel on the right. Narrow screen: the tiles are two columns and the open kind takes a full row.
- Only one kind is open at a time (`<details name="dl-lk">`): opening another closes the previous one; clicking the open tile closes it. The kinds with `open => true` start open (give it to one only).
- The count on a tile is the number of `items` of that kind.
- The slot (floor plan) is output once for every item that is both `active` and `map`; it is open by default and can be collapsed. Use one such item.
- Text in `label`, `title`, `sub` and `empty` goes through the dictionary, so give English text or wrap data values in `raw()`.

## Data and events

- Data in: only the `kinds` prop. No request, no script, no custom event and no `el.dlComponent`.
- Out: the user leaves by the `href` of a card; the destination screen decides what is active and renders this object again.

## Rules and pitfalls

- `href` and `title` are read without a fallback: an item without them breaks the page, so always supply both.
- The object does not remember which tile was open; send `open` and `active` from the server on every render.
- A kind with `open => true` plus another kind opened by the user are never open together; do not rely on two open tiles.
- The floor plan appears only for an item that is `active` and has `map => true`; otherwise the slot is not shown.
- Do not use hex colors or `<script>` in the screen around it: lint rules L001, L002 and L003 apply to the Blade screen. There is no object-specific lint rule.

## Example

```blade
<x-dl::location-kinds :kinds="[
    ['key' => 'cabinet', 'label' => 'Cabinet', 'icon' => 'fa-box', 'tone' => 'violet', 'open' => true, 'items' => [
        ['id' => 1, 'title' => 'A1', 'sub' => 'Sheet paper', 'href' => '/warehouse/1', 'active' => true, 'map' => true],
        ['id' => 2, 'title' => 'A2', 'sub' => 'Ink', 'href' => '/warehouse/2'],
    ]],
    ['key' => 'yard', 'label' => 'Open warehouse', 'tone' => 'amber', 'items' => []],
]">
    {{-- floor plan of the active location --}}
    <x-dl::image-gallery :items="$planImages" />
</x-dl::location-kinds>
```

## Related

- `x-dl::warehouse-desk`: the Warehouse Map screen that uses this object as its location picker.
- `x-dl::accordion`: a general open/close panel for content that is not a list of places.
- `x-dl::grid`: the table form of the same locations, with filters and export.
