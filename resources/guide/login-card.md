## Overview

`<x-dl::login-card>` is a small printable card that hands a new employee (or any new user) their first sign-in details: name with code, username and the initial password, plus a note that the password must be changed at first login. It is meant to sit inside an `x-dl::modal` that the calling screen opens right after it created the login; the Print button prints only the card.

The card is a pure display. It does not create the login, does not read a database, does not store or hash anything and does not remember the password after the page is rendered. The password is passed in by the calling screen at the moment it renders the card (usually the one and only time it is known in clear text); once the page is gone, the card cannot be shown again.

Pick another object for other jobs:

- `x-dl::login-box`: the page where the user signs in with these details.
- `x-dl::form` with `x-dl::field.display`: show ordinary read-only record data.
- `x-dl::print-doc`: a full business printout (this card is only the credential slip).

### Generated markup

```html
<div class="dl-logincard" data-dl-logincard data-dl-js="LoginCard">
  <div class="dl-logincard__head">Login card</div>
  <div class="dl-logincard__body">
    <div class="dl-logincard__org">Brand name — hand this card to the employee</div>
    <div><small>Name</small><b>Budi Santoso (TM-00498)</b></div>
    <div><small>Username</small><span class="dl-logincard__mono">budi</span></div>
    <div><small>Initial password</small><span class="dl-logincard__mono">...</span></div>
    <div class="dl-logincard__note">The employee must change the password at first login. ...</div>
    <div class="dl-logincard__bar">
      <button type="button" data-lc-print>Print</button>
      ...the slot...
    </div>
  </div>
</div>
```

The organisation line uses the application identity (`Brand::identity()['name']`). All values are written with `{{ }}`, i.e. HTML-escaped.

## Props

Constructor of `Devlib\Components\LoginCard` (6 props). Blade names are kebab-case. The default slot is the place for extra buttons (for example Close); it is drawn after the Print button inside `dl-logincard__bar`. `can="module.action"` works as on every DevLib component.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `name` | `string`, `''` | Full name of the person. Data: HTML-escaped, not translated. |
| `code` | `string`, `''` | Employee or user code, shown in parentheses after the name. An empty `code` shows no parentheses. |
| `login` | `string`, `''` | The username the person signs in with. Shown in a large monospace line. |
| `password` | `string`, `''` | The initial password, in clear text, in a large monospace line. This is the only place the card needs a secret; see the security notes. Empty shows an empty line. |
| `title` | `string`, `'Login card'` | Heading in the dark header bar. It is a standard text prop, so it is translated through the dictionary. |
| `note` | `?string`, `null` | Text of the note under the details. `null` uses the standard sentence "The employee must change the password at first login. This password is shown only once: print or write it down before closing." (translated). A custom value is written as given and is NOT translated automatically. |

## Behavior

### Print

The Print button (`data-lc-print`) calls `window.print()` (the `LoginCard` script, `resources/assets/js/components/login-card.js`). The print layout is pure CSS (`@media print` in `devlib.css`): everything on the page becomes invisible except the card, which is pinned to the top left and made full width; the button bar and the note are hidden. The result is a one-card sheet with the organisation line, name, username and password.

The rule is `body:has(.dl-logincard)`, so it needs a browser that supports `:has()`.

### Language

The labels `Name`, `Username`, `Initial password`, `Print`, the organisation sentence and the default note follow the page language through the dictionary. `name`, `code`, `login` and `password` never do.

### Accessibility as implemented

Plain `div`s and one native button; the card has no role and no `aria-*`. The monospace lines are ordinary text. Nothing masks the password on screen; that is the purpose of the card.

## Data and events

Nothing is posted: the card has no form and no input. It emits no `dl:*` event. The only behaviour is the print click. `el.dlComponent` is the `LoginCard` instance with one method `print()`; override it with `data-dl-js` (the PHP side already writes `data-dl-js="LoginCard"`) if a project needs a different printing flow, for example a PDF route.

### What the calling screen must do

1. Create the login on the server (the project's own provisioning), generate the initial password and flag the account so that the first sign-in forces a password change.
2. Render the page or modal with the card for exactly that response, passing the clear-text password once, and never read it back from the database (the database must only hold the hash).
3. Not log, cache or flash the password anywhere else, and not put it in a URL.

## Rules and pitfalls

- The password is written into the HTML. Anyone who can read that response (browser history cache, proxy logs of a mis-configured server, an open DevTools, screen sharing, a printout left on the printer) can read it. Render the card only in the response to the creating action, never from a shared link, and make the initial password single-purpose (forced change at first sign-in, short expiry).
- Do not render the card into a modal that is already in the page while closed. A closed `x-dl::modal` is `display:none` but its content is still in the DOM, so the password would sit in the page source before anyone asks for it, and a normal Ctrl+P on that page prints a blank sheet (the print rule hides everything except a card that is not displayed). Fill the modal only after the login was created.
- The printed sheet leaves out the note (the print CSS hides `.dl-logincard__note`): the instruction that the password must be changed is not on paper. Put it in your own handover process if it matters.
- The card is not a security feature. It does not make a weak password strong; the server decides the password rules and the forced change.
- Treat the card as data about a person: use fictional names in demos, and do not pass personal data that the screen does not need.
- A custom `note` is not translated; write it in the language the card is printed in, or translate it yourself on the server.
- `title`, `name`, `code`, `login` and `password` are plain strings; arrays or models are not accepted.
- Code oddity (not fixed): `LoginCard::source()` names a project table and a project service class (`src/Components/LoginCard.php:37`), which the developer catalog shows as the data source of this neutral component. The comment header (`src/Components/LoginCard.php:9`) also names a project screen.

## Example

The catalog demo: a card for a fictional employee with a Close button in the slot.

```blade
<div class="dl-demo">
    <x-dl::login-card name="Budi Santoso" code="TM-00498" login="budi" password="Sample-0000">
        <x-dl::button type="button" variant="secondary" label="Close" />
    </x-dl::login-card>
</div>
```

With a custom heading and note (values come from the response of the action that created the login):

```blade
<x-dl::login-card title="New employee" :name="$employee->name" :code="$employee->code" :login="$created->login" :password="$created->plainPassword" note="Sign in and choose your own password." />
```

## Related

- `x-dl::modal`: the usual container; open it only after the login exists.
- `x-dl::button`: the Print button is an `x-dl::button type="button" icon="print"`; extra buttons go into the slot.
- `x-dl::login-box`: the screen where the person uses these details.
- `x-dl::field.password`: the field used when the person chooses their own password.
- `x-dl::brand`: where the organisation name on the card comes from (`Brand::identity()`).
