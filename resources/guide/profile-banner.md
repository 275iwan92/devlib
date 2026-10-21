## Overview

`<x-dl::profile-banner>` is the standard header of a profile screen (person, customer, supplier, machine). Top block: an optional photo on the left, and next to it the large name, a sub-line, labelled groups of status chips and a completeness bar. Under it, full width: an optional row of KPI tiles, then an optional one-line notice. The object only lays out what the screen gives it; it reads no database and computes nothing (the screen computes completeness, KPI values and notices).

Use it once at the top of a profile or master-detail screen, typically in the `strip` slot of `x-dl::form`. Do NOT use it for a document summary of number, date, party and total (use `x-dl::strip-kv`), for a single status label (`x-dl::badge`), or for a card inside a list.

### Markup

```html
<div class="dl-pbanner" data-dl="x-dl::profile-banner" ...>
  <div class="dl-pbanner__top">
    <div class="dl-pbanner__photo"> photo slot </div>                      <!-- only with the photo slot -->
    <div class="dl-pbanner__id">
      <div class="dl-pbanner__name">Name</div>
      <div class="dl-pbanner__sub">Sub-line</div>                           <!-- only with subtitle -->
      <div class="dl-pbanner__grp"><span class="dl-pbanner__gl">Group label</span><span class="dl-pbanner__chips"> x-dl::badge ... </span></div>
      <div class="dl-pbanner__grp"><span class="dl-pbanner__gl">Profile</span><span class="dl-pbanner__prog"> x-dl::progress </span><span class="dl-pbanner__progtxt">text</span></div>
    </div>
  </div>
  <div class="dl-pbanner__kpis"> kpis slot </div>                           <!-- only with the kpis slot -->
  <div class="dl-pbanner__notice"> notice slot </div>                       <!-- only with the notice slot -->
</div>
```

Extra HTML attributes (`id`, `class`, `style`, `data-*`) are merged into the root; `can="module.action"` hides the whole banner without the ability. The object has no script.

## Props

Constructor of `Devlib\Components\ProfileBanner` (5 props). Use `:prop="expr"` for arrays and numbers.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `name` | `string`, `''` | The large name line (22px bold). Printed as given and not translated, so a real name needs no `raw()`. Empty prints an empty line. |
| `subtitle` | `?string`, `null` | Sub-line under the name (code, position, department...). Not rendered when empty. It is a standard text prop, so it IS translated through the dictionary: wrap text that contains data in `raw()` (`:subtitle="raw($code.' · '.$position)"`). |
| `groups` | `array`, `[]` | Labelled chip groups, one row each: `[['label' => 'Status', 'badges' => [['label' => 'Working', 'tone' => 'success', 'icon' => 'fa-lock']]]]`. `label` (required key) is the row label, translated (shown upper-case, 84px column). Each badge needs `label`; `tone` is optional (default `neutral`; `neutral`, `accent`, `success`, `warn`, `danger`, `info`, `violet`), `icon` is optional (a Font Awesome class such as `fa-lock`). A group with no `badges` (missing or empty) is skipped, so the screen can pass groups unconditionally. Badge labels are data and are not translated (badge terms stay English). |
| `progress` | `?float`, `null` | Completeness 0 to 100. `null` hides the whole row; `0` shows an empty bar. Drawn by `x-dl::progress` with `tone="auto"`: 80 and above green, 50 and above yellow, below 50 red (the bar width is clamped to 0..100, the colour uses the raw value). The row label is the fixed text `Profile`. The bar has no number of its own: put the text in `progress-text`. |
| `progress-text` | `?string`, `null` | Text after the bar, for example `46% complete · 7 items to go`. Shown only when `progress` is not `null` and the text is not empty. Not a translated prop (write it already in the display language, or translate it in the screen). |

Slots (named slots, not props):

| Slot | Content |
|---|---|
| `photo` | Normally `<x-dl::photo-box>` or `<x-dl::avatar>`. Rendered in a fixed (non-shrinking) box left of the identity block. Without it the identity block uses the full width. |
| `kpis` | Normally `<x-dl::kpi-row>` with `x-dl::kpi` tiles; CSS lays them in an auto-fit grid (minimum 140px per tile) full width. |
| `notice` | Normally one `<x-dl::alert>`; the bottom margin of the alert is removed. |

## Behavior

- Layout is flexible, not fixed in columns: the top block wraps (`flex-wrap`), the identity column takes at least 320px (`flex: 1 1 320px`), so on a narrow window the identity block drops below the photo. The KPI tiles use `repeat(auto-fit, minmax(140px, 1fr))`. The banner itself is `flex: 1 1 100%` so it fills the strip of a form.
- Chips inside the banner are slightly larger than normal badges (12px, padding 4px 11px); groups stay on one row and wrap when needed.
- Static: no script, no event, no state. The screen re-renders it with new data.
- Language: the `groups[].label` texts, the fixed row label `Profile` and `subtitle` go through the dictionary. `name`, badge labels and `progress-text` do not.
- Accessibility as implemented: plain `div`/`span` text without landmarks or `aria-*`; the name is not a heading element. The photo box and the alert keep their own semantics. Colour carries the completeness (green, yellow, red) and the badge tones, so keep the badge text meaningful.

## Data and events

- Nothing is posted by the banner itself. When it sits inside a form, the `photo-box` in its `photo` slot posts its file input as described in its entry.
- No `dl:*` event and no `el.dlComponent` (there is no script).

| Hook | Where |
|---|---|
| `.dl-pbanner__top`, `__photo`, `__id` | top block, photo box, identity column. |
| `.dl-pbanner__name`, `__sub` | name and sub-line. |
| `.dl-pbanner__grp`, `__gl`, `__chips` | a chip group, its label and its chips. |
| `.dl-pbanner__prog`, `__progtxt` | completeness bar and its text. |
| `.dl-pbanner__kpis`, `__notice` | KPI row and notice row. |

## Rules and pitfalls

- Pass groups in a fixed order and always give each group a `label`: the key is read without a check, so a group without `label` fails with an undefined array key error (a group without `badges` is fine).
- Badge tones are the badge tones (`neutral`, `accent`, `success`, `warn`, `danger`, `info`, `violet`); for a document status use `Badge::forStatus($status)` to get the standard tone.
- Wrap data in `raw()` for `subtitle` (see Props). `name` needs no wrapper, but a `title` or any other translated prop of a neighbouring object does.
- Put the banner once per screen, in the `strip` slot of a panel `x-dl::form` (`panel="top"`; the strip locks with the form) or at the top of a page. Do not nest banners.
- Compute completeness and KPI values on the server; the banner does not validate that `progress` is in 0..100.
- Lint: no own rule. L001 applies to the screen Blade (no hand-written `<table>`, `<button>`, `<input>`, `<select>`, `<textarea>`, `<form>` in the slots), L003 to hex colours.
- Code oddity (not fixed): the file header of `src/Components/ProfileBanner.php` says the layout is "grid dua kolom" (two-column grid), but `.dl-pbanner__top` in `resources/assets/devlib.css` is a wrapping flex row, not a grid.
- Code oddity (not fixed): the header (REMARK and `source()`) names a project screen and a project table, against the neutrality rule for library files.
- Code oddity (not fixed): in the demo `site/resources/views/demos/profile-banner.blade.php` the data-bearing `subtitle` is not wrapped in `raw()`, so it is sent to the dictionary as if it were a fixed text.

## Example

Copied in shape from the catalog demo (fictional data).

```blade
<x-dl::profile-banner name="Rina Wijaya" subtitle="EMP-0002 · Planning Specialist · Prepress" :progress="46" progress-text="46% complete · 7 items to go"
    :groups="[
        ['label' => 'Status', 'badges' => [['label' => 'Working', 'tone' => 'success'], ['label' => 'Non-staff', 'tone' => 'info', 'icon' => 'fa-lock']]],
        ['label' => 'Employment', 'badges' => [['label' => 'PERMANENT'], ['label' => 'Joined 01-Jun-2021']]],
    ]">
    <x-slot:photo><x-dl::photo-box name="banner_photo" label="Rina Wijaya" /></x-slot:photo>
    <x-slot:kpis>
        <x-dl::kpi-row>
            <x-dl::kpi label="Tenure" value="5y 4m" icon="fa-hourglass-half" tone="success" />
            <x-dl::kpi label="Leave days left" value="12" icon="fa-umbrella-beach" />
            <x-dl::kpi label="Discipline" value="Verbal 6/6" icon="fa-gavel" tone="danger" />
        </x-dl::kpi-row>
    </x-slot:kpis>
    <x-slot:notice><x-dl::alert tone="warn" message="To complete: Family card (Profile), Father, Mother (Family)" /></x-slot:notice>
</x-dl::profile-banner>
```

Inside a form strip:

```blade
<x-dl::form panel="top" title="Employee" :actions="['Save' => 'fa-floppy-disk']" enctype="multipart/form-data">
    <x-slot:strip>
        <x-dl::profile-banner :name="$employee->name" :subtitle="raw($employee->code.' · '.$employee->position)" :progress="$completeness" />
    </x-slot:strip>
    ...
</x-dl::form>
```

## Related

- `x-dl::photo-box`: the usual content of the `photo` slot (crop and upload).
- `x-dl::avatar`: read-only picture or initials for the `photo` slot.
- `x-dl::badge`, `x-dl::progress`: the chips and the bar the banner draws itself.
- `x-dl::kpi-row`, `x-dl::kpi`: the content of the `kpis` slot.
- `x-dl::alert`: the content of the `notice` slot.
- `x-dl::form`: the `strip` slot that normally holds the banner.
- `x-dl::strip-kv`: summary strip for documents (number, date, party, total).
- `skills/devlib/components.md`: generated prop list.
