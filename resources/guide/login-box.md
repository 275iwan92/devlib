## Overview

`<x-dl::login-box>` is the content of the sign-in screen. You do not build the form: you list the parts you want in the `show` prop (language switch, brand, title, username or e-mail, password, remember me, forgot link, button, register link, test-account help) and the box renders one `<form method="POST">` with a CSRF token and those parts in the order you wrote them. It is placed inside `x-dl::frame` (the background, widgets and the position of the box); the box itself has no background of its own.

The box is only a front end. It never checks a password, never starts a session, never throttles. The browser posts the form to the URL in `action`; the project's server logs the user in or sends the visitor back with an error code (see "What the server receives and must return").

Pick another object when the screen is not "enter credentials":

- `x-dl::register-box`: create an account (with e-mail and WhatsApp verification).
- `x-dl::forgot-box`: reset a forgotten password.
- `x-dl::lock-screen`: ask the already signed-in user for the password again after idle time.
- `x-dl::login-card`: the printable card that hands a new employee the first password.

### Generated markup

```html
<div class="dl-loginbox" data-dl-loginbox data-error="wrong">      <!-- data-error = the error prop -->
  <div class="dl-loginbox__top">                                    <!-- only with part lang and/or support -->
    <div class="dl-uat" data-dl-uat>...</div>                       <!-- part support, always first -->
    <div class="dl-langs" data-dl-langs>...</div>                   <!-- part lang -->
  </div>
  <form class="dl-loginbox__form" method="POST" action="..." novalidate>
    <input type="hidden" name="_token" value="...">
    ...the other parts, in the order of show...
  </form>
</div>
```

The root also carries `data-dl`, `data-dl-class` and `data-dl-src` (developer catalog and guide link). The form has `novalidate`: the browser shows no native validation bubbles.

## Props

Constructor of `Devlib\Components\LoginBox` (9 props). Blade names are kebab-case; use `:prop="expr"` for anything that is not a plain string. `can="module.action"` works as on every DevLib component (without the ability nothing is rendered).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `show` | `string`, `'lang, title, username, password, forgot, button, register'` | Comma separated list of the parts to draw, in drawing order. Valid names: `lang`, `support`, `brand`, `title`, `username`, `email`, `password`, `remember`, `forgot`, `button`, `register`. Names are trimmed; unknown names are silently dropped (`LoginBox::parts()`, an `array_intersect` against `PARTS`). A name written twice is drawn twice. `lang` and `support` are not part of the flow: they are always drawn in the top row (support first, then lang) wherever you write them. See the part table below. |
| `action` | `string`, `''` | The `action` of the form, i.e. the URL that receives the POST. Empty (`action=""`) posts to the current URL. The catalog demos pass `javascript:void(0)` so that the preview posts nothing. |
| `button` | `string`, `'Sign in'` | Label of the submit button (part `button`). It is a standard text prop and is translated through the dictionary. See the oddity below: the script overwrites this text when the page loads. |
| `error` | `?string`, `null` | An error CODE (not a message). Written to `data-error` on the root. The script shows the fixed message `Enter your username/email and password.` when the code is `empty`, and the fixed message `Incorrect username or password.` for any other non-empty value. `null` or `''` shows nothing. The message appears only inside the alert drawn by the `title` part. |
| `username` | `string`, `''` | Initial value of the `login` input of the `username` part (use it to refill after a failed attempt, usually `old('login')`). It does nothing for the `email` part. |
| `forgot-url` | `string`, `'#'` | `href` of the "Forgot password?" link (part `forgot`). With the default `#` the link is drawn but goes nowhere. |
| `register-url` | `string`, `'#'` | `href` of the "Register" link (part `register`). Same default behaviour. |
| `brand` | `?string`, `null` | Name given to the `x-dl::brand` of the `brand` part. `null` = the application identity (`Brand::identity()`, config `devlib.brand.*`, or the `devlib.brand` binding). |
| `tagline` | `?string`, `null` | Tagline of the same brand badge; `null` = identity tagline. |
| `uat-accounts` | `?array`, `null` | Accounts listed by the help icon (part `support`), in the shape `x-dl::uat-accounts` takes (`label => login`, or `label => ['login' =>, 'password' =>]` for click-to-fill rows). `null` = the built-in sample list. Not translated.

### The `show` parts

| Part | Draws | Posted field |
|---|---|---|
| `lang` | `x-dl::lang-switch` in the top row (flags). On a guest page it switches in place, without reload. | none (it sets the `dl_lang` cookie from JS) |
| `support` | `x-dl::uat-accounts` in the top row (the test-account help icon). No props are passed to it, so it always shows the built-in sample list. Remove before production. | none |
| `brand` | `x-dl::brand` badge (`tone="auto"`) with `brand` / `tagline`. | none |
| `title` | `<h2>` "Sign in", a sub line, and the `x-dl::alert` that receives the error message. | none |
| `username` | `x-dl::field.text` `name="login"`, label "Username or e-mail", icon `fa-user`, `size="lg"`, case badge off, `autocomplete="username"`, value from `username`. | `login` |
| `email` | `x-dl::field.text` `name="email"` `type="email"`, label "Email", icon `fa-envelope`, `size="lg"`, case badge off, `autocomplete="email"`. | `email` |
| `password` | `x-dl::field.password` `name="password"`, icon `fa-lock`, `size="lg"` (eye button included, `autocomplete="current-password"`). | `password` |
| `remember` | `x-dl::field.checkbox` `name="remember"`, text "Remember me". | `remember=1` only when ticked |
| `forgot` | link button "Forgot password?" to `forgot-url`. | none |
| `button` | submit button, large, full width, label from `button`. | none |
| `register` | divider "or" and the line "Don't have an account? Register" (link to `register-url`). | none |

Use either `username` or `email`, not both: the server then reads one identifier field. They post different names (`login` vs `email`).

## Behavior

### Client check before posting

On `submit` the script looks at every `.dl-field--lg` in the box (the username/e-mail and password fields). A field whose trimmed value is empty gets `is-invalid`; if any is empty the submit is cancelled (`preventDefault`) and the `empty` message is shown. Nothing is sent to the server in that case. This is only a convenience: it checks "not empty", nothing else (no format, no length). A value made only of spaces counts as empty (the submit is cancelled); a password with spaces around other characters is posted untrimmed. The server must validate again.

### Showing the server error

When the page is rendered with `:error="'wrong'"` (or any non-empty string) the script writes the fixed "Incorrect username or password." into the alert and shows it (`is-show`). The text of a Laravel validation message or a throttle message is never displayed; only the code decides which of the two fixed sentences appears. This is deliberate for the credentials case (the box never says whether the user exists), but it also means that a message such as "too many attempts" cannot be shown through `error`.

### Language switch (`data-i18n`, `LOGIN_T`)

Server-rendered text is already in the page language (dictionary `Devlib\Lang`, language from `print_lang` query, user language, the `dl_lang` cookie, then `devlib.lang`). To change language without reloading, the box uses a small two-language table (`LOGIN_T`, ids `id` and `en`, in `resources/assets/js/core.js`):

1. The parts that carry a `data-i18n` key are the `title` heading (`heading`), its sub line (`sub`), the username/e-mail and password labels (`l_login`, `l_email`, `l_pass`, set through the `i18n` prop of the field), the forgot link (`forgot`), the button (`btn`), the divider (`or`) and the register line (`no_acc`, `reg`).
2. The `x-dl::lang-switch` flags call `Devlib.lang = code`, set `<html lang>` and dispatch the document event `dl:lang`. The core also writes `localStorage dl-lang` and the cookie `dl_lang` (one year, `SameSite=Lax`), so the NEXT server render uses the same language.
3. On `dl:lang` (and once when mounted) the box replaces the `textContent` of every `[data-i18n]` element with `LOGIN_T[lang][key]` and refreshes the alert.

Only these keyed texts change in place. The "Remember me" text, placeholders and the support popup keep the language they were rendered in until the next page load. On a guest page the switch does not reload (`LangSwitch::reload` defaults to "a user is signed in"). Only `id` and `en` are in `LOGIN_T` (see the oddities).

### Accessibility as implemented

Labels are real `<label for>`; fields get `autocomplete` hints (`username`, `email`, `current-password`) so password managers work. The alert has no `role="alert"` or `aria-live`, and the box adds no `aria-*` attributes. Keyboard: normal form behaviour, Enter in a field submits.

## Data and events

### What the server receives and must return

The browser sends one POST to `action` with `application/x-www-form-urlencoded`:

| Field | When |
|---|---|
| `_token` | always (the box writes `@csrf`) |
| `login` | part `username` |
| `email` | part `email` |
| `password` | part `password` |
| `remember` = `1` | part `remember` and ticked (absent otherwise) |

DevLib does not trim or hash anything. The password travels in the request body, never in the URL.

The server must answer with a redirect, not with a JSON document:

- Success: regenerate the session id, store the user, redirect to the intended page. (The catalog site route does exactly this and is only a sample; it is bypassed by default for demos.)
- Failure: redirect back to the login page, keep the typed identifier (flash it as old input) and flash a code. The login view then passes `:error` (`'empty'` or `'wrong'`) and `:username` to the box. Use the same code for "unknown user" and "wrong password" so the page does not reveal which accounts exist, and apply a rate limit before checking the password.
- An expired CSRF token returns HTTP 419 from Laravel; the box has no handling for it.

### Events and JS

- The box emits no `dl:*` event of its own. It listens to `dl:lang` on `document`.
- `data-dl-loginbox` marks the root; `el.dlComponent` is the `LoginBox` instance (properties `alertBox`, `errCode`; methods `showError()`, `applyLang()`). Override with `Devlib.define('LoginBox', MyLoginBox)` or `data-dl-js` as for other components.
- The component is mounted by `boot.js` (`Devlib.mountOne("LoginBox")`).

## Rules and pitfalls

- A login screen is a view with `x-dl::frame` + `x-dl::login-box`: do not hand-write `<form>`, `<input>` or `<button>` tags there (lint `L001`), no inline `<script>` (`L002`), no hex colours (`L003`). There is no lint rule specific to this object.
- The sign-in logic belongs to the project. The `site/` login route is a demonstration (`DEVLIB_LOGIN_BYPASS`, sample accounts) and is not a security model; see `skills/devlib/inheritance.md`.
- `error` is a code, not text. Do not pass `$errors->first()` expecting the sentence to appear: any non-empty value is shown as "Incorrect username or password."
- The error is shown only if the `title` part is in `show` (the alert is drawn by that part). A box without `title` gives no visible message at all, besides the red border on empty fields.
- Keep the part `support` out of production. It lists sample accounts and a shared sample password in the page source for any visitor (see `x-dl::uat-accounts`).
- The `email` part posts `email` and has no initial value; the `username` prop does not apply to it. The server of a screen using `email` must read `email`, not `login`.
- `forgot-url` and `register-url` default to `#`: a `show` that contains `forgot` or `register` without the matching URL draws a dead link.
- The case badge is off on both identifier fields on purpose (logins are case-sensitive input, not names).
- `remember` only posts `remember=1`. Persistent login (a remember cookie, its lifetime) is entirely the project's job.
- Code oddity (not fixed): the `button` prop is overwritten when the page loads. The button carries `data-i18n="btn"` (`resources/views/components/login-box.blade.php:23`) and `applyLang()` replaces its text with `LOGIN_T[lang].btn`, `Sign in` or `Masuk` (`resources/assets/js/components/login-box.js:20`). The demo `login-2` passes `button="Masuk Aplikasi"` but the page shows `Masuk`.
- Code oddity (not fixed): `LOGIN_T[Devlib.lang]` has only `id` and `en`, but `x-dl::lang-switch` lists every language the project enables. For any other language `applyLang()` reads a key of `undefined` and throws (`resources/assets/js/components/login-box.js:19-20`). `register-box` and `forgot-box` are safe (`at()` falls back to `id`).
- Code oddity (not fixed): the divider gets `:i18n="\Devlib\Lang::text('or')"` (`resources/views/components/login-box.blade.php:25`), i.e. the already translated word, as its key. In an Indonesian page the key is `atau`, which does not exist in `LOGIN_T`, so the divider is not re-translated; only English pages (key `or`) work.
- Code oddity (not fixed): the part `support` renders `<x-dl::uat-accounts />` without props (`resources/views/components/login-box.blade.php:4`), so a project cannot give it its own `accounts` or `password` through this box.
- Code oddity (not fixed): the demo variant `login-3` uses the `email` part, which posts `email`, while the catalog login route reads `login` (`site/routes/web.php:26`); that variant would always answer `empty`. Only the default variant `login-1` matches the route.

## Example

Catalog demo `login-1` (default variant): box at the right of a random background, with the support icon, language flags, title, username, password, forgot link, button and register link. `$action`, `$error` and `$username` come from the login route.

```blade
<x-dl::frame bg="devlib" bg-order="random" box="right" color="light" resizable widgets="brand@top-left, clock@top-right, quote@bottom-left">
    <x-dl::login-box show="support, lang, title, username, password, forgot, button, register" :action="$action" :error="$error" :username="$username" />
</x-dl::frame>
```

A project login (no support icon, remember me, real routes). The route names are the project's own:

```blade
<x-dl::frame bg="devlib" box="right" color="light" widgets="brand@top-left, clock@top-right">
    <x-dl::login-box show="lang, brand, title, username, password, remember, forgot, button" :forgot-url="route('password.request')" :action="route('login.attempt')" :error="$error ?? ($errors->first() ? 'wrong' : null)" :username="old('login', '')" :brand="config('devlib.brand.name')" :tagline="config('devlib.brand.tagline')" />
</x-dl::frame>
```

Variant with e-mail as the identifier (the server reads `email`):

```blade
<x-dl::frame bg="devlib" box="left" color="navy" widgets="greeting@top-right, clock@top-right, weather@top-right, announce@bottom-right, quote@bottom-right" quote-interval="10">
    <x-dl::login-box show="lang, brand, title, email, password, remember, forgot, button" :action="$action" :error="$error" :username="$username" />
</x-dl::frame>
```

## Related

- `x-dl::frame`: the background, widgets and box position that hold the login box.
- `x-dl::register-box`, `x-dl::forgot-box`: the other two auth screens, same frame and language mechanism (`authLang`).
- `x-dl::lock-screen`: re-asks the password of a signed-in user.
- `x-dl::uat-accounts`: the test-account popup drawn by the `support` part.
- `x-dl::lang-switch`, `x-dl::brand`, `x-dl::alert`, `x-dl::button`, `x-dl::divider`: parts used inside.
- `x-dl::field.text`, `x-dl::field.password`, `x-dl::field.checkbox`: the fields used inside (`size="lg"`, `i18n`).
- `skills/devlib/examples/login.blade.php`, `skills/devlib/inheritance.md`: project example and the rule that login is a reference, not the project's security.
