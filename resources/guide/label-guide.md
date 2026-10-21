## Overview

`<x-dl::label-guide>` is a collapsible help panel that explains the three label sizes and how to print them without trouble. It is a native `<details>` element with a table of sizes (real size, what each size is used for, labels per A4 sheet), a short list of print tips (label roll, A4 sheet, no colour printer) and, when you give `colors`, a row of colour chips that explains the colour band of the labels (for example one colour per arrival month). It exists so the operator can read the answer next to the print button instead of asking.

Use it on the screen that prints labels: inside the `guide` slot of `x-dl::label-sheet`, or above the sheet on the label printing screen. Do NOT use it for:

- Printing itself or the sheet layout: that is `x-dl::label-sheet`.
- General help text or a field hint: use the `hint` of a field or a help topic.
- A legend of status colours in a grid: use the badge tones of `x-dl::grid`.

It is screen-only help: it is not printed (the sheet's print rule hides everything except the labels). There is no JavaScript class: opening and closing is the browser's own `<details>`.

### Markup produced

```html
<details class="dl-labelguide" data-dl="x-dl::label-guide" data-dl-class="LabelGuide ← DevlibComponent" data-dl-src="—" [open]>
  <summary><i class="fa-solid fa-ruler-combined"></i> Label sizes and how to print</summary>
  <table class="dl-labelguide__table">
    <thead><tr><th>Size</th><th>Used for</th><th class="num">Labels per A4 sheet</th></tr></thead>
    <tbody>
      <tr class="[is-active]"><td><b>60 x 40 mm</b></td><td>Item label for small packs: boxes, ink cans, rolls.</td><td class="num">21</td></tr>
      <tr><td><b>100 x 50 mm</b></td><td>Bin / rack label and item label on a stack.</td><td class="num">10</td></tr>
      <tr><td><b>100 x 150 mm</b></td><td>Pallet label, readable from a few metres away.</td><td class="num">2</td></tr>
    </tbody>
  </table>
  <ul class="dl-labelguide__tips"><li>...three tips...</li></ul>
  <div class="dl-labelguide__colors"><b>Colour band</b><span class="dl-labelguide__chip" data-color="accent">JAN</span>...</div>  <!-- only when colors is not empty -->
</details>
```

Extra HTML attributes (`id`, `class`, `data-*`) and `can="module.action"` go on the root `details`.

## Props

Constructor of `Devlib\Components\LabelGuide` (3 props). Blade names are kebab-case; arrays and booleans use the `:` binding.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `size` | `?string`, `null` | Key of the size that is currently chosen: `sm`, `md` or `lg`. The matching table row gets `class="is-active"` (accent-soft background). `null` or any other string highlights nothing (the value is NOT normalised, unlike `size` in `x-dl::label` and `x-dl::label-sheet`). Pass the same size the sheet uses. |
| `colors` | `array`, `[]` | Meaning of the colour band: `[['color' => 'accent', 'label' => 'JAN'], ...]`. Both keys are read directly by the view, so a row without `color` or `label` raises an error. `color` becomes `data-color` of a chip; the chip colours exist for `accent`, `green`, `tangerine`, `violet`, `lime`, `pink`, `navy`, `yellow`, `teal`, `orange`, `amber` and `danger`; `neutral` or any other name shows the default grey chip. `label` is data: printed as given, NOT translated (translate it yourself or wrap data in `raw()`). Empty array = no "Colour band" row. |
| `open` | `bool`, `false` | `true` adds the `open` attribute so the panel starts expanded. The user can still close it; the state is not remembered. |

The sizes themselves are not a prop. They come from the constants `Label::SPEC` (width and height in mm, labels per A4: `sm` 60 x 40 mm and 21, `md` 100 x 50 mm and 10, `lg` 100 x 150 mm and 2), `Label::SIZES` (text of the size) and `LabelGuide::USES` (text of the use). To add or change a size, change those constants, not the screen.

## Behavior

- The panel is collapsed unless `open` is true. Click or Enter/Space on the summary toggles it (native `<details>`).
- The table has always three rows (the order of `Label::SPEC`: `sm`, `md`, `lg`). Only the row of `size` is tinted. The size cell is bold in every row.
- The tips are fixed text (`LabelGuide::TIPS`):
  1. Label roll (label printer): choose the roll paper size in the print dialog, margins None, scale 100%. One label = one page.
  2. A4 sheet (office printer): paper A4, margins None or Minimum, scale 100%, turn on Background graphics so the colour band prints, cut along the label borders.
  3. No colour printer: the band still prints the label type and the mark as text, so the label stays readable.
- The numbers in the table (labels per A4 sheet) agree with the print rule of `x-dl::label-sheet` (A4 portrait, 5 mm margin, no gap).
- Language: the summary, the column heads, the "use" texts, the tips and `Colour band` pass through `Lang::text()` at render, so the application dictionary can translate them. These texts are not in the built-in dictionary of the package (checked in `src/Lang.php`), so without an application translation they stay English. The size text (`60 x 40 mm`), the counts and the chip labels are data.
- Access: `can="module.action"` hides the whole panel (nothing rendered) when the ability is missing.
- Accessibility as implemented: native `<details>`/`<summary>` (keyboard and screen-reader friendly); the icon is decorative (`<i>`); the active row is shown by colour only (no `aria-current`); chips are plain `span` with text, so the colour is never the only information as long as `label` is filled.

## Data and events

- No data layer, nothing posted, nothing fetched, no `dl:*` event, no `el.dlComponent`.
- Markup hooks: `.dl-labelguide`, `.dl-labelguide__table`, `tr.is-active`, `.dl-labelguide__tips`, `.dl-labelguide__colors`, `.dl-labelguide__chip[data-color]`; root `data-dl`, `data-dl-class`, `data-dl-src` for the developer catalog.
- PHP side: `rows()` builds the table rows (`key`, `size`, `use` translated, `a4`); constants `USES`, `TIPS`. A subclass can override `rows()`, but the texts are read through `self::USES` and `LabelGuide::TIPS` (not `static::`), so a subclass cannot replace those constants; and the label and sheet components still know only `sm`, `md`, `lg`.
- Colours of the chips follow the same palette tokens as the label band (`--dl-accent`, `--dl-green`, ... defined in the theme), so they change with the theme; `yellow` chips use dark text.

## Rules and pitfalls

- Give `colors` the same colour names and the same order as the labels use (for arrival-month bands, `Label::MONTH_COLORS` gives Jan to Dec: accent, green, tangerine, violet, lime, pink, navy, yellow, teal, orange, amber, neutral; red is never used for a month because red means dead stock or expired). `Label::monthColor($date)` returns the colour name for a `YYYY-MM-DD` date.
- Always write a text `label` for every chip (month name, type). Colour alone must not carry the meaning; the label itself also prints the mark as text for black-and-white printers.
- Pass `size` as the sheet's size so the operator sees which row applies. An invalid value is not corrected and simply highlights nothing.
- `colors` rows need both keys (`color`, `label`); a missing key is an error, not an empty chip.
- The three tips are English fixed text; translate them in the application dictionary if the screen is not English.
- Do not rely on `open` to remember the user's choice; it is the initial state only.
- Code oddity (not fixed): the header comment of `LabelGuide.php` says the selected row is made bold, but the code and CSS only tint it (`tr.is-active td` accent-soft); the size cell is bold in all rows (`<b>` is always written).
- Code oddity (not fixed): the second tip says to turn on Background graphics for the colour band, but `.dl-label` already has `print-color-adjust: exact` (inherited by the band), so the tip is conservative. Not checked in a real print preview.
- Code oddity (not fixed): `colors` is read by key (`$c['color']`, `$c['label']`) with no default, while `x-dl::label` quietly accepts an unknown `color` (it falls back to the neutral band). A typo in a chip colour only shows a grey chip.

## Example

Copied in shape from the warehouse label demo (fictional data), with the 12 month colours:

```blade
<x-dl::label-guide size="md" :open="true" :colors="[
    ['color' => 'accent', 'label' => 'JAN'], ['color' => 'green', 'label' => 'FEB'], ['color' => 'tangerine', 'label' => 'MAR'],
    ['color' => 'violet', 'label' => 'APR'], ['color' => 'lime', 'label' => 'MAY'], ['color' => 'pink', 'label' => 'JUN'],
    ['color' => 'navy', 'label' => 'JUL'], ['color' => 'yellow', 'label' => 'AUG'], ['color' => 'teal', 'label' => 'SEP'],
    ['color' => 'orange', 'label' => 'OCT'], ['color' => 'amber', 'label' => 'NOV'], ['color' => 'neutral', 'label' => 'DEC'],
]" />
```

Only the sizes and tips, collapsed:

```blade
<x-dl::label-guide />
```

Inside the label sheet:

```blade
<x-dl::label-sheet title="Labels" :count="$count" size="lg" paper="a4">
    <x-slot:guide>
        <x-dl::label-guide size="lg" />
    </x-slot:guide>
    ...labels...
</x-dl::label-sheet>
```

## Related

- `x-dl::label-sheet`: the print sheet that normally shows this guide in its `guide` slot.
- `x-dl::label`: the label whose sizes, uses and colour band this panel explains.
- `x-dl::qr`, `x-dl::barcode`: the codes printed on those labels.
- `x-dl::scan`: the field that reads the printed labels back.
