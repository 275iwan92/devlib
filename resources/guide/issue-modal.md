## Overview

`<x-dl::issue-modal>` is a ready-made warning popup: an `x-dl::modal` with a colored header, one bold explanation line and a table of problems (for example work orders, the process and the reason), plus a Close button. It is open when the page loads and shows itself only when `rows` is not empty. DevLib only displays; the application computes the list (a schedule guard, a shortage check) and passes it in.

Use it when a screen has a list of items the user must see before continuing and the list comes from the server at page load. Pick another object when:

- the content is arbitrary (a form, a picker, free text): `x-dl::modal`;
- one short message is enough: `x-dl::alert`, `x-dl::announce` or `Devlib.toast(...)`;
- a yes/no question must be answered: `Devlib.confirm({...})` / `data-dl-confirm`;
- the list is long and must stay on the page: `x-dl::table` or `x-dl::grid` in the page body.

`IssueModal` extends `Modal`: it inherits its hooks, sizes and behavior (see `x-dl::modal`); this entry lists what differs.

Generated markup (only when `rows` is not empty gets `is-on`; the markup is always rendered):

```html
<div class="dl-mov is-on" data-dl="x-dl::modal id=issues" data-dl-modal-id="issues" role="dialog" aria-modal="true" aria-label="Title">
  <div class="dl-modal dl-modal--lg dl-modal--tone dl-modal--danger">
    <div class="dl-modal__head"><h3><i class="fa-solid fa-triangle-exclamation"></i> Title</h3><button type="button" class="dl-modal__x" data-dl-close-modal aria-label="Close">&times;</button></div>
    <div class="dl-modal__body">
      <p><b>message</b></p>                       <!-- only when message is not empty -->
      <div class="dl-table-wrap" data-dl="x-dl::table"> <table class="dl-table is-striped">...</table> </div>
    </div>
    <div class="dl-modal__foot"> ...default slot (extra buttons)... <button data-dl-preset="close" data-dl-close-modal>Close</button> </div>
  </div>
</div>
```

Extra HTML attributes (`class`, `style`, `data-*`) are passed to the inner modal root. `can="module.action"` is handled by the base class: no ability, nothing is rendered.

## Props

Constructor of `Devlib\Components\IssueModal` (9 props: 6 inherited from `Modal` with different defaults, 3 own).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `id` | `string`, `'issues'` | `data-dl-modal-id` of the popup. Use it with `data-dl-open="<id>"` or `Devlib.open(id)` to show it again after the user closed it. Must be unique on the page (see `x-dl::modal`). |
| `title` | `?string`, `null` | Header text; translated. The default is `null`, and without a title the modal renders NO header (no title, no icon, no tinted header, no X button), leaving only the top border and the Close button in the footer. Always pass a title. |
| `size` | `string`, `'lg'` | Width class: `sm`, `md`, `lg` (default, 760px), `xl`. See `x-dl::modal`. |
| `open` | `bool`, `true` | Open on load. The view passes `$open && count($rows) > 0` to the modal, so with `rows` empty the popup stays hidden even when `open` is `true`. `:open="false"` renders it closed (open it later with `data-dl-open`). |
| `icon` | `?string`, `'fa-triangle-exclamation'` | Icon before the title (needs a title). Standard icon meaning or a Font Awesome solid class. |
| `tone` | `?string`, `'danger'` | Header color: `danger`, `warn`, `info`, `success`. Default red. `null` gives a plain, uncolored header. |
| `message` | `?string`, `null` | Explanation line above the table, printed bold inside `<p><b>`. Translated (standard text prop); wrap data values in `raw()`. Not rendered when empty. Plain text only (escaped). |
| `columns` | `array`, `[]` | Table columns, passed to `x-dl::table`: `key => 'Label'` or `key => ['Label', 'num' => true]` (also `'money' => true`, `'raw' => true`). Labels are translated. |
| `rows` | `list<array>`, `[]` | Table rows: one array per row keyed by the column keys. Cell values are escaped and NOT translated. A cell may be `['href' => url, 'text' => 'WO-0001']` for a link. A key missing in a row prints an empty cell. An empty list hides the whole popup (see `open`). |

Slot: the default slot is placed in the footer BEFORE the Close button (extra action buttons, for example "Open Scheduling"). Close is always present and cannot be removed.

## Behavior

- Everything about opening, closing, Esc, backdrop click, focus and accessibility is inherited from `x-dl::modal`; the close controls are the header X (when a title exists), the footer Close button, Esc and a click on the backdrop. Closing is client-side only: reloading the page shows the popup again while the problems exist, which is the intended "must be seen" behavior.
- Table: striped, one `<tr>` per row, numeric columns right-aligned when the column has `'num' => true`. The table is not sortable, not paged and has no empty message in this popup (an empty list hides the popup instead).
- The footer Close button is `x-dl::button preset="close"`: label follows the page language.
- Language: `title`, `message` and column labels are translated; row values are data and are not. The group in the translation dictionary is `messages`.
- Focus: when the page loads the popup opens by the server-rendered class, so nothing is focused. Opened later by `data-dl-open` / `Devlib.open`, the first non-close button or field gets focus (here usually none exists, so none).

## Data and events

- Display only: no input, no submit, no event, no `dl:*` signal. The data come from the application (the REMARK names a schedule guard); build `columns` and `rows` in the controller or view and pass them as props.
- The popup is a plain DOM element; to react to its closing, listen to `click` on `[data-dl-close-modal]` inside it.
- A button with `data-dl-open="issues"` (same id) re-opens it after it was closed: it works because the popup stays in the DOM when closed.

## Rules and pitfalls

- Always pass `title` (and `columns`): the defaults leave the header out and the table with no columns.
- Do not use it for form input or long reports: its job is to show a list the user acknowledges.
- Values in `rows` that are numbers or dates must be formatted by the caller unless the column is `'num'`/`'money'` (then `Devlib\Locale` formats them). Use `'raw' => true` only for values you already formatted.
- Names, document numbers and other data placed inside `title` or `message` must be wrapped in `raw()` so they are not translated or added to the dictionary.
- Hex colors, raw `<table>` / `<button>` tags and inline scripts around it are flagged by lint (L003, L001, L002). There is no rule specific to this object.
- The popup does not decide anything: the server must still refuse the blocked action. Hiding or closing it grants nothing.
- Code oddity (not fixed): the REMARK header of `src/Components/IssueModal.php:8` says the slot "footer" adds extra buttons, but the view passes its own default `{{ $slot }}` into the modal footer (`src/Components/IssueModal.php:46`) and never prints a caller-supplied `<x-slot:footer>`. Extra buttons go in the default slot.
- Code oddity (not fixed): the view of `src/Components/IssueModal.php:41-50` never calls `$dl()`, so the rendered root carries `data-dl="x-dl::modal id=..."` (the inner modal's tag) and the developer catalog / Technical Guide link resolves to `x-dl::modal`, not `x-dl::issue-modal`.

## Example

No catalog demo exists for this object in this repository; the example follows the constructor contract and the REMARK (fictional data).

```blade
<x-dl::issue-modal
    id="schedule-issues"
    title="Schedule conflicts"
    message="3 work orders cannot be scheduled. Fix them before releasing the plan."
    :columns="['wo' => 'Work Order', 'process' => 'Process', 'reason' => 'Reason']"
    :rows="[
        ['wo' => ['href' => '/work-orders/WO-0001', 'text' => 'WO-0001'], 'process' => 'Cutting', 'reason' => 'Machine under maintenance'],
        ['wo' => 'WO-0002', 'process' => 'Printing', 'reason' => 'Material not available'],
        ['wo' => 'WO-0003', 'process' => 'Finishing', 'reason' => 'Due date earlier than today'],
    ]">
    <x-dl::button label="Open Scheduling" icon="open" variant="primary" href="/scheduling" />
</x-dl::issue-modal>
```

Closed on load, opened from a button:

```blade
<x-dl::button label="Show problems" icon="warning" data-dl-open="schedule-issues" />
<x-dl::issue-modal id="schedule-issues" title="Schedule conflicts" :open="false" tone="warn" :columns="$columns" :rows="$rows" />
```

## Related

- `x-dl::modal`: the base object (sizes, tones, open/close hooks, JS API `Devlib.open` / `Devlib.close`).
- `x-dl::table`: renders the problems table (column formats `num`, `money`, `raw`, link cells).
- `x-dl::box`: wraps the `message` line.
- `x-dl::button`: Close (`preset="close"`) and the extra buttons you put in the slot.
- `x-dl::alert`, `x-dl::announce`, `Devlib.toast`, `Devlib.confirm`: lighter ways to show a message or ask a question.
