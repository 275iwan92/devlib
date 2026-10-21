## Overview

`<x-dl::wizard-option>` is one **choice row** of a wizard in a modal: a bordered row with a large emoji on the left, a title with an optional badge, an explanation line, an optional note, and, on the right, whatever you put in the slot (normally the action button that picks this choice and opens the next dialog). A coloured left edge marks the kind of choice.

Use it inside `x-dl::wizard-options` (the column container), inside an `x-dl::modal`. It is a pure display object: no script, no field, no state, nothing posted. It is **not** a step of the step-by-step form `x-dl::wizard` (see `resources/guide/wizard.md`, "How the four wizard tags fit together").

Pick another object when:

- the choice is a value of a form: `x-dl::field.radio` or `x-dl::field.select`;
- the row is a record in a list: `x-dl::grid`, `x-dl::feed` or `x-dl::member-list`;
- the row needs a status only: `x-dl::status-card`.

## Props

All constructor props of `Devlib\Components\WizardOption` (8). Blade names are kebab-case (`badge-tone`); use `:off="true"` or the bare attribute `off` for the boolean.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `emoji` | `string`, `''` | The large symbol in `<span class="dl-wiz__emoji">` (24 px), for example `✨`. Any short text is accepted (an emoji, a letter); it is written as given, escaped, not translated, and not hidden from screen readers (there is no `aria-hidden`). Empty leaves an empty space of the same column. |
| `title` | `string`, `''` | Bold title of the choice (`<b>`). Translated through the dictionary (standard text prop). Wrap data values in `raw()`. |
| `description` | `string`, `''` | The explanation under the title (`<p>`, small muted text). Translated (standard text prop). Write it in English. Always rendered, also when empty. |
| `badge` | `?string`, `null` | Small label next to the title, drawn by `x-dl::badge` (pill). `null` or empty = no badge. Not in the dictionary text props: the label is translated only when `config('devlib.translate_fixed')` is on. |
| `badge-tone` | `string`, `'neutral'` | Colour of the badge: `neutral`, `accent`, `success`, `warn`, `danger`, `info` or `violet` (the tones of `x-dl::badge`). Ignored without `badge`. |
| `note` | `?string`, `null` | Extra small line under the description (`<small>`), for example what the choice needs or the flow it follows. A finished text of your screen: NOT translated by this object (translate it yourself, or write it in the screen language). Rendered only when not empty. |
| `kind` | `string`, `''` | A CSS class name added to the row, used to colour the left edge. The row's CSS is `border-left: 3px solid var(--mp-tc, var(--dl-border))`, so the class you give must set the custom property `--mp-tc` (a colour); without such a class the edge has the normal border colour. DevLib itself defines no kind class: write one in your project (see Example). Empty = no extra class. |
| `off` | `bool`, `false` | Adds `is-off`: the whole row is dimmed (opacity .6). Visual only: it does not disable the slot content. |

The default slot is placed after the text block, at the right end of the row (the text block takes the free width, so a button is pushed to the right).

### Attributes accepted on every DevLib object

`can="module.action"` (no access = the row is not rendered) and any HTML attribute (`id`, `class`, `style`, `data-*`), merged into the row `div`.

## Behavior

### Generated markup

```html
<div class="dl-wiz__opt [kind] [is-off]" data-dl="x-dl::wizard-option" ...>
  <span class="dl-wiz__emoji">✨</span>
  <div class="dl-wiz__txt">
    <b>title</b> <span class="dl-badge dl-badge--accent dl-badge--pill">badge</span>   <!-- only with badge -->
    <p>description</p>
    <small>note</small>                                                               <!-- only with note -->
  </div>
  ... slot ...
</div>
```

CSS (`resources/assets/devlib.css`, rules `.dl-wiz__opt`, `.dl-wiz__emoji`, `.dl-wiz__txt`): a flex row, gap 12 px, 1 px border with a 3 px coloured left edge, radius from the theme, padding 12 x 14, surface background; the text block can shrink (`min-width: 0`). Fonts follow the user's font scale.

### States

- Normal: as above.
- `off`: dimmed to 60 %. The colours and the layout do not change. Nothing is disabled: if the row must not be chosen, also set `disabled` on the button in the slot.
- There is no hover, selected or focus style on the row itself; the button in the slot carries them.

### Language

`title` and `description` follow the dictionary. `badge` follows it only with `translate_fixed`. `note`, `emoji` and the slot content are as written.

### Accessibility as implemented

The row is a plain `div` with no role, no `aria-*` and no keyboard handling; the only focusable thing is what you put in the slot. Give the slot button a clear label (for example `Choose`) or an `aria-label` that names the choice, since several rows have the same button text.

## Data and events

- Posts nothing, emits no `dl:*` event, has no JavaScript instance.
- To react to a choice, put a button in the slot: `data-dl-open="<modal id>"` opens the next modal (of `x-dl::modal`), a link goes to a page, `onclick` or an event listener on the button does anything else. Use `data-dl-close-modal` on the button to close the current modal first.
- Markup hooks: `dl-wiz__opt`, `dl-wiz__emoji`, `dl-wiz__txt`, `is-off`, and the class you pass in `kind`.

## Rules and pitfalls

- Use it inside `x-dl::wizard-options` (it gives the vertical gap). Alone, rows touch each other.
- `kind` is a class name, not a colour. Define the class (with `--mp-tc`) in your project CSS; do not pass a hex colour.
- `off` does not block clicks; disable the slot button yourself.
- Do not use it as a step of `x-dl::wizard`: it has no field, so nothing is validated, posted or shown in the Review.
- `description` is always rendered as a `<p>`, also when empty: give a description for every row, so the rows look the same.
- `note` is not translated; `description` is. Do not put data values into `description` without `raw()`.
- Keep the number of rows small (about 2 to 5): the pattern is for a quick choice, not for a long list.
- No lint rule (`devlib:lint`) is specific to this object.
- Code oddity (not fixed): `src/Components/WizardOption.php:8` says `kind` takes a modifier such as `mp-typecard--new` as the example; that class (and the `--mp-tc` colours) exists only in the optional `mockup-parts` package (`site/packages/mockup-parts/resources/assets/css/mockup-parts.css:303`), not in `devlib.css`, and the `.dl-wiz__opt` edge colour in `devlib.css:3788` depends on it. In a project without that package the edge stays grey unless the project defines its own kind class.
- Code oddity (not fixed): `src/Components/WizardOption.php:10` names `screens/inquiry.blade.php` as the user; that file is not in this repository, and no catalog demo or test uses the tag, so the example below is written for this guide.

## Example

Fictional data. The kind classes belong to the project CSS:

```css
.my-type--new    { --mp-tc: var(--dl-accent); }
.my-type--recalc { --mp-tc: var(--dl-violet); }
```

```blade
<x-dl::modal id="wz-type" title="Create Request" icon="new">
    <x-dl::wizard-options>
        <x-dl::wizard-option emoji="✨" title="New Product" kind="my-type--new"
            description="Never made before. Sales defines the product from scratch."
            badge="Most used" badge-tone="accent"
            note="Needs: product type, dimensions, material">
            <x-dl::button label="Choose" size="sm" data-dl-open="wz-new" />
        </x-dl::wizard-option>
        <x-dl::wizard-option emoji="🔁" title="Recalculate Price" kind="my-type--recalc"
            description="The product already exists. Only a new price is needed.">
            <x-dl::button label="Choose" size="sm" data-dl-open="wz-recalc" />
        </x-dl::wizard-option>
        <x-dl::wizard-option emoji="✏️" title="Change Specification" description="Not available for this account." off>
            <x-dl::button label="Choose" size="sm" disabled />
        </x-dl::wizard-option>
    </x-dl::wizard-options>
    <x-slot:footer><x-dl::button preset="cancel" data-dl-close-modal /></x-slot:footer>
</x-dl::modal>
```

## Related

- `x-dl::wizard-options`: the column container for these rows.
- `x-dl::modal`: the dialog that holds the list.
- `x-dl::badge`: draws the `badge` (tones `neutral`, `accent`, `success`, `warn`, `danger`, `info`, `violet`).
- `x-dl::button`: the action button in the slot.
- `x-dl::wizard` and `x-dl::wizard.step`: the step-by-step form wizard (a different pattern).
