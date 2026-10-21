## Overview

`<x-dl::error-page>` is the standard error screen: a centred card with an icon, the status code in large type, a title, an explanation, and two buttons (`Back` and `Home`). It has built-in texts, icons and colours for `403`, `404`, `419`, `500`, `503` and `offline`, and a generic fallback for any other code. With `full` on (default) it fills the viewport with a soft gradient; with `full="false"` it is a plain card for previews inside a page (the catalog uses this to show all six side by side).

Use it for the Laravel error views (`resources/views/errors/404.blade.php`, ...) and for an offline page. Pick another object when:

- the page is valid but has no data: `x-dl::empty-state`;
- a message belongs inside a normal screen (validation, saved, warning): `x-dl::alert`;
- the user must wait or stay locked out: `x-dl::lock-screen`.

Generated markup:

```html
<div class="dl-error dl-error--full" data-dl="x-dl::error-page code=404" data-dl-class="ErrorPage ← DevlibComponent" data-dl-src="—">
  <div class="dl-error__card">
    <span class="dl-error__icon dl-error__icon--accent"><i class="fa-solid fa-compass"></i></span>
    <div class="dl-error__code">404</div>                 <!-- empty for code="offline" -->
    <h1 class="dl-error__title">Page not found</h1>
    <p class="dl-error__msg">The address you opened does not exist or has been moved.</p>
    <div class="dl-error__actions">
      <a class="dl-back" href="/" data-dl-back title="Back to the previous screen"><span>Back</span><i class="fa-solid fa-arrow-left"></i></a>
      <a class="dl-btn dl-btn--secondary ..." href="/"><i class="fa-solid fa-house"></i> Home</a>
    </div>
  </div>
</div>
```

## Props

Constructor of `Devlib\Components\ErrorPage` (5 props, none inherited). Use `:full="false"` for the boolean.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `code` | `string`, `'404'` | Selects the built-in preset from `ErrorPage::CODES`: `403`, `404`, `419`, `500`, `503`, `offline` (lower-case, exact match). Printed as the big code text (empty text for `offline`). Any other value (for example `429`) uses the fallback preset below and the value is still printed as the code. |
| `title` | `?string`, `null` | Heading. `null` uses the preset title. Translated through the dictionary (standard text prop); a given text replaces the preset title entirely. |
| `message` | `?string`, `null` | Explanation paragraph. `null` uses the preset message. Translated. Replaces the preset message entirely. |
| `home` | `string`, `'/'` | URL of the Home button and the fallback of the Back button (used when the browser cannot go back). Not translated. |
| `full` | `bool`, `true` | `true` adds `dl-error--full`: `min-height:100vh/100dvh` and a gradient background, for a real error page. `false` removes it, for an inline preview. |

Presets (`ErrorPage::CODES`, `code => [title, message, icon, tone]`, texts in English and translated at render):

| Code | Title | Icon | Tone |
|---|---|---|---|
| `403` | Access denied | `fa-lock` | `warn` |
| `404` | Page not found | `fa-compass` | `accent` |
| `419` | Session expired | `fa-clock-rotate-left` | `warn` |
| `500` | A system error occurred | `fa-triangle-exclamation` | `danger` |
| `503` | Under maintenance | `fa-screwdriver-wrench` | `accent` |
| `offline` | No connection | `fa-wifi` | `danger` |
| (other) | An error occurred / "Please try again." | `fa-circle-exclamation` | `danger` |

Inherited from `DevlibComponent` (no constructor props): `can="module.action"` hides the whole page for users without the ability (not meaningful for a real error page); extra attributes (`class`, `id`, `style`, `data-*`) are merged onto the root `div`.

## Behavior

- **Icon and colour.** The tone gives the icon circle its colour: `danger` red, `warn` amber, `accent` the theme accent. Colours follow the theme tokens, so light, dark and custom themes work; sizes follow `--dl-font-scale`.
- **Code size.** `46px` bold with letter spacing; the code `div` stays in the DOM (empty) for `offline` so the layout is the same.
- **Buttons.** `Back` is `x-dl::back-button :fallback="$home"`: the script goes to the previous screen when the visitor came from the same site (and `window.DL_BACK_HISTORY` is not `false`), otherwise follows the link to `home`. `Home` is an `x-dl::button` (secondary, house icon) with `href=home`. Both labels are fixed English words (`Back` is always `Back`; `Home` goes through the dictionary only when the app sets `devlib.translate_fixed`).
- **Language.** Preset title/message and the tooltip of Back are translated at render time. Texts you pass in `title` / `message` are translated too when they exist in the dictionary; otherwise they are shown as given.
- **Previews.** In `site/resources/views/demos/error-pages.blade.php` the six presets are put in a `div.dl-errors-grid` (auto-fill columns of 320px minimum; the cards lose their shadow and max width) with `:full="false"` and `home="#"`.
- **Accessibility as implemented.** The title is the `<h1>` of the page. There is no `role="alert"`, no `aria-live`, and the icon `<i>` is decorative without `aria-hidden`. Back and Home are real links, keyboard reachable.
- **HTTP status.** The component draws a page only; it does not set a status code, a `Retry-After` header or a title tag. The status comes from Laravel (the error view file name) and the `<title>` from your layout.

## Data and events

- Input: only the props. Nothing is posted, no request is made, no custom `dl:*` event is emitted.
- Only the Back button has a script (`resources/assets/js/components/back.js`, selector `[data-dl-back]`); the card itself has none, so there is no `el.dlComponent` on the error root.
- Constants available to PHP: `ErrorPage::CODES` (array above) and `info()` (returns `[title, message, icon, tone]` for the current code, falling back to the generic preset). The demo iterates `array_keys(ErrorPage::CODES)`.

## Rules and pitfalls

- A real error view must load the full layout (CSS, JS, fonts, theme script) because the error page is rendered by Laravel after the failure. Keep the view free of anything that queries the database or needs the session: on `500` and `419` those may be exactly what failed.
- Wrap the component in your normal layout for `full` pages; use `full="false"` anywhere else (inside a card, grid or preview), otherwise the page grows to the full viewport height.
- `code` is case-sensitive and exact: `Offline`, `OFFLINE` or `429` get the generic preset (generic title, red exclamation icon).
- `home` is not validated or escaped as a URL beyond normal attribute escaping. Use a trusted route (`url('/')`, `route('home')`).
- Custom `title` / `message` replace the preset text completely; if you only need to change the wording for one code, pass both.
- The `Back` button uses `history.back()` when the visitor came from your site, so after a `404` reached through a link it returns to the page that had the broken link. It may also go back to a page that itself errors; if that matters, point your own button to `home`.
- Code oddity (not fixed): the header comment of `src/Components/ErrorPage.php:9` says the component is used by "403, 404, 419 and 2 others"; the repository contains `site/resources/views/errors/{403,404,419,500,503}.blade.php` and `demos/error-pages.blade.php`. There is no `errors/offline` view, so `offline` is only a preset: an offline page needs a service worker or a static page written by the app.
- Note: the icon gets the class `dl-error__icon--accent` for the `accent` tone (`ErrorPage.php:52`) but `devlib.css:965-976` has no such rule; `accent` works only because it is the default colour of `.dl-error__icon` (harmless).

## Example

The five Laravel error views of the catalog site follow this shape (`resources/views/errors/404.blade.php`):

```blade
@extends('layout', ['title' => '404'])
@section('body')
    <x-dl::error-page code="404" :home="url('/')" />
@endsection
```

Same for `403`, `419`, `500` and `503` (only the `code` changes). A custom message for a known code, and a code without a preset:

```blade
<x-dl::error-page code="403" title="Restricted area" message="Only administrators can open the settings." :home="route('dashboard')" />
<x-dl::error-page code="429" title="Too many requests" message="Please wait a minute and try again." :home="url('/')" />
```

Inline preview of all presets (as in the catalog demo):

```blade
<div class="dl-errors-grid">
    @foreach (array_keys(\Devlib\Components\ErrorPage::CODES) as $code)
        <x-dl::error-page :code="$code" :full="false" home="#" />
    @endforeach
</div>
```

## Related

- `x-dl::back-button`: the Back button of the card (label always `Back`).
- `x-dl::button`: draws the Home button.
- `x-dl::empty-state`: for pages that work but have nothing to show.
- `x-dl::alert`: for messages inside a normal screen.
- `x-dl::lock-screen`: when the session is locked rather than broken.
- `skills/devlib/components.md`: generated prop list.
