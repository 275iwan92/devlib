## Overview

`<x-dl::wizard-options>` is the plain container of a list of choice rows (`x-dl::wizard-option`) for a **wizard in a modal**: a first dialog where the user picks a type or a data source by pressing the button of one row (for example "New product", "Recalculate price", "Change specification"), which then opens the next dialog or screen. It renders one `div.dl-wiz` (a vertical flex column with a 10 px gap) around its slot, and nothing else: no script, no props, no state.

It is **not** part of the step-by-step form `x-dl::wizard`: there is no connection to the stepper, to Next, to validation or to the Review, and the rows post nothing (they have no field). The relation of the four wizard tags:

| Tag | Role |
|---|---|
| `x-dl::wizard` > `x-dl::wizard.step` | the step-by-step form (entry `wizard.md`) |
| `x-dl::wizard-options` > `x-dl::wizard-option` | choice rows for a wizard in a modal (this entry and `wizard-option.md`) |

Pick another object when:

- the user fills fields step by step: `x-dl::wizard`;
- the choices are a single field: `x-dl::field.radio` or `x-dl::field.select`;
- the choices are large cards in a grid, not a list of rows: build a small component of your project with `x-dl::card`.

## Props

`Devlib\Components\WizardOptions` has **no constructor props**. The only content is the default slot: put `x-dl::wizard-option` rows in it.

### Attributes accepted on every DevLib object

`can="module.action"` (no access = the list is not rendered) and any HTML attribute (`id`, `class`, `style`, `data-*`), merged into the root `div`.

## Behavior

### Generated markup

```html
<div class="dl-wiz" data-dl="x-dl::wizard-options" ...>
  <div class="dl-wiz__opt"> ... </div>
  <div class="dl-wiz__opt"> ... </div>
</div>
```

CSS (`resources/assets/devlib.css`, the `.dl-wiz` rules): `display: flex; flex-direction: column; gap: 10px`. The container has no border, background or padding of its own: the modal body gives the frame.

- Rows are laid out in the order written. The container does not select, highlight or remember a row; the action button inside each row does the work.
- Any content is allowed in the slot (a heading or a paragraph between rows), but the spacing is made for rows only.

## Data and events

- Posts nothing, emits no `dl:*` event, has no JavaScript instance.
- The only interaction is in the rows: a button in the row slot with `data-dl-open="<modal id>"` opens the next modal, or a normal link / `onclick` does what the screen needs.

## Rules and pitfalls

- Put it inside an `x-dl::modal` body (or a drawer); the rows are made for a dialog width.
- Use it only for the "choose one action" step. For data entry use `x-dl::wizard`.
- The container does not enforce a single choice: each row is independent, so do not expect radio behaviour.
- A row marked `off` is only dimmed; it is still clickable unless its own button is `disabled` (see `wizard-option.md`).
- No lint rule (`devlib:lint`) is specific to this object.
- Code oddity (not fixed): `src/Components/WizardOptions.php:9` (and `WizardOption.php:10`) name `screens/inquiry.blade.php` as the user of the object; that file is not in this repository, and no catalog demo or test uses `x-dl::wizard-options`. The example below is therefore written for this guide, not copied from a demo.

## Example

Fictional data. A button opens a modal; each row has its own action button that opens the next modal (the second modal is not shown).

```blade
<x-dl::button label="Create Request" icon="new" data-dl-open="wz-type" />

<x-dl::modal id="wz-type" title="Create Request" icon="new">
    <x-dl::wizard-options>
        <x-dl::wizard-option emoji="✨" title="New Product" description="Never made before. Sales defines the product from scratch." badge="Most used" badge-tone="accent">
            <x-dl::button label="Choose" size="sm" data-dl-open="wz-new" />
        </x-dl::wizard-option>
        <x-dl::wizard-option emoji="🔁" title="Recalculate Price" description="The product already exists. Only a new price is needed.">
            <x-dl::button label="Choose" size="sm" data-dl-open="wz-recalc" />
        </x-dl::wizard-option>
    </x-dl::wizard-options>
    <x-slot:footer><x-dl::button preset="cancel" data-dl-close-modal /></x-slot:footer>
</x-dl::modal>
```

## Related

- `x-dl::wizard-option`: one row of this list.
- `x-dl::modal`: the dialog that holds the list (`data-dl-open` opens it, `data-dl-close-modal` closes it).
- `x-dl::wizard` and `x-dl::wizard.step`: the step-by-step form wizard.
- `x-dl::button`: the usual action button in a row.
