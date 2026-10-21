## Overview

`<x-dl::register-box>` is the content of the "Create account" screen. It is placed in `x-dl::frame`, like the login box, and draws a complete sign-up form: account type, full name, e-mail (with a Verify button), a username that is filled automatically from the e-mail, a WhatsApp number with an OTP step, an ID number (NIK or employee number), a password with confirmation and the submit button. After submitting, a "Registration submitted" panel replaces the form: a new account waits for approval by an administrator, who decides its group and permissions.

It is a front end with two jobs: collect and pre-check the data in the browser, and give the project three hooks to verify the e-mail, send and check a one-time code. It sends nothing, checks no code and creates no account by itself. Without an application handler it runs as a PREVIEW: every verification succeeds and the done panel appears (see "The verification steps" and "Final submit").

Pick another object when you need:

- `x-dl::login-box`: sign in with an existing account.
- `x-dl::forgot-box`: reset a forgotten password.
- `x-dl::form` or `x-dl::wizard`: an inside-the-app form (this box is for visitors who have no session).

### Generated markup (main parts)

```html
<div class="dl-loginbox dl-auth" data-dl-register>
  <div class="dl-loginbox__top"> language flags </div>
  <form class="dl-loginbox__form" method="POST" [action="..."] novalidate data-rg-form>
    <input type="hidden" name="_token" value="...">
    <div data-rg-box>
      heading, sub line, approval note, alert (data-dl-alert)
      reg_type (select), name, email + [data-rg-verify-email], username (readonly),
      whatsapp (phone) + [data-rg-send-otp], [data-rg-otp] (hidden: otp + [data-rg-verify-otp]),
      id_no [data-rg-idno], password + password_confirmation, submit, Back link
    </div>
    <div class="dl-auth__done" data-rg-done hidden> check mark, "Registration submitted", Back link </div>
  </form>
</div>
```

The `action` attribute is written only when the `action` prop is not empty. The form has `novalidate`.

## Props

Constructor of `Devlib\Components\RegisterBox` (3 props). Blade names are kebab-case; use `:prop="expr"` for arrays and expressions. `can="module.action"` works as on every DevLib component. The fields inside are fixed; only the three props below change the box.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `action` | `string`, `''` | URL that receives the POST. Empty: the `action` attribute is not written and the box runs in script-driven mode (preview, or an application handler on `dl:register`). A non-empty value switches the final submit to a real, native browser POST (see "Final submit"). |
| `login-url` | `string`, `'#'` | `href` of both "Back" links (the form and the done panel), i.e. the sign-in screen. Default `#` leaves a dead link. |
| `types` | `array`, `['customer' => 'Customer', 'supplier' => 'Supplier', 'internal' => 'Internal User']` | Options of the "Register as" select (`reg_type`): key = the posted value, value = the label. The select starts on the hard-coded value `customer`. The key `internal` has a built-in meaning: with `internal` the ID field is labelled "Employee ID (NRP)" and accepts any non-empty text; with any other key it is "NIK (ID card no.)" and must have 16 digits. |

### Fields and what they post

| Name | Field | Notes |
|---|---|---|
| `reg_type` | `x-dl::field.select`, required | key of `types` |
| `name` | `x-dl::field.text`, required | full name |
| `email` | `x-dl::field.text` `type="email"`, required | case badge off |
| `username` | `x-dl::field.text`, read-only | filled by script from the e-mail (see Behavior) |
| `whatsapp`, `whatsapp_country` | `x-dl::field.phone` `kind="mobile"`, required | number in international form, plus the country code |
| `otp` | `x-dl::field.otp` | hidden row; the code typed (default 6 boxes) |
| `id_no` | `x-dl::field.text`, required | NIK or NRP |
| `password`, `password_confirmation` | `x-dl::field.password` `autocomplete="new-password"`, required | strength message in the hint line |
| `_token` | CSRF | always |

## Behavior

### Live behavior in the browser

- **Username from e-mail.** On every input in `email` the readonly `username` is set to the part before the `@`, lower-cased, keeping only `a-z 0-9 . _ -`. Typing in `email` also marks the e-mail as not verified again, shows the Verify button again and clears its message. The value is a suggestion only: it is posted, but the server must build the real username itself.
- **ID field.** Changing `reg_type` re-labels `id_no` (NIK or NRP), changes its placeholder and re-checks it. For a non-internal type it shows "Valid NIK format." (green) or "NIK must be 16 digits." (red) while typing; only digits are counted.
- **Password check** (`passCheck`, shared with `x-dl::forgot-box`): at least 8 characters, at least one letter and one digit; the confirm field must equal it. Messages: "Min. 8 characters.", "Add letters & numbers.", "Strong password.", "Passwords match.", "Passwords do not match yet.". Failures are shown red on the error line; the two green messages ("Strong password.", "Passwords match.") replace the hint text, and the original hint returns when the field is emptied.
- **WhatsApp.** Typing in the number resets its verified state and brings back the Send OTP button.
- **Required fields.** Fields whose label shows `*` are checked on submit with "Complete all required (*) fields." under each empty field and in the alert.

### The verification steps (e-mail, WhatsApp, OTP)

Each step is a cancelable event on the root element (it bubbles; use `document.addEventListener` or `el.addEventListener`). The script first does its own cheap check, then dispatches the event; if no listener calls `preventDefault()` the step is treated as PASSED (preview).

| Step (button) | Own check first | Event (`detail`) | If not prevented (preview) |
|---|---|---|---|
| Verify e-mail | `name@host.tld` shape, else "Invalid email format." | `dl:verify-email` `{email}` | e-mail marked verified, message "Email verified.", button hidden |
| Send OTP | WhatsApp number not empty, else "Enter a WhatsApp number first." | `dl:send-otp` `{phone}` | OTP row shown, message "OTP sent to WhatsApp.", focus in the first box. NOTHING is sent. |
| Verify OTP | code at least 4 characters, else "OTP code is incomplete." | `dl:verify-otp` `{phone, code}` | WhatsApp marked verified: ANY 4+ characters pass, OTP row and Send button hidden |

An application that does the verification itself calls `preventDefault()` in its listener, talks to the server, and then reports the result with `el.dlRegister.verified(kind, ok, message)`:

- `kind` is `'email'` or `'wa'`; `ok` is `true` or `false`; `message` is optional text (the default texts above are used when it is omitted). `verified('email', true)` hides the Verify button; `verified('wa', true)` hides the OTP row and the Send OTP button; with `ok = false` the message is shown in red.
- `el.dlRegister.done()` hides the form and shows the done panel (for a flow that submitted by script).

### Final submit

On submit the script checks, in this order: all required fields filled, the ID rule (16 digits unless the type is `internal`), the password rule. Any failure cancels the submit and shows "Complete all required (*) fields.". Then it requires both e-mail and WhatsApp to be marked verified; otherwise it cancels and shows "Verify email and WhatsApp before submitting.".

If everything passes it dispatches the cancelable `dl:register` with `{data}`, where `data` is every field except `_token` and anything whose name contains `password` (so `reg_type`, `name`, `email`, `username`, `whatsapp`, `whatsapp_country`, `otp`, `id_no`). Then:

| `action` prop | Listener | Result |
|---|---|---|
| empty | none (not prevented) | preview: the form is hidden and the done panel is shown, nothing is sent |
| empty | calls `preventDefault()` | stays on the form: the application sends the data itself (fetch) and calls `el.dlRegister.done()` or shows its own error |
| set | any (prevented or not) | the browser performs the normal POST, including the password fields (`dl:register` cannot cancel it) |

### Language

The box uses `authLang(root)` and the table `AUTH_T` (keys `r_*`, ids `id` and `en`, in `resources/assets/js/core.js`). Every element with `data-i18n="r_..."` (heading, sub line, approval note, labels, the Verify, Send OTP and Submit buttons, the done panel) is re-translated from `AUTH_T[lang][key]` when the page language changes (`dl:lang`) and once at load. The field `i18n` prop writes that key on the label. Texts without a key (hints, placeholders, the "Back" link, the select options) keep the language they were rendered in until the next page load. The messages produced by the script (`at('key')`) use the language at the time they are shown. Unknown language codes fall back to `id` for messages (`at()`), and leave rendered text unchanged for labels.

### Accessibility as implemented

Labels are real `<label for>`; autocomplete hints are set (`email`, `new-password`). Errors and hints use the field lines (no `role="alert"`, no `aria-live`); the alert box is a plain `div`. The hidden OTP row and done panel use the `hidden` attribute.

## Data and events

### What the server receives

With `action` set, the browser sends one POST (form-encoded) with `_token`, `reg_type`, `name`, `email`, `username`, `whatsapp`, `whatsapp_country`, `otp` (empty if the code row was never used), `id_no`, `password`, `password_confirmation`. Nothing in the request proves that the e-mail or the WhatsApp number was verified: those flags exist only in the browser.

### What the server must do and return

1. Revalidate everything: required fields, unique e-mail and username, the type is one of the allowed keys, ID format, password policy (the 8 characters / letter + digit rule is only a hint) and `password_confirmation`.
2. Verify the e-mail and the WhatsApp number on the SERVER: issue the verification link or code, store only a hash with an expiry and an attempt limit, bind it to the number or e-mail it was sent to, and check it again when the registration is posted (or accept only a signed token created by the earlier step). Rate-limit "send" per number, e-mail and IP; compare codes in constant time; never return the code in a response.
3. Build the username on the server; ignore the posted `username` and `reg_type` for privileges. A new account must be created inactive, awaiting an administrator (the box says so); the type must not grant access by itself.
4. Answer a native POST with a redirect to a confirmation page (the script does not show the done panel after a native POST). For a script-driven flow answer JSON and then call `dlRegister.done()`. Use the same wording for "e-mail already registered" and "accepted" if you want to avoid revealing which e-mails exist.

### Events and JS API

| Name | Where | Purpose |
|---|---|---|
| `dl:verify-email` `{email}` | root, cancelable, bubbles | verify the e-mail |
| `dl:send-otp` `{phone}` | same | send the one-time code |
| `dl:verify-otp` `{phone, code}` | same | check the code |
| `dl:register` `{data}` | same | the final data (without passwords) |
| `el.dlRegister.verified(kind, ok, message)` | root property | report a verification result |
| `el.dlRegister.done()` | root property | show the done panel |

Markup hooks: `data-dl-register` (root), `data-rg-form`, `data-rg-box`, `data-rg-done`, `data-rg-verify-email`, `data-rg-send-otp`, `data-rg-otp`, `data-rg-verify-otp`, `data-rg-idno`. `el.dlComponent` is the `Register` instance (`ok` flags, `internal()`, `chkId()`). It is mounted by `boot.js` (`Devlib.mountOne("Register")`).

## Rules and pitfalls

- "Verified" is client state. A visitor can post the form without ever clicking Verify (or can set the flags in the console). The server must refuse the registration unless it knows, from its own storage, that the e-mail and the number were confirmed.
- Preview accepts anything. Before go-live, make sure the application listeners call `preventDefault()` on `dl:verify-email`, `dl:send-otp` and `dl:verify-otp`, otherwise every visitor passes all checks.
- A `dl:register` listener cannot stop a native submit when `action` is set; choose between `action` (server POST) and no `action` (script flow), do not mix them.
- The OTP code is a secret with a short life. Do not log it, do not put it in a URL, and limit attempts. The box accepts a code of 4 or more characters on the client, but the field has 6 boxes by default; the server checks the real length and value.
- The posted `username` is only a suggestion generated from the e-mail; two e-mails with the same local part produce the same username. Resolve duplicates on the server.
- The NIK rule is 16 digits (an Indonesian ID number). Other countries need a different check on the server and a project theme text; the box has no prop for it. The only built-in alternative is the `internal` type (free text).
- Keep the keys `customer` and `internal` in `types` if you change the list: the select starts on `customer` and the NIK/NRP switch tests for `internal`. If `customer` is not among the keys the select shows nothing selected but still posts the value `customer` (the hidden input is written from the fixed value).
- The names of the fields are fixed (`password`, `password_confirmation`, `email`, `whatsapp`, `id_no`, ...); the script reads them by name. Do not rename them.
- Do not pass the password through the events: the box deliberately leaves it out of `dl:register`. A script-driven flow that needs it must read the field itself and send it over HTTPS in the request body.
- Texts: this screen has no `error` prop. Server validation errors must be shown by your own handling (for example re-render the page with the errors), because the fields inside cannot be given an `error` from outside.
- Code oddity (not fixed): there is no API to open the OTP row from the application. When a listener cancels `dl:send-otp`, only the preview path unhides `[data-rg-otp]` (`resources/assets/js/components/register.js:32`) and `el.dlRegister` offers just `verified` and `done` (`:53`), so a real flow has to remove the `hidden` attribute of `[data-rg-otp]` itself.
- Code oddity (not fixed): `verified('wa', false)` without a message shows "OTP code is incomplete." (`register.js:68`, key `otp_bad`), which is misleading for a wrong or expired code; always pass your own message.
- Code oddity (not fixed): when `action` is set and a listener cancels `dl:register`, the script still lets the browser submit (`register.js:50`, `preventDefault` only when there is no `action`).

## Example

Catalog demo (preview, no `action`): the box in a frame with a wide box width; `url('/lib/login-1')` is the sign-in page of the catalog.

```blade
<x-dl::frame bg="devlib" bg-order="random" box="right" color="light" box-width="520px" widgets="brand@top-left, clock@top-right, quote@bottom-left">
    <x-dl::register-box :login-url="url('/lib/login-1')" />
</x-dl::frame>
```

A project page with a real POST and only two account types (keep `customer` as the default key):

```blade
<x-dl::frame bg="devlib" box="right" color="light" box-width="520px" widgets="brand@top-left, clock@top-right">
    <x-dl::register-box :action="route('register.store')" :login-url="route('login')" :types="['customer' => 'Customer', 'internal' => 'Internal User']" />
</x-dl::frame>
```

An application listener in a project script file (not inline in the view; lint `L002`). The endpoints are the project's own and return JSON:

```js
document.addEventListener('dl:send-otp', async e => {
  e.preventDefault();
  const box = e.target.closest('[data-dl-register]');
  const r = await fetch('/register/otp', {method: 'POST', credentials: 'same-origin',
    headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': window.DL_CSRF || ''},
    body: JSON.stringify({phone: e.detail.phone})});
  if (r.ok) box.querySelector('[data-rg-otp]').hidden = false;
  else box.dlRegister.verified('wa', false, 'Could not send the code. Try again later.');
});
```

## Related

- `x-dl::frame`: holds the box (background, widgets, box width).
- `x-dl::login-box`, `x-dl::forgot-box`: the other auth screens; same `authLang` mechanism and the same password rule (forgot).
- `x-dl::lang-switch`: the flags at the top of the box.
- `x-dl::field.phone`, `x-dl::field.otp`, `x-dl::field.password`, `x-dl::field.select`, `x-dl::field.text`: the fields used inside.
- `x-dl::alert`: the message line of the box; `x-dl::button`: Verify, Send OTP, Submit and Back.
- `skills/devlib/inheritance.md`: login and security screens are references; real protection lives in the project.
