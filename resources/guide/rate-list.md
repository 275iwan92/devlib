## Overview

`<x-dl::rate-list>` is the review list of delivered work: one table row per item (form, grid, report, dashboard, engine, object or feature) with module, type, name, who built it, when it was built and last changed, its QC status, a 1-5 star rating and a note. The reviewer clicks the stars and the rating is saved at once (one `POST` per click). A new rating does not overwrite the old one: the application keeps the history and the list shows the latest rating, who gave it and how many ratings the item has.

The object also filters the list in the browser: a search box (name or module), one type chip (All / each type), toggle chips per agent, "Not rated" and "3 stars or less".

The class `Devlib\Components\RateList` has no Blade view file (inline `render()`); the behaviour is the JS class `RateList` in `resources/assets/js/components/rate-list.js` (selector `[data-dl-rate-list]`). Each row embeds `x-dl::field.rating` (the star object) through the partial `devlib::components.field.rating`.

### When to use it, and when NOT

- Use it for a management or QC screen where a person judges finished items one by one and the verdict must be recorded immediately (the "By Object", "By Engine" and "By Feature" pages of the Executive Summary).
- Use `x-dl::review-feed` when each item needs a decision (accept / reject / ...) with a larger card per item rather than stars in a table.
- Use `x-dl::grid` for general record lists; it has no inline rating that saves per click.
- Use `x-dl::field.rating` alone for a rating inside a form.
- Use `x-dl::table` for a read-only list.

## Props

Constructor of `Devlib\Components\RateList` (9 props). Blade names are kebab-case; arrays and booleans use `:prop="expr"`.

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `items` | `array` | `[]` | The rows. Every item must contain all keys listed below (the view reads them directly; use `null` for empty). With `only`, the constructor drops the items of other types. |
| `types` | `array` | `[]` | `code => label` of the item types, for example `['FORM' => 'Form', 'GRID' => 'Grid']`. Used for the type chips (a chip appears only for a type that has items) and the Type badge. A code missing here is shown as its code. |
| `qc` | `array` | `[]` | `code => [label, tone]` of the QC status, for example `['LOCKED' => ['Locked', 'success'], 'READY_FOR_QC' => ['Waiting QC', 'warn']]`. Tone is a badge tone (`success`, `warn`, `danger`, `info`, `neutral`, ...). An unknown code is shown as the code with tone `neutral`. |
| `agents` | `array` | `[]` | `key => name` of the agents (the builders). One toggle chip per agent; also the name shown in the "Built by" column. |
| `action` | `?string` | `null` | URL that receives the rating (contract below). `null` = nothing is saved (the stars change on screen only). |
| `csrf` | `?string` | `null` | CSRF token sent as `X-CSRF-TOKEN`. Pass `csrf_token()`. |
| `can-rate` | `bool` | `true` | `false` = the stars are read-only and the note is plain text (no input). The server must still check the permission. |
| `only` | `?string` | `null` | A type key: the list shows only that type and the type chips and their separator are not drawn (for pages "By Engine", "By Feature"). |
| `source` | `?string` | `null` | Source text for the developer catalog (`data-dl-src`); default `'daftar hasil kerja + rating'`. |

### Item shape

```php
['id_item' => 17, 'item_type' => 'ENGINE', 'name' => 'Stock Card Engine', 'module' => 'Inventory',
 'qc_status' => 'LOCKED', 'built_by' => 'alpha', 'built_at' => '2026-10-01 09:15:00',
 'changed_by' => 'beta', 'changed_at' => '2026-10-12 14:02:00',
 'rating' => 4, 'note' => 'Clear', 'rated_by' => 'reviewer1', 'rated_at' => '2026-10-13 08:00:00', 'ratings' => 2]
```

| Key | Meaning |
|---|---|
| `id_item` | Numeric id; posted as `item` (a non-numeric id would become `NaN`). |
| `item_type` | A key of `types`. Also drives the badge tone through `RateList::TYPE_TONE` (`FORM` info, `GRID` violet, `REPORT` and `DASHBOARD` success, `ENGINE` warn, `OBJECT` neutral, `FEATURE` accent; other codes neutral). |
| `name`, `module` | Text data (never translated); both are searched. |
| `qc_status` | A key of `qc`. |
| `built_by` | Agent key; empty/`null` shows "Before tracking". |
| `built_at`, `changed_at` | Date strings, shown with `Locale::date`; `null` shows `-`. |
| `changed_by` | Agent key; shown in the "Last change" column only when it differs from `built_by`. Also the agent used by the agent chips (`changed_by`, else `built_by`). |
| `rating` | Integer 1-5, or `null`/0 when not rated. |
| `note` | The note of the latest rating (max 500 characters in the input). |
| `rated_by`, `rated_at` | Who and when gave the latest rating (shown under the note). |
| `ratings` | How many ratings the item has; shown as `N x` when more than 1. |

## Behavior

### Filtering

| Control | Effect |
|---|---|
| Search box | Case-insensitive substring of `name + " " + module`. |
| Type chips | `All` plus one chip per type that has items (with the count). Exactly one active. Not drawn with `only`. |
| Agent chips | Toggle. Several active = any of those agents (OR). An agent matches by `changed_by`, else `built_by`. |
| `Not rated` | Toggle: only rows without a rating. |
| `3 stars or less` | Toggle: only rows rated 1-3. |

All active conditions combine (AND), except the OR between agent chips. The "N shown" counter and the "Nothing matches." line follow the filter. Rows are hidden with the `hidden` attribute; the server is not asked.

### Rating and note

- A click on a star changes the hidden `rating` input (`data-dl-value`); the object saves when the value is above 0. Clicking the same star again clears the star (value 0): the screen and the filters update, but nothing is sent (see the notes).
- The note input saves when its value is changed (on leaving the box, or Enter, which blurs it) and the row already has a rating. A note typed before the first star is sent with the first star click.
- On success the row flashes (`is-saved`, 1.2 s), a toast says "Rating saved" and, if the answer contains `meta`, the small text under the note (who, when, count) is replaced. On any failure (HTTP not 2xx, network) the toast says "Rating was not saved".
- With `can-rate=false` the stars have no interaction and the note is shown as text.

### Translation (Lang) and access (`can`)

Headings, chip texts, placeholder, toasts and the empty text go through `Lang` (no dictionary group; the general group is used). Names of items, modules, agents and the labels you pass in `types`, `qc` and `agents` are shown as given (translate them yourself if needed). `can="module.action"` on the tag hides the whole list when the user lacks the ability. The per-user right to rate is `can-rate`, set by the screen from the user's role.

## Data and events

### Root and hook attributes

```html
<div class="dl-rl" data-dl="x-dl::rate-list" data-dl-rate-list data-action="/summary/rate" data-csrf="..." data-t-saved="Rating saved" data-t-failed="Rating was not saved">
  <div class="dl-rl__bar"> <input class="dl-rl__q" data-rl-q> <span class="dl-rl__chips"> button.dl-eng__chip[data-rl-type=""|<TYPE>] ... <i class="dl-rl__sep"> button[data-rl-flag="agent:<key>"|"unrated"|"low"] </span>
       <span class="dl-rl__count"><b data-rl-count>N</b> shown</span> </div>
  <div class="dl-rl__wrap"><table class="dl-rl__table"> ... <tr data-rl-row data-id data-type data-agent data-rating data-text>
        <td class="dl-rl__stars">...rating...</td> <td class="dl-rl__note"><input data-rl-note maxlength="500"><small data-rl-meta></small></td> </tr>
  <p class="dl-rl__none" data-rl-none hidden>Nothing matches.</p></div>
</div>
```

The built-by cell is `<span class="dl-rl__agent is-<agent key>">` so CSS can colour each agent. Extra attributes and classes go on the root.

### Server contract (`action`)

`POST <action>` with `Content-Type: application/json`, `Accept: application/json`, `X-CSRF-TOKEN: <csrf>`, same-origin credentials:

```json
{"item": 17, "rating": 4, "note": "Clear"}
```

Answer: any 2xx JSON. Optional `"meta": "reviewer1 · 13-Oct-2026 · 3x"`, replacing the small text under the note. Any other status (403 for no permission, 422 for a bad rating, 404 for an unknown item) is a failure toast. The rating is an integer 1-5; the note is trimmed and cut at 500 characters by the server's own rules. The server should insert a new rating row (history) instead of updating, and return the new count in `meta`.

### Events and JS access

There are no `dl:*` events and no `el.dlRateList` member. The object listens to `click` (chips) and `change` (stars and notes), and calls `Devlib.toast`.

## Rules and pitfalls

- **Always check the right to rate on the server.** `can-rate=false` only changes the screen; the demo route aborts with 403 for users who are not developers.
- **Every item key is required.** The view reads `$i['module']`, `$i['built_by']`, `$i['ratings']` and the others without defaults; a missing key is an "Undefined array key" error. Use `null` for empty values.
- **Use numeric item ids**; the script sends `+row.dataset.id`.
- **Do not combine "Not rated" and "3 stars or less"**: they are contradictory and show nothing.
- **Pass `csrf_token()` and a route that returns JSON**; a redirect or an HTML error page is treated as failure.
- One `x-dl::rate-list` per page area is expected; several lists on one page work (each has its own filter state) but each draws its own agent chips.
- Style hooks `dl-eng__chip` are shared with the engine catalog; do not restyle them for this list only.
- No Devlib lint rule inspects `x-dl::rate-list`, and no feature test renders it. It is used by the Executive Summary views in the catalog site (`summary.blade.php`, pages Object / Engine / Feature).

### Notes on inconsistencies found while writing this entry (not fixed)

- Clearing a star (clicking the same star) sets `data-rating` to 0 locally but is never saved; the server still has the old rating, so after a reload the stars come back. The REMARK says a click is "directly saved" and does not mention this.
- The agent chips match `changed_by ?? built_by`, while the visible columns show `built_by` ("Built by") and `changed_by` only when different. Filtering by an agent can therefore show rows where that agent is not the visible builder.
- The class comment lists the item keys but not that all of them are mandatory.
- `types`, `qc` and `agents` labels are not passed through `Lang`, unlike headings and chip words.

## Example

Adapted from the Executive Summary page (`summary.blade.php`), with fictional values:

```blade
@php
    $types  = ['FORM' => 'Form', 'GRID' => 'Grid', 'ENGINE' => 'Engine', 'OBJECT' => 'Object'];
    $qc     = ['LOCKED' => ['Locked', 'success'], 'READY_FOR_QC' => ['Waiting QC', 'warn'], 'NOT_BUILT' => ['Not built', 'neutral']];
    $agents = ['alpha' => 'Agent Alpha', 'beta' => 'Agent Beta'];
    $items  = [
        ['id_item' => 1, 'item_type' => 'GRID', 'name' => 'Customer Order List', 'module' => 'Sales', 'qc_status' => 'LOCKED',
         'built_by' => 'alpha', 'built_at' => '2026-10-01 09:15:00', 'changed_by' => 'alpha', 'changed_at' => '2026-10-12 14:02:00',
         'rating' => 4, 'note' => 'Clear', 'rated_by' => 'reviewer1', 'rated_at' => '2026-10-13 08:00:00', 'ratings' => 2],
        ['id_item' => 2, 'item_type' => 'ENGINE', 'name' => 'Stock Card Engine', 'module' => 'Inventory', 'qc_status' => 'READY_FOR_QC',
         'built_by' => 'beta', 'built_at' => '2026-10-05 10:00:00', 'changed_by' => null, 'changed_at' => null,
         'rating' => null, 'note' => null, 'rated_by' => null, 'rated_at' => null, 'ratings' => 0],
    ];
@endphp

<x-dl::rate-list :items="$items" :types="$types" :qc="$qc" :agents="$agents"
    :action="route('review.rate')" :csrf="csrf_token()" :can-rate="auth()->user()->can('review.rate')"
    source="review.items + review.ratings" />

{{-- only the engines, no type chips --}}
<x-dl::rate-list :items="$items" :types="$types" :qc="$qc" :agents="$agents" only="ENGINE"
    :action="route('review.rate')" :csrf="csrf_token()" :can-rate="false" />
```

Route sketch (insert a new rating each time, answer `meta`):

```php
Route::post('/review/rate', function (Request $r) {
    abort_unless($r->user()->can('review.rate'), 403);
    $rating = (int) $r->input('rating');
    abort_unless($rating >= 1 && $rating <= 5, 422);
    // insert a new row in the ratings table (keep the history), then count them
    $count = 3;

    return ['ok' => true, 'meta' => $r->user()->login.' · '.now()->format('d-M-Y').($count > 1 ? ' · '.$count.'x' : '')];
})->name('review.rate');
```

## Related

- `x-dl::field.rating` (the star object used in every row)
- `x-dl::review-feed` (decision cards that save on click)
- `x-dl::grid` (general record lists)
- `x-dl::badge` (type and QC badges)
- `x-dl::table` (read-only list)
- `x-dl::stat-box` (counters above the list on the Executive Summary pages)
