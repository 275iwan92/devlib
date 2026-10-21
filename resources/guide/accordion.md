## Overview

`<x-dl::accordion>` is one collapsible panel: a clickable header (icon, title, optional summary text and colored badge, chevron) and a body that opens and closes with a short height animation. Use it to hide secondary detail on a screen (tax data, shipping addresses, SOP revisions) while keeping a one-line `summary` or `badge` readable when the panel is closed.

Standing alone, an accordion is a plain vertical panel that opens and closes independently. Inside `x-dl::accordion-group` it follows the rules of the group (only one open, horizontal layout, remembered state); the group entry describes those rules.

Pick another object when:

- the content is a set of peer views the user switches between, not detail to hide: `x-dl::tabs`;
- several panels must behave as one set (one open at a time, side-by-side layout, remembered state): wrap them in `x-dl::accordion-group`;
- the content must be visible all the time with a title: `x-dl::card`;
- the content is an overlay or side panel: `x-dl::modal`, `x-dl::drawer`.

Generated markup (the root also carries `data-dl`, `data-dl-class`, `data-dl-src` for the developer catalog; extra attributes and `class` go on the root; `can="module.action"` removes the whole panel when the ability is missing):

```html
<div class="dl-accordion [is-open] [has-summary]" data-dl-acc data-acc-key="<key>">
  <button type="button" class="dl-accordion__head" aria-expanded="true|false">
    <i class="fa-solid fa-... dl-accordion__icon"></i>              <!-- only with icon -->
    <span class="dl-accordion__title">Title</span>
    <span class="dl-accordion__summary">summary</span>              <!-- only when summary is not null -->
    <span class="dl-accordion__badge dl-accordion__badge--ok">3</span> <!-- only when badge is not null -->
    <i class="fa-solid fa-chevron-down dl-accordion__chev"></i>
  </button>
  <div class="dl-accordion__body"><div class="dl-accordion__inner"><div class="dl-accordion__pad" style="max-height:620px;overflow:auto">slot</div></div></div>
</div>
```

## Props

Constructor of `Devlib\Components\Accordion` (8 props). Use `:prop="expr"` for anything that is not a plain string; booleans can be written bare (`open`).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `title` | `string`, `''` | Header text (`dl-accordion__title`). Translated through the dictionary (screens write English). Wrap data values in `raw()`: `:title="raw($sop->code.' · '.$sop->name)"`. An empty title leaves an empty header text, which is not a useful click target; always set it. |
| `open` | `bool`, `false` | Initial state. `true` adds `is-open` and `aria-expanded="true"`. It is only the starting state: the user toggles it afterwards. In a group with `remember`, the saved state replaces it on load. In a non-multiple vertical group nothing stops two panels from carrying `open` (the page then shows both open until the first click). |
| `icon` | `?string`, `null` | Icon before the title. A semantic name from `Devlib\Icon::MAP` (`file`, `user`, `attachment`, ...) or a Font Awesome class (`fa-truck`); unknown names are used as written. The view hard-codes `fa-solid`, so only solid-style icons work. `null` renders no icon element. |
| `badge` | `?string`, `null` | Small colored pill after the summary (a count, a revision, a status word). Rendered whenever it is not `null`, so `''` produces an empty pill. NOT translated: pass the final text (use `'Rev. '.$n`, a number as a string, or translate on the server). Color comes from `tone`. |
| `summary` | `?string`, `null` | Text on the right side of the header, readable while the panel is closed (for example `8 processes · 13 Oct - 16 Oct`). Adds `has-summary` to the root, which stops the title from stretching (`flex:0 0 auto`) so the summary gets the space; it is cut with an ellipsis when too long. Rendered whenever not `null` (also `''`). NOT translated. |
| `tone` | `string`, `'info'` | Badge color: `info` (accent, same as the base style), `ok` (green), `warn` (amber), `danger` (red). Only used together with `badge`. Any other string is written as `dl-accordion__badge--<value>`, has no rule, and so looks like `info`. |
| `key` | `?string`, `null` | Identity of the panel inside its group, written as `data-acc-key`. The only thing the group's `remember` stores and restores. Use stable, unique-per-group keys. Without a key the panel is never remembered (see Rules and pitfalls). |
| `max-height` | `?string`, `null` | Any CSS length (`620px`, `40vh`). Writes `style="max-height:...;overflow:auto"` on the inner `dl-accordion__pad`, so a long body scrolls inside the panel instead of stretching the page. `null` = no limit. In a horizontal group the pad is already `height:100%` with its own scroll; a `max-height` still applies on top of that. |

## Behavior

### Open and close

- The click is handled by ONE delegated listener on `document` (`resources/assets/js/core.js:617`), for any element `.dl-accordion__head`. There is no per-element script and no `Devlib.mount` step, so panels added later (modals, AJAX) work without extra code.
- A click toggles `is-open` on the root and sets `aria-expanded` on the header. The body animates with `grid-template-rows: 0fr -> 1fr` (`.22s`), so the height follows the content with no fixed value. The chevron rotates 180 degrees.
- Standalone: only this panel changes. Inside a group the group decides (see `x-dl::accordion-group`): non-multiple groups close the other panels first; horizontal groups never close the clicked panel.
- Open state is visual: an open panel gets an accent left bar and a soft accent header background. Hover tints the header; keyboard focus shows a 2px accent outline.

### Header content

The header is one `<button type="button">`, so Tab, Enter and Space work natively. Title and summary share the row; the badge and chevron sit at the right end. Do not put buttons or links inside the header; put them in the body.

### Language and permissions

- `title` is translated at render time; `summary` and `badge` are not. Everything in the slot is the screen's own markup and is not touched.
- `can="module.action"` (as on any DevLib component) removes the whole panel from the page when the user lacks the ability.

### Accessibility as implemented

- The header button has `aria-expanded` kept in sync by the click handler.
- There is no `aria-controls`, no `id` on the body and no `role="region"`.
- A closed body is only collapsed to zero height (`overflow:hidden`), not `display:none`, `hidden` or `inert`. Links and inputs inside a closed panel are still in the Tab order and still exposed to screen readers.

## Data and events

- The accordion posts nothing and loads nothing; the slot content is ordinary markup. Inputs inside a closed panel are still part of the surrounding `<form>` and are submitted.
- It dispatches no custom event (`dl:*`) and exposes no JS API (no `dlComponent`). To react to opening, listen to `click` on `.dl-accordion__head` or observe the `is-open` class.
- State kept in the browser exists only through the group: `localStorage` key `dl-acc:<remember>` holds a JSON array of the `key` values that are open. See `x-dl::accordion-group`.
- Markup hooks: `data-dl-acc` (root), `data-acc-key` (identity), `.dl-accordion__head` (the click target), `.is-open` (state).

## Rules and pitfalls

- Do not use it to hide required fields of a `x-dl::form`. The form marks an empty required field invalid and scrolls to it, but its auto-switch only knows tabs (`x-dl::tabs`, `form.js:140`); a closed accordion stays closed and the user sees nothing. Keep required fields visible or use `open`.
- `badge` and `summary` are not translated and not wrapped for you; give them final text. `title` is translated: wrap data in `raw()`.
- Set `key` on every accordion inside a group that uses `remember`. Once the group has saved state, a panel without a key counts as "closed" on the next load, even if it has `open`.
- Put a nested accordion in its OWN `x-dl::accordion-group` (or accept the cost). The click handler looks for the nearest group by walking up from the panel's parent, so a standalone accordion inside the body of a grouped panel is treated as a member of the OUTER group.
- Hidden content stays focusable and submittable (see Accessibility). Do not rely on a closed panel to keep a control out of reach.
- One accordion with a single `open` panel and a `summary` is the standard pattern for "detail you may skip": the closed header already tells the user the state.
- Code oddity (not fixed): in `resources/assets/js/core.js:619-622`, a standalone accordion nested inside a grouped panel is resolved to the outer group (`acc.parentElement.closest('[data-dl-accgroup]')`). In a non-multiple outer group, opening the inner panel closes the outer panel that contains it (the outer panel is a direct child with `is-open`); in a horizontal outer group the inner panel cannot be closed (`if (!open && horiz) return;`). Found by reading the code, not by running it.
- Code oddity (not fixed): `resources/assets/js/components/acc-group.js:27` forces the first panel of a horizontal group open by adding `is-open` only; the header keeps `aria-expanded="false"` for that panel (the click handler and the `remember` restore do update it).
- Code oddity (not fixed): the closed body is collapsed by height only (`resources/assets/devlib.css:1112-1114`), so its focusable children remain reachable by keyboard.

## Example

Names and values follow the catalog demo `accordion` (fictional data); the first group is the vertical single-open form.

```blade
<x-dl::accordion-group>
    <x-dl::accordion title="Data Pajak" icon="fa-file-invoice" open>NPWP, PPN, PPh 23.</x-dl::accordion>
    <x-dl::accordion title="Alamat Kirim" icon="fa-truck" badge="2">Gudang Rungkut · Gudang Sidoarjo.</x-dl::accordion>
    <x-dl::accordion title="Lampiran" icon="attachment">Belum ada lampiran.</x-dl::accordion>
</x-dl::accordion-group>
```

A standalone panel with summary, tone, key and a scrolling body:

```blade
<x-dl::accordion title="Work Order" icon="fa-industry" summary="8 processes · 13 Oct - 16 Oct" badge="Late" tone="warn" key="wo" max-height="320px">
    ...long content...
</x-dl::accordion>
```

A data title inside a loop (as the greeting card does for SOP documents):

```blade
@foreach ($sops as $i => $s)
    <x-dl::accordion :title="raw($s['code'].' · '.$s['title'])" :badge="'Rev. '.$s['revision']" :open="$i === 0" icon="fa-file-lines">
        {!! $s['html'] !!}
    </x-dl::accordion>
@endforeach
```

## Related

- `x-dl::accordion-group`: container that gives several accordions one open-at-a-time rule, a horizontal layout and remembered state.
- `x-dl::tabs`: peer views with one visible at a time; the form auto-switches to the tab holding an invalid field.
- `x-dl::card`: titled box that is always open.
- `x-dl::modal`, `x-dl::drawer`: overlays for content that needs focus.
- `x-dl::greeting-card`: uses accordions for SOP revisions in its modal.
- `skills/devlib/components.md`: generated prop list.
