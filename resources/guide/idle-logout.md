## Overview

`<x-dl::idle-logout>` signs the user out after a period without activity. It is a screen-side object: it counts idle time in the browser, shows a warning with a countdown shortly before the end, and when time runs out sends a logout request and moves the browser to the sign-in page. It is opt-in and mounted ONCE in the application layout, next to the other global objects. The server side (the real session lifetime, for example `SESSION_LIFETIME`) stays the application's job; this object only makes the client behave the same way and keeps the server session alive while the person is really working.

Activity means mouse move, click, key press, scroll, wheel and touch (at most one count per second). Typing in a form counts, so an operator who fills a long document is not thrown out. Activity in one browser tab extends every tab of the same browser; a logout in one tab moves the others too. The object never reads, saves or clears form data.

Generated markup (hidden until the warning starts; nothing is rendered when `minutes` is 0 or less):

```html
<div class="dl-idle" data-dl="x-dl::idle-logout" data-dl-class="IdleLogout ← DevlibComponent" data-dl-js="IdleLogout" data-dl-idle data-minutes="30" data-warn="2" data-logout-url="/logout" data-ping-url="/session/ping" data-ping-every="5" data-login-url="" hidden role="alertdialog" aria-labelledby="dl-idle-title" aria-describedby="dl-idle-msg">
  <i class="fa-solid fa-hourglass-half dl-idle__icon" aria-hidden="true"></i>
  <div class="dl-idle__body">
    <b id="dl-idle-title" data-dl-t="idle_title">…</b><span id="dl-idle-msg" data-idle-msg></span>
    <div class="dl-idle__act"><button type="button" class="dl-btn dl-btn--md dl-btn--primary" data-idle-stay>…</button><button type="button" class="dl-btn dl-btn--link" data-idle-out>…</button></div>
  </div>
</div>
```

## Props

Constructor of `Devlib\Components\IdleLogout` (7 props). Times are in minutes and may be fractions (`0.75` = 45 seconds).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `minutes` | `float`, `30` | Idle limit. After this long without activity the user is signed out. `0` or less renders nothing (switch it off without removing the tag). |
| `warn` | `float`, `2` | Minutes before the end when the warning appears. Clamped between 0 and `minutes`. `0` = no warning, straight logout. |
| `logout-url` | `string`, `''` | Address that logs out. Called with `POST` (same origin) and the CSRF token. Empty = no request, the page only moves to `login-url` or reloads. |
| `ping-url` | `string`, `''` | Light address that refreshes the server session. Called with `GET`, expected to answer `204`. Empty = no ping at all, only the client counter runs. |
| `ping-every` | `float`, `5` | Minutes between pings. A ping is sent only if the user was active in this tab since the previous ping. |
| `login-url` | `string`, `''` | Where to go after the logout. Empty = the address the logout request was redirected to; if none, the page is reloaded and the server shows its sign-in page. |
| `preview` | `bool`, `false` | Catalog demo mode: no request is ever sent and the page never moves; at the end it shows a simulation message with a restart button. Do not use it in an application. |

## Behavior

Script `IdleLogout` (`resources/assets/js/components/idle-logout.js`, selector `[data-dl-idle]`), checked once per second against the clock (a laptop that slept is handled on wake-up).

1. Activity moves the "last active" time forward. It is stored in localStorage (`dl-idle-last`) and announced through `BroadcastChannel` (`dl-idle`) so all tabs share it. Both may be blocked; the object then works per tab.
2. When `warn` minutes are left the dialog appears bottom right (non-modal, `role="alertdialog"`), focus goes to **Stay signed in**, and the text counts down (`m:ss`). Event `dl:idle-warn` fires once.
3. While the warning is open only the dialog moves the clock: **Stay signed in** (or Escape inside the dialog) sends a ping first, then closes the dialog. Plain mouse movement does not, so a bumped mouse cannot silently cancel the warning. Activity in another tab does.
4. If that ping answers `401` or `419` the server session is already gone and the logout runs at once.
5. At zero (or on **Sign out now**) it tells the other tabs, sends `POST logout-url` with `_token` and the `X-CSRF-TOKEN` header (token from `window.DL_CSRF`, then `<meta name="csrf-token">`, then an `input[name=_token]`), fires `dl:idle-logout`, and goes to `login-url`. A failed request does not stop the move.
6. Only one instance works per page: a second tag is removed.

## Data and events

- No form field; nothing is submitted with a form.
- Events on the root (bubble): `dl:idle-warn` (`detail.left` = milliseconds left) and `dl:idle-logout` (`detail.url`, fired after the logout request, before the move).
- localStorage keys `dl-idle-last` and `dl-idle-out`; BroadcastChannel `dl-idle`. They hold timestamps only.
- `el.dlComponent` is the instance; it also sits at `window.dlIdleLogout`. `reset()` restarts the count from zero (used by the catalog demo and scripts).
- Texts use the dictionary keys `idle_title`, `idle_msg` (placeholder `{time}`), `idle_stay`, `idle_out`, `idle_ended`, `idle_done`, `idle_preview`, `idle_again` and follow the display language.

## Rules and pitfalls

- Mount it once in the layout of the signed-in area only. Never on guest pages: there is nothing to sign out of.
- Keep `minutes` equal to (or a little below) the real server session lifetime. If the server session is shorter, the person is signed out by the server before the warning.
- `ping-url` must be a cheap authenticated route that returns `204`. It should not do work, and a route without the session middleware does not refresh anything.
- The ping runs only on activity in the tab, so an idle tab never keeps the server session alive.
- The object does not save drafts. If a page has a leave-page guard (`beforeunload`) the browser may ask before the final move; unsaved data is never touched.
- Time is measured with the browser clock; changing the system clock changes the countdown.
- The countdown text follows a language change on the next second; the title and buttons change at once.
- Keep `preview` for the catalog. With it the object cannot log anyone out.

## Example

Layout of the application (once):

```blade
<x-dl::idle-logout logout-url="/logout" ping-url="/session/ping" login-url="/login" />
```

The application adds the route, for example in `routes/web.php`:

```php
Route::get('/session/ping', fn () => response()->noContent())->middleware('auth');
```

Short times for a trial, no ping:

```blade
<x-dl::idle-logout :minutes="1" :warn="0.5" logout-url="/logout" />
```

## Related

- `x-dl::lock-screen`: locks the screen after idle time (`idle`) but keeps the session; this object ends the session.
- `x-dl::alert`: the inline message box whose look the warning follows.
- `x-dl::lang-switch`: changes the language used by the warning texts.
- `skills/devlib/components.md`: generated prop list.
