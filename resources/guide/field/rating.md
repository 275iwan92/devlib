## Overview

`<x-dl::field.rating>` is a star rating: a row of stars (5 by default) that the user clicks to give a score. With `half` the left half of a star gives a half point (3.5). Clicking the same score again clears it. A text such as `3,5 / 5` is shown at the right of the stars. As a read-only display it shows a stored score without any interaction (reviews, quality scores, supplier ratings).

Files: `src/Components/Field/Rating.php`, the view `resources/views/components/field/rating.blade.php`, the class `Rating` in `resources/assets/js/components/rating.js` (selector `[data-dl-rating]`) and the styles `.dl-rating*` in `resources/assets/devlib.css`.

### What it is not

- Not a "required answer" control: the score `0` means "no rating", is posted as `0` and is never empty, so the form's required check cannot fail.
- Not a keyboard control: the stars are plain `span` elements (see Accessibility).
- Not a validator: clicks can only produce scores from `0` to `max`, but the initial value is not limited and the server must check the score again.

## Props

Constructor of `Devlib\Components\Field\Rating` (16 props: 2 own, 14 inherited). Blade names are kebab-case; use `:prop="expr"` for numbers and booleans (`:max="10"`, `:value="3.5"`; `half` alone means `true`).

### Own props

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `max` | `int`, `5` | Number of stars; also `data-max`, `aria-valuemax` and the denominator of the text (`3 / 5`). |
| `half` | `bool`, `false` | Allows half points: a click on the **left half** of a star gives `i - 0.5` (`data-half="1"`). Without it every click gives a whole number. |

### Inherited props

All 14 come from `Devlib\Components\Field\Field`; the full reference is on `x-dl::field.text`. How this control uses them:

| Prop | Use in this control |
|---|---|
| `name` | `name` of the hidden input that carries the score. No name = nothing is posted. |
| `label` | Label text. Empty string = no label row. Translated. The label has no `for`; the root is tied to it with `aria-labelledby` (see Accessibility). |
| `tooltip` | The (i) bubble next to the label (needs a label). Translated. |
| `hint` | Help line under the control. Translated. |
| `error` | Server error text; non-empty marks the field invalid. Not translated. |
| `value` | Initial score, cast with `(float)` into the hidden input and `aria-valuenow`: `3`, `3.5`; `null`, text or `''` becomes `0`. Pass a number. Not limited to `0..max` at render. |
| `placeholder` | Not used by this control. |
| `required` | Red `*` only. The score is never empty (`0` is a value), so the form check never marks it. |
| `disabled` | Same look and behavior as `readonly` (class `is-readonly`, no click handlers). `disabled` also disables the hidden input, so nothing is posted; `readonly` still posts the score. |
| `readonly` | Display only: stars are painted but no interaction is attached; the score is posted. The root also gets `is-readonly`. Forced on while `Field::$readonlyAll` is true. |
| `icon` | Not used by this control. |
| `size` | `sm` (default) or `lg`: the CSS class `dl-field--<size>` and `dl-rating--<size>`; `lg` makes the stars bigger (30px, row 40px). |
| `i18n` | `data-i18n` key on the label for auth screens; normal screens never set it. |
| `source` | Free text for the developer catalog; default `input "<name>"`. No visual effect. |

## Behavior

### Markup

```html
<div class="dl-field dl-field--rating dl-field--sm" data-dl-field="quality">
  <div class="dl-field__labelrow">…label…</div>
  <div class="dl-rating dl-rating--sm" data-dl-rating data-max="5" data-half="1"
       role="slider" aria-labelledby="dlf-quality-xxxxx-lbl" aria-valuemin="0" aria-valuemax="5" aria-valuenow="3.5">
    <input type="hidden" name="quality" value="3.5" data-dl-value>
    <span class="dl-rating__star" data-value="1">★</span> … <span class="dl-rating__star" data-value="5">★</span>
    <span class="dl-rating__text" data-dl-rating-text></span>
  </div>
  <div class="dl-field__error" data-dl-field-error></div>
</div>
```

### Script (`Rating` class)

| Moment | What happens |
|---|---|
| Mount | The stars are painted from the hidden value: star `i` is active when `i <= value`, and a **half star** is drawn when `value = i - 0.5`; the text is set to `<value> / <max>` and `aria-valuenow` is updated. A read-only rating stops here: no listeners. |
| Hover over a star | The stars preview the score under the pointer (left half = half point when `half`). Leaving the control repaints the stored score. |
| Click | The score is the star number, or `i - 0.5` for a click on the left half with `half`. Clicking the **same score again** sets `0` (clear). The hidden input, stars, text and `aria-valuenow` are updated and a bubbling `change` is dispatched on the hidden input. |

A half star is painted for any half value, also when `half` is off (an initial `3.5` shows a half star; the next click gives a whole number).

The text always uses a comma as decimal separator (`3,5 / 5`), also in English.

### Read-only, disabled, locked

`readonly` and `disabled` render the same display-only rating. Inside a locked `x-dl::form` the fieldset disables the hidden input (so nothing is posted) but the stars are not form controls: they stay clickable and repaint. Pass `readonly` yourself for locked screens (`:readonly="$locked"`).

### Language and number format

No number formatting; the posted score is always `.`-decimal (`3.5`). The `3,5 / 5` text is not translated or localised.

## Data and events

### What the server receives

`name` = the score as text: `0` (no rating), `3`, `3.5`, ... Always present, never empty. Treat `0` as "not rated" and check `0 <= score <= max` (and whole numbers when `half` is off) on the server.

### Markup hooks

| Hook | Where |
|---|---|
| `data-dl-rating`, `data-max`, `data-half` | root `.dl-rating` (selector of the `Rating` class) |
| `data-dl-value` | the hidden input |
| `.dl-rating__star[data-value]` | one per star; `.is-active` and `.is-half` mark the painted state |
| `data-dl-rating-text` | the `score / max` text |
| `.is-readonly` | display-only state of the rating (and of the field wrapper for `readonly`) |

### Events and JavaScript

- No `dl:*` event. A bubbling native `change` is dispatched on the hidden input at each click; hover and mount fire nothing. There is no `input` event, so the "Required"/server error mark of the form is not cleared by a click.
- `el.dlComponent` on the `[data-dl-rating]` element is the `Rating` instance (`stars`, `hidden`, `max`, `half`; methods `paint(v)`, `text(v)`, `at(star, event)`); on the outer `.dl-field` it is the shared `Field` instance. There is no supported setter: to change the score from script, set the hidden value and call `paint(v)` and `text(v)`.
- The hidden input is named, so a click marks an `x-dl::form` dirty (leave warning, Undo enabled).
- `Devlib.fill()` / Undo write the hidden input only; the stars and text are not repainted (see the oddity).

### Accessibility as implemented

The root has `role="slider"` with `aria-valuemin="0"`, `aria-valuemax` and `aria-valuenow` (kept in step with each click), and an accessible name through `aria-labelledby` (the field label, when `label` is not empty), but no `tabindex` and no key handling: the control cannot be focused or operated with the keyboard. The label has no `for`, so clicking it does nothing. The stars are the character `★` in spans. A read-only rating is readable (`aria-valuenow`), but not announced with a name.

## Rules and pitfalls

- A rating is optional by nature: validate on the server that `0` means "not rated", not as a score of zero.
- `required` does nothing here. If the user must rate, check `score > 0` on the server and show `:error`.
- Give `max` and `value` consistently: `:max="10" :value="7"` for a 10-point scale. A value above `max` paints all stars and is posted unchanged.
- Use `half` only when the data can hold halves (a `DECIMAL(2,1)` column or similar); without it, store an integer.
- For a stored rating shown in a list or on a read-only page, pass `readonly` (and `size="lg"` when it is the main content), not `disabled`.
- Do not rely on the label click or the keyboard; if keyboard entry is a requirement, offer `x-dl::field.number` (0..max) or `x-dl::field.select` as the accessible alternative.
- Code oddity (not fixed): (1) The root is `role="slider"` without `tabindex`/keys: a mouse-only control that declares itself a slider. (2) `rating.js:22` formats the text with a fixed comma (`3,5 / 5`) instead of `NUM.dec`. (3) In a disabled fieldset the stars stay clickable (`rating.js` has no check on the fieldset). (4) `Devlib.fill()` / Undo (`core.js:663-677`) set the hidden input only, so the stars and the text keep showing the old score while the restored value is posted.

## Example

```blade
<x-dl::form.row :cols="2">
    <x-dl::field.rating name="rating" label="Rating" :value="3" />
    <x-dl::field.rating name="quality" label="Half stars (click the left half of a star)" :value="3.5" half />
</x-dl::form.row>
```

```blade
{{-- display only, large --}}
<x-dl::field.rating name="review" label="Customer review" :value="4.5" half readonly size="lg" />
```

```blade
{{-- 10-point scale, restored after a failed save, error under the field --}}
<x-dl::field.rating name="score" label="Score" :max="10"
    :value="old('score', $supplier->score ?? 0)" :error="$errors->first('score')" hint="Click the same star again to clear." />
```

## Related

- `x-dl::field.slider`: pick a number by dragging; keyboard-operable, always has a value.
- `x-dl::field.number`: exact whole number with spin buttons.
- `x-dl::field.select`: choose a score from a list.
- `x-dl::field.text`: full reference of the 14 inherited props and the shared field wrapper.
- `x-dl::form`: required-field check, dirty guard and Undo.
