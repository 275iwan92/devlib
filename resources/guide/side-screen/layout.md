## Overview

`<x-dl::side-screen.layout>` is the content of a Side Screen: a vertical rail of sections at the left (optionally grouped, with icons and number badges, plus a block of links), a sticky section heading (name, one-sentence hint, breadcrumb) and one panel per section at the right. Only the active section is visible; clicking a rail item switches sections without reloading. It replaces horizontal tabs when a screen has many sections (4 in Help, 11 in Profile) that should be readable at a glance.

Use it inside `x-dl::side-screen` (slot mode), or as the whole page that a side screen loads in its iframe (`url`). It also works as a normal full-page layout. Pick another object when:

- there are few sections and they fit as horizontal tabs: `x-dl::tabs` (this class extends it);
- the sections are the tabs of a record form: `tabs` of `x-dl::form`;
- the left rail is navigation to other pages, not sections of one screen: `x-dl::side-menu`.

### Generated markup

```html
<div class="dl-ssl" data-dl="x-dl::side-screen.layout" data-dl-class="Layout ← Tabs ← DevlibComponent" data-dl-src="—" data-dl-ss-layout data-dl-ss-param="tab">
  <nav class="dl-ssl__rail" aria-label="Sections">
    <div class="dl-ssl__cap">group caption</div>
    <button type="button" class="dl-ssl__nav is-on" data-dl-ss-nav="<key>" data-label="Title" data-icon="fa-..." title="Title">
      <i class="fa-solid fa-..."></i><span>Title</span><b class="dl-ssl__n [is-alert]">148</b>
    </button>
    <div class="dl-ssl__cap dl-ssl__cap--links">Link caption</div>
    <a class="dl-ssl__nav" href="..." [target="..."]><i ...></i><span>Label</span></a>
  </nav>
  <div class="dl-ssl__body" data-dl-ss-body>
    <div class="dl-ssl__sec"><i data-dl-ss-sec-icon></i><div class="dl-ssl__sec-text"><h2 data-dl-ss-sec-title>Title</h2><p data-dl-ss-sec-hint>hint</p></div><div class="dl-ssl__crumb">breadcrumb</div></div>
    <div class="dl-ssl__top">slot top</div>                                  <!-- only with the top slot -->
    <div class="dl-ssl__panel is-active" data-dl-ss-panel="<key>" data-hint="hint">slot <key></div>   <!-- one per section -->
  </div>
</div>
```

All panels are rendered on the server at once; the script only toggles `is-active`.

## Props

Constructor of `Devlib\Components\SideScreen\Layout` (7 props; it extends `Devlib\Components\Tabs`, whose `tabs` and `vertical` props are fed internally with `sections` and `true` and are not props of this object). Blade names are kebab-case; use `:prop="expr"` for arrays.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `sections` | `array`, `[]` | The sections, in rail order: `key => [title, icon]` or `key => [title, icon, count]` or `key => [title, icon, count, tone]`. `key` is also the NAME OF THE SLOT that holds the content (`<x-slot:key>`), so it must be a valid PHP variable name (letters, digits, underscore; not starting with a digit). `title` is translated; `icon` is a Font Awesome class or a DevLib alias (default icon `fa-circle`); `count` is shown as a badge when it is not `null` or `''` (`0` is shown); the badge is red (`is-alert`) when `count` is a number above 0 and `tone` is omitted or `'alert'`, otherwise grey. A plain string value (`key => 'Title'`) works for the rail button only (see the oddity below). |
| `active` | `?string`, `null` | Key of the section shown first. `null` = the first key of `sections`. A key that is not in `sections` leaves no panel active and no rail item highlighted. The server should pass the section it wants after a form post (see `param`). |
| `groups` | `array`, `[]` | `key => caption`: a small upper-case caption printed in the rail before that section. When the first section has no caption, the rail automatically starts with the caption "Sections". Captions are translated. |
| `hints` | `array`, `[]` | `key => sentence`: the grey line under the section name in the sticky heading (and `data-hint` of the panel). Switching sections replaces it. Translated; missing key = no line. |
| `links` | `array`, `[]` | `caption => [[label, icon, url, target?], ...]`: a block of plain links under the rail (for example a list of guides). The caption and each label are translated; `target` is optional (`_blank`). These are real `<a>` links, not sections. |
| `crumbs` | `array`, `[]` | Items for the breadcrumb on the right of the sticky heading, in the format of `x-dl::breadcrumb`: `label => url|null` (or `[[label, url|null, openUrl|null], ...]`), rendered as `<x-dl::breadcrumb menu :items="$crumbs" />`. Because `menu` is on, an item without url (except the last) is a link that opens the Layer Menu on that branch when the page runs inside the Shell; the last item is the current page (marked `is-current` when it has a url). Empty = no breadcrumb. Hidden in the narrow layout. |
| `param` | `string`, `'tab'` | Name of the URL query parameter that `show()` writes (`?tab=<key>`, through `history.replaceState`) so a reload or a form that returns to this URL can reopen the same section. The layout itself does NOT read it: the server must read the parameter and pass `active`. Change it when the same page has an `x-dl::tabs` whose keys could equal your section keys, because `x-dl::tabs` also reads `?tab=`. |

Slots: one named slot per `sections` key (the section content) and the optional `top` slot (a flash/error area above all sections, class `dl-ssl__top`, empty = hidden). Also accepted as on every object: `can="module.action"` and HTML attributes (`class`, `data-*`) on the root.

## Behavior

- Switching: a click on a rail button calls `show(key)`: toggles `is-on` on the rail and `is-active` on the panels, rewrites the heading title, icon and hint from the clicked item, scrolls the body to the top, updates `?<param>=<key>` in the address bar and fires `dl:side-screen:section`.
- Narrow mode: when the layout element is narrower than 900px (a `ResizeObserver` toggles `is-narrow`) the rail shrinks to 56px of icons, captions, names, badges and the breadcrumb are hidden (the `title` attribute still shows the name) and paddings are reduced.
- Search: `filter(q)` is called by the Side Screen header box, or by `postMessage({dl: 'ss-search', q})` when the layout lives in an iframe. Rail items whose name does not contain the text get `is-dim` (35% opacity, still clickable). In the ACTIVE section, every `.dl-card` and `[data-ss-item]` whose text does not contain the query gets `is-hidden`; mark your own repeating blocks with `data-ss-item` to make them filterable. Matching is case-insensitive substring; an empty query clears it.
- Phone view (automatic, only in the phone context: `body.dl-on-phone` or inside `x-dl::phone-shell`; the desktop markup and behaviour are unchanged): the layout has two states on the root, `data-ss-view="list"` and `"detail"`. LIST = the rail becomes full-width rows (icon, name, badge, chevron, group captions) and the sections are hidden. Tapping a row opens DETAIL = that section only, with a back button (`dl-ssl__back`, 44 px, made by the script) at the left of the section heading; back returns to the list, and so does the Android back button (the opened section has its own history entry). A page opened with `?<param>=<key>` (after a form post) starts in DETAIL. The heading is not sticky, the breadcrumb and the narrow-rail mode are off, and `x-dl::side-screen.foot` is an ordinary block at the end of the section (full-width buttons; nothing is stuck to the bottom of the screen). Phone form rules (16 px fields, 44 px targets) already apply to the cards inside. Live example: catalog page Phone shell, screen "Edit profile".
- Esc inside a page that runs in the Side Screen iframe (`window.name === 'dl-wide'`) is sent to the parent as `{dl: 'ss-close'}`.
- The sticky heading stays at the top of the scrolling body (z-index 5); an `x-dl::side-screen.foot` stays at the bottom.
- Standalone use: the root is `height:100vh` (full viewport), inside a `.dl-ss` it fills the window instead.
- Accessibility as implemented: the rail is a `nav` with `aria-label` "Sections" (translated); items are native buttons or links, so keyboard Tab/Enter/Space work. There is no `role="tablist"`/`tab`, no `aria-selected` or `aria-current` on the active item (only the `is-on` class), and panels have no `role="tabpanel"`. Inactive panels are `display:none`.
- Language: titles, groups, hints, link captions and labels go through the dictionary (English in code). Slot content is yours. `crumbs` labels are passed to the breadcrumb object as given (the breadcrumb does not translate them, only its own "Reload" / "Open menu" titles).
- Permissions: `can="module.action"` removes the whole layout. Nothing in this object checks rights per section; do not render a section the user may not see (wrap its slot content, or build `sections` from allowed keys), and check on the server.

## Data and events

- The layout posts nothing. Forms inside sections post as usual; to come back to the same section after a post, send `?<param>=<key>` (or redirect with it) and pass `:active="request('tab')"`.
- Custom event `dl:side-screen:section` (`detail: {key}`), bubbles from the layout root. In iframe mode it stays inside the iframe document (it does not cross to the parent page).
- `postMessage`: receives `{dl: 'ss-search', q}` (from any window; the sender is not checked); sends `{dl: 'ss-close'}` to the parent on Esc when inside the iframe `dl-wide`.
- JS: `el.dlComponent` is the `SideScreenLayout` instance (`resources/assets/js/components/side-screen.js`) with `show(key)` and `filter(q)`. Another script may call `show('<key>')` to jump to a section (the help map does this).
- Hooks: `data-dl-ss-layout`, `data-dl-ss-param`, `data-dl-ss-nav`, `data-dl-ss-panel`, `data-dl-ss-body`, `data-dl-ss-sec-title`, `data-dl-ss-sec-icon`, `data-dl-ss-sec-hint`, `data-ss-item`; classes `dl-ssl`, `is-narrow`, `is-on`, `is-active`, `is-dim`, `is-hidden`, `is-alert`.

## Rules and pitfalls

- Section keys are slot names read as variables (`${$key}`): do not use `top`, `slot`, `attributes`, `sections`, `active`, `groups`, `hints`, `links`, `crumbs`, `param`, `tabs`, `vertical` or the view's loop names (`key`, `s`, `cur`, `first`, `cap`, `items`, `l`), and avoid hyphens and numeric keys.
- One slot per key; a key without a slot shows an empty panel, a slot without a key is ignored.
- Keep `hints` to one short sentence per section.
- Mark section content as cards (`x-dl::card`) or give blocks `data-ss-item`, otherwise the search has nothing to hide. Only cards that are direct children of the panel (and `data-ss-item` blocks anywhere) are actually hidden by the CSS (`.dl-ssl__panel>.dl-card.is-hidden`, `[data-ss-item].is-hidden`).
- Put the section's submit/save buttons in `x-dl::side-screen.foot`, as the last child of the slot.
- The server decides which section opens: pass `active` from the `param` value after a post, validate it against `sections`.
- No lint rule applies to this object (the `F1xx` rules check `x-dl::form` only).
- Code oddity (not fixed): when a `sections` value is a plain string (`['a' => 'Alpha']`), the rail button handles it (`Layout.php` rail loop converts it to `[$s, null]`), but the sticky heading reads `$sections[$cur][0]` and `[1]` (`src/Components/SideScreen/Layout.php:72-73`), which on a string are its first and second characters: the heading shows "A" and the icon class becomes "l". Always pass `[title, icon]` arrays.
- Code oddity (not fixed): `filter()` only touches the active section (`resources/assets/js/components/side-screen.js:132-136`). Cards hidden in section A stay hidden if you switch to section B and then clear the search box; they come back only when you filter again while A is active.
- Code oddity (not fixed): the JS selector for hiding is `.dl-card` at any depth, but the CSS hides only direct-child cards (`devlib.css` `.dl-ssl__panel>.dl-card.is-hidden`), so a card nested inside another element gets `is-hidden` and stays visible.

## Example

From the catalog demo `side-screen` (fictional content):

```blade
<x-dl::side-screen.layout
    :sections="['topics' => ['Help Topics', 'fa-life-ring', 148], 'guide' => ['User\'s Guide', 'fa-book-open'], 'flow' => ['Application\'s Flow', 'fa-diagram-project'], 'tickets' => ['Tickets', 'fa-ticket', 2]]"
    active="flow"
    :hints="['topics' => 'Every screen has its help: purpose, steps and an explanation of each field.', 'guide' => 'Read the guides from top to bottom the first time.', 'flow' => 'Point the mouse at a block to see its short rule; click a block to open its screen.', 'tickets' => 'Send a ticket with screenshots; the reply appears here.']"
    :links="['Guides' => [['ERP Rules Guide', 'fa-book', '#'], ['Master Product & Revision', 'fa-book-open', '#']]]"
    :crumbs="['Account' => null, 'Help' => null, 'Help & support' => '#']">
    <x-slot:topics><x-dl::card title="Help Topics" icon="fa-life-ring">Grid Help Center (Part 3: Grid).</x-dl::card></x-slot:topics>
    <x-slot:guide>
        <x-dl::card title="1. Opening screen" description="What you see after login and how to read the widgets." icon="fa-circle-check">
            <x-dl::table :columns="['n' => 'Step', 'text' => 'What to do']" :rows="[['n' => 1, 'text' => 'Log in with your account.'], ['n' => 2, 'text' => 'Read the widgets of your board.']]" />
        </x-dl::card>
    </x-slot:guide>
    <x-slot:flow>...</x-slot:flow>
    <x-slot:tickets>
        <x-dl::card title="Send a Ticket" icon="fa-paper-plane">
            <x-dl::field.text name="description" label="Problem description" multiline />
        </x-dl::card>
        <x-dl::side-screen.foot hint="The developer team answers in Setup > Support & Ticket">
            <x-dl::button icon="fa-paper-plane" label="Send ticket" />
        </x-dl::side-screen.foot>
    </x-slot:tickets>
</x-dl::side-screen.layout>
```

Reopening the section after a post (controller redirects to `?tab=tickets`):

```blade
<x-dl::side-screen.layout :sections="$sections" :active="request('tab')" param="tab">...</x-dl::side-screen.layout>
```

## Related

- `x-dl::side-screen`: the window that normally holds the layout (slot mode) or loads a page built from it (iframe mode); its header search box drives `filter()`.
- `x-dl::side-screen.foot`: the sticky action row at the bottom of a section.
- `x-dl::tabs`: the parent object; horizontal or vertical tabs for ordinary screens.
- `x-dl::card`: the usual section content and the unit the search hides.
- `x-dl::breadcrumb`: renders `crumbs`.
- `x-dl::layer-shell`: the Shell whose Help and Profile windows are built from this layout.
- `x-dl::action-form`: wrap a section to make its foot buttons post.
