## Overview

`<x-dl::qc-bar>` is the quick **QC bar** that floats at the bottom edge of a page: it shows the review state of the current page for one stage, the other stages as small chips, and two buttons: **Approved** (one click, saved at once with the time) and **Need improvement** (opens a pop-up where a note about what must be improved is mandatory). It is a development and acceptance aid of the catalog and of inheriting projects that run a page-by-page QC; it is not a document approval.

The object only draws the bar and posts the decision. **Storing is the job of the application** (a POST route and a table); DevLib provides a ready store, `Devlib\Qc`, and the demo route used by the catalog.

Pick another object when:

- a business document is approved by a checker with levels, history and a decision log: `x-dl::approval`;
- a normal form needs a status and action buttons: `x-dl::form`;
- you need a transient message: `Devlib.toast`, or `x-dl::alert`.

### The three stages

Three approval layers run in sequence (owner rule 2026-10-07):

| Stage key | Chip label | Meaning |
|---|---|---|
| `QC` | `QC` | the user who checks the page |
| `UAT_OFFICER` | `UAT officer` | the officer concerned |
| `UAT_SUPERVISOR` | `UAT supervisor` | the superior of that officer |

The constant is `Devlib\Components\QcBar::STAGES`. A bar decides exactly one stage (`stage`); the chips on its left (`chain`) only inform about all three. When all three are `APPROVED` the page is **Ready to release** (`release`). In the current catalog only the `QC` stage is decided by the bar (`Devlib\Qc::bar()` has `$stage = 'QC'`).

### Statuses

`status` is `null` (not reviewed), `APPROVED`, or `NEEDS_IMPROVEMENT`. History is never overwritten: every decision is a new row, and the valid decision of a stage is the last row per (`key`, `stage`).

## Props

All constructor props of `Devlib\Components\QcBar` (12). Blade names are kebab-case (`can-review`); use `:prop="expr"` for anything that is not a plain string. In practice the props are filled from `Devlib\Qc::bar()`, which returns exactly these keys (catalog helper: `App\Support\Qc::bar($slug, $name, $user)`).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `key` | `string`, `''` | Stable identifier of the reviewed item (the catalog uses the page slug). Written to `data-key` and posted. The store cuts it to 190 characters and rejects an empty key. |
| `name` | `string`, `''` | Display name of the item, shown on the left of the bar and in the pop-up. Written to `data-name` and posted (cut to 190 by the store). Data: not translated. |
| `stage` | `string`, `'QC'` | The stage this bar decides: `QC`, `UAT_OFFICER` or `UAT_SUPERVISOR`. Posted with the decision; any other value is refused by the store (HTTP 422, red toast). The chip with this key gets the class `is-here`. |
| `status` | `?string`, `null` | The current decision of this stage: `null`, `APPROVED` or `NEEDS_IMPROVEMENT`. Adds the root class `is-approved` or `is-improve` and the state text ("Not reviewed" / "Approved" / "Need improvement"). |
| `note` | `?string`, `null` | The note of the last `NEEDS_IMPROVEMENT` decision. Shown after the by/at text, only when `status` is `NEEDS_IMPROVEMENT`. Data: not translated. |
| `by` | `?string`, `null` | Who took the last decision. The meta text (`<by> · <date>`) is only printed when `by` is set. |
| `at` | `?string`, `null` | When (a date or datetime string), formatted by `Locale::date()` in the meta text. |
| `action` | `?string`, `null` | URL that receives the decision (POST, JSON). With no `action` the buttons do nothing at all (no toast, no request). |
| `csrf` | `?string`, `null` | CSRF token, sent in the header `X-CSRF-TOKEN` of the request. Pass `csrf_token()`. Without it a normal Laravel app answers 419 and the bar shows the red toast. |
| `can-review` | `bool`, `true` | `false` removes the two buttons (the bar becomes a read-only display; the pop-up markup is still present but cannot be opened). This is a display flag only: the server must check the right again. |
| `chain` | `array`, `[]` | The summary of all stages: `[stage key => [label, status]]` in display order, for example `['QC' => ['QC', 'APPROVED'], 'UAT_OFFICER' => ['UAT officer', null], ...]`. One chip per entry: solid green for `APPROVED`, solid amber for `NEEDS_IMPROVEMENT`, dashed for no decision. Labels are written as given (not translated). Empty = no chips. |
| `release` | `bool`, `false` | Shows the blue "Ready to release" tag with a rocket icon. Compute it on the server (all three stages `APPROVED`); `Devlib\Qc::bar()` does it. |

### Attributes accepted on every DevLib object

`can="module.action"` (no access = not rendered) and any HTML attribute (`id`, `class`, `style`, `data-*`), merged into the root `div`.

## Behavior

### Generated markup

```html
<div class="dl-qc [is-approved|is-improve]" data-dl-qc data-key="..." data-name="..." data-stage="QC" data-action="..." data-csrf="..." data-t-approved="..." data-t-improve="..." data-t-saved="..." data-t-failed="..." data-t-empty="...">
  <span class="dl-qc__name">name</span>
  <span class="dl-qc__chip [is-approved|is-improve] [is-here]">QC</span> ...        <!-- one per chain entry -->
  <span class="dl-qc__rel"><i class="fa-solid fa-rocket"></i> Ready to release</span> <!-- release -->
  <span class="dl-qc__state" data-qc-state>Not reviewed</span>
  <small class="dl-qc__meta" data-qc-meta>by · date · note</small>
  <button type="button" class="dl-btn dl-btn--secondary dl-btn--sm" data-qc-improve>Need improvement</button>  <!-- can-review -->
  <button type="button" class="dl-btn dl-btn--primary dl-btn--sm" data-qc-ok>Approved</button>                 <!-- can-review -->
  <div class="dl-mov" data-qc-dialog role="dialog" aria-modal="true"> ... textarea[data-qc-note] maxlength=500, em[data-qc-err], buttons data-qc-cancel / data-qc-save ... </div>
</div>
```

The bar is `position: fixed`, centered at the bottom (14 px from the edge, `z-index` 60, at most the viewport width minus 24 px). Put **one** bar per page, as the last element of the page body, so it does not depend on the page layout.

### Approved

One click on **Approved** posts at once, without a confirm dialog and without a note. On success the root becomes `is-approved`, the state text changes, the chip of this stage turns green, the meta text is replaced by the text the server returned, and the toast "QC decision saved" shows.

### Need improvement

The button opens the pop-up "What needs to be improved?" with the item name, a textarea (maximum 500 characters) and Cancel / Save. Save with an empty note stays open and shows the line "Write what needs to be improved." under the box (the line clears when the user types). With a note, the decision is posted as `NEEDS_IMPROVEMENT`; on success the root becomes `is-improve`, the chip turns amber, the meta text is the server meta followed by ` · <note>`, the textarea is emptied and the pop-up closes. The pop-up also closes with the X button, with Cancel, and with a click on the dark area outside it. It does **not** close with the Esc key and there is no focus trap; focus goes to the textarea 50 ms after opening.

### Failure

Any non-2xx answer, network error, or a response that is not JSON shows the red toast "QC decision was not saved"; the bar keeps its previous state. A double click on **Approved** sends two requests (there is no busy guard) and writes two history rows.

### Language

The texts (`Not reviewed`, `Approved`, `Need improvement`, `Ready to release`, the pop-up texts, the toasts) come from the DevLib dictionary and follow the page language. Chip labels, `name`, `note`, `by` are data and are not translated.

### Accessibility as implemented

Buttons are native `<button type="button">` with text. The pop-up has `role="dialog"` and `aria-modal="true"` (no accessible name, no focus trap, no Esc). The close X has an `aria-label`. State changes are announced only by the toast.

## Data and events

### What the server receives

`POST <action>` with `Content-Type: application/json`, `Accept: application/json`, header `X-CSRF-TOKEN`, credentials `same-origin`, body:

```json
{"key": "p5-form-approval", "name": "Form Approval", "stage": "QC", "status": "NEEDS_IMPROVEMENT", "note": "The Hold button is too small"}
```

`status` is `APPROVED` or `NEEDS_IMPROVEMENT`; `note` is `''` for `APPROVED`. The answer must be JSON `2xx`; it may contain `meta` (a short text such as `login · 21 Oct 2026`) that replaces the meta text on the bar.

### The store `Devlib\Qc` (backend pair)

`src/Qc.php` writes and reads the table `qc_review` (name and connection can be changed: `new Qc($connection, $table)`, bind it again in `AppServiceProvider`; the migration of the catalog is `site/database/migrations/2026_10_07_000001_create_qc_review_table.php`).

| Method | What it does |
|---|---|
| `bar($key, $name, $action, $csrf, $canReview, $stage = 'QC')` | Returns the prop array of this object (including `chain` and `release`). `canReview` is forced false when the table does not exist. |
| `record($key, $name, $stage, $status, $note, $by)` | Inserts a row. Aborts 422 when the stage or status is unknown, the key is empty, or a `NEEDS_IMPROVEMENT` has no note. Trims the note and cuts it to 500 characters. |
| `latest($key)`, `statusMap($stage)` | The valid decision per stage of one item / of all items for one stage. |
| `releasable()` | Items with all three stages `APPROVED`. |
| `open()`, `approved($limit)`, `resolve($id, $by)` | Lists for the command `php artisan qc:reviews` (open Need improvement notes, ready to release, latest Approved; `--resolve=<id> --by=<name>` closes a note). |

The catalog route `POST /qc/review` (named `qc.review`) accepts the post only from a session with a developer account and when the table exists (otherwise 403), validates `key`, `name`, `stage`, `status`, `note` (maximum 500), calls `record()` and answers `{ok: true, meta: "<login> · <date>"}`.

### Events

None. The object dispatches no `dl:*` event; it emits a toast through `Devlib.toast`. To react to a decision, change the route answer or read the page again.

### Markup hooks and JavaScript

| Hook | Where |
|---|---|
| `data-dl-qc` | root; JS class `QcBar` |
| `data-qc-ok`, `data-qc-improve` | the two buttons |
| `data-qc-dialog`, `data-qc-note`, `data-qc-err`, `data-qc-cancel`, `data-qc-save` | the pop-up parts |
| `data-qc-state`, `data-qc-meta` | state text and meta text updated after a save |
| `data-t-*` | the translated texts used by the script |
| classes | `dl-qc`, `is-approved`, `is-improve`, `dl-qc__chip` (+ `is-approved`, `is-improve`, `is-here`), `dl-qc__rel` |

`el.dlComponent` on the root is the `QcBar` instance (`open()`, `close()`, `saveNote()`, `send(status, note)`).

## Rules and pitfalls

- One bar per page. It is a fixed overlay: do not place it inside a scrolling container or inside a form.
- The bar stores nothing by itself. Without `action` and `csrf` from a working route, the buttons are dead (and `action` missing is silent).
- Check the right on the server. `can-review` only hides the buttons; the catalog route answers 403 to a non-developer account.
- Keep the history: insert a new row for every decision, never update the old one. The valid decision is the last row per (item key, stage).
- `key` must stay the same for the same item over time (use the page slug or a stable code), or the history splits.
- The `note` is mandatory for `NEEDS_IMPROVEMENT` in the pop-up and again on the server (422).
- `chain` labels and `release` are server-made when the page is drawn. After a save the script updates only the state, the meta text and the chip of the decided stage; the other chips and the "Ready to release" tag stay until the page is loaded again.
- Do not use this bar for business approval of documents; it has no levels, no roles, no document history. Use `x-dl::approval`.
- No lint rule (`devlib:lint`) is specific to this object.
- Code oddity (not fixed): `resources/assets/js/components/qc-bar.js:29` returns silently when `action` is empty, so the Approved button, and Save in the pop-up, give no feedback at all.
- Code oddity (not fixed): `resources/assets/js/components/qc-bar.js:15-16` closes the pop-up only on click events; the Esc key is not handled (unlike the standard `Devlib.confirm` dialog).
- Code oddity (not fixed): `src/Components/QcBar.php:24` fixes the chip labels (`QC`, `UAT officer`, `UAT supervisor`) as English constants that are not passed through the dictionary, and `src/Qc.php:60` / the migration comment say only the `QC` stage is decided at present, so the two UAT chips are display-only.

## Example

As used by the catalog layout (`site/resources/views/lib.blade.php`, last element of the page):

```blade
@php($qc = \App\Support\Qc::bar((string) request()->route('slug'), $page['label'], session('devlib_user')))
<x-dl::qc-bar :key="$qc['key']" :name="$qc['name']" :stage="$qc['stage']" :status="$qc['status']" :note="$qc['note']" :by="$qc['by']" :at="$qc['at']" :action="$qc['action']" :csrf="$qc['csrf']" :can-review="$qc['canReview']" :chain="$qc['chain']" :release="$qc['release']" />
```

A bar with fixed fictional data (no store behind it, so the buttons do nothing):

```blade
<x-dl::qc-bar key="p9-sample-screen" name="Sample Screen" stage="QC" status="NEEDS_IMPROVEMENT" note="The Hold button is too small"
    by="qa.user" at="2026-10-20 16:40" action="/qc/review" :csrf="csrf_token()" :can-review="true"
    :chain="['QC' => ['QC', 'NEEDS_IMPROVEMENT'], 'UAT_OFFICER' => ['UAT officer', null], 'UAT_SUPERVISOR' => ['UAT supervisor', null]]" />
```

Route of the application (sketch, fictional):

```php
Route::post('/qc/review', function (Request $r) {
    abort_unless($r->user()?->can('qc.review'), 403);
    $r->validate(['key' => 'required|string|max:190', 'stage' => 'required|string', 'status' => 'required|string', 'note' => 'nullable|string|max:500']);
    return app(\Devlib\Qc::class)->record($r->key, (string) $r->name, $r->stage, $r->status, $r->note, $r->user()->name)
        + ['meta' => $r->user()->name.' · '.now()->format('d M Y')];
});
```

## Related

- `x-dl::approval`: the business Approval form (levels, history, decision log) for documents; the QC bar is a lighter page review.
- `x-dl::code-button`: the catalog button next to which the bar sits on every demo page.
- `Devlib\Qc` (`src/Qc.php`) and the command `php artisan qc:reviews`: the store and the agent report.
- `x-dl::modal`: the standard dialog; the bar's pop-up is its own small copy of the same classes.
- `README.md` (section "Perintah untuk agen"): how to bind the store to another table or connection.
