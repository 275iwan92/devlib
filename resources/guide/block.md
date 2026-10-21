## Overview

`<x-dl::block>` is a titled box made of a native `<fieldset>` + `<legend>`: a thin rounded border with the legend sitting on the top edge. Its one job is to group fields that belong together and can be locked together, because disabling a `<fieldset>` disables every control inside it (the original use is a "revise specification" screen with blocks such as JOIN, ARTWORK, DIMENSION, MATERIAL, COLOR, PROCESS where each block can be switched on or off as a whole).

Pick another object when:

- the group is a visual section with a header, description, icon or action buttons: use `x-dl::card`;
- you only need a neutral wrapper element (hook, print id, page class) with no frame: use `x-dl::box`;
- the group is a normal section of a form: put fields in `x-dl::form.row` / `x-dl::form.col` inside `x-dl::form` (the form already locks its own fieldsets by status);
- you need two blocks side by side: wrap them in `x-dl::cols`.

Generated markup:

```html
<fieldset class="dl-block" disabled data-dl="x-dl::block" data-dl-class="Block ← DevlibComponent" data-dl-src="—">
  <legend>Legend text</legend>
  ...slot...
</fieldset>
```

Extra attributes (`id`, `class`, `style`, `data-*`, `disabled`, `form`) are merged onto the `<fieldset>`; a `class` you pass is appended to `dl-block`. `can="module.action"` is also accepted: without the ability nothing is rendered.

## Props

Constructor of `Devlib\Components\Block` (1 prop). Blade names are kebab-case.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `legend` | `string`, `''` | Text of the `<legend>`. Written in English in the screen source and escaped with `{{ }}`. It is NOT run through the dictionary (see the oddity below), so a translated legend must be passed already translated. An empty string still renders an empty `<legend>`. |

There is no `disabled` prop: use the plain HTML attribute `disabled` (or `:disabled="$locked"`); it is passed through to the `<fieldset>`.

## Behavior

### Locking

- `disabled` on the block disables every control inside it: grayed, not focusable, and NOT submitted with the form (the same native rule as `disabled` on `x-dl::field.*`). The CSS `.dl-block:disabled` also draws the whole block at 55% opacity on the alternate surface color.
- A `readonly` field inside a disabled block is still disabled, so it is not posted. If the value must reach the server, do not lock the block with `disabled`; set `readonly` on each field instead.
- Nested fieldsets follow HTML: any disabled ancestor (a block, or a locked `x-dl::form` section) disables the content.

### Active state `is-on`

The CSS defines `.dl-block.is-on` (accent-tinted border and a 5% accent background) to mark a block that is currently active. DevLib itself never sets this class: no file in `resources/assets/js` toggles it (see the oddity). A project that wants it writes `class="is-on"` on the server or toggles it from its own script file.

### Layout

`border: 1px solid var(--dl-border)`, radius 10px, padding `8px 12px 10px`, `margin-top: 10px`, `min-width: 0` (a wide table inside does not stretch the parent). The legend is bold 12.5px in the strong ink color with 6px side padding. There is no gap rule inside the block: children follow normal block flow, so put `x-dl::form.row` (or another layout object) inside to space fields.

### Language and permissions

- The only text is `legend`; the component has no built-in screen text.
- `can="module.action"` hides the whole block (and everything in it) when `Devlib\Access::can()` says no. This is a render-time rule only; the server must still check permissions.

### Accessibility

A real `<fieldset>`/`<legend>`: screen readers announce the group name when entering a control, and a disabled fieldset is announced as unavailable by the browser. No `aria-*` attributes are added.

## Data and events

- Server: nothing is posted for the block itself. Its enabled fields post as usual; its disabled fields post nothing.
- Events: the component emits no `dl:*` event and has no JS class (`el.dlComponent` is not set). Native `input` / `change` of inner fields bubble through the block to the form, so the dirty guard of `x-dl::form` works normally.
- Markup hooks: `.dl-block`, `.dl-block legend`, `.dl-block.is-on`, `.dl-block:disabled`. Nothing in the library queries a `data-dl-block` hook.
- Toggling from a project script is plain DOM: `block.disabled = true|false` and `block.classList.toggle('is-on')`.

## Rules and pitfalls

- Do not use `x-dl::block` as a generic border box: it is a form group. For read-only information use `x-dl::card`.
- `disabled` means "not posted". Do not disable a block and still expect its values on the server.
- The legend is not translated by DevLib. On a multi-language screen pass the translated text (for example `legend="{{ \Devlib\Lang::text('Dimension') }}"`) and wrap data values with `raw()`.
- The `margin-top: 10px` is part of the style: stack blocks directly, do not add spacer elements. To place blocks side by side use `x-dl::cols`.
- A block has no collapse behavior; use `x-dl::accordion` if the group must fold.
- No lint rule targets `x-dl::block`. The general rules of `devlib:lint` apply to the view that contains it: L001 (raw `table|button|input|select|textarea|form` tags: use `x-dl::*`), L002 (inline `<script>`: the toggle script must be a project JS file), L003 (hex colors: use theme tokens).
- Code oddity (not fixed): `src/Components/Block.php:5` (REMARK header) says `legend` is "diterjemahkan", but `legend` is not in `DevlibComponent::TEXT_PROPS` (`src/Components/DevlibComponent.php`), so the legend is never translated automatically.
- Code oddity (not fixed): `src/Components/Block.php:8` says `is-on` is set by `revise-form.js` and names `screens/inquiry-revise` as the user. Neither exists in this repository (no `revise-form.js`, no demo uses `x-dl::block`), so `.dl-block.is-on` (`resources/assets/devlib.css:3803`) is styled but never applied by the library.

## Example

No catalog demo uses this object yet; the example follows the class contract (fieldset + legend, `disabled` passed through) with fictional data: a revision screen where the material block stays locked until it is opted in.

```blade
<x-dl::cols>
    <x-dl::block legend="DIMENSION" class="is-on">
        <x-dl::form.row :cols="3">
            <x-dl::field.text name="length" label="Length" code :case-toggle="false" value="120" />
            <x-dl::field.text name="width" label="Width" code :case-toggle="false" value="80" />
            <x-dl::field.text name="height" label="Height" code :case-toggle="false" value="45" />
        </x-dl::form.row>
    </x-dl::block>

    <x-dl::block legend="MATERIAL" :disabled="! $reviseMaterial">
        <x-dl::form.row>
            <x-dl::field.text name="material" label="Material" value="Corrugated board" />
            <x-dl::field.text name="flute" label="Flute" value="B" />
        </x-dl::form.row>
    </x-dl::block>
</x-dl::cols>
```

## Related

- `x-dl::cols`: puts blocks (or cards) side by side and stacks them on narrow screens.
- `x-dl::card`: titled section with description, icon and action slot; no lock behavior.
- `x-dl::box`: neutral wrapper without style.
- `x-dl::form`, `x-dl::form.row`, `x-dl::form.col`: form shell and layout; the form locks its own fieldsets by status.
- `x-dl::field.text` and the other `x-dl::field.*`: the fields placed inside a block; `disabled` / `readonly` semantics are in `field/text.md`.
- `x-dl::accordion`: when the group must fold.
