## Overview

`<x-dl::review-feed>` is a blog style REVIEW screen: the reader reads one big card per item and then decides with large buttons that save immediately. It is for "read, then decide" work (keep / condense / discard, approve / hold), not for filling cells. It is a child of `x-dl::feed` (PHP class `ReviewFeed extends Feed`: same `posts` / `empty` props, same markdown converter) but has its own markup, its own JS (`resources/assets/js/components/review-feed.js`) and its own CSS (`dl-rfeed*`). It always shows each card fully open (no Read more), in one column, with large black text (no grey text; grey is only for disabled).

Added on top of Feed: a sticky bar with filter chips (with live counts), a quick search box, a shown counter, and a decision footer per card.

Pick another object when:

- you only want to read narratives with an excerpt: `x-dl::feed`;
- you edit many rows or cells: `x-dl::sheet` / `x-dl::grid`;
- you need an approval with signatures and states: `x-dl::approval` / the Approval pattern of the form rules.

Setting `choices` to `[]` makes it a plain article index (no decision footer), as used by a docs library page.

Generated markup:

```html
<div class="dl-rfeed" data-dl="x-dl::review-feed" data-dl-class="ReviewFeed ← Feed ← DevlibComponent" data-dl-src="daftar butir review + keputusan"
     data-dl-review-feed data-action="/review/save" data-csrf="TOKEN" data-field="decision"
     data-t-saved="Decision saved" data-t-failed="Decision was not saved" data-t-none="Not decided yet" data-t-done="Your decision">
  <div class="dl-rfeed__bar">                                   <!-- only when search=true -->
    <input type="search" class="dl-input dl-rfeed__q" data-rf-q placeholder="Search..." aria-label="Search"> <!-- only when quick=true -->
    <span class="dl-rfeed__chips" role="group">
      <button type="button" class="dl-rfeed__chip dl-rfeed__chip--warn [is-active]" data-rf-filter data-where='{"decision":[""]}'>Not decided <b data-rf-n>3</b></button>
    </span>
    <span class="dl-rfeed__count"><b data-rf-shown>3</b> shown</span>
  </div>
  <div class="dl-rfeed__list">
    <article class="dl-rfeed__post [is-decided]" data-rf-post data-id="1" data-decision="" data-f='{"proposal":"DISCARD"}' data-text="lowercase title meta summary note">
      <header><h2>Title</h2><p class="dl-rfeed__meta">meta</p></header>
      <p class="dl-rfeed__prop"><span>Claude proposes</span> <span class="dl-badge ...">Discard</span></p>
      <p class="dl-rfeed__summary">..</p> <p class="dl-rfeed__text">..</p>
      <div class="dl-prose dl-rfeed__body">..markdown..</div>
      <p class="dl-rfeed__note"><b>Why:</b> ..</p>
      <p class="dl-rfeed__more"><a class="dl-rfeed__readmore" href="..">Read article &rarr;</a></p>
      <footer>                                                    <!-- only when choices is not empty -->
        <div class="dl-rfeed__choose" role="group" aria-label="Decision">
          <button type="button" class="dl-rfeed__btn dl-rfeed__btn--success [is-on]" data-rf-choice="KEEP" aria-pressed="false">Keep</button>
        </div>
        <p class="dl-rfeed__state" data-rf-state>Not decided yet</p>
      </footer>
    </article>
    <p class="dl-rfeed__none" data-rf-none hidden>Nothing matches.</p>
  </div>
</div>
```

## Props

Constructor of `Devlib\Components\ReviewFeed` (11 props: 2 passed to the parent `Feed`, 9 own). Use `:prop="expr"` for arrays and booleans (`:choices="[...]"`, `:search="false"`). Kebab-case in Blade (`:choice-tones`, `save-url`).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `posts` | `array`, `[]` | List of item arrays (shape below). Order is kept. |
| `empty` | `string`, `'Nothing to review.'` | Text shown (`dl-feed__empty`) when `posts` is empty. Translated. |
| `choices` | `array`, `[]` | `code => label` of the decision buttons, in order, for example `['KEEP' => 'Keep', 'DISCARD' => 'Discard']`. The code is what is stored/posted; the label is translated at render time. Empty array: no footer, no buttons, the card is read-only. |
| `choice-tones` | `array`, `[]` | `code => tone` for the colour of the button when selected: `success`, `warn`, `danger`, `accent`, `neutral`. A missing code uses `accent`. Not-selected buttons are always outlined black. |
| `filters` | `array`, `[]` | Chips of the bar: `[['label' => 'All'], ['label' => 'Not decided', 'where' => ['decision' => ['']], 'tone' => 'warn']]`. `where` is `column => value | [values]` matched (as strings, any of the values) against each post's `data` map; no `where` = all posts. The first chip starts active. `label` is translated. Same shape as the `stats` of `x-dl::grid`. |
| `save-url` | `?string`, `null` | URL that receives the decision (POST, JSON). Written to `data-action`. `null`: the buttons do nothing at all (no request, no toast, no visual change), which is how the catalog demo behaves. |
| `csrf` | `?string`, `null` | Token sent as `X-CSRF-TOKEN`. `null` uses `csrf_token()` of the current session, so normally leave it out. |
| `field` | `string`, `'decision'` | Name of the field in the posted item (`{id, <field>: code}`) and of the key in each post's `data` map that the script updates after saving. Change it when the stored column is not called `decision`. |
| `source` | `?string`, `null` | Free text shown by the developer catalog as the data source; default `'daftar butir review + keputusan'`. No visual or runtime effect. |
| `search` | `bool`, `true` | `false` removes the whole bar (chips, search box and counter), for example for one full article. |
| `quick` | `bool`, `true` | `false` keeps the chips and counter but removes the search box (use when the screen already has its own search). Ignored when `search` is `false`. |

Item array keys:

| Key | Meaning |
|---|---|
| `id` | REQUIRED. Item id; written to `data-id` and posted as a number (`+dataset.id`), so use integers. A missing `id` raises an "Undefined array key" error while rendering. |
| `title`, `meta` | `<h2>` and the line under it. Escaped, not translated. |
| `badge`, `tone` | Proposal badge under the header, preceded by the fixed text "Claude proposes". `tone` defaults to `neutral`. |
| `summary`, `text`, `note` | `summary`: large paragraph. `text`: plain text paragraph that keeps line breaks (`white-space:pre-line`). `note`: highlighted paragraph prefixed with "Why:". All escaped. |
| `body` | Markdown (same converter as `Feed`: raw HTML escaped, unsafe links removed), rendered as `dl-prose`. |
| `url` | Adds `Read article →` as a plain link in the same tab. Not filtered. |
| `decision` | Current decision code (`''` = not decided). Sets `is-decided`, `data-decision`, `is-on` and `aria-pressed` on the matching button. |
| `state` | Extra text after the decision in the state line (for example `jo · 13-Oct-2026`); shown only when `decision` is not empty. |
| `data` | Map `column => value` used by the filters (`data-f`). Keep `data[<field>]` equal to `decision`, because the filters read `data`, not `decision`. |

Inherited from `DevlibComponent`: `can="module.action"` (no ability: nothing is rendered) and extra attributes (`class`, `id`, `style`, `data-*`) merged onto the root `div`.

## Behavior

- **Filter chips.** Clicking a chip makes it the only active one (`is-active`) and re-filters. Each chip shows a count (`data-rf-n`), calculated in PHP at render (`ReviewFeed::count()`) and again by the script on start and after every saved decision. Filtering hides cards with the `hidden` attribute and updates "n shown" and the "Nothing matches." line.
- **Search.** Case-insensitive substring on `data-text`, which is built from `title`, `meta`, `summary` and `note` only. The body, `text`, badge and `url` are not searched. Search and the active chip combine (AND).
- **Deciding.** Clicking a button POSTs immediately. While the request runs the card has `is-saving` (opacity .7, no pointer events). On success the buttons, `aria-pressed`, the green left border (`is-decided`), the state line and `data-f` are updated and a success toast `Decision saved` shows. Clicking the already selected button sends an empty value and clears the decision (`Not decided yet`). On failure a danger toast shows the server message or `Decision was not saved` and nothing changes.
- **After saving, the list is not re-filtered.** The script recounts the chips but does not hide the card that just left the active filter, and "n shown" stays; it updates on the next chip click or search keystroke.
- **State line after a save** is `Your decision: <label>`; the `state` text from the server render is dropped until the page is reloaded.
- **Language.** Fixed texts (`Search...`, `shown`, `Claude proposes`, `Why`, `Read article`, `Decision`, `Your decision`, `Not decided yet`, `Nothing matches.`, toast texts) and `choices` / `filters` labels are translated at render time. Item data is not translated.
- **Theme.** Black text on the surface colour, no grey; borders and buttons use `--dl-ink-strong`; sizes follow `--dl-font-scale`. Focus rings are 3px accent outlines on chips and buttons.
- **Accessibility as implemented.** Decision buttons are native buttons in a `role="group"` labelled "Decision" with `aria-pressed`. The search input has `aria-label="Search"`. The chip group has `role="group"` but no label, chips have no `aria-pressed` (only the `is-active` class), the state line and toasts have no `aria-live`.
- **Sticky bar.** `.dl-rfeed__bar` is `position:sticky;top:47px` (devlib.css:3741), an offset that assumes the fixed top bar of the shell.

## Data and events

Request (only when `save-url` is set):

```http
POST {save-url}
Content-Type: application/json
Accept: application/json
X-CSRF-TOKEN: {csrf}

{"changed":[{"id":1,"decision":"KEEP"}]}
```

`decision` is the `field` name; its value is the choice code, or `''` to clear. The server must answer JSON: any non-2xx status, or a body with `error`, is a failure (message = `error`, then `message`). A 2xx response whose body is not JSON is also treated as a failure (the parse error is toasted) although the server may have saved. Use `credentials: same-origin`, so the session cookie and CSRF apply.

Events and hooks: no `dl:*` custom event is emitted. Hooks: `data-dl-review-feed` (root), `data-rf-post` (card), `data-rf-choice="CODE"`, `data-rf-filter` + `data-where`, `data-rf-q`, `data-rf-n`, `data-rf-shown`, `data-rf-none`, `data-rf-state`. `el.dlComponent` is the `ReviewFeed` JS instance (methods `filter()`, `counts()`, `choose(post, code)`, `apply(post, code)`). To react to a save in a project, subclass it (`data-dl-js` + `Devlib.register(class X extends Devlib.classes.ReviewFeed {...})`) and override `apply`.

## Rules and pitfalls

- Always give every item an integer `id` and a `data` map containing the filtered columns, including `data[<field>]`. A chip like "Not decided" with `where => ['decision' => ['']]` matches only through `data`.
- With `filters` present, the first chip is active on load: make it the "All" chip (no `where`), or the list starts filtered.
- Use only the five tones for `choice-tones` (`success`, `warn`, `danger`, `accent`, `neutral`); other values have no `is-on` background and the selected button turns white-on-white.
- `choices` codes are stored values: keep them stable (upper-case codes in the demo); change only labels.
- Keep the route behind auth and CSRF; the component sends no other proof. Validate the code on the server against your allowed list.
- The page that uses the component is a review page, so keep the whole screen black-on-white as the component does; do not add grey helper text.
- Code oddity (not fixed): the chip `tone` is written to the class `dl-rfeed__chip--<tone>` (`src/Components/ReviewFeed.php:74`), but `resources/assets/devlib.css` has no such rule, so the tone has no visual effect (all chips look the same; only `is-active` differs).
- Code oddity (not fixed): `apply()` in `resources/assets/js/components/review-feed.js:69-87` calls `counts()` but not `filter()`, so a card stays visible after leaving the active filter.
- Code oddity (not fixed): when `save-url` is `null`, `choose()` returns silently (`review-feed.js:54`); there is no toast or visual hint that nothing was saved.
- Code oddity (not fixed): the `data-text` search index (`src/Components/ReviewFeed.php:83`) excludes `body` and `text`, so searching for a word that only appears in the body finds nothing.
- Code oddity (not fixed): the text "Claude proposes" is hard coded (`src/Components/ReviewFeed.php:89`) in a generic library object, and the `source()` default and the comments are Indonesian while the rest of the screen text is English.
- Code oddity (not fixed): the header comment (`src/Components/ReviewFeed.php:7-9`) names `resources/views/v2/screens/setup-review.blade.php` and `setup-docs-library.blade.php` as users; they live in the inheriting project, not in this repository.

## Example

Copied in shape from the catalog demo (`site/resources/views/demos/review-feed.blade.php`); fictional documents. Without `save-url` the buttons stay inert, which is how the demo runs:

```blade
<x-dl::review-feed
    :choices="['KEEP' => 'Keep', 'CONDENSE' => 'Condense', 'DISCARD' => 'Discard']"
    :choice-tones="['KEEP' => 'success', 'CONDENSE' => 'warn', 'DISCARD' => 'danger']"
    :filters="[['label' => 'All'], ['label' => 'Not decided', 'where' => ['decision' => ['']], 'tone' => 'warn'], ['label' => 'Proposed discard', 'where' => ['proposal' => 'DISCARD'], 'tone' => 'danger']]"
    :posts="[
        ['id' => 1, 'title' => 'Old audit of the input screens', 'meta' => 'docs/audit_2026_10_02.md · 287 lines · 2 references', 'badge' => 'Discard', 'tone' => 'danger',
         'summary' => 'A one-time audit. The fixes it listed were already done.', 'note' => 'One-off audit; the fixes are finished.',
         'decision' => '', 'data' => ['proposal' => 'DISCARD', 'decision' => '']],
        ['id' => 2, 'title' => 'Inquiry to Master Product flow', 'meta' => 'docs/flow_product_master.md · 120 lines · 9 references', 'badge' => 'Keep', 'tone' => 'success',
         'summary' => 'Explains how an approved inquiry becomes a master product.', 'note' => 'Still correct and referenced often.',
         'decision' => 'KEEP', 'state' => 'reviewer · 13-Oct-2026', 'data' => ['proposal' => 'KEEP', 'decision' => 'KEEP']],
    ]" />
```

A real, saving screen (route returns `{"ok":true}` or `{"error":"..."}`):

```blade
<x-dl::review-feed :save-url="route('review.save')" field="decision"
    :choices="['APPROVE' => 'Approve', 'HOLD' => 'Hold']" :choice-tones="['APPROVE' => 'success', 'HOLD' => 'warn']"
    :filters="[['label' => 'All'], ['label' => 'Open', 'where' => ['decision' => ['']]]]"
    :posts="$items" />
```

A plain article index without decisions and without the bar:

```blade
<x-dl::review-feed :choices="[]" :search="false" :posts="$articles" empty="No articles yet." />
```

## Related

- `x-dl::feed`: the parent object (excerpt cards, Read more); same `posts` / `empty` props and markdown rules.
- `x-dl::badge`: draws the proposal badge.
- `x-dl::grid`, `x-dl::sheet`: their `stats` prop uses the same `label / where / tone` filter shape.
- `x-dl::alert`, toasts (`Devlib.toast`): the save result feedback.
- `skills/devlib/components.md`: generated prop list; `skills/devlib/object.md`: how objects and their JS twins are built.
