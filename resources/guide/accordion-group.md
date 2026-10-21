## Overview

`<x-dl::accordion-group>` is the container that turns several `x-dl::accordion` panels into one set. It decides the layout (vertical stack, or horizontal strips that widen sideways), whether one or many panels may be open, and whether the open panels are remembered in the browser between visits.

Use it when you have two or more accordions that belong together: a form section list, the steps of a process shown as horizontal strips, a set of FAQ-like details. A single accordion needs no group.

Pick another object when:

- the panels are peer views and exactly one must always be visible, with a tab bar: `x-dl::tabs`;
- you only need one hide/show panel: a standalone `x-dl::accordion`;
- the sections are a wizard with steps and validation: `x-dl::wizard`.

Generated markup (root also has `data-dl`, `data-dl-class`, `data-dl-src`; extra attributes and `class` go on this root; `can="module.action"` removes the whole group without the ability):

```html
<div class="dl-accgroup dl-accgroup--vertical|horizontal" data-dl-accgroup data-multiple="0|1" data-remember="<name>" style="--dl-acc-h:300px">
  <div class="dl-accordion" ...>...</div>   <!-- the slot: x-dl::accordion panels -->
</div>
```

`data-remember` exists only when `remember` is set.

## Props

Constructor of `Devlib\Components\AccordionGroup` (4 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `direction` | `string`, `'vertical'` | `vertical` stacks the panels (6px spacing between panels). `horizontal` lays them side by side as 46px vertical strips (title written sideways); the open strip takes the remaining width. The value becomes the class `dl-accgroup--<direction>`; any other string has no style and behaves like a plain vertical stack, but the horizontal rules in the scripts do not apply. At screen width up to 640px a horizontal group is shown as a normal vertical stack (CSS media query). The catalog tag shown for the object is `x-dl::accordion-group direction=<value>`. |
| `multiple` | `bool`, `false` | `false`: opening a panel closes the other panels of the group (only one open). `true`: panels open and close independently. Written to `data-multiple="1|0"`. In a horizontal group `multiple` still lets several strips be open, but the last open strip can never be closed (see Behavior). |
| `height` | `string`, `'300px'` | Height of the group, written as the CSS variable `--dl-acc-h` on the root. Only the horizontal layout uses it (`height:var(--dl-acc-h)`); a vertical group grows with its content and ignores it. On narrow screens (up to 640px) the horizontal group switches to `height:auto`. Any CSS length (`240px`, `60vh`). |
| `remember` | `?string`, `null` | A name. When set, the open panels (identified by the `key` prop of each accordion) are saved in the browser under `localStorage['dl-acc:<name>']` and restored on the next page load. `null` = nothing is saved or restored; panels start from their `open` prop. Choose a name unique per screen (`wo-sections`), because it is shared by every page of the same origin that uses the same name. |

## Behavior

### Open and close rules

The logic is one click handler in `resources/assets/js/core.js:617` (shared with the standalone accordion):

1. A click on a panel header toggles that panel.
2. Vertical, `multiple=false`: when the clicked panel opens, every other direct-child panel of the group is closed (`is-open` removed, `aria-expanded="false"`). The user may close the open panel, so zero panels open is possible.
3. Vertical, `multiple=true`: no other panel is touched.
4. Horizontal: clicking the open strip does nothing (it cannot be closed); clicking a closed strip opens it and, unless `multiple`, closes the others. So a horizontal group always keeps at least one open panel.
5. Only DIRECT children of the group count (`:scope > .dl-accordion`). Put the accordions straight in the slot, not inside another wrapper `div`, or the group rules will not see them.

### Initial state

- Vertical: each accordion starts from its own `open` prop. Nothing enforces "one open" at load, so two `open` panels in a non-multiple group are both shown until the first click.
- Horizontal: if no panel is open after loading (and after restoring), the first panel is opened by the script (`acc-group.js:27`).
- With `remember`, `acc-group.js` runs `init()` on `[data-dl-accgroup]` elements at page mount: it reads `localStorage['dl-acc:<name>']` (a JSON array of keys). If the value is an array, every direct-child panel is set open when its `key` is in the array and closed otherwise (also `aria-expanded` is updated). If nothing is stored yet (or the JSON is invalid), the panels keep their `open` prop.
- Each click saves the list again: the `key` values of the panels that are now open (panels without `key` are filtered out).

### Layout

- Horizontal: `display:flex`, closed strips `flex:0 0 46px`, open strip `flex:1 1 0`; header text is rotated (`writing-mode:vertical-rl`), the body appears with a width animation (`grid-template-columns: 0fr -> 1fr`), the pad has `min-width:220px` and scrolls (`overflow:auto`).
- Narrow screens (up to 640px): horizontal turns into a normal vertical stack with the same open/close rules (the "cannot close the open one" rule still holds because it is decided by the class, not the width).

### Accessibility, language, permissions

Same as the accordion: header buttons with `aria-expanded`, no `aria-controls`/`role="region"`, closed bodies collapsed by height only. The group itself has no ARIA role. It has no text of its own, so there is nothing to translate; `can="module.action"` hides it completely.

## Data and events

- No server data, nothing posted, no custom `dl:*` event. The only persistent state is the `localStorage` entry described above (try/catch protected: if storage is blocked, the group still works but nothing is remembered).
- The browser keeps the state, not the server: it is per browser and per user profile, not per account. Clearing site data resets it.
- Hooks: `data-dl-accgroup` (root), `data-multiple`, `data-remember`, `--dl-acc-h`, `.dl-accgroup--vertical|--horizontal`. The class is `AccGroup` (`Devlib.classes.AccGroup`, selector `[data-dl-accgroup]`, `el.dlComponent` is the instance); it has no methods other than `init()`.

## Rules and pitfalls

- Give every accordion in a `remember` group a unique `key`. Keys are the only thing stored; a keyless panel is treated as closed after the first save.
- Do not change the `key` values of a deployed screen without a plan: old saved keys no longer match, so panels fall back to "closed".
- Use a `remember` name that is unique per screen and per purpose. The same name on two screens makes them overwrite each other's list.
- `multiple=false` is only enforced on click. Do not rely on it to validate the server-rendered `open` flags: set `open` on at most one panel.
- For a horizontal group, give a `height` that fits the tallest content (the pad scrolls when content is taller). On phones the height is ignored.
- The group sets no layout for a `x-dl::accordion` that is not its direct child; keep the slot clean.
- Nested group inside an accordion body is fine and each group works on its own children; a standalone accordion (without its own group) inside a grouped panel is attributed to the outer group (see the accordion entry for the consequence).
- `direction` accepts only `vertical` or `horizontal`; do not invent other values.
- Code oddity (not fixed): `resources/assets/js/components/acc-group.js:27` forces the first strip open in a horizontal group by adding `is-open` only; `aria-expanded` stays `"false"` for that header until a later click.
- Code oddity (not fixed): the remembered list is written only by the click handler (`core.js:624`) and read only at mount (`acc-group.js:19-26`). A horizontal group whose restored list has no matching key opens the first strip, but that automatic choice is not saved until the user clicks.

## Example

Catalog demo `accordion` (fictional data), the three forms of the group:

```blade
{{-- Vertical, one open --}}
<x-dl::accordion-group>
    <x-dl::accordion title="Data Pajak" icon="fa-file-invoice" open>NPWP, PPN, PPh 23.</x-dl::accordion>
    <x-dl::accordion title="Alamat Kirim" icon="fa-truck" badge="2">Gudang Rungkut · Gudang Sidoarjo.</x-dl::accordion>
    <x-dl::accordion title="Lampiran" icon="attachment">Belum ada lampiran.</x-dl::accordion>
</x-dl::accordion-group>

{{-- Vertical, many open --}}
<x-dl::accordion-group multiple>
    <x-dl::accordion title="Spesifikasi Bahan" open>Ivory 310 gsm, plano 65×100.</x-dl::accordion>
    <x-dl::accordion title="Finishing" open>Laminasi doff, pond, lem samping.</x-dl::accordion>
</x-dl::accordion-group>

{{-- Horizontal: strips widen sideways (phone: vertical) --}}
<x-dl::accordion-group direction="horizontal" height="240px">
    <x-dl::accordion title="1 · Order" icon="fa-file-signature" open><b>Customer Order</b><br>CO-2609-0031 dikonfirmasi, due date 14 Okt 2026.</x-dl::accordion>
    <x-dl::accordion title="2 · Produksi" icon="fa-industry"><b>Work Order</b><br>Cetak 4 warna, 2 shift.</x-dl::accordion>
    <x-dl::accordion title="3 · QC" icon="fa-clipboard-check"><b>Quality Control</b><br>Sampling 1%, cek warna & potong.</x-dl::accordion>
</x-dl::accordion-group>
```

Remembering the open panels (every panel has a `key`):

```blade
<x-dl::accordion-group multiple remember="wo-sections">
    <x-dl::accordion key="material" title="Materials" open>...</x-dl::accordion>
    <x-dl::accordion key="process" title="Processes" summary="8 processes">...</x-dl::accordion>
    <x-dl::accordion key="notes" title="Notes">...</x-dl::accordion>
</x-dl::accordion-group>
```

## Related

- `x-dl::accordion`: the panel itself (title, icon, summary, badge, tone, key, max-height); also usable alone.
- `x-dl::tabs`: when one view must always show and switching is by a tab bar.
- `x-dl::wizard`: step-by-step flow with validation instead of free open/close.
- `x-dl::card`: a titled box that does not collapse.
- `skills/devlib/components.md`: generated prop list.
