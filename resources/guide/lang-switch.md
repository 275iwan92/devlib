## Overview

`<x-dl::lang-switch>` is the language picker: a row of flag buttons, one per available display language. Clicking a flag changes the display language of the device (cookie `dl_lang`, localStorage `dl-lang`), updates every dictionary text on the page (`x-dl::lang-text`) without reloading, and for a signed-in user also saves the choice to the account and reloads the page so that server-rendered text follows. The list of languages is data, not markup: by default it is `Locale::languages()` (the application fills it, for example from its languages table), so a new language appears by itself.

Use it where the user may change language: guest pages (login, register, forgot password) and the Language page of the Layer Menu profile panel (both already include it). Do NOT add a second one to a normal screen; the profile panel is the place. For one dictionary phrase use `x-dl::lang-text`; for translating screen labels nothing is needed (labels are written in English and translated by the dictionary at render).

Generated markup:

```html
<div class="dl-langs" data-dl="x-dl::lang-switch" data-dl-class="LangSwitch ← DevlibComponent" data-dl-src="sys_languages + sec_user.lang_code (aplikasi pemakai)" data-dl-langs data-reload>
  <button type="button" class="dl-flag is-active" data-lang="en" title="English">EN</button>
  <button type="button" class="dl-flag" data-lang="id" title="Bahasa Indonesia">ID</button>
</div>
```

`data-reload` is written only when `reload` is true. Extra HTML attributes (`id`, `class`, `style`, `data-*`) go on the root; `can="module.action"` is supported.

## Props

Constructor of `Devlib\Components\LangSwitch` (3 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `langs` | `array`, `[]` | The buttons as `code => [label, title]`, for example `['en' => ['EN', 'English']]`. Empty (default) = built from `Locale::languages()`: the label is the upper-case code (`EN`, `ID`, `DE`; flag emoji are not used by default because Windows draws them as letters such as `GB`), and the title is the language `name` (or the code). A project may still pass its own label, even a flag emoji, through this prop. The title is the button tooltip and is not translated. |
| `active` | `?string`, `null` | The code marked `is-active` at render. `null` = the current display language `Locale::lang()` (print language, user account, device cookie, then application default). |
| `reload` | `?bool`, `null` | Whether choosing a language saves it to the account and reloads the page. `null` = `true` when a user is signed in (`auth()->check()`), otherwise `false`. `true` writes `data-reload`. Force `:reload="false"` for a switcher that must only change texts in place, `:reload="true"` to always reload. |

## Behavior

Script `Langs` (`resources/assets/js/components/langs.js`, selector `[data-dl-langs]`):

1. Clicking the flag of the already active language does nothing.
2. Otherwise it sets `Devlib.lang`, re-marks `is-active` on every `[data-dl-langs] .dl-flag` of the page, sets `<html lang>`, and dispatches `dl:lang` on `document` with `detail` = the code.
3. The core listener of `dl:lang` stores the choice (localStorage `dl-lang`, cookie `dl_lang`, one year, path `/`, `SameSite=Lax`) and rewrites the text of every `[data-dl-t]` element (what `x-dl::lang-text` renders). Other components also listen to `dl:lang` (preset button labels, auth screen labels, clock, greeting).
4. Without `data-reload` it stops here (guest pages: in-place change; server-rendered text changes on the next page load because the cookie is read by the server).
5. With `data-reload`: if `window.DL_LANG_URL` (config `devlib.lang_url`) is set, it POSTs `_token` (`window.DL_CSRF`) and `lang` with `X-Requested-With: XMLHttpRequest` to save the language on the account, then reloads the page whether the request succeeded or failed; without that URL it just reloads.
6. With `data-reload`, if the Layer Menu shell reports tabs with unsaved content (`shell.dirtyTabs()`), a confirm dialog asks once (`lang_reload_title` / `lang_reload_msg` / `lang_reload_ok`); on Cancel the language is not changed.

Styling: `.dl-langs` is a right-aligned flex row with 7 px gap; `.dl-flag` is a bordered button, the active one has the accent border and ring. Font size follows `--dl-font-scale`.

Language and text on screen: the flags and titles come from data and are not translated. Accessibility as implemented: native buttons (Tab, Enter/Space) with a `title`; the flag emoji is the only visible content and there is no `aria-label`, `aria-pressed` or group role, so screen readers read the emoji.

## Data and events

- No form field: the buttons are `type="button"` and nothing is submitted with a form.
- Event: `dl:lang` on `document`, `detail` = language code. Listen with `document.addEventListener('dl:lang', e => ...)`. It fires before the save request and the reload.
- Persistence: cookie `dl_lang` (read by `Locale::lang()` on the server for guests), localStorage `dl-lang`, and for signed-in users the account language through `devlib.lang_url` and the application's `Locale::langUsing()` resolver.
- `el.dlComponent` on the root is the `Langs` instance. The root has no `data-dl-js` (no PHP `jsClass()`), so it cannot be swapped per tag; register a different JS class by selector if a project needs another behaviour.
- `source()` is `sys_languages + sec_user.lang_code (aplikasi pemakai)`; it only labels the developer catalog.

## Rules and pitfalls

- Put it only where language choice belongs (guest pages and the profile Language page). Several switchers on one page stay in sync (all `.dl-flag` are re-marked) but are redundant.
- The list is data: add languages through `Locale::languagesUsing()` / the application's languages table, not by passing `langs` on each screen, unless a screen truly needs a fixed subset.
- `lang` values must exist in `Locale::languages()`; the server ignores a cookie language it does not know and falls back to the application default.
- For a signed-in user without `devlib.lang_url`, the page reloads but the choice is only in the device cookie; `Locale::lang()` prefers the account language, so the old language may return. Set `lang_url` and `Locale::langUsing()` in the application.
- `reload` false on a signed-in screen changes only `[data-dl-t]` texts; labels rendered by the server stay in the old language until the next load.
- Do not rely on the flag for accessibility; the title is the only text.
- Code oddity (not fixed): `src/Components/LangSwitch.php:20` (comment) and `resources/assets/js/components/langs.js` refer to config `devlib.lang_url`, but the package ships no config file that defines it; only `src/Components/ThemeBoot.php:42` reads it (`window.DL_LANG_URL`), so the application must define it.
- Code oddity (not fixed): `src/Components/LangSwitch.php:11` names the users as "i18n, misc" (demos); the real in-repo users are `resources/views/components/login-box.blade.php`, `forgot-box.blade.php`, `register-box.blade.php` and `layer-shell.blade.php` (Language page).

## Example

Demo (`site/resources/views/demos/i18n.blade.php`):

```blade
<x-dl::lang-switch />
<x-dl::card title="Contoh">
    <p class="dl-note"><x-dl::lang-text key="welcome" />! · <x-dl::lang-text key="language" />: <b><x-dl::lang-text key="dirty_title" /></b></p>
</x-dl::card>
```

Fixed list, in-place change only:

```blade
<x-dl::lang-switch :langs="['en' => ['EN', 'English'], 'id' => ['ID', 'Bahasa Indonesia']]" active="en" :reload="false" />
```

## Related

- `x-dl::lang-text`: a dictionary phrase that changes live when this switch fires `dl:lang`.
- `x-dl::layer-shell`: shows the switch on the profile panel Language page.
- `x-dl::login-box`, `x-dl::forgot-box`, `x-dl::register-box`: guest screens that include the switch.
- `x-dl::button`: preset button labels also follow `dl:lang`.
- `skills/devlib/components.md`: generated prop list.
