## Overview

`<x-dl::announce>` is a small announcement strip: a megaphone icon and one line of text. When it has more than one line it rotates through them on a timer. It is meant for the corner widgets of `x-dl::frame` (the login and welcome backdrop), where the lines come from a text file; it can also be used alone with any array of lines.

With an empty `lines` array the object renders nothing at all (no empty box, and no `data-dl` marker).

Pick another object when the need is different: `x-dl::alert` for a message about the current page or form, `x-dl::quote` for a rotating quotation with author, `x-dl::notification-list` for a list of notifications the user can read and mark.

Generated markup:

```html
<div class="dl-announce" data-dl="x-dl::announce" data-dl-class="Announce" data-dl-src="media/text/pengumuman.txt"
     data-dl-announce data-lines="[&quot;First line&quot;,&quot;Second line&quot;]" data-interval="6">
  <i class="fa-solid fa-bullhorn"></i><span data-dl-announce-text>First line</span>
</div>
```

## Props

Constructor of `Devlib\Components\Announce` (2 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `lines` | `array`, `[]` | The announcements, a list of arrays that each have the key `t` (the text), for example `[['t' => 'Server maintenance on Saturday']]`. Only `t` is used (other keys such as `a` are ignored). The list must start at index 0: the view prints `$lines[0]['t']` first and sends `array_column($lines, 't')` to the script as JSON in `data-lines`. A line without `t` raises an "undefined array key" error. Empty array = render nothing. NOT translated (it is data, not a dictionary text); output is escaped. |
| `interval` | `int`, `6` | Seconds between two lines. Written to `data-interval`. The script uses `+interval \|\| 6`, so `0` or a non-number falls back to 6 seconds. Has no effect with a single line. Prefer `:interval="8"` (bound as integer). |

Extra HTML attributes (`id`, `class`, `style`, `data-*`) are merged onto the root `div`. The attribute `can="module.action"` is supported (base class `DevlibComponent`): without the ability nothing is rendered.

`source()` returns `media/text/pengumuman.txt`, the default file name used by `x-dl::frame`; it only labels the catalog overlay (`data-dl-src`) and does not read anything.

## Behavior

- **One line:** static text, no timer.
- **Several lines:** the script `Announce` (`resources/assets/js/components/announce.js`) starts a `setInterval` and replaces the `textContent` of `[data-dl-announce-text]` with the next line, wrapping from the last to the first. There is no fade or slide, no pause on hover and no manual control.
- **Look:** flex row, 10px gap, dark translucent background `rgba(0,0,0,.25)`, 3px amber left border, amber icon, font 13.5px times the user font scale, `max-width:520px`. The translucent black is designed for a photo or dark backdrop (the `x-dl::frame`); on a plain white page it shows as a grey bar.
- **Long text:** wraps inside the 520px width; the strip grows in height when the lines differ in length, so the layout under it can jump.
- **Language:** none. Write the lines in the language the audience reads; they are not run through the dictionary.
- **Accessibility as implemented:** no `role`, no `aria-live`; the icon has no `aria-hidden`. Screen readers do not get the rotating text announced.
- **Permissions:** `can="module.action"` hides it.

## Data and events

- Input is the `lines` array. `x-dl::frame` builds it with `\Devlib\Components\Frame::textLines($file)` which reads `<media_path>/text/<file>` (`config('devlib.media_path')`, default `public/media`). File format: one announcement per line; empty lines and lines starting with `#` are skipped; an optional `| author` suffix is parsed into `a` (unused here). `x-dl::frame announce="pengumuman.txt"` selects the file (this is the default); `announce@top-left` etc. in its `widgets` prop places the strip.
- Posts nothing. Emits no event.
- Markup hooks: `data-dl-announce` (root, the mount selector), `data-lines` (JSON array of strings), `data-interval`, `data-dl-announce-text` (the text span). `el.dlComponent` is the `Announce` JS instance with `lines`, `txt` and `i` (the current index).
- To change the lines at runtime, set `el.dlComponent.lines = [...]` (the timer reads the property on every tick). The timer only exists when the page rendered more than one line; a strip rendered with one line never starts rotating.
- Override the behavior with `data-dl-js="MyAnnounce"` plus `Devlib.register(class MyAnnounce extends Devlib.classes.Announce {...})`.

## Rules and pitfalls

- `lines` is a list of `['t' => ...]` arrays, not a list of strings. Passing plain strings fails (`$lines[0]['t']` on a string).
- Keep the keys of `lines` as 0, 1, 2, ... (a filtered array with gaps breaks `$lines[0]`); use `array_values()` after `array_filter()`.
- The text is escaped by Blade and written with `textContent` by the script, so HTML in a line is shown as text, not rendered.
- Because the file lines are data, they are never in the Translation Dictionary; keep one file per language if you need a translated set.
- Do not put critical information only in the strip: it rotates, it is not announced to screen readers, and it only exists where the frame widget is placed.
- No lint rule exists for this object (`lint()` returns no findings).
- Code oddity (not fixed): `resources/assets/js/components/announce.js:12` never clears its `setInterval`. If the element is removed from the page and `Devlib.mount` runs again on new content, the old timer keeps running on the detached node.
- Code oddity (not fixed): `src/Components/Announce.php:34` prints `{{ $lines[0]['t'] }}` without a guard, so a non-empty `lines` whose first row lacks `t` (or whose keys do not start at 0) throws instead of rendering nothing.

## Example

```blade
{{-- Used by the frame: lines come from media/text/pengumuman.txt --}}
<x-dl::frame widgets="brand@top-left, clock@top-right, announce@bottom-left" announce="pengumuman.txt">
    ...
</x-dl::frame>

{{-- Alone, with fixed lines (rotates every 8 seconds) --}}
<x-dl::announce :interval="8" :lines="[
    ['t' => 'The warehouse is closed on Friday for stock count.'],
    ['t' => 'New price list is active from the 1st.'],
    ['t' => 'Please change your password every 90 days.'],
]" />

{{-- One line = no rotation --}}
<x-dl::announce :lines="[['t' => 'Scheduled maintenance tonight 22:00.']]" />
```

Example of the text file `public/media/text/pengumuman.txt` (the part after `|` is optional and ignored here):

```text
# lines starting with # are comments
The warehouse is closed on Friday for stock count.
New price list is active from the 1st. | Sales Admin
```

## Related

- `x-dl::frame`: the backdrop that places this strip as a widget and supplies `lines` from the text file.
- `x-dl::quote`: rotating quotations (also fed by `Frame::textLines`), with author.
- `x-dl::alert`: a message about the page or form the user is working on.
- `x-dl::notification-list`: notifications with read/unread state.
- `skills/devlib/components.md` (generated prop list), `skills/devlib/object.md` (JS twin and `data-dl-js` override).
