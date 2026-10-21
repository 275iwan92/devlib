## Overview

`<x-dl::field.slider>` lets the user pick a number by dragging a handle instead of typing. It has one handle (a single value) or, with `range`, two handles (a from-to range). It can be horizontal or vertical, shows an optional scale (ticks) under or next to the track, and always shows the current value as text, built from a `format` template (`IDR {v}`, `{v}%`, `{v} gsm`). Typical uses: budget or price ranges, tolerances, percentages, ink level.

Files: `src/Components/Field/Slider.php`, the view `resources/views/components/field/slider.blade.php`, the class `Slider` in `resources/assets/js/components/slider.js` (selector `[data-dl-slider]`) and the styles `.dl-slider*` in `resources/assets/devlib.css`. The same class and view serve `x-dl::field.date-slider`, which extends `Slider` and sets `kind()` to `date` (days as numbers, ISO dates posted).

### What it is not

- Not an exact-entry control: for a precise number use `x-dl::field.number` or `x-dl::field.decimal`.
- Not optional: the slider always holds a value (it starts at `min`, or `value`), so it can never be "empty" and `required` has no effect.
- Not a validator: the browser clamps and snaps to `min`/`max`/`step`; the server must still validate.

## Props

Constructor of `Devlib\Components\Field\Slider` (24 props: 10 own, 14 inherited). Blade names are kebab-case; use `:prop="expr"` for numbers and booleans (`:max="100000000"`, `range` alone means `true`).

### Own props

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `min` | `int\|float`, `0` | Lowest value (`min` of the range input). |
| `max` | `int\|float`, `100` | Highest value (`max` of the range input). |
| `step` | `int\|float`, `1` | Step of the range input; values are snapped to `min + n * step` by the browser. |
| `format` | `string`, `'{v}'` | Template of the value text (`data-format`). The first `{v}` is replaced by the number written in the display language with thousand separators and at most 3 decimals (`15.000.000`). Does not affect the posted value and the tick labels. |
| `range` | `bool`, `false` | Two handles. Adds the class `is-range`, a second range input and a second hidden field. The value text becomes `from – to`. |
| `value-to` | `mixed`, `null` | Initial value of the second handle (range only); `null` = `max`. |
| `name-to` | `?string`, `null` | Name of the hidden field of the second handle; `null` = `<name>_to`. |
| `orientation` | `string`, `'horizontal'` | `horizontal` or `vertical` (class `dl-slider--<value>`). Any other word has no style and looks horizontal. In the vertical slider the higher value is at the top. |
| `height` | `string`, `'180px'` | Length of a vertical slider (CSS variable `--dl-slider-h`, any CSS length). Ignored when horizontal. |
| `ticks` | `int`, `0` | Number of scale labels under (horizontal) or beside (vertical) the track, evenly from `min` to `max`. Less than 2 = no scale. Labels are abbreviated (see Behavior). |

### Inherited props

All 14 come from `Devlib\Components\Field\Field`; the full reference is on `x-dl::field.text`. How this control uses them:

| Prop | Use in this control |
|---|---|
| `name` | `name` of the hidden input that carries the (first) value; the range inputs have no name. `data-dl-field` on the root and the id slug. |
| `label` | Label text (`for` = id of the first range input) and the `aria-label` of the handles: the label, and for `range` `label (from)` / `label (to)`. Empty string = no label row and an empty `aria-label`. Translated. |
| `tooltip` | The (i) bubble next to the label (needs a label). Translated. |
| `hint` | Help line under the control. Translated. |
| `error` | Server error text; non-empty marks the field invalid. Not translated. |
| `value` | Initial value (first handle); `null` = `min`. Pass a number (a numeric string such as `'25'` also works). The browser clamps it to `min..max`. An empty or non-numeric string is treated like `null` (`min`). |
| `placeholder` | Not used by this control. |
| `required` | Red `*` only. The slider always has a value, so the form check can never fail. |
| `disabled` | Disables the range inputs (greyed handles). The hidden inputs stay enabled (see the oddity). |
| `readonly` | Also disables the range inputs (a range input has no read-only state), so it looks and behaves like `disabled`, but the value is posted. Root gets `is-readonly`. Forced on while `Field::$readonlyAll` is true. |
| `icon` | Not used by this control. |
| `size` | `sm` (default) or `lg`; only the CSS class `dl-field--sm` / `dl-field--lg`. |
| `i18n` | `data-i18n` key on the label for auth screens; normal screens never set it. |
| `source` | Free text for the developer catalog; default `input "<name>"`. No visual effect. |

## Behavior

### Markup

```html
<div class="dl-field dl-field--slider dl-field--sm" data-dl-field="price">
  <div class="dl-slider dl-slider--horizontal is-range" data-dl-slider data-format="IDR {v}" data-kind="number" data-start="" style="--dl-slider-h:180px">
    <input type="hidden" name="price" data-dl-slider-a>
    <input type="hidden" name="price_to" data-dl-slider-b>
    <div class="dl-slider__track">
      <div class="dl-slider__rail"><div class="dl-slider__fill"></div></div>
      <input id="dlf-price-xxxxx" type="range" class="dl-slider__input" min="0" max="5000000" step="50000" value="750000" aria-label="Price range (from)">
      <input type="range" class="dl-slider__input" min="0" max="5000000" step="50000" value="2500000" aria-label="Price range (to)">
    </div>
    <div class="dl-slider__ticks"><span style="--p:0">0</span>…</div>
    <span class="dl-slider__value" data-dl-slider-value></span>
  </div>
  <div class="dl-field__error" data-dl-field-error></div>
</div>
```

Extra HTML attributes go on the root `div.dl-field`.

### Script (`Slider` class)

On mount the class finds the range inputs, the hidden inputs and the value text, then runs `u()` once and again on every `input` event of a range input:

- **Crossing**: with `range`, if the first handle is above the second, the handle being dragged stops at the other one (the second handle cannot go below the first and the first cannot pass the second).
- **Fill**: the CSS variables `--a` and `--b` (percent positions) paint the coloured part of the rail; the first handle is raised above the second when both are at the far end, so they can be separated again.
- **Hidden values**: `data-dl-slider-a` (and `-b`) are set to the plain number (`750000`), or to an ISO date for the date slider. These are the only inputs posted. They are written at mount, so they always hold a value, even if the user never touches the slider.
- **Text**: `data-dl-slider-value` shows the `format` text; a range shows `IDR 750.000 – IDR 2.500.000`.

In a single slider the whole track is clickable; in a range slider only the handles are (the inputs overlap and the track ignores the pointer). The native keyboard behavior of `<input type="range">` applies (arrow keys, Home, End, Page Up/Down).

### Tick labels

`tickList()` is computed in PHP when the page is rendered: positions in percent and labels from `Slider::compact()`, which abbreviates by the display language of the server (Indonesian `rb`, `jt`, `M`, `T`; other languages `K`, `M`, `B`, `T`; at most one decimal). The labels do not use `format`, are not recomputed by the script and do not follow a language switch without reloading. Note that `M` means milliar (billion) in Indonesian and million in English.

### Read-only, disabled, locked

`disabled` and `readonly` both disable the range inputs. Inside a locked `x-dl::form` the fieldset is disabled, which also stops the post of the hidden inputs.

### Language and number format

Only the value text and tick labels are formatted (`1.250.000` or `1,250,000`; `NUM` is frozen at page load). The posted numbers are always plain JavaScript numbers with `.` as decimal separator.

## Data and events

### What the server receives

| Field | Value |
|---|---|
| `<name>` | Value of the first handle, for example `15000000` or `0.5`. |
| `<name-to>` / `<name>_to` | Value of the second handle (range only). |

Both are text; read them as numbers and check `min`, `max`, step and that `from <= to` on the server. A date slider posts ISO dates (`YYYY-MM-DD`).

### Markup hooks

| Hook | Where |
|---|---|
| `data-dl-slider`, `data-format`, `data-kind`, `data-start` | root `.dl-slider` (selector of the `Slider` class; `kind` is `number`, `start` is empty) |
| `.dl-slider__input` | the range input(s); first has the field id |
| `data-dl-slider-a`, `data-dl-slider-b` | hidden inputs that are posted |
| `data-dl-slider-value` | the value text |
| `.dl-slider--vertical`, `.is-range` | orientation and range classes |

### Events and JavaScript

- No `dl:*` event. `el.dlComponent` on the `.dl-slider` element is the `Slider` instance (`ia`, `ib`, `ha`, `hb`, `out`; methods `u()`, `show()`, `pct()`); on the outer `.dl-field` it is the shared `Field` instance.
- The range inputs emit the native `input` (while dragging) and `change` (on release) events, which bubble to the field. The hidden inputs are written by script and emit nothing. A listener on the root sees the updated hidden values, because the slider handles the event first.
- The range inputs have no `name`, so moving a slider does **not** mark an `x-dl::form` dirty and the Undo button stays "nothing to undo".
- To set a value from script, set the range input's `value` and dispatch `input` on it.

### Accessibility as implemented

Each handle is a native range input, so role, keyboard operation and (in most browsers) value announcement come from the browser. The `<label for>` points to the first range input. The announced value is the raw number: there is no `aria-valuetext`, so the formatted text (`IDR 750.000`) is not spoken, and the value text is not an `aria-live` region.

## Rules and pitfalls

- Pass `value` and `value-to` as numbers (`:value="750000"`). The browser clamps them to `min..max` and snaps them to the step.
- Use a `step` that divides `max - min`, otherwise the top value cannot be reached.- A slider always posts a value. Do not use it where "not answered" is a valid state; use a number field with an empty option instead.
- `format` only changes the display. The tick labels have their own abbreviation rule and the language of the server request.
- A readonly slider is a disabled one: it looks greyed. Show a calculated value with `x-dl::field.decimal` `readonly` if it should look like text.
- `ticks` of 5 or 6 reads well on a normal width; labels of many digits crowd on a narrow screen.
- Use `name-to` when the second value has a name of its own in your model (`:name-to="'price_max'"`).
- Code oddity (not fixed): (1) `slider.blade.php:6-7` gives the range inputs no `name`, and `slider.js` writes the hidden inputs without an event, so the dirty guard never sees a change (no leave warning, Undo disabled). (2) `slider.blade.php:2-3`: the hidden inputs are not disabled by `disabled`, so a disabled slider is still posted. (3) `Devlib.fill()` / Undo (`core.js:663-677`) set only the first hidden input, so the handle and text keep the old position while the restored value is posted.

## Example

```blade
<x-dl::form.row :cols="2">
    <x-dl::field.slider name="budget" label="Monthly budget (one value)" :max="100000000" :step="1000000" :value="15000000" format="IDR {v}" :ticks="5" />
    <x-dl::field.slider name="price" label="Price range (two handles)" range :max="5000000" :step="50000" :value="750000" :value-to="2500000" format="IDR {v}" :ticks="6" />
</x-dl::form.row>
```

```blade
{{-- paper weight range, own name for the second value --}}
<x-dl::field.slider name="gsm_min" name-to="gsm_max" label="Paper weight" range :min="60" :max="400" :step="10"
    :value="old('gsm_min', 150)" :value-to="old('gsm_max', 310)" format="{v} gsm" :ticks="5" />
```

```blade
{{-- vertical, 220px high --}}
<div class="dl-row" style="align-items:flex-start;gap:40px">
    <x-dl::field.slider name="ink" label="Ink left" orientation="vertical" height="220px" :value="60" format="{v}%" :ticks="5" />
    <x-dl::field.slider name="temp" label="Room temperature" orientation="vertical" range :min="15" :max="35" :value="20" :value-to="26" format="{v} °C" :ticks="5" />
</div>
```

## Related

- `x-dl::field.date-slider`: the same control for dates (single date or range, ISO values).
- `x-dl::field.rating`: pick a score with stars.
- `x-dl::field.number`: exact whole number with spin buttons.
- `x-dl::field.decimal`: exact decimal number with a unit.
- `x-dl::field.text`: full reference of the 14 inherited props and the shared field wrapper.
- `x-dl::form`: required-field check, dirty guard and Undo.
