## Overview

`<x-dl::env-badge>` prints a small line of environment text that the host application supplies: a suffix after the logo title (for example `- mockup ver 0.20`) or a host / IP line under the user name (for example `Host: HOST-01 / 10.0.0.10 · Client: 127.0.0.1`). It is plain text, with no pill, background or frame, and it renders nothing at all when there is no text, so a live site shows nothing.

DevLib does not know the text. The application binds it in the container (`devlib.env_title`, `devlib.env_where`); the object only reads the binding. `x-dl::layer-menu` already places two badges (title and where); the login page of an application places its own (`variant="login"`). Do NOT use it for status labels (use `x-dl::badge`) or for the application name (use `x-dl::brand`).

## Props

Constructor of `Devlib\Components\EnvBadge` (3 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `part` | `string`, `'where'` | Which binding to read: the container key is `devlib.env_<part>`. Used values: `title` (suffix of the logo title) and `where` (host/IP line). Any other word reads `devlib.env_<word>`. Also appears in the catalog tag as `x-dl::env-badge part=<part>`. |
| `variant` | `string`, `'side'` | Visual variant, written as the class `dl-envbadge--<variant>`. Styled values: `title` (inline, 0.8em, inherits the colour of the logo title, no wrapping) and `login` (black text, centred, 8px top margin, for the light login panel). `side` (default) has no extra rule: the base style applies (own line, light text for the dark sidebar via token `--dl-side-ink`). |
| `label` | `?string`, `null` | The text. `null` = read it from the container binding for `part`; if there is no binding the text is `''` and nothing is rendered. A given label (including a data value) is used as is and the binding is not read. An explicit `''` also renders nothing. |

## Behavior

### Where the text comes from

Resolved once, in the constructor:

```php
$key = 'devlib.env_'.$part;
$this->label ??= app()->bound($key) ? (string) app($key) : '';
```

- The application binds the text, usually in a service provider (or a view for a demo): `app()->bind('devlib.env_where', fn () => 'Host: HOST-01 / 10.0.0.10 · Client: 127.0.0.1');` and `app()->bind('devlib.env_title', fn () => '- mockup ver 0.20');`.
- `bind()` (not `singleton()`) runs the closure on each badge, so the value may depend on the request.
- A binding that is not set, or returns an empty string, hides the badge. Use this for the live site: bind the texts only on test, staging and mockup machines.

### Generated markup

```html
<small class="dl-envbadge dl-envbadge--side" data-dl="x-dl::env-badge part=where" data-dl-class="EnvBadge" data-dl-src="—">Host: HOST-01 / 10.0.0.10 · Client: 127.0.0.1</small>
```

Extra attributes (`class`, `id`, `style`, `data-*`) go on the `<small>`. Nothing is rendered when the text is `''`. There is no JavaScript.

### Style

CSS is in its own small file, `resources/assets/envbadge.css` (served as `/_devlib/envbadge.css`, URL from `\Devlib\DevlibServiceProvider::asset('envbadge.css')`), so the application login page, which does not load `devlib.css`, can use the same object:

| Class | Rule |
|---|---|
| `.dl-envbadge` | `display:block`; `10.5px` times `--dl-font-scale`; weight 600; wraps long text (`overflow-wrap:anywhere`); colour `--dl-side-ink`. |
| `.dl-envbadge--title` | `display:inline`; `.8em`; inherits weight and colour; `white-space:nowrap`. |
| `.dl-envbadge--login` | colour `--dl-ink-strong`; centred; `margin:8px 0 0`. |

### Language and text

The text is data built by the host application (already in the right language). The class sets `TEXT_PROPS` and `TEXT_LISTS` to empty on purpose, so `label` is never put through the dictionary. The output is HTML-escaped.

### Accessibility

A plain `<small>`; no role or `aria-*`. It is read as normal text in the order of the page.

### Permissions

Only the generic `can="module.action"` (no ability = not rendered).

## Data and events

- No server write, no posted data, no events, no `el.dlComponent`.
- Inputs: the container bindings `devlib.env_title` and `devlib.env_where` (strings, or closures returning strings), or the `label` prop.
- Markup hooks: `.dl-envbadge`, `.dl-envbadge--title|side|login`.
- Where it is placed by DevLib: `x-dl::layer-menu` renders `<x-dl::env-badge part="title" variant="title" />` inside the logo title and `<x-dl::env-badge part="where" />` under the user name (also in the profile panel footer). The application login page places `part="title"` next to the logo title and `part="where" variant="login"` under the form.

## Rules and pitfalls

- The badge shows only what the application bound. Never bind it on the live site; "no binding" is the switch that turns it off.
- Link `envbadge.css` in the layout (and on the login layout): `<link rel="stylesheet" href="{{ \Devlib\DevlibServiceProvider::asset('envbadge.css') }}">`. Without it the text still appears but as an unstyled `<small>`.
- Keep the text short: one logical line. Long text wraps; it does not truncate.
- Do not put secrets (passwords, keys, connection strings) in the text; it is visible to every user who can see the sidebar. Host names and IP addresses are the intended content.
- The text is not translated and not parsed: write it in the language you want shown. It cannot contain HTML (it is escaped).
- Only the `where` binding is released in the profile demo (`app()->bind(...)` before, `unset(app()['devlib.env_where'])` after) so the demo does not leak into other pages; copy that pattern when you bind in a view.
- Code oddity (not fixed): `devlib.css` does not include the badge rules and nothing in the package or in the catalog layout links `envbadge.css`, so in the catalog the Layer Menu badge shows with the browser default for `<small>` (`resources/assets/envbadge.css`, `site/resources/views/layout.blade.php:15`). A project must add the link itself.
- Code oddity (not fixed): the class header lists the Layer Menu and the application login page as users; DevLib's own `x-dl::login-box` and `x-dl::frame` do not render the badge, so the login placement is up to the application (`src/Components/EnvBadge.php:11`).

## Example

Binding in the application (a service provider or test layout):

```php
// Only on non-live machines; leave unbound on the live site.
app()->bind('devlib.env_title', fn () => '- mockup ver 0.20');
app()->bind('devlib.env_where', fn () => 'Host: HOST-01 / 10.0.0.10 · Client: 127.0.0.1');
```

As placed inside `x-dl::layer-menu`:

```blade
<b>{{ $title }}<x-dl::env-badge part="title" variant="title" /></b>
<span class="dl-lm__user"><b>{{ $user }}</b><small>{{ $role }}</small><x-dl::env-badge part="where" /></span>
```

On an application login page (light panel), with an explicit text:

```blade
<link rel="stylesheet" href="{{ \Devlib\DevlibServiceProvider::asset('envbadge.css') }}">
...
<x-dl::env-badge part="where" variant="login" />
<x-dl::env-badge variant="login" label="Training server" />
```

## Related

- `x-dl::layer-menu`, `x-dl::layer-shell`: the menu that shows the title and where badges; the profile panel footer shows the Host / Client line.
- `x-dl::brand`: the application name and tagline (the title that `part="title"` follows).
- `x-dl::badge`: status labels with a tone, not environment text.
- `x-dl::login-box`, `x-dl::frame`: the login screen objects where an application may add the `login` variant.
