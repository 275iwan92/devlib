## Overview

`<x-dl::greeting>` prints one line of text that follows the time of day: "Selamat pagi" / "Selamat siang" / "Selamat sore" / "Selamat malam" (Indonesian) or "Good morning" / "Good afternoon" / "Good evening" / "Good night" (English), optionally followed by a first name and a wave emoji, for example `Good morning, Yuna 👋`. The text is built in the browser from the visitor's clock, in the configured time zone, and is rewritten every minute and whenever the page language is switched. The server prints only a placeholder (`—`).

Use it when the screen needs only a salutation: a header, a login frame over a photo (it is the `greeting` widget of `x-dl::frame`), a side panel. It has no data of its own and makes no network call.

Pick another object when you need more:

- `x-dl::greeting-card`: the full "hero" block (salutation + position + today brief + Job Description / SOP buttons + clock + check-in + weather). It uses `x-dl::greeting` inside; see the comparison table in its entry.
- `x-dl::clock`: the running time and date, not a salutation.
- `x-dl::dashboard`: the whole opening screen, which already contains a greeting card.

### Generated markup

```html
<div class="dl-greeting" data-dl="x-dl::greeting" data-dl-class="Greeting ← DevlibComponent" data-dl-src="jam sistem, zona Asia/Jakarta"
     data-dl-greeting data-name="Yuna" data-tz="Asia/Jakarta">—</div>
```

After mounting, the `div` content is replaced with plain text (`textContent`), so the name can never inject markup. Extra HTML attributes (`id`, `class`, `style`, `data-*`) go on this root `div`; `can="module.action"` also works (without the ability nothing is rendered).

## Props

Constructor of `Devlib\Components\Greeting` (2 props). Use `:prop="expr"` for anything that is not a plain string.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `name` | `string`, `''` | Name added after the salutation (`Good morning, Yuna`). Written to `data-name` (escaped by Blade) and shown with `textContent`. Empty = salutation only, without the comma. It is not a translated text prop: it is shown as given, never looked up in the dictionary, so no `raw()` is needed. Pass only the first name or a short display name. |
| `timezone` | `?string`, `null` | IANA time zone used to decide the hour (`Asia/Jakarta`, `Asia/Makassar`, `UTC`). `null` = `config('devlib.timezone')` (default `Asia/Jakarta`). Written to `data-tz`. An empty string `''` is kept as it is and makes the script use the visitor's own browser zone. An invalid name is NOT checked by PHP and throws in the browser, see the first code oddity below. Abbreviations such as `WIB` are not valid IANA names. |

## Behavior

### Time of day

The script takes the hour (0-23) in the configured zone and picks the key:

| Hour (in the zone) | Key | `id` | `en` |
|---|---|---|---|
| 00:00 - 10:59 | `gr_morning` | Selamat pagi | Good morning |
| 11:00 - 14:59 | `gr_afternoon` | Selamat siang | Good afternoon |
| 15:00 - 17:59 | `gr_evening` | Selamat sore | Good evening |
| 18:00 - 23:59 | `gr_night` | Selamat malam | Good night |

The boundaries are fixed in `resources/assets/js/components/greeting.js:14`; there is no prop to change them. The result is `T(key)` + `', ' + name` (when `name` is set) + ` 👋`.

### Timezone and the clock source

- The instant comes from the visitor's device clock (`new Date()`); only the hour is converted to the configured zone. A device with a wrong clock shows a wrong greeting. The word "system" in the source text (`jam sistem, zona ...`) means "the application's configured zone", not the server's clock.
- The zone is the application's (`devlib.timezone`), not the visitor's. A visitor in another country sees the greeting for the application's zone unless you pass `timezone=""`.
- The same default zone is used by `x-dl::clock`, so a clock and a greeting on the same screen agree unless you pass different values.

### Language switching

- The salutation words come from the DevLib dictionary through `Devlib.h.T('gr_...')`. `id` and `en` are built in; for another language the English text goes through the application dictionary, so a translation entered there is used.
- The active language is `Devlib.lang` (from `window.DL_LANG_ACTIVE`, printed by `x-dl::theme-boot`, or the `?lang=xx` query parameter). When the visitor clicks a flag of `x-dl::lang-switch` the `dl:lang` event is dispatched on `document` and the greeting is rewritten at once, without a reload.
- `name` is not translated.

### Update interval and cleanup

- `init()` writes the text once, then calls `setInterval(upd, 60000)`: the text can be up to one minute late after a boundary (at 11:00 it may still say "pagi" until the next tick).
- The interval and the `dl:lang` listener are never removed (the component has no destroy step). On a normal page this is harmless. If a screen replaces the DOM without a reload and mounts the component again, each removed greeting keeps its timer alive.
- Mounting is idempotent: `Devlib.mount(root)` skips elements already marked mounted. Call it after inserting new HTML dynamically.

### Offline and failure

No network is used, so it works offline. The failure case is a page without `x-dl::theme-boot`: `window.DL_LANG` is missing, `T()` returns the key itself and the greeting shows `gr_morning`. Always include `x-dl::theme-boot` in the layout.

### Accessibility as implemented

Plain `div` text, no `role`, no `aria-live` (a screen reader is not interrupted each minute). The wave emoji is part of the text. The style (`.dl-greeting`: bold, fluid size, soft text shadow) is made to sit on a photo or a dark hero; the colour is inherited, so set the colour of the parent.

## Data and events

- Server to browser: only `data-name` and `data-tz`.
- Browser to server: nothing is sent.
- No `dl:*` event is emitted. It listens to `dl:lang` on `document`.
- `el.dlComponent` is the `Greeting` instance with the methods `init()` and `upd()` (auto-bound, so `el.dlComponent.upd()` redraws on demand).
- Hooks: `[data-dl-greeting]` (mount selector), `data-name`, `data-tz`. Override the behaviour with `Devlib.define("Greeting", MyGreeting)` (all greetings) or `data-dl-js="MyGreeting"` + `Devlib.register(MyGreeting)` (one element). The PHP class has no `jsClass()`, so add the attribute by hand on the tag.

## Rules and pitfalls

- Always give a valid IANA time zone or leave `timezone` out. Use the config key for the whole application instead of repeating the prop.
- Do not use the greeting as a source of truth for the time (attendance, cut-offs): it is the device clock. Decide on the server.
- Put the greeting on a dark or photo background, or set the text colour of the parent; the style adds a dark shadow and no colour.
- Include `x-dl::theme-boot` in the layout (dictionary, language, CSRF values for all scripts).
- Related lint rules: none (`src/Lint` has no rule for this object).
- Code oddity (not fixed): an invalid `timezone` makes `toLocaleString` throw `RangeError` inside `init()` (`resources/assets/js/components/greeting.js:13`). `boot.js:22` calls `Devlib.mountOne("Greeting")` inside the single `DOMContentLoaded` handler without a `try`, so the exception stops the handler: every component mounted after the greeting in `resources/assets/js/boot.js` (and the final `Devlib.mount(document)`) is never mounted on that page. The same happens for `x-dl::clock` (`resources/assets/js/components/clock.js:17`, mounted at `boot.js:19`). PHP does not validate the value.
- Code oddity (not fixed): the hour table in `resources/assets/js/components/greeting.js:14` (`h < 11 ? 0`) treats 00:00-04:59 as morning, so a screen opened at 02:00 says "Good morning" / "Selamat pagi" instead of "night".
- Code oddity (not fixed): the interval and the `dl:lang` listener are never cleared (`resources/assets/js/components/greeting.js:9`; the same pattern in `clock.js:12`, `timer.js:14`, `weather.js:13`). `resources/assets/js/core.js:18-40` (`DlComponent`) has no destroy hook.

## Example

From the greeting card (`resources/views/components/greeting-card.blade.php:10`) and the frame (`resources/views/components/frame.blade.php:22`):

```blade
{{-- Salutation with the first name; zone from config('devlib.timezone') --}}
<x-dl::greeting name="Yuna" />

{{-- Anonymous salutation, as in the login frame --}}
<x-dl::greeting />

{{-- Explicit zone --}}
<x-dl::greeting name="Yuna" timezone="Asia/Makassar" />

{{-- Inside the login frame: widget list "name@position" --}}
<x-dl::frame box="right" widgets="brand@top-left, greeting@top-right, clock@bottom-left">
    ...
</x-dl::frame>
```

## Related

- `x-dl::greeting-card`: the dashboard hero that wraps this greeting with position, brief, documents, clock, check-in and weather.
- `x-dl::clock`: running time and date, same default time zone.
- `x-dl::weather`: current weather widget, usually shown next to the greeting.
- `x-dl::frame`: login frame; `greeting` is one of its widgets.
- `x-dl::dashboard`: the opening screen that contains a greeting card.
- `x-dl::lang-switch`, `x-dl::theme-boot`: language switch and the script values (`DL_LANG`, `DL_LANG_ACTIVE`) this object needs.
- `skills/devlib/components.md`: generated prop list.
