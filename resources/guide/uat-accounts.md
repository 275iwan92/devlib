## Overview

`<x-dl::uat-accounts>` is a small round help icon (a headset) that opens a popup listing the TEST accounts of an environment used for user acceptance testing (UAT): one row per account with a label (usually a role) and its login, and a line saying that all accounts share one password, which is printed in the popup. Testers can read which login to type without asking anyone.

It is a display helper for test environments only. It creates no account, checks nothing and does not fill the login form: the logins and the shared password are plain text that you hand to it, and the real accounts must exist in the user table of that environment. Because it prints the password in clear text for every visitor of the page, it must never be shown in production.

Pick another object when:

- you need to hand a real person their first password: `x-dl::login-card` (printable, one person, one time);
- you want a general help or tooltip bubble: `x-dl::tooltip` or `x-dl::hint`;
- you want the credentials of a real environment on screen: do not; use a password manager.

### Generated markup

```html
<div class="dl-uat" data-dl-uat>
  <button type="button" class="dl-uat__icon" data-dl-uat-toggle title="Help: test accounts (UAT)"><i class="fa-solid fa-headset"></i></button>
  <div class="dl-uat__panel">
    <div class="dl-uat__head">Test Accounts (UAT)</div>
    <p class="dl-uat__note">All passwords are the same: <code>(the password prop)</code></p>
    <table class="dl-uat__table"><tbody>
      <tr><td>label</td><td><code>login</code></td></tr> ...
    </tbody></table>
  </div>
</div>
```

The head line also carries a test-tube emoji. The root carries `data-dl`, `data-dl-class` and `data-dl-src` like every component. Extra attributes (`class`, `style`, `id`) go on the root `div`.

## Props

Constructor of `Devlib\Components\UatAccounts` (2 props). Blade names are kebab-case; both need `:prop="expr"` (an array and, usually, a config value). `can="module.action"` works as on every DevLib component, but a visitor on a login page has no abilities, so `can` is not a way to hide it.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `accounts` | `array`, built-in sample list of seven role logins (levels 1 to 4) | Map of `label => login`. Each entry becomes one table row: the label on the left, the login in a `<code>` on the right. The labels and logins are data: HTML-escaped and not translated. An empty array draws an empty table. The built-in list is only a sample for the catalog; replace it with the logins that really exist in your UAT environment. |
| `password` | `string`, a built-in placeholder sample | The one password shown in the line "All passwords are the same:". It is printed in clear text in the page source, one value for all rows. The component cannot show a different password per account. The built-in value is a sample placeholder for the catalog, not a real credential: always override it with the value of your UAT environment, and keep that value only in that environment's configuration. |

## Behavior

### Open and close

- Clicking the icon toggles the class `is-open` on the root; the panel is `display:none` until then, then drawn absolutely under the icon (width 290 px, left aligned, above other content, `z-index: 20`).
- Clicking inside the open panel keeps it open.
- Clicking anywhere else on the page closes it (a click listener on `document`).
- The icon button handles its click with `stopPropagation`, so other document-level click handlers do not run for that click (for example the one in `boot.js` that closes open dropdown panels).
- There is no Escape handling, no `aria-expanded` / `aria-controls`, and the panel is not a dialog. The icon is a native `<button type="button">` with a `title`, so it is reachable and activated with the keyboard.
- Nothing in the panel is interactive: clicking a row neither copies the login nor fills any input.

### Position

In the standard login box the icon is the first item of the top row (left), with the language flags at the right (`.dl-uat` has `margin-right:auto`). Standalone, the root is itself `position: relative`; put it where there is room for the 290 px panel below the icon.

### Language and text

The title of the icon, the panel head and the sentence "All passwords are the same:" follow the page language through the dictionary. `accounts` and `password` never do.

### Where the accounts come from

Nowhere inside DevLib. The block is only a list that you maintain by hand or from a config file. The real accounts must be created by the project in the test environment (a seeder that runs only there), with the shared password set to the value you print. If an account is changed or removed, update the list: the component cannot know.

## Data and events

- Nothing is posted, fetched or stored. The component has no form and no network access.
- It emits no `dl:*` event.
- Hooks: `data-dl-uat` (root), `data-dl-uat-toggle` (icon button), `is-open` (state class). `el.dlComponent` is the `Uat` instance; it is mounted by `boot.js` (`Devlib.mountOne("Uat")`). Override with `data-dl-js` as for other components.
- `x-dl::login-box` draws it for its `support` part (see below).

## Rules and pitfalls

- Production: never ship this popup. It tells every visitor, logged in or not, valid logins of the system and a working password. DevLib does not guard it: there is no environment check and no lint rule. The source note of the class says to remove it from the login before a production release.
- Guard it in the view, for example with `@unless (app()->isProduction())`, or drive it from a config flag that is `false` everywhere except UAT, so one forgotten line cannot publish the accounts.
- The shared password must be unique to the test environment: do not reuse any real password, and rotate or delete the test accounts when UAT ends.
- Test accounts should have the least permissions that still allow the test, no real data and no access to other systems. Do not use real people's names or e-mail addresses as labels or logins.
- The `support` part of `x-dl::login-box` draws this component without props, so it always shows the built-in sample list and the sample password. To show the logins of your environment, do not use the `support` part; render `<x-dl::uat-accounts :accounts=... :password=... />` yourself where the screen needs it.
- A page that shows the popup must also be protected against caching by shared proxies, since the password is in the HTML.
- Code oddity (not fixed): the defaults of both props are written in the class (`src/Components/UatAccounts.php:19-24`), including a sample password. Any screen that forgets `:password` (or uses the `support` part) displays that sample as if it were the environment's real password; nothing warns the developer.
- Code oddity (not fixed): the head line hard-codes an emoji in the markup (`src/Components/UatAccounts.php:43`), which the dictionary does not control.

## Example

Catalog demo: the `support` part of the login box (built-in sample list, preview only).

```blade
<x-dl::frame bg="devlib" bg-order="random" box="right" color="light" resizable widgets="brand@top-left, clock@top-right, quote@bottom-left">
    <x-dl::login-box show="support, lang, title, username, password, forgot, button, register" :action="$action" :error="$error" :username="$username" />
</x-dl::frame>
```

Standalone, with the accounts of a UAT environment (fictional logins), guarded for production. `config('uat.password')` is a project config key filled only in the UAT environment's own configuration:

```blade
@unless (app()->isProduction())
    <x-dl::uat-accounts :accounts="['Level 1 — Operator' => 'operator', 'Level 2 — Supervisor' => 'supervs', 'Level 4 — Director' => 'director']" :password="config('uat.password')" />
@endunless
```

## Related

- `x-dl::login-box`: the `support` part draws this icon in its top row.
- `x-dl::login-card`: the right object to give a real new user their first password.
- `x-dl::lock-screen`, `x-dl::forgot-box`, `x-dl::register-box`: the other credential-related screens.
- `x-dl::field.password`: the field the testers type the password into.
- `skills/devlib/inheritance.md`: login and security screens are references, the project owns the real protection.
