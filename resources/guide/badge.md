## Overview

`<x-dl::badge>` is the small coloured status label (a rounded "pill" by default) with an optional icon in front of the text. It is the standard way to show a document status, a category or a count-like tag in forms, cards, headers and lists.

Use it for short status words (`DRAFT`, `APPROVED`, `Expires soon`). Do NOT use it:

- for a status column in a data grid: use the grid column `'type' => 'badge'` with `tones` or `toneKey` (the grid draws the same `dl-badge` markup in JavaScript; a transactional grid must have one, lint rule `G120`).
- for a row of badges that must wrap: put them inside `x-dl::chips`.
- for a person: `x-dl::avatar`. For an environment mark (Host / Client): `x-dl::env-badge`. For a long message: `x-dl::alert`.

Generated markup:

```html
<span class="dl-badge dl-badge--success dl-badge--pill" data-dl="x-dl::badge tone=success" data-dl-class="Badge ← DevlibComponent" data-dl-src="—">
  <i class="fa-solid fa-plus"></i>APPROVED   <!-- <i> only when icon is set; text = label then the slot -->
</span>
```

Extra HTML attributes (`class`, `id`, `title`, `style`, `data-*`) and `can="module.action"` go on the root `span`; without the ability nothing is rendered.

## Props

Constructor of `Devlib\Components\Badge`: 4 props, none inherited. Use `:pill="false"` for a non-string value.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `label` | `string`, `''` | The text. Escaped (`{{ }}`). NOT translated by the dictionary: the class sets `TEXT_PROPS = []` (button and status terms are always English single words, user rule 2026-09-30). Exception: when the app config `devlib.translate_fixed` is truthy, the constructor passes the label through `Lang::text()` (one-language apps). A `raw()`/`Lang::raw()` wrapper is stripped before output, so wrap data values (customer names, numbers) in `raw()`. |
| `tone` | `string`, `'neutral'` | Colour family, written as the class `dl-badge--<tone>`. Styled values: `neutral` (default grey), `accent`, `success`, `warn`, `danger`, `info`, `violet`, `tangerine`, `yellow`. Any other value is written as a class with no style, so the badge falls back to the base grey look. Also appears in `data-dl` (`x-dl::badge tone=<tone>`). |
| `pill` | `bool`, `true` | Adds `dl-badge--pill` (fully rounded, 20 px radius). `:pill="false"` gives the small square radius of the theme. |
| `icon` | `?string`, `null` | Icon before the text. Either a standard meaning from `Devlib\Icon::MAP` (`new`, `success`, `warning`, `lock`, `user`, ...) or a Font Awesome class (`fa-fingerprint`). Rendered as `<i class="fa-solid ...">`, so only solid-style icons work. Unknown names are written as is (no icon shows if the class does not exist). The badge is a flex row with a 4 px gap between icon and text. |

Default slot: whatever is inside the tag is rendered right after `label` (no space added). Normally leave it empty and use `label`.

## Behavior

- Look: bold 10.5 px text (scaled by the user font scale), padding `2px 8px`, `white-space: nowrap`, inline flex with centred items. Tones are tinted backgrounds mixed from theme tokens (`color-mix` on `--dl-green`, `--dl-danger`, `--dl-accent`, ...) with a darker text of the same family, so the active theme recolours them.
- Document status colours: constant `Badge::STATUS` maps `DRAFT`->neutral, `SUBMITTED`->warn, `HOLD`->violet, `APPROVED`->success, `CONFIRMED`->success, `SENT`->info, `PARTIALLY_RECEIVED`->info, `RECEIVED`->success, `CLOSED`->neutral, `REJECTED`->danger, `CANCELLED`->danger. `Badge::forStatus('approved')` upper-cases the argument and returns the tone, or `neutral` when unknown (so `In Progress` with a space does NOT match; use underscores like `PARTIALLY_RECEIVED`).
- No states: a badge is static (no hover, focus, disabled or click behavior). Wrap it in a link or button yourself if it must be clickable.
- Context sizing: some parents resize it (`.dl-pbanner .dl-badge` 12 px, `.dl-rfeed__prop .dl-badge` 16 px, `.dl-grid__cardimg .dl-badge` floats top right, `.dl-flow__step > .dl-badge`, `.dl-scard__top .dl-badge` never shrinks).
- Language: the text is shown as written (English single words by project rule). Only `translate_fixed` apps translate it.
- Accessibility as implemented: plain `span`; no `role`, no `aria-*`. The icon `<i>` has no `aria-hidden`. Colour alone must not carry the meaning: always write a text `label`.
- Permissions: `can="module.action"`.

## Data and events

- Takes four scalars and an optional slot; no arrays. Nothing is posted; no `dl:*` events; no JavaScript twin. `el.dlComponent` is not defined.
- Hooks: `data-dl="x-dl::badge tone=..."`, class `dl-badge` and `dl-badge--<tone>` (CSS and tests can target them).
- Server-side helpers: `Badge::STATUS` (constant, the one place for document status colours) and `Badge::forStatus(string): string`.
- Grids, the approval log, the import summary and Kanban tags write the same classes (`dl-badge dl-badge--pill dl-badge--<tone>`) from JavaScript; they do not instantiate this component.

## Rules and pitfalls

- Use `Badge::forStatus()` / `Badge::STATUS` for document status so every screen shows the same colours; do not pick tones per screen.
- Project rule: status and button words are English single words (`Draft`, `Approved`); do not translate them by hand.
- Wrap database values in `raw()` when they go into `label`, otherwise (in `translate_fixed` apps) the value is looked up in, and recorded into, the dictionary.
- `tone` is not validated; a typo (`sucess`) silently shows grey.
- `icon` accepts a meaning key or a class, but the view always prefixes `fa-solid`; brands (`fa-brands`) or regular icons do not work.
- Do not put long text in a badge: it never wraps (`nowrap`). Use `x-dl::chips` for several badges so the row wraps.
- Code oddity (not fixed): the REMARK (`src/Components/Badge.php:5`) and `skills/devlib/components.md:52` list seven tones (`neutral|accent|success|warn|danger|info|violet`), but `resources/assets/devlib.css:675-676` also styles `tangerine` and `yellow`.
- Code oddity (not fixed): `src/Components/Badge.php:53` renders `{{ $label }}{{ $slot }}` with no separator, so a badge with both gets `APPROVEDtext`.

## Example

Shapes copied from the catalog demos (`badge`, `doc-share`, `tooltip`):

```blade
@foreach (\Devlib\Components\Badge::STATUS as $status => $tone)
    <x-dl::badge :label="$status" :tone="$tone" />
@endforeach
<x-dl::badge label="Baru" tone="accent" icon="new" />
<x-dl::badge label="Draft" />
```

Status of a record, and a square (non-pill) badge with a Font Awesome icon:

```blade
<x-dl::badge :label="$po->status" :tone="\Devlib\Components\Badge::forStatus($po->status)" />
<x-dl::badge label="Check-in 08:02" tone="success" icon="fa-fingerprint" :pill="false" />
<x-dl::badge :label="raw($customer->name)" tone="info" />
```

A hint in a tooltip, and a badge only for users with the right:

```blade
<x-dl::tooltip text="Keterangan di bawah" position="bottom"><x-dl::badge label="Arahkan kursor" /></x-dl::tooltip>
<x-dl::badge label="Locked" tone="warn" icon="lock" can="document.lock" />
```

## Related

- `x-dl::chips`: wrapping row that holds several badges.
- `x-dl::grid`: status column `'type' => 'badge'` with `tones`/`toneKey` for lists (rule `G120`).
- `x-dl::form`: shows the document status and the stage as badges in its header.
- `x-dl::flow`, `x-dl::feed`, `x-dl::profile-banner`, `x-dl::rate-list`: other objects that embed badges.
- `x-dl::env-badge`: Host / Client marker, a different object.
- `x-dl::tooltip`: wrap a badge to explain it.
- `skills/devlib/components.md` (generated prop list), `skills/devlib/transaction-lifecycle.md` (document statuses), `skills/devlib/grid.md` (Status column).
