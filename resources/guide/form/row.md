## Overview

`<x-dl::form.row>` puts form fields side by side. It is a plain CSS grid with `cols` equal-width columns; every child (field, button, `x-dl::form.col`, any markup) becomes one cell, and when there are more children than `cols` the grid wraps to a new line. It has no JavaScript, no translated text and no data of its own.

Use it inside `x-dl::form` (a tab slot of a panel form), inside a `x-dl::card` or inside an `x-dl::action-form` to lay out 2 to 4 fields per line. Pick another object when:

- the fields only need to stack vertically: just put them one after another, or inside `x-dl::form.col`;
- each column holds several stacked fields (a "left block / right block" layout): use one `x-dl::form.row` whose children are `x-dl::form.col`;
- the layout is a whole page grid or a dashboard: use the grid/board objects, not a form row.

### Generated markup

```html
<div class="dl-form__row" data-dl="x-dl::form.row cols=3" data-dl-class="Row ← DevlibComponent" data-dl-src="—" style="--dl-cols:3">...</div>
```

CSS (`resources/assets/devlib.css`): `.dl-form__row{display:grid;grid-template-columns:repeat(var(--dl-cols,2),1fr);gap:8px}`. Inside a panel form (`.dl-form--panel`) the gap is `6px 10px`. A `x-dl::button` placed directly in the row is aligned to the bottom/start of its cell (`.dl-form__row > .dl-btn{align-self:end;justify-self:start}`), so an action button lines up with the inputs next to it.

## Props

Constructor of `Devlib\Components\Form\Row` (1 prop).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `cols` | `int`, `2` | Number of equal columns. Written to the inline custom property `--dl-cols` that the CSS `repeat()` reads. Use `:cols="3"` or the string form `cols="3"` (a numeric string is cast to int); a non-numeric string throws a `TypeError`. There is no validation of the range: practical values are 1 to 4. `0` or a negative number makes the `repeat()` declaration invalid, so the children stack in a single column. All columns have the same width; the component has no per-column width, span or alignment option. |

Also accepted, as on every DevLib object: `can="module.action"` (without the ability the row and its content are not rendered) and any HTML attribute (`id`, `class`, `data-*`), merged into the root `div`.

## Behavior

- Layout only. The row does not know its children: fields keep their own labels, errors and required flags, and `x-dl::form` finds them anywhere inside its fieldsets, rows included.
- Rows stack top to bottom; use several rows for several lines. Two rows with different `cols` are independent grids, their columns do not line up.
- Cells are `1fr` wide: they share the available width. A wide child (a table, a long unbreakable text) can push its column wider than the others.
- There is no responsive rule for `.dl-form__row`: on a phone `cols="3"` stays three narrow columns. Only the two-column body of a plain `x-dl::form` collapses at 700px (`.dl-form__body`). Keep rows at 2 columns on screens that must work on small devices, or put fewer fields in a row.
- Placement matters in a plain (non-panel) `x-dl::form`: its body is itself a two-column grid (`.dl-form__body{grid-template-columns:1fr 1fr}`) and only tabs and `x-dl::lines` span the full width (`devlib.css:2174`), so a row placed directly there occupies one half of the body. In a panel form each tab slot is a normal block and the row takes the full width.
- No keyboard or accessibility behavior of its own (a `div` without role). Tab order follows the DOM order of the children, row by row.
- Language and permissions: the row has no screen text. `can="module.action"` hides the whole row (and its fields) when `Access::can` fails; the server must still check the right.

## Data and events

- Posts nothing and fires no event. The fields inside post as usual, in DOM order.
- No `dl:*` custom event, no JS class (`el.dlComponent` is not set). The only hooks are the class `dl-form__row` and the custom property `--dl-cols`.
- `data-dl`, `data-dl-class` and `data-dl-src` on the root are the marks used by the developer catalog and the Technical Guide title links; `data-dl-src` is always `—` (no data source).

## Rules and pitfalls

- Required fields stay required: mark each field `required`, the row does not.
- Put 2 or 3 short fields in a row (code, date, status) and give long fields (name, address, remark) their own full-width line; do not squeeze a multiline text or a table into a 3-column row.
- In a plain form, wrap rows in `x-dl::form.col` or use a panel form with tabs when you need full-width rows (see Behavior).
- Do not pass a `style` attribute to the row if you rely on `cols`: see the code oddity below.
- No lint rule applies to this object (the `F1xx` rules in `Devlib\Lint\FormRules` check `x-dl::form` only).
- Code oddity (not fixed): `src/Components/Form/Row.php:32` prints `{{ $attributes->class('dl-form__row') }}` and then a second `style="--dl-cols:..."` on the same `div`. When the caller also passes `style="..."`, the element gets two `style` attributes and browsers keep only the first, so `--dl-cols` is lost and the row falls back to the CSS default of 2 columns. The same pattern exists in `src/Components/Drawer.php:36` (`--dl-drawer-w`).

## Example

Taken in shape from the catalog demos `form-single` (a panel form tab) and `card-actionbar`:

```blade
<x-dl::form.row :cols="3">
    <x-dl::field.text name="employee_code" label="Employee code" placeholder="(automatic)" code readonly :case-toggle="false" hint="Made on the first Save." />
    <x-dl::field.text name="full_name" label="Full name" required />
    <x-dl::field.text name="nickname" label="Nickname" />
</x-dl::form.row>
<x-dl::form.row>
    <x-dl::field.lookup name="supplier_id" lookup="supplier" label="Supplier" />
    <x-dl::field.date name="tgl" label="PO date" value="2026-09-23" />
</x-dl::form.row>
<x-dl::field.text name="remark" label="Remark" multiline />
```

## Related

- `x-dl::form.col`: one vertical column; use it as a cell of a row when a cell must hold several stacked fields.
- `x-dl::form`: the shell that holds rows (tab slots of a panel form); its required check and dirty guard see the fields inside rows.
- `x-dl::field.text`, `x-dl::field.date`, `x-dl::field.select` and the other `x-dl::field.*` objects: the usual cells.
- `x-dl::card` and `x-dl::action-form`: other containers that hold rows.
- `x-dl::action-bar`: the button row at the foot of a card or form.
- `skills/devlib/form.md`: the project form layout rules.
