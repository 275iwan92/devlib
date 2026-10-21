## Overview

`<x-dl::field.display>` shows one value as read-only text with a label above it (label, then the value on a dashed underline). It is not an input box: there is no `<input>`, no `name` attribute on any element and no script. It exists so that a form can show data the user must see but not edit (document number, supplier, total, date) in the same field frame (label row, error line, hint line) as the editable fields.

It extends the abstract `Devlib\Components\Field\Field`, so it takes the 14 props every field has, plus one own prop, `format`.

### Formats

| `format` | Shown as |
|---|---|
| `text` (default) | The value as given (escaped). Empty (`null` or `''`) shows an em dash. |
| `mono` | Like `text`, in the monospace font (codes, document numbers). |
| `number` | `Locale::number()` in the application language (`1.234,5` or `1,234.5`), right-aligned. Empty (`null` or `''`) shows an em dash; `0` shows `0`. |
| `money` | `Locale::money()`: the currency code `IDR` + number with 0 decimals, right-aligned. Empty (`null` or `''`) shows an em dash; `0` shows `IDR 0`. |
| `date` | `Locale::date()` short style (`dd-Mmm-yyyy`). Empty shows an em dash. |

### What it is not

- Not an input: nothing is posted. If the server also needs the value, add an `x-dl::form` `hidden` entry or look the record up on the server.
- Not a place to compute anything: the value is formatted, not changed.
- Not the History block of the form: `x-dl::form` uses this object itself for "Created by / at" and "Last updated by / at" on its History tab.

## Props

Own prop (Blade name is kebab-case; use `:prop="expr"` for anything that is not a plain string):

| Prop | Type, default | Meaning |
|---|---|---|
| `format` | `string`, `'text'` | One of `text`, `mono`, `number`, `money`, `date` (table above). Any other word falls into the default branch (shown like `text`, no extra CSS class). `number` and `money` add the class `is-num` (right-aligned, tabular numbers), `mono` adds `is-mono`. |

Format details (from the view):

- `number`: shows 2 decimals when the value, converted to string, contains a `.` (`12500.5` is `12.500,50`), otherwise 0 decimals. `null` and `''` show an em dash; `0` shows `0`.
- `money`: always the currency `IDR` with 0 decimals. There is no currency prop; for other currencies use `format="text"` and format the string yourself. `null` and `''` show an em dash; `0` shows `IDR 0`.
- `date`: accepts an ISO string or a date object; a string that cannot be read as a date is shown unchanged.

### Inherited props

All 14 props come from `Field` (full reference: `x-dl::field.text`). Where this control differs:

| Prop | Effect on `display` |
|---|---|
| `name` | Written to `data-dl-field` on the root and used in the generated id. There is no input, so nothing is posted whatever the name. |
| `label` | Label text above the value. `''` removes the whole label row (then also the `*` and the tooltip). Translated. |
| `tooltip` | Small (i) bubble beside the label, only with a non-empty `label`. |
| `hint` | Small help line under the value; rendered whenever not `null`. |
| `error` | Error text under the value; non-empty adds `is-invalid` to the root. Not translated. |
| `value` | The value to show. Scalars only (string, int, float); an array or object throws in the view. |
| `placeholder` | Unused (an empty value shows an em dash instead). |
| `required` | Ignored: a display has no named input, so the constructor resets it to `false` (no `*`, and form validation is not blocked). |
| `disabled` | No effect (nothing to disable). |
| `readonly` | Adds `is-readonly` to the root; the display has no box, so there is no visible change. The control is always read-only anyway. |
| `icon` | Unused. |
| `size` | `sm` (default) or `lg`: only the CSS class `dl-field--sm` / `dl-field--lg`. |
| `i18n` | Written to `data-i18n` on the label (auth screens only). |
| `source` | Catalog text for the data origin; no visual effect. |

Attributes accepted on every DevLib object: `can="module.action"` (no access removes the whole field) and any HTML attribute (`id`, `class`, `style`, `data-*`), which is merged onto the root `div.dl-field`, not onto the value.

## Behavior

### Markup

```html
<div class="dl-field dl-field--display dl-field--sm" data-dl-field="">
  <div class="dl-field__labelrow"><label class="dl-field__label" id="dlf-...-lbl">No. PO</label></div>
  <div class="dl-display is-mono" id="dlf-..." role="group" aria-labelledby="dlf-...-lbl">PO-2609-0001</div>
  <div class="dl-field__error" data-dl-field-error></div>
</div>
```

The value sits in `div.dl-display` (min height 28px, bold, dashed bottom border). The generated id (`$uid`) is put on that `div`, and `role="group"` with `aria-labelledby` ties it to the label (a `div` is not labelable, so the label has no `for`; with an empty `label` there is no `aria-labelledby`).

### Styling hooks

Two modifier classes exist in the stylesheet and work when you pass `class` on the object (it lands on the root):

| Class | Effect |
|---|---|
| `dl-display--plain` | Removes the dashed underline. |
| `dl-display--lead` | Larger (20px), heavier value, for a key figure. |

### Empty values and escaping

The value is always output with `{{ }}` (escaped), so HTML in it is shown as text. For `text` / `mono` the empty check is strict: `0` and `'0'` are shown, `null` and `''` become an em dash.

### Echo of a select

Core script `echoAll` (`core.js`) looks for an element with `data-dl-echo="<select name>"` (and optional `data-dl-echo-prefix`) and writes prefix + the chosen option label of that `x-dl::field.select` into the `.dl-display` inside it. Put the attributes on the display object and it mirrors the select. It runs after every `change` event of a control inside the same `<form>` (so the display must be inside that form), never on first render: give the display an initial `value` too.

### Locking

Display ignores form locking: it never has an input to disable, and it looks the same in an editable form, a locked (Confirmed) form and `mode="view"`.

## Data and events

- Posted: nothing. No `name`, no hidden input.
- Server side: show only what the server already knows. For a locked document the stored record is the source; a value shown here must not be read back from the request.
- Events: none (no JS class, no `dl:*` event, no `el.dlComponent`).
- DOM hooks: root `[data-dl-field]`, `.dl-display`, modifiers `is-num`, `is-mono`; error line `[data-dl-field-error]` (always present); hint `[data-dl-field-hint]` (only when `hint` is not `null`).
- Wizard Review summary and `Devlib.fill()` do not read or write it (no named element).

## Rules and pitfalls

- Display is for showing, not for posting. If the server needs the value back (for a draft that is still editable), keep a real field (`readonly` text field) instead, or read the value from the record.
- `required` on a display is ignored (reset to `false` by the constructor); a display has no named input, so there is nothing to validate.
- Wrap data in `raw()` when it goes into `label` (labels are translated through the dictionary); `value` is not translated, so data values need no wrapper.
- Money is always `IDR`, 0 decimals. For a foreign currency show the text yourself (`'USD 1,250.50'`) with `format="text"`, or use `x-dl::field.money` for input.
- Pass the raw number (int, float or canonical string such as `1234.5`) to `number` / `money`; do not pre-format it.
- `placeholder`, `icon`, `disabled` and `readonly` do nothing useful here; do not rely on them.
- A very long value is not truncated (it wraps inside the cell).

## Example

Label + value pairs (shape of the demo):

```blade
<x-dl::form.row>
    <x-dl::field.display label="PO No." value="PO-2609-0001" format="mono" />
    <x-dl::field.display label="Date" value="2026-09-23" format="date" />
</x-dl::form.row>
<x-dl::form.row>
    <x-dl::field.display label="Supplier" value="PT Contoh Kertas Jaya" />
    <x-dl::field.display label="Total" :value="12500000" format="money" />
</x-dl::form.row>
```

A key figure without the underline, plus a hint:

```blade
<x-dl::field.display label="Balance due" :value="$po->balance" format="number" hint="Updated on every payment." class="dl-display--lead dl-display--plain" />
```

Mirroring a select (the label of the chosen option is written into the display):

```blade
<x-dl::field.select name="warehouse" label="Warehouse" :options="$warehouses" :value="$po->warehouse" />
<x-dl::field.display label="Receiving at" data-dl-echo="warehouse" data-dl-echo-prefix="WH:" value="" />
```

## Related

- `x-dl::field.text`: full reference of the 14 inherited props; use a `readonly` text field when the value must be posted.
- `x-dl::field.money`, `x-dl::field.number`, `x-dl::field.date`: the matching input fields.
- `x-dl::form` and `x-dl::form.row`: the shell and rows that hold it; the form's History tab is built from this object.
- `x-dl::field.select`: the source for `data-dl-echo`.
- `skills/devlib/form.md`: project rules for forms and read-only display.
