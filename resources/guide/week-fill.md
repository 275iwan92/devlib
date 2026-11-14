## Overview

`<x-dl::week-fill>` is a "fill once" bar for a weekly hours pattern. The user picks the days with round chips (Mon to Sun, plus the presets Weekdays, All and Clear), fills four times once (clock in, break start, break end, clock out) in the fields the screen puts into the slot, and presses Apply: the four values are copied into the per-day fields of every chosen day in the same form. A line under the chips shows the net hours per day and per week. The per-day fields stay editable by hand.

Use it when a form has seven day rows with the same four times and most days share the same hours (shift master, office hours, machine hours). Do NOT use it for a single time range (`x-dl::field.time`), for a grid of dated entries (`x-dl::calendar`), or for rows that differ completely every day.

### Markup

```html
<div class="dl-weekfill" data-dl-weekfill data-config="{...json...}">
  <div class="dl-weekfill__days" role="group">
    <button type="button" class="dl-weekfill__chip is-on" data-wf-day="Monday" aria-pressed="true">Mon</button> ...
    <span class="dl-weekfill__presets"><button type="button" data-wf-preset="weekdays">Weekdays</button> · <button data-wf-preset="all">All</button> · <button data-wf-preset="clear">Clear</button></span>
  </div>
  <div class="dl-weekfill__fields">
    ... four time fields from the slot (names qf_start, qf_bout, qf_bin, qf_end) ...
    <button type="button" class="dl-btn dl-btn--primary" data-wf-apply>Apply to selected days</button>
  </div>
  <p class="dl-weekfill__net" data-wf-net></p>
</div>
```

The chips, the Apply behavior and the summary are driven by `data-config` (JSON of `map`, `days`, `selected` and the texts). Extra HTML attributes and classes are merged into the root; `can="module.action"` hides the object without the ability.

## Props

Constructor of `Devlib\Components\WeekFill` (4 props). Use `:prop="expr"` for arrays.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `map` | `array`, `[]` | Which source field goes to which target field, per role: `['start' => ['from' => 'qf_start', 'to' => 'start_{day}'], 'bout' => ..., 'bin' => ..., 'end' => ...]`. `from` is the `name` of a field inside the slot, `to` is a field name pattern where `{day}` is replaced by the day key (for example `Monday`). The roles are fixed: `start` clock in, `bout` break start, `bin` break end, `end` clock out. A role without a target field in the form is skipped. |
| `days` | `array`, Mon..Sun | `[day key => chip label]`, by default the seven English day names with three-letter labels. The key is what replaces `{day}` in `map`. |
| `selected` | `array`, Mon..Fri | The day keys that are chosen when the page opens. |
| `apply-label` | `string`, `'Apply to selected days'` | Text of the Apply button (translated; prop `applyLabel` in PHP). |

## Behavior

- **Chips**: a click toggles a day (dark = chosen). The presets Weekdays (Mon to Fri), All and Clear set the whole selection.
- **Apply**: for every chosen day the four values of the slot fields are written into the target fields (an empty source empties the target, so empty clock in and clock out turn a day into a non-operating day). The input and change events are fired so time widgets redraw, and the form is marked unsaved. Without a chosen day nothing is written and the line says so.
- **Summary**: from the slot fields: net per day = (clock out - clock in) - (break end - break start), a time that ends at or before it starts means past midnight; per week = per day times the number of chosen days. It updates while the user types.
- **Not covered**: it does not validate the times (the server does) and it does not fill the break when only one of the two break times is given.

## Data and events

### What the server receives

Nothing of its own: the object posts the slot fields (for example `qf_start`) like any field, and the per-day target fields it filled (`start_Monday` and so on). The server should ignore the `qf_*` helper fields and validate the per-day fields as always.

### Events and JS

There is no instance property and no custom event: the behavior lives in the click handlers on the root. After Apply it fires `input` and `change` on every changed target field (bubbling), which the DevLib dirty guard and the time widgets already follow.

## Rules and pitfalls

- The slot fields and the target fields must be in the SAME `<form>`; the lookup is by `name` inside that form.
- Name the helper fields with a prefix that the server does not read (for example `qf_`), otherwise they may be taken as real data.
- `{day}` is replaced by the key from `days`, so the per-day field names must use the same keys (English day names by default).
- Apply overwrites what is typed in the chosen days; days that are not chosen are left alone.
- The summary only knows the slot fields, not the per-day fields; after hand edits of single days it can differ from the saved total.

## Example

```blade
<x-dl::week-fill :map="['start' => ['from' => 'qf_start', 'to' => 'start_{day}'], 'bout' => ['from' => 'qf_bout', 'to' => 'bout_{day}'], 'bin' => ['from' => 'qf_bin', 'to' => 'bin_{day}'], 'end' => ['from' => 'qf_end', 'to' => 'end_{day}']]">
    <x-dl::field.time name="qf_start" label="Clock in" />
    <x-dl::field.time name="qf_bout" label="Break start" />
    <x-dl::field.time name="qf_bin" label="Break end" />
    <x-dl::field.time name="qf_end" label="Clock out" />
</x-dl::week-fill>
```

## Related

- `x-dl::field.time`: the time field used for the four helper fields and the per-day fields.
- `x-dl::form.row`: the row that lays out the per-day fields.
- `x-dl::calendar`: dated entries on a calendar.
