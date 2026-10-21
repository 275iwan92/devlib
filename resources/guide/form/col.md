## Overview

`<x-dl::form.col>` is one vertical column of a form: whatever you put inside is stacked top to bottom with a small gap (5px). It is a `div` with the class `dl-form__col`, has no props, no JavaScript and no screen text.

Use it to group several fields (or fields plus buttons) into one block, mainly:

- as a cell of an `x-dl::form.row`, when the cell holds more than one field or card;
- directly in the body of a plain `x-dl::form`, whose body is a two-column grid: two `form.col` children give two side-by-side blocks that collapse to one column under 700px.

Pick another object when the fields only need a line of 2 to 4 equal cells (`x-dl::form.row`), when you need a titled block with a border (`x-dl::card`), or when the content is a tab (`tabs` of `x-dl::form`).

### Generated markup

```html
<div class="dl-form__col" data-dl="x-dl::form.col" data-dl-class="Col ← DevlibComponent" data-dl-src="—">...</div>
```

CSS (`resources/assets/devlib.css`): `.dl-form__col{display:flex;flex-direction:column;gap:5px;min-width:0}`. `min-width:0` lets the column shrink inside a grid cell instead of growing with a wide child.

## Props

Constructor of `Devlib\Components\Form\Col`: none (the class has no constructor), so the manifest lists no props.

Accepted as on every DevLib object: `can="module.action"` (without the ability the column and its content are not rendered) and any HTML attribute (`id`, `class`, `style`, `data-*`), merged into the root `div`. Unlike `x-dl::form.row`, a `style` attribute is safe here because the component writes no inline style of its own.

## Behavior

- Pure layout: no state, no events, no keyboard handling, no ARIA role. The Tab order follows the DOM order.
- The column does not size itself; its width comes from its parent: a cell of `x-dl::form.row` (equal `1fr` cells) or a cell of the plain-form body (`1fr 1fr`). A lone `form.col` in a plain form takes only the left half.
- There is no gap or alignment option. For other spacing pass `class` / `style` or wrap the content in your own element.
- Fields inside keep working as usual: `x-dl::form` validation, dirty guard and Undo see them wherever they are nested.
- No screen text, so nothing is translated. `can="module.action"` removes the column when the user lacks the ability (a display rule only; enforce it on the server).

## Data and events

- Posts nothing and fires nothing; the fields inside post normally.
- No `dl:*` event and no JS class (`el.dlComponent` is not set). The only hook is the class `dl-form__col`.
- `data-dl`, `data-dl-class` and `data-dl-src` are the catalog/Technical Guide marks; `data-dl-src` is always `—`.

## Rules and pitfalls

- In a plain `x-dl::form` (no `panel`) use `form.col` for the side-by-side blocks; a single `form.col` fills only half of the body.
- Inside a panel form (`panel="top"`) the tab area is a normal block: use `x-dl::form.row` for lines of fields and `form.col` only as a cell of a row.
- Do not wrap a single field in a `form.col`; the extra wrapper changes nothing.
- No lint rule applies to this object (the `F1xx` rules in `Devlib\Lint\FormRules` check `x-dl::form` only).
- The two-column behavior comes from the plain form body (`.dl-form__body`), not from `form.col`; outside a plain form, `form.col` is only a vertical stack.

## Example

Taken in shape from the catalog demo `dirty-guard` (plain form, two columns):

```blade
<x-dl::form title="Edit Supplier" :actions="['Save' => 'fa-floppy-disk']" style="height:auto">
    <x-dl::form.col>
        <x-dl::field.text name="nama" label="Supplier name" value="PT Contoh Kertas Jaya" />
        <x-dl::field.text name="kota" label="City" value="Surabaya" />
    </x-dl::form.col>
    <x-dl::form.col>
        <x-dl::back-button fallback="#" />
        <x-dl::button variant="link" href="#other-screen" label="Link to another screen" />
    </x-dl::form.col>
</x-dl::form>
```

As a cell of a row (the pattern of the request screen of the catalog site):

```blade
<x-dl::form.row :cols="2">
    <x-dl::card title="Request" icon="fa-wand-magic-sparkles">...</x-dl::card>
    <x-dl::form.col>
        <x-dl::card title="Notes" icon="fa-circle-info">...</x-dl::card>
    </x-dl::form.col>
</x-dl::form.row>
```

## Related

- `x-dl::form.row`: equal-width columns; its cells are often `form.col`.
- `x-dl::form`: the shell; the plain form's two-column body is what puts two `form.col` side by side.
- `x-dl::card`: a titled, bordered block when a plain column is not enough.
- `x-dl::field.text` and the other `x-dl::field.*` objects: the content of a column.
- `skills/devlib/form.md`: the project form layout rules.
