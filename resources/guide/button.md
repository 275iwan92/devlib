## Overview

`<x-dl::button>` is the standard button of DevLib: one `<button>` (or an `<a>` when `href` is given) with a variant colour, a size, an optional icon and an optional **preset**. A preset is a fixed action (Save, Cancel, Delete, Print, Approve ...) that brings its own label, colour, icon, `type` and, for two of them, a keyboard shortcut, so every screen shows the same word and look for the same action.

Use it for every action button, link-looking action and form submit. Do not write a raw `<button>` (lint rule `L001` reports raw `button`, `form`, `input`, `table` ... in screen views). Pick another object when:

- the action is "go back to the previous screen": `x-dl::back-button` (label locked to "Back");
- one button must offer several choices: `x-dl::menu-button` (one button, a menu) or `x-dl::split-button`;
- the button has only an icon and no text: `x-dl::icon-button`.

### Generated markup

```html
<!-- no href -->
<button type="button|submit|reset" class="dl-btn dl-btn--primary dl-btn--md [dl-btn--block]"
        data-dl="x-dl::button preset=save variant=primary size=md" data-dl-class="Button" data-dl-src="—"
        [data-i18n="..."] [data-dl-preset="save"] [data-dl-key="ctrl+s"]>
  <span class="dl-btn__icon"><i class="fa-solid fa-floppy-disk"></i></span>Save
</button>

<!-- with href: same classes, an anchor, no type, no data-dl-key -->
<a href="/somewhere" class="dl-btn dl-btn--secondary dl-btn--md" data-dl="..." data-dl-preset="cancel">...</a>
```

Extra HTML attributes (`id`, `class`, `style`, `data-*`, `disabled`, `target`, `onclick` ...) are merged onto this one root element (`class` is appended to the `dl-btn` classes). `can="module.action"` is also accepted: without the ability the button is not rendered at all.

## Props

Constructor of `Devlib\Components\Button` (9 props). Blade names are kebab-case; bind non-strings with `:prop="expr"` (`:block="true"`; a bare `block` also works).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `label` | `string`, `''` | Text of the button, written escaped before the slot content. With a `preset` and an empty label, the preset label is used (language dependent for a few presets, see the table). A non-empty label always wins over the preset label. `Button` is excluded from the automatic dictionary translation (`TEXT_PROPS = []`): button words are written in English and stay as written. Only when the app sets `config('devlib.translate_fixed')` (a one-language app) is the final label passed through `Lang::text()`. |
| `variant` | `string`, `'primary'` | Colour style, written as class `dl-btn--<variant>`. Styles that exist in the CSS: `primary`, `secondary`, `success`, `danger`, `ghost` (transparent), `plain` (surface with an inset border), `link` (looks like a text link: no background, no border, no padding), `danger-soft`. The value is not validated; an unknown value gives a button with no colour style. With a `preset`, the preset variant replaces the value only while `variant` is still `'primary'` (see pitfalls). |
| `size` | `string`, `'md'` | Class `dl-btn--<size>`: `sm` (compact, used in card headers and table rows), `md` (standard), `lg` (large, login mockup). Other values are written as-is and have no style. |
| `type` | `?string`, `null` | The `type` of the `<button>`: `button`, `submit` or `reset`. `null` becomes `'button'`, unless a preset gives another value (`save` and `submit` are `submit`). Ignored when `href` is set (an `<a>` has no type). Use `type="submit"` for a button that must send its form. |
| `href` | `?string`, `null` | When filled, the object renders an `<a href>` instead of a `<button>`: same look, navigates like a link. Also pass `target="_blank"` etc. as normal attributes. A link has no `type`, ignores `disabled`, and does not get the keyboard shortcut attribute. |
| `icon` | `?string`, `null` | Icon before the text, inside `<span class="dl-btn__icon">`. Three forms: a meaning from `Devlib\Icon::MAP` (`save`, `delete`, `print`, `back`, `new`, `edit`, `search`, ...: preferred, so one change in `Icon` updates every button); a Font Awesome class starting with `fa-` (`fa-bolt`); anything else is printed as plain text (an emoji). Icons are always `fa-solid`. A preset supplies its own icon when `icon` is empty. |
| `block` | `bool`, `false` | Adds `dl-btn--block` (`width:100%`). Used for full-width buttons in narrow panels and login forms. |
| `i18n` | `?string`, `null` | Written to `data-i18n` on the button. Only the auth screens (login, register, forgot) use it, so `authLang` can re-translate the label when the language is switched. Normal screens never set it. |
| `preset` | `?string`, `null` | Name of a fixed action from `Button::PRESETS`. Fills `label`, `variant`, `icon` and `type` (only the ones the screen did not set). Also writes `data-dl-preset="<name>"` (used by the form scripts and the language switch). An unknown name is ignored for the values but still written to `data-dl-preset`. |

### Presets

`[label ID / label EN, variant, icon, type]`. Presets marked "same" have the same word in both languages.

| Preset | Label (ID / EN) | Variant | Icon | Type | Shortcut |
|---|---|---|---|---|---|
| `save` | Save | primary | save | submit | `ctrl+s` |
| `cancel` | Cancel | secondary | cancel | button | |
| `close` | Tutup / Close | secondary | close | button | |
| `confirm` | Confirm | success | confirm | button | |
| `delete` | Delete | danger | delete | button | |
| `new` | + New Record | primary | none | button | `alt+n` |
| `apply` | Terapkan / Apply | primary | none | button | |
| `print` | Print | secondary | print | button | |
| `email` | Email | secondary | email | button | |
| `submit` | Ajukan / Submit | primary | submit | submit | |
| `approve` | Setujui / Approve | success | approve | button | |
| `reject` | Tolak / Reject | danger | reject | button | |
| `hold` | Tahan / Hold | secondary | hold | button | |

The label language is decided at render time from `Locale::lang()` (`en` gives the English column, anything else the Indonesian column).

## Behavior

### Variants, sizes and state

- `.dl-btn` is an inline-flex box with a 6px gap between icon and text, bold text, no wrapping. Hover darkens or lightens the background per variant.
- `disabled` (a normal HTML attribute on a `<button>`) dims the button to 45% and removes pointer events (`.dl-btn:disabled`).
- `.is-busy` is added by script (see below) and shows the progress cursor.
- Inside `.dl-form__foot` (the footer of `x-dl::form`) buttons get tighter padding; in an `x-dl::action-bar` they sit in a row with a gap.

### Keyboard shortcuts

The presets `save` (`ctrl+s`) and `new` (`alt+n`) write `data-dl-key` on a `<button>`. A global key handler (`core.js`) finds the first visible, enabled element with that `data-dl-key` and clicks it (`preventDefault`, so the browser's own Ctrl+S is suppressed). The `?` key (outside inputs) lists all visible `data-dl-key` buttons in a dialog. You can set `data-dl-key="ctrl+enter"` yourself on any button: the format is `ctrl+`, `alt+`, `shift+` followed by the lowercased key name, in that order (the Cmd key counts as `ctrl`).

### Submit lock (double click protection)

For every `submit` event that is not default-prevented, the pressed submit button (or the form's first `[type=submit]`) is disabled, gets `is-busy`, and its content is replaced by a spinner and the word "Processing" (dictionary key `processing`). To keep a button untouched, add the attribute `data-dl-no-busy`. A page that stays open after the submit (for example a download) therefore needs `data-dl-no-busy`, otherwise the button stays locked.

### Preset label and language switch

When the page language is switched at run time (event `dl:lang`), the last text node of every `[data-dl-preset]` element is rewritten from a built-in table: `save`, `cancel`, `close`, `delete`, `apply`, `print`, `submit`, `approve`, `reject`, `hold`. `confirm`, `new` and `email` are not in the table and keep their text (their words are the same in both languages anyway).

### Language and text on screen

- The `label` is not translated by DevLib (button words are fixed English terms). Only `translate_fixed` apps run it through the application dictionary.
- Wrap a label that holds a data value in `raw()` if it could be picked up by the dictionary; the `raw` marker is stripped before display in any case.
- The icon is decorative (`<i>` without text); the button text is the accessible name. An icon-only button (empty `label`, no slot) has no accessible name: use `x-dl::icon-button`, which has a `title`.

### Accessibility as implemented

A native `<button>` or `<a>`: focusable, Enter/Space or Enter activate it, and `disabled` is respected. There are no `aria-*` attributes added. A `variant="link"` button is still a button element (not a link) unless `href` is set.

## Data and events

- A `<button type="submit">` inside a `<form>` / `x-dl::action-form` / `x-dl::form` posts that form; the button itself has no `name`/`value` unless you pass them as attributes (then `e.submitter` posts them).
- DevLib adds no custom event to the button. Listen to the native `click` (bubbles), or use the `data-dl-*` hooks other objects understand on a button: `data-dl-open="modal-id"` (opens a modal or drawer; add `data-dl-close-modal` on the same element to close the current one first), `data-dl-close-modal` (closes the surrounding modal/drawer), `data-dl-open-item="menu code"` with `data-label` and `data-url` (opens a work tab inside the Layer Shell, plain link outside), `data-dl-leave` (asks the dirty-guard before leaving), `data-dl-save` (marks the button that the dirty-guard dialog "Save" clicks).
- For the dirty-guard, a `[data-dl-save]` or `[data-dl-preset=save]` button inside the guarded form is the one used when the user answers "Save".
- `el.dlComponent` does not exist for the button: it has no JS class; behaviour is global (`core.js`).
- Hooks: `.dl-btn`, `.dl-btn__icon`, `data-dl-preset`, `data-dl-key`, `data-i18n`.

## Rules and pitfalls

- Never write a raw `<button>` or `<a class="btn">` in a screen: use `x-dl::button` (lint `L001`).
- Use `preset` for the fixed actions. Do not retype "Save" / "Cancel" labels or recolour them: the label word, colour and icon are LOCKED project rules (same word on every screen).
- A preset's variant is replaced only while `variant` equals the default `'primary'`. Writing `preset="delete" variant="primary"` still gives the red delete style, and you cannot force `primary` onto a non-primary preset; use another variant or no preset.
- An explicit `label` beats the preset label, but a language switch at run time (`dl:lang`) overwrites the label text of the buttons that have a preset in the script table with the preset word. Do not combine `preset` with a custom label if the screen can switch language live (see the oddity).
- `href` turns the button into a link: no `type`, no shortcut, no `disabled`. A "Back" link in a form footer should use `x-dl::back-button`, not a button with `href`.
- Pass the icon by meaning (`icon="save"`), not by Font Awesome name, whenever a meaning exists in `Devlib\Icon::MAP`.
- Submit lock: a submit button turns into "Processing" after the first submit; set `data-dl-no-busy` where the page does not reload.
- `variant` and `size` are not validated: a typo (`variant="primry"`) silently renders an unstyled button.
- Code oddity (not fixed): `src/Components/Button.php:53` says action terms are always English in both languages, but `close`, `apply`, `submit`, `approve`, `reject` and `hold` have an Indonesian label for non-`en` pages (`Button.php:57,60,63-66`).
- Code oddity (not fixed): `resources/assets/js/core.js:332-336` rewrites the last text node of every `[data-dl-preset]` element on `dl:lang`, also when the screen set its own `label` (or when the last text node is slot text), so a custom label on a preset button is replaced by the preset word after a live language switch.
- Code oddity (not fixed): `Button.php:36` ignores an unknown `preset` name for label, variant, icon and type, but the view still writes `data-dl-preset="<unknown>"`.

## Example

Presets, variants and sizes (shape of the catalog demo `button`):

```blade
<div class="dl-row">
    <x-dl::button preset="save" /> <x-dl::button preset="cancel" /> <x-dl::button preset="delete" />
    <x-dl::button preset="approve" /> <x-dl::button preset="reject" /> <x-dl::button preset="hold" />
    <x-dl::back-button fallback="#" />
</div>
<div class="dl-row">
    <x-dl::button label="Primary" /> <x-dl::button variant="secondary" label="Secondary" />
    <x-dl::button variant="danger" label="Danger" /> <x-dl::button variant="ghost" label="Ghost" />
    <x-dl::button variant="link" href="#" label="Link" />
    <x-dl::button size="sm" label="Kecil" /> <x-dl::button size="lg" label="Besar (login)" />
</div>
```

Submit button with an icon, and a link-style button that opens another page in a new tab:

```blade
<x-dl::action-form action="{{ route('generator.generate', $req->id_request) }}">
    <x-dl::action-bar align="start">
        <x-dl::button type="submit" icon="fa-bolt" label="Generate Now" />
        <x-dl::button icon="fa-up-right-from-square" label="Open" :href="$req->screen_url" target="_blank" />
    </x-dl::action-bar>
</x-dl::action-form>
```

Footer of a card (Save with `ctrl+s`):

```blade
<x-dl::action-bar>
    <x-dl::button preset="cancel" />
    <x-dl::button preset="save" />
</x-dl::action-bar>
```

## Related

- `x-dl::action-bar`: the row that holds the buttons at the foot of a card or form.
- `x-dl::action-form`: the small `<form>` wrapper for a button or a few fields.
- `x-dl::back-button`: the locked Back button.
- `x-dl::icon-button`, `x-dl::menu-button`, `x-dl::split-button`: icon-only button, button with a menu, button with a default action plus a menu.
- `x-dl::form`: its footer buttons (Save, Undo, Confirm ...) are built from these presets; its dirty-guard looks for `[data-dl-preset=save]`.
- `skills/devlib/components.md`, `skills/devlib/grid-form-flow.md`: project rules and the button set per document status.
