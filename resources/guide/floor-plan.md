## Overview

`<x-dl::floor-plan>` draws a plan of a floor or an area with round numbered pins on it. The plan is either a schematic made from room rectangles (each labelled "CODE Name" at its top-left corner) or an uploaded plan image used as the background. Each pin has a status color (`success`, `warn`, `danger`, `neutral`). A person clicks, taps, or tabs to a pin and selects it; the screen shows a detail card for the selected pin below the plan. A legend row explains the colors. A summary slot above the plan holds the counts.

Use it for any "objects on a plan" screen: air conditioners and their service status, fire extinguishers, machines, desks, sockets. Pick another object when:

- the thing drawn is a warehouse with zones and bins and a side panel: `x-dl::wh-map`;
- you need a chart of values, not places: `x-dl::chart`;
- you only need a status word: `x-dl::badge`.

### Generated markup

```html
<div class="dl-floorplan" data-dl="x-dl::floor-plan" data-dl-js="FloorPlan" data-dl-floorplan data-selected="u2">
  <div class="dl-floorplan__summary">…summary slot…</div>             <!-- only with the summary slot -->
  <div class="dl-floorplan__plan" style="aspect-ratio: 16 / 9" role="group" aria-label="Floor 1 plan">
    <div class="dl-floorplan__room" style="left: 3%; top: 4%; width: 28%; height: 46%"><b>R1</b> Reception</div>   <!-- or <img class="dl-floorplan__img"> with image -->
    <button type="button" class="dl-floorplan__pin is-warn is-selected" style="left: 52%; top: 24%"
            title="Unit 02 · Due soon" aria-label="Unit 02, Due soon" data-pin="u2" aria-pressed="true">02</button>
  </div>
  <ul class="dl-floorplan__legend"><li><i class="dl-floorplan__key is-success"></i>Safe</li>…</ul>   <!-- only with legend -->
  <input type="hidden" name="unit" value="u2" data-floorplan-input>   <!-- only with name -->
  <div class="dl-floorplan__detail">…detail slot…</div>                <!-- only with the detail slot -->
</div>
```

## Props

Constructor of `Devlib\Components\FloorPlan` (9 props, none inherited). Blade names are kebab-case. All positions and sizes are percentages of the plan box (0-100).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `rooms` | `array`, `[]` | Room rectangles `[['code' => 'R1', 'label' => 'Meeting', 'x' => 4, 'y' => 6, 'w' => 40, 'h' => 38], ...]`, drawn as bordered boxes with "CODE Name" at the top-left. Ignored when `image` is set. Data, not translated: wrap database values with `raw()` if you build them from a dictionary. |
| `pins` | `array`, `[]` | Pins `[['id' => 'u1', 'x' => 20, 'y' => 30, 'tone' => 'success', 'label' => 'Unit 01', 'status' => 'Safe', 'number' => '01', 'title' => 'tooltip', 'href' => '/plan?pin=u1'], ...]`. `x`,`y` = center of the pin in percent. `tone`: `success`, `warn`, `danger`, `neutral` (anything else becomes `neutral`). `number`: text inside the dot (default: the position in the list, from 1). `label` + `status` build the `aria-label` ("Unit 01, Safe") and the tooltip ("Unit 01 · Safe"); `title` overrides the tooltip only. Without `status`, the legend word of the tone is used (Safe / Due soon / Overdue; none for `neutral`). With `href` the pin is a link (works without JavaScript). |
| `image` | `?string`, `null` | URL of an uploaded plan image used as the background (stretched to the box, so give the matching `aspect`). When set, `rooms` are not drawn; pins stay on top. |
| `aspect` | `string`, `'16:9'` | Width:height of the plan box (`'16:9'`, `'4/3'`, `'1920x1080'`). Anything else falls back to `16 / 9`. The box is `width: 100%`, so it shrinks on a phone and keeps its ratio. |
| `selected` | `?string`, `null` | `id` of the selected pin. Marked `is-selected` (ring), `aria-pressed="true"` on buttons or `aria-current="true"` on links. |
| `legend` | `array\|bool`, `false` | `true` = default legend (Safe, Due soon, Overdue; translated). An array `[['tone' => 'success', 'label' => 'OK'], ...]` = your own rows (labels are shown as given, not translated). `false` = no legend. |
| `name` | `?string`, `null` | Adds `<input type="hidden" name value="selected">`; the value follows the selection, so a surrounding form (GET or POST) reads the chosen pin. |
| `readonly` | `bool`, `false` | Pins without `href` become non-interactive `<span role="img">` (no click, no focus). Links keep working. |
| `label` | `?string`, `null` | Name of the plan (`aria-label` of the plan box and `alt` of the image). Translated through the dictionary; wrap database values in `raw()`. |

Slots: `summary` (above the plan, usually `x-dl::kpi-row`) and `detail` (below the legend, the card for the selected pin). Every DevLib component also accepts `can="module.action"` and extra HTML attributes merged on the root `div.dl-floorplan`.

## Behavior

- **Select.** Clicking, tapping, or pressing Enter/Space on a pin button sets `is-selected` on it, updates `aria-pressed`, writes the hidden input, sets `data-selected` on the root, and emits `dl:floorplan-select` (bubbles) with `detail.id`. Tab moves between pins in list order. Selecting does not deselect.
- **Detail cards.** Any element inside the root with `data-floorplan-detail="<id>"` (for example `<x-dl::box data-floorplan-detail="u1">`) is shown only while that pin is selected; the others get `hidden`. This lets a screen put all cards in the `detail` slot and switch without a server round trip. Without JavaScript all cards stay visible, so for no-JS screens render only the selected card.
- **Links.** A pin with `href` is an `<a>`; JavaScript does not intercept it, the page navigates and the server renders the new selection (`selected` + card). Use this when the card needs data you do not want to load for every pin.
- **Size.** Pins are 24 px circles (number 10 px). The touch area is extended to 32 px, and to 44 px in the phone context (`body.dl-on-phone` / `.dl-phone`). The plan scales with the container width; at 360 px there is no horizontal page scroll. Room text is 11 px (12 px in the phone context) and is clipped inside its rectangle.
- **Colors.** Only DevLib tokens: `--dl-success`, `--dl-amber`, `--dl-danger`, `--dl-muted` (neutral), `--dl-white` (pin number), `--dl-surface`, `--dl-border`, `--dl-accent`, `--dl-ink-strong`; light, dark and black themes follow the tokens.

### Accessibility as implemented

The plan box is `role="group"` with the `label`; every pin carries an `aria-label` that includes the status as text, so color is never the only carrier. Buttons expose `aria-pressed`, links `aria-current`. Focus shows a ring. The legend repeats the status words as text.

## Data and events

- Input: props and the two slots. `source()` is "rooms, pins (props), selection posted by hidden input".
- Output: hidden input `name` (when set); DOM event `dl:floorplan-select` with `{id}`; JS class `FloorPlan` (`data-dl-js`).
- Markup hooks: `.dl-floorplan`, `__summary`, `__plan`, `__room`, `__img`, `__pin` (`is-success|warn|danger|neutral`, `is-selected`), `__legend`, `__key`, `__detail`.

## Rules and pitfalls

- Give the pin `id` a stable value (a database key). Without `id` the list index is used, which breaks when the list changes.
- With `image`, set `aspect` to the image's real ratio, or the image is stretched.
- `x`/`y` are the pin center in percent of the plan box, not pixels.
- Do not use `readonly` together with `name` expecting a value to change: a readonly plan never changes the selection.
- A pin with a tone outside the four names is shown `neutral` without error.
- Legend rows given as an array are not translated; pass already translated text.
- Keep one `selected` pin only; there is no multi-select.

## Example

Schematic plan, three statuses, detail cards switched by JavaScript (fictional data):

```blade
@php
    $pins = [
        ['id' => 'u1', 'x' => 18, 'y' => 36, 'tone' => 'success', 'label' => 'Unit 01 · Reception', 'number' => '01'],
        ['id' => 'u2', 'x' => 52, 'y' => 24, 'tone' => 'warn', 'label' => 'Unit 02 · Meeting room', 'number' => '02'],
        ['id' => 'u3', 'x' => 66, 'y' => 40, 'tone' => 'danger', 'label' => 'Unit 03 · Meeting room', 'number' => '03'],
    ];
    $rooms = [
        ['code' => 'R1', 'label' => 'Reception', 'x' => 3, 'y' => 4, 'w' => 28, 'h' => 46],
        ['code' => 'R2', 'label' => 'Meeting room', 'x' => 34, 'y' => 4, 'w' => 40, 'h' => 46],
    ];
@endphp
<x-dl::floor-plan label="Floor 1 plan" :rooms="$rooms" :pins="$pins" selected="u2" name="unit" :legend="true">
    <x-slot:summary>
        <x-dl::kpi-row>
            <x-dl::stat-box label="Units" value="3" tone="accent" />
            <x-dl::stat-box label="Overdue" value="1" tone="danger" />
        </x-dl::kpi-row>
    </x-slot:summary>
    <x-slot:detail>
        @foreach ($pins as $p)
            <x-dl::box data-floorplan-detail="{{ $p['id'] }}">{{ $p['label'] }}</x-dl::box>
        @endforeach
    </x-slot:detail>
</x-dl::floor-plan>
```

Uploaded plan image, pins as links, card rendered by the server:

```blade
<x-dl::floor-plan label="Floor 2" :image="route('plans.image', $floor)" aspect="16:9" :legend="true" :selected="request('unit')"
    :pins="$units->map(fn ($u) => ['id' => (string) $u->id, 'x' => $u->x, 'y' => $u->y, 'tone' => $u->tone, 'label' => raw($u->label),
        'number' => $u->no, 'href' => route('plans.show', ['floor' => $floor->id, 'unit' => $u->id])])->all()">
    @if ($unit)<x-slot:detail>…card for $unit…</x-slot:detail>@endif
</x-dl::floor-plan>
```

## Related

- `x-dl::wh-map`: warehouse floor map with zones, bins and a side panel.
- `x-dl::stat-box` and `x-dl::kpi-row`: the summary boxes above the plan.
- `x-dl::badge`: status word in the detail card.
- `skills/devlib/components.md`: generated prop list of every object.
