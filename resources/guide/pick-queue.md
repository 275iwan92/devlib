## Overview

`<x-dl::pick-queue>` is the queue screen for choosing source documents that can be turned into ONE follow-up document. Each row is a source document (an order, a request) with how much of its goods is ready. The user ticks the rows that belong together and presses the single main button in the action bar above the list; the server then creates one document from all ticked ids.

It was built for "which orders can go on a delivery now", but it is generic: any list where rows have a readiness (ready / ordered), a rule for what may be merged (`group`), and one bulk action.

Layout, top to bottom: optional title, status chips with counts, the action bar (shown only while something is ticked), a column header line (desktop), the rows. There is no button per row and no control below the list.

Pick another object when:

- rows are edited or opened one by one: `x-dl::grid` (it has `bulk` actions, but no readiness, no merge rule and no single "create one document" bar);
- the user works through steps of one record: `x-dl::wizard`;
- the choice is one value in a form: `x-dl::field.select` / `x-dl::field.lookup`.

### Generated markup

```html
<form class="dl-pq" data-dl="x-dl::pick-queue" data-dl-pq data-pq-filter="ready" method="post" action="...">
  <div class="dl-pq__chips"><button class="dl-pq__chip is-active" data-pq-chip="ready" data-pq-states="ready">Fully ready <b>3</b></button> ...</div>
  <div class="dl-pq__bar" data-pq-bar hidden>
    <span class="dl-pq__sum"></span><span class="dl-pq__note"></span><button type="submit" class="dl-btn dl-btn--primary dl-pq__go"></button>
  </div>
  <ul class="dl-pq__list">
    <li class="dl-pq__row" data-pq-row data-pq-state="ready" data-pq-group="a|b|vat">
      <label class="dl-pq__pick"><input type="checkbox" name="ids[]" value="412"><i></i></label>
      <div class="dl-pq__main">...</div><div class="dl-pq__qty">badge + progress + numbers</div><div class="dl-pq__due">...</div>
    </li>
  </ul>
</form>
```

## Props

Constructor of `Devlib\Components\PickQueue` (17 props). All text props are translated through the dictionary (write them in English).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `title` | `?string`, `null` | Heading above the chips. Omit it when the screen already has a title. |
| `rows` | `array`, `[]` | The rows, see "Row shape" below. Order is kept; sort on the server. |
| `states` | `array`, ready / partial / waiting | `[stateKey => ['label' => .., 'tone' => .., 'pick' => bool]]`, merged over the defaults (`ready` = "Fully ready" `success`, `partial` = "Partly ready" `warn`, `waiting` = "Not ready" `danger`, only `waiting` has `pick => false`). `tone` colours the badge and the progress bar. `pick => false` makes the rows of that state not tickable. Add your own keys freely. |
| `filters` | `array`, one chip per state plus "All" | `[['key' => .., 'label' => .., 'states' => [stateKeys] or null (= all rows)]]`. The count on each chip is computed from `rows`. |
| `filter` | `?string`, first filter | Key of the chip that is active at load. |
| `action` | `?string`, `null` | POST url. With it the main button submits the form (with `@csrf`) and the server receives `name[]` = ticked ids. Without it nothing is posted: listen to `dl:pick-queue` instead. |
| `name` | `string`, `'ids'` | Name of the checkbox array posted to `action`. |
| `button` | `string`, `'Create 1 document from :n'` | Main button text when two or more rows are ticked; `:n` = number ticked. |
| `button-one` (`buttonOne`) | `string`, `'Create document'` | Text when exactly one fully ready row is ticked. |
| `button-partial` (`buttonPartial`) | `string`, `'Create document (:have of :want)'` | Text when exactly one partly ready row is ticked; `:have`, `:want`, `:unit` come from the row (locale formatted). |
| `together` | `string`, `'same address, can be merged'` | Note in the bar when two or more ticked rows share the same `group`. |
| `mismatch` | `string`, `'Choose items with the same customer and address'` | Red note in the bar when ticked rows have different `group` values; the main button is disabled. |
| `selected` | `string`, `':n selected'` | Start of the bar summary; the party of the first ticked row follows after a dot when all ticked rows share the group. |
| `row-head`, `qty-head`, `due-head` (`rowHead`, `qtyHead`, `dueHead`) | `string`, `'Order'`, `'Ready / ordered'`, `'Due'` | Column header texts (desktop only). |
| `empty` | `string`, `'Nothing in this list.'` | Shown when the active chip has no rows. |

### Row shape

| Key | Meaning |
|---|---|
| `id` | Value posted when the row is ticked (required). |
| `title` | Document number, bold on the first line. |
| `party`, `place` | Second line: `party · place`. `party` also appears in the action bar. |
| `group` | Merge key. Ticked rows may be merged only when all `group` values are equal. Build it on the server from everything that must match (customer, address, tax status ...), e.g. `'cust7\|addr3\|vat'`. |
| `state` | A key of `states`. |
| `have`, `want`, `unit` | Goods ready and ordered; the bar fills `have / want` and the text shows `5.000 / 5.000 pcs`. |
| `note` | Optional small text after the numbers (for example "done 29 Oct"). Data: wrap it in `raw()` if it must not be translated. |
| `due`, `dueIn` | Due date text and the number of days from today (negative = overdue, shown red). `dueIn` is optional. |
| `pick` | Optional `true` / `false`, overrides `states[state].pick` for this row. |

Extra HTML attributes (`id`, `class`, `style`, `data-*`) go to the root `<form>`. `can="module.action"` works as for every component.

## Behavior

- Filter: pressing a chip shows the rows of its states and hides the others; hidden rows are un-ticked. The count on a chip is the number of rows of that filter regardless of the active one.
- Ticking: a click anywhere on a row ticks or un-ticks it (links, buttons and the checkbox label keep their own click). Rows that cannot be picked have a dashed box, a dimmed look and ignore clicks.
- Action bar: hidden while nothing is ticked. With rows ticked it shows `N selected · party`, a note, and one primary button. Same `group` for all: note = `together` (only when N > 1) and the button is enabled with the text for one full row, one partial row or several rows. Different groups: red `mismatch` note and the button is disabled. The bar is sticky at the top of its scroll container.
- Phone (width under 600 px, or phone landscape): column headers disappear, each row becomes a card (box, number and party, due on the right, readiness under them), chips and the main button grow to touch size, the bar stays at the TOP (no control below the screen, see `skills/devlib/phone.md`).
- Look: all colours come from the theme tokens; dark mode and themes follow.
- Accessibility as implemented: native checkboxes (focus ring on the box), chips are buttons with `aria-pressed`, the bar has `role="status"`. The column header line is `aria-hidden`.

## Data and events

- Input is the props only; there is no slot. Counts, pickability and the due text are computed in PHP from `rows`.
- Posted: `name[]` (default `ids[]`) with the `id` of every ticked row, plus the CSRF token. The server must re-check every rule (same customer, quantity still available); the browser rule is a convenience.
- Event `dl:pick-queue` (bubbles, cancelable) is fired on the form just before submit with `detail.ids`. Call `preventDefault()` to do the work yourself; without `action` the form is never submitted.
- JS class `PickQueue` (`resources/assets/js/components/pick-queue.js`, selector `[data-dl-pq]`).

## Rules and pitfalls

- `group` is the whole merge rule. If two rows must not be merged, their groups must differ; do not rely on the party name.
- Do not put a per-row button next to the rows; the bar is the only action, by design.
- The default texts are generic English; pass your own `button*`, `together`, `mismatch` and the column heads so the screen says "delivery" or "order" in its own words.
- `have` / `want` are numbers, not formatted text; the component formats them with the page locale.
- A row whose `state` is not in `states` is shown with a neutral badge and is tickable.
- The first `filter` must exist in `filters`, otherwise all rows are shown with no chip active.
- Re-validate on the server: the page may be hours old, quantities change.
- No lint rule covers this object.

## Example

```blade
<x-dl::pick-queue title="Ready to ship" :rows="$rows" :action="route('delivery.create')"
    button="Create 1 delivery from :n orders" button-one="Create delivery"
    button-partial="Create delivery (:have of :want :unit)"
    together="same address, can be merged" mismatch="Choose orders with the same customer and address" />
```

Listening instead of posting (no `action`):

```blade
<script>document.addEventListener('dl:pick-queue', e => { e.preventDefault(); console.log(e.detail.ids); });</script>
```

## Related

- `x-dl::grid`: ordinary lists with `bulk` actions, `phoneCards` for the phone.
- `x-dl::badge`, `x-dl::progress`: used inside every row.
- `x-dl::queue-board`: read-only queue per machine.
- `x-dl::delivery-track`: the route diagram of a delivery.
- `skills/devlib/phone.md`: phone rules (no control at the bottom of the screen).
- `skills/devlib/components.md`: generated prop list.
