## Overview

`<x-dl::transfer>` is a dual list inside a form: two boxes side by side with a column of round buttons between them. The left box lists everything that is NOT chosen yet, the right box lists what IS chosen; both boxes have their own filter field and a counter. People move by selecting rows and using the middle buttons, by double-clicking a row, or by dragging rows to the other box (a drop zone lights up). Rows in the right box carry extra selects (for example Machine and Job). The right-hand rows are posted as hidden inputs together with the form, and the unsaved-changes guard of the form and its Undo know about the object.

Use it when a form assigns members of a known set to something and each assigned member needs a few choices of its own (people to a shift crew with machine and job, items to a group with a role). Do NOT use it for a short capped list of person + role only (`x-dl::member-list`), for a plain multiple choice (`x-dl::field.multi-select`, `x-dl::field.tags`), for document lines with quantities (`x-dl::lines`), or for cards that move between workflow stages (`x-dl::kanban`).

### Markup

```html
<div class="dl-transfer" data-dl-transfer data-config="{...json...}">
  <section class="dl-transfer__box" data-t-box="left">
    <header class="dl-transfer__head"><b class="dl-transfer__title">Not scheduled</b><span class="dl-transfer__pill" data-t-count="left">96</span><span class="dl-transfer__sel" data-t-sel="left" hidden></span></header>
    <label class="dl-transfer__filter"><i class="fa-solid fa-magnifying-glass"></i><input type="search" data-t-filter="left"></label>
    <div class="dl-transfer__list" data-t-list="left" role="listbox">
      <div class="dl-transfer__item" draggable="true" data-t-item="27" data-t-from="left"><span class="dl-transfer__avatar">AS</span><span class="dl-transfer__who"><b>Name</b><small>CODE</small></span></div>
    </div>
    <div class="dl-transfer__drop">Drop here</div>
  </section>
  <div class="dl-transfer__mid"><button class="dl-transfer__act" data-t-act="all-right">all right</button><button class="dl-transfer__act" data-t-act="sel-right">selected right</button><button class="dl-transfer__act" data-t-act="sel-left">selected left</button><button class="dl-transfer__act" data-t-act="all-left">all left</button></div>
  <section class="dl-transfer__box dl-transfer__box--right" data-t-box="right">
    same parts as the left box; rows have the class dl-transfer__item--row and a span dl-transfer__fields with one select data-t-field per field
  </section>
  <span hidden data-t-hidden><input type="hidden" name="crew[0][employee_id]" value="43"><input type="hidden" name="crew[0][machine_id]" value="1"></span>
</div>
```

The lists, counters, state of the buttons and hidden inputs are drawn by the script from `data-config` (JSON of `name`, `idKey`, `max`, `items`, `rows`, `fields` and the texts). Extra HTML attributes and classes are merged into the root; `can="module.action"` hides the object without the ability.

## Props

Constructor of `Devlib\Components\Transfer` (8 props). Use `:prop="expr"` for arrays and numbers.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `name` | `string`, `'rows'` | Prefix of the posted names: `<name>[i][<idKey>]` and `<name>[i][<field key>]`, with `i` from 0 and without gaps (re-indexed on every change). |
| `items` | `array`, `[]` | Everything that can be chosen: `[id => label]`. A label of the form `CODE · Name` is drawn as the name with the code under it, and the avatar shows the initials of the name. The left box shows the items whose id is not in `rows`; the right box shows the label of each row (an id that is not in `items` shows the raw id). Labels are data, not translated. |
| `rows` | `array`, `[]` | The rows that are already in the right box at load, `[['id' => '27', 'machine_id' => '1', 'role' => 'Helper'], ...]`: `id` plus one value per field key. They are also the state that Undo returns to. |
| `fields` | `array`, `[]` | The selects that every right-box row carries: `[['key' => 'machine_id', 'label' => 'Machine', 'options' => [value => label], 'required' => true, 'sticky' => true, 'default' => '']]`. `required` only marks a row with a red border while the value is empty (the server decides). `sticky` makes the people moved next start with the value of the last row (the same machine for a whole crew). `default` is the start value when there is no sticky value. The label is translated. |
| `idKey` | `string`, `'id'` | Name of the posted key that carries the id of the person or item, for example `employee_id`. |
| `leftTitle` | `string`, `'Not scheduled'` | Heading of the left box (upper-case by CSS). Translated. |
| `rightTitle` | `string`, `'Scheduled'` | Heading of the right box. Translated. |
| `max` | `int`, `0` | Maximum rows in the right box; `0` = no limit. At the limit the move-right buttons are disabled and a bulk move stops at the limit. The server must still enforce the limit. |

## Behavior

- **Select**: a click selects or unselects a row (the avatar turns into a tick); Shift+click selects the range from the last clicked row of that box; Space does the same from the keyboard. A box shows `n selected` in its header.
- **Move**: the middle buttons move the selected rows (single arrow) or every row that is currently shown after filtering (double arrow), to the right or to the left. A double click or Enter on a row moves that one row. Dragging a row to the other box moves it, and moves all selected rows of that box when the dragged row is one of them; the target box shows a drop zone while dragging. Moved rows flash briefly. Moving back to the left forgets the field values of the row. Touch screens use the buttons.
- **Filter**: each box has its own filter (case-insensitive, on the label); it never changes the rows, and the counter then shows `shown / total`. Selected rows that the filter hides are unselected.
- **Counters**: the pill of the left box counts the items not in the right box; the pill of the right box counts the right rows.
- **Posted data**: every change rebuilds the hidden inputs and marks the form unsaved. A row with an empty required field is posted with an empty value and drawn with a red border.
- **Undo**: the Undo of the form calls `reset()` and the opening rows come back.
- **Not covered**: sorting inside a box, and drag on touch screens.

## Example

```blade
<x-dl::transfer name="crew" id-key="employee_id" :items="$people" :rows="$crewRows"
    :fields="[
        ['key' => 'machine_id', 'label' => 'Machine', 'options' => $machines, 'required' => true, 'sticky' => true],
        ['key' => 'role', 'label' => 'Job', 'options' => $jobs, 'required' => true, 'default' => 'Co-operator'],
    ]" />
```
