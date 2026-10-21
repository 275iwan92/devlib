## Overview

`<x-dl::forgot-box>` is the content of the "Forgot password" screen, placed in `x-dl::frame` like the login box. It has two modes, chosen with the `mode` prop:

| `mode` | Flow |
|---|---|
| `email` (default) | The visitor types an e-mail address and presses "Send reset link". The screen always answers with the same sentence, "If the email is registered, a reset link has been sent.", whether or not the address exists. The link and the page where the new password is chosen belong to the project. |
| `questions` | No e-mail. Step 1 "Verify": username, date of birth, registered phone, mother's maiden name and the answer to one security question. Step 2 "New password": new password with confirmation. Then "Password updated". |

The box is a front end. It checks that required fields are filled and that the new password follows the rule (8 characters, a letter and a digit, confirmation equal), then asks the project's server what to do through events. It never sends an e-mail, never compares an answer and never changes a password. Without an application handler the box runs as a PREVIEW in which every step succeeds (see "Simulated or real").

Pick another object when:

- the user is signed in and wants to change the password: use an inside-the-app form with `x-dl::field.password`;
- the user knows the password and only needs to unlock the screen: `x-dl::lock-screen`;
- you need sign in or sign up: `x-dl::login-box`, `x-dl::register-box`.

### Generated markup

```html
<div class="dl-loginbox dl-auth" data-dl-forgot data-mode="email|questions">
  <div class="dl-loginbox__top"> language flags </div>
  <form class="dl-loginbox__form" method="POST" [action="..."] novalidate data-fg-form>
    <input type="hidden" name="_token" value="...">
    <h2 data-i18n="f_heading">Forgot password</h2>
    <!-- email mode -->
    <div data-fg-step="1"> sub line, email field, submit </div>
    <div class="dl-auth__ok" data-fg-step="done" hidden> the fixed confirmation sentence </div>
    <!-- questions mode -->
    <div class="dl-auth__steps"> data-fg-dot="1" / "2" </div>  <alert data-dl-alert>
    <div data-fg-step="1"> login, birth_date, phone, mother_name, security_answer, Next </div>
    <div data-fg-step="2" hidden> new_password, new_password_confirmation, Save </div>
    <div class="dl-auth__done" data-fg-step="done" hidden> "Password updated" </div>
    <a class="dl-auth__back">Back</a>
  </form>
</div>
```

The `action` attribute is written only when the `action` prop is not empty. The form has `novalidate`.

## Props

Constructor of `Devlib\Components\ForgotBox` (4 props). Blade names are kebab-case. `can="module.action"` works as on every DevLib component.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `mode` | `string`, `'email'` | `email` or `questions`. The view draws the e-mail form only for the exact value `email`; ANY other value (for example a typo such as `question`) draws and behaves as `questions`. Written to `data-mode` on the root, which the script reads. |
| `action` | `string`, `''` | URL for the POST. Only `mode="email"` uses it: with an `action` the e-mail form is submitted as a normal browser POST (see "Simulated or real"). In `questions` mode the attribute is written but never used, because the script always cancels that submit. |
| `login-url` | `string`, `'#'` | `href` of the "Back" link (to the sign-in screen). Default `#` leaves a dead link. |
| `question` | `string`, `'What was the name of your first pet?'` | Label of the security-answer field (`security_answer`) in `questions` mode. It is one fixed question for the whole application (the same text is shown before the user is identified); it is translated like any label. Ignored in `email` mode. |

### Fields

| Mode | Name | Field |
|---|---|---|
| `email` | `email` | `x-dl::field.text` `type="email"`, `size="lg"`, required |
| `questions`, step 1 | `login` | `x-dl::field.text`, required (username) |
| | `birth_date` | `x-dl::field.date`, required |
| | `phone` | `x-dl::field.phone` `kind="mobile"`, required |
| | `mother_name` | `x-dl::field.text`, required |
| | `security_answer` | `x-dl::field.text`, label = `question`, required |
| `questions`, step 2 | `new_password`, `new_password_confirmation` | `x-dl::field.password`, `autocomplete="new-password"`, required |

## Behavior

### Client checks

On every submit the script clears the old field messages, then checks the visible step: every field of that step whose label has `*` must have a value, otherwise "Complete all required (*) fields." appears under it and nothing else happens. There is no e-mail format check and no answer check on the client.

New password (step 2): the same rule as the register box (`passCheck`): at least 8 characters, at least one letter and one digit, and the confirmation must equal it. The messages appear live on `input` of both fields. "Save" only continues when the rule is met.

### Steps (`questions` mode)

`el.dlForgot.step(n)` shows the step `n` (`1`, `2` or `'done'`), updates the numbered pills at the top (`is-active`, `is-done`) and focuses the first input of the step. After "Password updated" no pill remains active. The "Back" link stays visible in every step.

### Simulated or real

Every transition is a cancelable event on the root element (it bubbles). If no listener calls `preventDefault()` the box acts as a preview and moves on by itself.

| Mode and moment | Event (`detail`) | If not prevented (preview) | What an application does |
|---|---|---|---|
| `email`, no `action`, "Send reset link" | `dl:forgot` `{email}` | shows the "If the email is registered..." sentence | `preventDefault()`, call the server, then `el.dlForgot.step('done')` (always, even when the address is unknown) |
| `email`, with `action` | none | the browser posts the form natively; the script does NOT validate anything first | the server answers; the page is replaced, so the server must render its own confirmation |
| `questions`, step 1 "Next" | `dl:recover` `{answers: {login, birth_date, phone, mother_name, security_answer}}` | goes to step 2 with ANY answers | `preventDefault()`, send the answers, then `step(2)` on success or `fail(message)` |
| `questions`, step 2 "Save" | `dl:reset` `{password}` | shows "Password updated" | `preventDefault()`, send the password with the proof from step 1, then `step('done')` |

`el.dlForgot.fail(message)` shows `message` (default "The data does not match any account.") in the alert at the top of the box (red). The alert exists only in `questions` mode; in `email` mode `fail()` has nothing to show. The alert is hidden again on the next submit.

### Language

`authLang(root)` and the table `AUTH_T` (keys `f_*`, plus `l_email`, `l_login`, `r_conf`; ids `id` and `en`, in `resources/assets/js/core.js`) re-translate every element that has a `data-i18n` key: heading, sub lines, step pills, field labels set through the `i18n` prop, Next, Save, Send reset link, the confirmation texts. It runs once at load and on each `dl:lang` document event (the flags of `x-dl::lang-switch`). Not re-translated live: hints, placeholders, the `question` label, the "Back" link. Messages produced by the script (`at('key')`, for example "Complete all required (*) fields.") use the language at the time they appear. The flags set the `dl_lang` cookie, so the next server render follows.

### Accessibility as implemented

Real `<label for>` labels, `autocomplete` hints (`email`, `username`, `new-password`), `hidden` attributes for the inactive steps and focus moved to the first field of the new step. The alert has no `role="alert"` or `aria-live`; the step pills are plain `span`s.

## Data and events

### What the server receives and must return

- `email` mode with `action`: one form-encoded POST with `_token` and `email`. Respond with a redirect or a page that shows the same neutral sentence whether the address exists or not (same HTTP status and similar time), generate a single-use token with an expiry, store only its hash, and e-mail the link. Throttle per address and per IP. The page the link opens (new password) is outside this box.
- `email` mode without `action`, and all of `questions` mode: nothing is posted by the box. The application listens to the events above and calls its own endpoints (POST, JSON, CSRF header `X-CSRF-TOKEN` from `window.DL_CSRF`). Never put the answers or the password in a URL or query string.

### Security flow of `questions` mode

The answers, the username and later the new password are three separate events; the box carries no token between them, and `dl:reset` has only `{password}`. The application must therefore tie the steps together on the server:

1. On `dl:recover` the server compares all five values with the stored account. On success it creates a short-lived, single-use proof (a server-side session value or a random token kept only in a script variable and sent back with the reset); on failure it answers with one neutral message, throttles by username and IP, and locks or delays after repeated failures. It must not say which of the answers was wrong, and must not reveal whether the username exists.
2. On `dl:reset` the server checks the proof, applies the password policy again (the 8-character rule is only a hint), saves the hash, invalidates the proof and other sessions, and answers success.
3. `step(2)` is only a visible panel: anyone can call it in the console. Never treat "reached step 2" as authorisation.

The answers (date of birth, mother's maiden name, phone) are personal data. They travel only in the request body over HTTPS; do not log them and do not echo them in responses. Knowledge-based answers are weak factors; combine them with throttling, notification of the account owner, and, where possible, the e-mail mode instead.

### Events and JS API

| Name | Where | Purpose |
|---|---|---|
| `dl:forgot` `{email}` | root, cancelable, bubbles | e-mail mode request |
| `dl:recover` `{answers}` | same | step 1 of questions mode |
| `dl:reset` `{password}` | same | step 2 of questions mode |
| `el.dlForgot.step(n)` | root property | show step `1`, `2` or `'done'` |
| `el.dlForgot.fail(message)` | root property | show an error in the alert (questions mode) |

Markup hooks: `data-dl-forgot`, `data-mode`, `data-fg-form`, `data-fg-step`, `data-fg-dot`. `el.dlComponent` is the `Forgot` instance (`mode`, `cur`, `step()`, `fail()`). It is mounted by `boot.js` (`Devlib.mountOne("Forgot")`).

## Rules and pitfalls

- The confirmation in `email` mode must be the same for known and unknown addresses. Do not add "No account found" anywhere (alert, redirect flash, status code).
- Preview passes everything. Before go-live, every transition you use must have a listener that calls `preventDefault()` and reports through `step()` / `fail()`; otherwise the box says "Password updated" without changing anything.
- With `action` in `email` mode the script does not run the required check at all: an empty or invalid address is posted. Validate on the server and re-render with the neutral message.
- In `email` mode there is no alert element; `fail()` is a no-op there. Show server problems yourself.
- The `question` is one global text. A per-user question would need the username first (and would reveal whether it exists); do not change `question` per request to reveal an account.
- `mode="questions"` makes the password the only secret the box handles in the page: do not log it, and do not include it in URLs, error messages or analytics.
- Spell `mode` exactly. A typo silently selects the questions flow.
- The set of fields and their names are fixed; the script reads `login`, `birth_date`, `phone`, `mother_name`, `security_answer`, `new_password`, `new_password_confirmation` by name.
- Code oddity (not fixed): in `email` mode with an `action`, the submit handler returns before any check (`resources/assets/js/components/forgot.js:16`), so the required-field message and the neutral "done" step are never shown by the box; the browser leaves the page.
- Code oddity (not fixed): in `questions` mode the `action` attribute is written (`resources/views/components/forgot-box.blade.php:3`) but the handler always calls `preventDefault()` (`forgot.js:17`), so `action` has no effect there.
- Code oddity (not fixed): `dl:reset` carries only the new password (`forgot.js:24`), no reference to the verification of step 1; the box leaves proof handling entirely to the application.

## Example

Catalog demo, e-mail mode (preview, no `action`):

```blade
<x-dl::frame bg="devlib" box="center" color="light" widgets="brand@top-left, clock@top-right">
    <x-dl::forgot-box mode="email" :login-url="url('/lib/login-1')" />
</x-dl::frame>
```

Catalog demo, questions mode with a custom question:

```blade
<x-dl::frame bg="devlib" box="right" color="light" box-width="520px" widgets="brand@top-left, clock@top-right, quote@bottom-left">
    <x-dl::forgot-box mode="questions" :login-url="url('/lib/login-1')" question="Nama sekolah dasar Anda?" />
</x-dl::frame>
```

A project page with a real POST (e-mail mode). The route names are the project's own and must answer with the same neutral page for every address:

```blade
<x-dl::frame bg="devlib" box="center" color="light" widgets="brand@top-left, clock@top-right">
    <x-dl::forgot-box mode="email" :action="route('password.email')" :login-url="route('login')" />
</x-dl::frame>
```

Application listeners for the questions flow, in a project script file (not inline in the view; lint `L002`). The endpoints are the project's own:

```js
let proof = null; // single-use value returned by the server after step 1
const post = (url, body) => fetch(url, {method: 'POST', credentials: 'same-origin',
  headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': window.DL_CSRF || ''},
  body: JSON.stringify(body)});

document.addEventListener('dl:recover', async e => {
  e.preventDefault();
  const r = await post('/password/recover', e.detail.answers);
  if (r.ok) { proof = (await r.json()).proof; e.target.dlForgot.step(2); } else e.target.dlForgot.fail();
});

document.addEventListener('dl:reset', async e => {
  e.preventDefault();
  const r = await post('/password/reset', {proof, password: e.detail.password});
  if (r.ok) e.target.dlForgot.step('done'); else e.target.dlForgot.fail('Could not change the password. Start again.');
});
```

## Related

- `x-dl::frame`: holds the box.
- `x-dl::login-box`: its `forgot` part links here (`forgot-url`); `x-dl::register-box` shares the password rule and the language mechanism.
- `x-dl::lang-switch`: the flags at the top.
- `x-dl::field.text`, `x-dl::field.date`, `x-dl::field.phone`, `x-dl::field.password`: the fields inside.
- `x-dl::alert`, `x-dl::button`: the message line and the buttons.
- `skills/devlib/inheritance.md`: login and security screens are references; the project owns the real protection.
