## Overview

`<x-dl::headline>` prints a large heading (an `<h2>`, 22px, extra bold) for the name of a document or product inside the body of a form or page. It is the smallest object in the library: one styled element, no props, no script. The text is the slot.

Use it for the "what am I looking at" line inside the content, for example the product name above a recalculation form. Pick another object when:

- it is the title of a whole form or card: the `title` / `subtitle` props of `x-dl::form`, `x-dl::card` (they also get the Technical Guide link and the status badge);
- it is a tab or group title inside a form: the `tabs` of `x-dl::form` or an `x-dl::card`;
- it is a small explanation text: `x-dl::hint`;
- it is a status or a number: `x-dl::badge`, `x-dl::strip-kv`.

Generated markup:

```html
<h2 class="dl-headline" data-dl="x-dl::headline" data-dl-class="Headline ← DevlibComponent" data-dl-src="—">Slot text</h2>
```

Extra HTML attributes (`id`, `class`, `style`, `data-*`) are merged on the `<h2>`; a passed `class` is added after `dl-headline`. `can="module.action"` is read by the base class: without the ability nothing is rendered.

## Props

Constructor of `Devlib\Components\Headline`: none. The class has no constructor, so the Manifest lists no props. Everything is given through the slot and HTML attributes:

| Input | Meaning and effect |
|---|---|
| default slot | The heading text (escaped when written with `{{ }}`; Blade markup and components in the slot are rendered as written). It is NOT translated by DevLib: pass final text. Names, numbers and other data are simply printed. |
| HTML attributes | `class`, `id`, `style`, `data-*` go on the `<h2>`; use them for page hooks and print ids. The level (`h2`) cannot be changed. |
| `can` | Optional ability `module.action`; no ability, no output. |

## Behavior

- Style (`.dl-headline` in `devlib.css`): 22px, weight 800, color `--dl-ink-strong`, margin `10px 0 2px` (a tight space below so a hint or the form fields follow closely).
- Static: no JavaScript, no event, no state, no `dlComponent`. It works in print and view mode alike (it is plain text, not a control).
- Language: the dictionary does not translate the slot. For text that must follow the page language use a translatable prop of another object, or `Devlib\Lang::text('...')` in the screen.
- Accessibility: it is a real `h2`, so it takes part in the heading outline. Using it several times on a page creates several `h2` of equal rank.

## Data and events

- Display only: nothing is posted and nothing is emitted.
- Source of the text: the screen (for example `$doc['name']`).

## Rules and pitfalls

- Do not use it as the page title: the form shell already prints a title (`div.dl-form__title`) with the status and navigation links. A second large title competes with it.
- Keep it short and on one line; the element is a block with no truncation.
- It has no sub-text slot or prop; put a small line below it with `x-dl::hint` (or a field) instead.
- Do not set colors with hex values in `style` (lint L003); use the theme tokens through classes.
- No lint rule is specific to this object.
- Code oddity (not fixed): the REMARK of `src/Components/Headline.php:6` says the headline has "sub-teks kecil opsional" (an optional small sub-text), but the view (`src/Components/Headline.php:25`) has no prop or slot for it; the same sentence is shown in `skills/devlib/components.md` ("## `<x-dl::headline>`").
- Code oddity (not fixed): `.dl-headline` (`resources/assets/devlib.css:3799`) uses a fixed `font-size:22px`, while the other text styles use `calc(Npx * var(--dl-font-scale))`, so the headline does not follow the user's font-size setting (`dl-font-scale`).
- Code oddity (not fixed): no screen in this repository uses the object (the REMARK names `inquiry-recalc` and `inquiry-revise`, which are project screens), so no catalog demo shows it.

## Example

No catalog demo exists in this repository; the example follows the view contract (fictional data).

```blade
<x-dl::form title="Recalculate Inquiry" kind="document" :back="$gridUrl" :action="$here" method="POST" :tabs="['general' => 'General']">
    <x-slot:general>
        <x-dl::headline>{{ $product['name'] }}</x-dl::headline>
        <x-dl::hint>Material and process costs are recalculated with the current price list.</x-dl::hint>
        <x-dl::form.row :cols="3">
            <x-dl::field.number name="qty" label="Quantity" :value="old('qty', 1000)" required />
        </x-dl::form.row>
    </x-slot:general>
</x-dl::form>
```

With a page hook:

```blade
<x-dl::headline id="doc-name" class="no-print-margin">Carton Box 20x15x10</x-dl::headline>
```

## Related

- `x-dl::form`: the shell that prints the document title; the headline sits inside its body.
- `x-dl::hint`: small explanatory text under a headline or field.
- `x-dl::strip-kv`: key figures (document number, total) in the form strip.
- `x-dl::box`: neutral wrapper when you need a container around the heading.
