## Overview

`<x-dl::switcher>` is a labelled dropdown that shows exactly one panel out of several without reloading the page: pick a role in the select and only the matching `x-dl::switch-case` panel below it stays visible (for example role -> job description and KPIs of that role). The dropdown is a display helper only: it has no `name`, so it is never submitted.

Use it when a few alternative blocks share one place and the user chooses by a single list. Do NOT use it for: tabs of a screen (`x-dl::tabs`), a value that must be posted or validated (`x-dl::field.select`), long data-driven lists (use a grid or `x-dl::drilldown`), or collapsible sections that the user opens and closes (`x-dl::accordion`).

Generated markup:

```html
<div class="dl-switcher" data-dl="x-dl::switcher" data-dl-class="Switcher ← DevlibComponent" data-dl-src="—" data-dl-js="Switcher" data-dl-switcher>
  <div class="dl-switcher__bar">
    <label>Role
      <select data-sw-select><option value="a" selected>Maintenance Head</option><option value="b">Project Assistant</option></select>
    </label>
  </div>
  <div class="dl-switcher__body" data-value="a"> ...x-dl::switch-case panels... </div>
</div>
```

Extra HTML attributes (`id`, `class`, `style`, `data-*`) go on the root `div`. `can="module.action"` is supported; without the ability the switcher and its panels are not rendered.

## Props

Constructor of `Devlib\Components\Switcher` (3 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `label` | `string`, `''` | Text above the select, inside the `<label>` that wraps it. Translated through the dictionary (standard text prop). An empty label still renders the (empty) label element and the select. Wrap data values in `raw()`. |
| `options` | `array`, `[]` | Choices as `value => text`. The key becomes the `<option value>` and must equal the `value` of one `x-dl::switch-case`; the text is the visible option. The option texts are NOT translated (they are not in the translated lists), so write them in the display language or wrap dynamic names in `raw()` as needed. Bind with `:options="[...]"`. |
| `value` | `?string`, `null` | The initially selected key. `null` (or omitted) selects the first option (`array_key_first`; an empty `options` gives `''`). The key is compared as a string: `(string) $key === $value`. A value that is not a key selects nothing in the markup and the browser then shows the first option. |

Slot: the body is the default slot; fill it with `x-dl::switch-case` panels (any content is allowed inside the slot, but only elements with `data-sw-case` are shown/hidden).

## Behavior

- Script `Switcher` (`resources/assets/js/components/switcher.js`, selector `[data-dl-switcher]`): on init and on every `change` of `[data-sw-select]` it sets `hidden` on every `[data-sw-case]` inside the root whose `data-sw-case` is not the select's value. Visible panel = the one whose value matches. The CSS rule `.dl-switcher__case[hidden]{display:none}` does the hiding.
- Without JavaScript all panels are visible (the server does not hide anything).
- The first paint after load is corrected by the script immediately (init calls `show` with the selected value); the `value` prop only decides which option is selected.
- A value with no matching case shows no panel at all.
- Styling: the label is a small bold caption above the select (`max-width: 340px`), select uses the standard border and surface colors, `--dl-font-scale` is respected.
- Language: `label` follows the display language at render; panel content is whatever the screen puts there.
- Accessibility as implemented: a real `<label>` wraps the `<select>` (clicking the caption focuses it); keyboard use is the native select (arrows, type-ahead). The panels have no `role="tabpanel"`, `aria-live` or `aria-controls`; hidden panels use the `hidden` attribute, so they are skipped by screen readers.

## Data and events

- Nothing is posted by the select (no `name`). But the inputs inside the hidden panels are still part of the form and ARE submitted (the `hidden` attribute does not disable fields). Do not put required fields in a panel that may stay hidden, or disable/clear them yourself.
- No custom event is emitted. React to the native `change` event of `[data-sw-select]` (it bubbles) if the screen needs to know.
- `el.dlComponent` on the root is the `Switcher` instance with `select` (the `<select>`) and `show(v)` (shows the panel `v`). `data-dl-js="Switcher"` is written on the root, so a project can override it: `Devlib.register(class MySwitcher extends Devlib.classes.Switcher {...})`.
- `data-value` on `.dl-switcher__body` holds the initial value from the server and is not updated when the user changes the select.
- Server-side switch: pass `value` from the request or the record to reopen with the same choice.

## Rules and pitfalls

- Every key in `options` needs one `x-dl::switch-case` with the same `value`; keys and case values are compared as strings.
- Do not rely on the dropdown for posted data, and do not rely on hidden panels to keep their fields out of the submit.
- Do not nest a switcher inside another switcher's panel: the script queries every `[data-sw-case]` below the root, including the ones of the inner switcher, so the outer one hides and shows the inner panels too.
- Option texts are not translated by the component; the `label` is.
- A duplicated `value` among cases shows both panels at once.
- Code oddity (not fixed): `src/Components/Switcher.php:46` writes `data-value` on the body from the initial `value`, but nothing reads or updates it; the live state is only the select's value.
- Code oddity (not fixed): the REMARK of `src/Components/Switcher.php` names `resources/views/v2/screens/hrm-employee-form.blade.php` as a user; that file is not part of this repository (the demo `site/resources/views/demos/switcher.blade.php` is the in-repo usage).

## Example

From the catalog demo (`site/resources/views/demos/switcher.blade.php`):

```blade
<x-dl::switcher label="Role" :options="['a' => 'Maintenance Head', 'b' => 'Project Assistant']">
    <x-dl::switch-case value="a"><x-dl::alert tone="info" message="Job description and KPI of Maintenance Head." /></x-dl::switch-case>
    <x-dl::switch-case value="b"><x-dl::alert tone="info" message="Job description and KPI of Project Assistant." /></x-dl::switch-case>
</x-dl::switcher>
```

Reopening on a chosen option:

```blade
<x-dl::switcher label="Role" :options="$roles" :value="old('role', $current)">
    @foreach ($roles as $key => $name)
        <x-dl::switch-case :value="(string) $key">...</x-dl::switch-case>
    @endforeach
</x-dl::switcher>
```

## Related

- `x-dl::switch-case`: the panel that belongs to one option (required child).
- `x-dl::tabs`, `x-dl::accordion`: other ways to show one block at a time.
- `x-dl::field.select`: a dropdown whose value is posted.
- `x-dl::alert`: the content used in the demo panels.
- `skills/devlib/components.md`: generated prop list.
