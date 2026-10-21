## Overview

`<x-dl::quote>` shows one motivational quote (text plus author) taken from a list. The list is embedded in the page and one entry is picked by a small script: at random, in sequence (continuing at each page load), or by the day of the year; optionally it rotates every N seconds. It is a decorative widget for login/register frames and the dashboard.

Use it where a short inspirational line fits: the corner of `x-dl::frame` (widget `quote`) or the "quote of the day" tile of `x-dl::dashboard`. Pick another object when:

- the text is an operational message that people must read, with a start and end date: `x-dl::announce` (scrolling lines) or `x-dl::alert`;
- a greeting by time of day or user: `x-dl::greeting`;
- a one-off message after an action: `Devlib.toast(...)`.

Generated markup:

```html
<div class="dl-quote" data-dl="x-dl::quote order=random" data-dl-class="Quote ← DevlibComponent" data-dl-src="media/text/*.txt (satu baris = &quot;teks | penulis&quot;)"
     data-dl-quote data-quotes='[{"t":"...","a":"..."}]' data-order="random" data-interval="0">
  <div class="qi">&#8220;</div>
  <div class="q" data-dl-quote-text>—</div>
  <div class="a" data-dl-quote-author>—</div>
</div>
```

The "—" placeholders are replaced by the script on page load. Extra HTML attributes (`id`, `class`, `style`, `data-*`) are merged on the root `div`. `can="module.action"` is read by the base class: without the ability nothing is rendered.

## Props

Constructor of `Devlib\Components\Quote` (3 props). Blade names are kebab-case; arrays and numbers need `:prop="expr"`.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `quotes` | `array`, `[]` | The list: `[['t' => 'quote text', 'a' => 'author'], ...]` (key `t` = text, `a` = author). The whole list is written as JSON into `data-quotes`, so every quote travels with the page (a 366-line list is a few tens of KB). Not translated: it is data. With an empty list the script hides the widget (`display:none`). The array must be a list; an associative array becomes a JSON object and the widget is hidden. An entry without `a` shows no author line. Elements that are plain strings show an empty text, because the script reads `.t`. |
| `order` | `string`, `'random'` | How the first quote is chosen. `random`: a random entry at each page load. `sequence`: the next entry after the one shown last time, remembered in the browser (`localStorage` key `dl-seq-quote`, shared by all quote widgets of the same origin); starts with the first entry. `day`: the entry for today's date: the list is meant to have 366 lines, line n = day n of the year counted in a leap year, so line 60 = 29 February (fewer lines wrap around with modulo). Any other value behaves like `random`. |
| `interval` | `int`, `0` | Seconds between automatic changes. `0` = fixed for the whole page view. `> 0`: the script moves to the next entry (`i + 1`, wrapping at the end) every `interval` seconds, whatever `order` was used to pick the first one. Bind as an integer (`:interval="30"`). |

## Behavior

- Script `resources/assets/js/components/quote.js` (class `Devlib.classes.Quote`, selector `[data-dl-quote]`). On start it reads the JSON, hides the widget if the list is empty, picks the index with `Devlib.h.pickIndex(length, order, 'quote')`, writes the text and the author, and starts the timer when `interval > 0`.
- Text is written with `textContent`, so quotes can never inject HTML. The author line is hidden (`display:none`) when `a` is empty; the CSS prefixes the author with "— " itself, so do not type the dash in the data.
- `day` order: the date is taken in the page time zone (`Devlib.h.TZ`); the index is the day count from 1 January of a fixed leap year, so the same calendar date always gives the same line, in leap and non-leap years.
- Look: white text (`.dl-quote{color: var(--dl-white)}`) with a large pale opening mark, italic 21px quote (max-width 620px) and a bold author; sizes follow `--dl-font-scale`. It is meant for a dark or image background (the login frame). On a light surface you must override the colors; the dashboard does it (`.dl-dash__quote .dl-quote`: ink color, 15.5px, two-column layout, mark in amber).
- Language: nothing is translated; write quotes in the language wanted. The widget has no label text of its own.
- Accessibility: plain text divs; no `role`, no `aria-live`, so rotations are not announced and the widget is decoration. Until the script runs the box shows "—".
- Not tied to a user or permission data; `can` can hide it.

## Data and events

- Input: the array `quotes`. Typical source is a text file in the media folder (`config('devlib.media_path')`, default `public/media`, sub-folder `text`), read with `Devlib\Components\Frame::textLines('file.txt')`: one quote per line, `text | author` (split on the first `|`, so the text cannot contain `|`); blank lines and lines starting with `#` are skipped; a missing file gives an empty list (widget hidden). `x-dl::frame` does this itself from its `quotes` file name (default `motivasi.txt`) together with `quote-order` and `quote-interval`; `x-dl::dashboard` takes `$d['quotes']` (same `t`/`a` shape) and uses `order="day"`.
- Output: only text on screen. Nothing is posted. The only stored state is the `dl-seq-quote` counter for `order="sequence"`.
- No `dl:*` event is emitted. `el.dlComponent` is the `Quote` instance (properties `list`, `i`, `sec`; method `show()`).
- Markup hooks: `data-dl-quote`, `data-quotes`, `data-order`, `data-interval`, `data-dl-quote-text`, `data-dl-quote-author`, and the classes `.qi` (mark), `.q` (text), `.a` (author).

## Rules and pitfalls

- Keep the list in a text file or a config, not in the screen: the quotes are data, and the file format `text | author` is the project standard (`source()` of the object says so).
- Give a 366-line file for `order="day"` (365 lines work, but line 366 of a leap year then repeats line 1). The dashboard shows the day number next to the quote and uses 365 as the maximum when there are 366 lines.
- Do not put the widget on a light background without styling it: the text is white.
- Do not use it for notices that people have to see; it can be hidden, changes at random and is not announced.
- Large lists inflate every page that renders the widget (the full JSON is in the HTML). Keep it to a few hundred short lines.
- Do not wrap data in `raw()` here: the quotes are not translated at all.
- No lint rule is specific to this object.
- Code oddity (not fixed): `interval` always advances sequentially (`resources/assets/js/components/quote.js:12`), so `order="random"` with `interval > 0` is random only for the first quote and then walks the list in order.
- Code oddity (not fixed): the timer started with `setInterval` (`resources/assets/js/components/quote.js:12`) is never cleared, so a widget removed from the DOM (for example a re-rendered widget board) keeps its timer running.
- Code oddity (not fixed): `localStorage` key `dl-seq-quote` is shared by every quote widget in the same browser origin (`resources/assets/js/core.js:299`), so two widgets with `order="sequence"` on different pages advance each other's position.
- Code oddity (not fixed): the REMARK of `src/Components/Quote.php:5` says the quotes come from `x-dl::frame quotes="..."`, but `Frame` gives only the file name; the lines are read by `Frame::textLines()` in `frame.blade.php:24`, and `Quote` itself never reads a file.

## Example

Taken in shape from the login frame demo (`login-1`) and the dashboard view (fictional quotes):

```blade
{{-- login/register frame: the quote widget in the bottom-left corner, read from media/text/motivasi.txt --}}
<x-dl::frame bg="devlib" box="right" color="light" widgets="brand@top-left, clock@top-right, quote@bottom-left" quote-order="sequence" :quote-interval="30">
    ...
</x-dl::frame>

{{-- quote of the day on a dashboard tile: 366 entries, line = day of the year --}}
<x-dl::quote :quotes="$d['quotes']" order="day" />

{{-- direct use with an inline list --}}
<x-dl::quote :quotes="[
    ['t' => 'Well begun is half done.', 'a' => 'Anonymous'],
    ['t' => 'Small steps every day.', 'a' => ''],
]" order="sequence" :interval="20" />
```

Matching line format of `media/text/motivasi.txt`:

```text
# one quote per line: text | author
Well begun is half done. | Anonymous
Small steps every day.
```

## Related

- `x-dl::frame`: login/register frame whose `quote` widget builds this object from a text file (`quotes`, `quote-order`, `quote-interval`).
- `x-dl::dashboard`: shows the quote of the day (`order="day"`) with the day counter.
- `x-dl::announce`: scrolling operational lines from the same kind of text file.
- `x-dl::clock`, `x-dl::greeting`, `x-dl::weather`, `x-dl::brand`: the other frame widgets.
