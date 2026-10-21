## Overview

`<x-dl::permission-matrix>` is the editor for group access rights per screen object. The user picks a group in a select; the table lists every object (`Form` or `Grid`, with its module) and each row has a select with an access level (`No access`, `Read only`, `Read-write`, `Read-write + Export`, `All` by default). The toolbar filters the rows by module and by name, and "Set all shown" applies one level to every row currently visible. Changed rows are marked, the footer counts unsaved changes across all groups, changes survive switching group, and Reset throws them away.

The object only edits and reports levels. It does not enforce anything: enforcement is the application's job (for example through `Devlib\Access::resolveUsing()` and the `can="module.action"` attribute that every DevLib object accepts). Saving is either an event (`dl:permissions-save`) for the host to handle, or a JSON `POST` to `save-url` done by the object itself.

Use it for "which group may do what on which object". Do NOT use it for one yes/no right per user (use `x-dl::field.toggle` or a `x-dl::grid` with a toggle column), for assigning users to groups (a `x-dl::grid`), or for choosing a winner per row (`x-dl::pick-matrix`).

## Props

Constructor of `Devlib\Components\PermissionMatrix` (7 props). Blade names are kebab-case; arrays and numbers go through `:prop="expr"`.

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `objects` | `array` | `[]` | The rows of the table: `['key' => string, 'name' => string, 'type' => 'Form'\|'Grid', 'module' => string]`. See "The objects array" below. Encoded into `data-objects`. |
| `groups` | `array` | `[]` | List of group names (strings) for the group select. The group name is also the key used in `perms` and in the posted `group`. `groups` is in the base class list of translated lists, so each name goes through `Lang::text` (see pitfalls). |
| `perms` | `array` | `[]` | Current rights: `[groupName => [objectKey => levelIndex]]`, for example `['Sales' => ['inq' => 2, 'po' => 0]]`. A group or object that is absent gets `default`. Encoded as `data-perms` (an object, even when empty). Level indexes must be integers (JSON numbers). |
| `levels` | `array` | `['No access', 'Read only', 'Read-write', 'Read-write + Export', 'All']` (`PermissionMatrix::LEVELS`) | Ordered list of level labels. The stored value is the index in this list (0 = first). Labels are translated with `Lang::text` in the view, so write them in English. The same list is used for every row and for "Set all shown". |
| `default` | `int` | `1` | Level index used when `perms` has no entry for the group and object. With the default levels, `1` = "Read only". Use `0` for a deny-by-default screen. Written as `data-default`. |
| `source` | `?string` | `null` | Free text naming where the data comes from, for the developer catalog (`data-dl-src`). `null` = `'sec_group_app (grup x objek sys_app_menu)'`. No visual or runtime effect. |
| `save-url` | `?string` | `null` | Address of a JSON `POST` endpoint. When set (written to `data-save-url`), Save posts the current group's rights to the server first and marks them saved only if the server answers with a 2xx status. `null` = no request; Save only marks the changes as saved on the screen and fires the event. See "Data and events" for the exact contract. |

### The objects array

```php
['key' => 'po', 'name' => 'Purchase Order', 'type' => 'Form', 'module' => 'Procurement']
```

| Key | Valid values | Notes |
|---|---|---|
| `key` | string | Required, unique. It is the key of the object in `perms` and in the posted `perms` map. |
| `name` | string | Required. Shown in the first column and used by the name search (the script calls `name.toLowerCase()` while searching, so a missing name raises a JavaScript error as soon as the user types). Also part of the select's `aria-label`. |
| `type` | `'Form'`, `'Grid'` (or any string) | Shown as a pill. The CSS class is `is-<type lower-case>`; `is-grid` has its own colour (violet), anything else, including `Form`, uses the base accent colour. Any other string is accepted and shown as is. |
| `module` | string | Used for the module select (distinct values, in order of appearance, computed in PHP) and the Module column. |

## Behavior

### Markup produced

```html
<div class="dl-perm [is-dirty]" data-dl="x-dl::permission-matrix" data-dl-class="PermissionMatrix" data-dl-src="sec_group_app (...)"
     data-dl-perm data-objects="[...]" data-groups="[...]" data-perms="{...}" data-levels="[...]" data-default="1" [data-save-url="..."]>
  <div class="dl-perm__bar"> Group select (data-pm-group), Module select (data-pm-module),
       search (data-pm-q), "Set all shown" select (data-pm-bulk) </div>
  <div class="dl-perm__scroll"><table class="dl-perm__table"> ... <tbody data-pm-body> rows built by the script </tbody></table></div>
  <div class="dl-perm__foot"><span data-pm-dirty></span> ... Reset (data-pm-reset), Save (data-pm-save)</div>
</div>
```

Each body row: `<tr class="is-dirty?"><td>name</td><td><span class="dl-perm__pill is-grid|is-form">type</span></td><td>module</td><td><select data-pm-obj="key" class="is-l<level>">...</select></td></tr>`. The level select carries the class `is-l<index>`; the CSS styles `is-l0` (muted) and `is-l4` (green, bold) only, so with the default five levels "No access" is grey and "All" is green.

### Working with it

- The group select decides whose rights are shown and edited. Edits to one group are kept when the user switches to another; nothing is lost until Reset or a page reload.
- The module select and the search box (case-insensitive substring of the object `name`) only filter which rows are displayed. They do not change the data.
- "Set all shown" applies the chosen level to every row currently displayed (so it respects the module and search filters) for the selected group, shows a toast "N objects set to: level" and goes back to the empty option.
- A row whose level differs from the saved level gets `is-dirty` (amber background and bar). The footer shows "N unsaved changes" (counted over all groups, not only the visible one) or "No changes".
- Reset restores all groups to the last saved state (initial `perms`, or the state after the last successful Save).
- Save is described in "Data and events". After a successful Save a toast "Group access saved." appears.
- When no row matches the filters the table shows one row "No objects."

### Language

Labels use `Lang::t()` keys (`g_group`, `pmx_module`, `pmx_all_modules`, `pmx_set_shown`, `pmx_object`, `pmx_type`, `pmx_access`) and `Lang::text()` for "Search objects…", "Set all" and the level names; script messages use `pmx_saved`, `pmx_bulk_set`, `pmx_dirty`, `pmx_clean`, `pmx_none`. Object names, modules, group names and types are data and are shown as given (but see the pitfall about `groups`).

### Accessibility

Native controls only: `select`s with `aria-label` (the row select reads "Access <object name>"), a search input with `aria-label`, and buttons. The table header is sticky inside the scroll area (max height 460px). Keyboard use is the browser's: Tab to a select and use the arrow keys.

### Access

The generic `can="module.action"` attribute hides the whole matrix when the ability is missing. Because this object changes everyone's rights, also authorize the `save-url` route on the server; hiding the component is not security.

## Data and events

### Save contract (`save-url`)

When `save-url` is set, clicking Save sends exactly one request for the group currently selected:

```
POST {save-url}
Content-Type: application/json
Accept: application/json
X-CSRF-TOKEN: {window.DL_CSRF or ''}
credentials: same-origin

{"group": "Sales", "perms": {"inq": 2, "quo": 2, "po": 0, ...}}
```

- `group`: the text of the selected group option (the entry of `groups`, after translation).
- `perms`: an object `objectKey => levelIndex` for EVERY object in `objects` (not only the visible or the changed ones), taken from the working copy; objects without an entry carry `default`.
- Success: any response with a 2xx status. The body is ignored.
- Failure: any non-2xx response. The script shows a danger toast with the `message` field of the JSON body (or the HTTP status text when the body is not JSON), keeps all changes on the screen as unsaved and does NOT fire `dl:permissions-save`. A Laravel `abort(403, 'message')` or a `ValidationException` response (422 with `message`) fits.
- Redirects are followed by `fetch`; a redirect to a login page that returns 200 is treated as success, so make the route return 401/403 JSON for unauthenticated or unauthorized calls.
- A network error (the request cannot be made) is not caught by the script: no toast, nothing is marked saved.

A minimal Laravel endpoint (host code):

```php
Route::post('/security/permissions', function (Request $request) {
    abort_unless($request->user()->can('security.manage'), 403, 'You may not change rights.');
    $data = $request->validate([
        'group' => ['required', 'string'],
        'perms' => ['required', 'array'],
        'perms.*' => ['integer', 'between:0,4'],
    ]);
    // store $data['perms'] for group $data['group']
    return response()->json(['ok' => true]);
});
```

With `save-url` omitted, nothing is sent; use the event.

### Event

| Event | When | `detail` |
|---|---|---|
| `dl:permissions-save` | After Save, once the server accepted it (immediately when there is no `save-url`). Dispatched on the root `.dl-perm`, bubbles. | `{group, perms, all}`: `group` = selected group; `perms` = `{objectKey: levelIndex}` for all objects of that group; `all` = the working state of ALL groups (`{group: {objectKey: levelIndex}}`; it holds the groups that have an entry in `perms` or were edited). |

Use `all` when the host persists every group in one go (for example with no `save-url`).

### JavaScript

The script class is `PermMatrix` (selector `[data-dl-perm]`, file `resources/assets/js/components/perm-matrix.js`); the instance is `el.dlComponent`. There is no extra `el.dlXxx` API. Useful members: `dirtyCount()`, `shown()`, `draw()`, `persist(group, perms)` (override to change how it is stored).

## Rules and pitfalls

- Level values in `perms` must be integers within `0 .. levels-1`. A numeric string (`"4"`, which database drivers often return) or an out-of-range index selects no option, so the browser displays the first option ("No access") for that row while the stored value is something else. Cast with `(int)` before passing.
- `perms` keys are the group names exactly as in `groups` and the object `key`s exactly as in `objects`. Names that do not match fall back to `default`.
- `groups` is a translated list: each name is passed through the dictionary. If the dictionary has a translation for a group name in the current language (for example "Administrator"), the select shows and posts the translated text and the `perms` lookup by the original name no longer matches. Keep group names to values that are not dictionary entries, or map the posted `group` text back to your group record on the server, and test the screen in every display language you offer.
- `default` is a safety decision. Remember that a missing row means `default`; for a new object in an existing system the group gets `default` until someone saves.
- Do not use the matrix as the only enforcement. Authorize on the server for every request; the matrix only records intent.
- The matrix has no `name` attributes and posts nothing by itself, so it must not be relied upon inside a normal `<form>` submit. It also does not take part in the dirty-guard (`data-dl-guard`).
- Level styling is positional: only index 0 (muted) and index 4 (green) are styled. If you change `levels` to a list with a different length, the green style may land on a level that is not the highest.
- Keep the object list reasonable: all rows of the selected module are rebuilt on each keystroke, and the scroll area is 460px high.

### Notes on inconsistencies found while writing this entry (not fixed)

- With `save-url`, only the selected group is posted, but on success the script marks the pending changes of ALL groups as saved (`saved[g] = {...saved[g], ...work[g]}`). If the user edits group A, switches to group B, edits it and presses Save, group A's edits are shown as saved (the dirty counter drops to 0) although they were never sent. The host should either save one group at a time (Save before switching), or use `detail.all` from `dl:permissions-save` and persist every group there.
- A `fetch` network failure is not caught in `persist()`; there is no feedback for it (see the save contract).
- A group present in `perms` with an empty PHP array (`'Sales' => []`) is encoded as a JSON array `[]`, not an object. Reads work, but the first edit adds a string-keyed property to that array, which `JSON.stringify` drops from `detail.all`. Omit empty groups or pass `(object) []`.
- `Guide::entries()` looks for the script at `resources/assets/js/components/permission-matrix.js`, but the file is named `perm-matrix.js` (class `PermMatrix`), so the catalog shows no JS file for this object.
- The default `source()` text, `sec_group_app (grup x objek sys_app_menu)`, names tables of an application; it is a catalog label only, but it is not neutral wording. Pass your own `source`.
- The toolbar uses the CSS class `dl-print__spacer` (defined in the print section of `devlib.css`) as a flex spacer.

## Example

Runnable with fictional data (adapted from the catalog demo `site/resources/views/demos/security.blade.php`; the demo does not set `save-url`, the line below shows how to):

```blade
@php
    $objects = [
        ['key' => 'inq', 'name' => 'Sales Inquiry', 'type' => 'Form', 'module' => 'Sales & Inquiry'],
        ['key' => 'inq-l', 'name' => 'Inquiry List', 'type' => 'Grid', 'module' => 'Sales & Inquiry'],
        ['key' => 'cust', 'name' => 'Customer Master', 'type' => 'Form', 'module' => 'Master Data'],
        ['key' => 'po', 'name' => 'Purchase Order', 'type' => 'Form', 'module' => 'Procurement'],
        ['key' => 'po-l', 'name' => 'PO List', 'type' => 'Grid', 'module' => 'Procurement'],
    ];
@endphp
<x-dl::permission-matrix
    :objects="$objects"
    :groups="['Administrator', 'Sales', 'Warehouse']"
    :perms="[
        'Administrator' => array_fill_keys(array_column($objects, 'key'), 4),
        'Sales' => ['inq' => 2, 'inq-l' => 1, 'po' => 0],
    ]"
    :default="1"
    :save-url="url('/security/permissions')" />

<script>
    document.addEventListener('dl:permissions-save', e => console.log(e.detail.group, e.detail.perms));
</script>
```

## Related

- `x-dl::tabs`: the Setup & Security screen puts this matrix on one tab next to users, groups and objects.
- `x-dl::grid`: lists of users, groups and objects that feed the matrix.
- `x-dl::field.toggle`: single on/off right.
- `x-dl::pick-matrix`: a different matrix (one radio winner per row).
- `Devlib\Access` and the `can="module.action"` attribute: where the stored rights are enforced.
