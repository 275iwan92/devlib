## Overview

`<x-dl::suggestion-strip>` is a one-row strip, designed to sit above a Gantt, that shows a machine-learning suggestion. DevLib only DISPLAYS it; the app computes the suggestion (for example a job sequence) and supplies the URLs. It has two modes:

- `ready`: an `ML` badge, a title, an optional subtitle, the proposed order as numbered chips joined by arrows, a note line, and the buttons Preview / Use / Dismiss;
- `learning`: the `ML` badge, a title, the text `Learning n / needed`, a progress bar and the note line (no buttons).

Use it for model suggestions that the user may accept or ignore. Pick another object when:

- the message is a plain notice (info, warning): `x-dl::alert`;
- the user must fill or confirm data in a dialog: `x-dl::modal`;
- you only need a progress bar: `x-dl::progress`.

Generated markup (mode `ready`):

```html
<section class="dl-seqstrip dl-seqstrip--ready" data-dl="x-dl::suggestion-strip mode=ready" data-dl-class="SuggestionStrip ← DevlibComponent" data-dl-src="—">
  <div class="dl-seqstrip__main">
    <span class="dl-badge dl-badge--violet dl-badge--pill">ML</span>
    <b class="dl-seqstrip__title">Suggested order</b>
    <span class="dl-seqstrip__sub">saves about 40 minutes</span>          <!-- only when subtitle is not '' -->
    <span class="dl-seqstrip__chips">
      <span class="dl-seqstrip__chip">1 JOB-101</span><i class="fa-solid fa-arrow-right"></i><span class="dl-seqstrip__chip">2 JOB-102</span>
    </span>
  </div>
  <p class="dl-seqstrip__note">Based on the last 30 runs.</p>               <!-- always rendered, even when empty -->
  <div class="dl-seqstrip__actions">                                       <!-- only in ready mode, each button only with its URL -->
    <a class="dl-btn ...">Preview</a>
    <form method="post" action="..."> (csrf) <button type="submit" class="dl-btn ...">Use</button></form>
    <form method="post" action="..."> (csrf) <button type="submit" class="dl-btn ...">Dismiss</button></form>
  </div>
</section>
```

In `learning` mode the chips and the actions are replaced by `<div class="dl-seqstrip__learn"><span class="dl-seqstrip__count">Learning 12 / 30</span> + an x-dl::progress bar</div>`.

## Props

Constructor of `Devlib\Components\SuggestionStrip` (11 props, none inherited). Use `:prop="expr"` for arrays, numbers and booleans.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `mode` | `string`, `'ready'` | `ready` or `learning`. Adds the class `dl-seqstrip--<mode>` and decides which parts render. Any other value renders only badge, title, subtitle and note (no chips, no progress, no buttons) and the data-dl tag shows `mode=<value>`. |
| `title` | `string`, `''` | Bold title after the badge. Translated (it is in this class's `TEXT_PROPS`). |
| `subtitle` | `string`, `''` | Small text next to the title; the element exists only when not `''`. NOT translated (this class limits translated props to `title` and `note`). |
| `items` | `array`, `[]` | List of strings, in order, shown as numbered chips `1 label`, `2 label`... with an arrow between them. `ready` mode only. Escaped, not translated (data). Must be a list (keys 0..n-1). |
| `note` | `string`, `''` | Explanation line under the main row, full width. Translated. The `<p>` is always printed, also when empty. |
| `value` | `float`, `0` | `learning` mode: how many samples/records the model has learned from. Shown as `(int) value`; also the numerator of the progress bar. |
| `needed` | `float`, `0` | `learning` mode: how many are needed to start suggesting. Shown as `(int) needed`. When `needed <= 0` the bar is 0%. |
| `preview-url` | `?string`, `null` | `ready` mode: adds the Preview button, a normal link (GET) to this URL. `null` = no button. |
| `use-url` | `?string`, `null` | `ready` mode: adds the Use button, a POST form (CSRF included) to this URL. `null` = no button. |
| `dismiss-url` | `?string`, `null` | `ready` mode: adds the Dismiss button (secondary), a POST form to this URL. `null` = no button. |
| `previewing` | `bool`, `false` | Only changes the Preview button label to `Hide preview`. The link target stays `preview-url`, so the app must pass the URL that turns the preview off when `previewing` is true. |

Inherited from `DevlibComponent` (no constructor props): `can="module.action"` (without the ability nothing is rendered) and extra attributes (`class`, `id`, `style`, `data-*`) merged onto the root `<section>`.

## Behavior

- **Colors.** Lavender background from the token `--dl-violet-soft`, border `--dl-violet-line`, black text (`--dl-ink-strong` / `--dl-ink`); the chips are white pills with a violet border. The `ML` badge uses the `violet` tone. Everything follows the theme tokens and `--dl-font-scale` (note line is 12px scaled).
- **Layout.** `display:flex; flex-wrap:wrap`. The main block takes at least 420px; the actions are pushed to the right (`margin-left:auto`); the note always occupies a full line (`flex:1 1 100%`).
- **Progress.** In learning mode the bar is `x-dl::progress` with `value / needed * 100`, accent tone, no text; the count text is translated (`Learning`) and truncates decimals with `(int)`. Progress clamps the bar to 0..100%.
- **Buttons.** `x-dl::button` size `sm`: Preview (secondary, eye icon, link), Use (primary, check icon, submit), Dismiss (secondary, xmark icon, submit). Button labels `Preview`, `Hide preview`, `Use`, `Dismiss` and the badge `ML` are fixed English terms: button/badge text is not run through the dictionary unless the app sets `devlib.translate_fixed`.
- **No JS.** The strip has no script, no state and no keyboard behavior of its own; Use and Dismiss are ordinary form submits (full page reload), Preview is an ordinary link.
- **Accessibility as implemented.** A `<section>` without `aria-label` or `role`, no `aria-live`; the arrows between chips are `<i>` icons without `aria-hidden`. Buttons are native `<a>` / `<button>`.

## Data and events

- Input: the props above, computed by the app (for example `items` = job labels in the proposed sequence, `value`/`needed` from the training counter).
- Output: Preview issues `GET preview-url`. Use and Dismiss issue `POST` with the `_token` field and no other field to `use-url` / `dismiss-url`; the controller must know what to apply from the URL itself (put the ids in the route or the query string of the URL).
- No `dl:*` event, no JS twin, no `el.dlComponent`. The root carries only the catalog attributes (`data-dl`, `data-dl-class`, `data-dl-src`).
- Re-render the page (or the part containing the strip) after Use/Dismiss; there is no client side removal.

## Rules and pitfalls

- Keep the model and its sequence out of DevLib: DevLib draws, the app computes. Never hard-code job or customer names in the labels of a reusable screen; pass them from data (wrap data inside translated text with `raw()`).
- Pass `items` as a list (`array_values(...)`). The view computes `$i + 1` from the array key, so an associative array breaks the render.
- Give `needed` a value above 0 in learning mode, otherwise the bar stays empty and the text reads `Learning n / 0`.
- Use and Dismiss are POST: the receiving route must accept a form POST with CSRF, and redirect back to the screen.
- Do not use `previewing` without a matching URL: the same `preview-url` is used for both states.
- Code oddity (not fixed): the header comment says `Output: ... kosong bila tidak ada isi` (empty when there is no content), but `render()` has no such condition (`src/Components/SuggestionStrip.php:45-73`): the section and the note `<p>` are always printed.
- Code oddity (not fixed): `tag()` returns `'x-dl::suggestion-strip mode='.$this->mode` (`SuggestionStrip.php:39`), so `data-dl` carries a space and the mode, unlike most objects whose `data-dl` is the bare tag.
- Code oddity (not fixed): the object has no demo in `site/resources/views/demos` and is not in `Catalog`; the only user named in the header (`layar Work Order PPIC (Simulation)`) is in an inheriting project.

## Example

There is no catalog demo; the shapes below follow the view (fictional jobs).

Ready, with all three actions:

```blade
<x-dl::suggestion-strip
    title="Suggested job order"
    subtitle="expected saving: 40 minutes"
    :items="['JOB-101', 'JOB-102', 'JOB-103']"
    note="Based on the last 30 completed runs on this machine."
    :preview-url="route('sim.preview', ['machine' => $machine->id])"
    :use-url="route('sim.use', ['machine' => $machine->id])"
    :dismiss-url="route('sim.dismiss', ['machine' => $machine->id])" />
```

Preview is on (the link now turns it off):

```blade
<x-dl::suggestion-strip title="Suggested job order" :items="$labels" note="Preview is shown on the chart."
    :previewing="true" :preview-url="route('sim.index', ['machine' => $machine->id])"
    :use-url="route('sim.use', ['machine' => $machine->id])" :dismiss-url="route('sim.dismiss', ['machine' => $machine->id])" />
```

Learning:

```blade
<x-dl::suggestion-strip mode="learning" title="Learning your sequence" :value="12" :needed="30"
    note="The suggestion starts after 30 completed runs." />
```

## Related

- `x-dl::button`: draws Preview / Use / Dismiss (size `sm`).
- `x-dl::action-form`: the POST form (with CSRF) wrapped around Use and Dismiss.
- `x-dl::progress`: the bar of the learning mode.
- `x-dl::badge`: the violet `ML` badge.
- `x-dl::gantt`: the chart this strip is placed above (the preview bars are drawn by the Gantt).
- `x-dl::alert`: for plain notices that carry no action.
- `skills/devlib/components.md`: generated prop list.
