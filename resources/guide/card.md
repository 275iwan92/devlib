## Overview

`<x-dl::card>` is the standard titled box of DevLib: a bordered, rounded `<section>` with an optional header (icon, title, description, action buttons on the right) and a body that stacks its children vertically. Use it to group content on a screen: a section of a form, an info panel, a dashboard tile, a block of a catalog page.

Pick another object when:

- the group is a set of fields that must be locked or posted together as one unit: `x-dl::block` (a `fieldset`);
- you need a neutral wrapper with no frame: `x-dl::box`;
- the content is a number or trend: `x-dl::kpi-row`, `x-dl::gauge`, `x-dl::status-card`;
- the content is a whole document with header, lines and actions: `x-dl::form` (it has its own title and buttons);
- the section must fold: `x-dl::accordion`.

Generated markup:

```html
<section class="dl-card dl-card--section" data-dl="x-dl::card variant=section" data-dl-class="Card ← DevlibComponent" data-dl-src="—">
  <header class="dl-card__head">                      <!-- only when title is set or the actions slot is given -->
    <div><i class="fa-solid fa-file-lines"></i> <b>Title</b><small>Description</small></div>
    <div class="dl-card__actions"> ...actions slot... </div>   <!-- only with the actions slot -->
  </header>
  <div class="dl-card__body"> ...default slot... </div>
</section>
```

Extra attributes (`id`, `class`, `style`, `data-*`) go on the `<section>`; a `class` you pass is appended after `dl-card dl-card--<variant>`. `can="module.action"` is also accepted: without the ability nothing is rendered.

## Props

Constructor of `Devlib\Components\Card` (4 props). Blade names are kebab-case; all four are plain strings.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `title` | `?string`, `null` | Header title, written in `<b>`. Translated through the dictionary (it is a standard text prop); wrap data values (document numbers, customer names) in `raw()` or build the string as `$t('...').' · '.$no` after translating. Shown in upper case by CSS (`text-transform: uppercase`, letter-spacing .4px). The header exists only when `title` is non-empty OR the `actions` slot is given. |
| `description` | `?string`, `null` | Small gray line under the title (`<small>`, normal case). Translated. It lives inside the header, so it is shown only when the header is shown (see pitfalls). |
| `variant` | `string`, `'section'` | Adds the class `dl-card--<variant>` and is written in the `data-dl` tag. Styled values: `section` (default, full border and tinted header), `plain` (no border, no background, header and body without side padding; for a card that sits inside another surface) and `accent` (adds a 3px accent top border). Any other string is accepted and only adds a class with no style. |
| `icon` | `?string`, `null` | Icon before the title, inside the header. Either a DevLib meaning name from `Devlib\Icon::MAP` (`file`, `user`, `settings`, `print`, ...; resolved by `Icon::fa()`) or a Font Awesome class (`fa-user`). The view hard-codes `fa-solid`, so only solid icons work. Shown in the accent color. Needs the header (title or actions) to be visible. |

### Slots

| Slot | Meaning |
|---|---|
| default | Body content, inside `<div class="dl-card__body">`. |
| `actions` | Buttons or links placed at the right of the header (`dl-card__actions`, `display:flex; gap:6px`). Usually `size="sm"` buttons: `<x-slot:actions><x-dl::button preset="print" size="sm" /></x-slot:actions>`. Its presence alone creates the header. |

## Behavior

### Layout and style

- Card: surface background, 1px border, large radius, `overflow: hidden` (children are clipped at the rounded corners; an absolutely-positioned dropdown that extends beyond the card can be cut off).
- Header: flex row, `space-between`, 10px gap, `10px 14px` padding, bottom border and alternate surface background. `b` is 12.5px bold in strong ink color; `small` is a block under it in muted color.
- Body: `padding: 14px; display: flex; flex-direction: column; gap: 8px`. Children are stacked vertically with 8px between them; plain text nodes become flex items. Put `x-dl::form.row` or `x-dl::cols` inside when you need columns.
- Other CSS that knows the card: `.dl-wb__item > .dl-card` (dashboard widget board makes it fill its cell), `.dl-dash__targets .dl-card__body` (14px gap), `.dl-pdoc__appx-body .dl-card` (no page break inside when printed), `.dl-ssl__panel > .dl-card.is-hidden` (side-screen search hides non-matching cards).

### Language and permissions

- `title` and `description` follow the page language through the dictionary at render time. The body is whatever you place there.
- `can` hides the whole card; it is a render-time rule only.

### Accessibility

A `<section>` with a `<header>`. There is no `aria-labelledby`, so the section is not a named landmark; the title `<b>` is not a heading element. Use real headings in the body when document outline matters.

### Title link to the Technical Guide

When the project sets a guide URL (`config('devlib.guide_url')` or the built-in route), `guide-link.js` wraps `.dl-card__head b` and links it to this entry (`x-dl::card` mapped to `.dl-card__head b`). Cards without a title have no link.

## Data and events

- Server: the card posts nothing. Fields inside it post as part of the enclosing `<form>` (a card does not create a form).
- Events: none emitted; no JS class (`el.dlComponent` is not set).
- Markup hooks: `.dl-card`, `.dl-card--<variant>`, `.dl-card__head`, `.dl-card__actions`, `.dl-card__body`. `x-dl::doc-share` uses `closest('form, .dl-card, main')` to find its default capture target.
- Note: `x-dl::card` is not a `form`; for save/confirm behavior wrap the card in `x-dl::form` or use `x-dl::action-form`.

## Rules and pitfalls

- The header is conditional: `@if ($title || isset($actions))`. A card with only `description` and/or `icon` (no `title`, no `actions` slot) renders NO header, so the description and icon disappear silently. Give it a `title`.
- A card with the `actions` slot but no `title` renders an empty `<b></b>` (the actions stay right-aligned).
- `title` and `description` are translated; wrap data in `raw()`. Building a title from a translation key plus a number is the usual pattern (see the document share demo).
- `variant` is not validated: `variant="accent "` or a typo only produces an unstyled class. Use `plain` for a card nested in another card to avoid a double border.
- Do not use a card as a form locker; use `x-dl::block` with `disabled`.
- The card clips its content (`overflow: hidden`) and does not scroll; wide content must handle its own overflow.
- Page-level rules: the screen should group data in cards instead of raw `<div>`s with custom borders; L003 (no hex colors in `style=`) applies to any `style` you pass on the card.
- Code oddity (not fixed): `src/Components/Card.php:34` gates the whole header on `$title || isset($actions)`, so `description` and `icon` (props of their own) are dropped silently when neither a title nor the `actions` slot is given (verified by rendering). The class header and `skills/devlib/components.md:106` do not mention this condition.

## Example

From the catalog demo `site/resources/views/demos/card-actionbar.blade.php`:

```blade
<x-dl::card title="Informasi Umum" icon="file" description="Data utama dokumen">
    <x-slot:actions><x-dl::button preset="print" size="sm" /></x-slot:actions>
    <x-dl::form.row>
        <x-dl::field.lookup name="supplier_id" lookup="supplier" label="Supplier" />
        <x-dl::field.date name="tgl" label="Tanggal PO" value="2026-09-23" />
    </x-dl::form.row>
    <x-dl::field.text name="catatan" label="Catatan" multiline />
    <x-dl::action-bar>
        <x-dl::button preset="cancel" />
        <x-dl::button preset="save" />
    </x-dl::action-bar>
</x-dl::card>
```

Cards inside `x-dl::split` (shape of `split.blade.php`; the `variant="accent"` and the plain card are added here to show the variants):

```blade
<x-dl::split aside="300px">
    <x-dl::card title="Isi dokumen">Header, tab baris item, jadwal, dst.</x-dl::card>
    <x-slot:side>
        <x-dl::card title="Summary &amp; Tax" variant="accent">Subtotal, diskon, DPP, PPN, total.</x-dl::card>
    </x-slot:side>
</x-dl::split>

<x-dl::card title="Notes" variant="plain">Text without frame.</x-dl::card>
```

## Related

- `x-dl::cols`: two or more cards side by side, stacking on narrow screens.
- `x-dl::split`: a main card plus a side card.
- `x-dl::block`: framed `fieldset` for fields that lock together.
- `x-dl::box`: neutral wrapper.
- `x-dl::button`, `x-dl::icon-button`, `x-dl::action-bar`: what normally goes in the `actions` slot and the footer of the body.
- `x-dl::form`, `x-dl::form.row`, `x-dl::form.col`, `x-dl::field.*`: the form shell and fields placed in the body.
- `x-dl::accordion`: when a section must fold.
