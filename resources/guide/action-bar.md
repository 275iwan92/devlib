## Overview

`<x-dl::action-bar>` is the button row at the foot of a card or form: a flex container with a thin top border, a gap between its children and an alignment. It holds `x-dl::button` objects (and `x-dl::back-button`, `x-dl::menu-button`, `x-dl::doc-share` and the like). It has no logic of its own: no script, no data, no events.

Use it whenever two or more actions end a card, a form, a print preview or a side panel. Do not use it:

- as the footer of a full document or master form: `x-dl::form` already renders its own footer (`dl-form__foot`) with the Save / Undo / Confirm / Back buttons of the document status;
- for a toolbar above a grid: `x-dl::grid` has its own toolbar;
- when one button is enough and it needs no border or alignment: place the `x-dl::button` directly.

### Generated markup

```html
<div class="dl-actionbar dl-actionbar--end" data-dl="x-dl::action-bar" data-dl-class="ActionBar" data-dl-src="—">
  ...slot (buttons)...
</div>
```

Extra HTML attributes (`id`, `class`, `style`, `data-*`) are merged onto this `div` (`class` is appended). `can="module.action"` hides the whole bar when the ability is missing.

## Props

Constructor of `Devlib\Components\ActionBar` (1 prop).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `align` | `string`, `'end'` | Class `dl-actionbar--<align>`. `end` (default): children grouped at the right (`justify-content:flex-end`). `between`: first child at the left, last at the right, space in the middle (`space-between`), for example a status badge on the left and the share/print buttons on the right. `start`: grouped at the left. `start` has no CSS rule of its own; it works because the bar is a flex row that starts at the left by default. Any other value is written as a class and gets no special alignment (so it behaves like `start`). There is no `center`. |

The default slot is the only slot.

## Behavior

- CSS: `display:flex; gap:8px; flex-wrap:wrap; align-items:center; padding-top:10px; margin-top:4px; border-top:1px solid` (soft border). The row wraps on narrow screens.
- The order of the children is the order in the source. For the usual pair, write the secondary action first and the primary last: Cancel then Save (the primary sits at the far right with `align="end"`).
- With `between`, put the group that belongs at the left first. If the bar has more than two children, the middle ones are spread evenly; wrap two buttons in a `<div class="dl-row">` if they must stay together on one side.
- The bar does not dim, disable or hide buttons by itself. Hide an action by not rendering it (`@unless`, `can=` on the button).
- Language and text: the bar has no text of its own. The dictionary groups it with the `buttons` group (texts that first appear through it are recorded there).
- Accessibility: a plain `div`, no role. The buttons inside are normal focusable controls in source order.

## Data and events

None. The bar emits no event, has no `dlComponent`, and posts nothing. A `<button type="submit">` inside the bar submits the nearest enclosing form, so the bar is normally placed inside an `x-dl::action-form`, an `x-dl::form` or an `x-dl::card` that is inside one.

Hooks: `.dl-actionbar`, `.dl-actionbar--end`, `.dl-actionbar--between`.

## Rules and pitfalls

- The bar draws a top border and a top margin. Do not put it above content or stack two bars in a row; one bar per card.
- The bar does not create a form. Buttons with `type="submit"` need an enclosing form; a `type="button"` (the default of `x-dl::button`) does nothing by itself. Check which `type` the button really has: `preset="save"` is `submit`, `preset="cancel"` is `button`.
- Do not write raw `<button>` tags inside the bar (lint `L001`); use `x-dl::button`.
- `align` is not validated. A typo is silent.
- Side panels and small cards use `align="start"` (the action is the first thing read); document cards use the default `end`.
- Do not put a status badge and the buttons on the same side if the screen is narrow: use `align="between"` so the badge stays left.

## Example

Card footer (shape of the demo `card-actionbar`):

```blade
<x-dl::card title="Informasi Umum" icon="file" description="Data utama dokumen">
    <x-dl::form.row>
        <x-dl::field.lookup name="supplier_id" lookup="supplier" label="Supplier" />
        <x-dl::field.date name="tgl" label="Tanggal PO" value="2026-09-23" />
    </x-dl::form.row>
    <x-dl::action-bar>
        <x-dl::button preset="cancel" />
        <x-dl::button preset="save" />
    </x-dl::action-bar>
</x-dl::card>
```

Badge on the left, document actions on the right (shape of the demo `doc-share`):

```blade
<x-dl::action-bar align="between">
    <x-dl::badge label="Draft" />
    <x-dl::doc-share target="#demo-doc" type="demo" doc-id="12" number="IQ-2609-0012" title="Quotation" email-to="purchasing@customer.test" />
</x-dl::action-bar>
```

Left-aligned actions inside a small form (shape of the generator screen):

```blade
<x-dl::action-form action="{{ route('generator.generate', $req->id_request) }}">
    <x-dl::action-bar align="start">
        <x-dl::button type="submit" icon="fa-bolt" label="Generate Now" />
    </x-dl::action-bar>
</x-dl::action-form>
```

## Related

- `x-dl::button`, `x-dl::back-button`, `x-dl::menu-button`, `x-dl::split-button`, `x-dl::doc-share`: what normally goes inside.
- `x-dl::action-form`: the small form that usually wraps a card with an action bar.
- `x-dl::card`: the container that the bar closes.
- `x-dl::form`: the full document/master form with its own footer (use instead of a hand-made bar).
