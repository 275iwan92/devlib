## Overview

`<x-dl::pagination>` draws a ScriptCase-style page footer: on the left "Go to [page] View [10 25 50 100]", in the middle the navigation buttons (first, previous, five page numbers, next, last), on the right the range info `[51 – 75 of 1.284]`. It is a pure server-rendered strip: the component has no script of its own, no name, and posts nothing. The numbers are computed once in PHP from `total`, `per-page` and `page`.

Use it when a screen shows a server-paged list that is not an `x-dl::grid` and the screen wires the buttons itself (listen to clicks, reload with the new `page` / `per-page`). Do NOT use it inside a grid: `x-dl::grid` and `x-dl::lookup-grid` draw their own live footer (same CSS classes `dl-pager*`, but with working `data-p`, `data-pp`, `data-g-goto` hooks) and keep the state in the browser. For a one-off "load more" use a plain `x-dl::button`.

Generated markup:

```html
<div class="dl-pager" data-dl="x-dl::pagination" data-dl-class="Pagination ← DevlibComponent" data-dl-src="—" data-dl-pager>
  <div class="dl-pager__left">Go to <input type="number" min="1" max="52" value="3" class="dl-pager__goto"> View
    <button type="button" class="dl-pager__size">10</button><button type="button" class="dl-pager__size is-active">25</button> ...
  </div>
  <div class="dl-pager__nav">
    <button type="button" disabled title="First page">⏮</button><button type="button" disabled title="Previous">◀</button>
    <button type="button" class="is-active">3</button> ...
    <button type="button" title="Next">▶</button><button type="button" title="Last page">⏭</button>
  </div>
  <div class="dl-pager__info">[51 – 75 of 1,284]</div>
</div>
```

Extra HTML attributes (`id`, `class`, `style`, `data-*`) go on the root `div`; `class` is merged with `dl-pager`. `can="module.action"` is supported (without the ability nothing is rendered). The root carries `data-dl`, `data-dl-class`, `data-dl-src` (developer catalog); `data-dl-pager` is a marker only, no script reads it.

## Props

Constructor of `Devlib\Components\Pagination` (4 props). Use `:prop="expr"` for anything that is not a plain string (`:total="1284"`, `:sizes="[20, 50]"`).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `total` | `int`, `0` | Total number of rows over all pages. Drives the number of pages and the info line. `0` gives one page and the info `[0 – 0 of 0]`. |
| `per-page` | `int`, `25` | Rows per page. The button in `sizes` equal to it (strict integer comparison) gets `is-active`. Values below 1 are treated as 1 when counting pages. |
| `page` | `int`, `1` | Current page, 1-based. It is the value of the Go-to input, the `is-active` page number, and decides which buttons are disabled and which five numbers are shown. It is not clamped to the valid range (see pitfalls). |
| `sizes` | `array`, `[10, 25, 50, 100]` | The rows-per-page choices, one `dl-pager__size` button each, in the order given. Give integers: a size written as a string never matches `per-page`, so no button is marked active. An empty array removes the buttons (the "View" word stays). |

Computed values (not props): `last = max(1, ceil(total / max(1, per-page)))`; `from = min(total, (page-1) * per-page + 1)`; `to = min(total, page * per-page)`; `start = max(1, min(page - 2, last - 4))`. The page-number window is `start .. min(last, start + 4)`, so at most five numbers: the current page sits in the middle when possible, and the window is pushed against the start or the end.

## Behavior

- Static strip. Nothing happens on click: the buttons are `type="button"` without `data-*` hooks, the Go-to input has no listener, and nothing emits an event. The screen must bind its own handlers (for example by selecting `.dl-pager__nav button`, `.dl-pager__size`, `.dl-pager__goto` inside the root) or re-render the page with new props.
- Disabled state: first and previous are `disabled` when `page <= 1`; next and last are `disabled` when `page >= last`.
- The Go-to input is `type="number"`, `min="1"`, `max=<last>`, `value=<page>`; the browser enforces the range only on its spinner / validation, not on typing.
- Styling (`resources/assets/devlib.css`): flex row that wraps (`dl-pager`), 30 px buttons, the active page and the active size use the navy fill, disabled buttons have 45 % opacity. Font size follows `--dl-font-scale`. Inside `.dl-grid [data-g-pager]` the strip gets rounded bottom corners.
- Locale and language: the words "Go to", "View", "Previous", "Next", "First page", "Last page" and the info template come from the dictionary and follow the display language at render (`g_goto`, `g_view`, `g_prev`, `g_next`, `g_range`; the first/last titles use the English phrases "First page" / "Last page"). The three numbers in the info line are formatted with `Locale::number()` (`1.284` in Indonesian, `1,284` in English); the page numbers, the Go-to value and the size buttons are plain integers. The language is not switched live: the page must be re-rendered (the language switch reloads the page for signed-in users).
- Translation Dictionary group: `grid` (Pagination is listed in `DevlibComponent::GROUP_OF['grid']`).
- Accessibility as implemented: native `<button>` elements (Tab order, Enter/Space work once handlers exist). Only the first/previous/next/last buttons have a `title`; the page-number buttons, the size buttons and the Go-to input have no label, `aria-label` or `aria-current`; the nav has no landmark role. Add them in the screen if required.

## Data and events

- Input only: the component reads `total`, `per-page`, `page` and `sizes`; it has no `name` and nothing is submitted with a form (the Go-to input has no `name`).
- No `dl:*` event and no `el.dlComponent` (there is no JS class: `jsClass()` is `null`). The root has the plain hooks `data-dl-pager`, `.dl-pager__left`, `.dl-pager__goto`, `.dl-pager__size`, `.dl-pager__nav`, `.dl-pager__info`.
- Typical server flow: read `page` and `per-page` from the query string, query with `forPage()` / `paginate()`, pass `$paginator->total()`, `$paginator->perPage()`, `$paginator->currentPage()` to the component, and let the screen's buttons navigate to `?page=N&per_page=M`.

## Rules and pitfalls

- Do not use it for `x-dl::grid` / `x-dl::lookup-grid`: they already render and drive their own pager.
- It is not interactive by itself; a screen that shows it without wiring leaves dead buttons. If you need a working pager with no code, use a grid.
- Pass integers. `:per-page="'25'"` or string sizes break the strict `is-active` match.
- `page` is not clamped. `page` above `last` shows the last five numbers with none active, disables next/last, and the info line collapses to `[total – total of total]`. `page` of 0 or less gives a negative `from`. Clamp in the controller.
- `per-page` that is not in `sizes` simply shows no active size button.
- The info line uses the page range clipped to `total`; with `total="0"` it reads `[0 – 0 of 0]` and the single page 1 is still drawn.
- Row counts of data (the `total` value) are numbers, not text: nothing in this component needs `raw()`.
- Code oddity (not fixed): `src/Components/Pagination.php:37` writes the marker `data-dl-pager` but no script in `resources/assets/js` reads it (the grid pager uses `data-g-pager`), so the strip is inert on its own; the REMARK header says nothing about this.
- Code oddity (not fixed): `src/Components/Pagination.php:42` and `:44` take the first/last button titles through `Lang::text('First page')` / `Lang::text('Last page')`, while the other titles use `Lang::t('g_*')` keys (the dictionary key `g_first` / `g_last` exists and is used by the grid).

## Example

Taken from the catalog demo (`site/resources/views/demos/pagination.blade.php`):

```blade
<div class="dl-demo" style="max-width:900px;padding:0">
    <x-dl::pagination :total="1284" :per-page="25" :page="3" />
</div>
```

With a Laravel paginator and custom sizes:

```blade
<x-dl::pagination :total="$rows->total()" :per-page="$rows->perPage()" :page="$rows->currentPage()" :sizes="[20, 50, 100]" />
```

## Related

- `x-dl::grid`, `x-dl::lookup-grid`: data tables that include their own live pager (same `dl-pager*` classes, with `data-p` / `data-pp` hooks).
- `x-dl::lang-text`, `x-dl::lang-switch`: dictionary text and the language choice that change the words in this strip on the next render.
- `x-dl::button`, `x-dl::icon-button`: for a custom "load more" or step control instead of a footer.
- `skills/devlib/components.md`: generated prop list.
