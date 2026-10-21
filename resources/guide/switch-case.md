## Overview

`<x-dl::switch-case>` is one panel of an `x-dl::switcher`. It is a plain `div` that carries its key in `data-sw-case`; the `Switcher` script shows it only while the dropdown's selected value equals that key and sets the `hidden` attribute otherwise. It has no logic of its own and no JavaScript class.

Use it only as a direct child (inside the slot) of `x-dl::switcher`. Outside a switcher nothing hides it, so it behaves like a normal `div`. For tabs use `x-dl::tabs`; for a collapsible block use `x-dl::accordion`.

Generated markup:

```html
<div class="dl-switcher__case" data-dl="x-dl::switch-case" data-dl-class="SwitchCase ← DevlibComponent" data-dl-src="—" data-sw-case="a"> ...slot... </div>
```

Extra HTML attributes (`id`, `class`, `style`, `data-*`) go on this `div`; `class` is merged with `dl-switcher__case`. `can="module.action"` is supported: without the ability the panel is not rendered (its option stays in the dropdown, so choosing it shows nothing).

## Props

Constructor of `Devlib\Components\SwitchCase` (1 prop).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `value` | `string`, `''` | The key of this panel. It must equal one key of the parent switcher's `options` (compared as strings against the select value). Written escaped to `data-sw-case`. An empty value matches only an option whose key is `''`. Bind non-strings as `:value="(string) $id"`. |

Slot: the default slot is the panel content; any Blade or component markup.

## Behavior

- Visible only for the selected option: `Switcher.show(v)` sets `c.hidden = c.dataset.swCase !== v` for every `[data-sw-case]` below the switcher root. The CSS rule `.dl-switcher__case[hidden]{display:none}` hides it.
- Without JavaScript, or outside a switcher, the panel is always visible.
- The panel is rendered by the server in full even while hidden: its content is in the page source, its scripts and components initialise, and its inputs stay part of the form.
- It adds no spacing or border of its own; style the content (a card, an alert, a form row) inside it.
- No text of its own: nothing is translated by this component; the slot content follows the rules of its own components.
- Accessibility as implemented: hidden panels use the `hidden` attribute (not exposed to assistive technology); the visible panel has no `role` or `aria-labelledby`.

## Data and events

- Posts nothing by itself. Fields inside it are submitted even when the panel is hidden (a hidden element is not disabled).
- Emits no event. The parent `x-dl::switcher` root exposes `dlComponent.show(v)`; there is no `el.dlComponent` on the panel (no `jsClass()`).
- Hook for scripts and tests: `[data-sw-case="<value>"]` and its `hidden` property.

## Rules and pitfalls

- One `x-dl::switch-case` per option key, with exactly the same `value`. A key without a panel shows nothing; a panel without a key is never shown (when inside a switcher).
- Two panels with the same `value` are shown together.
- Do not nest a switcher inside a panel of another switcher: the outer script also toggles the inner switcher's panels.
- Do not put required inputs in a panel that may stay hidden; hidden fields are still submitted and counted by form checks.
- The `value` prop is a string: for numeric keys write `value="1"` (or `:value="(string) $n"`), otherwise a loosely typed value may not equal the option key as a string in the browser.
- Code oddity (not fixed): the REMARK of `src/Components/SwitchCase.php` lists `resources/views/v2/screens/hrm-employee-form.blade.php` (tab Job) as a user; this file is not in the repository. The in-repo usage is `site/resources/views/demos/switcher.blade.php`.

## Example

```blade
<x-dl::switcher label="Role" :options="['a' => 'Maintenance Head', 'b' => 'Project Assistant']">
    <x-dl::switch-case value="a"><x-dl::alert tone="info" message="Job description and KPI of Maintenance Head." /></x-dl::switch-case>
    <x-dl::switch-case value="b"><x-dl::alert tone="info" message="Job description and KPI of Project Assistant." /></x-dl::switch-case>
</x-dl::switcher>
```

## Related

- `x-dl::switcher`: the dropdown and container that shows and hides the panels (required parent).
- `x-dl::tabs`, `x-dl::accordion`: other ways to show one block at a time.
- `x-dl::alert`, `x-dl::card`: typical content of a panel.
- `skills/devlib/components.md`: generated prop list.
