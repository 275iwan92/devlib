## Overview

`<x-dl::notification-list>` is a full-width list of notifications: a heading with the number of unread items and a "Mark all read" link, then one row per notification (colored round icon, bold title, optional body text, relative time such as "2 minutes ago", and a green dot while unread). Clicking a row marks it read; "Mark all read" marks everything read. The change is visual only (see Data and events).

Use it for a notification page or panel that shows a list you already loaded. Pick another object when the need is different:

- The notification drawer of the shell (`x-dl::layer-shell`) is a separate piece of markup (`.dl-nz` rows) wired to the account data; it does not use this object.
- `x-dl::alert` for a message about the current page or form.
- `x-dl::empty-state` when you only want a "nothing here" panel (this object already uses it for an empty list).
- `x-dl::grid` for a searchable, sortable table of records.

Generated markup:

```html
<div class="dl-notif" data-dl="x-dl::notification-list" data-dl-class="NotificationList" data-dl-src="—" data-dl-notif>
  <div class="dl-notif__head"><b>All Notifications</b>
    <span class="dl-notif__count" data-dl-notif-count>4 unread</span>
    <button type="button" class="dl-notif__all" data-dl-notif-all>Mark all read</button></div>
  <div class="dl-notif__item is-unread" data-dl-notif-item>
    <span class="dl-notif__icon dl-notif__icon--success"><i class="fa-solid fa-check"></i></span>
    <div class="dl-notif__text">
      <div class="dl-notif__title">Title<span class="dl-notif__dot"></span></div>
      <div class="dl-notif__body">Body text</div>
      <div class="dl-notif__time">2 minutes ago</div>
    </div>
  </div>
  ...
</div>
```

## Props

Constructor of `Devlib\Components\NotificationList` (3 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `items` | `array`, `[]` | The notifications, a list of arrays (shape below). Empty list renders `<x-dl::empty-state icon="notification" title="No notifications" />` under the heading. |
| `title` | `string`, `'All Notifications'` | Heading text in `<b>`. Translated (`title` is in `TEXT_PROPS`); wrap data in `raw()`. |
| `mark-all` | `bool`, `true` | Shows the "Mark all read" button. The button is rendered only when this is true AND at least one item is unread at render time. `:mark-all="false"` removes it (rows can still be clicked one by one). |

Extra HTML attributes (`id`, `class`, `style`, `data-*`) are merged onto the root `div`. The attribute `can="module.action"` is supported (base class `DevlibComponent`): without the ability nothing is rendered.

### Item shape

| Key | Required | Meaning |
|---|---|---|
| `title` | yes | Bold first line. Escaped. NOT translated (data). A missing key raises an "undefined array key" error. |
| `body` | no | Second line, normal weight. Skipped when empty. Escaped, not translated. |
| `icon` | no, `'notification'` | Icon meaning from `Devlib\Icon::MAP` (`approve`, `money`, `users`, `submit`, `settings`, `notification`, `warning`, ...) or a bare Font Awesome class such as `fa-truck`. Written as `fa-solid <class>`, so only solid icons work and the value must be a single class (no `fa-regular fa-bell`). |
| `tone` | no, `'accent'` | Color of the icon tile: `accent` (default), `success`, `warn`, `danger`. Any other string gives a tile without color. |
| `read` | no, `false` | `true` = already read: no green dot, no tint. Missing or falsy = unread. |
| `at` | no | When it happened: a `DateTimeInterface` or any string Carbon can parse. Shown as a relative time (`diffForHumans`) in the page language and the Locale timezone. Missing = the time line is printed empty. |

## Behavior

- **Unread count:** computed in PHP when the page is rendered (items whose `read` is falsy). The badge text is the dictionary text `n_unread` (`{n} unread` / `{n} belum dibaca`). The badge exists only when the count is above zero at render.
- **Row click:** the script `Notif` (`resources/assets/js/components/notif.js`) removes `is-unread` and the dot from the clicked row and recounts. Rows look clickable (pointer cursor, hover tint) but nothing opens or navigates.
- **Mark all read:** clears every unread row, recounts, hides the badge and the button (the `hidden` attribute), and shows the success toast "All notifications marked as read." (dictionary text `n_all_read`).
- **Look:** rows have 14px/16px padding and rounded corners; unread rows have a faint green tint, the dot and the badge use the green token; icon tile is 44px with a soft tone background; time is small muted text. Fonts follow the user font scale; colors come from theme tokens, so dark theme follows.
- **Relative time:** printed once at render ("2 minutes ago" does not tick later).
- **Language:** `title`, the "Mark all read" label, the badge and the empty-state text follow the page language. Item `title`, `body` and the relative time wording: the time follows the page language through Carbon; the item texts are data and are not translated.
- **Accessibility as implemented:** rows are plain `div`s: no `role`, not focusable, no keyboard way to mark one read. Only the "Mark all read" button (a native `<button>`) is reachable by keyboard. The unread state is shown by a dot and a tint only; there is no `aria-live` for the count change.
- **Permissions:** `can="module.action"` hides the whole list.

## Data and events

- Input: the `items` array. Typical source: a query mapped to the shape above.

```php
$items = $notifications->map(fn ($n) => [
    'title' => $n->title, 'body' => $n->body, 'icon' => 'approve', 'tone' => 'success',
    'read' => $n->read_at !== null, 'at' => $n->created_at,
])->all();
```

- Posts nothing, calls no URL, and emits no `dl:*` event. Reading a row is NOT saved: after a reload the rows are unread again. To keep the state, add your own listener on the root (`click` bubbles from the rows and `[data-dl-notif-all]`) and call your endpoint, or render with the right `read` flags.
- Markup hooks: `data-dl-notif` (root, the mount selector), `data-dl-notif-item` (each row, the click target), `data-dl-notif-all` (button), `data-dl-notif-count` (badge), `.is-unread`, `.dl-notif__dot`.
- `el.dlComponent` on the root is the `Notif` instance (`all`, `count` and the method `refresh()` which recounts unread rows and updates the badge and the button).
- Override with `data-dl-js="MyNotif"` plus `Devlib.register(class MyNotif extends Devlib.classes.Notif {...})`.

## Rules and pitfalls

- Every item needs `title`; give `at` so the time line is not empty.
- Item texts are data: do not expect them in the dictionary, and write them already in the user's language. Use `raw()` only for the `title` prop (the component heading), not for item keys (item keys are never translated).
- `icon` accepts a meaning or ONE `fa-*` class; the markup always adds `fa-solid`.
- The "read" state is client-side only (see Data and events). Never treat it as a saved status.
- Do not use it for hundreds of rows: all rows are rendered at once, there is no paging or scrolling container (the parent decides the height).
- The root class names `.dl-notif*` are also used by the shell notification drawer (`.dl-notif__bar`, `.dl-notif__list`), so CSS changes to them affect both.
- No lint rule exists for this object (`lint()` returns no findings).
- Code oddity (not fixed): `resources/assets/js/components/guide-link.js:10` maps `x-dl::notification-list` to the selector `.dl-notif__title`, which is the class of each row title, not of the heading (`<b>` in `.dl-notif__head`). When the Technical Guide link is enabled, the link wraps the first row's title text instead of the "All Notifications" heading.
- Code oddity (not fixed): the JS mount selector `[data-dl-notif]` (`notif.js:7`) also matches the rows of the shell drawer (`resources/views/components/shell/overlays.blade.php:52`, `data-dl-notif="<id>"`), so a `Notif` instance is created on each drawer row. It is harmless today (it finds no count or button) but it is an unintended collision.
- Code oddity (not fixed): `resources/assets/js/components/notif.js:11` lets a click on any row mark it read, yet the row has no link or action; the pointer cursor suggests more than happens.

## Example

```blade
{{-- Shape of the catalog demo (fictional data) --}}
<x-dl::notification-list :items="[
    ['title' => 'New PO waiting for approval', 'body' => 'PO-2609-0014 from Example Paper Co, IDR 12,500,000', 'icon' => 'approve', 'tone' => 'success', 'at' => now()->subMinutes(2)],
    ['title' => 'Payment received', 'body' => 'Invoice INV-2609-0031 paid in full', 'icon' => 'money', 'tone' => 'accent', 'at' => now()->subMinutes(15)],
    ['title' => 'Delivery order sent', 'body' => 'DO-2609-0120 received by the customer', 'icon' => 'submit', 'tone' => 'success', 'at' => now()->subHours(2), 'read' => true],
    ['title' => 'Payment failed', 'body' => 'Transfer to supplier was rejected by the bank', 'icon' => 'money', 'tone' => 'danger', 'at' => now()->subHours(4)],
]" />

{{-- Own heading, no "Mark all read" button --}}
<x-dl::notification-list title="Approvals" :mark-all="false" :items="$items" />

{{-- Empty list: heading plus the "No notifications" empty state --}}
<x-dl::notification-list :items="[]" />
```

## Related

- `x-dl::empty-state`: rendered by this object when `items` is empty.
- `x-dl::alert`: a single message for the current page or form.
- `x-dl::announce`: a rotating one-line announcement strip.
- `x-dl::layer-shell`: owns the notification drawer of the app shell (different markup).
- `x-dl::grid`: when the list needs search, sort and paging.
- `skills/devlib/components.md` (generated prop list), `skills/devlib/object.md` (text props, `can`, JS override).
