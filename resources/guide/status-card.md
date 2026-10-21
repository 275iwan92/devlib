## Overview

`<x-dl::status-card>` is a card that reports the state of one thing (a machine, a document, a service): a round icon, a bold title, a status pill, and optional value, description and "time ago" lines. A colored left border and the pill follow the `tone`. In its plain form it is a `div` that only displays. With `filter` it becomes a `<button>` that toggles on and off like `x-dl::stat-box` (one active card per group) and emits `dl:stat`; something else has to apply the filter.

Use it for a board of live statuses ("Press 1: Running", "Die-cut 2: Broken"). Pick another object when:

- you only need a number tile: `x-dl::stat-box`;
- you need change against the previous period, a sparkline or a target: `x-dl::kpi`;
- you only need a red / amber / green verdict with a sentence: `x-dl::traffic-light`;
- the status belongs to a record in a list (a status column): `x-dl::badge` inside the grid;
- the card must open or drag something (kanban): `x-dl::kanban`.

### Generated markup

```html
<div class="dl-scard dl-scard--warn" data-dl="x-dl::status-card tone=warn" data-dl-class="StatusCard ← DevlibComponent" data-dl-src="—">
  <span class="dl-scard__icon"><i class="fa-solid fa-hourglass-half"></i></span>
  <span class="dl-scard__body">
    <span class="dl-scard__top">
      <b class="dl-scard__title">Press 2</b>
      <span class="dl-badge dl-badge--pill dl-badge--warn"><span class="dl-scard__dot"></span>Waiting plate</span>
    </span>
    <span class="dl-scard__value">0 sheets/hour</span>                         <!-- only when value is not null -->
    <span class="dl-scard__desc">Third color plate not arrived</span>           <!-- only with desc -->
    <span class="dl-scard__meta"><i class="fa-regular fa-clock"></i> 15 minutes ago</span>  <!-- only with meta -->
  </span>
</div>
```

With `filter` the root is `<button type="button" class="dl-scard ... is-filter [is-active]" data-dl-stat>`. A group of cards belongs in `<div class="dl-scards">` (CSS grid, `auto-fill`, columns of at least 240px, gap 10px).

## Props

Constructor of `Devlib\Components\StatusCard` (9 props, none inherited). Blade names are kebab-case; booleans can be bare attributes (`filter`) or `:filter="$expr"`.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `title` | `string`, `''` | Bold name of the thing. Translated through the dictionary (wrap database names in `raw()`). When the site sets a Technical Guide URL, the title text is wrapped in a small link to this guide entry (see Behavior). |
| `status` | `string`, `''` | Text inside the pill (`Running`, `Broken`). Not translated: write it in the page language or translate it yourself. Empty still draws the pill with its dot. |
| `tone` | `string`, `'accent'` | Color of the left border, icon tint and pill: `accent`, `success`, `warn`, `danger`, `info`, `neutral`. Written as `dl-scard--<tone>` and `dl-badge--<tone>`. `info` and `accent` have the same border color (accent); the pill of `info` is the info badge. `violet` colors the pill but not the border (no `dl-scard--violet` rule). Unknown values give the default accent border and a grey pill. |
| `icon` | `string`, `'info'` | Icon in the round badge. Either a DevLib meaning name (`info`, `warning`, `error`, `success`, `user`, `time`, ... from `Devlib\Icon::MAP`) or a full Font Awesome class (`fa-gears`). The view adds `fa-solid`, so only solid icons work. A bare name that is not in the map and has no `fa-` prefix (`gears`) produces a broken icon. |
| `value` | `?string`, `null` | Large line under the title (a reading, a count). Shown when not `null` (so `'0'` is shown, `''` prints an empty line). Not translated, not formatted: pass a ready string. |
| `desc` | `?string`, `null` | Small grey description line. Hidden when empty. Not translated. |
| `meta` | `?string`, `null` | Smallest line with a clock icon, for time ("2 minutes ago", "today 08:00"). Hidden when empty. Not translated: format the time on the server. |
| `filter` | `bool`, `false` | `true` renders a `<button type="button">` with `is-filter` (pointer, hover) and `data-dl-stat`, so the `Stat` script makes it a toggle. `false` renders a `div`. |
| `active` | `bool`, `false` | Initial state only: adds `is-active` (soft background and ring). The script changes it on every click. |

Every DevLib component also accepts `can="module.action"` (without the ability nothing is rendered) and extra HTML attributes (`id`, `class`, `style`, `data-*`), merged on the root element; `class` is appended to the generated classes.

## Behavior

- Layout: a row with the icon (38px circle, tinted with the tone color) and a column body. The title and the pill sit on one line and wrap when the card is narrow; the pill never shrinks or breaks its text.
- Tone: border `success` green, `warn` amber, `danger` red, `info` and `accent` blue, `neutral` grey. The pill uses the badge tones of the same name.
- Animation: only the pill dot of tone `success` pulses (`dl-pulse`, 1.6 s, opacity). There is no reduced-motion override for it. Other tones have a still dot.
- Filter toggle (script `Stat`, `resources/assets/js/components/stat.js`): a click removes `is-active` from every `[data-dl-stat]` in the same parent element, then switches the clicked card on if it was off (clicking the active card turns all off). The group is the parent element: keep the cards of one filter in one container (`div.dl-scards`), and do not mix `x-dl::stat-box` tiles into it unless they should share the group.
- Keyboard: a real `button` (Tab, Enter, Space) when `filter`; the plain `div` is not focusable.
- Guide link: with `window.DL_GUIDE` set (`devlib.guide_url`), `guide-link.js` moves the title text into `<a class="dl-guide-link" target="_blank">` pointing at this guide entry. This is for developers; leave the guide URL empty in the production theme if the cards should not link out.
- Theme and language: colors are CSS variables (light and dark follow the site); only `title` is translated.

### Accessibility as implemented

No `aria-*` and no `role`. The status is a colored pill with text (the word is readable without color); the icon is decorative `<i>` without `aria-hidden`. A filter card has no `aria-pressed`: the active state is only a class and color.

## Data and events

- Props only; no slot, no data access (`source()` is `—`).
- Nothing is posted. A filter card dispatches a bubbling `dl:stat` with `detail.active` (`true` = switched on, `false` = switched off) from the card. Listen on a parent and read your own `data-*` from the card: `e.target.closest('[data-dl-stat]').dataset.machine`. The event carries no key.
- `x-dl::sheet` listens to `dl:stat` and filters by the `data-where` JSON of the clicked tile (`resources/assets/js/components/sheet.js:69`); a status card placed inside a sheet has no `data-where`, so it would be read as an empty filter. Do not put status cards inside a sheet.
- Hooks: `.dl-scard`, `.dl-scard__title`, `.dl-scard__value`, `.dl-scard__desc`, `.dl-scard__meta`, `[data-dl-stat]`, `.is-filter`, `.is-active`. `el.dlComponent` is the `Stat` instance for filter cards.

## Rules and pitfalls

- Use `filter` only when the card really filters something and a listener exists; otherwise keep the plain card (no pointer cursor, no button).
- `status`, `value`, `desc` and `meta` are not translated. Statuses of documents must still use the standard English words (Draft, Confirmed, ...) per the project terminology, and the same word must not be spelled two ways across cards.
- Keep `tone` consistent with the meaning on the whole board: `success` running / ok, `warn` waiting, `danger` broken, `info` planned work, `neutral` off.
- Pass a ready string in `value`; there is no number formatting (use `Locale::number()` on the server).
- `icon` is `fa-solid` only; use a meaning name or `fa-<name>`.
- The title link added by `DL_GUIDE` sits inside a `button` when `filter` is on (a link inside a button is invalid HTML; clicking the title opens the guide in a new tab and the click also reaches the button, which toggles).
- `active` is rendered once; reload state from the server or your listener.
- No lint rule targets this object.
- Code oddity (not fixed): `x-dl::status-card` is the only card type in the `TITLES` map of `resources/assets/js/components/guide-link.js:9`, so its title becomes a link when the guide URL is set, even inside a filter button; the other cards and tiles never do.
- Code oddity (not fixed): `violet` is a valid pill tone (`resources/assets/devlib.css:678`) but has no `dl-scard--violet` border rule (`:1176`), so a violet card shows a violet pill with an accent border.
- Code oddity (not fixed): `dl:stat` is dispatched by the shared `Stat` script and carries only `detail.active` (`resources/assets/js/components/stat.js:13`), so listeners cannot tell which card fired without their own `data-*` attribute.

## Example

Copied in shape from the catalog demo `status-card` (fictional data).

```blade
<div class="dl-scards">
    <x-dl::status-card title="Press 1" status="Running" tone="success" icon="fa-gears" value="8,450 sheets/hour" desc="PO-2609-0012 · morning shift" meta="2 minutes ago" filter />
    <x-dl::status-card title="Press 2" status="Waiting plate" tone="warn" icon="fa-hourglass-half" value="0 sheets/hour" desc="Third color plate has not arrived" meta="15 minutes ago" filter />
    <x-dl::status-card title="Die-cut 2" status="Broken" tone="danger" icon="fa-triangle-exclamation" desc="Blade broken, technician called" meta="1 hour ago" filter />
    <x-dl::status-card title="Laminator" status="Maintenance" tone="info" icon="fa-screwdriver-wrench" desc="Monthly service" meta="today 08:00" filter />
    <x-dl::status-card title="Auto glue" status="Off" tone="neutral" icon="fa-power-off" desc="Not scheduled" filter :active="$selected === 'glue'" />
</div>
```

Read-only board with a meaning-name icon, a permission and data-driven values:

```blade
@foreach ($machines as $m)
    <x-dl::status-card can="production.view" :title="raw($m->name)" :status="$m->state_label" :tone="$m->tone" icon="warning" :value="$m->rate" :meta="$m->since" />
@endforeach
```

## Related

- `x-dl::stat-box`: the same toggle behavior for plain number tiles.
- `x-dl::kpi`: dashboard number with change, sparkline and target.
- `x-dl::traffic-light`: red / amber / green verdict with a sentence.
- `x-dl::badge`: status word inside a list or form.
- `x-dl::kanban`: cards that move between columns.
- `skills/devlib/components.md`: generated prop list of every object.
