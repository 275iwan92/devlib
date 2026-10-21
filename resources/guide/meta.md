## Overview

`<x-dl::meta>` is the record information line shown under a record (usually at the bottom of a card or a form): who created it and when, who last changed it and when, and an origin tag. All values come from the caller; the object only formats and lays them out.

Use it on master and document screens that keep audit columns. Do NOT use it for the document status (use `x-dl::badge` with `Badge::forStatus()`), for a full change history (use the history / audit objects of the screen) or for read-only data fields (use `x-dl::field.display`).

## Props

Constructor of `Devlib\Components\Meta` (5 props). Blade names are kebab-case.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `created-by` | `?string`, `null` | Name of the creator, printed in bold after `by`. Shown only together with `created-at` (without a creation time the whole "Created" part is hidden). Not translated. |
| `created-at` | `mixed`, `null` | Creation time: a `DateTimeInterface` (Carbon) or a date-time string such as `2026-09-20 08:15`. Falsy (`null`, `''`) hides the "Created" part. Shown as `dd-Mmm-yyyy HH:mm WIB` (see Behavior). |
| `updated-by` | `?string`, `null` | Name of the last editor. Shown only together with `updated-at`. |
| `updated-at` | `mixed`, `null` | Time of the last change, same types and format as `created-at`. Falsy hides the "Updated" part. |
| `origin` | `?string`, `null` | Origin of the data, rendered as an `x-dl::badge` with that text as label. The value `REAL` gets the `success` tone (green); any other value (for example `DUMMY`) gets `warn` (amber). `null` or `''` hides it. The label is not translated. |

## Behavior

### Generated markup

```html
<div class="dl-meta" data-dl="x-dl::meta" data-dl-class="Meta" data-dl-src="—">
  <span><i class="fa-solid fa-circle-plus"></i> Created 20-Sep-2026 08:15 WIB by <b>Andi Pratama</b></span>
  <span><i class="fa-solid fa-pen"></i> Updated 23-Sep-2026 14:02 WIB by <b>Siti Rahma</b></span>
  <span class="dl-badge dl-badge--warn">DUMMY</span>
</div>
```

- The wrapper `div.dl-meta` is always rendered, even when every prop is empty (an empty bar with its top border and padding); do not include the object at all when there is nothing to show.
- Extra attributes (`class`, `id`, `style`, `data-*`) go on the wrapper; `can="module.action"` hides the whole object without the ability.
- Icons: `fa-circle-plus` for created and `fa-pen` for updated.

### Style

A wrapping flex row (`gap: 6px 16px`), `11.5px` times `--dl-font-scale`, muted colour, dashed top border and `8px` vertical padding. Names are bold in the normal ink colour. On a narrow screen the three parts wrap onto separate lines.

### Time format and time zone

Time goes through `Devlib\Locale::dateTime()`: `dd-Mmm-yyyy` with a three letter month in the page language (`20-Sep-2026`, `20-Okt-2026` in Indonesian), then `HH:mm` and the zone label, converted to the configured zone (`config('devlib.timezone')`, default `Asia/Jakarta`; label `config('devlib.timezone_label')`, default `WIB`). A string without a zone is read in the PHP default zone (Laravel `app.timezone`) and then shown in the DevLib zone; pass a Carbon instance if the source zone matters.

### Language and text

The words `Created` / `Dibuat`, `Updated` / `Diubah` and `by` / `oleh` follow the page language (dictionary keys `created_by`, `updated_by`, `by`). Names, the date text and `origin` are data and are never translated.

### States

Static: no hover, focus, loading or editing state. No JavaScript.

## Data and events

- No posted data, no events, no `el.dlComponent`.
- Typical source: the audit columns of the record, for example `:created-at="$po->created_at"` and `:created-by="$po->creator?->name"`.
- Hooks: `.dl-meta` and the child `.dl-badge`.
- The object receives only values; it never reads the database. The class declares no data source (`source()` is `—`).

## Rules and pitfalls

- Pass real timestamps. A string that cannot be parsed as a date makes `Locale::dateTime()` throw an exception (a date parser error), which breaks the page; check or omit bad values (`null` is safe).
- A "by" name without its time is not shown at all: always pass the pair.
- Names and the `origin` text are shown as typed. If a name is an application value that could be mistaken for a dictionary phrase, no translation happens anyway (these props are not translated), so `raw()` is not needed.
- `origin="REAL"` must be exactly upper case `REAL` to get the green tone; `real` or `Real` is amber.
- Do not put the document status here; this line is for audit information only.
- Code oddity (not fixed): the catalog demo passes `table="purchase.po_order"` and `:record-id="1"`, props that were removed from the object. Because they are no longer declared, they are rendered as plain HTML attributes on the wrapper (`<div class="dl-meta" table="purchase.po_order" record-id="1">`) and do nothing (`site/resources/views/demos/metadata.blade.php:7`).
- Code oddity (not fixed): the REMARK says "dibuat/diubah oleh siapa & kapan (WIB)" but the zone label and zone are configurable (`devlib.timezone`, `devlib.timezone_label`), not fixed to WIB (`src/Components/Meta.php:5`).

## Example

Copied in shape from the catalog demo (fictional data):

```blade
<x-dl::card title="Purchase Order PO-2609-0001">
    <x-dl::form.row>
        <x-dl::field.display label="Supplier" value="PT Contoh Kertas Jaya" />
        <x-dl::field.display label="Total" :value="12500000" format="money" />
    </x-dl::form.row>
    <x-dl::meta created-by="Andi Pratama" created-at="2026-09-20 08:15" updated-by="Siti Rahma" updated-at="2026-09-23 14:02" origin="DUMMY" />
</x-dl::card>
```

From a model (only the parts that exist are shown):

```blade
<x-dl::meta :created-by="$po->creator?->name" :created-at="$po->created_at"
            :updated-by="$po->editor?->name" :updated-at="$po->updated_at"
            :origin="$po->is_demo ? 'DUMMY' : 'REAL'" />
```

## Related

- `x-dl::badge`: the object used for the `origin` tag; also the document status label.
- `x-dl::card`, `x-dl::form`: the usual containers for the line.
- `x-dl::field.display`: read-only values in the same frame as inputs.
- `Devlib\Locale`: date, time and zone formatting (`dateTime`).
