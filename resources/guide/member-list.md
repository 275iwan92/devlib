## Overview

`<x-dl::member-list>` is a bounded list of people inside a form: each row has a running number, the name, a role selector and a trash button (with confirmation). Under the rows a select box plus an Add button adds the next person; a header shows a counter `n of N filled` and a bar. The chosen rows are posted as hidden inputs together with the form, and the form's unsaved-changes guard and Undo know about the list.

Use it when a form owns a short, capped list of "person + role" rows chosen from a known set (a crew, a team, a committee), for example the people of a work shift. Do NOT use it for open-ended detail lines with quantities and prices (`x-dl::lines`), for a plain multiple choice without roles (`x-dl::field.multi-select` or `x-dl::field.tags`), or for a list that must be searched on the server (`x-dl::field.lookup`, `x-dl::lookup-grid`).

### Markup

```html
<div class="dl-members" data-dl-members data-config="{...json...}">
  <div class="dl-members__head"><b class="dl-members__title">Title</b><span class="dl-badge dl-badge--pill dl-badge--neutral" data-m-count>2 of 10 filled</span></div>
  <div class="dl-members__bar"><i data-m-bar style="width:20%"></i></div>
  <div class="dl-members__rows" data-m-rows>
    <div class="dl-members__row"><span class="dl-members__no">1</span><span class="dl-members__who">Name</span><select class="dl-members__role" data-m-role="0">...</select><button class="dl-iconbtn dl-iconbtn--danger" data-m-rm="0" data-dl-confirm="...">trash</button></div>
  </div>
  <div class="dl-members__add"><select class="dl-members__pick" data-m-pick></select><button type="button" class="dl-btn dl-btn--plain dl-btn--sm" data-m-add>+ Add</button></div>
  <span hidden data-m-field><input type="hidden" name="members[0][employee_id]" value="27"><input type="hidden" name="members[0][role]" value="Helper"></span>
</div>
```

The rows, counter, bar and hidden inputs are drawn by the script from `data-config` (JSON of `name`, `rows`, `options`, `roles`, `max` and the texts). Extra HTML attributes and classes are merged into the root; `can="module.action"` hides the object without the ability.

## Props

Constructor of `Devlib\Components\MemberList` (6 props). Use `:prop="expr"` for arrays and numbers.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `name` | `string`, `'members'` | Prefix of the posted names: `<name>[i][employee_id]` and `<name>[i][role]`, with `i` from 0 and without gaps (the list is re-indexed on every change). Also written to the config. |
| `rows` | `array`, `[]` | The rows that exist at load: `[['id' => '27', 'role' => 'Helper'], ...]` (keys `id` and `role`; the array is re-indexed with `array_values`). They are also the state that Undo returns to. More rows than `max` are kept (the list then gets `is-over` and the bar turns to the danger colour), so the server must enforce the limit. |
| `options` | `array`, `[]` | The people that can be added: `[id => label]`, for example `['27' => 'EMP-0027 · Anna Putri']`. The label is what the row and the select box show; a row whose `id` is not in `options` shows the raw `id` and cannot be added again after it is removed. Labels are data and are not translated. The select box lists the people that are not yet in the list. |
| `roles` | `array`, `[]` | Role choices of the per-row selector, as plain strings, in order: `['Main operator', 'Co-operator', 'Helper']`. A person who is added gets the SECOND role (`roles[1]`); with a single role that one; with none an empty string. Not translated. |
| `max` | `int`, `10` | Maximum number of rows. At the limit the select box is disabled and shows the "full" text, and Add does nothing. Must be at least 1 (0 gives an invalid bar width). |
| `title` | `string`, `''` | Heading in the header (upper-case via CSS). Translated (twice: as a standard text prop and again in the view, which is harmless). Empty prints an empty heading. The heading is also the link to the Technical Guide when the guide link is on. |

## Behavior

### Adding, changing, removing

- The select box has the placeholder `Choose employee...` and then every person from `options` who is not in the list. Add stays disabled until a person is chosen. A click on Add appends the person with the default role, redraws the list (the select resets) and marks the form unsaved.
- The same person cannot be added twice (the id is compared as a string).
- The role selector of a row changes the role of that row and marks the form unsaved. If a `role` in `rows` is not in `roles`, the selector displays the first role while the posted value stays the original one until the user changes it.
- The trash button opens a confirmation (`Remove this person from the list?`, warning tone, via `data-dl-confirm`); only confirming removes the row. Rows below move up and renumber.
- Full list: the counter shows `N of N filled`, the select shows `Crew is full (N of N). Make a new crew for the next people.` and is disabled.

### Counter, bar, layout

Header badge text is `:n of :max filled`; the bar width is `n / max` (at most 100%). The row is a grid of four columns (number, name, role, trash); from 560px width down the role selector moves to its own line under the name. Order of the rows is the order of adding; there is no sorting or drag. The people in the select box appear in the order of the browser's JSON object: keys that are integers (`'27'`, `'104'`) come in ascending numeric order, other keys in the given order.

### Undo and dirty state

- Adds, removals and role changes mark the surrounding form as unsaved (`is-dirty`, the browser leave warning). This works when the object is inside a form with the dirty guard (`x-dl::form` has `guard` on by default); outside such a form nothing is marked.
- `Devlib.fill` / the Undo of `x-dl::form` calls `reset()` on every member list in the form, which restores the rows given in `rows` at render time and redraws them.
- In a locked form (fieldset disabled) the select boxes and buttons are inert and the hidden inputs are not posted.

### Text and language

The texts are built by the PHP class with the dictionary and passed in the config: `:n of :max filled`, `Choose employee...`, `Add`, `Remove`, `Remove this person from the list?`, `Crew is full (:max of :max). Make a new crew for the next people.`, `Role` (the aria-label of the role selectors). None of them is in the built-in dictionary, so they are English unless the application dictionary translates them. The button text is `+ Add`.

### Accessibility as implemented

Native `select` and `button` elements; the role selector has `aria-label="Role"` and the trash button has `title` and `aria-label` (`Remove`). The counter is not a live region and the rows have no list roles. The row number, name and role are in one grid row.

## Data and events

### What the server receives

| Case | Posted |
|---|---|
| Two people | `members[0][employee_id]`, `members[0][role]`, `members[1][employee_id]`, `members[1][role]` (strings, the ids as given in `rows` / `options`). |
| Empty list | nothing at all for this name, so the server cannot tell "no members" from "field not sent". Send a marker (a `hidden` field) if an empty list must clear the stored rows. |
| The select box and the role selectors | never posted (they have no `name`); only the hidden inputs are. |
| `can` denies | the object is not rendered, nothing is posted. |

The hidden inputs sit in `<span hidden data-m-field>` elements and are rebuilt after every change. The key `employee_id` is fixed in the script; the `name` prop only changes the prefix.

### Events and JS

- No `dl:*` event and no native `change` event for the list as a whole (the hidden inputs are re-created without events). The dirty state is set directly.
- `el.dlMembers` (and `el.dlComponent`) on the root is the `MemberList` instance: `rows` (current state as `[{id, role}]`), `initial`, `cfg`, and the methods `reset()`, `draw()`, `sync()` and `markDirty()`.
- Read the current state in a screen with `el.dlMembers.rows`.

| Hook | Where |
|---|---|
| `data-dl-members`, `data-config` | root and its JSON configuration. |
| `data-m-count`, `data-m-bar` | counter badge and bar. |
| `data-m-rows` | container of the rows. |
| `data-m-pick`, `data-m-add` | select box and Add button. |
| `data-m-role="i"`, `data-m-rm="i"` | role selector and trash button of row `i`. |
| `data-m-field` | wrappers of the posted hidden inputs. |

## Rules and pitfalls

- The server must validate everything again: that each `employee_id` exists, that ids are not duplicated, that `role` is one of the allowed roles, and that the number of rows does not exceed `max`. The browser limit is a convenience only.
- Keep `roles` free of the empty string and make the role the user is most likely to need the SECOND entry, because that is the default for a new row (the demo uses `Main operator` first and `Co-operator` second).
- The list cannot be used outside a form for posting; put it in the body of an `x-dl::form` (or any element with `data-dl-guard`) so the unsaved guard and Undo work.
- Keep the labels in `options` short and unique; the select shows nothing else about a person.
- A role stored in the database that is no longer in `roles` is displayed as the first role but posted unchanged: keep `roles` in sync with the stored data.
- Lint: no own rule. L001 applies to the Blade that uses the object (no hand-written `<select>`, `<button>`, `<input>`, `<form>`), L002 to inline `<script>`, L003 to hex colours.
- Code oddity (not fixed): the posted key `employee_id` and the texts `Choose employee...` and `Crew is full (:max of :max). Make a new crew for the next people.` (`src/Components/MemberList.php`, `resources/assets/js/components/member-list.js`) are domain-specific wording inside a generic object; a list of other kinds of people or things posts `employee_id` anyway and shows the crew sentence.
- Code oddity (not fixed): a new row gets `roles[1]` (second role) as its default, not the first, in `member-list.js` (`this.cfg.roles[1] || this.cfg.roles[0]`); the REMARK header of the class does not say so.
- Code oddity (not fixed): `options` is passed as a JSON object, and JavaScript orders integer-like keys ascending, so the order of the select box does not follow the order of the PHP array when the ids are numeric.
- Code oddity (not fixed): the demo note in `site/resources/views/demos/member-list.blade.php` mentions a cross/trash icon, but the button only has the trash icon; and the demo options use real-looking staff names, which should be fictional.
- Code oddity (not fixed): the REMARK header names a project screen as the user of the object, against the neutrality rule for library files.

## Example

Copied in shape from the catalog demo, with fictional people.

```blade
<x-dl::form title="Crew" :actions="['Save' => 'fa-floppy-disk']" style="height:auto">
    <x-dl::member-list name="members" title="Choose user names · maximum 10 rows" :max="10"
        :options="['27' => 'EMP-0027 · Anna Putri', '43' => 'EMP-0043 · Bagas Pratama', '104' => 'EMP-0104 · Citra Lestari', '115' => 'EMP-0115 · Dimas Wibowo']"
        :roles="['Main operator', 'Co-operator', 'Helper', 'Supervisor']"
        :rows="[['id' => '27', 'role' => 'Main operator'], ['id' => '43', 'role' => 'Helper']]" />
</x-dl::form>
```

Reading the posted value in Laravel:

```php
$members = collect($request->input('members', []))->map(fn ($m) => [
    'employee_id' => (int) $m['employee_id'],
    'role' => $m['role'],
]);
```

## Related

- `x-dl::form`: the form that holds the list, posts it, and calls `reset()` on Undo.
- `x-dl::lines`: editable detail rows (quantities, prices) when the rows are not "person + role".
- `x-dl::field.multi-select`, `x-dl::field.tags`: plain multiple choice without roles.
- `x-dl::field.lookup`: searching a large set of people.
- `x-dl::badge`, `x-dl::icon-button`: the counter badge style and the trash button style used by the list.
- `skills/devlib/components.md`: generated prop list.
