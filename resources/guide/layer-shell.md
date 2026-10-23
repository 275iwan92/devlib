## Overview

`<x-dl::layer-shell>` is the application frame shown after login: the Layer Menu (`x-dl::layer-menu`) on the left, NO top bar, and to the right the work area with MDI tabs. Every menu leaf opens as a tab that holds the screen in an `<iframe>`; the first view is an opening screen (home). Around it the shell provides the Ctrl+K command palette, the drawers (Notifications, Chat, AI Assistant), the optional wide windows (Edit profile, Help & support as `x-dl::side-screen`), the Edit profile and Change password modals, and the Profile panel that slides up from the user row of the menu. It is the only main menu of DevLib applications.

The menu is data, not markup: the screen only passes an array `modules` (rows equal to an `sys_app_menu` table). Use `x-dl::layer-shell` once per application, on the home page. Do not use it for a login screen, a print page or a screen that lives inside a tab (those are plain pages). For a secondary panel inside a screen use `x-dl::side-menu`; for the bare layered panel without tabs use `x-dl::layer-menu`.

Root markup:

```html
<div class="dl-shell dl-shell--layer" data-dl="x-dl::layer-shell" data-dl-js="LayerShell"
     data-dl-modules="[...json...]" data-dl-user="{...json...}" data-dl-open-tab="" data-dl-fresh="0"
     data-dl-help="" data-dl-profile-page="">
  <aside class="dl-lm dl-shell__lm" data-dl-layer-menu data-id="shell">...</aside>   <!-- the Layer Menu -->
  <div class="dl-main"> tab bar [data-dl-tabbar] + content [data-dl-content] with the home pane [data-dl-home] </div>
  <aside class="dl-lm-dock dl-shell__dock" data-dl-lm-dock></aside>                  <!-- right dock for a left chat (not used by the shell) -->
  <form method="POST" action="{logout-action}" data-dl-logout hidden>@csrf</form>
  ... backdrop, drawers, modals, palette [data-dl-palette] ...
</div>
```

The root does not render `$attributes`: extra HTML attributes and classes written on `<x-dl::layer-shell>` are not copied to the markup (only `can="module.action"` is honoured; without the ability nothing is rendered).

## Props

Constructor of `Devlib\Components\LayerShell`: 17 props. Blade names are kebab-case; use `:prop="expr"` for arrays and booleans.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `modules` | `array`, `[]` | The menu (see "Modules shape"). Put into `data-dl-modules`; the script reads the leaves from it (code, label, url, icon, type) to open tabs and fill the palette. |
| `user` | `array`, `['name' => 'User', 'email' => '', 'role' => '']` | Current user. `name` (required key), `email`, `role`. Name, e-mail and role go to the Layer Menu (`user`, `login`, `role`) and the home greeting uses the first word of `name`. The whole array is written as JSON to `data-dl-user`, so keep secrets out. |
| `brand` | `?string`, `null` | Application name in the menu head. `null` = `Brand::identity()['name']` (container binding `devlib.brand`, else `config('devlib.brand.name')`, else `DEVLIB`). Passed as `Lang::raw`, so it is never translated. |
| `tagline` | `?string`, `null` | Small line under the name; `null` = `Brand::identity()['tagline']`. |
| `logo` | `?string`, `null` | URL of the APPLICATION logo; `null` = `Brand::identity()['logo']`, and when that is empty the default gradient badge. The company logo is for print headers, not for here. |
| `logout-action` | `string`, `''` | URL of the hidden form `data-dl-logout` (`POST` with `@csrf`) submitted by Sign out. Must accept `POST`. Empty = the form posts to the current URL. |
| `menu-source` | `string`, `'sys_app_menu'` | Only text shown by the developer catalog (`source()` = `menu = ...`, and `data-dl-src` of the palette). No runtime effect. |
| `chat` | `array`, `[]` | Data for the Chat drawer: `['me', 'people', 'convos', 'api']` for a compact `x-dl::chat`. `me` falls back to `user`. Empty `api` = a preview without a server. Ignored when `without` contains `chat`. |
| `open` | `?string`, `null` | Code of the menu item opened as a tab on start (`data-dl-open-tab`). It wins over tabs restored from the browser session, and `?open=` is removed from the address bar after use. If the code belongs to the `MGMT` module the Management Reports view is shown first. |
| `help-url` | `?string`, `null` | Address of the Help & support page (`data-dl-help`), opened as the tab `HELP`. Empty = the toast "Help & support - coming soon". |
| `profile-page` | `?string`, `null` | Key of a Profile page to open on load (`data-dl-profile-page`), for example to come back to a ticket page after a submit. The page must exist in the `profilePages` slot (or the built-in `setup`, `theme`, `lang`). |
| `assistant` | `array`, `[]` | Data of the AI Assistant drawer. With `api.ask` filled the assistant is live; empty = a preview that matches words against menu names. See "AI Assistant". |
| `account` | `array`, `[]` | Server connection of Notifications, Edit profile and Change password. See "Account". Empty = a preview with sample content where Save only closes. |
| `fresh` | `bool`, `false` | `true` right after login: the old tabs and the Layer Menu position are discarded (`data-dl-fresh`, `boot.js` calls `Devlib.resetWorkspace()`), so the opening screen shows. |
| `drawers` | `array`, `[]` | Wide windows from the right edge, each a full screen in an iframe: `['edit' => ['label', 'icon' => 'fa-...', 'url', 'subtitle'?], 'help' => [...]]`. See "Drawers". |
| `without` | `array`, `[]` | Profile panel items to hide (opt-in, for simple applications). Recognised values: `notif`, `chat`, `ai`, `edit`, `help`, `setup`, `theme`. See "The without prop". |
| `device-action` | `?string`, `null` | POST address that saves the chosen view (field `ui`, value `hp`). When set, the profile panel gets one more action, "Phone view", placed before Change password; it submits a hidden form and the project stores the cookie `ui` (see `Devlib\Device`, `x-dl::phone-shell`). `null` = no such action and the markup is exactly as before. |

### Slots

| Slot | Content |
|---|---|
| default | Extra content on the opening screen, under the welcome text (before the hint line). |
| `dashboard` | `x-dl::dashboard` (or any content) that REPLACES the default welcome text on the opening screen (wrapped in `dl-home dl-home--dash`). |
| `profilePages` | Extra Profile pages: `<section data-lm-page="key" data-label="Text" data-icon="fa-...">`. A page whose key equals an action key (for example `help`) replaces the action: the row opens the page and `Back` returns to the Profile list. |

### Modules shape

```php
$modules = [
  ['code' => 'SALES', 'section' => 'Operations', 'label' => 'Sales', 'icon' => 'fa-solid fa-cart-shopping', 'pin' => null, 'children' => [
      ['label' => 'Transactions', 'items' => [ITEM, ITEM]],     // group = a sub-menu (opens the next panel)
      ITEM,                                                      // a direct item
  ]],
];
// ITEM = ['code', 'label', 'url', 'type' => 'form'|'grid'|'report', 'icon'?, 'badge'?, 'badgeTone'?, 'badgeTitle'?, 'group'?]
// A group may hold further groups: ['label' => .., 'icon'? , 'group'?, 'items' => [ITEM | group ...]]
```

- Module: `code` (used to recognise `MGMT`), `label`, `icon`, optional `section` (title of a group of modules on layer 1: consecutive modules with the same section sit under one caption; when any module has a section the menu uses `fold-groups="accordion"`) and `pin` (the module row sticks to the bottom of the menu).
- Leaf: `code` is the tab identity and the menu `key`; `url` is the screen loaded in the iframe; `type` chooses the default tab icon (`typeIcon`) when `icon` is missing. `badge` is the pill text (or `icon:fa-solid fa-circle-check` for an icon only), `badgeTone` is `partial` (warn), `done` (success), `danger` (danger); anything else is the accent colour.
- Group (an entry with `items`): becomes a sub-panel; if it has no `icon` the folder icon is added.
- The module named by `LayerShell::REPORT_MODULE` (`MGMT`) gets its own view: when `modules` has both `MGMT` and other modules, the menu head shows two icon buttons, `Operational` and the MGMT label (`views()`); the MGMT view is the starting view (`startView()`) when `open` is a code inside it. If `MGMT` is the only module, or missing, there is a single normal menu.
- Every leaf must have a `code` and `label`; the code of leaf and tab is also used by the palette.

## Behavior

### Tabs and screens

- Click on a leaf (`dl:layer-menu`) = `openTab(code)`: a tab is created with an iframe of the leaf `url`; clicking the same leaf again activates the tab and, if the screen has moved on from its grid (a form is open), goes back to the grid address. If the form has unsaved changes the DevLib dirty dialog asks Save / Discard / Cancel first.
- The open tabs (codes and the active one) are kept in `sessionStorage` `dl-shell-tabs` (per browser tab), so a reload for theme or language keeps them. The active tab marks its leaf in the menu.
- Closing the last tab shows the opening screen. Clicking the logo or application name (`home`) closes ALL tabs; when some tab has unsaved changes, a confirm dialog "Close all tabs?" appears first.
- Pages outside the menu (Help, guides, notification links) open as ordinary tabs through `openUrlTab(code, label, url, icon)`; any element with `data-dl-tab-url` (and optional `-label`, `-code`, `-icon`) inside the shell does the same on click.
- Messages from screens inside tabs (`window.postMessage`, same origin only): `{dl: 'help', hash}` opens Help; `{dl: 'open-item', code, url, label}` opens a tab and floats the Help window; `{dl: 'open-tab', ...}` opens a tab; `{dl: 'menu', path, leaf}` opens the Layer Menu on that branch (breadcrumb of a screen).

### Palette (Ctrl+K / Cmd+K)

The magnifier in the menu head (`search` is always on) or the shortcut opens the palette over all leaves. All words typed must occur in `label + module label`; with an empty text the first 7 leaves show. Arrow keys move, Enter opens a tab, Escape or the X closes. Texts: "Type a form, grid or report name…", footer "select / open / close".

### Opening screen

A translated greeting with the first word of `name`, plus the text "Choose a module in the left menu; click a branch to go down one layer, Back to go up." and the hint line, then the default slot or the `dashboard` slot.

### The Profile panel

The panel is the Layer Menu's Profile overlay (see `x-dl::layer-menu`); the shell fills it. It slides up from the user row at the bottom of the menu, covering the menu at the same width.

- Open and close: click the avatar and name row; click it again, or the X at the top right of the panel, to close it. `Escape` does not close it. `Back` inside a page returns to the list.
- Header: large avatar (initials), the user name, and below it the e-mail (`user.email`) followed by ` · ` and `user.role` when a role is given. An application that wants "name and e-mail only" passes an empty `role`.
- Items (from `LayerShell::actions()`, in this order; the first group is above the line):

| Key | Label | Opens | Hidden by `without` |
|---|---|---|---|
| `notif` | Notifications | Notifications drawer | `notif` |
| `chat` | Chat | Chat drawer | `chat` |
| `ai` | AI Assistant | AI Assistant drawer | `ai` |
| `edit` | Edit profile | modal, or the wide window `drawers.edit` | `edit` |
| `help` | Help & support | the Profile page `help` (slot `profilePages`), else the wide window `drawers.help`, else the tab `HELP` (`help-url`), else a toast | `help` |
| (page) Setup | Setup | page listing Theme and Language, inserted right after Help & support | `setup`, or `theme` hides only Theme |
| `pass` | Change password | modal | never |
| `logout` | Sign out | confirm dialog, then logout | never |

  The action `logout` has `tone` `danger` (red) and `confirm` "Sign out of the application?". Labels are translated; keys, icons (`notification`, `fa-comment-dots`, `ai`, `fa-user-pen`, `help`, `password`, `logout`) and the `sep` flag are fixed by `actions()`.
- Separator: a line is drawn once, before the first visible item of the lower group (Edit profile, Help & support, Change password, Sign out). It appears only if at least one of Notifications, Chat or AI Assistant is still shown; with all three hidden there is no line.
- Setup page (`data-lm-page="setup"`) has two children: Theme (`x-dl::theme-picker`) and Language (`x-dl::lang-switch`); opening Setup lists them and `Back` goes up. If `theme` is in `without` but `setup` is not, Setup is not shown and the Language page itself is listed in its place (after Help & support).
- Footer (the user row, always visible under the menu): avatar, name, role, and the line `Host / Client` produced by `<x-dl::env-badge part="where" />`; the application name has the suffix of `<x-dl::env-badge part="title" variant="title" />`. Both stay empty unless the application binds `devlib.env_where` and `devlib.env_title` in the container (for example `app()->bind('devlib.env_where', fn () => 'Host: HOST-01 · Client: 127.0.0.1')`); they are not props of the shell.
- Language: all item labels, tooltips and dialogs follow the page language; the user name, e-mail and role do not.

### The without prop

`without` is a list of strings; unknown values are ignored. Default `[]` shows every item. It hides ONLY the Profile item (and the extra it owns); Change password and Sign out cannot be hidden.

| Value | Hides | What else is not rendered |
|---|---|---|
| `notif` | action Notifications | the Notifications drawer and "Mark all as read"; the Change password modal then carries the account data for Save |
| `chat` | action Chat | the Chat drawer (`chat` data is ignored) |
| `ai` | action AI Assistant | only the action: the AI drawer is still rendered |
| `edit` | action Edit profile | the Edit profile modal and `drawers.edit` |
| `help` | action Help & support | only the action: F1 and `drawers.help` still work (see pitfalls) |
| `setup` | the Setup page and the Language page | Theme, Language |
| `theme` | the Theme page; Setup is replaced by the Language page directly | Theme |

Example for a simple application with only a login name and password change: `:without="['notif', 'chat', 'ai', 'edit', 'help', 'setup']"` leaves only Change password and Sign out in the panel (this is tested in `LayerShellWithoutTest`).

### Drawers and modals

- Right drawers (`dl-drawer`, `data-dl-drawer`): `notif`, `chat`, `ai`. One is open at a time, with a backdrop; the backdrop, the X or opening another closes it. They have a Maximize button.
- Wide windows (`drawers`): each entry is rendered as `<x-dl::side-screen :id="$key" :title="label" :subtitle :icon :url>`. The key `edit` replaces the Edit profile modal and the key `help` replaces the Help tab. The screen is loaded the first time it opens. Help opens with `?topic=<slug>` of the active screen when it has one; opening Help while it is open closes it (F1 toggles). Other keys are rendered but nothing opens them.
- Modals `edit` and `pass` (`dl-mov`): fields `name`, `email`, `phone` (and read-only position), and `old_password`, `new_password`, `new_password_confirmation`.

### Account

`account = ['csrf', 'profile' => ['url', 'phone', 'role'], 'password' => ['url'], 'notifications' => ['read' => url, 'items' => [...]]]`. The data is written to `data-dl-account` (on the Notifications drawer, or on the Change password modal when `notif` is hidden).

- Save (`profile`, `password`): the script collects the `input[name]` values of the modal and sends them with `POST`, JSON, `credentials: same-origin`, `Accept: application/json` and `X-CSRF-TOKEN: account.csrf`. The server answers `{message}` (HTTP 200) or `422 {message}`; a message is shown in the toast, a refusal is shown in the dialog. After a successful password change the three fields are cleared.
- Notifications: `items` is a list of `['id', 'title', 'sub', 'tone' => danger|warn|success|accent, 'read', 'url']`. Clicking an unread item posts `{id}` to `notifications.read` (answer `{unread}`) and marks it read; an item with `url` opens a tab and closes the drawer. "Mark all as read" posts `{}`. With an empty list the text "No notifications" shows.
- Without `account` (catalog preview): sample content; Save only closes the dialog and the counters use sample numbers.
- The edit modal shows `phone` and `role` from `account.profile`; in preview it shows sample values.

### AI Assistant

`assistant = ['api' => ['ask' => url, 'feedback' => url, 'csrf' => token], 'hello', 'chips' => [...], 'history' => [['id', 'q', 'reply', 'feedback']], 'placeholder', 'thinking', 'failed']`. With `api.ask`, a question is posted as `{q, topic}` and the answer `{id, reply}` is drawn as a card: `reply = {kind, title, text, big, rows [[label, value]], steps [..], note, actions [{label, url, code, icon} | {label, topic} | {label, page}]}`. Thumbs up/down post `{id, value}` to `api.feedback` (clicking an active thumb sets `0`). `reply.refresh` fires `dl:refresh` on `document`. Without `api`, the assistant matches the words with menu names and opens the matching tab (preview).

## Data and events

### Events and messages

- From the Layer Menu (listened by the shell, see `x-dl::layer-menu`): `dl:layer-menu` (open tab), `dl:layer-menu-action` (`palette`, `home`, `chat`, and the account keys); the shell cancels the default so the Layer Menu shows no preview toast.
- The shell listens to `document` event `dl:help` (F1 from `core.js`) and to `message` events (see above).
- Other events on the shell: `dl:chat-unread` from the chat is relayed as the unread badge on the avatar and the Chat row; `dl:refresh` is fired by assistant replies.

### What the server receives

| Source | Request |
|---|---|
| Sign out | `POST logout-action` with the CSRF `_token` of the hidden form |
| Edit profile / Change password | JSON `POST` to `account.profile.url` / `account.password.url` |
| Mark read | JSON `POST` to `account.notifications.read` with `{id}` or `{}` |
| Assistant | JSON `POST` to `assistant.api.ask` and `.feedback` |
| Screens in tabs | normal GET of each leaf `url` in an iframe (same origin) |

### Markup hooks and JS

| Hook | Where |
|---|---|
| `.dl-shell` (class `LayerShell`), `data-dl-modules`, `data-dl-user`, `data-dl-open-tab`, `data-dl-fresh`, `data-dl-help`, `data-dl-profile-page` | root. |
| `[data-dl-tabbar]`, `[data-dl-content]`, `[data-dl-home]` | tab bar, panes, opening screen. |
| `[data-dl-drawer="notif|chat|ai"]`, `[data-dl-side-screen="edit|help"]`, `[data-dl-modal="edit|pass"]`, `[data-dl-palette]` | overlays. |
| `[data-lm-act]`, `[data-lm-page]` | Profile rows and pages (Layer Menu). |
| `form[data-dl-logout]` | hidden logout form. |

`el.dlComponent` on `.dl-shell` is the `LayerShell` instance: `openTab(code)`, `closeTab(code)`, `activate(code)`, `showHome()`, `goHome()`, `openUrlTab(code, label, url, icon?)`, `openHelp(hash?)`, `openPage(key)`, `command(key)` (`help`, `chat`, `notif`, `ai`, `edit`, `pass`, `palette`, `home`, `logout`), `openPalette()`, `openDrawer(id)`, `closeDrawers()`, `findItem(code)`; `lm` is the Layer Menu element. Override with `data-dl-js` plus `Devlib.register(class X extends Devlib.classes.LayerShell {...})` only in a project that needs it.

### Browser storage

`sessionStorage` `dl-shell-tabs` (open tabs); `localStorage` keys of the Layer Menu with `id` `shell`: `dl-lm-shell`, `dl-lm-shell-mini`, `dl-lm-shell-fold`. Sign out and `fresh` clear the tab list and all `dl-lm-*` keys except `-mini`.

## Rules and pitfalls

- One layer shell per application, on the home page. The screens inside tabs are separate pages with their own layout; they must be same-origin to take part in messages and the dirty guard.
- Pass Profile choices through `without`, not by editing the view. Change password and Sign out are fixed by design.
- `without` hides menu items, not capabilities: `help` does not disable F1 (`command('help')` still opens the window, the Profile page `help` or the Help tab) and a `drawers.help` entry is still rendered (only `drawers.edit` is removed with `edit`); `ai` leaves the AI drawer in the markup. Leave `help-url` and `drawers.help` empty when Help must be gone.
- `without` hiding `chat` removes the Chat drawer. Hiding `notif` removes the drawer, but `account` is still honoured by the Change password modal.
- The Profile header shows `email · role`; to show only the e-mail pass `role => ''`.
- Host and Client lines are container bindings (`devlib.env_where`, `devlib.env_title`), not props. They are shown for development hosts and are empty on live sites by design.
- `modules` and `user` are serialized into the HTML (`data-dl-modules`, `data-dl-user`): send only what the user may see. The server must still check access on each `url`.
- Keys inside `modules`: a leaf without `code` raises an error in `layerTree()`; `code` must be unique across the menu because it identifies the tab and the palette result.
- Module and item labels are data and are not translated by the shell; translate them where you build `modules`. `brand` is never translated.
- Extra attributes written on `<x-dl::layer-shell>` are dropped (the root view does not render `$attributes`).
- Code oddity (not fixed): `src/Components/LayerShell.php:18` and `resources/assets/js/components/layer-shell.js:220` say that Chat stays inside the Layer Menu (swap / right dock), but the view (`resources/views/components/layer-shell.blade.php:21`) renders Chat as a right drawer and passes no `chat` slot to the menu; the dock element (`data-dl-lm-dock`, `dock="[data-dl-lm-dock]"`) is rendered but unused by the shell.
- Code oddity (not fixed): the hint on the opening screen (`resources/views/components/layer-shell.blade.php:15`) says "the gear next to the name = Chat, Profile, Notifications", but the gear no longer exists; the user row opens the Profile panel and Chat / Notifications are rows of that panel.
- Code oddity (not fixed): `badgeTitle` is set by `LayerShell::layerTree()` (`LayerShell.php:105`) but dropped by `LayerMenu::tree()` (`LayerMenu.php:82`), so the tooltip of icon-only badges is empty.
- Code oddity (not fixed): the buttons `Cancel` and `Save` of the Edit profile and Change password modals are plain English (`resources/views/components/shell/overlays.blade.php:77` and `:89`), and the drawer title `Chat` (`overlays.blade.php:5`) is not passed through the dictionary.

## Example

Minimal application home (tested form), and the form of the demo page with the extra Profile page:

```blade
<x-dl::layer-shell
    :modules="$modules"
    :user="['name' => 'Budi Santoso', 'email' => 'budi@example.test', 'role' => 'Operator']"
    :logout-action="route('logout')"
    :without="['notif', 'chat', 'ai', 'edit', 'help', 'setup']"
/>
```

Full application with server connections, and a Help page inside the Profile panel (shape of `site/resources/views/demos/shell.blade.php` and the Profile demo):

```blade
<x-dl::layer-shell
    :modules="$modules"
    :user="['name' => 'Budi Santoso', 'email' => 'budi@example.test', 'role' => 'Operator']"
    :account="$account"
    :chat="$chat"
    :assistant="$assistant"
    :drawers="$drawers"
    help-url="/help"
    open="SALES_CO"
    :logout-action="route('logout')">
    <x-slot:profilePages>
        <section data-lm-page="help" data-label="Help & support" data-icon="fa-circle-question">
            <p>Press <kbd>F1</kbd> on any screen to open this page.</p>
        </section>
    </x-slot:profilePages>
</x-dl::layer-shell>
```

```php
$modules = [
    ['code' => 'SALES', 'label' => 'Sales', 'icon' => 'fa-solid fa-cart-shopping', 'children' => [
        ['label' => 'Transactions', 'items' => [
            ['code' => 'SALES_CO', 'label' => 'Customer Order', 'url' => '/co', 'type' => 'grid'],
            ['code' => 'SALES_QUO', 'label' => 'Quotation', 'url' => '/quo', 'type' => 'grid', 'badge' => '3', 'badgeTone' => 'partial'],
        ]],
        ['code' => 'SALES_RPT', 'label' => 'Sales Summary', 'url' => '/r/sales', 'type' => 'report'],
    ]],
];
$account = ['csrf' => csrf_token(), 'profile' => ['url' => '/account/profile', 'phone' => '0812-0000-0000', 'role' => 'Operator'],
            'password' => ['url' => '/account/password'], 'notifications' => ['read' => '/account/notif-read', 'items' => []]];
$drawers = ['edit' => ['label' => 'Edit profile', 'icon' => 'fa-user-pen', 'url' => '/account/edit']];
```

## Related

- `x-dl::layer-menu`: the left panel, the Profile overlay, its events (`dl:layer-menu*`) and JS API.
- `x-dl::side-menu`: a secondary side panel (not the application menu).
- `x-dl::side-screen`: the wide window used by the `drawers` entries.
- `x-dl::chat`: the compact chat in the Chat drawer.
- `x-dl::env-badge`: the Host / Client line and the title suffix in the menu.
- `x-dl::theme-picker`, `x-dl::lang-switch`: the Theme and Language pages of Setup.
- `x-dl::dashboard`: content for the `dashboard` slot.
- `skills/devlib/components.md`, `skills/devlib/object.md`: generated prop list and object rules; `site/tests/Feature/LayerShellWithoutTest.php`: test of `without`.
