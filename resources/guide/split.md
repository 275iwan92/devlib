## Overview

`<x-dl::split>` is the document layout of DevLib: a wide main area plus a narrow side panel, in one CSS grid. Typical uses are the document body on the left with a "Summary & Tax" panel on the right, or a group tree on the left with the detail on the right (Translation Dictionary). The main area takes the remaining width (`minmax(0, 1fr)`), the side panel takes a fixed width from the `aside` prop, and below 900px of viewport width the two stack.

Pick another object when:

- the blocks are equal and should wrap by available width: `x-dl::cols`;
- two or three fields in one line of a form: `x-dl::form.row` / `x-dl::form.col`;
- the side panel is a menu or a slide-in panel with its own open/close behavior: `x-dl::side-menu` or `x-dl::layer-menu`;
- you only need one neutral wrapper: `x-dl::box`.

Not to be confused with `x-dl::split-button` (a button with a dropdown), which is a different object.

Generated markup:

```html
<div class="dl-split [dl-split--left]" data-dl="x-dl::split [side=left]" data-dl-class="Split ← DevlibComponent" data-dl-src="—" style="--dl-aside:320px">
  <div class="dl-split__main"> ...default slot... </div>
  <aside class="dl-split__aside"> ...side slot... </aside>
</div>
```

Extra attributes (`id`, `class`, `data-*`) are merged onto the root `div`. `can="module.action"` is accepted: without the ability nothing is rendered.

## Props

Constructor of `Devlib\Components\Split` (2 props). Blade names are kebab-case.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `aside` | `string`, `'320px'` | Width of the side panel: any CSS length (`300px`, `22rem`, `28%`). Written to the inline custom property `--dl-aside` on the root; the grid is `minmax(0,1fr) var(--dl-aside)` (or the reverse for the left side). Not validated: write a length with a unit; an invalid value makes the CSS declaration invalid and the grid falls back to a single column. It is written into a `style` attribute (escaped), so never bind user input to it. |
| `side` | `string`, `'right'` | Which side the panel is on. Only the exact value `left` has an effect (adds `dl-split--left`, also written in the `data-dl` tag as `side=left`). Any other value, including `right`, `Left`, `kiri` or an empty string, means right. Always write `side="left"` as a plain string. |

### Slots

| Slot | Meaning |
|---|---|
| default | Main area, in `<div class="dl-split__main">`. |
| `side` | Side panel, in `<aside class="dl-split__aside">`. Written as `<x-slot:side> ... </x-slot:side>`. The slot name and the prop `side` are the same word: inside the template the slot hides the prop, which is why the class has the method `isLeft()` for the check. |

## Behavior

### Layout and style

- `.dl-split`: `display: grid; grid-template-columns: minmax(0,1fr) var(--dl-aside,320px); gap: 12px; align-items: start`. Main and side cells do not stretch to equal height; each is as tall as its content.
- `.dl-split__main`, `.dl-split__aside`: `min-width: 0; display: flex; flex-direction: column; gap: 12px`. Children inside each area are stacked vertically with 12px between them (two cards in the main slot are one above the other).
- `side="left"`: the columns are reversed (`var(--dl-aside) minmax(0,1fr)`) and the aside gets `order: -1`. The DOM order stays main first, then aside: keyboard Tab order and screen readers read the main area first even though the panel is drawn on the left.
- Below `max-width: 900px` (viewport, not container): `grid-template-columns: 1fr`. With the panel on the right the main area is on top and the panel below; with `side="left"` the `order: -1` still applies, so the panel is on top.
- The panel is not sticky and not collapsible; there is no JS.

### Language and permissions

No screen text. `can` hides the whole layout.

### Accessibility

The side slot is an `<aside>` element, a complementary landmark without a label. Add `aria-label` through a wrapper inside the slot if several asides exist on the page.

## Data and events

- Server: nothing is posted by the layout. Fields in either area post as part of their enclosing form; put the `<form>` (usually `x-dl::form`) outside the split, or the whole split inside it, not between.
- Events: none emitted; no JS class (`el.dlComponent` is not set). The JS file `split.js` belongs to `x-dl::split-button` (selector `[data-dl-split]`), not to this object.
- Markup hooks: `.dl-split`, `.dl-split--left`, `.dl-split__main`, `.dl-split__aside`, the custom property `--dl-aside`.

## Rules and pitfalls

- Always give the `side` slot. Without it the aside renders the value of the prop as text (`right` or `left`). See the oddity below.
- Do not pass your own `style` attribute. The root ends up with two `style` attributes, and the browser keeps only the first (yours), so `--dl-aside` is lost and the panel falls back to 320px. Put extra styling on a child element or in a class.
- Use a length with a unit in `aside`. For a responsive width prefer `%`, `rem` or `min(...)` over fixed pixels on small laptop screens.
- The 900px breakpoint is on the viewport. Inside a narrow modal on a wide screen the two areas stay side by side; use `x-dl::cols` when the layout must follow the container width.
- Do not nest splits to get three columns; use `x-dl::cols` inside the main area.
- Each slot child is a flex item: wrap a group that must stay together in `x-dl::box` or `x-dl::card`.
- No lint rule targets `x-dl::split`. General `devlib:lint` rules L001 to L003 apply to the surrounding view.
- Code oddity (not fixed): `src/Components/Split.php:41` renders `{{ $side ?? '' }}` for the aside content. When the caller gives no `side` slot, `$side` is the public prop (`'right'` or `'left'`), not null, so the panel contains the literal word `right` / `left` (verified by rendering). The `?? ''` never takes effect.
- Code oddity (not fixed): `src/Components/Split.php:39` writes `style="--dl-aside:..."` after the attribute bag; a caller `style` attribute produces a duplicate `style` attribute and the first one wins, so `--dl-aside` is ignored (verified by rendering).

## Example

From the catalog demo `site/resources/views/demos/split.blade.php`:

```blade
<x-dl::split aside="300px">
    <x-dl::card title="Isi dokumen">Header, tab baris item, jadwal, dst.</x-dl::card>
    <x-slot:side>
        <x-dl::card title="Summary &amp; Tax">Subtotal, diskon, DPP, PPN, total.</x-dl::card>
    </x-slot:side>
</x-dl::split>
```

Panel on the left, for example a group tree next to the detail (fictional data):

```blade
<x-dl::split side="left" aside="260px">
    <x-dl::card title="Messages in group: Buttons">Detail of the selected group.</x-dl::card>
    <x-slot:side>
        <x-dl::card title="Groups" variant="plain">Buttons, Grid, Form, Print, Messages, Menu</x-dl::card>
    </x-slot:side>
</x-dl::split>
```

## Related

- `x-dl::card`: the usual content of both areas.
- `x-dl::cols`: equal blocks that wrap by container width.
- `x-dl::form`, `x-dl::form.row`: the form layout inside the main area.
- `x-dl::box`: neutral wrapper for a group inside a slot.
- `x-dl::split-button`: unrelated button with a dropdown that only shares the word "split".
- `x-dl::side-menu`, `x-dl::layer-menu`: navigation panels with their own behavior.
