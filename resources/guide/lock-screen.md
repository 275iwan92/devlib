## Overview

`<x-dl::lock-screen>` is a full-screen privacy cover for a signed-in user. When it is triggered (idle time, a button, or the keys Ctrl+Shift+L) it hides the application behind a blurred overlay with a clock, the user's photo or initials, their name and one password field. To continue, the user types their password and presses Unlock. A "Log out" button can be added.

It is a COVER, not a lock. The component keeps a flag in the browser, draws the overlay, and asks the project to check the password. It does not check the password, does not talk to the server, and does not end the server session. Everything that makes it secure (verifying the password, throttling, expiring the session) is the project's job, described below.

Pick another object when:

- the visitor is not signed in yet: `x-dl::login-box`;
- the user forgot the password: `x-dl::forgot-box`;
- you need to sign the user out after inactivity: configure the server session lifetime; this object only covers the screen.

### Generated markup

```html
<div class="dl-lock" data-dl-lockscreen data-idle="15" data-id="lock" hidden role="dialog" aria-modal="true" aria-label="Screen locked">
  <div class="dl-lock__clock"><b data-lk-time></b><span data-lk-date></span></div>
  <form class="dl-lock__card" data-lk-form novalidate>
    <span class="dl-lock__av"><img src="..." alt=""> or initials</span>
    <b class="dl-lock__name">Demo Admin</b>
    <small class="dl-lock__sub">Locked — enter the password to continue</small>
    <div class="dl-field ..."> password field, name="unlock_password", eye button </div>
    <button type="submit">Unlock</button>
    <button type="submit" formaction="..." formmethod="POST" formnovalidate data-lk-out>Log out</button>  <!-- only with logout-action -->
  </form>
</div>
```

The element starts `hidden`. There is no CSRF token and no `method` on the inner form (see the pitfalls).

## Props

Constructor of `Devlib\Components\LockScreen` (4 props). Blade names are kebab-case; use `:prop="expr"` for arrays and numbers. `can="module.action"` works as on every DevLib component. Place the element once per page, anywhere in the layout (it is `position: fixed`).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `user` | `array`, `['name' => 'User']` | Who is shown on the card: `name` (text; escaped) and optional `photo` (an image URL or data URI for the avatar). Without `photo`, the avatar shows the initials of the first two words of the name, upper-cased (`?` when there is no name). It is display only: it does not decide whose password is accepted; the server must use the signed-in user from the session. |
| `idle` | `int`, `0` | Minutes of no activity before the screen locks by itself. `0` (default) means no automatic lock; only the button or the keys lock it. Written to `data-idle`. Activity that restarts the countdown: `pointerdown`, `keydown`, `wheel`, `touchstart` on the document (mouse movement alone does not count). The countdown runs only while the screen is not locked. |
| `logout-action` | `string`, `''` | URL of the sign-out route. When not empty, a "Log out" button is drawn that sends the lock form to this URL as POST. Empty means no Log out button. |
| `id` | `string`, `'lock'` | Name of this lock instance. Part of the browser storage key `dl-lock-<id>` and of `data-id`. Give each application on the same host its own `id`, otherwise they share the "locked" flag. |

## Behavior

### Ways to lock

- **Idle**: `idle` minutes without activity (see above).
- **Button**: any element with the attribute `data-dl-lock` (for example `<x-dl::button icon="lock" label="Lock screen" data-dl-lock />`): a click anywhere in the page is caught at `document` level, so buttons added later work too.
- **Keys**: Ctrl+Shift+L (the script calls `preventDefault` for it).
- **Code**: `el.dlLock.lock()`.

Locking shows the overlay, adds the class `dl-locked` to `<html>` (the page body stops scrolling), starts the clock (time and long date, refreshed every 10 seconds, in the application time zone from `data-dl-tz` on `<html>` or `Asia/Jakarta`), clears the password field and its message, focuses the field and dispatches `dl:lock`. Calling `lock()` while already locked does nothing.

### Locked state survives reload

On lock the script writes `sessionStorage['dl-lock-<id>'] = '1'`. When the page loads again and finds the flag, it locks again immediately. The flag lives in the TAB: another tab, a new window or a restarted browser start unlocked; a duplicated tab inherits the flag. If `sessionStorage` is not available (private mode, blocked), the lock still works but is lost on reload.

### Unlocking

1. The user presses Unlock (or Enter in the field). The script cancels the browser submit.
2. An empty field gets the message "Enter the password." and nothing else happens.
3. Otherwise the script dispatches the cancelable event `dl:unlock` on the element with `detail.password` (the typed text). If NO listener calls `preventDefault()` the screen unlocks at once. This is the preview behaviour: in the catalog, any non-empty text unlocks.
4. An application that checks the password calls `preventDefault()`, verifies on the server, and then calls `el.dlLock.unlock()` (hide the overlay, clear the flag, restart the idle countdown, dispatch `dl:unlocked`) or `el.dlLock.fail(message)` (red message under the field, text selected). `fail()` without a message shows "Wrong password.".

### Log out

The Log out button is a submit button with `formaction`, `formmethod="POST"` and the marker `data-lk-out`. Clicking it removes the "locked" flag and lets the browser submit the lock form to `logout-action`. The script does not call `preventDefault` for it, so the whole form is posted, including whatever the user already typed in the password field (field name `unlock_password`).

### Accessibility as implemented

The root is `role="dialog" aria-modal="true"` with the label "Screen locked". The password field has a real label and the eye button of `x-dl::field.password`. There is no focus trap and the covered page is not marked `inert`: keyboard users can still Tab to controls behind the overlay, and assistive technology may still read the page. Do not rely on the cover for confidentiality (see below).

## Data and events

### What DevLib sends

Nothing, except the optional Log out POST. The password never leaves the page unless the application sends it.

### What the application must provide

1. **A verification endpoint** (the name is the project's choice; DevLib has none). The listener of `dl:unlock` posts the password to it over HTTPS in the request body (never in a URL or query string) with the CSRF header. The server must:
   - take the user from the SESSION, never from the request (no username parameter), and check the password against that user's stored hash;
   - throttle attempts (per user and per IP) and answer "too many attempts" distinctly (for example HTTP 429) so `fail()` can show it;
   - never log the password; return no password-derived data;
   - answer success (for example `204` or `{"ok":true}`) only when the hash matches; answer `422` or `401` on a wrong password; answer `401` or `419` when the session itself has ended, in which case the page should go to the sign-in screen instead of calling `unlock()`.
2. **A sign-out route** for `logout-action`: invalidate the session, regenerate the CSRF token, redirect to sign-in. The component does not add the token to the lock form, so the route must accept the POST (see the CSRF pitfall).
3. **Real protection at the server.** The overlay is only CSS and script; anyone with access to the browser can remove it in the developer tools or call `el.dlLock.unlock()`. To make the lock mean something, keep a "locked" flag in the server session as well: set it when `dl:lock` fires (a small POST), refuse every data request (for example with HTTP 423) while it is set, and clear it only in the verification endpoint. Pair it with a normal session lifetime.

### Events and JS API

| Name | Where | Purpose |
|---|---|---|
| `dl:lock` | root, bubbles | the screen has been locked |
| `dl:unlock` `{password}` | root, bubbles, cancelable | unlock requested; prevent the default to take over |
| `dl:unlocked` | root, bubbles | the screen has been unlocked |
| `el.dlLock.lock()` | root property | lock now |
| `el.dlLock.unlock()` | root property | unlock now (the application calls it after the server said yes) |
| `el.dlLock.fail(message)` | root property | show the password error |

Markup hooks: `data-dl-lockscreen`, `data-lk-form`, `data-lk-time`, `data-lk-date`, `data-lk-out`, `[data-dl-lock]` (trigger). `el.dlComponent` is the `LockScreen` instance. It is mounted by `boot.js` (`Devlib.mountOne("LockScreen")`); if you add the element to the page later, call `Devlib.mountOne('LockScreen')` again.

## Rules and pitfalls

- Never treat the lock screen as authentication. It protects against a glance, not against a person with the browser. The server session is still valid underneath.
- Preview unlocks with any text. Before go-live there must be a `dl:unlock` listener that calls `preventDefault()`. Test it by typing a wrong password: the screen must stay locked.
- Write the listener in a project JS file, not inline in the view (lint `L002`). The catalog demo page is listed in `lint_except` only for hex colours in its avatar data (`L003`).
- Use a unique `id` per application; the flag is per tab and per `id`.
- Do not use the user's real credentials in demos; the catalog uses a fictional name.
- The idle countdown measures page input only. It is not the server session timer: a request made after the server session has expired answers 401/419 whatever the screen shows.
- Do not trust `user`: it is what is shown. The password check uses the session user.
- The password field is `name="unlock_password"` with `autocomplete="current-password"`: browser password managers may offer saved passwords for the application's host, which is intended.
- Code oddity (not fixed): the lock form has no CSRF token and no `method` (`src/Components/LockScreen.php:44`, `:51`); the Log out button therefore posts without `_token`. A default Laravel POST route answers 419 unless it is excluded from CSRF verification or the page adds the token.
- Code oddity (not fixed): the Log out button submits the whole form, so whatever was typed into the unlock field is sent to `logout-action` (`src/Components/LockScreen.php:51`; the script returns without cancelling at `resources/assets/js/components/lock-screen.js:19`).
- Code oddity (not fixed): `unlock()` does not clear the password input (`lock-screen.js:31`); only the next `lock()` clears it (`lock-screen.js:30`). The typed password stays in the hidden field's value until then.
- Code oddity (not fixed): `LockScreen.php:16` says the clock shows "jam WIB"; the code uses the time zone from `data-dl-tz` on `<html>` (default `Asia/Jakarta`) and the `id-ID` format (`lock-screen.js:28`), whatever the page language.
- Code oddity (not fixed): `role="dialog" aria-modal="true"` is declared (`LockScreen.php:42`) but nothing traps focus or marks the page `inert`, so Tab can reach the covered page.

## Example

Catalog demo: a trigger button, the lock screen with a fictional user and a photo, no idle lock, a placeholder log-out URL (the catalog site posts nothing).

```blade
<div class="dl-forms-row">
    <x-dl::button icon="lock" label="Kunci layar sekarang" data-dl-lock />
    <span class="dl-note">atau tekan <kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>L</kbd>. Di aplikasi: <code>idle="15"</code> = terkunci otomatis setelah 15 menit tanpa aktivitas.</span>
</div>
<x-dl::lock-screen :user="['name' => 'Demo Admin', 'photo' => \App\Support\DemoData::avatarPhoto('#e2e8f0', '#475569')]" :idle="0" logout-action="#" />
```

A project layout (once per page, after the shell) with a 15-minute idle lock and a real sign-out route. The route names are the project's own:

```blade
<x-dl::lock-screen :user="['name' => auth()->user()->name]" :idle="15" :logout-action="route('logout')" id="app" />
```

The unlock listener in a project script file. The endpoint `/lock/verify` is the project's own: it reads the user from the session, checks the hash, throttles, and answers `204` on success, `422` on a wrong password, `429` when throttled and `401` or `419` when the session ended:

```js
document.addEventListener('dl:unlock', async e => {
  e.preventDefault();                       // nothing unlocks until the server says yes
  const lock = e.target.dlLock;
  const r = await fetch('/lock/verify', {method: 'POST', credentials: 'same-origin',
    headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': window.DL_CSRF || ''},
    body: JSON.stringify({password: e.detail.password})});
  if (r.status === 204) lock.unlock();
  else if (r.status === 401 || r.status === 419) location.href = '/login';
  else if (r.status === 429) lock.fail('Too many attempts. Wait a moment.');
  else lock.fail();                         // default: "Wrong password."
});
```

## Related

- `x-dl::login-box`: the sign-in screen; `x-dl::forgot-box` if the user forgot the password.
- `x-dl::field.password`: the field used for the unlock password (`size="lg"`, eye button).
- `x-dl::button`: the trigger (`data-dl-lock`) and the Unlock button.
- `x-dl::layer-shell`: the application shell, whose own `logout-action` is the sign-out for the whole app.
