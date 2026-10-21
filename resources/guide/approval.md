## Overview

`<x-dl::approval>` is the standard **Approval form** of DevLib: a read-only screen where a checker reads a document (summary, lines, multi-level approval flow, history) and takes one decision: **Reject**, **Hold** or **Approve**. It has no input fields. It comes in two shapes of the same object:

| Shape | How | Used for |
|---|---|---|
| Approval Center (default) | list of waiting documents on the left, detail of the selected one on the right, Decision Log under the list | the locked standard page (`p5-form-approval`, demo `approval-po`) |
| Single (`single`) | one document, no list, no heading | the form of the Approval flow in Grid and Form: the grid is the list, a double click opens this form, `x-dl::back-button` returns to the grid |

Approval decisions are NOT taken in `x-dl::form`: the document form (Single Form, Master-Detail) has no Approve / Hold / Reject buttons and no `approval` / `otp-url` props. Decisions belong only here (see `resources/guide/form.md` and the test `site/tests/Feature/GridFormFlowTest.php`, method `test_approval_flow_opens_the_standard_read_only_approval_form_and_back_returns_to_the_grid`).

Pick another object when:

- the user must type or edit data of the document: `x-dl::form` (a document is locked once Confirmed);
- the screen is a list of many documents with columns and filters: `x-dl::grid` (the Approval flow grid has the columns **Status** and **Approval** as two badges, tabs All / Awaiting / Hold / Approved / Rejected);
- only a printout is needed: `x-dl::print-doc`.

### Document state and who decides

The document status (Draft / Confirmed / Cancelled) and the approval status are **two separate fields**; approval exists only on a Confirmed document (`skills/devlib/transaction-lifecycle.md`). In the data given to this object the approval state is expressed like this:

| State | How it appears in a `docs` entry | What the user sees |
|---|---|---|
| Awaiting | a step with `status` `current`, no `held`, no `readonly` | Reject, Hold, Approve buttons |
| Hold | `held` => `['by', 'at', 'note']` (and a `HOLD` history row) | grey "On hold for now" note, only Reject and Approve buttons (Hold is hidden) |
| Approved / Rejected | `'readonly' => true` (steps and history show the result) | the detail without any button |

The component has **no notion of roles or rights**: it never decides who may approve. `me` is only the name written into the decision log, and the buttons show for anyone who can open the page. The server must give each user only the documents of which that user is the checker of the pending level, and must check the same again on every POST. Hiding the whole screen from users without the right is done with the `can="module.action"` attribute (no ability = not rendered).

### Decision rules (built into the script)

- **Approve** and **Reject** are final: the document leaves the list and there is no way back.
- **Hold** is temporary: the document stays, the Hold button disappears, and later only Approve or Reject is possible.
- **Reject** and **Hold** need a reason (mandatory textarea in the confirm dialog, the dialog does not close while it is empty). **Approve** has no reason.
- Every decision is a **log**: who, when, action, level, reason. The log is shown in the document history and the Decision Log panel, and is emitted as the event `dl:approval`. The application MUST store it.

## Props

All constructor props of `Devlib\Components\Approval` (12). Blade names are kebab-case; use `:prop="expr"` for anything that is not a plain string (`:docs`, `:line-columns`, `:log`); the boolean `single` can be a bare attribute.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `docs` | `array`, `[]` | The documents. Written as JSON into `data-docs`, so everything in it reaches the browser: give only what this user may see. Shape of one entry below. An empty array renders an empty list ("Everything is decided") in the default shape, and an empty detail in `single`. The first document is selected at start. |
| `line-columns` | `array`, `[]` | The lines table of the detail: `[field => 'Column title']`, in this order. A key ending in `:money` (for example `'price:money'`) is right-aligned and shown as a number with thousand separators of the page language, read from the line field without the `:money` suffix. No `line-columns` or no `lines` in the document = no table. The titles are written as given (data, not translated). |
| `title` | `string`, `'Waiting for My Approval'` | Heading of the Center shape. Translated through the dictionary (standard text prop). Not shown with `single`. |
| `approve-label` | `string`, `'Approve'` | Label of the green Approve button (an `x-dl::button preset="approve"`). Only the button changes: the confirm dialog title and the toast still use the dictionary words ("Approve" / "Setujui"), not this label. |
| `hold-label` | `?string`, `'Hold'` | Label of the Hold button. `null` removes the Hold button completely (a screen without temporary hold). |
| `reject-label` | `string`, `'Reject'` | Label of the Reject button. Same remark as `approve-label`. |
| `me` | `string`, `'Me'` | Name of the current checker, written as `by` into the decision log and the history in the browser simulation. The default `'Me'` is translated; any other value is used as given. It is a label only, never an identity check. |
| `source` | `?string`, `null` | Free text naming where the data comes from, shown by the developer catalog (`source()`); default `dokumen menunggu approval`. No visual or runtime effect. |
| `action` | `?string`, `null` | URL for real decisions. Set = the component renders a hidden POST form with `@csrf` and the fields `id`, `action`, `_reason`; a decision fills it and submits it, and the page is loaded again from the server. Not set = **simulation** in the browser (demo, no storage, see Behavior). |
| `log` | `array`, `[]` | Initial Decision Log (from the database), newest first: rows `['no', 'status' => APPROVED\|REJECTED\|HOLD, 'level', 'levels', 'by', 'at', 'note']`. The panel is only shown when it has rows, and only in the default shape (`single` hides the side column). |
| `single` | `bool`, `false` | One document opened from a grid: adds `dl-appr--single`, hides the heading, the count badge, the list and the Decision Log. The history of the document is still in the detail. |
| `back` | `?string`, `null` | Address of the grid. Used only in the **simulation** (no `action`) and only after a final decision (Approve or Reject): the page goes there after 1.5 seconds; Hold stays. With `action` it is ignored (the server redirects). The Back button itself is `x-dl::back-button`, placed above the component by the screen. |

### Shape of one `docs` entry

```php
[
  'id' => 1, 'no' => 'PO-2610-0001', 'title' => 'Purchase Order', 'amount' => 12500000,
  'party' => 'PT Contoh Kertas', 'date' => '2026-10-02', 'submitted_by' => 'Rina Wijaya (Purchasing)',
  'url' => '/po/1',                                   // optional: icon link "Open document" next to the title
  'summary' => ['Value' => 'IDR 12.500.000', 'Term' => 'Net 30'],   // label => value cards
  'lines' => [['item_label' => 'Kraft roll', 'qty' => 10, 'uom' => 'roll', 'price' => 1250000]],
  'steps' => [
      ['role' => 'Creator', 'by' => 'Rina Wijaya', 'at' => '02 Oct 2026', 'status' => 'done', 'submitter' => true],
      ['role' => 'Head of Purchasing', 'by' => 'Dewi Kusuma', 'at' => '03 Oct 2026', 'status' => 'done', 'plt' => false],
      ['role' => 'Operational Manager', 'status' => 'current'],
      ['role' => 'Director (> IDR 50 mio)', 'status' => 'pending'],
  ],
  'history' => [['action' => 'SUBMITTED', 'by' => 'Rina Wijaya', 'at' => '02 Oct 2026 10:05', 'note' => null]],
  'held' => ['by' => '...', 'at' => '...', 'note' => '...'],   // optional: document is on hold
  'readonly' => true,                                          // optional: already decided, no buttons
]
```

- `id` is the key sent back to the server. `no` is the document number shown everywhere. `amount` and `date` are used in the list item only (`amount` as `IDR <number>`, `date` as a local date).
- `steps`: the step with `submitter` => true is the creator and is **not** an approval level. The other steps are Level 1..n in order; the one with `status` `current` is the running level ("Approval Level 2 of 3 - role"). Step `status` values: `done`, `current`, `pending`, `rejected` (these become the CSS state `is-<status>` of the step dot and the level meter). `plt` => true adds a violet **PLT** badge (the checker acted as a stand-in) on the step and on a history row.
- `history[].action`: `SUBMITTED`, `APPROVED`, `REJECTED`, `HOLD` get their own icon, anything else a plain circle. `note` is shown in italics when not empty.
- `summary` labels and values, `party`, `title`, `submitted_by`, `role`, `by`, `at` and notes are data: written as given, never translated.

### Attributes accepted on every DevLib object

`can="module.action"` (no access = not rendered) and any HTML attribute (`id`, `class`, `style`, `data-*`), merged into the root `div`.

## Behavior

### Generated markup

```html
<div class="dl-appr [dl-appr--single]" data-dl-approval data-docs='[...]' data-cols='{...}' data-me="..." data-log='[...]' [data-back="..."]>
  <form method="post" action="..." data-a-form hidden>  <!-- only with action -->
    @csrf <input name="id"> <input name="action"> <input name="_reason">
  </form>
  <div class="dl-appr__head"><h2>title</h2><span class="dl-badge dl-badge--pill dl-badge--warn" data-a-count>3 waiting</span></div>
  <div class="dl-appr__grid">
    <div class="dl-appr__side">
      <div class="dl-appr__list" data-a-list>  <!-- buttons .dl-appr__item[data-a-id] -->
      <div class="dl-appr__log" data-a-log hidden>  <!-- Decision Log, ul[data-a-logrows] -->
    </div>
    <div class="dl-appr__detail" data-a-detail>
      <div data-a-body></div>   <!-- built by script: title, level meter, held note, summary, lines table, flow, history -->
      <div class="dl-appr__action" data-a-action><div class="dl-appr__buttons"> Reject, Hold, Approve (data-a-do="reject|hold|approve") </div></div>
    </div>
  </div>
</div>
```

The default slot is rendered inside the detail, after the body and before the buttons. Everything in `data-a-list` and `data-a-body` is made by the script from `docs`; all data values are HTML-escaped.

### List and detail

- Default shape: the left list shows documents that are not decided (`decided` is only set by the browser simulation), each item with number, amount (`IDR n`), party, date, a `Level n / N` tag and an "On hold" badge. The count badge says `N waiting`. Click an item to show its detail. With no waiting document the list shows "Everything is decided" and the detail is hidden (`is-empty`).
- The detail shows: title line (number - title, optional open-document icon from `url`, "Submitted by ..."), the **level badge and meter** (one bar per level: done green, current amber, rejected red), the held note, the summary cards, the lines table, **Approval Flow** (vertical steps; a step without `by` says "Waiting for you" when current, "Waiting" otherwise) and **Document History**.
- `readonly` hides the whole button area; `held` hides the Hold button.

### Taking a decision

Clicking a button opens the standard confirm dialog (`Devlib.confirm`): title `<Word> <number>?`, the tone danger for Reject, warn for Hold, success for Approve, an explanation text, and for Reject and Hold the mandatory reason textarea. Cancel, Esc or a click outside does nothing. After Yes:

**With `action` (real):** the hidden form is filled (`id` = document id, `action` = `approve` | `reject` | `hold`, `_reason` = the reason, empty for approve), the event `dl:approval` is dispatched, and the form is submitted with `form.submit()`. The server decides, stores, and redirects (PRG). Nothing is changed in the browser first.

**Without `action` (simulation, demo only):** the script itself adds a history row and a log row (`by` = `me`, `at` = now), sets the step to `done` / `rejected` for Approve / Reject (the document is marked `decided` and leaves the list; the next waiting document is selected) or stores `held` for Hold, shows a toast, and dispatches `dl:approval`. In `single`, after Approve or Reject the page goes to `back` after 1.5 seconds when `back` is set. Nothing is stored and a reload brings the documents back.

### Language

Fixed texts (waiting count, flow, history, dialog messages, toasts, empty state, Decision Log) come from the DevLib dictionary and follow the page language. The words `Level`, `Approval Level n of N`, `PLT` and the statuses in the Decision Log badges are written in English in the script.

### Accessibility as implemented

The list items and decision buttons are native `<button>` elements (keyboard operable, text labels). The dialog is the standard DevLib confirm dialog (`role="alertdialog"`, Enter confirms, Esc cancels, focus goes to the reason box or to the Yes button). The level meter bars carry a `title` only; the flow and history lists are plain `ol` / `ul`. There are no `aria-live` regions: a decision is announced only by the toast.

## Data and events

### What the server receives (with `action`)

A POST of the hidden form:

| Field | Value |
|---|---|
| `_token` | CSRF token (`@csrf`). |
| `id` | `id` of the document. |
| `action` | `approve`, `reject` or `hold`. The field is called `action`, not `_action`. |
| `_reason` | The reason typed in the dialog; `''` for `approve`. |

What the server must do (rules in `skills/devlib/grid-form-flow.md` section 5):

- Load the stored document by `id`; never trust anything else from the browser. Check that the user is the checker of the current level and that the document is Confirmed and still awaiting (or on hold) before accepting the action. Refuse Hold on a document already on hold, and refuse any action on an approved / rejected one.
- Require a non-empty `_reason` for `reject` and `hold` (the client check is only a convenience).
- In one database transaction: change the approval state and level, and write the decision log row (who, when, action, level, reason). Approve moves to the next level or, on the last level, the document is Approved; Reject sends the document back to its creator.
- Redirect back to the Approval form of the same document (or to the grid) with a flash message (`<div hidden data-dl-flash="success">text</div>` in the layout). Errors: redirect with an error flash, state unchanged.
- The next time the page is rendered, give `held` / `readonly` according to the stored state, and fill `log` from the stored decision log.

The standard flow form of the catalog (`/lib/p5-flow-approval/form/{id}`) is stateless: a POST of `approve`, `hold` or `reject` to it is refused with a warning toast and no change (the test checks this), because the demo Approval form works in simulation without `action`.

### Events

| Event | `detail` | When |
|---|---|---|
| `dl:approval` (bubbles from the root) | With `action`: `{id, no, action, note}` (`action` is `approve` / `reject` / `hold`, `note` the reason). Simulation: the whole log row `{id, no, action, status, by, at, level, levels, role, note}` (`status` is `APPROVED` / `REJECTED` / `HOLD`). | After the dialog is confirmed. With `action` it is fired just before the page submits (do not rely on asynchronous work in a listener). |

### Markup hooks and JavaScript

| Hook | Where |
|---|---|
| `data-dl-approval` | root; JS class `Approval` |
| `data-docs`, `data-cols`, `data-me`, `data-log`, `data-back` | the data of the props |
| `data-a-form`, `data-a-list`, `data-a-log`, `data-a-logrows`, `data-a-count`, `data-a-detail`, `data-a-body`, `data-a-action` | parts the script fills |
| `data-a-id` | each list item (the document id) |
| `data-a-do="reject\|hold\|approve"` | the decision buttons |
| classes | `dl-appr`, `dl-appr--single`, `dl-appr__item` (+ `is-active`, `is-held`), `is-empty` on the detail |

`el.dlComponent` on the root is the `Approval` instance: `docs`, `cur` (selected id), `log`, `pending()`, `level(doc)`, `list()`, `detail()`, `logs()`, `decide(action)`. A project may override it with `data-dl-js="MyApproval"` + `Devlib.register(class MyApproval extends Devlib.classes.Approval {...})`.

## Rules and pitfalls

- **The Approval form is locked as the standard** (owner decision 2026-10-21, `skills/devlib/form.md`): the read-only form with Level n of N, history and Reject / Hold / Approve buttons is not changed without an order from the owner. In Grid and Form, Approval has its own grid and its own form (this object with `single`), separate from the document form.
- Approve and Reject are final, Hold is temporary. Do not offer a way back from Approved or Rejected in the screen; if the business needs a correction, it is a new process on the server.
- Always set `:action` in a real project. Without it everything is a browser simulation that stores nothing.
- Never build the approval buttons, an OTP step or a simulated login into `x-dl::form`: that form does not carry decisions.
- Every decision must be stored as a log row by the application (`dl:approval` is only a notice). Show it again through `log`.
- The server owns the rights. The component shows the buttons to whoever sees the page. Check the checker of the current level, the status, and the reason on every POST.
- Put only the documents of the current user into `docs`, and nothing secret: the whole array is in the page source (`data-docs`).
- `url` of a document is written into a link `href` after HTML escaping only; give a local address that you built, never a value typed by a user.
- `docs[].id` should be unique, and either a number or a non-numeric string (see the oddity below).
- `back` works only without `action`. With `action` the redirect after the POST decides where the user lands.
- With `single` and the simulation, once the only document is decided and `back` is not set, the detail disappears and the screen stays empty. Set `back`.
- `log` has no visible effect with `single` (the side column is hidden); the document history in the detail is the record there.
- A button label such as `approve-label="Approve & Send to Vendor"` changes only the button. The dialog and the toast still say Approve.
- `hold-label` null hides the Hold button; the server must then also refuse `hold`.
- No lint rule (`devlib:lint`) is specific to this object.
- Code oddity (not fixed): `resources/assets/js/components/approval.js:17` converts a clicked item id with `isNaN(+id) ? id : +id`, so a string id that looks numeric (`'007'`, or `'5'` when the document has `'id' => '5'`) becomes a number and the strict `d.id === this.cur` comparison in `list()` / `detail()` no longer finds the document: the item is not highlighted and the detail turns empty. Use integer ids or ids with letters.
- Code oddity (not fixed): the comment in `src/Components/Approval.php:27-29` says `dl:approval` always carries `{id, no, action, status, by, at, level, role, note}`; with `action` set, `resources/assets/js/components/approval.js:85` dispatches only `{id, no, action, note}`. The full row exists only in the simulation (line 101).
- Code oddity (not fixed): the comment `src/Components/Approval.php:22` says columns ending in `:money` are formatted as Rupiah; `resources/assets/js/components/approval.js:44` formats them as a plain number without `IDR` (only the list amount at line 39 and 50 uses `fmt.money`, which always prefixes `IDR`).
- Code oddity (not fixed): `resources/assets/js/components/approval.js:36`, `50`, `63`, `68` write the words `Level` and `Approval Level n of N` as fixed English text instead of dictionary keys, so they do not follow the page language.
- Code oddity (not fixed): the rule files disagree about correcting an Approved document: `skills/devlib/transaction-lifecycle.md:28` says an Approved document can still be changed to Rejected with a reason, while `skills/devlib/grid-form-flow.md:95` and this component make Approve and Reject final (a `readonly` document has no buttons).

## Example

Standard page, Approval Center (demo `approval-po`; the documents come from the demo data class, see the shape above):

```blade
<x-dl::approval title="PO Menunggu Approval Saya" me="Operational Manager" approve-label="Setujui & Kirim ke Vendor" :docs="\App\Support\DemoData::approvals()"
    :line-columns="['item_label' => 'Item', 'qty' => 'Qty', 'uom' => 'Satuan', 'price:money' => 'Harga/Unit', 'subtotal:money' => 'Subtotal']" />
```

Form of the Approval flow, one document opened from the grid (demo `form-approval`; Back returns to the grid the user came from):

```blade
<div class="dl-row" style="margin-bottom:8px"><x-dl::back-button :fallback="$back" /></div>
<x-dl::approval single :back="$back" me="Operational Manager" approve-label="Setujui" :docs="$docs"
    :line-columns="['product_label' => 'Item', 'qty' => 'Qty', 'uom' => 'Satuan', 'price:money' => 'Harga/Unit', 'subtotal:money' => 'Subtotal']" />
```

Real project: decisions posted to the server, with the stored decision log:

```blade
<x-dl::back-button :fallback="$from" />
<x-dl::approval single :docs="[$doc]" :action="route('po.decide')" :line-columns="$cols" :log="$decisionLog" />
```

Controller sketch (fictional route): `Route::post('po/decide', ...)` reads `id`, `action` (`approve|reject|hold`) and `_reason`, loads the stored document, checks the user and the state, writes the log in a transaction, and redirects with a flash.

## Related

- `x-dl::form`: the document form; shows the status, has no approval decision. Its `Confirm` makes the document available for approval. See `resources/guide/form.md`.
- `x-dl::grid`: the list that opens this form (Approval flow grid with the badges Status and Approval and the tabs All / Awaiting / Hold / Approved / Rejected).
- `x-dl::back-button`: the Back link above the form (`:fallback` = the grid address).
- `x-dl::button`: the three decision buttons are `preset="reject"`, `preset="hold"` and `preset="approve"`.
- `x-dl::print-doc`: the printout; its status line carries the document and the approval status (for example `CONFIRMED · APPROVED`).
- `skills/devlib/grid-form-flow.md` (section 5), `skills/devlib/form.md`, `skills/devlib/transaction-lifecycle.md`: the project rules behind this object.
