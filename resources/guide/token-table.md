## Overview

`<x-dl::token-table>` prints a documentation table for design tokens, the CSS custom properties (`--dl-*`) that make up the Basic theme of DevLib. One row per token, four columns: the token name, its current value, a live example, and a short "used for" note. The current value is read in the browser from the computed style of `<html>`, so it follows the active theme, density and font scale and refreshes when the user changes them with `x-dl::theme-picker`. The example is drawn with the token itself (`var(--dl-...)`), so it is always the real thing, not a copy.

The table documents tokens; it does not define or change them. Tokens live in `resources/assets/devlib.css` (`:root`), and the themes override them (the CSS of 12 themes is written into the page by `x-dl::theme-boot` and selected by `data-dl-theme` on `<html>`). The catalog uses this object on the Basic pages: Colors (`basic-colors`), Typography (`basic-typography`), Spacing / Radius / Shadow (`basic-shape`), Breakpoints (`basic-breakpoints`) and Density (`basic-density`).

Use it for documentation pages of a design system. Do NOT use it for application data (use `x-dl::grid` or `x-dl::table`), to pick a theme (use `x-dl::theme-picker`), or to show status colors on business data (use `x-dl::badge`).

## Props

Constructor of `Devlib\Components\TokenTable` (2 props). The object has no `source` prop: its source text is fixed (`resources/assets/devlib.css (:root) + tema aktif`).

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `tokens` | `array` | `[]` | The rows, as `[tokenName => 'what it is used for']`. The array KEY is the token (custom property) name including the two dashes, for example `'--dl-accent'`; the VALUE is the text of the "Used for" column. A list without keys (`['--dl-accent']`) is wrong: the keys would be `0, 1, ...` and the names printed as numbers. The "Used for" text is printed as given and is not translated. |
| `kind` | `string` | `'color'` | How the "Example" column is drawn for every row. Valid values: `color`, `font`, `space`, `radius`, `shadow`, `text`. Any other string behaves like `text`. It also becomes the table class `dl-tokens--<kind>` and is appended to the catalog tag (`x-dl::token-table kind=color`). One table has one kind; use several tables on a page for mixed groups. |

### The `kind` values

| `kind` | Example drawn from the token | Typical tokens |
|---|---|---|
| `color` | a 64 x 24 swatch with `background: var(token)` | `--dl-accent`, `--dl-surface`, `--dl-danger`, ... |
| `font` | the text "DevLib 123" with `font-size: calc(var(token) * var(--dl-font-scale))` | `--dl-text-2xs` ... `--dl-text-3xl` |
| `space` | a bar 10px high with `width: var(token)` | `--dl-space-1` ... `--dl-space-8` |
| `radius` | a 56 x 34 box with `border-radius: var(token)` | `--dl-radius-sm`, `--dl-radius`, `--dl-radius-lg`, ... |
| `shadow` | the same box with `box-shadow: var(token)` | `--dl-shadow-pop`, `--dl-shadow-modal` |
| `text` | an em dash (no drawing) | `--dl-font`, `--dl-font-mono`, `--dl-font-scale`, `--dl-density`, `--dl-bp-*` (numbers, families and other non-visual values) |

## Behavior

### Markup produced

```html
<table class="dl-tokens dl-tokens--color" data-dl="x-dl::token-table kind=color" data-dl-class="TokenTable" data-dl-src="resources/assets/devlib.css (:root) + tema aktif">
  <thead><tr><th>Token</th><th>Current value</th><th>Example</th><th>Used for</th></tr></thead>
  <tbody>
    <tr data-token="--dl-accent">
      <td><code>--dl-accent</code></td>
      <td class="dl-tokens__val" data-dl-token-value="--dl-accent">…</td>
      <td><span class="dl-tokens__swatch" style="background:var(--dl-accent)"></span></td>
      <td class="dl-tokens__use">Main color: buttons, focus, links, active tab</td>
    </tr>
  </tbody>
</table>
```

Other example elements: `.dl-tokens__box` (radius, with `--shadow` modifier for shadow) and `.dl-tokens__bar` (space). Extra attributes (`class`, `id`, `style`) go on the `<table>`.

### Reading the current value

The script `Tokens` (`resources/assets/js/components/tokens.js`, selector `.dl-tokens`) fills each `[data-dl-token-value]` cell with `getComputedStyle(document.documentElement).getPropertyValue(name).trim()`, or an em dash when the property is not set on `<html>`. Consequences:

- The cell shows the declared text of the property, for example `#2f6fed`, `8px`, `0 16px 44px rgba(15,22,45,.24)` or `color-mix(in srgb, ...)`; functions such as `calc()` and `color-mix()` are not evaluated.
- Only tokens visible on `<html>` are found. A token defined only on `body` or on a component class shows "—".
- The script paints once at mount and again on the document event `dl:theme`. That event is dispatched by `x-dl::theme-picker` (at its start and after each click on a theme, density or font-scale option). If the theme is changed by other code, dispatch `document.dispatchEvent(new CustomEvent('dl:theme'))` yourself.
- Before the script runs (or when JavaScript is off) the value cell shows "…".

### Language

The headings "Current value", "Example" and "Used for" use `Lang::text` (translated). The heading "Token", the text "DevLib 123" in `font` rows and the "Used for" notes are not translated; write the notes in the language of the documentation page.

### Access

Only the generic `can="module.action"` attribute (no ability = not rendered). There is no JavaScript API other than the instance `el.dlComponent` (class `Tokens`, method `paint()`), no events are dispatched and nothing is posted.

## Data and events

- Input: `tokens` and `kind` only. Every token name is used inside a `style` attribute (`var(NAME)`), so it must be a valid custom property name written by the developer; do not build it from user input.
- Output: none. The only runtime input is the computed style of `<html>` and the `dl:theme` event (listened to, never dispatched by this object).
- Style hooks: `.dl-tokens`, `.dl-tokens--<kind>`, `.dl-tokens__val`, `.dl-tokens__use`, `.dl-tokens__swatch`, `.dl-tokens__box`, `.dl-tokens__box--shadow`, `.dl-tokens__bar`, row attribute `data-token`.
- Where the tokens are defined: `resources/assets/devlib.css` `:root` (colors, `--dl-radius*`, `--dl-shadow-*`, `--dl-font`, `--dl-font-scale`, `--dl-font-mono`, `--dl-density`, `--dl-text-*`, `--dl-space-*`, `--dl-bp-*`). `--dl-density` is changed by `[data-dl-density="compact"|"comfortable"]` on `<html>` (0.7 / 1.35); colors are replaced by the active theme. Breakpoints are documentation only (CSS media queries cannot read variables).

## Rules and pitfalls

- Put tokens with the same `kind` in one table. A `color` table with a `--dl-radius` row shows an empty swatch; a `space` table with a color row shows a bar of width zero.
- Use the real token names from `devlib.css`. A name that does not exist shows "—" as the value and an empty example, and nothing warns about it.
- The page that holds the table should load `x-dl::theme-boot` (normal in the layout) so the themes exist; without them the table shows the `:root` defaults only.
- Descriptions belong to the table, not to the CSS: when a token is renamed or removed in `devlib.css`, update the Basic pages too. New components must use the tokens listed here instead of fixed numbers (the rule stated on the Spacing / Radius / Shadow page).
- Tokens that are gradients (`--dl-logo`) or lists (`--dl-font`) do not fit `color`; document them with `kind="text"`.
- The `font` example scales with the user's font scale (`--dl-font-scale`); density does not affect it.

### Notes on inconsistencies found while writing this entry (not fixed)

- The class header says `kind` is `color | font | space | radius | shadow | text`, but the view has no case for `text`; it is simply the `default` branch, so every unknown value behaves the same.
- `Guide::entries()` looks for the script at `resources/assets/js/components/token-table.js`, but the file is `tokens.js` (class `Tokens`), so the catalog shows no JS file for this object.
- The default notes in the demos (`basic-*.blade.php`) are written in Indonesian while the object headings are English source text.
- `tag()` returns `x-dl::token-table kind=<kind>`, so the catalog and `data-dl` carry the kind; the guide-link script keeps only the first word, so this is harmless.

## Example

Runnable (adapted from `site/resources/views/demos/basic-colors.blade.php` and `basic-shape.blade.php`):

```blade
<h2>Brand and action colors</h2>
<x-dl::token-table :tokens="[
    '--dl-accent' => 'Main color: buttons, focus, links, active tab',
    '--dl-accent-2' => 'Hover of the primary button',
    '--dl-accent-soft' => 'Soft accent background (hints, selection)',
    '--dl-navy' => 'Sidebar, dark panels, login background',
]" />

<h2>Radius</h2>
<x-dl::token-table kind="radius" :tokens="[
    '--dl-radius-sm' => 'Form inputs, small buttons',
    '--dl-radius' => 'Messages, dropdowns',
    '--dl-radius-lg' => 'Cards, widgets',
    '--dl-radius-xl' => 'Modals, login box',
]" />

<h2>Typography</h2>
<x-dl::token-table kind="font" :tokens="[
    '--dl-text-sm' => 'Field labels, table body',
    '--dl-text-lg' => 'Form title',
]" />
<x-dl::token-table kind="text" :tokens="[
    '--dl-font-scale' => 'Multiplier for all font sizes (1 = mockup size)',
]" />

{{-- Let the user change theme / density / font scale; the "Current value" column follows. --}}
<x-dl::theme-picker />
```

## Related

- `x-dl::theme-picker`: changes the theme, density and font scale that the table reads back (it dispatches `dl:theme`).
- `x-dl::theme-boot`: writes the CSS of the themes and applies the saved choice before the page is shown.
- `x-dl::table`, `x-dl::grid`: ordinary tables for data.
- `x-dl::badge`: colored status labels for business data.
