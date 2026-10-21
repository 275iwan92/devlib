## Overview

`<x-dl::field.color>` is a colour picker: the browser's native colour button plus the chosen HEX code printed next to it (`#2563EB`). It always has a colour; it posts one `#rrggbb` string. Use it for a theme or label colour setting. There is no alpha channel, no palette and no "empty" state.

It extends the abstract `Devlib\Components\Field\Field` (shared frame: caption row, control, error line, hint line) and inherits the 14 common props. It has no own props. The view is `resources/views/components/field/color.blade.php`; the code label is kept in step by the `data-dl-color` branch of the shared `Field` class in `resources/assets/js/components/field.js`.

## Props

Constructor of `Devlib\Components\Field\Color` (14 props, all inherited; no own props). Blade names are kebab-case; use `:prop="expr"` for anything that is not a plain string.

### Own props

None.

### Inherited props

Full reference: `x-dl::field.text`. What they mean for this control:

| Prop | Type, default | Effect here |
|---|---|---|
| `name` | `string`, `''` | `name` of the `<input type="color">`, `data-dl-field` on the root, slug of the generated id. |
| `label` | `string`, `''` | Caption above the control; `<label for>` points at the colour input id, so clicking the caption focuses it. Empty = no caption row, and then no `*` and no tooltip. |
| `tooltip` | `?string`, `null` | (i) bubble next to the caption; only with a non-empty `label`. |
| `hint` | `?string`, `null` | Help line under the control; rendered whenever not `null`. |
| `error` | `?string`, `null` | Server error text; non-empty adds `is-invalid` on the root (message only, no bordered box). |
| `value` | `mixed`, `null` | The colour, written to the input `value` and, upper-cased, to the code label. Must be a 7-character `#rrggbb` string. `null` falls back to `#2563eb` (the theme blue); the fallback is what gets posted when the user never touches the picker. |
| `placeholder` | `?string`, `null` | Not used by this control. |
| `required` | `bool`, `false` | Red `*` after the caption, but the form validation can never fail: a colour input always holds a non-empty value, so `hasValue()` is always true. Not an HTML `required`. |
| `disabled` | `bool`, `false` | `disabled` on the colour input; it cannot be opened and nothing is posted. |
| `readonly` | `bool`, `false` | The colour input is rendered `disabled` and, when not also `disabled`, a hidden `<input name="<name>">` with the normalised initial value (or the default blue) is added so the colour is still posted. Root gets `is-readonly`. Forced on while `Field::$readonlyAll` is true. |
| `icon` | `?string`, `null` | Not used by this control. |
| `size` | `string`, `'sm'` | Only changes the root class `dl-field--sm` / `dl-field--lg`. |
| `i18n` | `?string`, `null` | Written to `data-i18n` on the caption `<label>`. |
| `source` | `?string`, `null` | Label for the developer catalog (`source()`); default `input "<name>"`. No visual effect. |

Other HTML attributes (`id`, `class`, `data-*`) and `can="module.action"` go on the root `.dl-field` div.

## Behavior

### Markup

```html
<div class="dl-field dl-field--color dl-field--sm" data-dl-field="warna">
  <div class="dl-field__labelrow"><label class="dl-field__label" for="dlf-warna-xxxxx">Favourite colour</label></div>
  <div class="dl-color">
    <input id="dlf-warna-xxxxx" name="warna" type="color" class="dl-color__btn" value="#2563eb" data-dl-color>
    <span class="dl-color__code" data-dl-color-code>#2563EB</span>
  </div>
  <div class="dl-field__error" data-dl-field-error></div>
</div>
```

The colour button is 30 x 26 px; the code is shown in a bold monospace span.

### Code label

On every `input` event of the colour input the shared `Field` script sets `[data-dl-color-code]` to `input.value.toUpperCase()`. `input` fires continuously while the user drags inside the browser's picker, so the label follows live; `change` fires when the picker is committed. The label is plain text, not an input: the code cannot be typed. Anything that sets the input through `Devlib.h.setField` (Form Undo, `Devlib.fill`) fires `input`, so the label follows.

### Posted state

One value, always: `warna=#2563eb`, lower-case, as normalised by the browser. An enabled colour input cannot be empty; to allow "no colour" put a separate `x-dl::field.checkbox` or `x-dl::field.toggle` next to it and ignore the colour on the server when it is off.

### Required

`hasValue()` looks at the value of the colour input, which is never empty (a missing or invalid `value` becomes `#000000` in the browser). A required colour therefore always passes client validation.

### Disabled and read-only

`disabled` and `readonly` put `disabled` on the colour input, so the picker cannot be opened. `readonly` keeps the colour in the post through the hidden input (initial value only). In a locked `x-dl::form` the fieldset is disabled and nothing is posted.

### Accessibility as implemented

A native `<input type="color">`: focusable with Tab, opened with Enter or Space, and named by the caption through `<label for>`. The HEX text next to it is plain text outside the label, without `aria-live`. The picker dialog itself is the browser's.

## Data and events

| Item | Detail |
|---|---|
| Posted | `name=#rrggbb` (7 characters, lower-case) while enabled. |
| Read on the server | `$request->input('name')`; validate with `'name' => ['regex:/^#[0-9a-f]{6}$/i']`. |
| DOM events | `input` (continuous while choosing) and `change` (on commit) on the colour input, both bubble. `x-dl::form` uses them for the dirty guard. |
| Fill from code | `Devlib.fill(form, {warna: '#ff0000'})` / `Devlib.h.setField(form, 'warna', v)` sets the value and fires `input`; the code label follows. |
| Wizard review | `fieldText()` falls through to the colour input value, so the review shows the lower-case code. |
| Hooks | `data-dl-color` (input), `data-dl-color-code` (label), classes `dl-color`, `dl-color__btn`, `dl-color__code`. JS branch: `this.color` in `resources/assets/js/components/field.js` (class `Field`). |

## Rules and pitfalls

- Pass `#rrggbb`. `#rgb` (`#fff`) is expanded to `#rrggbb`; anything else the browser would reject (`fff`, `rgb(...)`, a colour name, `#12345`, an empty string) is replaced by the default blue `#2563EB`, so the swatch, the HEX text and the posted value always agree. Convert other notations on the server before passing a value in.
- `null` (and any invalid value) means "use the default blue", not "no colour". A model with a nullable colour column shows `#2563EB`, and the user's untouched form posts that blue; the user cannot clear it.
- `required` has no effect on this control (always passes); do not rely on it. Validate the format on the server.
- `size`, `placeholder` and `icon` change nothing here except the `dl-field--sm|lg` class.
- Restoring after a failed validation: `old('warna', $m->warna)` works as usual, because the colour is always posted.

## Example

```blade
<x-dl::field.color name="warna" label="Favourite colour" value="#2563eb" />
```

```blade
{{-- State from a model, hint and server error --}}
<x-dl::field.color name="badge_color" label="Badge colour" :value="old('badge_color', $tag->color ?? '#16a34a')"
    hint="Shown on the tag in lists." :error="$errors->first('badge_color')" />
```

```blade
{{-- Locked display that still posts the colour --}}
<x-dl::field.color name="badge_color" label="Badge colour" :value="$tag->color" readonly />
```

## Related

- `x-dl::field.text`: full reference of the 14 inherited props, wrapper markup and shared behavior.
- `x-dl::field.select`: choose from a fixed set of named colours instead of a free picker.
- `x-dl::field.toggle` and `x-dl::field.checkbox`: pair with a colour when "no colour" must be possible.
- `x-dl::form`: dirty guard, Undo and locked fieldsets.
