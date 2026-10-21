## Overview

`<x-dl::help-map>` is the interactive "Help Topics" map of an application: the menu as a tree (Module > Group > Screen) on the left, and on the right the guide of the selected screen (purpose, steps, field list). A search box, a module selector, type chips (Input / Report / Guide) and a "No guide yet" toggle narrow the tree; a summary line says how many screens match and how many screens have a guide. The screen the user came from is marked "here" and offered in one click; a screen without a guide can be reported through a "Request guide" button that pre-fills the ticket form.

Use it as the content of a Help window or Help page that lists the operational screens of an application. Do NOT use it as a navigation menu (`x-dl::layer-menu`, `x-dl::side-menu`), as a developer reference (`x-dl::engine-catalog`, the Technical Guide), or for a single tooltip (use the `tooltip` prop of the field).

The object reads no database. The caller builds the tree and the topics (usually from the menu registry and a table of help topics) and hands them over as arrays; the browser script does the rest.

### Generated markup

```html
<div class="dl-hm" data-dl="x-dl::help-map" data-dl-js="HelpMap" data-dl-helpmap data-map='{"tree":[...],"topics":{...},"current":"...","copyUrl":"...","requestTab":"tickets","l":{...labels}}'>
  <div class="dl-hm__top">
    <label class="dl-hm__search"><input type="search" data-hm-q><kbd>/</kbd></label>
    <div class="dl-hm__filters" data-hm-filters></div>   <!-- module select, type chips, "No guide yet" -->
    <div class="dl-hm__sum" data-hm-sum></div>           <!-- counts, Reset filter -->
  </div>
  <div class="dl-hm__main">
    <nav class="dl-hm__tree" data-hm-tree aria-label="Operational menus"></nav>
    <article class="dl-hm__detail" data-hm-detail aria-live="polite"></article>
  </div>
  <footer class="dl-hm__keys"> F1 open / close · / search · ↑ ↓ move between screens · Ctrl+Enter open the screen · Esc clear search, then close </footer>
</div>
```

The filters, the tree and the detail are drawn by `resources/assets/js/components/help-map.js`. Extra HTML attributes and `can="module.action"` go on the root `div`; without the ability nothing is rendered.

## Props

Constructor of `Devlib\Components\HelpMap` (5 props). All values are written into the JSON attribute `data-map`, so they must be JSON-safe and contain nothing private.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `tree` | `array`, `[]` | The menu tree (shape below). The top level are MODULES; below them groups; the leaves are screens. With `[]` the tree area shows "No screen matches". |
| `topics` | `array`, `[]` | Help topics keyed by topic key: `[key => ['key', 'type', 'title', 'purpose', 'keywords', 'steps', 'fields']]` (shape below). Written as a JSON object even when empty. A leaf whose `topic` is not found here (or is `null`) is a screen WITHOUT guide. |
| `current` | `?string`, `null` | Slug of the screen the user is on (for example the one that opened Help with F1). When it matches a leaf, that leaf is selected, all filters are reset, the leaf is tagged "here" in the tree and the detail starts with a card "You are at ... / Guide for this screen". It overrides the selection remembered from the session. `null` = no marker. |
| `copy-url` | `string`, `''` | Base address of the Help page for "Copy guide link". The link copied is `copy-url` + `?topic=<slug>` (or `&topic=` when `copy-url` already has a `?`). With `''` the copied link is just `?topic=<slug>` (relative). The button exists only for a leaf that has both a slug and a guide. |
| `request-tab` | `string`, `'tickets'` | Key of the section of the Side Screen layout that holds the ticket form. "Request guide" shows that section and pre-fills it (see Data and events). |

### Shape of `tree`

A node is either a BRANCH or a LEAF.

| Node | Keys | Meaning |
|---|---|---|
| Branch | `id`, `label`, `icon`?, `kids` (list of nodes) | A module (top level) or a group. `id` must be unique in the whole tree: it keeps the open/closed state and is the value of the module selector. The icon is accepted but not drawn by the current script. |
| Leaf | `label`, `s` => `['slug', 'url', 'code', 'topic']` | A screen or a general guide. `slug` = screen id (also the leaf id; `null` for a guide that has no screen), `url` = address of the screen, `code` = menu code sent to the shell to open it as a tab, `topic` = key into `topics` (or `null`). |

A leaf without `slug` (a general guide) gets the id `t:<topic>`; it has no "Open screen" button, no "Copy guide link" and is not counted in the "screens have a guide" figure.

### Shape of one topic

| Key | Meaning |
|---|---|
| `key` | The topic key (same as the array key). |
| `type` | `Input`, `Report` or `Guide` (any case). Decides the filter chips, the small letter badge (I / R / G) and the colour. Another value is shown but cannot be filtered. Required: a topic without `type` breaks the script. |
| `title` | Heading of the guide; also searched. |
| `purpose` | One or two sentences under "Purpose"; also searched. |
| `keywords` | Extra search words (a string, or a list of words); searched only. |
| `steps` | Ordered list of texts under "Steps". Optional. |
| `fields` | List of `[name, meaning]` pairs under "Fields". Optional. |

## Behavior

### Filters and search

- Search: the typed words (split on spaces, all must be present, case-insensitive substring) are matched against the label, the whole menu path, the topic title, purpose and keywords of each leaf.
- Module selector (`All modules` + one entry per top-level branch), type chips (`All`, `Input`, `Report`, `Guide`, each with a count inside the chosen module) and the chip `No guide yet` (screens without a guide). All filters combine (AND). A type chip other than `All` already excludes screens without a guide, so combining it with `No guide yet` always gives an empty result.
- Summary line: the number of matching screens, then "N of M screens have a guide, K without a guide" (counted over screens that have a `slug`), and a `Reset filter` button while any filter is active.
- When nothing matches the tree shows a message and a reset button.

### Tree

- Branches are open when filters are active, when they lie on the path of the selected leaf, when the user opened them, or (top level only) when nothing is selected and nothing was opened. Because the first matching screen is selected automatically, in practice the module of that screen is the one open at the start. A branch shows how many matching screens it contains; a small warning dot marks a branch that holds screens without a guide.
- A leaf shows a dot, the label, the "here" marker for the current screen and the letter badge of its guide type. Screens without guide are drawn with the `is-missing` style.
- The selected leaf is highlighted and scrolled into view (`aria-current` is true on it; branch headers carry `aria-expanded`; chips carry `aria-pressed`).

### Detail pane

- With a guide: the path (breadcrumb), the title with its type badge, the route text under it, then Purpose, Steps and Fields.
- Without a guide: the title and a warning "There is no guide for this screen yet." with the button `Request guide`.
- Buttons: `Open screen` (leaves with a slug), `Copy guide link` (slug and guide), and `Previous screen` / `Next screen`, which move through the leaves that sit directly under the same group, in tree order (the filters are ignored there).
- `Open screen` sends `{dl: 'open-item', code, url, label}` with `postMessage` to the parent window (the Shell opens the screen as a tab and keeps the Help window floating so the steps can be followed). If the map is not inside a frame it navigates to `url` instead.

### Remembered state

The filters, the search text, the selected leaf and the open branches are stored in `sessionStorage` under the key `dl-helpmap` and restored on the next load in the same browser tab. A valid `current` overrides the selection and clears the filters.

### Keyboard

Active only while the map is visible (the Help tab may be hidden behind another section):

| Key | Effect |
|---|---|
| `/` | Focus and select the search box (when not already typing in a field). |
| `↑` / `↓` | Select the previous / next MATCHING leaf (works also while the search box has focus; ignored inside a `select` or textarea). |
| `Ctrl` + `Enter` (or `Cmd` + `Enter`) | Open the selected screen. |
| `Esc` | First press clears the search text (and stops there); the next press reaches the window, which closes the Side Screen. |
| `F1` | Handled by the Shell, not by this object: opens or closes Help and passes the slug of the active screen. |

### Language and accessibility

All texts of the script come from `HelpMap::labels()`, which translates 31 phrases on the server and puts them into `data-map` (key `l`); the script has no dictionary of its own. Titles, purposes, steps and field names of topics are data and are not translated. The tree is a `<nav aria-label="Operational menus">` and the detail pane is `aria-live="polite"`, so the new guide is announced when the selection changes. Dots and the coloured letter badges carry their meaning only in `title`/colour; the text "here" and the summary line are real text.

## Data and events

- Nothing is requested from the server by the map. Data in: `data-map`. Data out: messages and the clipboard.
- Listens to `window` `message` events of the form `{dl: 'ss-search', q}`: the search box in the header of the Side Screen forwards its text, so the user has a single search box (`x-dl::side-screen` does this for pages in its iframe).
- Posts `{dl: 'open-item', code, url, label}` to `window.parent` (target origin = own origin) from `Open screen` and `Ctrl+Enter`. `x-dl::layer-shell` receives it and opens a tab.
- `Copy guide link`: writes `copy-url?topic=<slug>` with `navigator.clipboard` (fallback: a hidden textarea and `execCommand('copy')`) and shows the toast `Link copied`. The Help page that receives `?topic=<slug>` is expected to pass that slug as `current` (this is how `x-dl::layer-shell` opens Help for the active screen).
- `Request guide`: calls `show(<request-tab>)` on the first `[data-dl-ss-layout]` on the page (`x-dl::side-screen.layout`), then fills, inside `[data-dl-ss-panel="<request-tab>"]`, the field named `screen_context` with the menu path (`Module > Group > Screen`) and the field named `description` with `Request guide: <screen name>`, dispatches `input` on both and focuses `description`. If the layout or the fields do not exist nothing happens.
- JS: class `HelpMap` (`selector [data-dl-helpmap]`, also written as `data-dl-js="HelpMap"`); `el.dlComponent` is the instance. Hooks: `data-hm-q`, `data-hm-filters`, `data-hm-sum`, `data-hm-tree`, `data-hm-detail`, `data-hm-leaf`, `data-hm-branch`, `data-hm-mod`, `data-hm-type`, `data-hm-missing`, `data-hm-reset`, `data-hm-open`, `data-hm-copy`, `data-hm-request`, `data-hm-go`, `data-hm-mine`.

## Rules and pitfalls

- Build `tree` so that every leaf sits UNDER a branch. A leaf placed directly at the top level is counted and can be reached by search and arrow keys, but the tree never draws it (see the code oddity).
- Use unique branch `id`s across the whole tree and give every top-level module a stable `id`: it is the value of the module filter and is stored in the session.
- Always give every topic a `type`, and use `Input`, `Report` or `Guide`; the script calls `type.toLowerCase()` on every topic it shows.
- Leaves with a `topic` key that is not in `topics` are treated as "No guide yet"; use that to make the gaps visible, and do not fill in empty topics just to make the number look good.
- Filter the tree by the user's rights BEFORE passing it: the map shows every screen it receives, and `Open screen` only forwards the `url` and `code` you supplied.
- Do not put secrets into topics. Everything is in the page source (`data-map`).
- `current` is the screen slug, not a menu code or a URL. The same slug must be `s.slug` of the leaf.
- Pages that host the map in the Side Screen must keep the form fields named `screen_context` and `description` in the ticket section, otherwise "Request guide" opens the section but cannot fill it.
- Several maps on one page share the `sessionStorage` key and the window key handlers; use one map per page.
- Code oddity (not fixed): the route text under the title is always written as `/v2/s/<slug>` (`resources/assets/js/components/help-map.js:176` and `:183`) instead of the real `s.url`, which hard-codes one application's URL pattern into a library script.
- Code oddity (not fixed): the chip `All` shows the number of screens WITH a guide (`total.length - miss`, `help-map.js:131`), but selecting `All` lists every screen including those without a guide, so the number and the list disagree.
- Code oddity (not fixed): a leaf at the top level (a node with `s` directly in `tree`, `help-map.js:37-45` with `parent = null`) is added to the leaf list but `drawTree()` draws only branches (`help-map.js:144`), so it is selectable by keyboard but not visible in the tree.
- Code oddity (not fixed): `copy()` uses the same function for success and failure (`navigator.clipboard.writeText(url).then(done, done)`, `help-map.js:200`), so the toast `Link copied` also appears when the browser refused to write to the clipboard.
- Code oddity (not fixed): `request()` (`help-map.js:205-210`) depends on page markup outside the component (`[data-dl-ss-layout]`, `[data-dl-ss-panel]`, the field names `screen_context` and `description`) and fails silently when it is absent.
- Code oddity (not fixed): the `keydown` handlers on `window` and `document` (`help-map.js:88-105`) and the `message` handler are never removed, and the `sessionStorage` key `dl-helpmap` (`help-map.js:15`) is shared by all instances on the origin.
- Code oddity (not fixed): the branch `icon` in `tree` is read (`help-map.js:47`) but never drawn.

## Example

No catalog demo of this object exists in this repository (the class header names a project Help screen as its user). The example below is built from the data contract in `src/Components/HelpMap.php`, with fictional modules and topics.

```blade
@php
    $tree = [
        ['id' => 'sales', 'label' => 'Sales', 'icon' => 'fa-cart-shopping', 'kids' => [
            ['id' => 'sales-orders', 'label' => 'Orders', 'kids' => [
                ['label' => 'Sales Order', 's' => ['slug' => 'sales-order', 'url' => '/s/sales-order', 'code' => 'SO01', 'topic' => 'sales-order']],
                ['label' => 'Sales Order Report', 's' => ['slug' => 'sales-order-report', 'url' => '/s/sales-order-report', 'code' => 'SO90', 'topic' => null]],
            ]],
        ]],
        ['id' => 'general', 'label' => 'General guides', 'kids' => [
            ['id' => 'general-start', 'label' => 'Getting started', 'kids' => [
                ['label' => 'How to sign in', 's' => ['slug' => null, 'url' => null, 'code' => null, 'topic' => 'sign-in']],
            ]],
        ]],
    ];
    $topics = [
        'sales-order' => ['key' => 'sales-order', 'type' => 'Input', 'title' => 'Sales Order', 'keywords' => 'order customer quotation',
            'purpose' => 'Record what a customer orders and the delivery date that was promised.',
            'steps' => ['Choose the customer.', 'Add the items and quantities.', 'Save, then Confirm.'],
            'fields' => [['Customer', 'The buyer; required.'], ['Delivery date', 'Promised date; cannot be in the past.']]],
        'sign-in' => ['key' => 'sign-in', 'type' => 'Guide', 'title' => 'How to sign in', 'keywords' => 'login password',
            'purpose' => 'Open the application with your own account.', 'steps' => ['Type your user name and password.', 'Press Sign in.']],
    ];
@endphp
<x-dl::help-map :tree="$tree" :topics="$topics" :current="request('topic')" :copy-url="url('/help')" request-tab="tickets" />
```

## Related

- `x-dl::side-screen` and `x-dl::side-screen.layout`: the floating window and its layout whose header search feeds this map (`ss-search`) and whose ticket section is filled by "Request guide".
- `x-dl::layer-shell`: opens Help with F1 (`?topic=<slug>` of the active screen), receives the `open-item` message and opens the chosen screen as a tab; its `help-url`, `drawers.help` and profile page `help` host this map.
- `x-dl::layer-menu`: the menu whose structure this tree usually mirrors.
- `x-dl::engine-catalog`: the developer-side reference (engines), as opposed to this user help.
- `x-dl::build-board`: progress numbers; the "No guide yet" count of this map is the user-help equivalent.
