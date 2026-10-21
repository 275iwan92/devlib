## Overview

`<x-dl::form>` is the standard form shell of DevLib. It renders one HTML `<form>` (with CSRF), a title, the fields you put inside it, and the action buttons. Its main job is to make every data screen behave the same way: which buttons exist is decided by `kind` + `status` (+ `approval`), not by the screen.

### Kinds

| `kind` | Meaning | Statuses |
|---|---|---|
| `master` (default) | Master data (employee, customer, item). No document number, no Confirm/Cancel/Print. | `new`, `active`, `inactive` |
| `document` | Transactional record (order, voucher, PO), with or without detail lines. Has a document number, a lifecycle and a print. | `new`, `draft`, `confirmed`, `working`, `approval`, `hold`, `approved`, `rejected`, `cancelled`, `closed` |

`new` means "not saved yet" (same form, empty or filled with defaults). A Single Form is a `document` with no lines; a Master-Detail form is a `document` with an `x-dl::lines` object in the `detail` slot.

### Document lifecycle

A new record is saved as **Draft**. Draft can be edited and deleted (Delete removes the record completely). **Confirm** moves it to **Confirmed**; from then on the content is read-only. **Cancel** (a reason is required, it is written to History) moves Confirmed to **Cancelled**; a cancelled record stays stored. **Closed** is set by a later process (for example a PR turned into a PO), never by a form button.

Approval is separate from the document status. In the strict cycle (`strict`) the document status stays Draft / Confirmed / Cancelled and `approval` (`waiting`, `approved`, `hold`, `rejected`) is a second field that only has meaning while the document is Confirmed. In the older (non-strict) cycle approval states are modelled as document statuses (`approval`, `hold`, `approved`, `rejected`) together with `approval-levels` / `approval-at` / `can-approve`.

Status and button words are always English (New, Save, Undo, Delete, Confirm, Cancel, Duplicate, Back to Draft, Back; Draft, Confirmed, Cancelled, Closed), so users and developers use the same words.

### Where it sits in the flow

`x-dl::grid` (list) -> `x-dl::form` (one record, its own page and URL) -> `x-dl::print-doc` (the printout opened by the Print button). The grid opens the form with `open-url` / `new-url`; the form returns with `back`. Only Back and Delete leave the form; Save and Confirm stay on it. The full screen order is described in `skills/devlib/grid-form-flow.md`.

### What it is not

- Not a data layer: it does not validate on the server, store, number or authorize anything. The server decides every action again.
- Not a small action wrapper: for a Post / Create button, a dialog body or a GET filter use `x-dl::action-form`.
- Not a wizard: step-by-step input is `x-dl::wizard`.
- Not the printout: the Print button only links to the page you give in `print-url`.

## Props

All constructor props of `Devlib\Components\Form` (36). Blade names are kebab-case; use `:prop="expr"` for anything that is not a plain string.

### Basics

| Prop | Type, default | Meaning |
|---|---|---|
| `title` | `string`, `''` | Form title. Translated through the dictionary (`title` is a standard text prop). Wrap data values (document number, customer name) in `raw()` / `Lang::raw()` so they are not translated or logged as dictionary entries. In a non-panel form an empty title renders no header (and no Edit button in `mode="view"`). |
| `subtitle` | `?string`, `null` | Small line under the title in the panel header (for example the supplier). Only rendered when `panel` is set. Translated; use `raw()` for data. |
| `action` | `?string`, `null` | Form action URL. With `action` the form posts normally. Without it the form is a client-side preview: Save shows a toast, fires `dl:form-submit` and closes the enclosing modal/drawer. Lint F103 and the Print/Email rule depend on it (see `print-url`). |
| `method` | `string`, `'POST'` | Only the exact upper-case `'GET'` renders a GET form (no CSRF token). Any other value, including lower-case `'get'`, `PUT` or `DELETE`, renders `method="POST"` with `@csrf`; there is no `_method` spoofing. |
| `action-field` | `string`, `'_action'` | Name of the request field that carries the clicked action (`save`, `confirm`, ...). Written to `data-action-field` and used for the Save button and for the hidden input the panel buttons create. Old screens that already read `action` set it to `'action'`. |
| `hidden` | `array`, `[]` | `name => value` pairs rendered as `<input type="hidden">` outside the fieldsets, so they are always posted, also when the form is locked. Values must be scalars. This is a prop, not the HTML `hidden` attribute. Typical content: `from`, `status`, `as`. |
| `guard` | `bool`, `true` | Adds `data-dl-guard`: the dirty guard (see Behavior). `:guard="false"` switches it off; the Undo button then is not disabled while clean either. |
| `mode` | `string`, `'edit'` | `'view'` renders the fields disabled (read as text). Non-panel: an Edit button appears in the header (needs a non-empty `title`) and a Cancel button in the footer restores the values from before Edit. Panel: the fields are disabled and there is no Edit button (use `strict` + `status` for an editable locked form). Any other value behaves as `edit`. |

### Layout

| Prop | Type, default | Meaning |
|---|---|---|
| `panel` | `?string`, `null` | Any non-empty value enables the panel layout (`panel="top"` is the standard): the action panel sits on top (title, status, buttons, Back at the right end), the content is split into tabs, and the buttons come from `kind` + `status`. Without `panel` the form is a plain body + footer. `panel` is required for `kind="document"` (lint F109) and for `back` to be checked (F101/F102). |
| `tabs` | `array`, `[]` | `['key' => 'Title']` (or `['key' => ['Title', 'fa-icon']]`). One named slot per key (`<x-slot:key>`). Without `tabs` the default slot is shown in a tab called Data. Titles are translated. Tab keys are read as view variables (`${$key}`), so do not use keys that collide with the form's own slots/props (`strip`, `detail`, `special`, `title`, `action`, ...) and avoid `audit` (reserved for History). Panel only. |
| `footer` | `bool`, `true` | Shows the footer (extra button, Cancel in view mode, split action button). Non-panel only: a panel form never renders the footer. |
| `actions` | `array`, `['Save' => 'fa-floppy-disk', 'Download' => 'fa-download', 'Export' => 'fa-file-export']` | Items of the split action button in the footer: `label => icon`. The first entry is the main button; the others are in the menu. Non-panel only. Labels are shown as given. |
| `extra-action` | `?string`, `null` | Label of an extra full-width success button above the footer buttons; clicking it shows a QR code containing all field values. Non-panel footer only. |
| `extra-icon` | `string`, `'📱'` | Icon of the extra button (Font Awesome `fa-*`, a DevLib icon name, or an emoji). Only used with `extra-action`. |
| `pipeline` | `?bool`, `null` | Stage bar in the panel header (Draft > Confirmed > Closed, or with approval levels). `null` = automatic: on for `kind="document"` when not `strict`, off for `master`. `true` forces it on a master; `false` removes it for a document (lint F106, info). Ignored when `strict` is on: the strict form never shows the bar, it shows stamps. |
| `stage` | `?string`, `null` | Second badge next to the status: the work stage inside a status (for example Confirmed + "In Progress"). Not translated by the form. Panel only. |

### Navigation and links

| Prop | Type, default | Meaning |
|---|---|---|
| `back` | `?string`, `null` | URL of the Back button: the grid of the module, including its query string. Required on panel forms (F101: error for documents, warning for masters). Without it Back is a `<button>` that calls `history.back()`. |
| `new-url` | `?string`, `null` | Target of the New button (non-strict cycle). Without it New is a button that fires `dl:form-action` and, if `action` is set, posts `_action=new` (lint F104). Not used by the strict cycle (no New). |
| `print-url` | `?string`, `null` | URL of the printout (`x-dl::print-doc`). Print opens it in a new tab; Email opens the same URL with `ds_do=email` (appended with `?` or `&`) so the e-mail dialog opens. A form that has `action` but no `print-url` shows neither Print nor Email (lint F103 asks for it). |
| `otp-url` | `?string`, `null` | Strict cycle only. In combination with `approval="rejected"` and `can-approve` it switches the OTP dialog on for Approve (`data-f-otp`). The value itself is written to `data-f-otp-url` but no DevLib script reads it: the OTP is posted as `_otp` to the form `action`. |

### Status, lifecycle and approval

| Prop | Type, default | Meaning |
|---|---|---|
| `kind` | `string`, `'master'` | `master` or `document`; anything else is lint F107 (error). |
| `status` | `string`, `'active'` | Current status, see the table in Overview. Always pass it for a document: the default `active` is not a document status (F108) and gives the fallback buttons New + Print. Invalid for the kind = F108 (error). |
| `strict` | `bool`, `false` | Opt-in strict cycle (owner rule 2026-10-21), see Behavior. Off keeps the legacy cycle (New, Email, Back to Draft, pipeline bar). |
| `approval` | `?string`, `null` | Strict cycle only: `waiting`, `approved`, `hold`, `rejected`, or `null` for no approval. Only valid with `strict`, `kind="document"`, `status="confirmed"` (F110, error). Adds the second stamp. |
| `can-approve` | `bool`, `false` | The current user is the checker of the pending level. Strict: switches to the checker buttons while `approval` is set. Legacy: used by statuses `approval` and `hold`. Never a security check; the server must verify. |
| `approval-levels` | `int`, `0` | Legacy cycle: number of approval levels (documented range 1-3, not enforced). `> 0` replaces the stage bar by `Draft > Confirmed > Approval 1..n > Approved > Closed`. |
| `approval-at` | `int`, `0` | Legacy cycle: the level that is waiting. Clamped to `1..approval-levels` for the stage bar; used in the badge text (`Waiting Approval 2`, `Hold L2`). |
| `can-reopen` | `bool`, `false` | Legacy cycle: shows Back to Draft next to Cancel on a Confirmed document. Set it only for users allowed to reopen; the server must check the same right. Ignored by the strict cycle. |
| `can-input` | `bool`, `true` | `false` = the user is a checker who may not input: the content is locked in every status and the buttons Save, Undo, Delete, Confirm, New, Duplicate, Cancel and Edit are removed. |
| `deletable` | `bool`, `false` | Strict master only: shows Delete on an Active master. Pass `true` only for an administrator and only when the data is not used by any transaction; the server checks again. |
| `hide` | `array`, `[]` | Action keys removed from the panel: `save`, `undo`, `delete`, `new`, `print`, `confirm`, `cancel`, `draft` (Back to Draft), `email`, `duplicate`, `edit`, `deactivate`, `activate`, `approve`, `reject`, `hold`. Back cannot be hidden. Use it only for actions a module truly does not support; hiding a mandatory action is lint F105 (error). |

### History

| Prop | Type, default | Meaning |
|---|---|---|
| `created-by` | `?string`, `null` | Shown on the History tab ("Created by"). |
| `created-at` | `?string`, `null` | Timestamp string (`Y-m-d H:i:s`), formatted by `Locale::stamp` as `dd-Mmm-yyyy HH:mm`. |
| `updated-by` | `?string`, `null` | "Last updated by". |
| `updated-at` | `?string`, `null` | "Last updated at", formatted like `created-at`. |
| `log` | `array`, `[]` | Rows `['at' => ..., 'by' => ..., 'action' => ..., 'note' => ...]`, newest first (Created, Saved, Confirmed, Back to Draft, Cancelled, Closed, ...). The table is only rendered when `log` is not empty. |

### Attributes accepted on every DevLib object

`can="module.action"` (no access = the whole form is not rendered) and any HTML attribute (`id`, `class`, `style`, `enctype`, `data-*`), which are merged into the `<form>` element. Slots are described in Behavior.

### Lint rules (F1xx)

`Devlib\Lint\FormRules`, run by `php artisan devlib:lint`; nothing is shown on screen.

| Code | Level | Rule |
|---|---|---|
| F101 | error for document, warning for master | `panel` form without `back`. |
| F102 | warning | `panel` form while `config('devlib.back_history')` is not `false`. |
| F103 | warning | Document with `action` and no `print-url`, in any status except `new`. |
| F104 | warning | Non-strict document in `draft`, `confirmed` or `cancelled` without `new-url` (and `new` not hidden). |
| F105 | error | Non-strict document with a mandatory action in `hide`: Draft needs save, delete, new, print; Confirmed needs cancel, print; Cancelled needs print. |
| F106 | info | Document with `:pipeline="false"`. |
| F107 | error | Unknown `kind` (the check stops there). |
| F108 | error | `status` not valid for the `kind`. |
| F109 | error | Document without `panel`. |
| F110 | error | `approval` used outside `strict` + `document` + `confirmed`, or with a value other than `waiting`, `approved`, `hold`, `rejected`. |

## Behavior

### Two rendering modes

- **Panel** (`panel="top"`): action panel, tabs, optional `strip` and `detail` slots, History tab, status display. Used for every data screen.
- **Plain**: title row, one fieldset with the default slot, optional footer (extra button, Cancel in view mode, split action button). `tabs`, `strip`, `detail`, `special`, `log` and the History tab do not exist here.

### Slots (panel)

| Slot | Where |
|---|---|
| default slot | Tab Data, only when `tabs` is empty. |
| `<x-slot:key>` | One per `tabs` key. |
| `strip` | Summary row above the tabs (document number, date, party, large Grand Total). Inside a fieldset, so it locks with the form. |
| `detail` | Detail lines (`x-dl::lines`) in the middle, always visible, not part of the tabs. Not inside the form fieldset: the lines object controls its own read-only state (`:readonly`). |
| `special` | Screen-specific buttons, printed just before the last panel button (or alone when there are no panel buttons), for example Start / Done on a work order. |

### Locking and read-only display

`locked()` is true on a panel form when `can-input` is false, or the status is `confirmed`, `approval`, `hold`, `approved`, `rejected`, `cancelled` or `closed`, or `strict` is on and the status is not `new`. A locked form gets the class `is-view` and its fieldsets are rendered `disabled` (only the fields; tab buttons and the panel stay clickable). Disabled fields are not submitted: for actions on a locked document the server must work from the stored record, not from request fields. `hidden` inputs are outside the fieldsets and are always sent. `mode="view"` disables the fieldsets in the same way.

In the strict cycle the locked colour follows the final status: Confirmed green, Cancelled red, Inactive grey, Draft neutral (class `dl-form--tone-success|danger|muted`); numbers are bold, dates italic, amount-in-words underlined. This is automatic.

### Standard buttons (non-strict cycle)

Order is left to right; Back is always at the far right and is not part of this list. Print is available in every document status, Draft included (the printout carries the status; Draft is watermarked).

| `kind.status` | Buttons |
|---|---|
| `master.new`, `document.new` | Undo, Save |
| `master.active` | Deactivate, New, Undo, Save |
| `master.inactive` | Activate, New, Undo, Save |
| `document.draft` | Delete, New, Undo, Print, Confirm, Save |
| `document.confirmed` | Cancel, Back to Draft (only with `can-reopen`), New, Email, Print |
| `document.approval` | checker (`can-approve`): Print, Reject, Hold, Approve; otherwise Cancel, New, Print |
| `document.hold` | checker: Print, Reject, Approve; otherwise Cancel, New, Print |
| `document.approved` | Cancel, New, Email, Print |
| `document.rejected` | New, Duplicate, Print |
| `document.working` | Cancel, New, Print, Save (Confirmed but still being filled; work buttons go in the `special` slot) |
| `document.cancelled` | New, Duplicate, Print |
| `document.closed` and any other | New, Print |

After a document enters approval there is no Back to Draft. Email is only shown with Print (see `print-url`).

### Standard buttons (strict cycle)

`strict` makes a saved record locked: Edit opens the fields and swaps the buttons for Save + Undo (CSS classes `is-view-only` / `is-edit-only`; the edit-only buttons also show when the form is dirty). There is no New, Email, Back to Draft or Export. A new record is Draft/Active, filled with default values.

| Case | Buttons |
|---|---|
| `new` (master or document) | Undo, Save |
| master `active` | Deactivate, Delete (only with `deletable`), Edit; while editing Undo, Save |
| master `inactive` | Activate |
| document `draft` | Delete, Edit, Print, Confirm; while editing Undo, Save |
| document `confirmed`, creator, `approval` null or `waiting` | Cancel, Print |
| document `confirmed`, creator, `approval="rejected"` | Duplicate, Print |
| document `confirmed`, creator, `approval` `hold` or `approved` | Print |
| document `confirmed`, `can-approve`, `waiting` | Print, Reject, Hold, Approve |
| document `confirmed`, `can-approve`, `hold` | Print, Reject, Approve |
| document `confirmed`, `can-approve`, `approved` | Print, Reject |
| document `confirmed`, `can-approve`, `rejected` | Print, Approve (with OTP, see below) |
| any other status (`cancelled`, `closed`, ...) | Print |

`can-approve` with `approval` null behaves as the creator. Strict masters have no Confirm, Cancel or Print. Status and approval show as stamps at the top right (`Draft`, `Draft · Unsaved`, `Confirmed`, `Cancelled`, `Active`, `Inactive`, and for a Confirmed document also `Awaiting`, `Approved`, `Hold`, `Rejected`). Stamp text is never translated.

For all cycles: `hide` removes buttons afterwards; Print and Email are removed when `action` is set and `print-url` is not; `can-input="false"` removes the input buttons listed under that prop.

### Actions and confirmation dialogs

Save is a normal submit button (`name` = `action-field`, `value="save"`). Every other panel button is handled by the form script:

| Button | Behavior |
|---|---|
| Undo | Asks "Discard all unsaved changes?" when something changed, restores the values (and lines) from when the form was opened, clears errors and the dirty mark. Clean new form: toast "There is nothing to undo." While editing (strict) a clean Undo just locks the form again. In the legacy cycle the button is disabled while the form is clean. |
| Edit (strict) | Enables the fields, remembers the current values as the Undo point. Edit/Delete/Add on a detail row does the same automatically. |
| New, Print, Email with a URL | Plain link (Print/Email in a new tab). |
| Back | Link to `back`; guarded by the dirty guard. |
| Delete, Confirm | Yes/No dialog. Delete says the record cannot be restored. |
| Cancel, Back to Draft, Reject, Hold | Dialog with a mandatory reason. |
| Approve | Dialog without reason. With the OTP switch (strict, `approval="rejected"`, `can-approve`, `otp-url`) the dialog asks reason + OTP code, both mandatory. |
| Deactivate, Activate | Yes/No dialog. |
| Duplicate, New without URL | No dialog; submitted at once. |

After the dialog the form sets the hidden field named `action-field` to the action key, adds `_reason` (empty when no reason was asked) and, for the OTP case, `_otp`, then submits. Only `confirm` goes through `requestSubmit()`, so only Confirm runs the required-field validation; every other action uses `form.submit()` (no validation, no submit event). Without `action` nothing is sent: the form fires `dl:form-action` and shows a "preview" toast.

### Required-field validation

On Save and Confirm: every `x-dl::field.*` with a required marker and no value is marked invalid with the message "Required"; `x-dl::lines` validate their own cells and count too (fields inside lines are excluded from the form-level pass; fields inside a nested modal are not part of this form). The tab holding errors gets a red counter, the first error's tab is opened and focused, the toast says "N required fields are empty." and the submit is cancelled. Typing a value clears the error of that field. The server must validate again; the form has `novalidate`.

### Dirty guard (`guard`)

With `guard` (default) any named input or select changed inside the form marks it dirty (`is-dirty`, registered in `Devlib.h.dirtyForms`); a change inside `x-dl::lines` marks it as well. Then:

- Back, the dirty-aware breadcrumb and any other link outside the form open the dialog Save / Don't Save / Cancel. Save submits the form through its Save button; Don't Save clears the mark and continues; Cancel stays. Links inside the same form are not intercepted unless they carry `data-dl-back` or `data-dl-leave`.
- Closing the tab, reload and browser Back show the browser's own warning.
- A submit event, Undo and a successful Save clear the mark. Actions sent with `form.submit()` (all panel actions except Confirm) do not fire a submit event and therefore do not clear it.
- A failed required-field check marks the form dirty.
- When Save is clicked the submit button is disabled and shows "Processing..." to prevent a double submit (opt out with `data-dl-no-busy` on the button).

### Stage bar and badge

Non-strict, `kind="document"` (or `pipeline` true): `Draft > Confirmed > Closed`. `new` highlights Draft; `cancelled` and `working` sit on Confirmed, and Cancelled turns that step red and renames it. With `approval-levels` n > 0: `Draft > Confirmed > Approval 1..n > Approved > Closed`; `rejected` / `hold` take the step of `approval-at` and turn red / amber (`Rejected L2`, `Hold L2`); `cancelled` is red at its position. Without `approval-levels`, the statuses `approval`, `hold`, `approved` and `rejected` do not match any step, so no step is highlighted. Step labels are translated only when `config('devlib.translate_fixed')` is on.

Without the bar (master, or `pipeline=false`) the header shows a status badge: Draft (for `new` and `draft`), Confirmed (also for `working`), Cancelled, Closed, `Waiting Approval N`, `Hold LN`, Approved, Rejected, Active, Inactive. Strict forms show stamps instead of the bar or badge.

### History tab

A tab called History (dictionary: "Riwayat") is always appended to a panel form. It shows created by/at and last updated by/at, and, when `log` is not empty, a table Date & time, By, Action, Note. Each action cell gets a class `dl-form__logact--<action lower-cased, spaces to ->` for colouring.

### Print and Email

Print/Email are links to `print-url` (Email adds `ds_do=email`), opened in a new tab. The printout page must show the document status (and approval status); Print on an unsaved change prints the stored data, not the screen. If `action` is not set and there is no `print-url`, the buttons stay as plain buttons and only fire `dl:form-action` (the screen handles it, for example by opening `x-dl::doc-share`).

### Translation

Screens write English. `title`, `subtitle` and `tabs` labels go through `Lang::text()` (dictionary); the History labels and table headings, dialog texts and toasts are in the DevLib dictionary. Data inside those props must be wrapped: `:title="'Customer Order · '.raw($co->co_no)"`, `:subtitle="raw($customer)"`. `raw()` is the global helper for `Devlib\Lang::raw()`; the invisible markers are stripped before output. Button labels and status words are English by design; with `config('devlib.translate_fixed')` (single-language apps) buttons, badges and stage-bar labels follow the application dictionary. Stamps and `stage` are never translated.

### Access

`can="po.approve"` on the form removes the whole form from the output when the user lacks the ability (`Access::can`). Per-button rights are expressed with `can-input`, `can-reopen`, `can-approve`, `deletable` and `hide`, and must be enforced again on the server.

### Plain-form extras

Footer: optional extra button (`extra-action`) that opens a QR with all field values (passwords masked, files and `_token` / `_action` skipped, multi-value fields merged into arrays); in `mode="view"` a Cancel button; the split button. Choosing an item in the split menu only selects it; the main button submits. The split button always posts its choice in a field called `_action`, whatever `action-field` says.

## Data and events

### What the server receives

A normal POST (or GET) of all enabled named fields plus:

| Field | When |
|---|---|
| `_token` | every non-GET form |
| entries of `hidden` | always |
| `<action-field>` (default `_action`) | Save: `save`. Panel buttons: `delete`, `confirm`, `cancel`, `draft`, `approve`, `reject`, `hold`, `duplicate`, `deactivate`, `activate`, and `new` when the New button has no `new-url`. Never sent: `edit`, `undo`, `back`; `print` and `email` never submit. Split button (plain form): the chosen label. |
| `_reason` | every panel action other than Save (empty string when no reason was asked) |
| `_otp` | Approve of a rejected document (strict, OTP dialog) |

Numeric fields marked `data-dl-num` are sent in canonical form (`1234.5`) regardless of display language. Disabled (locked) fields are not sent.

### What the server must do

Always redirect after POST (PRG):

- Save, Confirm, Cancel, approval decisions, Deactivate/Activate: redirect back to the form of the same record (a new record: to its own URL, with its new status), with a flash message.
- Delete: redirect to the grid (the `back` / `from` address).
- Duplicate: redirect to the empty form that copies the record (the demo uses `copy=<id>`; a Draft without number).
- Errors (missing field, no right, action not valid in this state, wrong OTP, no reason): redirect back to the form and flash an error; the status must not change.
- Validate `from` / `back` as a local path only, and re-check rights, status transitions and required reasons on the server.

Flash messages are shown as one-time toasts on the target page by rendering, in your layout, `<div hidden data-dl-flash="success|danger|warn|info">text</div>`; the boot script turns it into a toast and removes the element.

### Markup hooks

| Hook | Where |
|---|---|
| `data-dl-form` | the `<form>`; JS class `Form` |
| `data-mode` | `edit` or `view` |
| `data-action-field` | name of the action field |
| `data-dl-guard` | dirty guard on |
| `data-f-strict` | strict cycle |
| `data-f-otp`, `data-f-otp-url` | OTP dialog on Approve (rejected document) |
| `data-f-act="<key>"` | every panel button: `save`, `undo`, `delete`, `new`, `print`, `confirm`, `cancel`, `draft`, `email`, `duplicate`, `edit`, `deactivate`, `activate`, `approve`, `reject`, `hold`, `back` |
| `data-f-fs` | each content fieldset |
| `data-f-edit`, `data-f-cancel` | Edit / Cancel buttons of the plain view mode |
| `data-dl-form-extra` | the extra QR button |
| `data-dl-back` | Back button |
| classes | `dl-form`, `dl-form--panel`, `dl-form--strict`, `is-view`, `is-editing`, `is-dirty`, `dl-form--tone-{success, danger, muted}`; stamps `dl-form__stamp--{success, warn, danger, muted, neutral, wait}`; steps `dl-form__step` plus one of `is-done`, `is-active`, `is-rejected`, `is-hold` |

### Custom events (bubble from the `<form>`)

| Event | `detail` | When |
|---|---|---|
| `dl:form-action` | `{action: '<key>'}` | A panel button handled by the script was confirmed (Delete, Confirm, Cancel, Print/Email/New without a link, Approve, ...), just before the form is submitted or the preview toast is shown. Not cancelable. Not fired for Save, Edit, Undo, Back or link buttons. |
| `dl:form-submit` | object of all field values (without `_token`) | Save of a form that has no `action` (preview). The enclosing modal/drawer is then closed. |

### JavaScript

- `el.dlComponent` on the form is the `Form` instance: `values()`, `undo()`, `startEdit()`, `endEdit()`, `act(key)`, `setView(bool)`, `validate()` (returns the number of errors).
- `Devlib.h.dirtyForms` is the `Set` of dirty guarded containers (form elements, or other `data-dl-guard` containers); `Devlib.h.markDirty`, `askDirty` and `submitForm` are the helpers behind the guard.
- `Devlib.confirm({message, tone, reason, otp})` (promise), `Devlib.toast(text, tone)`, `Devlib.fill(form, data, clear)` are the helpers the form uses.

## Rules and pitfalls

Project rules (`skills/devlib/form.md`, `grid-form-flow.md`, `transaction-lifecycle.md`):

- Back goes to the grid of the module: set `:back="route('x.index')"` (with the query the grid had) and `'back_history' => false` in `config/devlib.php`. Never use `history.back()` or `url()->previous()` in a form; after Save "previous" is the entry form and double input is a risk. Without `back`, the Back button falls back to `history.back()`.
- Document forms use `kind="document"` and `panel="top"`; Save/Confirm/Delete/Cancel/Print/Back to Draft come from the status. Never build these buttons by hand and do not hide mandatory actions. Say **Confirm**, not Submit.
- The controller reads the action field (`save`, `confirm`, `delete`, `cancel`, `approve`, `reject`, ...). Layouts must keep the double-submit guard.
- Required fields: mark the fields `required`; show server errors with `:error="$errors->first('x')"`; restore values with `old()`.
- Do not mix up Single Form (a transactional record: Draft, Confirmed, Cancelled, with number and Print) and Master Form (`kind="master"`: Active/Inactive only).
- Status and approval are two fields in the strict cycle. Rejected becomes Approved only by a checker with an OTP issued by an administrator; the OTP issue and check are the project's job, DevLib only provides the dialog.
- Print always carries the document status (and approval status).
- A new record is Draft/Active with default values filled; only fields the user must choose stay empty.

Pitfalls:

- `history.back()` or a missing `back`: F101/F102.
- A document without `:status` falls back to `active`: wrong buttons and F108.
- A document without `print-url` while `action` is set has no Print/Email (F103). A New button without `new-url` posts `_action=new` to your controller (F104).
- `method="get"` (lower-case) is rendered as POST. Use `'GET'`.
- Locked fields are not posted. The server must never rebuild a Confirmed or Draft-locked document from request fields on Confirm, Cancel or Approve; load the record.
- `can-reopen`, `can-approve`, `can-input` and `deletable` only change what is shown. Check the same rights, the allowed status transition, the reason and the OTP on the server.
- In the strict cycle detail-row buttons (`x-dl::lines`) unlock the form when clicked. Give the lines `:readonly` for Confirmed / Cancelled / Closed documents.
- Panel actions other than Confirm skip client validation; the form-level validation is no substitute for server validation.
- `pipeline` has no effect when `strict` is on. Without `approval-levels`, the legacy statuses `approval`, `hold`, `approved`, `rejected` leave the stage bar without an active step.
- `strict` statuses other than `new`, `draft`, `confirmed` (and `active` / `inactive` for masters) give only a Print button.
- The History tab key is `audit`; do not use it as a tab key. Tab keys must not collide with other slot or prop names.
- Names of the strict cycle (`approval`, `otp-url`, `deletable`) do nothing in the legacy cycle, and `new-url`, `can-reopen`, `pipeline` do nothing in the strict cycle.
- `can-input="false"` also removes Cancel and Duplicate (and Save, Undo, Delete, Confirm, New, Edit); a checker keeps Approve/Reject/Hold, Back to Draft, Email, Print, Deactivate/Activate.

Data integrity:

- Delete is only for Draft documents and, for masters, only for unused data and administrators (`deletable`). A Confirmed document is cancelled, never deleted; a Cancelled record stays stored, and a Cancel that makes other data wrong (stock, balance) must create the reversing adjustment on the server (see `skills/devlib/engine.md`).
- Run Save, Cancel and approval decisions in one database transaction and write every status change to the History log (`log`).
- Never trust `status`, `approval` or `as`-like hidden fields posted by the browser; they are for the demo only. Read them from the stored record and the login.

## Example

### Master form (strict cycle)

```blade
<x-dl::form :title="$isNew ? 'Employee · New' : 'Employee · '.raw($emp->name)" :subtitle="$isNew ? null : raw($emp->code)"
    panel="top" kind="master" :status="$status" :strict="true" :deletable="$isAdmin && ! $isNew && ! $emp->used"
    :action="route('emp.save', $emp ?? 'new')" method="POST" :back="$back" :hidden="['status' => $status, 'from' => $back]"
    :tabs="['general' => 'General', 'personal' => 'Personal']"
    :created-by="$emp->created_by ?? null" :created-at="$emp->created_at ?? null" :updated-by="$emp->updated_by ?? null" :updated-at="$emp->updated_at ?? null">
    <x-slot:general>
        <x-dl::form.row :cols="3">
            <x-dl::field.text name="code" label="Employee code" :value="$emp->code ?? null" placeholder="(automatic)" code readonly :case-toggle="false" hint="Made on the first Save." />
            <x-dl::field.text name="full_name" label="Full name" :value="$emp->name ?? null" required />
            <x-dl::field.date name="join_date" label="Join date" :value="$emp->join_date ?? now()->format('Y-m-d')" required />
        </x-dl::form.row>
    </x-slot:general>
    <x-slot:personal>
        <x-dl::form.row :cols="3">
            <x-dl::field.radio name="gender" label="Gender" :options="['M' => 'Male', 'F' => 'Female']" :value="$emp->gender ?? null" />
            <x-dl::field.text name="birth_place" label="Place of birth" :value="$emp->birth_place ?? null" />
        </x-dl::form.row>
    </x-slot:personal>
</x-dl::form>
```

### Strict document with approval

```blade
<x-dl::form :title="$isNew ? 'Cash Voucher · New' : 'Cash Voucher · '.raw($cv->no)" :subtitle="$isNew ? null : raw($cv->payee)"
    panel="top" kind="document" :status="$status" :strict="true"
    :approval="$status === 'confirmed' ? $cv->approval : null" :can-approve="$canApprove" :otp-url="$isNew ? null : route('cv.save', $cv)"
    :action="route('cv.save', $cv ?? 'new')" method="POST" :back="$back" :print-url="$isNew ? null : route('cv.print', $cv)"
    :hidden="['status' => $status, 'from' => $back]" :log="$log"
    :created-by="$cv->created_by ?? null" :created-at="$cv->created_at ?? null" :updated-by="$cv->updated_by ?? null" :updated-at="$cv->updated_at ?? null">
    <x-dl::form.row :cols="3">
        <x-dl::field.text name="cv_no" label="Voucher No." :value="$cv->no ?? null" placeholder="(automatic)" code readonly :case-toggle="false" />
        <x-dl::field.date name="cv_date" label="Date" :value="$cv->date ?? now()->format('Y-m-d')" required />
        <x-dl::field.select name="paid_from" label="Paid from" :options="['Main Cash' => 'Main Cash', 'Petty Cash' => 'Petty Cash']" :value="$cv->paid_from ?? 'Main Cash'" required />
    </x-dl::form.row>
    <x-dl::form.row :cols="3">
        <x-dl::field.text name="payee" label="Payee" :value="$cv->payee ?? null" required />
        <x-dl::field.number-words name="amount" label="Amount" currency="IDR" :value="$cv->amount ?? null" required />
    </x-dl::form.row>
</x-dl::form>
```

The controller handles `_action` = `save`, `delete`, `confirm`, `cancel`, `approve`, `hold`, `reject`, `duplicate`, redirects with a flash message, and for a rejected document checks `_otp` and `_reason`. In the layout: `@if (session('flash'))<div hidden data-dl-flash="{{ session('flash')[0] }}">{{ session('flash')[1] }}</div>@endif`.

### Read-only view (plain form)

```blade
<x-dl::form title="Supplier" mode="view" :action="route('supplier.update', $s)" method="POST" :hidden="['id' => $s->id]" :actions="['Save' => 'fa-floppy-disk']">
    <x-dl::form.col>
        <x-dl::field.text name="name" label="Supplier name" :value="$s->name" required />
        <x-dl::field.text name="city" label="City" :value="$s->city" />
    </x-dl::form.col>
</x-dl::form>
```

The fields are disabled; Edit in the header enables them and shows the footer Cancel (restores the old values); the footer Save posts the form. For a locked document (Confirmed or Cancelled) use the panel form of the previous example with the matching `status`: the fields are read-only automatically.

## Related

- `x-dl::grid`: the list that opens the form (`open-url`, `new-url`, `restore`) and receives the user back.
- `x-dl::lines`: detail lines for Master-Detail forms, placed in the `detail` slot (`:row-state`, `:readonly`).
- `x-dl::form.row` and `x-dl::form.col`: field rows and columns inside the form.
- `x-dl::field.text`, `x-dl::field.select`, `x-dl::field.date` and the other `x-dl::field.*` objects: the inputs; their `required` flag drives the form validation.
- `x-dl::back-button`: standalone Back link for screens without a form panel (the form's own Back is built in).
- `x-dl::approval`: Approval Center, the read-only list-and-detail screen where checkers approve, hold or reject many documents.
- `x-dl::print-doc` and `x-dl::doc-share`: the printout opened by Print/Email and its print, PDF, online and e-mail actions.
- `x-dl::action-form`: small `<form>` wrapper for action buttons, dialog bodies and GET filters.
- `x-dl::wizard`: step-by-step input without a grid.
- `x-dl::split-button`: the footer split action button of a plain form.
- `x-dl::modal` and `x-dl::drawer`: a form without `action` inside them closes the layer after Save.
- `skills/devlib/form.md`, `skills/devlib/grid-form-flow.md`, `skills/devlib/transaction-lifecycle.md`: the project rules behind this object.
