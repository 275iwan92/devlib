## Overview

`<x-dl::feed>` is a blog / news style stream: many short narratives read one after another on a single page, without opening each one. Each post is a card with a title, an optional status badge, a meta line, a markdown body shown as an excerpt, a "Read more / Show less" button and an optional "Open Link" button. Cards sit in 3 columns (2 below 1100px, 1 below 700px). Opening a post makes it span the full row.

Use it for notes, reports, release or history narratives. Pick another object when:

- the reader must decide per item (keep / discard / approve): `x-dl::review-feed` (it inherits this component and adds filter, search and decision buttons);
- the data is tabular: `x-dl::grid` or `x-dl::sheet`;
- you show one long document: a `dl-prose` block with markdown, or `x-dl::card`.

Generated markup:

```html
<div class="dl-feed" data-dl="x-dl::feed" data-dl-class="Feed ← DevlibComponent" data-dl-src="—" data-dl-feed>
  <article class="dl-feed__post [is-open]">
    <header>
      <h3><i class="fa-solid fa-thumbtack"></i> Title</h3>          <!-- icon only when pinned -->
      <span class="dl-badge dl-badge--warn dl-badge--pill">Unread</span> <!-- only with badge -->
    </header>
    <small class="dl-feed__meta">Author · Kind · Date</small>        <!-- only with meta -->
    <div class="dl-prose dl-feed__body">...markdown as HTML...</div>
    <button type="button" class="dl-btn dl-btn--secondary dl-btn--sm dl-feed__more" data-feed-toggle data-more="Read more" data-less="Show less">Read more</button>
    <a class="dl-btn dl-btn--secondary dl-btn--sm" href="..." target="_blank" rel="noopener">Open Link</a> <!-- only with url -->
  </article>
  <!-- no posts: --> <p class="dl-feed__empty">Nothing to read yet.</p>
</div>
```

## Props

Constructor of `Devlib\Components\Feed` (2 props). Use `:posts="$posts"` (an array expression); `empty` can be a plain string.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `posts` | `array`, `[]` | List of post arrays (shape below). Rendered in the given order; the component does not sort and does not paginate. An empty list shows the `empty` text instead. |
| `empty` | `string`, `'Nothing to read yet.'` | Text of `<p class="dl-feed__empty">` when `posts` is empty. Translated through the dictionary (`empty` is a standard text prop). |

Post array keys (all optional; missing keys are treated as empty):

| Key | Meaning |
|---|---|
| `title` | Text of the `<h3>`. Escaped, NOT translated (it is data). Wrap in `raw()` if you build it from a label plus data. |
| `meta` | Small line under the header (author, kind, date). Escaped, not translated. |
| `body` | Markdown. Converted with `Str::markdown(..., ['html_input' => 'escape', 'allow_unsafe_links' => false])`: headings, lists, tables, code are rendered; raw HTML in the text is escaped and `javascript:` style links are dropped. |
| `badge`, `tone` | A small `x-dl::badge` next to the title. `tone` defaults to `neutral` (valid: `neutral`, `accent`, `success`, `warn`, `danger`, `info`, `violet`). The badge label is not translated (badge terms stay English unless the app sets `devlib.translate_fixed`). |
| `pinned` | Truthy shows a thumbtack icon before the title. It does not move the post (see pitfalls). |
| `url` | Adds an "Open Link" button that opens in a new tab (`target="_blank" rel="noopener"`). Written with `{{ }}` (escaped) but the scheme is not filtered. |

Inherited from `DevlibComponent` (no constructor props): `can="module.action"` hides the whole feed when the user lacks the ability; extra attributes (`class`, `id`, `style`, `data-*`) are merged onto the root `div`.

## Behavior

- **Excerpt and expand.** The body is clipped to `max-height:12em` with a fade at the bottom. `Read more` adds `is-open` to the post: the card spans all columns (`grid-column:1/-1`), the title gets bigger, the body loses the clip and uses a 78ch reading width and 15px text. The button text switches to `Show less` and the page scrolls the card into view smoothly (`block:'nearest'`). Click again to collapse.
- **Button hidden when not needed.** On start the script hides the button when the body is not taller than its box (`scrollHeight <= clientHeight + 4`).
- **Language.** `Read more`, `Show less`, `Open Link` and the default `empty` text go through the dictionary at render time. Post data is never translated.
- **Dark mode / theme.** Colors come from tokens (`--dl-surface`, `--dl-border`, `--dl-ink-strong`); font size follows `--dl-font-scale`.
- **Accessibility as implemented.** Each post is an `<article>` with an `<h3>`; the toggle is a native `<button type="button">`. There is no `aria-expanded` on the toggle, no `aria-live`, and the faded excerpt is still readable by screen readers (the full text is in the DOM).
- **Keyboard.** Normal: Tab to the button, Enter or Space toggles.
- **Without JS** the posts stay as clipped excerpts with a visible Read more button that does nothing.

## Data and events

- Input: the `posts` array only. Nothing is posted and no request is made.
- No `dl:*` event is emitted. Hooks: `data-dl-feed` (root, JS selector), `.dl-feed__post` (card, gets `is-open`), `.dl-feed__body`, `[data-feed-toggle]` (button, with `data-more` / `data-less` texts).
- `el.dlComponent` on the root is the `Feed` JS instance (`resources/assets/js/components/feed.js`). A project can subclass it with `data-dl-js` and `Devlib.register(class X extends Devlib.classes.Feed {...})`.
- The PHP class also exposes `html(?string $markdown): string`, the markdown converter used by the view (also used by `ReviewFeed`).

## Rules and pitfalls

- Build long `posts` arrays in a `@php` block and pass `:posts="$posts"`. Double quotes inside a `:posts="[...]"` attribute break the Blade tag parser (the demo comment in `site/resources/views/demos/feed.blade.php:2`).
- Markdown only. Do not pass HTML: it is escaped on purpose. Do not pass a user-supplied `url` without checking it (scheme `http`/`https` or a relative path), because the link is not filtered.
- Sort pinned posts first yourself. The component only draws the thumbtack.
- Titles built from data (document numbers, names) go through `raw()` so they do not end up in the translation dictionary.
- A feed that is initialised while hidden (inside a closed tab or accordion) has `scrollHeight` and `clientHeight` of 0, so the check hides every Read more button; render feeds in the visible panel, or init after showing.
- Code oddity (not fixed): `src/Components/Feed.php:18-19` has a damaged header comment (the `$posts = [...]` line is cut off and the sentence "yang 3 kolom" lost its start).
- Code oddity (not fixed): the demo comment `site/resources/views/demos/feed.blade.php:1` says "yang disematkan di atas" (pinned ones on top) and the class comment says pinned posts get a pin mark, but `Feed::render()` only draws the icon (`src/Components/Feed.php:44`); there is no sorting, so the order is purely the array order.
- Code oddity (not fixed): the toggle button has no `aria-expanded` (`src/Components/Feed.php:49`, `resources/assets/js/components/feed.js:15-19`).

## Example

Copied in shape from the catalog demo (fictional authors and texts):

```blade
@php
    $posts = [
        ['title' => 'Why the old screen documents were removed', 'meta' => 'Agent One · Report · 07-Oct-2026', 'badge' => 'Unread', 'tone' => 'warn', 'pinned' => true,
            'body' => "The per-screen documents described screens that were replaced.

- Replaced by v2 screens
- Still in git history

| File | Reason |
|---|---|
| screen_a.md | Replaced |"],
        ['title' => 'Second story', 'meta' => 'Agent Jo · Note · 06-Oct-2026', 'body' => 'A **short** note.'],
    ];
@endphp
<x-dl::feed :posts="$posts" />
```

From model rows, with a link and a custom empty text:

```blade
<x-dl::feed empty="No notes yet." :posts="$notes->map(fn ($n) => [
    'title' => raw($n->title),
    'meta' => raw($n->author.' · '.$n->created_at->format('d-M-Y')),
    'badge' => $n->state, 'tone' => $n->state === 'Done' ? 'success' : 'warn',
    'pinned' => (bool) $n->pinned,
    'url' => $n->url,
    'body' => $n->body,
])->all()" />
```

## Related

- `x-dl::review-feed`: same cards, but one column, big text, filter + search and decision buttons that save.
- `x-dl::badge`: the status label drawn next to the title.
- `x-dl::button`: the style of the Read more and Open Link buttons.
- `x-dl::empty-state`: a richer empty message when a feed is the main content of a screen.
- `skills/devlib/components.md`: generated prop list.
