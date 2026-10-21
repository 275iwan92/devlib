## Overview

`<x-dl::doc-footer>` is the footer of a Master-Detail document for screens whose **totals are calculated by the server** (for example a model method such as `totals()`). It has the same look as the footer of `x-dl::lines` (the approved Master-Detail form, 2026-09-30): on the left an optional tab area for adjustments (Discount, Tax, Other Costs, Notes, ...) and a dashed "Amount in words" box in the free space; on the right a **Summary** box that is always visible and lists the totals you pass in, with the Grand Total highlighted.

The object is display only. It calculates nothing, reads no input and posts nothing of its own; the numbers and the words are texts prepared by the server. Fields you place inside a tab slot are ordinary fields of the surrounding form.

### Use it, or use another object

| Need | Object |
|---|---|
| Footer under a read-only or server-totalled list (totals come from the server) | `x-dl::doc-footer` |
| Editable detail lines whose footer must recalculate live in the browser (discount %, tax, other costs, amount in words) | `x-dl::lines` (it has this footer built in) |
| The printed totals box of a document | `x-dl::print-doc` (totals come from the `Document` object) |
| Plain grid of key figures on a dashboard | `x-dl::kpi-row` |

### Markup

```html
<div class="dl-lines__footer dl-docfoot" data-dl="x-dl::doc-footer" ... [id, class, ...]>
  <div class="dl-lines__left">
    <div class="dl-lines__adj dl-tabs" data-dl-tabs>                       <!-- only with tabs -->
      <div class="dl-tabs__bar" role="tablist"><button type="button" class="dl-tabs__tab is-active" data-tab="discount">Discount</button> ...</div>
      <div class="dl-tabs__panel is-active" data-panel="discount"> slot content </div> ...
    </div>
    <div class="dl-lines__words"><span>Amount in words</span><i>Tiga belas juta ... rupiah</i></div>   <!-- only with non-zero words -->
  </div>
  <div class="dl-lines__totals">                                           <!-- only with summary -->
    <div class="dl-lines__sumh">Summary</div>
    <div><span>Subtotal</span><b>IDR 12.500.000</b></div>
    <div class="dl-lines__dpp">...</div> <div class="dl-lines__grand">...</div>
  </div>
</div>
```

The class `dl-docfoot` adds a border, rounded corners and a 6 px top margin on top of the shared `dl-lines__footer` style. Extra HTML attributes (`id`, `class`, `style`, `data-*`) and `can="module.action"` go on the root `div`; without the ability nothing is rendered.

## Props

Constructor of `Devlib\Components\DocFooter`: **3 props**. Use `:prop="expr"` for arrays.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `tabs` | `array`, `[]` | Tabs of the left area: `key => label`. The first tab is open at the start. For every key there must be a slot of the same name, `<x-slot:key>`, holding the tab content (a missing slot prints an empty panel). The label is translated through the dictionary; the key becomes `data-tab` / `data-panel`. Empty = no tab area (the words box then fills the whole left column). |
| `summary` | `array`, `[]` | Rows of the Summary box, each a **list** `[label, value, kind?]`. `label` and `value` are printed as given (value in bold, right aligned, tabular figures), neither is translated. `kind` is optional: `'grand'` = Grand Total row (larger, top rule, value in the accent colour); `'dpp'` = Tax Base row (dashed top rule). Any other value, or none, is a plain row. Empty = no Summary box; the heading `Summary` is translated. |
| `words` | `?string`, `null` | Amount in words, printed in italics in a dashed box titled `Amount in words` (translated title). The box is omitted when the text is empty, or when it starts with `nol` or `zero` (case-insensitive, whole word), so a zero total shows no words. The text itself is not translated: produce it for the right language and currency, for example `\Devlib\Terbilang::words($grand, 'IDR')`. |

Interaction between the props: `tabs` and `words` share the left column (side by side on wide screens, stacked below 1100 px); `summary` is the right column (stacked below 700 px). All three can be empty, which still renders an empty bordered box: render the component only when there is something to show.

## Behavior

### Layout

- A two-column grid: left column flexible, right column up to 320 px (`.dl-lines__footer`). With both a tab area and a words box the left column places them side by side (tab area up to 560 px), the words box dropped slightly so it lines up with the tab content; with only one of them it takes the full width.
- The Summary box has a fixed small heading, one line per row (label left, value right), and is always visible; tabs only affect the left side.

### Tabs

`tabs.js` (JS class `Tabs`, selector `[data-dl-tabs]`) switches panels on click by `is-active` classes. Opening the page with `?tab=<key>` (a key that exists in this tab area) selects that tab; unknown keys are ignored. Tabs of the footer are independent from the tabs of an enclosing `x-dl::form`; when the footer sits inside a form, the form's required-field check marks and opens the tab that holds an invalid field by the same `data-dl-tabs` / `data-tab` markup.

### Translation and language

Only the tab labels, the heading `Summary` and the title `Amount in words` go through the dictionary. Summary labels and values, the words text and the content of the slots are written by the screen. Format money with `Devlib\Locale::money()` / `Locale::number()` (display or print language) on the server, and wrap data that could be mistaken for a dictionary phrase in `raw()`.

### Accessibility

The tab bar container has `role="tablist"` but the buttons have no `role="tab"`, `aria-selected` or `aria-controls`, and the panels have no `role="tabpanel"`; the active tab is shown by the `is-active` class only. The Summary rows are plain `div` / `span` / `b` without a table or list role. Keyboard: the tab buttons are native buttons (Tab, Enter, Space).

## Data and events

- The component posts nothing. Fields placed in a tab slot are normal fields of the surrounding `<form>` and are posted with it (names are yours).
- No custom event is emitted. `el.dlComponent` on the `.dl-tabs` element is the `Tabs` instance; the footer root has no JS class of its own.
- Markup hooks: `data-dl-tabs`, `.dl-tabs__bar`, `.dl-tabs__tab` with `data-tab`, `.dl-tabs__panel` with `data-panel`, `.dl-lines__words`, `.dl-lines__totals`, `.dl-lines__sumh`, `.dl-lines__grand`, `.dl-lines__dpp`.
- Server side: compute the totals once (for example from the stored record), format them, and pass them in `summary`; the screen must not recompute in the browser.
- Lint: no rule is specific to this object. General screen rules apply to the Blade file: `L001` (no raw `table` / `button` / `input` / `form`), `L002` (no inline `<script>`), `L003` (no hex colours).

## Rules and pitfalls

- Use a tab key that is a valid PHP variable name: letters, digits and underscore (`discount`, `tax`, `other_costs`). The view reads the slot as `${$key}`; a key with a hyphen cannot become a variable, so its panel would stay empty.
- Do not use a tab key that equals one of the view's own variables, for example `tabs`, `summary`, `words`, `slot`, `attributes`, `dl`, `showWords`, `tag`, `source`, `lineage`, `lint`, `jsClass`, `dictionaryGroup`, or the loop variables `t`, `k`, `row`, `loop`. The slot would not be found or the wrong value would print.
- `summary` rows are positional lists, not associative arrays. A row with fewer than two elements triggers an "Undefined array key" error.
- The `words` zero test looks at the first word only (`nol` / `zero`). Pass the plain output of `Terbilang::words()`; the `# ... #` framing used on paper (`Document::amountWords()`) would defeat the test and print "# Nol rupiah #".
- Summary labels such as `Subtotal`, `Tax Base` and `Grand Total` are not translated by the component. Take them from the dictionary yourself when the screen is multi-language.
- Put the footer in the place of `x-dl::lines` totals only when the totals really are server-made. For editable lines with live totals use `x-dl::lines`; do not mix both footers on one document.
- A form places `detail` outside its locked fieldset, so inputs inside tab slots of a footer placed there are not disabled when the document is Confirmed or Cancelled. Make them `readonly` (or show text) yourself when the document status is locked.
- Totals shown here are display only: the server recalculates and validates on save (project rule: the server decides every action again).
- Catalog: this object has no catalog page and no test; the example below is written from the view and was read-checked, not rendered.
- Code oddity (not fixed): the class REMARK text of `src/Components/DocFooter.php` ends mid-sentence ("... tampilan sama dengan.") and cites a project model method as the example; no screen, demo or test in this repository uses `x-dl::doc-footer`.
- Code oddity (not fixed): the tab bar is hand-written in `render()` (`src/Components/DocFooter.php:46-49`), a copy of the markup in `src/Components/Lines.php:146-149`, instead of rendering `x-dl::tabs`; a change to one copy does not reach the others.

## Example

Server-totalled document with a Discount tab and a Notes tab (fictional figures; rendered inside a form screen that already holds the lines).

```blade
@php
    $summary = [
        ['Subtotal', \Devlib\Locale::money(12500000)],
        ['Discount', '('.\Devlib\Locale::money(500000).')'],
        ['Tax Base', \Devlib\Locale::money(12000000), 'dpp'],
        ['VAT 11%', \Devlib\Locale::money(1320000)],
        ['Grand Total', \Devlib\Locale::money(13320000), 'grand'],
    ];
@endphp
<x-dl::doc-footer :tabs="['discount' => 'Discount', 'note' => 'Notes']" :summary="$summary" :words="\Devlib\Terbilang::words(13320000, 'IDR')">
    <x-slot:discount>
        <x-dl::field.text name="discount_reason" label="Discount reason" value="Year-end agreement" readonly :case-toggle="false" />
    </x-slot:discount>
    <x-slot:note>
        <x-dl::field.text name="remark" label="Remarks" multiline value="Deliver in two batches." readonly />
    </x-slot:note>
</x-dl::doc-footer>
```

Totals only, no tabs and no words:

```blade
<x-dl::doc-footer :summary="[['Subtotal', 'IDR 12.500.000'], ['Grand Total', 'IDR 12.500.000', 'grand']]" />
```

## Related

- `x-dl::lines`: editable detail lines with the same footer calculated in the browser; use it when the totals must update live.
- `x-dl::form`: the screen shell; the `detail` slot is the usual place under the list of lines.
- `x-dl::tabs`: the general tab object (this footer has its own small tab bar).
- `x-dl::print-doc`: the printed counterpart (totals box and amount in words come from the `Document` object).
- `x-dl::field.text`, `x-dl::field.decimal`: fields typically placed in the tab slots.
- `skills/devlib/form.md`: Master-Detail form rules.
