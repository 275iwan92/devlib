## Overview

`<x-dl::coverage>` is a completeness matrix: one row per group (for example a machine set or a customer), one column per document type or item, and in each cell a small status chip that can be a link. It answers "what is still incomplete" at a glance. A row can have an arrow that opens a detail area (lists of text lines). A search box and filter chips above the table work in the browser (`coverage.js`); there is no request to the server.

Use it when the question is "for each X, which of A, B, C exist and in what state". Do NOT use it for:

| Need | Better object |
|---|---|
| A normal record list with search, sort, paging, export | `x-dl::grid` |
| Editable cells | `x-dl::sheet` |
| User-arranged cross-tab of measures | `x-dl::pivot` |
| Drill into a master / detail hierarchy | `x-dl::drilldown` |
| A matrix where the user picks cells | `x-dl::pick-matrix` |

### Markup produced

```html
<div class="dl-cov" data-dl="x-dl::coverage" data-dl-class="Coverage ← DevlibComponent" data-dl-src="penelepon (baris + status per sel)" data-dl-js="Coverage" data-dl-cov>
  <div class="dl-cov__bar">
    <div class="dl-grid__search"><input type="search" data-cv-q placeholder="Search…"></div>
    <button class="dl-chipbtn is-active" data-cv-filter="">All <b>12</b></button>
    <button class="dl-chipbtn" data-cv-filter="incomplete">Incomplete <b>3</b></button>
  </div>
  <div class="dl-cov__scroll"><table class="dl-cov__table">
    <thead><tr><th class="dl-cov__arrow"></th><th>Item</th><th>Sub</th><th>Doc A</th>...</tr></thead>
    <tbody>
      <tr class="dl-cov__row" data-cv-row data-flags="incomplete" data-text="code title sub">
        <td class="dl-cov__arrow"><button class="dl-cov__toggle" data-cv-toggle aria-expanded="false" aria-label="Details">...</button></td>
        <td class="dl-cov__title"><b>CODE</b> Title</td><td class="dl-cov__sub">Sub</td>
        <td><a class="dl-badge dl-badge--success dl-badge--pill dl-cov__chip" href="...">Complete</a></td>
      </tr>
      <tr class="dl-cov__detail" data-cv-detail hidden> ... groups of lists ... </tr>
    </tbody>
  </table></div>
  <p class="dl-cov__empty" data-cv-empty hidden>Nothing matches.</p>
</div>
```

Extra HTML attributes (`id`, `class`, `style`, `data-*`) and `can="module.action"` go on the root `div`; without the ability nothing is rendered.

## Props

Constructor of `Devlib\Components\Coverage` (8 props). Use `:prop="expr"` for arrays.

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `columns` | `array` | `[]` | The matrix columns as `key => label`. The keys select the cell in every row (`rows[].cells[key]`); the order of `columns` is the column order. Labels are translated through the dictionary (translated list). |
| `rows` | `array` | `[]` | The matrix rows, see [Row shape](#row-shape). |
| `filters` | `array` | `[]` | Filter chips as `flag key => label`. Each chip shows the count of rows whose `flags` contain the key (computed on the server once, from all rows). A first chip `All` with the total row count is always added. Labels are translated. A flag key must not contain spaces. |
| `head` | `string` | `'Item'` | Header of the title column. Not translated (it is not in the translated prop list): pass it in the wanted language. |
| `sub-head` | `string` | `''` | Header of the optional second text column. When it is `''` the second column is not rendered at all (also `rows[].sub` is then not shown). Not translated. |
| `search` | `string` | `'Search…'` | Placeholder and `aria-label` of the search box. Translated. |
| `empty` | `string` | `'Nothing matches.'` | Message shown when search and filter hide every row. Translated. |
| `source` | `?string` | `null` | Free text naming where the data comes from, shown in the catalog / Manifest (`data-dl-src`). Default `penelepon (baris + status per sel)`. No visual or runtime effect. |

### Row shape

`rows[]` items:

| Key | Meaning |
|---|---|
| `code` | Optional short code, printed bold in the accent colour before the title. Searchable. |
| `title` | Main text of the row (data, not translated). Searchable. |
| `sub` | Text of the second column (only when `sub-head` is set). Searchable. |
| `cells` | `columnKey => cell`, where a cell is `['label' => text, 'tone' => tone, 'url' => ?, 'icon' => ?]` or `null` / missing. |
| `detail` | Optional list of groups `['title' => text, 'items' => [text, ...], 'wide' => bool]`. When not empty the row gets an arrow button that opens an extra row; each group is a heading and a numbered list. `wide => true` spans the full width and lays its list out in 2 columns. |
| `flags` | List of filter keys that apply to the row (keys of `filters`). |
| `id` | Documented in the class comment but not read by the view (see the notes below). |

Cell keys:

| Cell key | Meaning |
|---|---|
| `label` | Chip text (required when the cell exists). Printed as given. |
| `tone` | Badge tone: `neutral` (default), `accent`, `success`, `warn`, `danger`, `info`, `violet`, `tangerine`, `yellow`. Rendered as `dl-badge dl-badge--<tone> dl-badge--pill`. |
| `url` | When not empty the chip is an `<a href>` (same tab) with class `dl-cov__chip`; otherwise a `<span>`. |
| `icon` | Optional icon before the label: a meaning from `Devlib\Icon::MAP` (for example `confirm`, `warning`, `info`) or an `fa-...` class name; rendered as `<i class="fa-solid ...">`. |

A missing or `null` cell is shown as a dash (`—`, class `dl-cov__none`).

## Behavior

### Search and filters

- The search box is a live filter on input. Each row has `data-text` = lower-cased `code title sub` (computed on the server); the typed text (trimmed, lower-cased) must be contained in it. Cell labels and `detail` texts are NOT searched.
- A filter chip shows only rows whose `data-flags` (space separated) contain its key; `All` (empty key) shows every row. The active chip gets `is-active`. Search and filter combine with AND.
- The counts on the chips are fixed at render time; they do not change while searching.
- Rows that do not match get the `hidden` attribute; an open detail of a hidden row is closed. When no row is left the `empty` message appears.

### Detail rows

The arrow button (`data-cv-toggle`) sets `aria-expanded`, adds `is-open` to the row (the arrow turns) and unhides the next sibling row `[data-cv-detail]`. Detail rows start closed. The button exists only for rows with a non-empty `detail`; the button has `aria-label="Details"` (translated), the rest of the matrix has no extra `aria-*`.

### Language and access

Translated from English: `columns` labels, `filters` labels, `search`, `empty`, and the fixed words `All` and `Details`. Not translated: `head`, `sub-head`, `rows` content (titles, subs, chip labels, detail texts). The class comment says chip status texts are always written in English by the caller, but the object does not translate them; pass the wanted wording (or translate with `Lang::text()`). `can="module.action"` hides the whole matrix without the ability.

### Layout

The table scrolls horizontally inside `.dl-cov__scroll` on narrow screens. Rows highlight on hover. Colours come from theme tokens.

## Data and events

- Server data in, markup out. Nothing is posted or requested; filtering is done on the DOM only.
- No `dl:*` event is emitted. `el.dlComponent` is the `Coverage` instance with the methods `apply()` (re-run search and filter, for example after changing the search input value from script) and `open(row, on)` (open or close the detail of a `[data-cv-row]`). Methods are auto-bound. The root carries `data-dl-js="Coverage"`, so a project can extend the class (`Devlib.register(class MyCoverage extends Devlib.classes.Coverage {...})` with a PHP subclass returning that name from `jsClass()`).
- Hooks: `data-dl-cov` (root), `data-cv-q`, `data-cv-filter`, `data-cv-row` (with `data-flags`, `data-text`), `data-cv-toggle`, `data-cv-detail`, `data-cv-empty`. Classes: `dl-cov`, `dl-cov__bar`, `dl-cov__scroll`, `dl-cov__table`, `dl-cov__row`, `dl-cov__title`, `dl-cov__sub`, `dl-cov__chip`, `dl-cov__none`, `dl-cov__detail`, `dl-cov__groups`, `dl-cov__group`, `dl-cov__group--wide`, `dl-cov__empty`; chips reuse `dl-badge*` and `dl-chipbtn`.

## Rules and pitfalls

- The cell keys must equal the keys of `columns`; a cell under an unknown key is never shown.
- Put the meaning in the label, not only in the tone: `tone` is colour only (`success` = complete, `danger` = missing, and so on is your convention).
- Give `flags` only keys that exist in `filters`; a flag that is not in `filters` still filters nothing and is invisible.
- Flag keys are split on spaces: do not use spaces in them.
- Filter counts are computed from all rows once; if the page is long-lived and rows change in script, the counts do not follow.
- Row text for search is built on the server from `code`, `title` and `sub` only. If users must find a row by a cell value, add that word to `sub` or `title`.
- `url` is written to `href` as given (escaped, not validated): never put user input there without checking the scheme.
- Large matrices (hundreds of rows) are rendered fully in the page; there is no paging. For big sets use `x-dl::grid`.
- No lint rule is specific to this object. General screen rules `L001`, `L002`, `L003` apply to the Blade file.
- There is currently no catalog demo page for this object (the earlier `grid-coverage` demo page was removed from the catalog; the component itself is unchanged).

### Notes on inconsistencies found while writing this entry (not fixed)

- The class comment lists `id` among the row keys, but the view never reads it.
- The REMARK header says the object is used by `grid-coverage`; that demo page no longer exists (removed with the Grid Variant page), so the catalog has no live example.
- `resources/assets/js/components/guide-link.js` registers the title selector `.dl-cov__title` for `x-dl::coverage`. In the view `.dl-cov__title` is the title cell of every row, not a heading, so with the Technical Guide link enabled (`window.DL_GUIDE`) the first data row's title is wrapped in a link to this guide.
- The class comment says status texts are "always English"; the cells are echoed without translation, so they stay in the language the caller passes.
- `head` and `sub-head` are visible texts but are not in the translated prop list of `DevlibComponent`, while `search` and `empty` are.

## Example

Built from the class header and the view (no catalog demo exists); fictional data.

```blade
<x-dl::coverage head="Machine set" sub-head="Plant" search="Search machine set…"
    :columns="['manual' => 'Manual', 'sop' => 'SOP', 'checklist' => 'Checklist']"
    :filters="['incomplete' => 'Incomplete', 'complete' => 'Complete']"
    :rows="[
        [
            'code' => 'MS-01', 'title' => 'Offset press line A', 'sub' => 'Plant 1',
            'flags' => ['complete'],
            'cells' => [
                'manual' => ['label' => 'Complete', 'tone' => 'success', 'url' => '/docs/ms-01/manual', 'icon' => 'confirm'],
                'sop' => ['label' => 'Complete', 'tone' => 'success', 'url' => '/docs/ms-01/sop'],
                'checklist' => ['label' => 'Complete', 'tone' => 'success'],
            ],
        ],
        [
            'code' => 'MS-02', 'title' => 'Die-cutting line B', 'sub' => 'Plant 2',
            'flags' => ['incomplete'],
            'cells' => [
                'manual' => ['label' => 'Complete', 'tone' => 'success'],
                'sop' => ['label' => 'Draft', 'tone' => 'warn', 'url' => '/docs/ms-02/sop'],
                'checklist' => null,
            ],
            'detail' => [
                ['title' => 'Machines in this set', 'items' => ['Die-cutter DC-2', 'Stripping unit ST-1']],
                ['title' => 'Missing', 'items' => ['Daily checklist', 'SOP approval'], 'wide' => true],
            ],
        ],
    ]" />
```

## Related

- `x-dl::grid`: the searchable, sortable record list; the usual place to open a row's documents.
- `x-dl::sheet`: editable cell grid.
- `x-dl::pivot`: cross-tab of measures chosen by the user.
- `x-dl::drilldown`: summary rows that open the records behind them.
- `x-dl::badge` (class `dl-badge`): the chip style and tones used for the cells.
- `x-dl::stat-box`, `x-dl::status-card`: filterable status counts and cards.
- `Devlib\Icon`: the icon meanings accepted by the cell `icon`.
