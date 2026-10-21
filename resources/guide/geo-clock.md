## Overview

`<x-dl::geo-clock>` is a mobile "clock in / clock out" card based on the device's GPS (geofencing). On load it asks the browser for the device position, sends it to the server to learn the distance to the work location, shows the distance, a radius bar and an inside / outside badge, and enables two buttons, **Clock In** and **Clock Out**. Pressing one posts the position and the type to the server, which answers with a message and the updated list of today's records.

The object knows no database and takes no decision. The server computes the distance and decides the status; the position given by the device is never trusted by the object. The widget is a thin client for two JSON endpoints that the project must provide.

Use it on a phone-first attendance screen. Pick another object when you need:

- Attendance from a fixed terminal, badge or fingerprint reader: that is not a browser feature; show the result with `x-dl::status-card` or in a grid.
- The check-in line of the home screen: `x-dl::greeting-card` / `x-dl::dashboard` show an `attendance` block that this card typically produces.
- A plain clock or a stopwatch: `x-dl::clock`, `x-dl::timer`.

### Generated markup

```html
<div class="dl-geo" data-dl="x-dl::geo-clock" data-dl-class="GeoClock ← DevlibComponent" data-dl-src="..." data-dl-js="GeoClock"
     data-dl-geo data-check-url="/attendance/check" data-clock-url="/attendance/clock" data-radius="200"
     data-t='{"waiting":"...","denied":"...", ...}' data-history='[{"type":"IN","time":"07:28:14","status":"AUTO_APPROVED","tone":"success"}]'>
  <div class="dl-geo__head"><b>Title</b> <span>Subtitle</span></div>                       <!-- only with title or subtitle -->
  <div class="dl-geo__box">
    <small>Distance to the work location</small>
    <div class="dl-geo__msg" data-geo-msg>Waiting for GPS location permission…</div>
    <div class="dl-geo__dist" data-geo-dist hidden><b data-geo-meters></b> <span>meters</span></div>
    <div class="dl-geo__bar" data-geo-bar hidden><i data-geo-fill></i></div>
    <span class="dl-geo__badge" data-geo-badge hidden></span>
    <button type="button" class="dl-btn dl-btn--link dl-btn--sm" data-geo-recheck>Check location again</button>
  </div>
  <div class="dl-geo__note" data-geo-note hidden></div>
  <div class="dl-geo__actions"> [Clock In data-geo-clock="IN" disabled] [Clock Out data-geo-clock="OUT" disabled] </div>
  <small>Today</small>
  <div class="dl-geo__history" data-geo-history></div>
</div>
```

Extra HTML attributes (`id`, `class`, `style`, `data-*` such as `data-demo-distance`) go on the root `div`; `can="module.action"` also works (without the ability nothing is rendered and the browser is never asked for the position).

## Props

Constructor of `Devlib\Components\GeoClock` (7 props). Use `:prop="expr"` for numbers and arrays.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `check-url` | `string`, `''` | URL that measures the distance. The script POSTs JSON `{lat, lng}` and expects `{distance, in_radius}` (see Data and events). Empty = demo mode: no request is made; the distance is taken from the attribute `data-demo-distance` (default 80 m) and `in_radius` is `distance <= radius`. Always set it in a real screen. |
| `clock-url` | `string`, `''` | URL that records the clock in / out. The script POSTs `{type: "IN"\|"OUT", lat, lng}` and expects `{message, tone, history}`. Empty = nothing is posted: the buttons only fire the event `dl:geo-clock` and are enabled again. |
| `radius` | `int`, `200` | Allowed radius in metres, used by the browser only for the bar: the fill is `min(100, distance / radius * 100)` percent and the bar turns red when outside. It is NOT the rule that is enforced: the server must apply its own radius. A value of `0` is read by the script as `200`. |
| `history` | `array`, `[]` | Today's records shown under "Today". List of rows `['type' => 'IN'\|'OUT', 'time' => text, 'status' => text, 'tone' => 'success'\|'warn'\|'danger']`. All values are data (shown as given, not translated): wrap them with `raw()`; the constructor removes the `raw()` markers from string values (`Lang::plain`). `type` decides the label (`Clock In` for `IN`, anything else `Clock Out`); `tone` is optional (default `neutral`, no colour). Empty = "No attendance yet today." |
| `title` | `string`, `''` | Heading line (bold). Translated (standard text prop). When both `title` and `subtitle` are empty the head row is not rendered. |
| `subtitle` | `string`, `''` | Smaller text next to the title. Translated. |
| `source` | `?string`, `null` | Free text naming where the data comes from, shown by the developer catalog / Manifest (`source()`). `null` = a default text (see the code oddity below). No visual or runtime effect. |

## Behavior

### Flow

1. `init()` reads the translated texts (`data-t`), the radius and the initial `history`, binds the buttons and calls `locate()`.
2. `locate()` clears the old position, disables both buttons, shows "Waiting for GPS location permission…" and calls `navigator.geolocation.getCurrentPosition` with `enableHighAccuracy: true`, `timeout: 10000` and no cached position (a fresh fix is requested every time).
3. Position error, permission denied, timeout or an unsupported device: `fail(text)` shows the red message ("Location permission denied or GPS could not be read." or "This device does not support GPS."), hides distance, bar and badge, and leaves the buttons disabled. All geolocation errors share the same message.
4. Success: `measure(lat, lng)` stores the position and POSTs it to `check-url`. The answer is drawn by `show()`: the metres, the bar, the badge "Inside / Outside the work location radius" (green / red), and **both buttons are enabled**.
5. A click on **Clock In** / **Clock Out**: `clock(type)` disables the buttons, fires `dl:geo-clock`, POSTs `{type, lat, lng}` to `clock-url`, shows the answer in the note box (`message`, coloured by `tone`), replaces the list with `history` and enables the buttons again. An error shows the message in red above the distance and enables the buttons again.
6. **Check location again** repeats from step 2. The position used for clocking is the last measured one: on a page left open for a long time, ask the user to re-check first.

### Security model

- The decision is the server's: it recomputes the distance from the posted `lat` / `lng` on `clock-url`, applies its radius, and decides status (on time, late, auto approved, pending, rejected). The widget does not block a click when the badge says "outside": it enables both buttons whenever a distance was received, and the server answers (usually with a `warn` or `danger` message).
- The browser position can be forged (developer tools, mock-location apps). The object cannot detect this; use server-side signals if spoofing matters (accuracy, device binding, photo, review of "pending" records).
- Requests are `fetch` with `credentials: 'same-origin'`, JSON `Content-Type` and `Accept`, and the header `X-CSRF-TOKEN` taken from `window.DL_CSRF` (printed by `x-dl::theme-boot`). The user must already be logged in through the session cookie; authorise the endpoints on the server with the logged-in user, never with a user id sent by the page.
- Geolocation works only on a secure context (HTTPS or `localhost`). On plain HTTP the browser refuses and the user sees the "denied" message.
- Locations of employees are personal data: store the minimum, restrict access, and do not copy coordinates into logs that many people can read.

### Language switching

All fixed texts are translated on the server when the page is rendered (`texts()` for the script, `Lang::text(...)` for the markup). The two big buttons "Clock In" / "Clock Out" follow the project rule for buttons (`x-dl::button` labels stay English unless `config('devlib.translate_fixed')` is on), while the same words in the history list come from `texts()` and are translated. The widget does NOT listen to `dl:lang`, so a language switch changes it only after a reload (use `x-dl::lang-switch` with reload, or reload the page). Server messages returned in `message` use whatever language the server uses for that request. `title` and `subtitle` are translated; the history rows are not (data).

### Time zone and locale

The widget formats nothing. The `time` of each history row and the server `message` arrive as ready text; format them on the server with the application zone (`Devlib\Locale::timezone()`) so that they agree with `x-dl::clock`.

### Offline and failure

- No internet needed except for the two endpoints. If `check-url` fails (offline, HTTP error, invalid JSON), the script shows `message` from the JSON error body, or the HTTP status text, or "Could not send. Try again." and keeps the buttons disabled; the user can press **Check location again**.
- If `clock-url` fails, the same message is shown above the distance, the buttons are enabled again and nothing is added to the list. The script does not retry and does not queue records offline; a record is never created on the device.
- Because the HTTP error `message` is shown to the user as it is, a server in debug mode can leak internal text; return short messages.

### Update interval and cleanup

There is no timer. The position is read at mount and on **Check location again**; the object does not watch the position continuously. It registers click handlers on its own element only, so nothing global is left behind when the element is removed.

### Accessibility as implemented

Real `button`s (the two main actions are disabled until a position is known). The message, note and list are plain elements without `aria-live`, so results are not announced to screen readers; the badge says inside / outside in words as well as colour.

## Data and events

### Request to `check-url`

`POST` JSON `{"lat": -7.2575, "lng": 112.7521}` (numbers, `lng` not `lon`). Expected answer `200` JSON:

```json
{"distance": 80, "in_radius": true}
```

`distance` is printed as it is (round it on the server); `in_radius` is a boolean that drives the badge and the bar colour.

### Request to `clock-url`

`POST` JSON `{"type": "IN", "lat": -7.2575, "lng": 112.7521}` (`type` is `IN` or `OUT`). Expected answer `200` JSON:

```json
{"message": "Clock In recorded 07:28", "tone": "success",
 "history": [{"type": "IN", "time": "07:28:14", "status": "AUTO_APPROVED", "tone": "success"}]}
```

`tone` of the message: `success`, `warn`, `danger` (red) or anything else / missing (neutral, class `is-info`). `history` replaces the list; omit it to leave an empty list. Any non-2xx answer with `{"message": "..."}` is shown as the error text.

### Events and JS

- `dl:geo-clock` is dispatched on the root element (bubbles, not cancelable) every time a clock button is pressed, before the request, with `detail: {type, lat, lng}`. It is also fired in demo mode (no `clock-url`), which is how a screen can do its own handling.
- `el.dlComponent` is the `GeoClock` instance (`pos`, `inRadius`, `radius`, `buttons`, `t`) with the methods `locate()`, `measure()`, `show()`, `clock()`, `history()`, `post()`, `csrfHeaders()`, `setMessage()`, `fail()`, `enable()`; the PHP class sets `jsClass()` to `GeoClock` so the root carries `data-dl-js="GeoClock"`. Override with `Devlib.register(class MyGeo extends Devlib.classes.GeoClock {...})` and a PHP subclass whose `jsClass()` returns `MyGeo` (for example to add a photo step or a different CSRF header).
- Hooks: `[data-dl-geo]` (mount selector), `data-geo-msg`, `data-geo-dist`, `data-geo-meters`, `data-geo-bar`, `data-geo-fill`, `data-geo-badge`, `data-geo-note`, `data-geo-recheck`, `data-geo-clock="IN|OUT"`, `data-geo-history`, and the attributes `data-check-url`, `data-clock-url`, `data-radius`, `data-t`, `data-history`, `data-demo-distance`.

### Server side

The library ships no route for these URLs. The project provides two endpoints that: authenticate the user, validate `lat` (-90..90) and `lng` (-180..180), compute the distance to the user's work location (for example a haversine formula), compare it with its own radius, store the record, and return the JSON above. Keep the radius in one place on the server and pass the same value to the `radius` prop only for the bar.

## Rules and pitfalls

- Always pass both URLs in a real screen. With an empty `check-url` the widget decides in the browser from `data-demo-distance` (default 80 m, which is inside the default radius), so every user looks "inside".
- Never trust the page: the server recomputes the distance on `clock-url` and rejects or marks records outside the radius. The buttons being enabled outside the radius is by design (the server decides), not a permission.
- Load the layout with `x-dl::theme-boot` (CSRF token) and serve over HTTPS.
- Return short, user-safe `message` texts and JSON for errors (`Accept: application/json` is sent so Laravel returns JSON).
- Wrap history values in `raw()`; they are data and must not be translated or recorded in the dictionary.
- `radius` is display only; keep it equal to the server rule.
- Do not use it for a desktop attendance flow: desktop positions come from Wi-Fi/IP and are coarse.
- Related lint rules: none (`src/Lint` has no rule for this object).
- Code oddity (not fixed): `resources/assets/js/components/geo-clock.js:59` falls back to `data-demo-distance` (default 80) whenever `check-url` is empty, in every environment, not only in the catalog. A forgotten `check-url` silently turns the widget into an always-inside demo.
- Code oddity (not fixed): `show()` (`resources/assets/js/components/geo-clock.js:77`) calls `enable(true)` regardless of `inRadius`, so the clock buttons are active when the badge says "Outside the work location radius".
- Code oddity (not fixed): `src/Components/GeoClock.php:45` uses a default `source` text that names a project database table (`att_mobile_gps_log + lokasi kerja`); a neutral library should not name a project table.
- Code oddity (not fixed): `radius` of `0` becomes `200` in the script (`+this.el.dataset.radius || 200`, `resources/assets/js/components/geo-clock.js:13`), and a `radius` smaller than the distance always fills the bar to 100%.
- Code oddity (not fixed): the error callback of `getCurrentPosition` ignores the error code (`resources/assets/js/components/geo-clock.js:47`), so denied permission, a timeout (10 s) and an unavailable position all show "Location permission denied or GPS could not be read."
- Code oddity (not fixed): `texts()` includes the key `meters` (`src/Components/GeoClock.php:57`) but the script never reads `t.meters`; the unit is printed by the markup instead.
- Code oddity (not fixed): the widget does not listen to `dl:lang` (`resources/assets/js/components/geo-clock.js`, no handler), unlike `x-dl::clock`, `x-dl::greeting` and `x-dl::weather`, so the language changes only after a reload.

## Example

Copied from the catalog demo (`site/resources/views/demos/geo-clock.blade.php`); there is no server, so the distance is a fixed `data-demo-distance`:

```blade
<x-dl::geo-clock title="Clock In / Out" subtitle="Demo: no server, the distance is a fixed 80 m (inside the 200 m radius)" data-demo-distance="80"
    :history="[['type' => 'IN', 'time' => raw('07:28:14'), 'status' => raw('AUTO_APPROVED'), 'tone' => 'success'], ['type' => 'OUT', 'time' => raw('16:35:02'), 'status' => raw('PENDING'), 'tone' => 'warn']]" />
<x-dl::geo-clock title="Outside the radius" subtitle="Demo: a fixed 650 m, the badge turns red" data-demo-distance="650" style="margin-top:16px" />
```

With real endpoints (fictional route names):

```blade
<x-dl::geo-clock title="Clock In / Out" :radius="200"
    check-url="{{ route('attendance.check') }}" clock-url="{{ route('attendance.clock') }}"
    :history="$todayRows" />
```

where `$todayRows` is built on the server as `[['type' => 'IN', 'time' => raw('07:28:14'), 'status' => raw('ON_TIME'), 'tone' => 'success']]`.

## Related

- `x-dl::button`: renders the two clock buttons and the re-check button style.
- `x-dl::greeting-card`, `x-dl::dashboard`: show the check-in (`attendance`) of the day on the home screen.
- `x-dl::clock`: running time in the application zone; use the same zone for the history times.
- `x-dl::theme-boot`: prints `window.DL_CSRF` that the requests need.
- `x-dl::lang-switch`: language switch; the geo clock follows it after a reload.
- `x-dl::status-card`, `x-dl::modal`: ways to show attendance results or confirmations around the card.
- `skills/devlib/components.md`: generated prop list.
