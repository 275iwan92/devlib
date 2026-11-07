## Overview

`<x-dl::world-clock>` shows the time in other countries: one card per city with the running time, the date, a sun or moon icon and the difference from the user's own device time ("3 h ahead"). A drop-down adds a city from a list of time zones; a cross removes it.

Pick `x-dl::clock` for the system clock of the application, or `x-dl::calendar` for dated events.

### Generated markup

```html
<div class="dl-wclock" data-dl-wclock data-save="/tools/clocks" data-csrf="..." data-items='[{"id":"c1","tz":"Asia/Tokyo","label":"Tokyo"}]'>
  <div class="dl-wclock__cards">city cards: icon, name, time, date, difference, remove</div>
  <form class="dl-wclock__add">city drop-down + "Add city"</form>
</div>
```

## Props

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `items` | array | `[]` | Cities: `['id', 'tz' => IANA zone name such as Asia/Tokyo, 'label' => city name]`. |
| `zones` | array | big cities | Choices of the drop-down: `[tz => city name]`; empty = the built-in list of about 40 cities and UTC. |
| `save-url` | string | empty | POST address; the body is JSON `{items: [...]}` with the whole list. Empty = preview without a server. |
| `csrf` | string | empty | Value for the `X-CSRF-TOKEN` header. |

## Behavior

- Time, date and difference are computed in the browser with `Intl.DateTimeFormat`, so daylight saving follows the browser rules; the server only stores the list.
- The cards refresh once a second.

## Data and events

POST `save-url` with `{items}`; the server checks that each `tz` is a real time zone and replaces the stored list. No custom event.

## Rules and pitfalls

- The server must validate the zone name (`timezone` rule) and the list size.
- The difference compares with the device's own zone, not the server's.

## Example

```blade
<x-dl::world-clock :items="$cities" save-url="/v2/tools/clocks" :csrf="csrf_token()" />
```

## Related

`x-dl::clock`, `x-dl::calendar`, `x-dl::alarm-list`.
