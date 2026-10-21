## Overview

`<x-dl::strip-kv>` is one summary box of the strip at the top of a document form: a small upper-case label over a value. It is used for the document number, the grand total, a date or any key figure that must stay visible while the user works in the tabs below. It replaces the hand-written `<div class="dl-strip__kv"><span>..</span><b>..</b></div>`.

Use it inside the `strip` slot of `x-dl::form` (kind `document`), next to the fields that are also placed in that strip. Pick another object when:

- the value is editable: put an `x-dl::field.*` in the strip instead (fields in the strip stretch to share the row);
- the value is a read-only field inside the form body: `x-dl::field.display`;
- a page-level number tile with trend: `x-dl::kpi` / `x-dl::kpi-row`;
- a status label: `x-dl::badge`.

Generated markup:

```html
<div class="dl-strip__kv [dl-strip__doc | dl-strip__grand]" data-dl="x-dl::strip-kv" data-dl-class="StripKv ← DevlibComponent" data-dl-src="—">
  <span>Label</span><b {value-attrs}>Value</b>
</div>
```

Extra HTML attributes (`id`, `class`, `style`, `data-*`) go on the root `div`. Attributes for the value element go through `value-attrs`. `can="module.action"` is read by the base class: without the ability nothing is rendered.

## Props

Constructor of `Devlib\Components\StripKv` (4 props). Use `:prop="expr"` for non-string values.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `label` | `string`, `''` | The small caption (`<span>`, 10.5px, upper-case by CSS, no wrapping). Translated through the dictionary: write it in English. An empty label leaves an empty `<span>` that still takes space. |
| `value` | `?string`, `null` | The text inside `<b>`. Not translated. Data (numbers, dates, document numbers) is printed as given and escaped, so format it yourself before passing. When it is `null` the default slot is printed instead (use the slot for markup or for a value that is only filled by script). An empty string `''` prints nothing and does NOT fall back to the slot. A number passed with `:value` is converted to a string. |
| `kind` | `string`, `''` | Visual style: `''` (plain box), `doc` (document number: left-aligned, accent-tinted, accent bar on the left, monospace 20px accent text) or `grand` (grand total: solid accent background, white text, 21px, min-width 190px). Any other value gives the plain box. Normally one `doc` and at most one `grand` per strip. |
| `value-attrs` | `array`, `[]` | Attributes written on the `<b>` value element, as `['name' => 'value']` (escaped through Laravel's attribute bag). The main use is `data-l-val` so `x-dl::lines` can keep a total alive: `['data-l-val' => 'lines:grand']`. In Blade write it as `:value-attrs="[...]"`. |

## Behavior

- CSS only (no JavaScript of its own). The box is a flex column, right-aligned for plain and `grand` boxes, left-aligned for `doc`; value uses tabular numbers and does not wrap. Inside a `panel` form the paddings and font sizes are reduced (`.dl-form--panel .dl-strip__kv`).
- Placement is decided by the strip container (`.dl-form__strip`: flex row, wraps, 8px gap). Plain boxes keep their natural width; fields in the same strip grow (`flex:1 1 200px`). The class `dl-strip__gap` exists in CSS as a flexible spacer (`<div class="dl-strip__gap"></div>`) to push the following boxes to the right edge; there is no component for it.
- Locked or view mode: the strip lives in a `fieldset` that the form disables in view/locked mode. A `strip-kv` is not a control, so it is unaffected.
- Live totals: when `value-attrs` has `data-l-val="<lines name>:<key>"`, `x-dl::lines` rewrites the text of that `<b>` after every change. Keys: `grand`, `sub`, `dpp`, `ppn` (currency-formatted), `count` (filled rows) and `sum.<column>` (column total). The `<lines name>` must equal the `name` of the `x-dl::lines` in the same form (it searches inside the enclosing `<form>`). The initial `value` is replaced on first calculation, so give a sensible placeholder such as `IDR 0`.
- Language: only `label` is translated; the value is data.
- Accessibility: plain text in a `div`/`span`/`b`; there is no `role`, and the label is not programmatically tied to the value. The live total is not announced (no `aria-live`).

## Data and events

- Display only: nothing is posted; do not use it to carry a value to the server (use a hidden field or a field). The text is whatever the screen passed in, or the number script wrote.
- No `dl:*` event and no `dlComponent` instance. The only runtime hook is the `data-l-val` attribute described above.
- Source of the data: the screen (document number from the model, totals from the lines script, a date).

## Rules and pitfalls

- Put a data value in `value`, a fixed caption in `label`. A document number that exists only after saving should show a placeholder such as `(otomatis)` for a new record, as in the demos.
- Format numbers and dates before passing them (locale, currency symbol). The component does not format.
- Do not wrap `label` data (customer name) without `raw()`: it is translated and added to the dictionary otherwise.
- Use `kind="doc"` only for the document number and `kind="grand"` only for the total that `x-dl::lines` computes; both are positional highlights, not statuses.
- Put it inside the form's `strip` slot. Outside a strip container it still renders, but the layout (row, gap, wrapping) is lost.
- Do not rely on it for authorization or logic: it is a label.
- The lint rules do not check this object. Hand-written `<span>` / `<b>` markup in the strip is not flagged either; hex colors are (L003).
- Code oddity (not fixed): the REMARK of `src/Components/StripKv.php:9` says the document screens use this component instead of the handwritten `div`, but the catalog demos still write the raw markup: `site/resources/views/demos/form-md.blade.php:69` and `:72`, `site/resources/views/demos/form-md-po.blade.php:63` and `:66`. Those boxes therefore carry no `data-dl` attributes.

## Example

The two raw boxes of the demo `form-md` (document number and live Grand Total) written with the component (fictional data):

```blade
<x-dl::form title="Customer Order · New" kind="document" panel="top" :back="$gridUrl" :action="$here" method="POST" :tabs="['general' => 'General']">
    <x-slot:strip>
        <x-dl::strip-kv kind="doc" label="No. Pesanan" value="(otomatis)" />
        <x-dl::field.date name="co_date" label="Tanggal Pesan" :value="now()->format('Y-m-d')" required />
        <x-dl::field.select name="customer" label="Customer" :options="$customers" required />
        <x-dl::strip-kv kind="grand" label="Grand Total" value="IDR 0" :value-attrs="['data-l-val' => 'lines:grand']" />
    </x-slot:strip>
    <x-slot:general>
        ...
    </x-slot:general>
</x-dl::form>
```

Other forms of use:

```blade
<x-dl::strip-kv label="Items" value="12" />
<x-dl::strip-kv label="Due date">{{-- slot when value is null --}}<x-dl::badge label="Overdue" tone="danger" /></x-dl::strip-kv>
<x-dl::strip-kv label="Net Weight" :value-attrs="['data-l-val' => 'lines:sum.weight']" value="0" />
```

## Related

- `x-dl::form`: owns the `strip` slot (`dl-form__strip`) and the `panel` density that shrinks these boxes.
- `x-dl::lines`: updates a `value` through `data-l-val` (`grand`, `sub`, `dpp`, `ppn`, `count`, `sum.<column>`).
- `x-dl::field.date`, `x-dl::field.select`, `x-dl::field.lookup`: editable fields that share the same strip.
- `x-dl::field.display`: read-only value inside the form body.
- `x-dl::kpi-row`, `x-dl::badge`: number tiles and status labels outside document forms.
