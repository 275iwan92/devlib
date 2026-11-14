## Overview

`<x-dl::hour-bars>` draws one bar per day on a 24-hour axis (Mon to Sun) for a weekly hours pattern. The user drags in an empty area to create the working hours of a day, drags the bar to move it, drags an end of the bar to change the clock in or clock out, and clicks a day name to set that day off. The break is drawn as a striped strip at the bottom of the bar and the net hours of the day stand at the right. The bars do not store anything themselves: they read and write the per-day time fields that the screen keeps in the same form, so the fields stay the source that is saved.

Use it on top of seven day rows (clock in, break start, break end, clock out) when people should see and shape the week at a glance: shift master, office hours, machine hours. Do NOT use it for dated schedules (`x-dl::gantt`, `x-dl::calendar`), for one time range (`x-dl::field.time`), or without the per-day fields.

### Markup

```html
<div class="dl-hbars" data-dl-hbars data-config="{...json...}" style="--hb-n:24">
  <div class="dl-hbars__axis" data-hb-axis></div>
  <div class="dl-hbars__row" data-hb-row="Monday">
    <button type="button" class="dl-hbars__day" data-hb-off="Monday">Mon</button>
    <div class="dl-hbars__track" data-hb-track="Monday">
      <div class="dl-hbars__bar" data-hb-bar="Monday" style="left:29%;width:35%"><b class="dl-hbars__h dl-hbars__h--l" data-hb-h="l"></b><span>07:00 - 15:30</span><b class="dl-hbars__h dl-hbars__h--r" data-hb-h="r"></b></div>
      <div class="dl-hbars__brk" style="left:50%;width:4%"></div>
    </div>
    <span class="dl-hbars__net" data-hb-net="Monday">8.00 h</span>
  </div>
</div>
```

The axis, the bars and the net labels are drawn by the script from `data-config` (JSON of `fields`, `days`, `from`, `to`, `snap`, `readonly` and the texts). Extra HTML attributes and classes are merged into the root; `can="module.action"` hides the object without the ability.

## Props

Constructor of `Devlib\Components\HourBars` (6 props). Use `:prop="expr"` for arrays, numbers and booleans.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `fields` | `array`, `[]` | Name pattern of the per-day time fields in the same form, by role: `['start' => 'start_{day}', 'bout' => 'bout_{day}', 'bin' => 'bin_{day}', 'end' => 'end_{day}']`. `{day}` is replaced by the day key. The roles are `start` (clock in), `bout` (break start), `bin` (break end) and `end` (clock out). |
| `days` | `array`, Mon..Sun | `[day key => label]` of the rows; the key replaces `{day}` in `fields`. Same default as `x-dl::week-fill`. |
| `from` | `int`, `0` | First hour of the axis. |
| `to` | `int`, `24` | Last hour of the axis. A day that ends later than the axis cannot be drawn. |
| `snap` | `int`, `15` | Minutes to which dragging snaps (at least 1). A drag shorter than one snap step changes nothing. |
| `readonly` | `bool`, `false` | Bars are only drawn: no drag, no click on the day name. |

## Behavior

- **Create**: press in an empty part of a day and drag sideways; on release the clock in and clock out fields of that day are filled and the break is left empty.
- **Move**: drag the bar; the break moves with it.
- **Change an end**: drag the left or right handle of the bar. A break that falls outside the new hours is cleared.
- **Set off**: click the day name: the four fields of the day are emptied (empty clock in and clock out = non-operating day).
- **Redraw**: the bars follow the fields at any time (week-fill, typing, Undo of the form). While dragging only the bar is drawn; the fields are written on release, with the input and change events so time widgets and the dirty guard follow.
- **Past midnight**: a day whose clock out is at or before its clock in is drawn in two hatched pieces and cannot be dragged (a tooltip says to edit the times).
- **Net hours**: (end - start) minus the break, shown at the right of each row (`off` for an empty day).
- **Not covered**: dragging the break itself and keyboard dragging (use the fields).

## Data and events

### What the server receives

Nothing of its own. The bars write the per-day time fields (for example `start_Monday`, `end_Monday`), and the form posts them like any field. The server validates them as always.

### Events and JS

There is no instance property and no custom event. After a drag the script fires `input` and `change` on every field it wrote (bubbling) and marks the form unsaved through the dirty guard of DevLib.

## Rules and pitfalls

- The fields and the bars must be in the SAME `<form>`; the lookup is by `name`.
- The per-day field names must use the same day keys as `days` (English day names by default).
- The axis starts at `from` and ends at `to`; times outside it are clipped, so keep them at 0 to 24 unless the pattern is known to be shorter.
- The object does not validate anything: a break outside the hours or a day with only one of clock in and clock out is the server's to refuse.
- Touch screens can drag (pointer events), but the bars are thin; keep the fields visible for exact times.
- Hand edits of a field redraw the bar of every day; a half-typed time is simply not drawn.

## Example

```blade
<x-dl::hour-bars :fields="['start' => 'start_{day}', 'bout' => 'bout_{day}', 'bin' => 'bin_{day}', 'end' => 'end_{day}']" />
```

## Related

- `x-dl::week-fill`: fill the same per-day fields once for the chosen days.
- `x-dl::field.time`: the time field of each per-day row.
- `x-dl::gantt`: scheduling bars on a resource by time board.
