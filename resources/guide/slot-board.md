## Overview

`<x-dl::slot-board>` is a one-screen board for placing many people into a grid: a pool of people who are not placed yet on the left, and a matrix of rows by columns on the right (for example shifts by work units or machines). A person is a round token with initials. Tokens move by dragging, or by ticking several tokens and clicking a slot, or by double-clicking a placed token to send it back. The pool and every slot show counters, rows show whether they have enough people, and a locked board becomes read-only. The placements are posted as hidden inputs with the form, and the unsaved-changes guard and Undo of the form know about the object.

Use it when a form places a large group of people (tens to a hundred) into slots every week and speed matters more than detail: a weekly shift roster, a crew plan, a seating plan. Do NOT use it for one box of chosen items with per-row selects (`x-dl::transfer`), for a short capped list of person + role (`x-dl::member-list`), for cards that move between workflow stages (`x-dl::kanban`), or for a calendar of dated entries (`x-dl::calendar`).

### Markup

```html
<div class="dl-slots" data-dl-slots data-locked="0" data-config="{...json...}">
  <aside class="dl-slots__pool" data-s-pool>
    <header class="dl-slots__head"><b>Not scheduled</b><span class="dl-slots__pill" data-s-count="pool">42</span></header>
    <label class="dl-slots__search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" data-s-search></label>
    <div class="dl-slots__chips" data-s-chips></div>
    <div class="dl-slots__tokens" data-s-tokens><button class="dl-slots__tok" draggable="true" data-s-id="7">AB</button></div>
    <p class="dl-slots__hint" data-s-hint></p>
  </aside>
  <div class="dl-slots__matrix" data-s-matrix>
    <div class="dl-slots__colh"><b>Press A</b><small>target 3,200 pcs/h</small></div>
    <div class="dl-slots__rowh"><b>Shift 1</b><small>05:00-13:15</small></div>
    <div class="dl-slots__cell" data-s-cell="s1|a"><span class="dl-slots__cnt">2 / 3</span><span class="dl-slots__p"><button class="dl-slots__tok" data-s-id="1">AP</button><small>Anna</small></span></div>
  </div>
  <span hidden data-s-hidden><input type="hidden" name="slots[1]" value="s1|a"></span>
</div>
```

The pool, matrix, counters and hidden inputs are drawn by the script from `data-config` (JSON of `name`, `locked`, `rowTitle`, `rows`, `cols`, `people`, `placed` and the texts). Extra HTML attributes and classes are merged into the root; `can="module.action"` hides the object without the ability.

## Props

Constructor of `Devlib\Components\SlotBoard` (8 props). Use `:prop="expr"` for arrays and booleans.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `name` | `string`, `'slots'` | Prefix of the posted names: `<name>[<person id>]` with the value `"rowKey|colKey"`. Only placed people are posted. |
| `rows` | `array`, `[]` | Rows of the matrix, for example shifts: `[['key' => 's1', 'label' => 'Shift 1', 'sub' => '05:00-13:15', 'need' => 4]]`. `need` is the number of people each slot of that row should have (0 = no counting). The row header shows the total and a tag `enough` or `short n` (the need is counted over the slots that are in use in that row). |
| `cols` | `array`, `[]` | Columns of the matrix, for example work units or machines: `[['key' => 'a', 'label' => 'Press A', 'sub' => 'target 3,200 pcs/h']]`. The matrix scrolls sideways only when the columns do not fit. |
| `people` | `array`, `[]` | Everyone that can be placed: `[['id' => 7, 'name' => 'Anna Putri', 'short' => 'Anna', 'code' => 'E-07', 'group' => 'Press', 'flag' => false]]`. `name` and `code` are the tooltip and the search text, `short` the small label under a placed token, `group` makes the filter chips of the pool, `flag` draws a dashed warning ring (for example day labour). |
| `placed` | `array`, `[]` | People already in a slot: `[id => 'rowKey|colKey']`. Everyone not listed is in the pool. They are also the state that Undo returns to. |
| `locked` | `bool`, `false` | Read-only board: no drag, no tick, no double-click; the hint says it is locked. The server must refuse the save of a locked board too. |
| `pool-title` | `string`, `'Not scheduled'` | Heading of the pool (translated; prop `poolTitle` in PHP). |
| `row-title` | `string`, `'Shift'` | Text in the top-left corner of the matrix (translated; prop `rowTitle` in PHP). |

## Behavior

- **Place**: drag a token from the pool (or from another slot) onto a slot. Tick several tokens (click the round token, it turns into a tick) and click a slot: all ticked people go in at once. The slots show a dashed "drop here" while something is ticked.
- **Unplace**: double-click a placed token, or drag it back onto the pool.
- **Several at once**: dragging a ticked token drags every ticked token.
- **Pool filter**: a search field (name or code) and one chip per group; the counter always shows everyone who is not placed.
- **Counters**: each slot with a `need` shows `n / need` (red when it has people but fewer than `need`); the row header shows the total and `enough` or `short n`.
- **Posted data**: every change rebuilds the hidden inputs, marks the form unsaved and fires `dl:slot-change`.
- **Undo**: the Undo of the form calls `reset()` and the opening state comes back.
- **Not covered**: sorting inside a slot, per-person job or time, and drag on touch screens (use tick plus click on the slot).

## Data and events

### What the server receives

One field per placed person: `<name>[<id>]=<rowKey>|<colKey>`, for example `slots[7]=s1|a`. People in the pool are absent, so the server treats absence as "unplaced". Keys are the `key` values of `rows` and `cols`; the server must check that they exist and that the person may be placed there.

### Events and JS

The instance is `el.dlSlots` with `reset()` (called by the Undo of the form) and `place(ids, cell)` (cell `null` = unplace). The event `dl:slot-change` bubbles from the root with `detail.placed` (a copy of the map id to cell). The drag uses the native `dragstart`, `dragover`, `drop` and `dragend` events.

## Rules and pitfalls

- The object only helps to place. The server MUST validate again: unknown people or slots, a person placed twice, quotas, qualification, and the lock.
- A person in `placed` whose id is not in `people` is drawn with the raw id and cannot be searched.
- `need` is per slot and is counted over the slots in use of the row; a row with `need` 0 never shows `enough` or `short`.
- `locked` must be set from the same state the server checks, otherwise the screen allows what the server refuses.
- Touch screens cannot drag; tick plus click on the slot is the way there.
- With many columns the matrix scrolls sideways; keep the columns to the units that are really used.

## Example

```blade
<x-dl::slot-board name="slots" :rows="$shifts" :cols="$units" :people="$people" :placed="$placed" :locked="$locked" />
```

## Related

- `x-dl::transfer`: two boxes (not chosen, chosen) with per-row selects.
- `x-dl::member-list`: a short capped list of person + role.
- `x-dl::kanban`: cards that move between workflow stages.
- `x-dl::calendar`: dated entries on a calendar.
