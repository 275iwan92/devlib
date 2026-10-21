## Overview

`<x-dl::alert>` is the inline message box of DevLib: a colored bar with a text (or rich content) that explains a state to the user right where it matters, for example a server error above a form, a success note after saving, or a notice that a screen is read-only. It has four tones (`danger`, `info`, `warn`, `success`).

An alert with no text and no slot content stays **hidden**. That is on purpose: the auth boxes (`x-dl::login-box`, `x-dl::register-box`, `x-dl::forgot-box`) render an empty `<x-dl::alert />` and their scripts fill it and show it later.

Use it for a message that belongs to the page or form. Pick another object when the need is different:

- A short message that disappears by itself is a toast, made by the JS helper `Devlib.h.toast(text, tone)`, not by this object.
- `x-dl::modal` when the user must answer before continuing.
- `x-dl::empty-state` when a list or screen has no data.
- `x-dl::hint` for quiet grey help text; the `hint` prop of a field for help under one input; the field `error` prop for the error of one input.

Generated markup (root only, there is no JS class):

```html
<div class="dl-alert dl-alert--danger is-show" data-dl="x-dl::alert tone=danger" data-dl-class="Alert" data-dl-src="—" data-dl-alert>Message text{slot content}</div>
```

`is-show` is written only when there is a `message` or non-empty slot. Without it the CSS rule `.dl-alert{display:none}` hides the box. `data-dl-alert` is the hook scripts use to find the box.

## Props

Constructor of `Devlib\Components\Alert` (2 props, nothing inherited from `DevlibComponent` besides the shared behavior below).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `tone` | `string`, `'danger'` | Color family. Valid: `danger` (red), `info` (blue), `warn` (amber), `success` (green). Adds class `dl-alert--<tone>`. Not translated. Any other string (for example `warning`, `error`) is written as a class that has no CSS, so the box shows with a plain 1px border and no tint: use exactly `warn`, not `warning`. Also appears in the catalog tag: `x-dl::alert tone=<tone>`. |
| `message` | `?string`, `null` | Plain text inside the box. Output with `{{ }}`, so HTML is escaped. Translated through the dictionary (`message` is in `TEXT_PROPS`); wrap data values in `raw()` (the helper, or `Lang::raw()`), for example `:message="raw($log)"`. A non-empty message (`'0'` counts as empty) turns `is-show` on. Can be combined with the slot: the message is printed first, then the slot. |
| `items` | `array`, `[]` | List of points printed as a bulleted list (`ul.dl-alert__list`) under the message, for example every validation error of a long form. Each item is escaped plain text, not translated (pass final text or `raw()`). A non-empty list turns `is-show` on. Printed between the message and the slot. |

Extra HTML attributes (`id`, `class`, `style`, `data-*`) are merged onto the root `div` (`class` is appended to the `dl-alert` classes). The attribute `can="module.action"` is supported (base class `DevlibComponent`): without the ability nothing is rendered at all, not even the empty box.

Slot: rich content. Slot content is NOT translated and NOT escaped (it is Blade you write), so escape data yourself and translate texts with `\Devlib\Lang::text('...')`. A slot with only whitespace counts as empty.

## Behavior

- **Visibility:** `.dl-alert` is `display:none`; `.dl-alert.is-show` is `display:block`. The decision is made at render (message or slot present). Scripts show and hide the box by toggling `is-show` and changing its `textContent`.
- **Tones:** each tone sets background, border color and uses the normal ink color for the text, so contrast follows the active theme and the dark theme through the theme tokens (`--dl-danger-soft`, `--dl-info-soft`, `--dl-amber`, `--dl-success`).
- **Size:** font is 13px times the user font scale (`--dl-font-scale`); padding `10px 12px`; bottom margin 16px.
- **Buttons in the slot:** `.dl-alert .dl-btn` gets a 6px left margin and vertical-align middle, so a small `x-dl::button` sits inline after the text.
- **Language:** `message` is translated at render to the page language. Texts written by scripts (the auth boxes) come from the script's own dictionary and follow the language switch.
- **Accessibility as implemented:** the box has no `role="alert"`, no `aria-live` and no icon. When a script shows it after the page has loaded, screen readers are not told. Add `role="alert"` through extra attributes (`<x-dl::alert role="alert" />`) if the box is filled dynamically for assistive technology. The tone is communicated by color only, so also write the meaning in the text.
- **Animation:** none. It appears and disappears at once.
- **Permissions:** `can="module.action"` hides it for users without the ability.

## Data and events

- Takes no arrays. Data enters as the `message` string or as slot markup.
- Posts nothing and emits no `dl:*` event. There is no JS class for Alert, so `el.dlComponent` is not set.
- Markup hooks: `data-dl-alert` (find the box), `.dl-alert--<tone>`, `.is-show`. The three auth scripts (`login-box.js`, `register.js`, `forgot.js`) read `[data-dl-alert]` inside their root and do `box.textContent = text; box.classList.toggle('is-show', !!text)`. Do the same for your own script:

```js
const box = form.querySelector('[data-dl-alert]');
box.textContent = 'Could not save the document.';
box.classList.add('is-show');   // remove 'is-show' to hide again
```

- To change the tone from script, swap the `dl-alert--<tone>` class yourself (a project script in the sibling package builds a new box that way: the class string `dl-alert dl-alert--<tone> is-show`).

## Rules and pitfalls

- Tones are exactly `danger | info | warn | success`. A typo gives an uncolored box without any error.
- `message` is escaped plain text; for bold text or buttons use the slot. Slot content is raw Blade: never place unescaped user data in it.
- `message` and slot texts that come from the database or a user must not go into the dictionary: use `raw()` for `message`; the slot is not translated anyway.
- `:message="session('status')"` is the normal pattern: `null` keeps the box hidden, so no `@if` is needed.
- An alert that is empty on purpose (filled by script) must keep the `data-dl-alert` hook; do not remove it from the root or the auth scripts cannot find it.
- Server validation messages of one field belong in the field `error` prop, not in an alert. Use an alert for a message about the whole form or page.
- No lint rule exists for this object (`lint()` returns no findings).
- Code oddity (not fixed): the `Alert` class has no JS twin, yet three scripts depend on its markup contract (`data-dl-alert` + `is-show`): `resources/assets/js/components/login-box.js:19`, `register.js:56`, `forgot.js:29`. Changing the root markup breaks them silently.
- Code oddity (not fixed): `layer-shell.js:316` checks `.dl-alert--danger.is-show` inside a tab iframe to decide whether to keep a failed login page; it relies on the class names above.

## Example

```blade
{{-- Plain messages (shape of the catalog demo) --}}
<x-dl::alert tone="danger" message="Username or password is wrong." />
<x-dl::alert tone="info" message="Information message." />
<x-dl::alert tone="warn" message="Fill in the height of every floor before the volume can be counted." />

{{-- From the session; stays hidden when empty (the @if is optional) --}}
<x-dl::alert tone="success" :message="session('status')" />

{{-- Data value inside the message: not translated, not stored in the dictionary --}}
<x-dl::alert tone="info" :message="raw($summary)" />

{{-- Rich content: bold text and an action button in the slot --}}
<x-dl::alert tone="info">
    {{ \Devlib\Lang::text('Choose the Part Role first.') }}
    <x-dl::button size="sm" variant="secondary" label="Choose Part Role" />
</x-dl::alert>

{{-- Empty on purpose, filled by script --}}
<x-dl::alert id="save-alert" />

{{-- Only for users who may see it --}}
<x-dl::alert tone="warn" can="finance.view" message="Closing period is near." />
```

## Related

- `x-dl::login-box`, `x-dl::register-box`, `x-dl::forgot-box`: render an empty alert and drive it from their scripts.
- `x-dl::hint`: quiet grey help text; use when nothing is wrong.
- `x-dl::empty-state`: a "nothing here" panel for lists and screens.
- `x-dl::modal`: when the user must confirm or answer.
- `x-dl::button`: the usual content of the slot (size `sm`).
- `x-dl::field.text` and the other `x-dl::field.*`: `error` and `hint` props for one input.
- `skills/devlib/components.md` (generated prop list), `skills/devlib/object.md` (translation of text props, `can`), `skills/devlib/inheritance.md` (use `Lang::text` / `Lang::raw` in views).
