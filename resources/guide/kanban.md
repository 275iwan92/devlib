## Overview

`<x-dl::kanban>` is a card board: columns left to right, cards inside the columns. People move a card between columns by dragging it or with the "Move to" menu (the non-drag fallback is a project rule and always present), open a card with a double-click (a dialog with labels, dates, checklist, members, priority, description, comments and activity), and filter the board with a search box and a priority switch. A screen only declares `columns` and `cards`; the object draws everything else in JavaScript.

Use it for work that moves through ordered stages, one card per work item (production orders through Queue, Print, Finishing, QC, Done; tickets; approvals-in-progress), when:

- each column can carry a WIP limit (`limit`) and the allowed moves can be restricted (`transitions`);
- the server must have the last word on every move (`move-url`).

Pick another object when:

- the work is a table of rows with columns of values, not stages: `x-dl::grid`;
- the work is a schedule against time and resources (bars on a time axis): `x-dl::gantt`;
- the work is a fixed pipeline you only want to explain, not operate: `x-dl::flow`;
- you need one document with Save/Confirm: `x-dl::form`.

The object reads no table or model: data enters only through props. It does not save anything by itself. Without `move-url` a move stays in the browser; creating, editing and deleting cards (and adding or renaming columns) only fire the `dl:kanban` event, so the application must listen and save. The database stays the source of truth for card positions.

Generated markup (the board and the cards are drawn into `[data-k-board]` by the script):

```html
<div class="dl-kanban" data-dl="x-dl::kanban" data-dl-class="Kanban ← DevlibComponent" data-dl-src="kartu kanban"
     data-dl-kanban data-columns='[...]' data-cards='[...]' data-transitions='{...}'
     data-move-url="/work-orders/move" data-add-column="1" data-add-card="1" data-readonly="0"
     data-me="Me" data-members='[...]'>
  <div class="dl-kanban__bar">
    <div class="dl-grid__search"><input type="search" data-k-search ...></div>
    <div class="dl-segment" data-k-prio> All | High | Medium | Low </div>
  </div>
  <div class="dl-kanban__board" data-k-board>
    <section class="dl-kcol dl-kcol--accent [is-over]" data-k-col="print">
      <header class="dl-kcol__head"><span class="dl-kcol__title" contenteditable data-k-rename>Print</span><span class="dl-kcol__count">3 / 2</span></header>
      <div class="dl-kcol__body" data-k-drop>
        <div class="dl-kcard" draggable="true" data-k-card="3" tabindex="0"> tags, title, desc, footer ... </div>
      </div>
      <footer class="dl-kcol__foot"> "Add card" button + composer </footer>
    </section>
    <button class="dl-kanban__addcol" data-k-addcol>Add column</button>
  </div>
</div>
```

Extra HTML attributes (`id`, `class`, `style`, `data-*`) go on the root `div`. `data-move-url` is written only when `move-url` is set. `can="module.action"` is read by the base class: without the ability nothing is rendered at all (use the `readonly` prop, not `can`, for "may look but not touch").

## Props

Constructor of `Devlib\Components\Kanban` (10 props). Blade names are kebab-case; use `:prop="expr"` for arrays and booleans (`:add-column="false"`, `:readonly="true"`).

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `columns` | `array` | `[]` | Columns, left to right. Each item: `key` (string, unique, the value `cards[].col` points to; also used in `transitions` and in the `move-url` body), `label` (title shown in the header; translated through the dictionary, see Behavior), `tone` (color of the top bar: `success`, `warn`, `danger`, `neutral` or `violet`; `accent` is the default when missing, and any other string, `info` included, also gives the accent color because the CSS defines no other `dl-kcol--<tone>`), `limit` (optional integer WIP limit: the counter shows `n / limit`, turns red when `n > limit`), `done` (optional `true`: cards in this column never show as late). `done` is read by the script but is not listed in the PHP header comment. An empty array draws an empty board. |
| `cards` | `array` | `[]` | Cards. Each item: `id` (required, unique; compared as a string, so `1` and `'1'` are the same), `col` (required, a column `key`; a card whose `col` matches no column is never drawn), `title` (string; escaped), `desc` (optional text, clamped to 2 lines on the card), `tags` (optional list of `['label' => ..., 'tone' => ...]`; a plain string is also accepted and gets the `accent` badge on the card; `tone` is a badge tone such as `accent`, `danger`, `violet`, `warn`, `success`, `info`), `priority` (optional, exactly `High`, `Medium` or `Low`; case-sensitive because the filter compares the text), `due` (optional `'YYYY-MM-DD HH:MM'` read as Western Indonesia Time, fixed `+07:00`; a date without a time is not parsed correctly), `assignee` (optional name; shown as initials avatar), `members` (optional list of names; when present it replaces `assignee` on the card), `checklist` (optional list of `['text' => ..., 'done' => bool]`), `checklistTitle` (optional title of the checklist, default `Checklist`), `comments` (optional list of `['by', 'at', 'text']`, `at` is shown as given), `activity` (optional list of `['by', 'at', 'text']`, newest first, usually written by the script), `url` and `urlLabel` (optional: the card shows an "open" icon link to `url`, with `urlLabel` as tooltip, default `Open`; works also in a read-only board). Cards are shown in array order inside their column. Card data is not translated. |
| `transitions` | `array` | `[]` | Allowed moves as `[from-column-key => [to-column-key, ...]]`. A source column that is not listed may move to any column. A listed column with `[]` can never be left (a done column). Staying in the same column (reordering) is always allowed. A refused move shows a danger toast, the board redraws and the card stays. The same rule greys out the targets in the "Move to" menu and in the dialog's Move popover. It is a convenience for the user only: the server must enforce the same rules. An empty array is sent to the script as `{}` (free moves). |
| `members` | `array` | `[]` | Names offered in the Members popover of the card dialog (list of strings). The popover also lists `me` and every name already on any card (`assignee` / `members`). |
| `add-column` | `bool` | `true` | Shows the dashed "Add column" button at the end of the board. A new column gets a generated key (`c` + a number), the label "New column", tone `neutral`, and its title is put into rename mode. The button is not hidden by `readonly`. |
| `add-card` | `bool` | `true` | Shows "Add card" at the foot of every column (a composer with title and optional due date). Not hidden by `readonly`. |
| `me` | `string` | `'Me'` | Name of the current user: author of comments and activity lines, assignee of new cards, first name in the Members popover. The default `Me` is translated through the dictionary (`Saya` in Indonesian); any other value is used as given. |
| `source` | `?string` | `null` | Free text naming where the data comes from, shown by the developer catalog (`data-dl-src`); the default is `kartu kanban`. A subclass can fix it. No runtime effect. |
| `move-url` | `?string` | `null` | Address that receives every move to a different column as a JSON `POST` (contract in "Data and events"). Without it the move stays in the browser. |
| `readonly` | `bool` | `false` | Read-only board: cards are not draggable, no double-click open, no "Move to" menu, column titles are not editable. The search box and the priority switch still work, and cards with `url` keep their link. `add-card` and `add-column` still show unless you turn them off, so a read-only board normally also has `:add-card="false" :add-column="false"`. |

## Behavior

### Toolbar and filter

A search box (matches `title`, `desc` and tag labels, case-insensitive) and a segment switch `All / High / Medium / Low` (matches `priority` exactly). Filtering only hides cards: the column counter and the WIP check still count all cards of the column. `All`, the search placeholder and aria-label are translated; `High`, `Medium` and `Low` are fixed English texts.

### Moving cards

- Drag and drop (HTML5): while dragging, a dashed placeholder shows the drop position (the first card whose middle is below the cursor decides it) and the target column gets `is-target`. Dropping into another column, or between cards of the same column, calls `move`.
- Menu: the `...` button on every card (`data-k-move`) opens a small "Move to" menu with all columns; the current column and columns not allowed by `transitions` are disabled. This is the keyboard/touch fallback.
- Dialog: the Move popover in the card dialog does the same.
- A move into a column that is already at its `limit` is allowed but shows a warning toast ("Column X is over its WIP limit"). The limit is a signal, not a block; the column counter turns red.
- A move to another column adds an activity line ("moved from X to Y") to `card.activity`. A reorder inside the same column adds nothing, fires `dl:kanban` with `from === to` and is not sent to the server.
- With `move-url` the move is posted to the server after the board has already been redrawn (optimistic update). A refusal puts the card back (see Data and events).

### Card dialog (double-click)

Open a card by double-clicking it, or by focusing it (cards have `tabindex="0"`) and pressing Enter. Opening is locked to double-click on purpose (a single click is kept free). The dialog (`dl-mov dl-kmodal`, appended to `<body>`) has:

- an editable title and the current column;
- action buttons with popovers: Labels (search, tick existing labels, or create a new one from the typed text), Dates (due date and time, clear), Checklist (create a titled checklist), Members (tick names; the first ticked name becomes `assignee`), Priority (`High` / `Medium` / `Low` / none), Move (target column);
- a summary of members, labels, due date (red when past) and priority;
- the description (click to edit, Save / Cancel), the checklist with progress bar (tick, delete an item, add item, delete the whole checklist after a confirmation), the comments field (Enter posts a comment, Shift+Enter is a new line) and the list below it: comments first (newest first), then the activity lines (newest first);
- "Delete card" (asks for confirmation) and Close.

Escape closes a popover first, then the dialog. Clicking the dark overlay closes it. Everything the user edits in the dialog changes the in-browser card object immediately; the single event `dl:kanban` with `type: "update"` is fired when the dialog closes (also when nothing changed).

### Cards, columns and WIP

- A card shows tags, title, description, priority, due time (red and bold when past and the column does not have `done: true`), checklist counter `done/total` (green when complete), comment count, avatars (initials of the first two words of each name) and, when set, the open link and the `...` menu.
- A column header shows the title (click to rename; Enter or leaving the field saves the new label), and the counter `n` or `n / limit`.
- Adding a card: "Add card" opens a composer (title, optional due); Enter saves (Shift+Enter is a new line), the composer reopens for fast entry. A new card has id `n<timestamp>`, assignee `me`, empty tags/checklist/comments and one activity line "created this card".
- Adding a column creates a client-side column only; it has no `limit` and is only known to the application through the `column` event.

### Accessibility and keyboard

Cards are focusable (`tabindex="0"`), Enter opens the dialog, the `...` button is a real button with a tooltip. The dialog has `role="dialog"` and `aria-modal="true"` but no focus trap and the board has no keyboard drag: use the Move menu. The search box has an aria-label. Color is never the only signal for the WIP limit (the counter shows `n / limit`), but the priority and late state use color plus text.

### Language and translation

- Interface texts (buttons, popovers, toasts, placeholders) come from the DevLib dictionary keys `k_*` (Indonesian / English) and follow the page language. They are read when the board is drawn, so a language switch without a page reload does not re-translate a board already on screen.
- `columns[].label` is translated through the dictionary (the `columns` prop is a translated list), so write it in English; wrap data values in `raw()`. Card texts, tags, member names, `me` (other than the default) are data and are not translated.
- Fixed English texts in the script (not translated): `Save`, `Cancel`, `Delete`, `Checklist`, `High`, `Medium`, `Low`, the default tooltip `Open` of the open link.
- Dictionary group: `grid` (`Kanban` is listed in `DevlibComponent::GROUP_OF`).

### Access

`can="module.action"` on the tag hides the whole board when the user lacks the ability. For a board the user may see but not change, compute the flag yourself: `:readonly="! \Devlib\Access::can('wo.move')"` and still re-check on the server.

## Data and events

### Data in

Everything is passed as JSON in `data-columns`, `data-cards`, `data-transitions`, `data-members` (escaped with `json_encode`). The script keeps working copies; edits are never written back to the server unless you listen to the event or set `move-url`.

### `move-url` contract

Sent only for a move to a different column (`from !== to`), after the board was redrawn:

```
POST <move-url>
Content-Type: application/json
Accept: application/json
X-CSRF-TOKEN: <window.DL_CSRF or ''>
credentials: same-origin

{"card": <card id as in the data>, "from": "<column key>", "to": "<column key>"}
```

- Any `2xx` answer means accepted; the body is ignored (a redirect that ends in a `200` HTML page would also be taken as accepted, so answer with JSON status codes, never a redirect).
- Any other status means rejected: the script reads `message` from a JSON body (falls back to the HTTP status text), puts the card back in `from` (`card.col = from`), redraws and shows a danger toast with that message. Laravel's `422` / `403` JSON answers fit this directly: `return response()->json(['message' => 'Work order is locked.'], 422);`.
- A network error (fetch throws) rolls back the same way and toasts the error text.
- The rollback restores the column only, not the position inside the column, and removes nothing from `card.activity`. The `move` event has already been fired before the answer arrives, and no event is fired for the rollback.
- Position inside a column is never sent. If the order matters, listen to `dl:kanban` and save it yourself.
- Creating, editing and deleting cards, and column changes, are not sent to `move-url`.

### Event `dl:kanban`

Dispatched on the board root element, bubbles, not cancelable. `detail` is always `{type, card, from, to}` where `card` is the live card object (the same object as in `el.dlKanban.cards`):

| `type` | When | `card` | `from` | `to` |
|---|---|---|---|---|
| `move` | after any successful local move (drag, menu, dialog, API), including a reorder inside one column | moved card | previous column key | new column key |
| `create` | a card was added from the composer | new card (`id` `n<timestamp>`) | `null` | column key |
| `update` | the card dialog was closed | the card | its column key | its column key |
| `delete` | a card was deleted in the dialog | removed card | its column key | `null` |
| `column` | a column was added or renamed | `null` | `null` | column key |

```js
document.querySelector('[data-dl-kanban]').addEventListener('dl:kanban', e => {
  const {type, card, from, to} = e.detail;
  if (type === 'create') fetch('/cards', {method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.DL_CSRF}, body: JSON.stringify(card)});
});
```

### JS API

`el.dlKanban = {cards, cols, render, move(id, to, index)}`: `cards` and `cols` are the live arrays, `render()` redraws, `move(id, to, index)` moves a card by id (checks `transitions`, fires the event, posts to `move-url`; returns `true`/`false`). `el.dlComponent` is the `Kanban` instance (methods such as `allowed`, `cardHtml`, `openCard`, `persist`; DevLib autobinds methods).

### Extending

A project can extend the JS class: `class KanbanProduksi extends Devlib.classes.Kanban { allowed(from, to) {...} cardHtml(card) {...} }` followed by `Devlib.register(KanbanProduksi)`, and a PHP subclass `class KanbanProduksi extends Kanban` whose `jsClass()` returns that name and whose constructor fixes `columns`, `transitions` and so on. See `site/resources/views/demos/oo-override.blade.php` and `site/app/View/Components/Demo/KanbanProduksi.php`. The rules of this extension are in `skills/devlib/object.md`.

## Rules and pitfalls

- The server is the source of truth. Re-validate every move (existing card, allowed transition, permission, WIP policy) and answer with a JSON status code; the screen rules are only a convenience.
- Use `move-url` for the one write the board can do by itself. Everything else (create, update, delete, rename column) needs your own listener on `dl:kanban`.
- Opening a card is a double-click (locked). Do not add a single-click open.
- Keep the "Move to" menu: it is the required non-drag path. Do not hide the `...` button with CSS.
- Column `key` and card `id` must be unique and stable; `cards[].col` must equal a column `key`, otherwise the card is invisible.
- Write `due` as `'YYYY-MM-DD HH:MM'`; it is interpreted as `+07:00` whatever the application time zone is.
- `priority` must be exactly `High`, `Medium` or `Low` for the filter, the badge color and the popover to agree.
- Mark the finished column with `done => true` or finished cards will turn red after their due time.
- `transitions` with a column mapped to `[]` locks it forever for drag, menu and dialog; omit the key if the column should move freely.
- `readonly` does not hide "Add card" / "Add column": switch those props off too.
- Put the `limit` only where the policy is real. Over the limit is a warning toast, not a block; enforce a hard limit on the server through `move-url`.
- Do not use hex colors or `<script>` in the screen around it: lint rules L001 (raw HTML tags), L002 (inline `<script>`) and L003 (hex color) apply to the Blade screen. There is no Kanban-specific lint rule.

### Notes on inconsistencies found while writing this entry (not fixed)

- The header comment of `src/Components/Kanban.php` lists the column shape as `key, label, tone, limit` but the script also reads `done` (`resources/assets/js/components/kanban.js:109`, used by the demos `kanban-production` and by `ObjectNotes`).
- The late-due highlight on the card (`kanban.js:109`) skips columns with `done: true`, but the same highlight inside the card dialog (`kanban.js:216`) only compares with the clock, so a finished card can look late in the dialog and normal on the board.
- Drag reorder index with an active filter (`kanban.js:38` and `:148`): the drop index is counted among the visible (filtered) cards, but `move()` applies it to all cards of the column, so the card can land at a different position than the placeholder showed.
- Server refusal (`persist`, `kanban.js:158`) restores `card.col` only: the activity line "moved from..." stays in `card.activity` and the position inside the column is not restored; `dl:kanban` already announced `move` and announces nothing for the rollback.
- `readonly` leaves "Add card" and "Add column" active (`kanban.js:133` and `:137` test only `addCard` / `addColumn`), although the header comment describes a read-only board without any change. A read-only board must also set `:add-card="false" :add-column="false"`.
- Several texts in the script are English constants outside the dictionary (`Save`, `Cancel`, `Delete`, `Checklist`, `High`, `Medium`, `Low`, `Open`), so they stay English in the Indonesian interface.
- A redirect answer to `move-url` (for example to a login page) ends as `200` HTML and `r.ok` is true, so the move is taken as accepted.

## Example

Adapted from the catalog demo `kanban-production` (fictional data):

```blade
<x-dl::kanban me="Demo Admin" source="production.work_orders"
    :members="['Budi Santoso', 'Rina Wulandari', 'Andi Pratama']"
    move-url="/work-orders/move"
    :columns="[
        ['key' => 'queue', 'label' => 'Queue', 'tone' => 'neutral'],
        ['key' => 'print', 'label' => 'Print', 'tone' => 'accent', 'limit' => 2],
        ['key' => 'finish', 'label' => 'Finishing', 'tone' => 'warn', 'limit' => 3],
        ['key' => 'qc', 'label' => 'QC', 'tone' => 'violet'],
        ['key' => 'done', 'label' => 'Done', 'tone' => 'success', 'done' => true],
    ]"
    :transitions="['queue' => ['print'], 'print' => ['finish', 'queue'], 'finish' => ['qc', 'print'], 'qc' => ['done', 'finish'], 'done' => []]"
    :cards="[
        ['id' => 1, 'col' => 'queue', 'title' => 'CO-2609-0031 · Snack box 50,000 pcs', 'desc' => 'Waiting for plates and paper.',
         'tags' => [['label' => 'Offset', 'tone' => 'accent'], ['label' => 'Urgent', 'tone' => 'danger']],
         'priority' => 'High', 'due' => '2026-09-26 16:00', 'assignee' => 'Budi Santoso',
         'checklist' => [['text' => 'Plate ready', 'done' => true], ['text' => 'Paper issued', 'done' => false]]],
        ['id' => 3, 'col' => 'print', 'title' => 'CO-2609-0027 · Pharma carton', 'priority' => 'Medium', 'due' => '2026-09-22 10:00',
         'assignee' => 'Rina Wulandari',
         'comments' => [['by' => 'QC', 'at' => '23 Sep 2026 08:10', 'text' => 'Cyan slightly dark, corrected.']]],
    ]" />
```

Server side for `move-url` (Laravel route, JSON in and out):

```php
Route::post('/work-orders/move', function (Request $r) {
    $data = $r->validate(['card' => 'required', 'from' => 'required|string', 'to' => 'required|string']);
    $wo = WorkOrder::findOrFail($data['card']);
    abort_if($wo->stage !== $data['from'], 422, 'The card was moved by someone else. Reload the page.');
    abort_unless(StageRules::allows($data['from'], $data['to']), 422, 'This move is not allowed.');
    $wo->update(['stage' => $data['to']]);

    return response()->json(['ok' => true]);
});
```

(`abort(422, ...)` on a JSON request returns `{"message": "..."}`, which is what the board shows in its toast.)

Read-only variant (cards link back to the source screen):

```blade
<x-dl::kanban :columns="$columns" :cards="$cards" readonly :add-card="false" :add-column="false" />
```

## Related

- `x-dl::grid`: row-based list of the same work items, with filters and export; use it when stages are just a status column.
- `x-dl::gantt`: schedule of tasks over time and resources, drag to reschedule.
- `x-dl::flow`: static pipeline diagram that explains a process; the Kanban operates one.
- `x-dl::badge`: the badge style used for card tags.
- `x-dl::modal` and `x-dl::form`: the other dialog and document shells; the card dialog is internal to the Kanban.
- `skills/devlib/object.md`: how to extend an object (PHP subclass plus JS subclass), with the Kanban as the worked example.
- `skills/devlib/components.md`: the generated prop list.
